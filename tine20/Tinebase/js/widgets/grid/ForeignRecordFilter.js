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
     * @cfg {Record} foreignRecordClass for explicit filterRow
     */
    foreignRecordClass : null,
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: null,
    
    /**
     * @property this filterModel is the generic filterRow
     * @type Boolean
     */
    isGeneric: false,
    
//    isForeignFilter: true,
//    filterValueWidth: 200,
    
    
    /**
     * @private
     */
    initComponent: function() {
        if (this.foreignRecordClass) {
            this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(this.foreignRecordClass);
        }
        
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
            
        this.label = _('Relation');
        this.field = 'foreignRecord';
        
        var operators = [];
        
        // linkType relations automatic list
        if (this.ownRecordClass.hasField('relations')) {
            Tine.Tinebase.data.RecordMgr.eachKey(function(key, record) {
                if (record.hasField('relations') && Ext.isFunction(record.getFilterModel)) {
                    var label = Tine.Tinebase.appMgr.get(record.getMeta('appName')).i18n._hidden(record.getMeta('recordsName')),
                        parts = key.split('.'),
                        appName = parts[0],
                        modelName = parts[1];
                    
                    if (Tine.Tinebase.common.hasRight('run', appName)) {
                        operators.push({operator: {linkType: 'relation', appName: appName, modelName: modelName}, label: label});
                    }
                }
            }, this);
        }
        
        // get operators from registry
        Ext.each(Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.get(this.ownRecordClass), function(def) {
            // translate label
            var foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(def.foreignRecordClass),
                appName = foreignRecordClass.getMeta('appName'),
                label = Tine.Tinebase.appMgr.get(appName).i18n._hidden(def.label);
                
            
            operators.push({operator: {linkType: def.linkType, appName: appName, modelName: foreignRecordClass.getMeta('modelName'), filterName: def.filterName}, label: label});
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
        this.foreignField = this.foreignRecordClass.getMeta('idProperty');
        
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
        
        if (! this.field) {
            this.field = {linkType: this.linkType, appName: this.foreignRecordClass.getMeta('appName'), modelName: this.foreignRecordClass.getMeta('modelName'), filterName: this.filterName};
        }
        
        if (Ext.isObject(this.field)) {
            this.field.toString = this.objectToString;
        }
        
        if (! this.operators) {
            this.operators = ['equals', 'definedBy'];
        }
        
        if (! this.defaultOperator) {
            this.defaultOperator = 'equals';
        }
        
    },
    
    onDefineRelatedRecord: function(filter) {
        var operator = filter.formFields.operator.getValue(),
            ftb = this.ftb,
            foreignRecordClass;
        
        try {
            if (! filter.sheet) {
                foreignRecordClass = operator.appName && operator.modelName ? 
                    Tine.Tinebase.data.RecordMgr.get(operator.appName, operator.modelName) :
                    this.foreignRecordClass;
                    
                filter.sheet = new Tine.widgets.grid.FilterToolbar({
                    recordClass: foreignRecordClass,
                    defaultFilter: 'query'
                });
                
                ftb.addFilterSheet(filter.sheet);
            }
            
            ftb.setActiveSheet(filter.sheet);
            filter.formFields.value.setText(_('Edit Definition ...'));
        } catch (e) {
            console.error(e.stack);
        }
    },
    
    /**
     * get filter data of (sub) filter sheet
     */
    getRelatedRecordValue: function(filter) {
        var sheet = filter.sheet;
        
        return sheet ? sheet.getValue() : null;
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
        if (! this.isGeneric) {
            return Tine.widgets.grid.ForeignRecordFilter.superclass.operatorRenderer.apply(this, arguments);
        }
        
        var operator = new Ext.form.ComboBox({
            filter: filter,
            width: 80,
            id: 'tw-ftb-frow-operatorcombo-' + filter.id,
            mode: 'local',
            lazyInit: false,
            emptyText: _('select a operator'),
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: this.operatorStore,
            displayField: 'label',
            valueField: 'operator',
            value: filter.get('operator') ? filter.get('operator') : this.defaultOperator,
            renderTo: el
        });
        operator.on('select', function(combo, newRecord, newKey) {
            if (combo.value != combo.filter.get('operator')) {
                this.onOperatorChange(combo.filter, combo.value);
            }
        }, this);
        
        return operator;
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        filter.set('value', '');
        
        var el = filter.formFields.value.el.up('td[class^=tw-ftb-frow-value]');
        
        // NOTE: removeMode got introduced on ext3.1 but is not docuemented
        //       'childonly' is no ext mode, we just need something other than 'container'
        filter.formFields.value.removeMode = 'childsonly';
        filter.formFields.value.destroy();
        delete filter.formFields.value;
        
        filter.formFields.value = this.valueRenderer(filter, el);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value;

        switch(filter.formFields.operator.getValue()) {
            case 'equals':
                value = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                    recordClass: this.foreignRecordClass,
                    filter: filter,
                    blurOnSelect: true,
                    width: this.filterValueWidth,
                    listWidth: 500,
                    listAlign: 'tr-br',
                    id: 'tw-ftb-frow-valuefield-' + filter.id,
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    renderTo: el
                });
                value.on('specialkey', function(field, e){
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                }, this);
                break;
                
            default: 
                value = new Ext.Button({
                    text: _('Start Definition ...'),
                    filter: filter,
                    width: this.filterValueWidth,
                    id: 'tw-ftb-frow-valuefield-' + filter.id,
                    renderTo: el,
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    handler: this.onDefineRelatedRecord.createDelegate(this, [filter]),
                    scope: this,
                    getValue: this.getRelatedRecordValue.createDelegate(this, [filter])
                });
                
                // show button
                el.addClass('x-btn-over');
                break;
        }

        
        return value;
    },
    
//    
//    getSubFilters: function() {
//        var filterConfigs = this.foreignRecordClass.getFilterModel();
//        
//        Ext.each(filterConfigs, function(config) {
//            this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
//        }, this);
//        
//        return this.subFilterModels;
//    },
//    
    
    objectToString: function() {
        return Ext.encode(this);
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
