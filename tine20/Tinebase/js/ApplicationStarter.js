/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.namespace('Tine.Tinebase');

/**
 * Tinebase Application Starter
 * 
 * @namespace   Tine.Tinebase
 * @function    Tine.MailAccounting.MailAggregateGridPanel
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
        var field = {name: key};
        
        if (fieldDefinition.type) {
            // add pre defined type
            field.type = this.types[fieldDefinition.type];
            switch (fieldDefinition.type) {
                case 'datetime':
                    field.dateFormat = Date.patterns.ISO8601Long;
                    break;
                case 'date':
                    field.dateFormat = Date.patterns.ISO8601Long;
                    break;
                case 'time':
                    field.dateFormat = Date.patterns.ISO8601Time;
                    break;
                case 'record':
                case 'records':
                    fieldDefinition.config.modelName = fieldDefinition.config.modelName.replace(/_/, '');
                    field.type = fieldDefinition.config.appName + '.' + fieldDefinition.config.modelName;
                    break;
            }
            // allow overwriting date pattern in model
            if (fieldDefinition.hasOwnProperty('dateFormat')) {
                field.dateFormat = fieldDefinition.dateFormat;
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
                    gridRenderer = function(value, row, record) {
                        var foreignRecordClass = Tine[config.config.appName].Model[config.config.modelName];
                        var titleProperty = foreignRecordClass.getMeta('titleProperty');
                        return record.get(field) ? Ext.util.Format.htmlEncode(record.get(field)[titleProperty]) : '';
                    };
                    break;
                case 'integer':
                    if (config.hasOwnProperty('specialType')) {
                        switch (config.specialType) {
                            case 'bytes1000':
                                gridRenderer = function(a,b,c) {
                                    return Tine.Tinebase.common.byteRenderer(a, b, c, 2, true);
                                };
                                break;
                            case 'bytes':
                                gridRenderer = function(a,b,c) {
                                    return Tine.Tinebase.common.byteRenderer(a, b, c, 2, false);
                                };
                                break;
                            case 'minutes':
                                gridRenderer = Tine.Tinebase.common.minutesRenderer;
                                break;
                            case 'seconds':
                                gridRenderer = Tine.Tinebase.common.secondsRenderer;
                                break;
                            case 'usMoney':
                                gridRenderer = Ext.util.Format.usMoney;
                                break;
                            case 'euMoney':
                                gridRenderer = Ext.util.Format.euMoney;
                                break;
                            default:
                                gridRenderer = Ext.util.Format.htmlEncode;
                        }
                    }
                    break;
                case 'user':
                    gridRenderer = Tine.Tinebase.common.usernameRenderer;
                    break;
                case 'keyfield': 
                    gridRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get(appName, config.name);
                    break;
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
    filterMap: {
        'boolean': function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            filter.valueType = 'bool'
            filter.defaultValue = false;
            return filter;
        },
        record: function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
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
            return filter;
        },
        tag: function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            return {filtertype: 'tinebase.tag', app: appName};
        },
        container: function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            var applicationName = filterconfig.appName ? filterconfig.appName : appName;
            var modelName = filterconfig.modelName ? filterconfig.modelName : modelName;
            return {
                filtertype: 'tine.widget.container.filtermodel', 
                app: applicationName, 
                recordClass: applicationName + '.' + modelName,
                field: fieldconfig.key,
                label: fieldconfig.label,
                callingApp: appName
            };
        },
        keyfield: function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            filter.filtertype = 'tine.widget.keyfield.filter';
            filter.app = {name: appName};
            filter.keyfieldName = fieldconfig.name;
            return filter;
        },
        'string' :  function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            return filter;
        },
        'user':     function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            return filter;
        },
        'date':     function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            filter.valueType = 'date';
            return filter;
        },
        'datetime': function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            filter.valueType = 'date';
            return filter;
        },
        'text':     function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            return filter;
        },
        'integer':  function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            filter.valueType = 'number';
            return filter;
        },
        'default':  function(fieldconfig, filter, filterconfig, appName, modelName, modelConfig) {
            return filter;
        }
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
        var fieldconfig = modelConfig.fields[fieldKey];
        var appName = modelConfig.appName;
        var modelName = modelConfig.modelName;
        
        var app = Tine.Tinebase.appMgr.get(appName),
            fieldTypeKey = (fieldconfig && fieldconfig.type) ? fieldconfig.type : (filterconfig && filterconfig.type) ? filterconfig.type : 'default',
            label = (filterconfig && filterconfig.hasOwnProperty('label')) ? filterconfig.label : (fieldconfig && fieldconfig.hasOwnProperty('label')) ? fieldconfig.label : null,
            globalI18n = ((filterconfig && filterconfig.hasOwnProperty('useGlobalTranslation')) || (fieldconfig && fieldconfig.hasOwnProperty('useGlobalTranslation')));
        
        if (! label) {
            return null;
        }
        // prepare filter
        var filter = {
            label: globalI18n ? _(label) : app.i18n._(label),
            field: fieldKey
        };
        
        if (filterconfig) {
            if (filterconfig.hasOwnProperty('options') && (filterconfig.options.hasOwnProperty('jsFilterType') || filterconfig.options.hasOwnProperty('jsFilterValueType'))) {
                Tine.log.err('jsFilterType and jsFilterValueType are deprecated. Use jsConfig.<property> instead.');
            }
            // if js filter is defined in filterconfig.options, take this and return
            if (filterconfig.hasOwnProperty('jsConfig')) {
                Ext.apply(filter, filterconfig.jsConfig);
                return filter;
            } 
            
            try {
                filter = this.filterMap[fieldTypeKey](fieldconfig, filter, filterconfig, appName, modelName, modelConfig);
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
            Ext.namespace('Tine.' + appName);
            
            var models = Tine[appName].registry ? Tine[appName].registry.get('models') : null;
            
            if (models) {
                
                Tine[appName].isAuto = true;
                var contentTypes = [];
                
                // create translation
                Tine[appName].i18n = new Locale.Gettext();
                Tine[appName].i18n.textdomain(appName);
                
                // iterate models of this app
                Ext.iterate(models, function(modelName, modelConfig) {
                    var containerProperty = modelConfig.hasOwnProperty('containerProperty') ? modelConfig.containerProperty : null;
                    
                    modelName = modelName.replace(/_/, '');
                    
                    Ext.namespace('Tine.' + appName, 'Tine.' + appName + '.Model');
                    
                    var modelArrayName = modelName + 'Array',
                        modelArray = [];

                    if (modelConfig.createModule) {
                        contentTypes.push(modelConfig);
                    }
                    
                    // iterate record fields
                    Ext.each(modelConfig.fieldKeys, function(key) {
                        // add field to model array
                        modelArray.push(this.getField(modelConfig.fields[key], key));
                        
                        if (modelConfig.fields[key].label) {
                            // register grid renderer
                            if (initial) {
                                var renderer = this.getGridRenderer(modelConfig.fields[key], key, appName, modelName);
                                if (renderer) {
                                    if (! Tine.widgets.grid.RendererManager.has(appName, modelName, key)) {
                                        Tine.widgets.grid.RendererManager.register(appName, modelName, key, renderer);
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
                    
                    // collect the filterModel
                    var filterModel = [];
                    Ext.iterate(modelConfig.filterModel, function(key, filter) {
                        var f = this.getFilter(key, filter, modelConfig);
                        
                        if (f) {
                            Tine.widgets.grid.FilterRegistry.register(appName, modelName, f);
                            filterModel.push(f);
                        }
                    }, this);
                    
                    // TODO: registry looses info if gridpanel resides in an editDialog
                    // delete filterModel as all filters are in the filter registry now
                    // delete modelConfig.filterModel;
                    
                    Tine[appName].Model[modelArrayName] = modelArray;
                    
                    // create model
                    if (! Tine[appName].Model.hasOwnProperty(modelName)) {
                        Tine[appName].Model[modelName] = Tine.Tinebase.data.Record.create(Tine[appName].Model[modelArrayName], 
                            Ext.copyTo({}, modelConfig, 
                               'defaultFilter,appName,modelName,recordName,recordsName,titleProperty,containerProperty,containerName,containersName,group')
                        );
                        Tine[appName].Model[modelName].getFilterModel = function() {
                            return filterModel;
                        }
                    }
                    
                    Ext.namespace('Tine.' + appName);
                    
                    // create recordProxy
                    var recordProxyName = modelName.toLowerCase() + 'Backend';
                    if (! Tine[appName].hasOwnProperty(recordProxyName)) {
                        Tine[appName][recordProxyName] = new Tine.Tinebase.data.RecordProxy({
                            appName: appName,
                            modelName: modelName,
                            recordClass: Tine[appName].Model[modelName]
                        });
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
                                    
                                var container = (ctp ? ctp.getDefaultContainer() : null) || (registry ? registry.get("default" + modelName + "Container") : null);
                                
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
                    
                    // create main screen
                    if(! Tine[appName].hasOwnProperty('MainScreen')) {
                        Tine[appName].MainScreen = Ext.extend(Tine.widgets.MainScreen, {
                            app: appName,
                            contentTypes: contentTypes,
                            activeContentType: modelName
                        });
                    }
                    
                    // create editDialog openWindow function only if edit dialog exists
                    var editDialogName = modelName + 'EditDialog';
                    
                    if (Tine[appName].hasOwnProperty(editDialogName)) {
                        var edp = Tine[appName][editDialogName].prototype;
                        if (containerProperty) {
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
                                var id = (cfg.record && cfg.record.id) ? cfg.record.id : 0;
                                var window = Tine.WindowFactory.getWindow({
                                    width: edp.windowWidth ? edp.windowWidth : 600,
                                    height: edp.windowHeight ? edp.windowHeight : 230,
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
                            app: Tine[appName], 
                            recordProxy: Tine[appName][recordProxyName],
                            recordClass: Tine[appName].Model[modelName]
                        };
                        
                    if (! Tine[appName].hasOwnProperty(gridPanelName)) {
                        Tine[appName][gridPanelName] = Ext.extend(Tine.widgets.grid.GridPanel, gpConfig);
                    } else {
                        Ext.apply(Tine[appName][gridPanelName].prototype, gpConfig);
                    }
                }, this);
            }
        }, this);
        
        var stop = new Date();
    }
}
