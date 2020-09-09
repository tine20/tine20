/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase.widgets.form');

/**
 * config grid panel
 *
 * @namespace   Tine.Tinebase.widgets.form
 * @class       Tine.Tinebase.widgets.form.RecordsPickerCombo
 * @extends     Tine.widgets.grid.LayerCombo
 */
Tine.Tinebase.widgets.form.RecordsPickerCombo = Ext.extend(Ext.ux.form.LayerCombo, {

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
    recordClass: null,

    // NOTE: minWidth gets not evaluated by ext - it's just a hint for consumers!
    minWidth: 200,

    /**
     * config spec for additionalFilters - passed to PickerGridPanel
     *
     * @type: {object} e.g.
     * additionalFilterConfig: {config: { 'name': 'configName', 'appName': 'myApp'}}
     * additionalFilterConfig: {preference: {'appName': 'myApp', 'name': 'preferenceName}}
     * additionalFilterConfig: {favorite: {'appName': 'myApp', 'id': 'favoriteId', 'name': 'optionallyuseaname'}}
     */
    additionalFilterSpec: null,

    initComponent: function () {
        this.emptyText = this.emptyText || (this.readOnly || this.disabled ? '' : i18n._('Search for records ...'));
        this.currentValue = this.currentValue || [];

        Tine.Tinebase.widgets.form.RecordsPickerCombo.superclass.initComponent.call(this);
        this.store = new Ext.data.SimpleStore({
            fields: this.recordClass
        });

        this.on('beforecollapse', this.onBeforeCollapse, this);
    },

    getItems: function () {
        this.pickerGrid = new Tine.widgets.grid.PickerGridPanel({
            recordClass: this.recordClass,
            height: this.layerHeight - 40 || 'auto',
            onStoreChange: Ext.emptyFn,
            store: this.store,
            additionalFilterSpec: this.additionalFilterSpec
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
        var _ = window.lodash;

        value = value || [];
        this.setStoreFromArray(value);
        if (this.rendered) {
            var text = _.reduce(this.store.data.items, function(result, record) {
                return result.concat(record.getTitle());
            }, []).join(', ');
            this.setRawValue(text || this.emptyText);
            this.el[(text ? 'remove' : 'add') + 'Class'](this.emptyClass);
        }

        var oldValue = this.currentValue;
        this.currentValue = value;
        this.value = value;
        Tine.Tinebase.common.assertComparable(this.currentValue);

        if (JSON.stringify(value) != JSON.stringify(oldValue)){
            this.fireEvent('change', this, value, oldValue);
        }

        return this;
    },

    reset : function() {
        this.currentValue = this.originalValue;

        Tine.Tinebase.widgets.form.RecordsPickerCombo.superclass.reset.apply(this, arguments);
    },

    afterRender: function () {

        Tine.Tinebase.widgets.form.RecordsPickerCombo.superclass.afterRender.apply(this, arguments);
        if (this.currentValue) {
            this.setValue(this.currentValue);
        }
    },
    /**
     * sets values to innerForm (grid)
     */
    setFormValue: function (value) {
        if (!value) {
            value = [];
        }

        this.setStoreFromArray(value);
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
     */
    setStoreFromArray: function(data) {
        data = data || [];
        //this.pickerGrid.getStore().clearData();
        this.store.removeAll();

        for (var i = data.length-1; i >=0; --i) {
            var recordData = data[i],
                newRecord = new this.recordClass(recordData);
            this.store.insert(0, newRecord);
        }
    },

    /**
     * get values from store (as array)
     *
     * @return {Array}
     *
     */
    getFromStoreAsArray: function() {
        var result = [];
        this.store.each(function(record) {
            result.push(record.data);
        }, this);

        return result;
    }
});

Ext.reg('tinerecordspickercombobox', Tine.Tinebase.widgets.form.RecordsPickerCombo);
