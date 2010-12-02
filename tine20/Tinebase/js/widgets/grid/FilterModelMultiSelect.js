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
     * @cfg recordClass
     * @type Tine.Tinebase.data.Record
     */
    recordClass: null,
    
    /**
     * xtype for the value select field
     * 
     * @cfg valueXtype
     * @type String
     */
    valueXtype: 'checkbox',
    
    /**
     * @cfg labelField
     * @type String
     */
    labelField: 'name',
    
    /**
     * @cfg layerHeight
     * @type Integer
     */
    layerHeight: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.operators = this.operators || ['in', 'notin'];
        
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
            valueStore: this.valueStore,
            xtype: this.valueXtype,
            recordClass: this.recordClass,
            labelField: this.labelField,
            layerHeight: this.layerHeight
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
 */
Tine.widgets.grid.FilterModelMultiSelectValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    labelField: 'name',
    xtype: 'checkbox',
    recordClass: null,
    valueStore: null,
    
    /**
     * init
     */
    initComponent: function() {
        this.on('beforecollapse', this.onBeforeCollapse, this);
        if (! this.store) {
            this.store = new Ext.data.SimpleStore({
                fields: this.recordClass
            });
        }
        
        Tine.widgets.grid.FilterModelMultiSelectValueField.superclass.initComponent.call(this);
    },
    
    /**
     * get form values
     * 
     * @return {Array}
     */
    getFormValue: function() {
        var values = [];

        if (this.xtype == 'checkbox') {
            var formValues = this.getInnerForm().getForm().getValues();
            for (var id in formValues) {
                if (formValues[id] === 'on' && this.valueStore.getById(id)) {
                    values.push(id);
                }
            }
        } else {
            this.store.each(function(record) {
                values.push(record.data);
            }, this);            
        }
        
        return values;
    },
    
    /**
     * get items
     * 
     * @return {Array}
     */
    getItems: function() {
        var items = [];

        if (this.xtype == 'wdgt.pickergrid') {
//            var selectionCombo = this.containerSelectionCombo = new Tine.widgets.container.selectionComboBox({
//                allowNodeSelect: true,
//                allowBlank: true,
//                blurOnSelect: true,
//                recordClass: this.recordClass,
//                appName: this.recordClass.getMeta('appName'),
//                containerName: this.containerName,
//                containersName: this.containersName,
//                listeners: {
//                    scope: this, 
//                    select: this.onContainerSelect
//                }
//            });
                    
            this.pickerGridPanel = new Tine.widgets.grid.PickerGridPanel({
                height: this.layerHeight || 'auto',
                recordClass: this.recordClass,
                store: this.store,
                autoExpandColumn: this.labelField,
                getColumnModel: this.getColumnModel.createDelegate(this),
                initActionsAndToolbars: function() {
                    Tine.widgets.grid.PickerGridPanel.prototype.initActionsAndToolbars.call(this);
                    
//                    this.tbar = new Ext.Toolbar({
//                        layout: 'fit',
//                        items: [
//                            this.selectionCombo
//                        ]
//                    });
                }
            });
            
            items.push(this.pickerGridPanel);
            
        } else if (this.xtype == 'checkbox') {
            this.valueStore.each(function(record) {
                items.push({
                    xtype: this.xtype,
                    boxLabel: record.get(this.labelField),
                    name: record.get('id')
                    //icon: record.get('status_icon'),
                });
            }, this);
        }
        
        return items;
    },
    
    /**
     * @return Ext.grid.ColumnModel
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false
            },
            columns:  [
                {id: this.labelField, header: String.format(_('Selected  {0}'), this.recordClass.getMeta('recordsName')), dataIndex: this.labelField}
            ]
        });
    },
    
    /**
     * record select
     * 
     * @param {String} field
     * @param {Object} recordData
     */
    onRecordSelect: function(field, recordData) {
        var existingRecord = this.store.getById(recordData.id);
        if (! existingRecord) {
            var data = (recordData.data) ? recordData.data : recordData;
            this.store.add(new Tine.Tinebase.Model.Container(data));
            
        } else {
            var idx = this.store.indexOf(existingRecord);
            var row = this.pickerGridPanel.getView().getRow(idx);
            Ext.fly(row).highlight();
        }
        
        this.containerSelectionCombo.clearValue();
    },
    
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        
        var recordText = [];
        this.currentValue = [];
        
        if (this.xtype == 'checkbox') {
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
        } else {
            this.store.removeAll();
            var record;
            console.log(value);
            for (var i=0; i < value.length; i++) {
                record = this.valueStore.getById(value[i]);
                if (record) {
                    this.currentValue.push(record.id);
                    this.store.add(record);
                    recordText.push(record.get(this.labelField));
                }
            }
        }
        
        this.setRawValue(recordText.join(', '));
        
        return this;
    },
    
    /**
     * cancel collapse if ctx menu or record selection is shown
     * 
     * @return Boolean
     */
    onBeforeCollapse: function() {
        if (this.pickerGridPanel) {
            var contextMenuVisible = this.pickerGridPanel.contextMenu && ! this.pickerGridPanel.contextMenu.hidden;
            return ! (contextMenuVisible);
        } else {
            return true;
        }
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
    }
});
