/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ProductAggregateGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Product aggregate grid panel</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ProductAggregateGridPanel
 */
Tine.Sales.ProductAggregateAccountableLayerCombo = Ext.extend(Ext.ux.form.LayerCombo, {
    layerHeight: 200,
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    // TODO should be fetched from MC
    labelField: 'vm_name',
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

        this.selectionWidget = Tine.widgets.form.RecordPickerManager.get(
            this.recordClass.getMeta('appName'),
            this.recordClass.getMeta('modelName')
        );

        Tine.widgets.grid.PickerFilterValueField.superclass.initComponent.call(this);
    },

    /**
     * get form values
     *
     * @return {Array}
     */
    getFormValue: function() {
        var value = [];

        this.store.each(function(record) {
            value.push(record);
        }, this);

        return value;
    },

    /**
     * get items
     *
     * @return {Array}
     */
    getItems: function() {
        var me = this,
            items = [];

        this.initSelectionWidget();

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
                    items: [ me.selectionWidget ]
                });
            }
        });

        items.push(this.pickerGridPanel);

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
        var labelColumn = {id: this.labelField, header: String.format(i18n._('Selected  {0}'), this.recordClass.getMeta('recordsName')), dataIndex: this.labelField};
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
        this.selectionWidget.suspendEvents();
        this.selectionWidget.clearValue();
        this.selectionWidget.resumeEvents();
    },

    /**
     * adds record from selection widget to store
     *
     * @param {Object} recordData
     */
    addRecord: function(recordData) {
        Tine.log.debug('Tine.widgets.grid.PickerFilterValueField::addRecord()');
        Tine.log.debug(recordData);

        var data = (recordData.data) ? recordData.data : recordData.attributes ? recordData.attributes : recordData;

        var existingRecord = this.store.getById(recordData.id);
        if (! existingRecord) {
            this.store.add(new this.recordClass(data));

        } else {
            var idx = this.store.indexOf(existingRecord);
            var row = this.pickerGridPanel.getView().getRow(idx);
            Ext.fly(row).highlight();
        }

        if (this.selectionWidget.selectPanel) {
            this.selectionWidget.selectPanel.close();
        }
    },

    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];

        Tine.log.debug('Tine.widgets.grid.PickerFilterValueField::setValue()');
        Tine.log.debug(value);

        var recordText = [];
        this.currentValue = [];

        this.store.removeAll();
        var record, id, text;
        for (var i=0; i < value.length; i++) {
            text = this.getRecordText(value[i]);
            if (text && text !== '') {
                recordText.push(text);
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
        var id = (Ext.isString(value)) ? value : (value ? (Ext.isString(value.id) ? value.id : value.id.id) : ''),
            record = (id ? (this.valueStore) ? this.valueStore.getById(id) : ((! Ext.isString(value)) ? new this.recordClass(value, id) : null) : null);

        Tine.log.debug('Tine.widgets.grid.PickerFilterValueField::getRecordText()');
        Tine.log.debug(record);

        if (! record) {
            return '';
        }

        // FIXME how can this happen??
        if (record.data.data) {
            record = new this.recordClass(record.data.data, id);
        }

        // always copy/clone record because it can't exist in 2 different stores
        this.store.add(record.copy());
        this.currentValue.push(record.data);

        var text = record.id[this.labelField];
        return text;
    },

    /**
     * cancel collapse if ctx menu or record selection is shown
     *
     * @return Boolean
     */
    onBeforeCollapse: function() {
        var result = true;

        if (this.pickerGridPanel) {
            var contextMenuVisible = this.pickerGridPanel.contextMenu && ! this.pickerGridPanel.contextMenu.hidden,
                selectionVisible = this.isSelectionVisible();
            result = ! (contextMenuVisible || selectionVisible);
        }

        Tine.log.debug('Tine.widgets.grid.PickerFilterValueField::onBeforeCollapse() - collapse: ' + result);

        return result;
    },

    /**
     * is selection visible ?
     * - overwrite this when extending to make sure that the selection widget is no longer visible on collapse
     *
     * @return {Boolean}
     */
    isSelectionVisible: function() {
        return false;
    }
});
