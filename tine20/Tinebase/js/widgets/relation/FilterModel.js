/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.relation');

/**
 * @namespace   Tine.widgets.relation
 * @class       Tine.widgets.relation.FilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @todo CARE FOR SHEET DESTRUCTON!
 * @todo CARE FOR OPERATOR CHANGE
 */
Tine.widgets.relation.FilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    valueType: 'relation',
    field: 'relation',
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = _('Relation');
        
        // @TODO whipe some models?
        var relatedModels = [];
        Tine.Tinebase.data.RecordMgr.eachKey(function(operator, record) {
            if (record.hasField('relations') && Ext.isFunction(record.getFilterModel)) {
                var label = Tine.Tinebase.appMgr.get(record.getMeta('appName')).i18n._hidden(record.getMeta('recordsName'));
                
                relatedModels.push({operator: operator, label: label});
            }
        }, this);
        
        
        this.relatedModelStore = this.fieldStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: relatedModels
        });
        
        this.defaultOperator = this.relatedModelStore.getAt(0).get('operator');
        
        Tine.widgets.relation.FilterModel.superclass.initComponent.call(this);
        
    },
    
    onDefineRelatedRecord: function(filter) {
        var operator = filter.formFields.operator.getValue();
        console.log(operator);
        console.log(Tine.Tinebase.data.RecordMgr.get(operator));
        
        if (! filter.sheet) {
            filter.sheet = new Tine.widgets.grid.FilterToolbar({
                recordClass: Tine.Tinebase.data.RecordMgr.get(operator),
                defaultFilter: 'query'
            });
            
            this.ftb.addFilterSheet(filter.sheet);
        }
        
        this.ftb.setActiveSheet(filter.sheet);
        filter.formFields.value.setText(_('Defined by ...'));
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
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
            store: this.relatedModelStore,
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
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Ext.Button({
            text: _('Define ...'),
            filter: filter,
            width: this.filterValueWidth,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            renderTo: el,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            handler: this.onDefineRelatedRecord.createDelegate(this, [filter]),
            scope: this,
            getValue: function() {}
        });
        
        // show button
        el.addClass('x-btn-over');
        
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.relation'] = Tine.widgets.relation.FilterModel;

