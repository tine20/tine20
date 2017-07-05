/*
 * Tine 2.0
 *
 * @package     Offertory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux.Printer');

/**
 * Render any EditDialog.
 */
Ext.ux.Printer.EditDialogRenderer = Ext.extend(Ext.ux.Printer.BaseRenderer, {
    /**
     * @param {Tine.widgets.dialog.EditDialog} editDialog the edit dialog to print
     * @return {Array} Data suitable for use in the XTemplate
     */
    prepareData: function (editDialog) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            var form = editDialog.getForm(),
                recordData = Ext.util.JSON.decode(Ext.util.JSON.encode(editDialog.record.data));

            form.items.each(function (field) {
                if (field.hasOwnProperty('selectedRecord')) {
                    recordData[field.getName()] = field.getRawValue();
                }
            });

            fulfill(recordData);
        });
    }
});
