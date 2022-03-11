/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import "../../Tinebase/js/ux/form/FieldClipboardPlugin";

Ext.ns('Tine.Filemanager');

Tine.Filemanager.FilePublishedDialog = Ext.extend(Ext.FormPanel, {
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
    
    cls: 'tw-editdialog',
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
    
        this.initButtons();
        
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
                    columnWidth: 1,
                },
                items: [
                    [{
                        plugins: [{
                            ptype: 'ux.fieldclipboardplugin',
                        }],
                        fieldLabel: this.app.i18n._('URL'),
                        name: 'url',
                        value: this.record.get('url'),
                        maxLength: 100,
                        allowBlank: true,
                        readOnly: true
                    }, {
                        fieldLabel: this.app.i18n._('Password'),
                        name: 'url',
                        value: this.password,
                        xtype: 'tw-passwordTriggerField',
                        allowBlank: true,
                        editable: false
                    }, {
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

        Tine.Filemanager.FilePublishedDialog.superclass.initComponent.call(this);
    },
    
    initButtons: function () {
        this.fbar = ['->', {
            xtype: 'button',
            text: this.app.i18n._('Send as e-mail'),
            iconCls: 'action_composeEmail',
            handler: this.onSendAsMail.createDelegate(this),
            scope: this,
            hidden: !Tine.Tinebase.appMgr.isEnabled('Felamimail')
        }, {
            text: this.app.i18n._('Close'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        }];
    },
    
    onCancel: function () {
        this.window.close();
    },

    onSendAsMail: function () {
        let body =  this.app.i18n._("Download") + ": " + this.record.get('url');
        
        if (this.password) {
            body += '<br>' + this.app.i18n._("Password") + ": " + this.password;
        }
        
        let defaults = Tine.Felamimail.Model.Message.getDefaultData();
        defaults.body = body + Tine.Felamimail.getSignature();
        
        const record = new Tine.Felamimail.Model.Message(defaults, 0);

        Tine.Felamimail.MessageEditDialog.openWindow({
            record: record
        });
    }
});

Tine.Filemanager.FilePublishedDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    return Tine.WindowFactory.getWindow({
        width: 350,
        height: 200,
        name: Tine.Filemanager.FilePublishedDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.FilePublishedDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
};
