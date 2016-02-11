/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets.grid');

/**
 * config grid panel
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.PickerGridLayerCombo
 * @extends     Tine.widgets.grid.LayerCombo
 */
Tine.widgets.grid.PickerGridLayerCombo = Ext.extend(Ext.ux.form.LayerCombo, {

    hideButtons: false,
    layerAlign: 'tr-br?',
    minLayerWidth: 400,
    layerHeight: 300,
    allowBlur: true,

    lazyInit: true,

    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },

    pickerGrid: null,
    inEditor: true,
    gridRecordClass: null,

    initComponent: function () {
        Tine.widgets.grid.PickerGridLayerCombo.superclass.initComponent.call(this);

        this.on('beforecollapse', this.onBeforeCollapse, this);
    },

    getItems: function () {
        this.pickerGrid = new Tine.widgets.grid.PickerGridPanel({
            recordClass: this.gridRecordClass,
            height: this.layerHeight - 40 || 'auto',
            onStoreChange: Ext.emptyFn
        });

        return [this.pickerGrid];
    },

    /**
     * cancel collapse if ctx menu is shown
     */
    onBeforeCollapse: function () {
        return this.pickerGrid
            && (!this.pickerGrid.contextMenu || this.pickerGrid.contextMenu.hidden)
            && !this.pickerGrid.editing;
    },

    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function (value) {
        this.currentValue = value;
        // to string overwrite, to make sure record is changed.
        Tine.Tinebase.common.assertComparable(this.currentValue);
        return this;
    },

    /**
     * sets values to innerForm (grid)
     */
    setFormValue: function (value) {
        this.setStoreFromArray(listRoles);
    },

    /**
     * retrieves values from grid
     *
     * @returns {*|Array}
     */
    getFormValue: function () {
        return this.getFromStoreAsArray();
    },

    /**
     * get values from store (as array)
     *
     * @param {Array}
     *
     * TODO move to picker grid?
     */
    setStoreFromArray: function(data) {
        //this.pickerGrid.getStore().clearData();
        this.pickerGrid.getStore().removeAll();

        for (var i = data.length-1; i >=0; --i) {
            var recordData = data[i];

            this.pickerGrid.getStore().insert(0, new this.gridRecordClass(recordData));
        }
    },

    /**
     * get values from store (as array)
     *
     * @return {Array}
     *
     * TODO move to picker grid?
     */
    getFromStoreAsArray: function() {
        var result = [];
        this.pickerGrid.getStore().each(function(record) {
            result.push(record.data);
        }, this);

        return result;
    }
});
