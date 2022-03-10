/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Admin');

/**
 * @namespace   Tine.Admin
 * @class       Tine.Admin.QuotaEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 */
Tine.Admin.QuotaEditDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    /**
     * @private
     */
    windowNamePrefix: 'uquotaEditWindow_',
    evalGrants: false,
    passwordConfirmWindow: null,
    mode: 'local',
    node: null,
    record: null,
    windowTitle: '',
    hasRequiredRight: false,

    /**
     * @private
     */
    initComponent() {
        // check manage personal/shared quota right
        this.translation = new Locale.Gettext();
        this.window.setTitle(this.windowTitle);
        
        if (this.customizeFields?.isPersonalNode) {
            this.hasRequiredRight = Tine.Tinebase.common.hasRight('manage_accounts', 'Admin');
            if (this.appName === 'Felamimail') {
                this.hasRequiredRight = this.hasRequiredRight && Tine.Tinebase.registry.get('manageImapEmailUser');
            }
        } else {
            const path = this.node.attributes.path;
            
            if (path.includes(`/folders/personal`)) {
                this.hasRequiredRight = Tine.Tinebase.common.hasRight('manage_accounts', 'Admin');
            }
            
            if (this.appName === 'Felamimail') {
                if (path.includes(`/folders/shared`)) {
                    this.hasRequiredRight = Tine.Tinebase.common.hasRight('manage_shared_email_quotas', 'Admin');
                }
            }
    
            if (this.appName === 'Filemanager') {
                if (path.includes(`/folders/shared`)) {
                    this.hasRequiredRight = Tine.Tinebase.common.hasRight('manage_shared_filesystem_quotas', 'Admin');
                }
            }
        }
        
        this.afterIsRendered().then(() => {
            // se current usage/quota but can not press ok button
            this.buttonApply.setDisabled(!this.hasRequiredRight);
        });

        this.items = this.getFormItems();
      
        Tine.Admin.QuotaEditDialog.superclass.initComponent.call(this);
    },
    
    resolveAdditionalData() {
        const additionalData = {};

        if (this.appName === 'Felamimail') {
            if (this.customizeFields?.isPersonalNode) {
                additionalData['emailMailQuota'] = this.getForm().findField('emailMailQuota').getValue();
                additionalData['emailSieveQuota'] = this.getForm().findField('emailSieveQuota').getValue();
                additionalData['isPersonalNode'] = this.customizeFields?.isPersonalNode;
                this.node.attributes.quota = additionalData['emailMailQuota'];
            } else {
                this.node.attributes.quota = this.getForm().findField('quota').getValue();
            }
        }
        
        if (this.appName === 'Filemanager') {
            if (this.customizeFields?.isPersonalNode) {
                additionalData['isPersonalNode'] = this.customizeFields?.isPersonalNode;
                additionalData['accountId'] = this.customizeFields?.accountId;
            }
            
            this.node.attributes.quota = this.getForm().findField('quota').getValue();
        }
        
        return additionalData;
    },
    
    onButtonApply: async function() {
        const additionalData = this.resolveAdditionalData();
        
        await Tine.Admin.saveQuota(this.appName, this.node.attributes, additionalData)
            .then((result) => {
                // expand child node to the deepest
                this.node.parentNode.reload();
                this.window.close();
            })
            .catch((e) => {
                this.node.parentNode.reload();
                this.window.close();
            });
    },
    
    getEmailQuotaTabItems() {
        this.translation.textdomain('Admin');
        // email user
        const emailResponse = {
            responseText: Ext.util.JSON.encode(this.customizeFields?.emailUser)
        };
        const emailUser = Tine.Admin.emailUserBackend.recordReader(emailResponse);
        
        return {
            layout: 'form',
            header: false,
            frame: true,
            title: this.translation.gettext('Quota'),
            hideLabels: true,
            width: '100%',
            items: [{
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    columnWidth: 1
                },
                items: [[{
                    fieldLabel:  this.translation.gettext('Email User'),
                    xtype: 'displayfield',
                    emptyText: 'unknown account',
                    value: this.node.attributes.i18n_name,
                    name: 'email_account_name'
                }],
                    [{
                    fieldLabel: this.translation.gettext('IMAP Quota'),
                    emptyText:'no quota set',
                    value: emailUser.get('emailMailQuota'),
                    name: 'emailMailQuota',
                    xtype: 'extuxbytesfield',
                    disabled: ! this.hasRequiredRight
                }], [{
                    fieldLabel: this.translation.gettext('Current Mailbox size'),
                    value: emailUser.get('emailMailSize') ?? 0,
                    xtype: 'extuxbytesfield',
                    disabled: true
                }], [{
                    fieldLabel: this.translation.gettext('Sieve Quota'),
                    emptyText: 'no quota set',
                    value: emailUser.get('emailSieveQuota'),
                    name: 'emailSieveQuota',
                    xtype: 'extuxbytesfield',
                    disabled: ! this.hasRequiredRight
                }], [{
                    fieldLabel: this.translation.gettext('Current Sieve size'),
                    value: emailUser.get('emailSieveSize') ?? 0,
                    xtype: 'extuxbytesfield',
                    disabled: true
                }]]
            }]
        };
    },
    
    getFileSystemTabItems() {
        this.translation.textdomain('Filemanager');
        const showQuotaUi = Tine.Tinebase.configManager.get('quota')?.showUI || true;
        debugger
        //if (Tine.Tinebase.registry.get('manageImapEmailUser')) {
        this.pathField = [{
            fieldLabel: this.translation.gettext('path'),
            xtype: 'displayfield',
            emptyText: 'unknown path',
            value: this.node.attributes.path,
            name: 'path',
        }];
        
        this.hasOwnQuotaCheckbox =  [{
            boxLabel: this.translation.gettext('This folder has own quota'),
            xtype: 'checkbox',
            hidden: ! showQuotaUi,
            checked: this.node.attributes.quota,
            disabled: ! this.hasRequiredRight,
            listeners: {scope: this, check: this.onOwnQuotaCheck}
        }];

        this.quotaField = [{
            fieldLabel: this.translation.gettext('Quota'),
            xtype: 'extuxbytesfield',
            emptyText: this.translation.gettext('No quota set (examples: 10 GB, 900 MB)'),
            disabled: !this.hasRequiredRight || !this.node.attributes.quota,
            value: this.node.attributes.quota ?? 0,
            name: 'quota',
            hidden: ! showQuotaUi
        }];

        this.currentUsageField = [{
            fieldLabel: this.translation.gettext('Current Usage'),
            xtype: 'extuxbytesfield',
            value: this.node.attributes.size ?? 0,
            name: 'currentUsage',
            disabled: true
        }];
        
        return {
            layout: 'form',
            title: this.translation.gettext('Quota'),
            frame: true,
            hideLabels: true,
            width: '100%',
            items: [{
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    columnWidth: 1
                },
                items: [
                    this.pathField,
                    this.hasOwnQuotaCheckbox,
                    this.quotaField,
                    this.currentUsageField
                ]
            }]
        };
        //}
    },

    onOwnQuotaCheck: function(cb, checked) {
        if (!cb.disabled) {
            this.getForm().findField('quota').setDisabled(!checked);
            if (!checked) {
                this.getForm().findField('quota').setValue(null);
            }
        }
    },

    getFormItems() {
        if (this.node.attributes) {
            if (this.customizeFields?.isPersonalNode && this.appName === 'Felamimail') {
                this.quotaUsagePanel = this.getEmailQuotaTabItems();
            } else {
                this.quotaUsagePanel = this.getFileSystemTabItems();
            }
        }

        return {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            defaults: {
                frame: true
            },
            items:
                [this.quotaUsagePanel]
        };
    }
    
});

/**
 * Container Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.QuotaEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 300,
        name: Tine.Admin.QuotaEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.QuotaEditDialog',
        contentPanelConstructorConfig: config
    });
};
Ext.ux.ItemRegistry.registerItem(Tine.Admin.QuotaEditDialog, 5);
