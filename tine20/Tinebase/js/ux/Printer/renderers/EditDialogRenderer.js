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

    stylesheetPath: 'Tinebase/css/widgets/print.css',

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
                    if (
                          (field instanceof Ext.form.ComboBox || ! Ext.isString(recordData[name]))
                        && Ext.isFunction(field.getRawValue)
                    ) {
                        var name = field.getName(),
                            isCustomField = String(name).match(/^customfield_(.*)/),
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
    },

    generateBody: function (editDialog, data) {
        var _ = window.lodash,
            me = this,
            appName = editDialog.recordClass.getMeta('appName');

        data.titleHTML = editDialog.record.getTitle();
        data.customFieldHTML = Tine.widgets.customfields.Renderer.renderAll(appName, editDialog.recordClass, data.customfields);


        // @TODO render container
        // @TODO render tags, notes, attachments
        return new Promise(function (fulfill, reject) {
            me.generateRecordHTML(editDialog).then(function(recordHTML) {
                data.recordHTML = recordHTML;

                var bodyTpl = new Ext.XTemplate(
                    '<div class="rp-print-single">',
                    '    {[Tine.widgets.printer.headerRenderer()]}',
                    '    <div class="rp-print-single-summary">{titleHTML}</div>',
                    '    <table>',
                    '        <tr>',
                    '            <td>',
                    '            </td>',
                    '            <td>',
                    '                <div class="rp-print-single-block">',
                    '                    {values.recordHTML}',
                    '                    <br/>',
                    '                    {values.customFieldHTML}',
                    '                    <br/>',
                    '                    <div class="cal-print-single-block-heading">', window.i18n._('Related to'), '</div>',
                    '                    <div class="rp-print-single-block">',
                    '                        {[this.relationRenderer(values.relations)]}',
                    '                    </div>',
                    '                </div>',
                    '            </td>',
                    '        </tr>',
                    '    </table>',
                    '</div>',
                    {
                        relationRenderer: function (values) {
                            return Tine.widgets.relation.Renderer.renderAll(values);
                        }
                    });
                fulfill(bodyTpl.apply(data));
            })
        })
    },

    /**
     * generate html for direct record properties
     *
     *
     * @param editDialog
     * @param {Array} recordComponents Array of components/strings
     * @returns {Promise}
     */
    generateRecordHTML: function(editDialog, recordComponents) {
        var _ = window.lodash,
            me = this,
            appName = editDialog.recordClass.getMeta('appName'),
            app = Tine.Tinebase.appMgr.get(appName),
            form = editDialog.recordForm || editDialog.getForm(),
            fields = form.items.items;

        recordComponents = recordComponents || fields;

        return _.reduce(recordComponents || fields, function(promise, cmp) {
            return promise.then(function(html) {
                return new Promise(function(resolve, reject) {
                    var renderer = Ext.ux.Printer.findRenderer(_.isString(cmp) ? form.findField(cmp) : cmp),
                        result = renderer ? renderer.generateBody(cmp) : '';

                    if (_.isString(result)) {
                        resolve(html + result);
                    } else {
                        result.then(function(string) {
                            resolve(html + string);
                        })
                    }
                });
            });
        }, Promise.resolve(''));
    },

    getTitle: function(editDialog) {
        return editDialog.recordClass.getRecordName() + ': ' + editDialog.record.getTitle();
    }

});
