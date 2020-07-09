/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.FilePublishedDialog = Ext.extend(Ext.Panel, {
    /**
     * Tine.Filemanager.Model.DownloadLink
     */
    record: null,

    /**
     * Password used to protect downloadlink
     */
    password: null,

    /**
     * Filemanager
     */
    app: null,

    windowNamePrefix: 'FilePublishedDialog_',

    layout: 'fit',
    border: false,
    frame: false,

    /**
     * Constructor.
     */
    initComponent: function () {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.items = [{
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: [
                    [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('URL'),
                        name: 'url',
                        value: this.record.get('url'),
                        maxLength: 100,
                        allowBlank: true,
                        readOnly: true
                    }, {
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Password'),
                        name: 'url',
                        value: this.password,
                        xtype: 'tw-passwordTriggerField',
                        allowBlank: true,
                        editable: false
                    }, {
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Valid until'),
                        name: 'url',
                        xtype: 'datefield',
                        editable: false,
                        readOnly: true,
                        value: this.record.get('expiry_time')
                    }]
                ]
            }]
        }];

        var me = this;

        this.tbar = [];

        if (Tine.Tinebase.appMgr.isEnabled('Felamimail')) {
            this.sendByMailAction = new Ext.Action({
                disabled: false,
                text: this.app.i18n._('Send as e-mail'),
                iconCls: 'action_composeEmail',
                minWidth: 70,
                handler: this.onSendAsMail.createDelegate(me),
                scope: this
            });
            this.tbar.push(this.sendByMailAction);
        }


        Tine.Filemanager.FilePublishedDialog.superclass.initComponent.call(this);
    },

    onSendAsMail: function () {
        var body =  this.app.i18n._("Download") + ": " + this.record.get('url');

        if (this.password) {
            body += "\n" + this.app.i18n._("Password") + ": " + this.password;
        }

        var record = new Tine.Felamimail.Model.Message({
            'body': body
        });

        Tine.Felamimail.MessageEditDialog.openWindow({
            record: record
        });
    }
});

Tine.Filemanager.FilePublishedDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    return Tine.WindowFactory.getWindow({
        width: 350,
        height: 200,
        name: Tine.Filemanager.FilePublishedDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.FilePublishedDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
};
