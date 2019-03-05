/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Foreign Record Filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ForeignRecordFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * <p>Filter for foreign records</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 */
Tine.widgets.grid.ForeignRecordFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    /**
     * @cfg {Application} app (required)
     */
    app: null,
    
    /**
     * @cfg {Application} ownRecordClass own record class for generic filter row
     */
    ownRecordClass: null,
    
    /**
     * @cfg {Record} foreignRecordClass needed for explicit defined filters
     */
    foreignRecordClass : null,

    /**
     * @cfg {String} foreignRefIdField optional, defaults to idProperty
     * might differ for foreignRecords (many to many relation) filter
     *  -> foreignRefId is not yet part of modelConfiguration :-(
     */
    foreignRefIdField: null,

    /**
     * @cfg {String} linkType {relation|foreignId} needed for explicit defined filters
     */
    linkType: 'relation',
    
    /**
     * @cfg {String} filterName server side filterGroup Name, needed for explicit defined filters
     */
    filterName: null,
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: null,
    
    /**
     * @cfg {String} editDefinitionText untranslated edit definition button text
     */
    editDefinitionText: 'Edit definition', // i18n._('Edit definition')
    
    /**
     * @cfg {Object} optional picker config
     */
    pickerConfig: null,
    
    /**
     * @cfg {String} startDefinitionText untranslated start definition button text
     */
    startDefinitionText: 'Start definition', // i18n._('Start definition')
    
    /**
     * @property this filterModel is the generic filterRow
     * @type Boolean
     */
    isGeneric: false,
    
    field: 'foreignRecord',
    
    /**
     * ignore this php models (filter is not shown)
     * @cfg {Array}
     */
    ignoreRelatedModels: null,
    
    /**
     * @private
     */
    initComponent: function() {
        if (this.foreignRecordClass) {
            this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(this.foreignRecordClass);
        }
        
        // TODO: remove this when files can be searched
        this.ignoreRelatedModels = this.ignoreRelatedModels ? this.ignoreRelatedModels.push('Filemanager_Model_Node') : ['Filemanager_Model_Node'];
        // TODO: remove this when ProductAggregates can be searched (or move this exception to Sales app)
        this.ignoreRelatedModels.push('Sales_Model_ProductAggregate');
        
        if (this.ownField) {
            this.field = this.ownField;
        }
        
        this['init' + (this.isGeneric ? 'Generic' : 'Explicit')]();
        Tine.widgets.grid.ForeignRecordFilter.superclass.initComponent.call(this);
    },
    
    /**
     * init the generic foreign filter row
     */
    initGeneric: function() {
            
        this.label = i18n._('Related to');
        
        var operators = [];
        
        // linkType relations automatic list
        if (this.ownRecordClass.hasField('relations')) {
            var operators = [];
            Ext.each(Tine.widgets.relation.Manager.get(this.app, this.ownRecordClass, this.ignoreRelatedModels), function(relation) {
                if (Tine.Tinebase.common.hasRight('run', relation.relatedApp)) {
                    // TODO: leave label as it is?
                    var label = relation.text.replace(/ \(.+\)/,'');
                    operators.push({operator: {linkType: 'relation', foreignRecordClass: Tine.Tinebase.common.resolveModel(relation.relatedModel, relation.relatedApp)}, label: label});
                }
            }, this);
        }
        // get operators from registry
        Ext.each(Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.get(this.ownRecordClass), function(def) {
            // translate label
            var foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(def.foreignRecordClass),
                appName = foreignRecordClass.getMeta('appName'),
                app = Tine.Tinebase.appMgr.get(appName),
                label = app ? app.i18n._hidden(def.label) : def.label;

            operators.push({operator: {linkType: def.linkType, foreignRecordClass: foreignRecordClass, filterName: def.filterName}, label: label});
        }, this);

        // we need this to detect operator changes
        Ext.each(operators, function(o) {o.toString = this.objectToString}, this);

        this.operatorStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: operators
        });

        if ( this.operatorStore.getCount() > 0) {
            this.defaultOperator = this.operatorStore.getAt(0).get('operator');
        }
    },

    /**
     * init an explicit filter row
     */
    initExplicit: function() {
        this.foreignField = this.foreignRefIdField || this.foreignRecordClass.getMeta('idProperty');

        var foreignApp = Tine.Tinebase.appMgr.get(this.foreignRecordClass.getMeta('appName')),
            i18n;
        if (foreignApp) {
            i18n = foreignApp.i18n;
        } else {
            i18n = new Locale.Gettext();
            i18n.textdomain('Tinebase');
        }

        if (! this.label) {
            this.label = i18n.n_(this.foreignRecordClass.getMeta('recordName'), this.foreignRecordClass.getMeta('recordsName'), 1);
        } else {
            this.label = i18n._(this.label);
        }

        if (! this.operators) {
            this.operators = ['equals', 'not', 'in', /*'notin',*/ 'definedBy'];
        }

        // get operators from registry
        if (this.ownRecordClass) {
            Ext.each(Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.get(this.ownRecordClass), function (def) {
                // translate label
                var foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(def.foreignRecordClass),
                    appName = foreignRecordClass.getMeta('appName'),
                    app = Tine.Tinebase.appMgr.get(appName),
                    label = app ? app.i18n._hidden(def.label) : def.label;

                if (foreignRecordClass == this.foreignRecordClass) {
                    this.operators.push({
                        operator: Ext.apply(def, {
                            foreignRecordClass: foreignRecordClass
                        }),
                        label: label
                    });
                }
            }, this);
        }


        if (! this.defaultOperator) {
            this.defaultOperator = 'equals';
        }
    },
    
    /**
     * onDefineRelatedRecord
     * 
     * @param {} filter
     */
    onDefineRelatedRecord: function(filter) {
        Tine.log.debug('Tine.widgets.grid.ForeignRecordFilter::onDefineRelatedRecord() - filter:');
        Tine.log.debug(filter);
        
        if (! filter.toolbar) {
            this.createRelatedRecordToolbar(filter);
        }
        
        this.ftb.setActiveSheet(filter.toolbar);
        filter.formFields.value.setText((this.editDefinitionText));
    },
    
    /**
     * get related record value data
     * 
     * NOTE: generic filters have their foreign record definition in the values
     */
    getRelatedRecordValue: function(filter) {
        var _ = window.lodash,
            me = this,
            filters = filter.toolbar ? filter.toolbar.getValue() : [],
            foreignRecordClass = filter.foreignRecordDefinition.foreignRecordClass,
            value;
            
        if (this.isGeneric) {
            value = {
                appName: foreignRecordClass.getMeta('appName'),
                modelName: foreignRecordClass.getMeta('modelName'),
                linkType: filter.foreignRecordDefinition.linkType,
                filterName : filter.foreignRecordDefinition.filterName,
                filters: filters
            };
            
        } else {
            value = filters;

            var operator = filter.get('operator') || filter.formFields.operator.origGetValue(),
                registeredOperator = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == operator;}), 'operator', false);

            if (registeredOperator) {
                value.push({
                    filterName: registeredOperator.filterName,
                    field: ':' + registeredOperator.field,
                    operator: registeredOperator.operator,
                    value: filter.formFields.value.value
                });
            } else {
                // get value for idField if our own operator is not definedBy

                // auto switch not to equals as we auto switch definedBy to notDefinedBy
                var op = filter.get('operator'),
                    opMap = {not: 'equals'};

                if (op != 'definedBy') {
                    value.push({
                        field: ':' + (me.foreignRefIdField || foreignRecordClass.getMeta('idProperty')),
                        operator: opMap[op] || op,
                        value: filter.formFields.value.value
                    });
                }

                // get values of filters of our toolbar we are superfilter for (left hand stuff)
                this.ftb.filterStore.each(function (filter) {
                    var filterModel = this.ftb.getFilterModel(filter);
                    if (filterModel.superFilter && filterModel.superFilter == this) {
                        var filterData = this.ftb.getFilterData(filter);
                        value.push(filterData);
                    }
                }, this);
            }
            
        }
        
        return value;
    },
    
    /**
     * set related record value data
     * @param {} filter
     */
    setRelatedRecordValue: function(filter) {
        var _ = window.lodash,
            me = this,
            value = filter.get('value'),
            operator = filter.get('operator') || filter.formFields.operator.origGetValue(),
            isRegisteredOperator = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == operator;}), 'operator', false);
        
        if (isRegisteredOperator || ['equals', 'not', 'in', 'notin'].indexOf(operator) >= 0) {
            // NOTE: if setValue got called in the valueField internally, value is arguments[1] (createCallback)
            return filter.formFields.value.origSetValue(arguments.length ? arguments[1] : value);
        }
        
        // generic: choose right operator : appname -> generic filters have no subfilters an if one day, no left hand once!
        if (this.isGeneric) {
            // get operator
            this.operatorStore.each(function(r) {
                var operator = r.get('operator'),
                    foreignRecordClass = operator.foreignRecordClass;
                    
                if (foreignRecordClass.getMeta('appName') == value.appName && foreignRecordClass.getMeta('modelName') == value.modelName) {
                    filter.formFields.operator.setValue(operator);
                    filter.foreignRecordDefinition = operator;
                    return false;
                }
            }, this);
            
            // set all content on childToolbar
            if (Ext.isObject(filter.foreignRecordDefinition) && value && Ext.isArray(value.filters) && value.filters.length) {
                if (! filter.toolbar) {
                    this.createRelatedRecordToolbar(filter);
                }
                
                filter.toolbar.setValue(value.filters);
                
                // change button text
                if (filter.formFields.value && Ext.isFunction(filter.formFields.value.setText)) {
                    filter.formFields.value.setText(i18n._(this.editDefinitionText));
                }
            }
            
            
        } else {
            if (! Ext.isArray(value)) return;
            
            // explicit chose right operator /equals / in /definedBy: left sided values create (multiple) subfilters in filterToolbar
            var foreignRecordDefinition = filter.foreignRecordDefinition,
                foreignRecordClass = foreignRecordDefinition.foreignRecordClass,
                foreignRecordIdProperty = me.foreignRefIdField || foreignRecordClass.getMeta('idProperty'),
                parentFilters = [];
                
            Ext.each(value, function(filterData, i) {
                if (! Ext.isString(filterData.field)) return;
                
                if (filterData.implicit) parentFilters.push(filterData);
                    
                var parts = filterData.field.match(/^(:)?(.*)/),
                    leftHand = !!parts[1],
                    field = parts[2];
                
                if (leftHand) {
                    // leftHand id property and registered operators are handled below
                    if (field == foreignRecordIdProperty || filterData.filterName) {
                        return;
                    }

                    // move filter to leftHand/parent filterToolbar
                    if (this.ftb.getFilterModel(filterData.field)) {
                        // ftb might have a record with this id
                        // and we can't keep it yet
                        delete filterData.id;
                        this.ftb.addFilter(new this.ftb.record(filterData));
                    }
                    
                    parentFilters.push(filterData);
                }
            }, this);
            
            // remove parent filters
            Ext.each(parentFilters, function(filterData) {value.remove(filterData);}, this);
            
            // if there where no remaining childfilters, hide this filterrow
            if (! value.length)  {
                // prevent loop
                filter.set('value', '###NOT SET###');
                filter.set('value', '');
                
                filter.formFields.operator.setValue(this.defaultOperator);
                this.onOperatorChange(filter, this.defaultOperator, false);
                
                // if (not empty value through operator chage)
                Tine.log.info('hide row -> not yet implemented');
            }
            
            // a single id filter is always displayed in the parent Toolbar with our own filterRow
            else if (value.length == 1 && [foreignRecordIdProperty, ':' + foreignRecordIdProperty].indexOf(value[0].field) > -1) {
                // switch back to not when operator is notDefinedby as we switched not to equals
                var op = value[0].operator,
                    opMap = { 'notDefinedBy:AND': 'not' },
                    finalOp = opMap[operator] || op;

                filter.set('value', value[0].value);
                filter.formFields.operator.setValue(finalOp);
                this.onOperatorChange(filter, finalOp, true);
            }

            // a single registered filter is always displayed in the parent Toolbar with our own filterRow
            else if (value.length == 1 && (registeredOperator = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == value[0].filterName;}), 'operator', false))) {
                filter.set('value', value[0].value);
                filter.formFields.operator.setValue(registeredOperator.filterName);
                this.onOperatorChange(filter, registeredOperator.filterName, true);
                filter.formFields.value.origSetValue(value[0].value)
            }
            
            // set remaining child filters
            else {
                if (! filter.toolbar) {
                    this.createRelatedRecordToolbar(filter);
                }
                
                filter.toolbar.setValue(value);
                
                filter.formFields.operator.setValue('definedBy');
            }
        }
        
    },
    
    /**
     * create a related record toolbar
     */
    createRelatedRecordToolbar: function(filter) {
        Tine.log.debug('Tine.widgets.grid.ForeignRecordFilter::createRelatedRecordToolbar() - filter:');
        Tine.log.debug(filter);
        
        var foreignRecordDefinition = filter.foreignRecordDefinition,
            foreignRecordClass = foreignRecordDefinition.foreignRecordClass,
            filterModels = foreignRecordClass.getFilterModel(),
            ftb = this.ftb;

        if (! filter.toolbar) {
            // add our subfilters in this toolbar (right hand)
            if (Ext.isFunction(this.getSubFilters)) {
                filterModels = filterModels.concat(this.getSubFilters());
            }

            filter.toolbar = new Tine.widgets.grid.FilterToolbar({
                recordClass: foreignRecordClass,
                filterModels: filterModels,
                defaultFilter: foreignRecordClass.getMeta('defaultFilter') ? foreignRecordClass.getMeta('defaultFilter') : 'query'
            });
            
            ftb.addFilterSheet(filter.toolbar);
            
            // force rendering as we can't set values on non rendered toolbar atm.
            this.ftb.setActiveSheet(filter.toolbar);
            this.ftb.setActiveSheet(this.ftb);
        }
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
        var operator;
        
        // init operator value
        filter.set('operator', filter.get('operator') ? filter.get('operator') : this.defaultOperator);
        
        if (! this.isGeneric) {
            operator = Tine.widgets.grid.ForeignRecordFilter.superclass.operatorRenderer.apply(this, arguments);
            filter.foreignRecordDefinition = {linkType: this.linkType, foreignRecordClass: this.foreignRecordClass, filterName: this.filterName}
        } else {
            operator = new Ext.form.ComboBox({
                filter: filter,
                width: 80,
                id: 'tw-ftb-frow-operatorcombo-' + filter.id,
                mode: 'local',
                lazyInit: false,
                emptyText: i18n._('select a operator'),
                forceSelection: true,
                typeAhead: true,
                triggerAction: 'all',
                store: this.operatorStore,
                displayField: 'label',
                valueField: 'operator',
                value: filter.get('operator'),
                renderTo: el
            });
            operator.on('select', function(combo, newRecord, newKey) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
            
            // init foreignRecordDefinition
            filter.foreignRecordDefinition = filter.get('operator');
        }
        
        
        operator.origGetValue = operator.getValue.createDelegate(operator);
        
        operator.getValue = function() {
            // auto switch operator for negating single line filters
            var op = operator.origGetValue(),
                opMap = {
                    not: 'notDefinedBy:AND'
                };

            return opMap[op] || 'AND';
        };
        
//        var origSetValue = operator.setValue.createDelegate(operator);
//        operator.setValue = function(value) {
//            origSetValue(value == 'AND' ? 'definedBy' : value);
//        }
        
        return operator;
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator, keepValue) {
        if (this.isGeneric) {
            filter.foreignRecordDefinition = newOperator;
        }
        
        if (filter.get('operator') != newOperator) {
            if (filter.toolbar) {
                filter.toolbar.destroy();
                delete filter.toolbar;
            }
        }
        
        filter.set('operator', newOperator);

        if (! keepValue) {
            filter.set('value', '');
        }
        
        var el = Ext.select('tr[id=' + this.ftb.frowIdPrefix + filter.id + '] td[class^=tw-ftb-frow-value]', this.ftb.el).first();
        
        // NOTE: removeMode got introduced on ext3.1 but is not docuemented
        //       'childonly' is no ext mode, we just need something other than 'container'
        if (filter.formFields.value && Ext.isFunction(filter.formFields.value.destroy)) {
            filter.formFields.value.removeMode = 'childsonly';
            filter.formFields.value.destroy();
            delete filter.formFields.value;
        }
        
        filter.formFields.value = this.valueRenderer(filter, el);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var _ = window.lodash,
            me = this,
            operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator,
            value;

        switch(operator) {
            case 'equals':
            case 'not':
            case 'in':
            case 'notin':
                //@TODO find it
                var pickerRecordClass = this.foreignRecordClass;
                if (this.foreignRefIdField) {
                    // many 2 many relation
                    var foreignRecordConfig = _.get(this.foreignRecordClass.getModelConfiguration(), 'fields.' + this.foreignRefIdField + '.config');
                    pickerRecordClass = Tine.Tinebase.data.RecordMgr.get(foreignRecordConfig.appName, foreignRecordConfig.modelName);
                }

                value = Tine.widgets.form.RecordPickerManager.get(pickerRecordClass.getMeta('appName'), pickerRecordClass, Ext.apply({
                    filter: filter,
                    blurOnSelect: true,
                    width: this.filterValueWidth,
                    listWidth: 500,
                    listAlign: 'tr-br',
                    id: 'tw-ftb-frow-valuefield-' + filter.id,
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    renderTo: el,
                    allowMultiple: ['in', 'notin'].indexOf(operator) > -1
                }, this.pickerConfig));
                
                value.on('specialkey', function(field, e){
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                }, this);

                value.origSetValue = value.setValue.createDelegate(value);

                break;

            default:
                var def = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == operator;}), 'operator', {}),
                    backup = _.reduce(_.keys(def), function(bkup, key) {
                        return _.set(bkup, key, me[key]);
                    }, {}),
                    valueType = _.get(def, 'valueType');

                // cope with operator registry values
                if ( valueType) {
                    _.assign(me, def);
                    value = Tine.widgets.grid.FilterModel.prototype.valueRenderer.call(me, filter, el);
                    _.assign(me, backup);

                    value.origSetValue = value.setValue.createDelegate(value);
                } else {
                    this.setRelatedRecordValue(filter);

                    if (!filter.formFields.value) {
                        value = new Ext.Button({
                            text: i18n._(this.startDefinitionText),
                            filter: filter,
                            width: this.filterValueWidth,
                            id: 'tw-ftb-frow-valuefield-' + filter.id,
                            renderTo: el,
                            handler: this.onDefineRelatedRecord.createDelegate(this, [filter]),
                            scope: this
                        });

                        // show button
                        el.addClass('x-btn-over');

                        // change text if setRelatedRecordValue had child filters
                        if (filter.toolbar) {
                            value.setText((this.editDefinitionText));
                        }

                    } else {
                        value = filter.formFields.value;
                    }
                }
                break;
        }

        value.setValue = this.setRelatedRecordValue.createDelegate(this, [filter], 0);
        value.getValue = this.getRelatedRecordValue.createDelegate(this, [filter]);
        
        return value;
    },
    
//    getSubFilters: function() {
//        var filterConfigs = this.foreignRecordClass.getFilterModel();
//        
//        Ext.each(filterConfigs, function(config) {
//            this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
//        }, this);
//        
//        return this.subFilterModels;
//    },
    
    objectToString: function() {
        return Ext.encode(this);
    },
    
    onDestroy: function(filterRecord) {
        if(filterRecord.toolbar) {
            this.ftb.removeFilterSheet(filterRecord.toolbar);
            
            delete filterRecord.toolbar;
        }
        
    }
});
    
/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterRegistry
 * @singleton
 */
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry = function() {
    var operators = {};
    
    return {
        register: function(appName, modelName, operator) {
            var key = appName + '.' + modelName;
            if (! operators[key]) {
                operators[key] = [];
            }
            
            operators[key].push(operator);
        },
        
        get: function(appName, modelName) {
            if (Ext.isFunction(appName.getMeta)) {
                modelName = appName.getMeta('modelName');
                appName = appName.getMeta('appName');
            }
        
            var key = appName + '.' + modelName;
            
            return operators[key] || [];
        }
    };
}();

Tine.widgets.grid.FilterToolbar.FILTERS['foreignrecord'] = Tine.widgets.grid.ForeignRecordFilter;
