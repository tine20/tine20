/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

require('./SignatureGridPanel');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.AccountEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Account Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.AccountEditDialog
 * 
 */
Tine.Felamimail.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'AccountEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Account,
    recordProxy: Tine.Felamimail.accountBackend,
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    asAdminModule: false,

    initComponent: function() {

        if (this.asAdminModule) {
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                appName: 'Admin',
                modelName: 'EmailAccount',
                recordClass: Tine.Felamimail.Model.Account,
                idProperty: 'id'
            });
        }

        Tine.Felamimail.AccountEditDialog.superclass.initComponent.call(this);
    },

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * -> only allow to change some of the fields if it is a system account
     */
    onRecordLoad: function() {
        Tine.Felamimail.AccountEditDialog.superclass.onRecordLoad.call(this);

        if (! this.copyRecord && ! this.record.id && this.window) {
            this.window.setTitle(this.app.i18n._('Add New Account'));
            if (this.asAdminModule) {
                this.record.set('type', 'shared');
                this.typePicker.setValue('shared');
            }
        } else {
            this.grantsGrid.setValue(this.record.get('grants'));
        }

        this.disableFormFields();
    },

    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function(callback, scope) {
        Tine.Felamimail.AccountEditDialog.superclass.onRecordUpdate.apply(this, arguments);

        this.record.set('grants', this.grantsGrid.getValue());
    },

    disableFormFields: function() {
        // if account type == system disable most of the input fields
        this.getForm().items.each(function(item) {
            var disabled = false;
            // only enable some fields
            switch (item.name) {
                case 'user_id':
                    if (! this.asAdminModule) {
                        item.hide();
                    } else {
                        disabled = this.record.get('type') === 'shared';
                        item.setDisabled(disabled);
                        if (disabled) {
                            item.setValue('');
                        }
                    }
                    break;
                case 'signatures':
                case 'signature_position':
                case 'display_format':
                case 'compose_format':
                case 'preserve_format':
                case 'sieve_notification_email':
                case 'reply_to':
                    break;
                case 'type':
                    item.setDisabled(this.record.id);
                    break;
                case 'password':
                    item.setDisabled(! (
                        !this.record.get('type') || this.record.get('type') === 'shared' || this.record.get('type') === 'user')
                    );
                    break;
                case 'user':
                    disabled = !(!this.record.get('type') || this.record.get('type') === 'userInternal' || this.record.get('type') === 'user');
                    item.setDisabled(disabled);
                    break;
                case 'host':
                case 'port':
                case 'ssl':
                case 'smtp_hostname':
                case 'smtp_port':
                case 'smtp_ssl':
                case 'smtp_auth':
                case 'smtp_user':
                case 'smtp_password':
                case 'sieve_hostname':
                case 'sieve_port':
                case 'sieve_ssl':
                    // always disabled for system accounts
                    item.setDisabled(this.isSystemAccount());
                    break;
                default:
                    item.setDisabled(! this.asAdminModule && this.isSystemAccount());
            }
        }, this);

        this.grantsGrid.setDisabled(! (this.record.get('type') === 'shared' && this.asAdminModule));
    },

    isSystemAccount: function() {
        return this.record.get('type') === 'system' || this.record.get('type') === 'shared' || this.record.get('type') === 'userInternal';
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @private
     */
    getFormItems: function() {
        this.grantsGrid = new Tine.widgets.account.PickerGridPanel({
            selectType: 'both',
            title:  i18n._('Permissions'),
            store: this.getGrantsStore(),
            hasAccountPrefix: true,
            configColumns: this.getGrantsColumns(),
            selectTypeDefault: 'group',
            height : 250,
            disabled: ! this.asAdminModule,
            recordClass: Tine.Tinebase.Model.Grant
        });
        
        var commonFormDefaults = {
            xtype: 'textfield',
            anchor: '100%',
            labelSeparator: '',
            maxLength: 256,
            columnWidth: 1
        };
        
        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Account'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [
                    {
                        region: 'north',
                        xtype: 'columnform',
                        formDefaults: commonFormDefaults,
                        height: 200,
                        items: [[{
                            fieldLabel: this.app.i18n._('Account Name'),
                            name: 'name',
                            allowBlank: this.asAdminModule
                        }, this.typePicker = new Ext.form.ComboBox({
                            fieldLabel: this.app.i18n._('Account Type'),
                            name: 'type',
                            hidden: ! this.asAdminModule,
                            typeAhead: false,
                            triggerAction: 'all',
                            lazyRender: true,
                            editable: false,
                            mode: 'local',
                            forceSelection: true,
                            xtype: 'combo',
                            store: new Ext.data.JsonStore({
                                data: Tine.Felamimail.Model.getAvailableAccountTypes(),
                                fields: Tine.Tinebase.Model.KeyFieldRecord
                            }),
                            valueField: 'id',
                            displayField: 'value',
                            listeners: {
                                scope: this,
                                select: function(combo, record) {
                                    // apply to record for disableFormFields()
                                    this.record.set('type', combo.getValue());
                                    this.disableFormFields();
                                },
                                blur: function() {
                                    this.disableFormFields();
                                }
                            }
                        }), this.userAccountPicker = Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                            userOnly: true,
                            fieldLabel: this.app.i18n._('User'),
                            useAccountRecord: true,
                            name: 'user_id',
                            allowBlank: true
                            // TODO user selection for system accounts should fill in the values!
                        }), {
                            fieldLabel: this.app.i18n._('User Email'),
                            name: 'email',
                            allowBlank: this.asAdminModule,
                            vtype: 'email'
                        }, {
                            fieldLabel: this.app.i18n._('User Name (From)'),
                            name: 'from'
                        }, {
                            fieldLabel: this.app.i18n._('Organization'),
                            name: 'organization'
                        }, {
                            fieldLabel: this.app.i18n._('Signature position'),
                            name: 'signature_position',
                            typeAhead: false,
                            triggerAction: 'all',
                            lazyRender: true,
                            editable: false,
                            mode: 'local',
                            forceSelection: true,
                            value: 'below',
                            xtype: 'combo',
                            store: [
                                ['above', this.app.i18n._('Above the quote')],
                                ['below', this.app.i18n._('Below the quote')]
                            ]
                        }]]
                    }, new Tine.Felamimail.SignatureGridPanel({
                        region: 'center',
                        editDialog: this
                    })
                ]
            }, {
                title: this.app.i18n._('IMAP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[{
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'host',
                    allowBlank: this.asAdminModule
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 143 / SSL: 993)'),
                    name: 'port',
                    allowBlank: this.asAdminModule,
                    maxLength: 5,
                    value: 143,
                    xtype: 'numberfield'
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'tls',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')],
                        ['ssl',  this.app.i18n._('SSL')]
                    ]
                },{
                    fieldLabel: this.app.i18n._('Username'),
                    name: 'user',
                    allowBlank: this.asAdminModule
                }, {
                    fieldLabel: this.app.i18n._('Password'),
                    name: 'password',
                    emptyText: 'password',
                    xtype: 'tw-passwordTriggerField',
                    inputType: 'password'
                }]]
            }, {
                title: this.app.i18n._('SMTP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[ {
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'smtp_hostname'
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 25)'),
                    name: 'smtp_port',
                    maxLength: 5,
                    xtype:'numberfield',
                    value: 25,
                    allowBlank: this.asAdminModule
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'smtp_ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    value: 'tls',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')],
                        ['ssl',  this.app.i18n._('SSL')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Authentication'),
                    name: 'smtp_auth',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    xtype: 'combo',
                    value: 'login',
                    store: [
                        ['none',    this.app.i18n._('None')],
                        ['login',   this.app.i18n._('Login')],
                        ['plain',   this.app.i18n._('Plain')]
                    ]
                },{
                    fieldLabel: this.app.i18n._('Username (optional)'),
                    name: 'smtp_user'
                }, {
                    fieldLabel: this.app.i18n._('Password (optional)'),
                    name: 'smtp_password',
                    emptyText: 'password',
                    xtype: 'tw-passwordTriggerField',
                    inputType: 'password'
                }]]
            }, {
                title: this.app.i18n._('Sieve'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[{
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'sieve_hostname',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 2000)'),
                    name: 'sieve_port',
                    maxLength: 5,
                    value: 2000,
                    xtype:'numberfield'
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'sieve_ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    value: 'tls',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Notification Email'),
                    name: 'sieve_notification_email',
                    vtype: 'email'
                }]]
            }, {
                title: this.app.i18n._('Other Settings'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[{
                    fieldLabel: this.app.i18n._('Sent Folder Name'),
                    name: 'sent_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Sent'
                }, {
                    fieldLabel: this.app.i18n._('Trash Folder Name'),
                    name: 'trash_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Trash'
                }, {
                    fieldLabel: this.app.i18n._('Drafts Folder Name'),
                    name: 'drafts_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Drafts'
                }, {
                    fieldLabel: this.app.i18n._('Templates Folder Name'),
                    name: 'templates_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Templates'
                }, {
                    fieldLabel: this.app.i18n._('Display Format'),
                    name: 'display_format',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'html',
                    xtype: 'combo',
                    store: [
                        ['html', this.app.i18n._('HTML')],
                        ['plain',  this.app.i18n._('Plain Text')],
                        ['content_type',  this.app.i18n._('Depending on content type (experimental)')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Compose Format'),
                    name: 'compose_format',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'html',
                    xtype: 'combo',
                    store: [
                        ['html', this.app.i18n._('HTML')],
                        ['plain',  this.app.i18n._('Plain Text')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Preserve Format'),
                    name: 'preserve_format',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: '0',
                    xtype: 'combo',
                    store: [
                        [0, this.app.i18n._('No')],
                        [1,  this.app.i18n._('Yes')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Reply-To Email'),
                    name: 'reply_to',
                    vtype: 'email'
                }]]
            },
            this.grantsGrid
            ]
        };
    },
    
    /**
     * generic request exception handler
     * 
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        this.saving = false;
        this.hideLoadMask();

        // if code == 925 (Felamimail_Exception_PasswordMissing)
        // -> open new Tine.Tinebase.widgets.dialog.PasswordDialog to set imap password field
        if (exception.code === 925) {
            var me = this,
                dialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
                windowTitle: this.app.i18n._('E-Mail account needs a password')
            });
            dialog.openWindow();

            // password entered
            dialog.on('apply', function (password) {
                me.getForm().findField('password').setValue(password);
                me.onApplyChanges(true);
            });
        } else {
            Tine.Felamimail.handleRequestException(exception);
        }
    },

    getGrantsColumns: function() {
        return [
            new Ext.ux.grid.CheckColumn({
                header: i18n._('Use'),
                dataIndex: 'readGrant',
                tooltip: i18n._('The grant use the shared email account'),
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: i18n._('Edit'),
                tooltip: i18n._('The grant edit the shared email account'),
                dataIndex: 'editGrant',
                width: 55
            }),
        ];
    },

    /**
     * get grants store
     *
     * @return Ext.data.JsonStore
     */
    getGrantsStore: function() {
        if (! this.grantsStore) {
            this.grantsStore = new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                // use account_id here because that simplifies the adding of new records with the search comboboxes
                id: 'account_id',
                fields: Tine.Tinebase.Model.Grant
            });
        }
        return this.grantsStore;
    }
});

/**
 * Felamimail Account Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
 Tine.Felamimail.AccountEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 580,
        height: 550,
        name: Tine.Felamimail.AccountEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
