/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: LeadStateFilterModel.js 14369 2010-05-14 13:53:58Z c.weiss@metaways.de $
 */
Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterModelMultiSelect
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: LeadStateFilterModel.js 14369 2010-05-14 13:53:58Z c.weiss@metaways.de $
 */
Tine.widgets.grid.FilterModelMultiSelect = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    /**
     * @cfg field
     * @type String
     */
    field: '',

    /**
     * @cfg defaultOperator
     * @type String
     */
    defaultOperator: 'in',

    /**
     * @cfg defaultValue
     * @type String
     */
    defaultValue: '',

    /**
     * @cfg label
     * @type String
     */
    label: '',

    /**
     * @cfg filterValueWidth
     * @type Integer
     */
    filterValueWidth: 200,

    /**
     * @cfg valueStore
     * @type Ext.data.Store
     */
    valueStore: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.operators = ['in', 'notin'];
        
        Tine.widgets.grid.FilterModelMultiSelect.superclass.initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.widgets.grid.FilterModelMultiSelectValueField({
            app: this.app,
            filter: filter,
            width: this.filterValueWidth,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            valueStore: this.valueStore 
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        value.on('select', this.onFiltertrigger, this);
        
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.multiselect'] = Tine.widgets.grid.FilterModelMultiSelect;

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterModelMultiSelectValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: LeadStateFilterModel.js 14369 2010-05-14 13:53:58Z c.weiss@metaways.de $
 * 
 * TODO         allow grid panel as items (make this configurable)
 */
Tine.widgets.grid.FilterModelMultiSelectValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    labelField: 'name',
    xtype: 'checkbox',
    
    /**
     * get form values
     * 
     * @return {Array}
     */
    getFormValue: function() {
        var ids = [];

        var formValues = this.getInnerForm().getForm().getValues();
        for (var id in formValues) {
            if (formValues[id] === 'on' && this.valueStore.getById(id)) {
                ids.push(id);
            }
        }
        
        return ids;
    },
    
    /**
     * get items
     * 
     * @return {Array}
     */
    getItems: function() {
        var items = [];
        
        this.valueStore.each(function(record) {
            items.push({
                xtype: this.xtype,
                boxLabel: record.get(this.labelField),
                name: record.get('id')
                //icon: record.get('status_icon'),
            });
        }, this);
        
        return items;
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        
        var recordText = [];
        this.currentValue = [];
        
        this.valueStore.each(function(record) {
            var id = record.get('id');
            var name = record.get(this.labelField);
            Ext.each(value, function(valueId) {
                // NOTE: no type match id's might be int or string and should match anyway!
                if (valueId == id) {
                    recordText.push(name);
                    this.currentValue.push(id);
                }
            }, this);
        }, this);
        
        this.setRawValue(recordText.join(', '));
        
        return this;
    },
    
    /**
     * sets values to innerForm
     * 
     * TODO do we need this?
     */
    setFormValue: function(value) {
        this.getInnerForm().getForm().items.each(function(item) {
            item.setValue(value.indexOf(item.name) >= 0 ? 'on' : 'off');
        }, this);
    }
});
