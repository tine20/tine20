/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * config grid panel
 *
 * @namespace   Tine.Admin.config
 * @class       Tine.Admin.config.ConfigField
 * @extends     Tine.widgets.grid.LayerCombo
 */
Tine.Tinebase.widgets.keyfield.ConfigField = Ext.extend(Ext.ux.form.LayerCombo, {

    /**
     * @cfg {Admin.Model.Config} ConfigRecord
     */
    configRecord: null,

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

    configGrid: null,

    initComponent: function () {
        this.on('beforecollapse', this.onBeforeCollapse, this);

        this.supr().initComponent.call(this);
    },

    getFormValue: function () {
        return this.configGrid.getValue();
    },

    getItems: function () {

        this.configGrid = new Tine.Tinebase.widgets.keyfield.ConfigGrid({
            title: _('Key Field Records'),
            height: this.layerHeight - 40 || 'auto',
            configRecord: this.configRecord,
            onStoreChange: Ext.emptyFn
        });

        return [this.configGrid];
    },

    /**
     * cancel collapse if ctx menu is shown
     */
    onBeforeCollapse: function () {

        return (!this.configGrid.contextMenu || this.configGrid.contextMenu.hidden) && !this.configGrid.editing;
    },

    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function (value) {
        this.currentValue = value;
        Tine.Tinebase.common.assertComparable(this.currentValue);

        this.setRawValue('...');
        return this;
    },

    /**
     * sets values to innerForm
     */
    setFormValue: function (value) {
        this.configGrid.setValue(this.currentValue);
    }
});
