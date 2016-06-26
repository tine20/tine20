/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.widgets.dialog');

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.SimpleRecordEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * SimpleRecord Edit Dialog <br>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.widgets.dialog.SimpleRecordEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    windowNamePrefix: 'SimpleRecordEditWindow_',

    getFormItems: function () {
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items: [{
                title: this.app.i18n._(this.recordClass.getMeta('recordName')),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 1
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Name'),
                        name: 'name',
                        allowBlank: false
                    }, {
                        fieldLabel: this.app.i18n._('Description'),
                        name: 'description',
                        xtype:'textarea'
                    }]
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record && ! this.copyRecord) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })
            ]
        };
    }
});
