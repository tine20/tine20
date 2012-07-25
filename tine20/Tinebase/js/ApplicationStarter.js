/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        'boolean':  'bool',
        'integer':  'int',
        'float':    'float'
    },
    /**
     * maps php filters to value types of js filter
     * @type {Object}
     */
    filters: {
        'Tinebase_Model_Filter_Id': null,
        'Tinebase_Model_Filter_Text': 'string',
        'Tinebase_Model_Filter_Date': 'date',
        'Tinebase_Model_Filter_Bool': 'bool',
        'Tinebase_Model_Filter_ForeignId': 'foreign'
    },
    
    /**
     * initializes the starter
     */
    init: function() {
        // Wait until appmgr is initialized
        if (!Tine.Tinebase.hasOwnProperty('appMgr')) {
            this.init.defer(100, this);
            return;
        }
        if(!this.userApplications) {
            this.userApplications = Tine.Tinebase.registry.get('userApplications');
            this.createStructure(true)
        }
    },
    
    /**
     * returns the record type
     * @param {} type
     * @param {} options
     * @return {}
     */
    getRecordType: function(type, options) {
        switch (type) {
            case 'custom':
                var recordType = this.types[type];
                break;
            case 'foreign':
                var recordType = options.app + '.' + options.model;
                break;
            default:
                var recordType = this.types[type];
        }
        return recordType;
    },
    /**
     * returns the grid renderer
     * @param {Object} config
     * @param {String} field
     * @return {Function}
     */
    getGridRenderer: function(config, field, appName, modelName) {
        var gridRenderer = null;
        if(config && field) {
            switch (config.type) {
                case 'foreign':
                    var foreignRecordClass = Tine[config.options.app].Model[config.options.model];
                    var titleProperty = foreignRecordClass.getMeta('titleProperty');
                    gridRenderer = function(value, row, record) {
                        return record.get(field) ? Ext.util.Format.htmlEncode(record.get(field)[titleProperty]) : '';
                    }
                    break;
                case 'integer':
                    if(config.hasOwnProperty('specialType')) {
                        switch(config.specialType) {
                            case 'bytes':
                                gridRenderer = function(a,b,c) {
                                    return Tine.Tinebase.common.byteRenderer(a,b,c);
                                }
                            break;
                        }
                    }
                    break;
                case 'user':
                    gridRenderer = Tine.Tinebase.common.accountRenderer;
                    break;
                case 'keyfield': 
                    gridRenderer = Tine.Tinebase.widgets.keyfield.Renderer.get(appName, config.name);
                    break
            }
        }
        return gridRenderer;
    },

    /**
     * returns filter
     * @param {String} key
     * @param {Object} filterconfig
     * @param {Object} fieldconfig
     * @return {Object}
     */
    getFilter: function(key, filterconfig, fieldconfig, appName, modelName) {
        // prepare filter
        // take field label if no filterlabel is defined
        var filter = {
            label: (filterconfig && filterconfig.label) ? filterconfig.label : (fieldconfig && fieldconfig.label) ? fieldconfig.label : '',
            field: key
        };

        if (filterconfig) {
            // if js filter is defined in filterconfig.options, take this and return
            if(filterconfig.hasOwnProperty('options') && filterconfig.options.hasOwnProperty('jsFilterType')) {
                filter.valueType = filterconfig.options.jsFilterType;
                return filter;
            } 
            
            switch (filterconfig.filter) {
                case 'Tinebase_Model_Filter_ForeignId':
                    // create generic foreign id filter
                    var filterclass = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
                        foreignRecordClass: fieldconfig.options.app + '.' + fieldconfig.options.model,
                        linkType: 'foreignId',
                        ownField: key
                    });
                    var a = appName.toLowerCase();
                    var b = fieldconfig.options.model.toLowerCase();
                    var fc = a + '.' + b; 
                    Tine.widgets.grid.FilterToolbar.FILTERS[fc] = filterclass;
                    filter = {filtertype: fc};
                    break;
                case 'Tinebase_Model_Filter_Tag':
                    filter = {filtertype: 'tinebase.tag', app: appName};
                    break;
                case 'Tinebase_Model_Filter_Container':
                    filter = {filtertype: 'tine.widget.container.filtermodel', app: appName, recordClass: appName + '.' + modelName};
                    break;
                default:
                    if(this.filters[filterconfig.filter]) {  // use pre-defined default filter (this.filters)
                        filter.valueType = this.filters[filterconfig.filter];
                    } else {    // try to find registered filter
                        var keys = filterconfig.filter.split('_'),
                            filterkey = keys[0].toLowerCase() + '.' + keys[2].toLowerCase();
                            filterkey = filterkey.replace(/filter/g, '');
            
                        if(Tine.widgets.grid.FilterToolbar.FILTERS[filterkey]) {
                            filter = {filtertype: filterkey};
                        } else { // set to null if no filter could be found
                            filter = null;
                        }
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
            if(Tine[appName].registry && Tine[appName].registry.get('models')) {
                Tine[appName].isAuto = true;
                var models = Tine[appName].registry.get('models');
                var contentTypes = [];

                // create translation
                Tine[appName].i18n = new Locale.Gettext();
                Tine[appName].i18n.textdomain(appName);
                
                // iterate models of this app
                Ext.iterate(models, function(model, config) {
                    Ext.namespace('Tine.' + appName, 'Tine.' + appName + '.Model');
                    var modelArrayName = model + 'Array';
                    var rA = [];
                    contentTypes.push(config);
                    
                    var defaultData = {},
                        filterModel = [];
                    // iterate record fields
                    Ext.each(config.keys, function(key) {
                        // add field to model array
                        rA.push({name: key, type: this.getRecordType(config.fields[key].type, config.fields[key].options)});
                        // create default data
                        defaultData[key] = config.fields[key].standard ? config.fields[key].standard : null;
                        // if field config has label, create grid renderer
                        if(config.fields[key].label) {
                            // register grid renderer
                            if(initial) {
                                var renderer = this.getGridRenderer(config.fields[key], key, appName, model);
                                if(renderer) {
                                    Tine.widgets.grid.RendererManager.register(appName, model, key, renderer);
                                }
                            }
                        }
                    }, this);
                    
                    if(config.hasOwnProperty('meta')) {
                        // relations
                        if(config.meta.hasRelations) {
                            rA.push('relations');
                        }
                        // tags
                        if(config.meta.hasTags) {
                            rA.push('tags');
                        }
                        // customfields
                        if(config.meta.hasCustomFields || config.meta.hasCustomfields) {
                            rA.push('customfields');
                        }
                        // notes
                        if(config.meta.hasNotes) {
                            rA.push('notes');
                        }
                        Ext.iterate(config.filter, function(key, filter) {
                            // create Filter Model
                            if (filter = this.getFilter(key, filter, config.fields[key], appName, model)) {
                                filterModel.push(filter);
                            }
                        }, this);
                    }
                    // add generic fields if modlog is active
                    Tine[appName].Model[modelArrayName] = (config.meta && config.meta.useModlog) ? Tine.Tinebase.Model.genericFields.concat(rA) : rA;
                    
                    // create model
                    if(! Tine[appName].Model.hasOwnProperty(model)) {
                        Tine[appName].Model[model] = Tine.Tinebase.data.Record.create(Tine[appName].Model[modelArrayName], Ext.apply(config.meta ? config.meta : {}, {
                            appName: appName,
                            modelName: model
                        }));
                    }
                    Ext.namespace('Tine.' + appName);
                    // create recordProxy
                    var recordProxyName = model.toLowerCase() + 'Backend';
                    if(! Tine[appName].hasOwnProperty(recordProxyName)) {
                        Tine[appName][recordProxyName] = new Tine.Tinebase.data.RecordProxy({
                            appName: appName,
                            modelName: model,
                            recordClass: Tine[appName].Model[model]
                        });
                    }

                    // create container tree panel, if needed
                    var containerTreePanelName = model + 'TreePanel';
                    if(! Tine[appName].hasOwnProperty(containerTreePanelName)) {
                        Tine[appName][containerTreePanelName] = Ext.extend(Tine.widgets.container.TreePanel, {
                            filterMode: 'filterToolbar',
                            recordClass: Tine[appName].Model[model]
                        });
                    }
                    
                    // create default data function
                    if(!Ext.isFunction(Tine[appName].Model[model].getDefaultData)) {
                        Tine[appName].Model[model].getDefaultData = function() {
                            if(!dd) var dd = Ext.decode(Ext.encode(defaultData));
                            return dd;
                        };
                        Tine[appName].Model[model].getDefaultData();
                    }

                    // create the filter model
                    if (!Ext.isFunction(Tine[appName].Model[model].getFilterModel)) {
                        Tine[appName].Model[model].getFilterModel = function() {
                            if(!pF) var pF = Ext.decode(Ext.encode(filterModel));
                            return pF;
                        };
                        Tine[appName].Model[model].getFilterModel();
                    }

                    // create filter panel
                    var filterPanelName = model + 'FilterPanel';
                    if (! Tine[appName].hasOwnProperty(filterPanelName)) {
                        Tine[appName][filterPanelName] = function(config) {
                            Ext.apply(this, config);
                            Tine[appName][filterPanelName].superclass.constructor.call(this);
                        };
                        Ext.extend(Tine[appName][filterPanelName], Tine.widgets.persistentfilter.PickerPanel);
                    }

                    // create main screen
                    if(! Tine[appName].hasOwnProperty('MainScreen')) {
                        Tine[appName].MainScreen = Ext.extend(Tine.widgets.MainScreen, {
                            app: appName,
                            contentTypes: contentTypes,
                            activeContentType: model
                        });
                    }
                    
                    var editDialogName = model + 'EditDialog';
                    // create editDialog openWindow function only when edit dialog exists
                    if(Tine[appName].hasOwnProperty(editDialogName)) {
                        if(config.meta.containerProperty) {
                            Tine[appName][editDialogName].prototype.showContainerSelector = true;
                        }
                        if(!Ext.isFunction(Tine[appName][editDialogName].openWindow)) {
                            Tine[appName][editDialogName].openWindow  = function (cfg) {
                                var id = (cfg.record && cfg.record.id) ? cfg.record.id : 0;
                                var window = Tine.WindowFactory.getWindow({
                                    width: Tine[appName][editDialogName].prototype.windowWidth ? Tine[appName][editDialogName].prototype.windowWidth : 600,
                                    height: Tine[appName][editDialogName].prototype.windowHeight ? Tine[appName][editDialogName].prototype.windowHeight : 230,
                                    name: Tine[appName][editDialogName].prototype.windowNamePrefix + id,
                                    contentPanelConstructor: 'Tine.' + appName + '.' + editDialogName,
                                    contentPanelConstructorConfig: cfg
                                });
                                return window;
                            };
                        }
                    }
                    // create gridpanel
                    var gridPanelName = model + 'GridPanel';
                    if (! Tine[appName].hasOwnProperty(gridPanelName)) {
                        Tine[appName][gridPanelName] = Ext.extend(Tine.widgets.grid.GridPanel, {
                            modelConfig: config,
                            app: Tine[appName], 
                            recordProxy: Tine[appName][recordProxyName],
                            recordClass: Tine[appName].Model[model]
                        });
                    } else {
                         Ext.apply(Tine[appName][gridPanelName].prototype, {
                            modelConfig: config,
                            app: Tine[appName], 
                            recordProxy: Tine[appName][recordProxyName],
                            recordClass: Tine[appName].Model[model]
                        });
                    }
                }, this);
            }
        }, this);
        
        var stop = new Date();
    }
}