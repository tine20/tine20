/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         rename to PickerFilter
 * TODO         remove checkbox stuff
 * TODO         container / folder / tag filter should extend this
 */
Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterModelMultiSelect
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
     * @cfg multiselectField
     */
    multiselectFieldConfig: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.operators = this.operators || ['in', 'notin'];
        this.multiselectFieldConfig = this.multiselectFieldConfig || {};
        
        Tine.widgets.grid.FilterModelMultiSelect.superclass.initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.widgets.grid.FilterModelMultiSelectValueField(Ext.apply({
            app: this.app,
            filter: filter,
            width: this.filterValueWidth,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        }, this.multiselectFieldConfig));
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

    selectionWidget: null,
    labelRenderer: Ext.emptyFn,
    
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
            this.initSelectionWidget();
            
            // defeat scoping :)
            selectionWidget = this.selectionWidget;
            
            this.pickerGridPanel = new Tine.widgets.grid.PickerGridPanel({
                height: this.layerHeight || 'auto',
                recordClass: this.recordClass,
                store: this.store,
                autoExpandColumn: this.labelField,
                getColumnModel: this.getColumnModel.createDelegate(this),
                initActionsAndToolbars: function() {
                    Tine.widgets.grid.PickerGridPanel.prototype.initActionsAndToolbars.call(this);
                    this.tbar = new Ext.Toolbar({
                        layout: 'fit',
                        items: [ selectionWidget ]
                    });
                }
            });
            
            items.push(this.pickerGridPanel);
            
        } else if (this.xtype == 'checkbox') {
            this.valueStore.each(function(record) {
                items.push({
                    xtype: this.xtype,
                    boxLabel: record.get(this.labelField),
                    name: record.get('id')
                    //icon: record.get('icon'),
                });
            }, this);
        }
        
        return items;
    },
    
    /**
     * init selection widget
     */
    initSelectionWidget: function() {
        this.selectionWidget.on('select', this.onRecordSelect, this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     */
    getColumnModel: function() {
        var labelColumn = {id: this.labelField, header: String.format(_('Selected  {0}'), this.recordClass.getMeta('recordsName')), dataIndex: this.labelField};
        if (this.labelRenderer != Ext.emptyFn) {
            labelColumn.renderer = this.labelRenderer;
        }
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false
            },
            columns:  [ labelColumn ]
        });
    },
    
    /**
     * record select
     * 
     * @param {String} field
     * @param {Object} recordData
     */
    onRecordSelect: function(field, recordData) {
        this.addRecord(recordData);        
        this.selectionWidget.clearValue();
    },
    
    /**
     * adds record from selection widget to store
     * 
     * @param {Object} recordData
     */
    addRecord: function(recordData) {
        var data = (recordData.data) ? recordData.data : recordData.attributes ? recordData.attributes : recordData;
        
        var existingRecord = this.store.getById(recordData.id);
        if (! existingRecord) {
            
            this.store.add(new Tine.Tinebase.Model.Container(data));
            
        } else {
            var idx = this.store.indexOf(existingRecord);
            var row = this.pickerGridPanel.getView().getRow(idx);
            Ext.fly(row).highlight();
        }
        
        this.selectionWidget.selectPanel.close();
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
            var record, id, text;
            for (var i=0; i < value.length; i++) {
                text = this.getRecordText(value[i]);
                if (text && text !== '') {
                    recordText.push(text);
                }
            }
        }
        
        this.setRawValue(recordText.join(', '));
        
        return this;
    },
    
    /**
     * get text from record defined by value (id or something else)
     * 
     * @param {String|Object} value
     * @return {String}
     */
    getRecordText: function(value) {
        var text = '';
        
        id = (Ext.isString(value)) ? value : value.id;
        record = this.valueStore.getById(id);
        if (record) {
            this.currentValue.push(record.id);
            // always copy/clone record because it can't exist in 2 different stores
            this.store.add(record.copy());
            text = record.get(this.labelField);
            text = (this.labelRenderer != Ext.emptyFn) ? this.labelRenderer(text) : text;
        }
        
        return text;
    },
    
    /**
     * cancel collapse if ctx menu or record selection is shown
     * 
     * @return Boolean
     */
    onBeforeCollapse: function() {
        if (this.pickerGridPanel) {
            var contextMenuVisible = this.pickerGridPanel.contextMenu && ! this.pickerGridPanel.contextMenu.hidden,
                selectionVisible = this.isSelectionVisible();
            
            return ! (contextMenuVisible || selectionVisible);
        } else {
            return true;
        }
    },
    
    /**
     * is selection visible ?
     * - overwrite this when extending to make sure that the selection widget is no longer visible on collapse
     * 
     * @return {Boolean}
     */
    isSelectionVisible: function() {
        return false;
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        if (this.xtype == 'checkbox') {
            this.getInnerForm().getForm().items.each(function(item) {
                item.setValue(value.indexOf(item.name) >= 0 ? 'on' : 'off');
            }, this);
        }
    }
});
