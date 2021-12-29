/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import "../../Tinebase/js/ux/form/FieldClipboardPlugin";

Ext.ns('Tine.Filemanager');

Tine.Filemanager.FileSystemLinkDialog = Ext.extend(Ext.FormPanel, {

    /**
     * Filemanager
     */
    app: null,
    link: null,

    windowNamePrefix: 'FileSystemLinkDialog_',
    trigger1Class:'x-form-trigger',
    
    cls: 'tw-editdialog',
    layout: 'fit',
    border: false,
    frame: false,

    /**
     * Constructor.
     */
    initComponent: async function () {
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
                        xtype: 'textfield',
                        plugins: [{
                            ptype: 'ux.fieldclipboardplugin'
                        }],
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Use this link to share the entry with other system users'),
                        name: 'link',
                        value: this.link,
                        maxLength: 100,
                        allowBlank: true,
                        readOnly: true
                    }]
                ]
            }]
        }];
        
        Tine.Filemanager.FileSystemLinkDialog.superclass.initComponent.call(this);
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
        let body = `<a href="${this.link}" target="_blank">${Ext.ux.util.urlCoder.decodeURI(this.link)}</a>`;
        
        let defaults = Tine.Felamimail.Model.Message.getDefaultData();
        defaults.body = body + Tine.Felamimail.getSignature();
        
        const record = new Tine.Felamimail.Model.Message(defaults, 0);

        Tine.Felamimail.MessageEditDialog.openWindow({
            record: record
        });
    }
});

Tine.Filemanager.FileSystemLinkDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    return Tine.WindowFactory.getWindow({
        width: 350,
        height: 150,
        name: Tine.Filemanager.FileSystemLinkDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.FileSystemLinkDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
};
