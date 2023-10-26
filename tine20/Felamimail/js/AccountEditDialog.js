/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Felamimail');

require('./SignatureGridPanel');
require('./sieve/VacationPanel');
import waitFor from "util/waitFor.es6";
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

    /**
     * needed to prevent events in form fields (i.e. migration_approved checkbox)
     * @type boolean
     */
    preventCheckboxEvents: true,

    initComponent: function() {

        if (this.asAdminModule) {
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                appName: 'Admin',
                modelName: 'EmailAccount',
                recordClass: Tine.Felamimail.Model.Account,
                idProperty: 'id'
            });
        }

        this.hasEditAccountRight = this.asAdminModule ? true : Tine.Tinebase.common.hasRight('manage_accounts', 'Felamimail');

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
     * -> only allow to change some fields if it is a system account
     */
    onRecordLoad: function() {
        Tine.Felamimail.AccountEditDialog.superclass.onRecordLoad.call(this);

        if (! this.copyRecord && ! this.record.id && this.window) {
            this.window.setTitle(this.app.i18n._('Add New Account'));
            if (this.asAdminModule) {
                this.record.set('type', 'shared');
            }
        } else {
            this.grantsGrid.setValue(this.record.get('grants'));
        }

        this.loadEmailQuotas();
        this.disableSieveTabs();
        this.disableFormFields();
        this.loadEmailAliasesAndForwards();
    },

    // finally load the record into the form
    onAfterRecordLoad: function() {
        Tine.Felamimail.AccountEditDialog.superclass.onAfterRecordLoad.call(this);
        this.preventCheckboxEvents = false;
        this.loadDefaultAddressbook();
        this.loadSieve();
    },

    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function(callback, scope) {
        Tine.Felamimail.AccountEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        const hasRight = this.checkAccountEditRight(this.record);
        this.action_saveAndClose.setDisabled(!hasRight);
        
        this.record.set('grants', this.grantsGrid.getValue());

        this.updateContactAddressbook();
        this.updateEmailQuotas();
        this.updateEmailAliasesAndForwards();

        if (this.isSystemAccount()) {
            this.updateSieve();
        }
    },

    disableFormFields: function() {
        // if account type == system disable most of the input fields
        this.getForm().items.each(function(item) {
            let disabled = false;
            // only enable some fields
            switch (item.name) {
                case 'user_id':
                    this.disableUserCombo(item);
                    break;
                case 'migration_approved':
                    disabled = this.record.get('type') === 'shared' || this.record.get('type') === 'adblist';
                    item.setDisabled(disabled);
                    break;
                case 'signatures':
                case 'signature_position':
                case 'from':
                case 'display_format':
                case 'compose_format':
                case 'preserve_format':
                case 'organization':
                case 'reply_to':
                case 'save_sent_mail_copy':
                case 'sent_folder':
                case 'trash_folder':
                case 'drafts_folder':
                case 'templates_folder':
                    // fields can be edited in any mode
                    item.setDisabled(false);
                    break;
                case 'type':
                    item.setDisabled(! this.asAdminModule);
                    break;
                case 'password':
                    this.disablePasswordField(item);
                    break;
                case 'revealpwd':
                    item.setDisabled(false);
                    break;
                case 'user':
                    disabled = ! this.hasEditAccountRight || !(
                        !this.record.get('type')
                        || this.record.get('type') === 'userInternal'
                        || this.record.get('type') === 'user'
                    );
                    item.setDisabled(disabled);
                    break;
                case 'smtp_user':
                case 'smtp_password':
                    item.setDisabled(! this.hasEditAccountRight || this.isSystemAccount() || (this.asAdminModule && this.record.get('type') === 'user'));
                    break;
                case 'host':
                case 'port':
                case 'ssl':
                case 'smtp_hostname':
                case 'smtp_port':
                case 'smtp_ssl':
                case 'smtp_auth':
                case 'sieve_hostname':
                case 'sieve_port':
                case 'sieve_ssl':
                    // always disabled for system accounts
                    item.setDisabled(this.isSystemAccount() || ! this.hasEditAccountRight);
                    break;
                case 'sieve_notification_email':
                case 'sieve_notification_move':
                case 'sieve_notification_move_folder':
                case 'enabled':
                    // always disabled for non-system accounts
                    item.setDisabled(! this.isSystemAccount());
                    break;
                case 'emailMailSize':
                case 'emailSieveSize':
                    item.setDisabled(true);
                    break;
                case 'emailMailQuota':
                    item.setDisabled(! this.emailImapUser.hasOwnProperty('emailMailQuota') || ! this.hasEditAccountRight);
                    break;
                case 'emailSieveQuota':
                    item.setDisabled(! this.emailImapUser.hasOwnProperty('emailSieveQuota') || ! this.hasEditAccountRight);
                    break;
                case 'container_id':
                    item.setDisabled(this.record.get('visibility') === 'hidden');
                    break;
                case 'visibility':
                    item.setDisabled(this.record.get('type') === 'system');
                    break;
                case 'save_sent_mail_to_source':
                    item.setDisabled(this.record.get('message_sent_copy_behavior') === 'skip');
                    break;
                default:
                    item.setDisabled(! this.asAdminModule && (this.isSystemAccount() || ! this.hasEditAccountRight));
            }
        }, this);

        this.grantsGrid.setDisabled(! (this.record.get('type') === 'shared' && this.asAdminModule));
    },

    disableUserCombo: function(item) {
        if (! this.asAdminModule) {
            item.hide();
        } else {
            let disabled = this.record.get('type') === 'shared' || this.record.get('type') === 'adblist';
            item.setDisabled(disabled);
            if (disabled) {
                item.setValue('');
            }
        }
    },

    disablePasswordField: function(item) {
        if (this.record.get('type') && this.record.get('type') === 'user') {
            item.setDisabled(false);
        } else {
            item.setDisabled(! (
                !this.record.get('type') || this.record.get('type') === 'shared')
            );
        }
    },

    disableSieveTabs() {
        if (this.asAdminModule) {
            if (!Tine.Admin.registry.get('masterSieveAccess')) {
                this.vacationPanel.setDisabled(true);
                this.rulesGridPanel.setDisabled(true);
            }
        } else {
            if (! this.isSystemAccount()) {
                this.vacationPanel.setDisabled(true);
                this.rulesGridPanel.setDisabled(true);
            }
        }
    },
    
    checkAccountEditRight(account) {
        if (this.asAdminModule) {
            return Tine.Tinebase.common.hasRight('manage_emailaccounts', 'Admin');
        } else {
            if (account.data?.type === 'shared') {
                return account.data?.account_grants?.editGrant;
            }
            return true;
        }
    },

    isSystemAccount: function() {
        return this.record.get('type') === 'system' || this.record.get('type') === 'shared' || this.record.get('type') === 'userInternal' || this.record.get('type') === 'adblist';
    },

    isExternalUserAccount: function () {
        return this.record.get('type') === 'user';
    },

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initialization is done.
     * @private
     */
    getFormItems: function() {
        const me = this;
        this.grantsGrid = new Tine.widgets.account.PickerGridPanel({
            selectType: 'both',
            title: this.app.i18n._('Permissions'),
            store: this.getGrantsStore(),
            hasAccountPrefix: true,
            configColumns: this.getGrantsColumns(),
            selectTypeDefault: 'group',
            height : 250,
            disabled: ! this.asAdminModule,
            recordClass: Tine.Tinebase.Model.Grant,
            checkState: function() {
                const disabled = ! (me.record.get('type') === 'shared' && me.asAdminModule);
                me.grantsGrid.setDisabled(disabled);
            }
        });

        this.rulesGridPanel = new Tine.Felamimail.sieve.RulesGridPanel({
            title: this.app.i18n._('Filter Rules'),
            account: this.record ? this.record : null,
            recordProxy: this.ruleRecordProxy,
            initialLoadAfterRender: false,
            disabled: !this.isSystemAccount()
        });

        this.vacationPanel = new Tine.Felamimail.sieve.VacationPanel({
            title: this.app.i18n._('Vacation'),
            account: this.record,
            editDialog: this,
            disabled: !this.isSystemAccount()
        });
    
        this.saveInAdbFields = [];
        this.emailImapUser = [];
        this.aliasesGrid = [];
        this.forwardsGrid = [];
        
        if (this.asAdminModule) {
            this.saveInAdbFields = Tine.Admin.UserEditDialog.prototype.getSaveInAddessbookFields(this, this.record.get('type') === 'system');
            this.emailImapUser = this.record.data?.email_imap_user || [];
    
            const commonConfig = {
                autoExpandColumn: 'email',
                quickaddMandatory: 'email',
                frame: false,
                useBBar: true,
                height: 200,
                columnWidth: 0.5,
                recordClass: Ext.data.Record.create([
                    { name: 'email' }
                ]),
                scope: this,
                record: this.record,
            };
            
            this.aliasesGrid = Tine.Admin.UserEditDialog.prototype.initAliasesGrid(commonConfig);
            this.forwardsGrid = Tine.Admin.UserEditDialog.prototype.initForwardsGrid(commonConfig);
        }

        var commonFormDefaults = {
            xtype: 'textfield',
            anchor: '100%',
            labelSeparator: '',
            maxLength: 256,
            columnWidth: 1
        };
        
        return {
            xtype: 'tabpanel',
            name: 'accountEditPanel',
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
                        height: 400,
                        items: [[{
                            fieldLabel: this.app.i18n._('Account Name'),
                            name: 'name',
                            allowBlank: this.asAdminModule
                        }], [new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Felamimail',
                            keyFieldName: 'mailAccountType',
                            fieldLabel: this.app.i18n._('Type'),
                            name: 'type',
                            hidden: ! this.asAdminModule,
                            listeners: {
                                scope: this,
                                select: this.onSelectType,
                                blur: function() {
                                    this.disableFormFields();
                                }
                            }
                        })], [{
                            fieldLabel: this.app.i18n._('Migration Approved'),
                            name: 'migration_approved',
                            hidden: ! this.asAdminModule,
                            xtype: 'checkbox',
                            listeners: {
                                check: function(checkbox, checked) {
                                    if (! this.preventCheckboxEvents && checked) {
                                        checkbox.setValue(0);
                                        Ext.MessageBox.show({
                                            title: this.app.i18n._('Approve Migration'),
                                            msg: this.app.i18n._('Do you want to approve the migration of this account?'),
                                            buttons: Ext.MessageBox.YESNO,
                                            fn: (btn) => {
                                                if (btn === 'yes') {
                                                    this.preventCheckboxEvents = true;
                                                    this.record.set('migration_approved', true);
                                                    checkbox.setValue(1);
                                                    this.preventCheckboxEvents = false;
                                                }
                                            },
                                            icon: Ext.MessageBox.QUESTION
                                        });
                                    }
                                },
                                scope: this
                            },
                            checkState: function() {
                                const disabled = me.record.get('type') === 'shared' || me.record.get('type') === 'adblist';
                                this.setDisabled(disabled);
                            }
                        }], [this.userAccountPicker = Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                            userOnly: true,
                            fieldLabel: this.app.i18n._('User'),
                            useAccountRecord: true,
                            name: 'user_id',
                            allowBlank: true
                            // TODO user selection for system accounts should fill in the values!
                        })], [{
                            fieldLabel: this.app.i18n._('User Email'),
                            name: 'email',
                            allowBlank: this.asAdminModule,
                            vtype: 'email'
                        }], [{
                            fieldLabel: this.app.i18n._('User Name (From)'),
                            name: 'from'
                        }], [{
                            fieldLabel: this.app.i18n._('Organization'),
                            name: 'organization'
                        }], 
                            this.saveInAdbFields, 
                            [{
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
                        }]
                        ]
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
                    allowBlank: this.asAdminModule,
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Port (Default: 143 / SSL: 993)'),
                    name: 'port',
                    allowBlank: this.asAdminModule,
                    maxLength: 5,
                    value: 143,
                    xtype: 'numberfield',
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
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
                    ],
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Username'),
                    name: 'user',
                    allowBlank: this.asAdminModule,
                    checkState: function() {
                        const disabled = !(
                            !me.record.get('type')
                            || me.record.get('type') === 'userInternal'
                            || me.record.get('type') === 'user'
                        );
                        this.setDisabled(disabled);
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Password'),
                    name: 'password',
                    record: this.record,
                    emptyText: 'password',
                    xtype: 'tw-passwordTriggerField',
                    clipboard: false,
                    revealPasswordFn: async () => {
                        return new Promise((fulfill, reject) => {
                            if (this.record.id === 0) {
                                return reject('none existing account');
                            }
                            
                            Ext.Msg.confirm(this.app.i18n._('Reveal Password?'),
                                this.app.i18n._('You are about to reveal the password. This action will be logged. Proceed?'),
                                async (button) => {
                                    if (button === 'yes') {
                                        const result = await Tine.Admin.revealEmailAccountPassword(this.record.id ?? '');
                                        fulfill(result['password']);
                                    } else {
                                        reject('canceled');
                                    }
                                });
                        });
                    },
                    listeners: {
                        scope: this,
                        keyup: (field)=> {
                            if ('' !== field.getValue()) {
                                this.imapButton.setDisabled(false);
                                this.smtpButton.setDisabled(false);
                            }
                        }
                    },
                }], [
                this.imapButton = new Ext.Button({
                    text: this.app.i18n._('Test IMAP Connection'),
                    handler: () => {
                        this.testConnection('IMAP', true, true);
                    },
                    disabled: true
                })],
                    [{
                        fieldLabel: 'Imap Quota',
                        emptyText:'no quota set',
                        name: 'emailMailQuota',
                        xtype: 'extuxbytesfield',
                        disabled: true,
                        hidden: !this.asAdminModule || ! this.emailImapUser.hasOwnProperty('emailMailQuota')
                    }],
                    [{
                        fieldLabel: 'Current Mailbox size',
                        name: 'emailMailSize',
                        xtype: 'extuxbytesfield',
                        disabled: true,
                        hidden: !this.asAdminModule || ! this.emailImapUser.hasOwnProperty('emailMailSize')
                    }],
                    [{
                        fieldLabel: 'Sieve Quota',
                        emptyText: 'no quota set',
                        name: 'emailSieveQuota',
                        xtype: 'extuxbytesfield',
                        disabled: true,
                        hidden: !this.asAdminModule || ! this.emailImapUser.hasOwnProperty('emailSieveQuota')
                    }],
                    [{
                        fieldLabel: 'Current Sieve size',
                        name: 'emailSieveSize',
                        xtype: 'extuxbytesfield',
                        disabled: true,
                        hidden: !this.asAdminModule || ! this.emailImapUser.hasOwnProperty('emailSieveSize')
                    }]
                ]
            }, {
                title: this.app.i18n._('SMTP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[ {
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'smtp_hostname',
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Port (Default: 25)'),
                    name: 'smtp_port',
                    maxLength: 5,
                    xtype:'numberfield',
                    value: 25,
                    allowBlank: this.asAdminModule,
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
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
                    ],
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
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
                    ],
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Username (optional)'),
                    name: 'smtp_user',
                    checkState: function() {
                        const disabled = me.isSystemAccount() || (me.asAdminModule && me.record.get('type') === 'user');
                        this.setDisabled(disabled);
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Password (optional)'),
                    name: 'smtp_password',
                    emptyText: 'password',
                    xtype: 'tw-passwordTriggerField',
                    clipboard: false,
                    listeners: {
                        scope: this,
                        keyup: (field)=> {
                            if ('' !== field.getValue()) {
                                this.imapButton.setDisabled(false);
                                this.smtpButton.setDisabled(false);
                            }
                        }
                    },
                    checkState: function() {
                        const disabled = me.isSystemAccount() || (me.asAdminModule && me.record.get('type') === 'user');
                        this.setDisabled(disabled);
                    }
                }], [
                    this.smtpButton = new Ext.Button({
                        text: this.app.i18n._('Test SMTP Connection'),
                        height: 40,
                        handler: () => {
                            this.testConnection('SMTP',true, true);
                        },
                        disabled: true
                })], [this.aliasesGrid, this.forwardsGrid]
                ]
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
                    maxLength: 64,
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Port (Default: 2000)'),
                    name: 'sieve_port',
                    maxLength: 5,
                    value: 2000,
                    xtype:'numberfield',
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
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
                    ],
                    checkState: function() {
                        this.setDisabled(me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Notification Email'),
                    name: 'sieve_notification_email',
                    vtype: 'email',
                    checkState: function() {
                        this.setDisabled(! me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Auto-move notifications'),
                    name: 'sieve_notification_move',
                    xtype: 'widget-keyfieldcombo',
                    keyFieldName: 'sieveNotificationMoveStatus',
                    app:   'Felamimail',
                    hidden: ! Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('accountMoveNotifications'),
                    checkState: function() {
                        this.setDisabled(! me.isSystemAccount());
                    }
                }], [{
                    fieldLabel: this.app.i18n._('Auto-move notifications folder'),
                    name: 'sieve_notification_move_folder',
                    maxLength: 255,
                    hidden: ! Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('accountMoveNotifications'),
                    checkState: function() {
                        this.setDisabled(! me.isSystemAccount());
                    }
                }], [new Ext.Button({
                    text: this.app.i18n._('Explore Sieve script'),
                    handler: async () => {
                        await this.showSieveScriptWindow();
                    },
                    hidden: ! this.asAdminModule
                })]
                ]
            }, {
                title: this.app.i18n._('Other Settings'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                items: [[{
                    hideLabel: true,
                    xtype: 'checkbox',
                    boxLabel: this.app.i18n._('Save copy of sent mail on mail server'),
                    name: 'save_sent_mail_copy',
                    checked: me.record.get('message_sent_copy_behavior') !== 'skip',
                    listeners: {
                        'check': (checkbox, value) => {
                            const mode = value ? 'sent' : 'skip';
                            me.record.set('message_sent_copy_behavior', mode);
                            this.getForm().findField('sent_folder').setDisabled(!value);
                            this.getForm().findField('save_sent_mail_to_source').setDisabled(!value);
                        },
                    },
                }], [{
                    hideLabel: true,
                    xtype: 'checkbox',
                    name: 'save_sent_mail_to_source',
                    boxLabel: this.app.i18n._('Folder of the source mail if it is no system folder.'),
                    checked: me.record.get('message_sent_copy_behavior') === 'source',
                    listeners: {
                        'check': (checkbox, value) => {
                            const mode = value ? 'source' : 'sent';
                            me.record.set('message_sent_copy_behavior', mode);
                        },
                    },
                }], [{
                    fieldLabel: this.app.i18n._('Sent Folder Name'),
                    name: 'sent_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Sent'
                }], [{
                    fieldLabel: this.app.i18n._('Trash Folder Name'),
                    name: 'trash_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Trash'
                }], [{
                    fieldLabel: this.app.i18n._('Drafts Folder Name'),
                    name: 'drafts_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Drafts'
                }], [{
                    fieldLabel: this.app.i18n._('Templates Folder Name'),
                    name: 'templates_folder',
                    xtype: 'felamimailfolderselect',
                    account: this.record,
                    maxLength: 64,
                    value: 'Templates'
                }], [{
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
                        //TODO: content_type should not be experimental anymore
                        ['content_type',  this.app.i18n._('Depending on content type (experimental)')]
                    ]
                }], [{
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
                }], [{
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
                }], [{
                    fieldLabel: this.app.i18n._('Reply-To Email'),
                    name: 'reply_to',
                    vtype: 'email'
                }]]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }),
                this.rulesGridPanel,
                this.vacationPanel,
                this.grantsGrid
            ]
        };
    },

    onSelectType: function(combo, record) {
        let newValue = combo.getValue();
        let currentValue = this.record.get('type');
        if (this.record.id) {
            if (! Tine.Tinebase.configManager.get('emailUserIdInXprops')) {
                this.onTypeChangeError(combo,
                    this.app.i18n._('It is not possible to convert accounts because the config option EMAIL_USER_ID_IN_XPROPS is disabled.'));
            }

            if (newValue === 'shared' && [
                'system',
                'userInternal'
            ].indexOf(currentValue) !== false
            ) {
                // check migration_approved
                if (! this.record.get('migration_approved')) {
                    this.onTypeChangeError(combo, this.app.i18n._('Migration has not been approved.'));
                    return false;
                }
                this.showPasswordDialog();

            } else if (newValue === 'userInternal' && [
                'shared'
            ].indexOf(currentValue) !== false
            ) {
                // this is valid

            } else {
                this.onTypeChangeError(combo, this.app.i18n._('It is not possible to convert the account to this type.'));
                return false;
            }
        } else if (newValue === 'system') {
            this.onTypeChangeError(combo, this.app.i18n._('System accounts cannot be created manually.'));
            return false;
        } else if (newValue === 'adblist') {
            this.onTypeChangeError(combo, this.app.i18n._('Mailinglist accounts cannot be created manually.'));
            return false;
        }

        // apply to record for disableFormFields()
        this.record.set('type', newValue);
        this.disableFormFields();
    },

    onTypeChangeError: function(combo, errorText) {
        combo.setValue(this.record.get('type'));
        Ext.MessageBox.alert(this.app.i18n._('Account type change not possible'), errorText);
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
            this.showPasswordDialog(true);
        } else {
            Tine.Felamimail.handleRequestException(exception);
        }
    },

    showPasswordDialog: function(apply) {
        var me = this,
            dialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
                windowTitle: this.app.i18n._('E-Mail account needs a password')
            });
        dialog.openWindow();

        // password entered
        dialog.on('apply', function (password) {
            me.getForm().findField('password').setValue(password);
            if (apply) {
                me.onApplyChanges(true);
            }
        });
    },

    /**
     * Show window for script reading
     */
    showSieveScriptWindow: async function () {
        const script = await Tine.Admin.getSieveScript(this.record.data.id);
        const windowTitle = this.app.i18n._('Explore Sieve script');

        const dialog = new Tine.Tinebase.dialog.Dialog({
            items: [{
                cls: 'x-ux-display-background-border',
                xtype: 'ux.displaytextarea',
                type: 'code/folding/mixed',
                value: script,
                listeners: {
                    render: async (cmp) => {
                        // wait ace editor
                        await waitFor(() => {
                           return cmp.el.child('.ace_content');
                        });

                        cmp.el.setStyle({'overflow': null});
                    }
                },
            }],

            initComponent: function() {
                this.fbar = [
                    '->',
                    {
                        text: i18n._('Ok'),
                        minWidth: 70,
                        ref: '../buttonApply',
                        scope: this,
                        handler: this.onButtonApply,
                        iconCls: 'action_saveAndClose'
                    }
                ];
                Tine.Tinebase.dialog.Dialog.superclass.initComponent.call(this);
            },

            /**
             * Creates a new pop up dialog/window (acc. configuration)
             *
             * @returns {null}
             * TODO can we put this in the Tine.Tinebase.dialog.Dialog?
             */
            openWindow: function (config) {
                if (this.window) {
                    return this.window;
                }

                config = config || {};
                this.window = Tine.WindowFactory.getWindow(Ext.apply({
                    resizable:false,
                    title: windowTitle,
                    closeAction: 'close',
                    modal: true,
                    width: 550 ,
                    height: 400,
                    items: [this],
                    fbar: ['->']
                }, config));

                return this.window;
            },
        });

        dialog.openWindow();
    },

    getGrantsColumns: function() {
        return [
            new Ext.ux.grid.CheckColumn({
                header: this.app.i18n._('Use'),
                dataIndex: 'readGrant',
                tooltip: this.app.i18n._('The grant use the shared email account'),
                width: 60
            }),
            new Ext.ux.grid.CheckColumn({
                header: this.app.i18n._('Edit'),
                tooltip: this.app.i18n._('The grant edit the shared email account'),
                dataIndex: 'editGrant',
                width: 60
            }),
            new Ext.ux.grid.CheckColumn({
                header: this.app.i18n._('Send Mails'),
                tooltip: this.app.i18n._('The grant to send mails via the email account'),
                dataIndex: 'addGrant',
                width: 60
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
    },

    /**
     * generic apply changes handler
     */
    onApplyChanges: async function(closeWindow) {
        let result = true;
        let errorMessage = '';
        const password = this.getForm().findField('password').getValue();

        //only test connection for external user account
        if (this.isExternalUserAccount() && '' !== password) {
            await (_.reduce(['IMAP', 'SMTP'], (pre, server) => {
                return pre.then(async () => {
                    await this.testConnection(server, false)
                        .catch((e) => {
                            result = false;
                            errorMessage = errorMessage + '</b><br>' + e.message;
                        });
                })
            }, Promise.resolve()));
        }

        if(!result) {
            Ext.MessageBox.show({
                title: this.app.i18n._('Warning'),
                msg: this.app.i18n._(
                    'Server connection failed. Do you still want to save this setting and exit?')
                    + '<br>'
                    + errorMessage,
                buttons: Ext.MessageBox.YESNO,
                fn: (btn) => {
                    if (btn === 'yes') {
                        Tine.Felamimail.AccountEditDialog.superclass.onApplyChanges.call(this, closeWindow);
                    }
                },
                icon: Ext.MessageBox.WARNING
            });
        } else {
            if (this.isSystemAccount()) {
                await this.updateSieve();
            }
            Tine.Felamimail.AccountEditDialog.superclass.onApplyChanges.call(this, closeWindow);
        }
    },

    /**
     * test IMAP or SMTP connection
     *
     */
    testConnection: async function (server, showDialog = true, forceConnect = false) {

        let message = this.app.i18n._('Connection succeed');
        let fields = {
            'SMTP': {
                'smtp_hostname': '',
                'smtp_port': '',
                'smtp_ssl': '',
                'smtp_auth': '',
                'smtp_user': '',
                'smtp_password': '',
                'user': '', //use Imap username if not set
                'password': '' //use Imap password if not set
            },
            'IMAP': {
                'host': '',
                'port': '',
                'ssl': '',
                'user': '',
                'password': ''
            }
        };

        _.each(fields[server], (val, key) => {
            val = this.getForm().findField(key).getValue();
            fields[server][key] = val ? val : '';
        }, this);

        try {
            //show load mask
            if (!this['connectLoadMask' + server]) {
                this['connectLoadMask' + server] = new Ext.LoadMask(this.getEl(), {
                    msg: String.format(this.app.i18n._('Connecting to {0} server...'), server)
                });
            }

            this['connectLoadMask' + server].show();

            //test connection
            await Tine.Felamimail[server === 'SMTP' ? 'testSmtpSettings' : 'testIMapSettings'](this.record.id, fields[server], forceConnect);
        } catch (e) {
            message = this.app.i18n._hidden(_.get(e,'message',this.app.i18n._('Connection failed')));
            return Promise.reject(new Error(message));
        } finally {
            this['connectLoadMask' + server].hide();

            if (showDialog) {
                Ext.MessageBox.alert(
                    this.app.formatMessage('{server} Connection Status',{server : server}),
                    message
                ).setIcon(result ? Ext.MessageBox.INFO : Ext.MessageBox.WARNING);
            }
        }

        return message;
    },

    /**
     * is form valid?
     *
     * @return {Boolean}
     */
    isValid: function() {
        var result = Tine.Felamimail.AccountEditDialog.superclass.isValid.call(this);
        var from = this.getForm().findField('from').getValue();
        if (String(from).includes(',')) {
            this.getForm().markInvalid([{
                id: 'from',
                msg: this.app.i18n._("User Name (From) cannot contain a comma.")
            }]);
            return false;
        }
        return result;
    },

    /**
     * load vacation record
     *
     */
    loadSieve: function() {
        if (! this.isSystemAccount()) {
            return ;
        }

        let me = this;

        me.vacationRecord = Tine.Tinebase.data.Record.setFromJson(this.record.data?.sieve_vacation, Tine.Felamimail.Model.Vacation);

        // mime type is always multipart/alternative
        me.vacationRecord.set('mime', 'multipart/alternative');
        if (me.record && me.record.get('signature')) {
            me.vacationRecord.set('signature', me.record.get('signature'));
        }

        me.getForm().loadRecord(me.vacationRecord);

        me.ruleRecords = this.record.data?.sieve_rules === '' ? [] : this.record.data?.sieve_rules;
        Ext.each(me.ruleRecords, function(item) {
            const record = Tine.Tinebase.data.Record.setFromJson(item, Tine.Felamimail.Model.Rule);
            me.rulesGridPanel.store.addSorted(record);
        });

        me.getForm().loadRecord(me.ruleRecords);
    },

    /**
     * load email quotas
     *
     */
    loadEmailQuotas: function () {
        if (this.asAdminModule ) {
            const emailImapUser = this.record.data?.email_imap_user || [];

            if (emailImapUser.hasOwnProperty('emailMailQuota')) {
                this.getForm().findField('emailMailQuota').setValue(emailImapUser.emailMailQuota);
                this.getForm().findField('emailMailSize').setValue(emailImapUser.emailMailSize);
            }
            if (emailImapUser.hasOwnProperty('emailSieveQuota')) {
                this.getForm().findField('emailSieveQuota').setValue(emailImapUser.emailSieveQuota);
                this.getForm().findField('emailSieveSize').setValue(emailImapUser.emailSieveSize);
            }
        }
    },
    
    loadEmailAliasesAndForwards: function () {
        if (Tine.Tinebase.registry.get('manageSmtpEmailUser') && this.record.data?.email_smtp_user) {
            if (this.record.data?.email_smtp_user?.emailAliases) {
                this.aliasesGrid.setStoreFromArray(this.record.data.email_smtp_user.emailAliases);
            }
            if (this.record.data?.email_smtp_user?.emailForwards) {
                this.forwardsGrid.setStoreFromArray(this.record.data.email_smtp_user.emailForwards);
            }
        }
    },
    
    updateEmailAliasesAndForwards: function () {
        // get aliases / forwards
        if (this.asAdminModule && Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            // forcing blur of quickadd grids
            this.aliasesGrid.doBlur();
            this.forwardsGrid.doBlur();
            if (this.record.data?.email_smtp_user?.emailAliases) {
                this.record.data.email_smtp_user.emailAliases = this.aliasesGrid.getFromStoreAsArray();
            }
    
            if (this.record.data?.email_smtp_user?.emailForwards) {
                this.record.data.email_smtp_user.emailForwards = this.forwardsGrid.getFromStoreAsArray();
            }
            Tine.log.debug('Tine.Felamimail.AccountEditDialog::onRecordUpdate() -> setting aliases and forwards in e-mail record');
            Tine.log.debug(this.record);
        }
    },
  

    /**
     * update vacation record
     *
     */
    updateSieve: async function() {
        if (this.record.id && this.vacationRecord && this.vacationPanel) {
            let form = this.getForm();
            const contactIds = [];

            form.updateRecord(this.vacationRecord);

            Ext.each(['contact_id1', 'contact_id2'], function (field) {
                if (form.findField(field) && form.findField(field).getValue() !== '') {
                    contactIds.push(form.findField(field).getValue());
                }
            }, this);

            let template = form.findField('template_id') ? form.findField('template_id').getValue() : '';

            this.vacationRecord.set('contact_ids', contactIds);
            this.vacationRecord.set('template_id', template);

            if (template !== '') {
                try {
                    const response = await Tine.Felamimail.getVacationMessage(this.vacationRecord.data);
                    this.vacationPanel.reasonEditor.setValue(response?.message);
                } catch (e) {
                    Tine.Felamimail.handleRequestException(e);
                }
            }
        }

        let rules = [];

        this.rulesGridPanel.store.each(function (record) {
            rules.push(record.data);
        });

        this.record.set('sieve_rules', rules);
        this.record.set('sieve_vacation', this.vacationRecord);
    },

    /**
     * update email quotas
     *
     */
    updateEmailQuotas: function () {
        if (this.asAdminModule) {
            if (! this.emailImapUser || ! this.record.data.email_imap_user) {
                return;
            }
            if (this.emailImapUser.hasOwnProperty('emailMailQuota')) {
                this.record.data.email_imap_user.emailMailQuota = this.getForm().findField('emailMailQuota').getValue();
            }

            if (this.emailImapUser.hasOwnProperty('emailSieveQuota')) {
                this.record.data.email_imap_user.emailSieveQuota = this.getForm().findField('emailSieveQuota').getValue();
            }
        }
    },

    /**
     * update email_account type contact addressbook
     *
     */
    updateContactAddressbook: function () {
        if (this.record.get('type') === 'system') {
            return;
        }
        
        const container = this.getForm().findField('container_id');
        
        if (this.record.data?.visibility === 'displayed' && container) {
            if (! this.record.data?.contact_id?.container_id) {
                this.record.data.contact_id = {
                    'container_id' : container.getValue()
                }
            } else {
                this.record.data.contact_id.container_id = container.getValue();
            }
        }
    },

    /**
     * load deafault addressbook from contact
     *
     */
    loadDefaultAddressbook: function () {
        const item = this.getForm().findField('container_id');

        if (this.record.get('type') === 'system' || ! item) {
            return;
        }

        const id = this.record.data?.contact_id?.container_id ?? Tine.Admin.registry.get('defaultInternalAddressbook');
        item.setValue(id);
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
        height: 600,
        name: Tine.Felamimail.AccountEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
