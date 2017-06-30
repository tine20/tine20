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
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 */
Tine.widgets.grid.PickerGridLayerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

    /**
     * @cfg {Record} gridRecordClass
     */
    gridRecordClass: null,

    initComponent: function () {
        this.recordClass = this.gridRecordClass;
        Tine.widgets.grid.PickerGridLayerCombo.superclass.initComponent.call(this);

    }

});

Ext.reg('tinepickergridlayercombo', Tine.widgets.grid.PickerGridLayerCombo);
