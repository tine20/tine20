/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.namespace('Tine.Tinebase');

require('widgets/grid/AttachmentRenderer');
require('widgets/grid/ImageRenderer');

/**
 * Tinebase Application Starter
 * 
 * @namespace   Tine.Tinebase
 * @function    Tine.Tinebase.ApplicationStarter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Tinebase.ApplicationStarter = {
    
    /**
     * the applictions the user has access to
     * @type
     */
    userApplications: null,
    
    /**
     * type mapping
     * @type {Object}
     */
    types: {
        'date':     'date',
        'datetime': 'date',
        'datetime_separated_date': 'date',
        'datetime_separated_time': 'date',
        'time':     'date',
        'string':   'string',
        'text':     'string',
        'boolean':  'bool',
        'integer':  'int',
        'float':    'float'
    },
    
    /**
     * initializes the starter
     */
    init: function() {
        // Wait until appmgr is initialized
        if (! Tine.Tinebase.hasOwnProperty('appMgr')) {
            this.init.defer(100, this);
            return;
        }
        
        Tine.log.info('ApplicationStarter::init');
        
        if (! this.userApplications || this.userApplications.length == 0) {
            this.userApplications = Tine.Tinebase.registry.get('userApplications');
            this.createStructure(true);
        }
    },
    
    /**
     * returns the field
     * 
     * @param {Object} fieldDefinition
     * @return {Object}
     */
    getField: function(fieldDefinition, key) {
        // default type is auto
        var field = {
            name: key,
            fieldDefinition: fieldDefinition
        };
        
        if (fieldDefinition.type) {
            // add pre defined type
            field.type = this.types[fieldDefinition.type];
            switch (fieldDefinition.type) {
                case 'datetime_separated_date':
                case 'datetime':
                case 'date':
                    field.dateFormat = Date.patterns.ISO8601Long;
                    break;
                case 'time':
                    field.dateFormat = Date.patterns.ISO8601Time;
                    break;
                case 'record':
                case 'records':
                    field.type = fieldDefinition.config.appName + '.' + fieldDefinition.config.modelName;
                    field.getRecordClass = function() {
                        return Tine.Tinebase.data.RecordMgr.get(field.type);
                    }
                    break;
            }
            // allow overwriting date pattern in model
            if (fieldDefinition.hasOwnProperty('dateFormat')) {
                field.dateFormat = fieldDefinition.dateFormat;
            }
            
            if (fieldDefinition.hasOwnProperty('label')) {
                field.label = fieldDefinition.label;
            }
        }
        
        // TODO: create field registry, add fields here
        return field;
    },
    /**
     * returns the grid renderer
     * @param {Object} config
     * @param {String} field
     * @return {Function}
     */
    getGridRenderer: function(config, field, appName, modelName) {
        var gridRenderer = null;
        if (config && field) {
            switch (config.type) {
                case 'record':
                    if (Tine.Tinebase.common.hasRight('view', config.config.appName, config.config.modelName.toLowerCase())) {
                        if (config.config.appName == appName && config.config.modelName == modelName) {
                            // pointing to same model
                            gridRenderer = function (value, row, record) {
                                var title = value && config.config.titleProperty ? value[config.config.titleProperty] : '';
                                return Ext.util.Format.htmlEncode(title);
                            };
                        } else {
                            gridRenderer = function (value, row, record) {
                                var foreignRecordClass = Tine[config.config.appName].Model[config.config.modelName];
                                if (foreignRecordClass) {
                                    var titleProperty = foreignRecordClass.getMeta('titleProperty');
                                    return record && record.get(field) ? Ext.util.Format.htmlEncode(record.get(field)[titleProperty]) : '';
                                } else {
                                    return value;
                                }
                            };
                        }
                    } else {
                        gridRenderer = null;
                    }
                    break;
                case 'integer':
                case 'float':
                    if (config.hasOwnProperty('specialType')) {
                        switch (config.specialType) {
                            case 'bytes1000':
                                gridRenderer = function(value, cell, record) {
                                    return Tine.Tinebase.common.byteRenderer(value, cell, record, 2, true);
                                };
                                break;
                            case 'bytes':
                                gridRenderer = function(value, cell, record) {
                                    return Tine.Tinebase.common.byteRenderer(value, cell, record, 2, false);
                                };
                                break;
                            case 'minutes':
                                gridRenderer = Tine.Tinebase.common.minutesRenderer;
                                break;
                            case 'seconds':
                                gridRenderer = Tine.Tinebase.common.secondsRenderer;
                                break;
                            case 'percent':
                                gridRenderer = function(value, cell, record) {
                                    return Tine.Tinebase.common.percentRenderer(value, config.type);
                                };
                                break;
                            case 'durationSec':
                                gridRenderer = function(value, cell, record) {
                                    return Ext.ux.form.DurationSpinner.durationRenderer(value, {
                                        baseUnit: 'seconds'
                                    });
                                };
                                break;
                            default:
                                gridRenderer = Ext.util.Format.htmlEncode;
                        }

                        gridRenderer = gridRenderer.createSequence(function(value, metadata, record) {
                            if (metadata) {
                                metadata.css = 'tine-gird-cell-number';
                            }
                        });

                    }
                    break;
                case 'user':
                    gridRenderer = Tine.Tinebase.common.usernameRenderer;
                    break;
                case 'keyfield': 
                    gridRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get(appName, config.name);
                    break;
                case 'datetime_separated_date':
                case 'date':
                    gridRenderer = Tine.Tinebase.common.dateRenderer;
                    break;
                case 'datetime':
                    gridRenderer = Tine.Tinebase.common.dateTimeRenderer;
                    break;
                case 'time':
                    gridRenderer = Tine.Tinebase.common.timeRenderer;
                    break;
                case 'tag':
                    gridRenderer = Tine.Tinebase.common.tagsRenderer;
                    break;
                case 'container':
                    gridRenderer = Tine.Tinebase.common.containerRenderer;
                    break;
                case 'boolean':
                    gridRenderer = Tine.Tinebase.common.booleanRenderer;
                    break;
                case 'money':
                    if (config.hasOwnProperty('specialType')) {
                        if (config.specialType == 'zeroMoney') {
                            // if this option is set, zero values are hidden in the grid
                            gridRenderer = function (value) {
                                return Ext.util.Format.money(value, {zeroMoney: true});
                            }
                            break;
                        }
                    }
                    gridRenderer = Ext.util.Format.money;
                    break;
                case 'attachments':
                    gridRenderer = Tine.widgets.grid.attachmentRenderer;
                    break;
                case 'image':
                    gridRenderer = Tine.widgets.grid.imageRenderer;
                    break;
                case 'json':
                    gridRenderer = Tine.widgets.grid.jsonRenderer;
                    break;
                case 'relation':
                    var cc = config.config;
                    
                    if (cc && cc.type && cc.appName && cc.modelName) {
                        gridRenderer = new Tine.widgets.relation.GridRenderer({
                            appName: appName,
                            type: cc.type,
                            foreignApp: cc.appName,
                            foreignModel: cc.modelName
                        });
                        break; 
                    }
                default:
                    gridRenderer = Ext.util.Format.htmlEncode;
            }
        }
        return gridRenderer;
    },

    /**
     * used in getFilter for mapping types to filter
     * 
     * @type 
     */
    filterMap: function(type, fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
        switch (type) {
            case 'string':
            case 'text':
                break;
            case 'attachments':
                filter.label = window.i18n._('Attachment');
            case 'fulltext':
                filter.valueType = 'fulltext';
                break;
            case 'user':
                filter.valueType = 'user';
                break;
            case 'boolean': 
                filter.valueType = 'bool';
                filter.defaultValue = false;
                break;
            case 'record':
                filterconfig.options.modelName = filterconfig.options.modelName.replace(/_/, '');
                var foreignApp = filterconfig.options.appName;
                var foreignModel = filterconfig.options.modelName;
                
                // create generic foreign id filter
                var filterclass = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
                    foreignRecordClass: foreignApp + '.' + foreignModel,
                    linkType: 'foreignId',
                    ownField: fieldconfig.key,
                    label: filter.label
                });
                // register foreign id field as appName.modelName.fieldKey
                var fc = appName + '.' + modelName + '.' + fieldconfig.key;
                Tine.widgets.grid.FilterToolbar.FILTERS[fc] = filterclass;
                filter = {filtertype: fc};
                break;
            case 'tag': 
                filter = {filtertype: 'tinebase.tag', app: appName};
                break;
            case 'container':
                var applicationName = filterconfig.appName ? filterconfig.appName : appName;
                var modelName = filterconfig.modelName ? filterconfig.modelName : modelName;
                filter = {
                    filtertype: 'tine.widget.container.filtermodel', 
                    app: applicationName, 
                    recordClass: applicationName + '.' + modelName,
                    field: fieldconfig.key,
                    label: fieldconfig.label,
                    callingApp: appName
                };
                break;
            case 'keyfield':
                filter.filtertype = 'tine.widget.keyfield.filter';
                filter.app = {name: appName};
                filter.keyfieldName = fieldconfig.name;
                break;
            case 'date':
            case 'datetime':
            case 'datetime_separated_date':
                filter.valueType = 'date';
                break;
            case 'time':
                filter.valueType = 'time';
                break;
            case 'money':
                filter.valueType = 'money';
                break;
            case 'float':
            case 'integer':
                filter.valueType = 'number';
                break;
        }
        return filter;
    },
    
    /**
     * returns filter
     * 
     * @param {String} fieldKey
     * @param {Object} filterconfig
     * @param {Object} fieldconfig
     * @return {Object}
     */
    getFilter: function(fieldKey, filterconfig, modelConfig) {
        // take field label if no filterlabel is defined
        // TODO Refactor: tag and tags see ticket 0008944
        // TODO Remove this ugly hack!
        if (fieldKey == 'tag') {
            fieldKey = 'tags';
        }
        var fieldconfig = modelConfig.fields[fieldKey];

        if (fieldconfig && fieldconfig.type === 'virtual') {
            fieldconfig = fieldconfig.config;
        }

        var appName = modelConfig.appName;
        var modelName = modelConfig.modelName;
        
        var app = Tine.Tinebase.appMgr.get(appName);
        if (! app) {
            Tine.log.error('Application ' + appName + ' not found!');
            return null;
        }
        
        // check right on foreign app
        if (fieldconfig && (fieldconfig.type == 'record' || fieldconfig.type == 'records')) {
            var opt = fieldconfig.config;
            
            if (opt && (! opt.doNotCheckModuleRight) && (! Tine.Tinebase.common.hasRight('view', opt.appName, opt.modelName.toLowerCase()))) {
                return null;
            }
        }
        
        var fieldTypeKey = (fieldconfig && fieldconfig.type) ? fieldconfig.type : (filterconfig && filterconfig.type) ? filterconfig.type : 'default',
            label = (filterconfig && filterconfig.hasOwnProperty('label')) ? filterconfig.label : (fieldconfig && fieldconfig.hasOwnProperty('label')) ? fieldconfig.label : null,
            globalI18n = ((filterconfig && filterconfig.hasOwnProperty('useGlobalTranslation')) || (fieldconfig && fieldconfig.hasOwnProperty('useGlobalTranslation'))),
            i18n = globalI18n ? window.i18n : app.i18n;
        
        if (! label) {
            return null;
        }
        // prepare filter
        var filter = {
            label: i18n._hidden(label),
            field: fieldKey,
            gender: i18n._hidden('GENDER_' + label),
            specialType: fieldconfig ? fieldconfig.specialType : null
        };
        
        if (filterconfig) {
            if (filterconfig.hasOwnProperty('options') && (filterconfig.options.hasOwnProperty('jsFilterType') || filterconfig.options.hasOwnProperty('jsFilterValueType'))) {
                Tine.log.error('jsFilterType and jsFilterValueType are deprecated. Use jsConfig.<property> instead.');
            }
            // if js filter is defined in filterconfig.options, take this and return
            if (filterconfig.hasOwnProperty('jsConfig')) {
                Ext.apply(filter, filterconfig.jsConfig);
                return filter;
            } 
            
            try {
                filter = this.filterMap(fieldTypeKey, fieldconfig, filter, filterconfig, appName, modelName, modelConfig);
            } catch (e) {
                var keys = filterconfig.filter.split('_'),
                    filterkey = keys[0].toLowerCase() + '.' + keys[2].toLowerCase();
                    filterkey = filterkey.replace(/filter/g, '');
    
                if (Tine.widgets.grid.FilterToolbar.FILTERS[filterkey]) {
                    filter = {filtertype: filterkey};
                } else { // set to null if no filter could be found
                    filter = null;
                }
            }
        }

        return filter;
    },
    
    /**
     * if application starter should be used, here the js contents are (pre-)created
     */
    createStructure: function(initial) {
        var start = new Date();
        Ext.each(this.userApplications, function(app) {
            
            var appName = app.name;
            Tine.log.info('ApplicationStarter::createStructure for app ' + appName);
            Ext.namespace('Tine.' + appName);

            if (! Tine[appName].AdminPanel) {
                Tine[appName].AdminPanel = Ext.extend(Ext.TabPanel, {
                    border: false,
                    activeTab: 0,
                    appName: appName,
                    initComponent: function () {
                        this.app = Tine.Tinebase.appMgr.get(this.appName);
                        this.items = [
                            new Tine.Admin.config.GridPanel({
                                configApp: this.app
                            })
                        ];
                        this.supr().initComponent.call(this);
                    }
                });
                Tine[appName].AdminPanel.openWindow = function (config) {
                    return Tine.WindowFactory.getWindow({
                        width: 600,
                        height: 470,
                        name: 'Tine.' + appName + '.AdminPanel',
                        contentPanelConstructor: 'Tine.' + appName + '.AdminPanel',
                        contentPanelConstructorConfig: config
                    });
                };
            }

            var models = Tine[appName].registry ? Tine[appName].registry.get('models') : null;
            
            if (models) {
                
                Tine[appName].isAuto = true;
                var contentTypes = [];
                
                // create translation
                Tine[appName].i18n = new Locale.Gettext();
                Tine[appName].i18n.textdomain(appName);
                
                // iterate models of this app
                Ext.iterate(models, function(modelName, modelConfig) {
                    // create main screen
                    if (! Tine[appName].hasOwnProperty('MainScreen')) {
                        Tine[appName].MainScreen = Ext.extend(Tine.widgets.MainScreen, {
                            app: appName,
                            contentTypes: contentTypes,
                            activeContentType: modelConfig.createModule ? modelName : null
                        });
                    }

                    var containerProperty = modelConfig.hasOwnProperty('containerProperty') ? modelConfig.containerProperty : null;

                    // NOTE: we need to preserve original modelName.
                    //       - otherwise we can't referece
                    //       - otherwise we can't compute phpClassName
                    // modelName = modelName.replace(/_/, '');
                    
                    Ext.namespace('Tine.' + appName, 'Tine.' + appName + '.Model');
                    
                    var modelArrayName = modelName + 'Array',
                        modelArray = [];
                    
                    Tine.log.info('ApplicationStarter::createStructure for model ' + modelName);
                    
                    if (modelConfig.createModule) {
                        contentTypes.push(modelConfig);
                    }
                    
                    // iterate record fields
                    Ext.each(modelConfig.fieldKeys, function(key) {
                        var fieldDefinition = modelConfig.fields[key];

                        if (fieldDefinition.type === 'virtual') {
                            fieldDefinition = fieldDefinition.config;
                        }

                        // add field to model array
                        modelArray.push(this.getField(fieldDefinition, key));

                        if (fieldDefinition.label) {
                            // register grid renderer
                            if (initial) {
                                var renderer = null;
                                try {
                                    renderer = this.getGridRenderer(fieldDefinition, key, appName, modelName);
                                } catch (e) {
                                    Tine.log.err(e);
                                    renderer = null;
                                }
                                
                                if (Ext.isFunction(renderer)) {
                                    if (! Tine.widgets.grid.RendererManager.has(appName, modelName, key)) {
                                        Tine.widgets.grid.RendererManager.register(appName, modelName, key, renderer);
                                    }
                                } else if (Ext.isObject(renderer)) {
                                    if (! Tine.widgets.grid.RendererManager.has(appName, modelName, key)) {
                                        Tine.widgets.grid.RendererManager.register(appName, modelName, key, renderer.render, null, renderer);
                                    }
                                }
                            }
                        }
                        
                    }, this);
                    
                    // iterate virtual record fields
                    if (modelConfig.virtualFields && modelConfig.virtualFields.length) {
                        Ext.each(modelConfig.virtualFields, function(field) {
                            modelArray.push(this.getField(field, field.key));
                        }, this);
                    }

                    Tine[appName].Model[modelArrayName] = modelArray;
                    
                    // create model
                    if (! Tine[appName].Model.hasOwnProperty(modelName)) {
                        Tine[appName].Model[modelName] = Tine.Tinebase.data.Record.create(Tine[appName].Model[modelArrayName], 
                            Ext.copyTo({modelConfiguration: modelConfig}, modelConfig,
                               'idProperty,defaultFilter,appName,modelName,recordName,recordsName,titleProperty,' +
                                'containerProperty,containerName,containersName,group,copyOmitFields,copyNoAppendTitle')
                        );

                        // called from legacy code - but all filters should come from registy (see below)
                        Tine[appName].Model[modelName].getFilterModel = function() { return [];};
                    }

                    // register filters
                    Ext.iterate(modelConfig.filterModel, function(key, filter) {
                        var f = this.getFilter(key, filter, modelConfig);

                        if (f) {
                            Tine.widgets.grid.FilterRegistry.register(appName, modelName, f);
                        }
                    }, this);

                    // create recordProxy
                    var recordProxyName = modelName.toLowerCase() + 'Backend';
                    if (! Tine[appName].hasOwnProperty(recordProxyName)) {
                        Tine[appName][recordProxyName] = new Tine.Tinebase.data.RecordProxy({
                            appName: appName,
                            modelName: modelName,
                            recordClass: Tine[appName].Model[modelName]
                        });
                    }
                    // if default data is empty, it will be resolved to an array
                    if (Ext.isArray(modelConfig.defaultData)) {
                        modelConfig.defaultData = {};
                    }
                    
                    // overwrite function
                    Tine[appName].Model[modelName].getDefaultData = function() {
                        if (! dd) {
                            var dd = Ext.decode(Ext.encode(modelConfig.defaultData));
                        }
                        
                        // find container by selection or use defaultContainer by registry
                        if (modelConfig.containerProperty) {
                            if (! dd.hasOwnProperty(modelConfig.containerProperty)) {
                                var app = Tine.Tinebase.appMgr.get(appName),
                                    registry = app.getRegistry(),
                                    ctp = app.getMainScreen().getWestPanel().getContainerTreePanel();
                                    
                                var container = (ctp && Ext.isFunction(ctp.getDefaultContainer) ? ctp.getDefaultContainer() : null)
                                    || (registry ? registry.get("default" + modelName + "Container") : null);
                                
                                if (container) {
                                    dd[modelConfig.containerProperty] = container;
                                }
                            }
                        }
                        return dd;
                    };

                    // create filter panel
                    var filterPanelName = modelName + 'FilterPanel';
                    if (! Tine[appName].hasOwnProperty(filterPanelName)) {
                        Tine[appName][filterPanelName] = function(c) {
                            Ext.apply(this, c);
                            Tine[appName][filterPanelName].superclass.constructor.call(this);
                        };
                        Ext.extend(Tine[appName][filterPanelName], Tine.widgets.persistentfilter.PickerPanel);
                    }
                    // create container tree panel, if needed
                    if (containerProperty) {
                        var containerTreePanelName = modelName + 'TreePanel';
                        if (! Tine[appName].hasOwnProperty(containerTreePanelName)) {
                            Tine[appName][containerTreePanelName] = Ext.extend(Tine.widgets.container.TreePanel, {
                                filterMode: 'filterToolbar',
                                recordClass: Tine[appName].Model[modelName]
                            });
                        }
                    }
                    
                    // create editDialog openWindow function only if edit dialog exists
                    var editDialogName = modelName + 'EditDialog';
                    if (! Tine[appName].hasOwnProperty(editDialogName)) {
                        Tine[appName][editDialogName] = Ext.extend(Tine.widgets.dialog.EditDialog, {
                            displayNotes: Tine[appName].Model[modelName].hasField('notes')
                        });
                    }

                    
                    if (Tine[appName].hasOwnProperty(editDialogName)) {
                        var edp = Tine[appName][editDialogName].prototype;
                        if (containerProperty && edp.showContainerSelector !== false) {
                            edp.showContainerSelector = true;
                        }
                        Ext.apply(edp, {
                            modelConfig:      Ext.encode(modelConfig),
                            modelName:        modelName,
                            recordClass:      Tine[appName].Model[modelName],
                            recordProxy:      Tine[appName][recordProxyName],
                            appName:          appName,
                            windowNamePrefix: modelName + 'EditWindow_'
                        });
                        if (! Ext.isFunction(Tine[appName][editDialogName].openWindow)) {
                            Tine[appName][editDialogName].openWindow  = function (cfg) {
                                var id = cfg.recordId ? cfg.recordId : ( (cfg.record && cfg.record.id) ? cfg.record.id : 0 );
                                var window = Tine.WindowFactory.getWindow({
                                    width: edp.windowWidth ? edp.windowWidth : 600,
                                    height: edp.windowHeight ? edp.windowHeight :
                                        Tine.widgets.form.RecordForm.getFormHeight(Tine[appName].Model[modelName]),
                                    name: edp.windowNamePrefix + id,
                                    contentPanelConstructor: 'Tine.' + appName + '.' + editDialogName,
                                    contentPanelConstructorConfig: cfg
                                });
                                return window;
                            };
                        }
                    }
                    // create Gridpanel
                    var gridPanelName = modelName + 'GridPanel', 
                        gpConfig = {
                            modelConfig: modelConfig,
                            app: Tine.Tinebase.appMgr.get(appName),
                            recordProxy: Tine[appName][recordProxyName],
                            recordClass: Tine[appName].Model[modelName]
                        };
                        
                    if (! Tine[appName].hasOwnProperty(gridPanelName)) {
                        Tine[appName][gridPanelName] = Ext.extend(Tine.widgets.grid.GridPanel, gpConfig);
                    } else {
                        Ext.apply(Tine[appName][gridPanelName].prototype, gpConfig);
                    }

                    if (! Tine[appName][gridPanelName].prototype.detailsPanel) {
                        Tine[appName][gridPanelName].prototype.detailsPanel = {
                            xtype: 'widget-detailspanel',
                            recordClass: Tine[appName].Model[modelName]
                        }
                    }
                    // add model to global add splitbutton if set
                    if (modelConfig.hasOwnProperty('splitButton') && modelConfig.splitButton == true) {
                        var iconCls = appName + modelName;
                        if (! Ext.util.CSS.getRule('.' + iconCls)) {
                            iconCls = 'ApplicationIconCls';
                        }
                        Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', {
                            text: Tine[appName].i18n._('New ' + modelName), 
                            iconCls: iconCls,
                            scope: Tine.Tinebase.appMgr.get(appName),
                            handler: (function() {
                                var ms = this.getMainScreen(),
                                    cp = ms.getCenterPanel(modelName);
                                    
                                cp.onEditInNewWindow.call(cp, {});
                            }).createDelegate(Tine.Tinebase.appMgr.get(appName))
                        });
                    }
                    
                }, this);
            }
        }, this);
        
        var stop = new Date();
    }
}
