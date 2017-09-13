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
        return new Promise(function (fulfill, reject) {
            var _ = window.lodash;

            // hack to have all form items rendered
            _.each(editDialog.findByType('tabpanel'), function(tabpanel) {
                var active = tabpanel.getActiveTab();
                _.each(tabpanel.items.items, function(item) {
                    tabpanel.setActiveTab(item);
                });
                tabpanel.setActiveTab(active);
            });

            // wait for rendering
            (function() {
                var recordData = Ext.util.JSON.decode(Ext.util.JSON.encode(editDialog.record.data));

                editDialog.getForm().items.each(function (field) {
                    if (field instanceof Ext.form.ComboBox || ! Ext.isString(recordData[name])) {
                        var name = field.getName(),
                            isCustomField = name.match(/^customfield_(.*)/),
                            string = field.getRawValue();

                        if (isCustomField) {
                            _.set(recordData, 'customfields.' + isCustomField[1], string);
                        } else {
                            recordData[name] = string;

                        }
                    }
                });

                fulfill(recordData);
            }).defer(1000);

        });
    }
});
