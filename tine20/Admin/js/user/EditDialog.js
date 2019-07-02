/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/

Ext.ns('Tine.Admin.user');

/**
 * @namespace   Tine.Admin.user
 * @class       Tine.Admin.UserEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * NOTE: this class doesn't use the user namespace as this is not yet supported by generic grid
 * 
 * <p>User Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.UserEditDialog
 */
Tine.Admin.UserEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'userEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.User,
    recordProxy: Tine.Admin.userBackend,
    evalGrants: false,
    passwordConfirmWindow: null,
    
    /**
     * @private
     */
    initComponent: function () {
        var accountBackend = Tine.Tinebase.registry.get('accountBackend');
        this.ldapBackend = (accountBackend === 'Ldap' || accountBackend === 'ActiveDirectory');

        Tine.Admin.UserEditDialog.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    onRecordLoad: function () {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        // samba user
        var response = {
            responseText: Ext.util.JSON.encode(this.record.get('sambaSAM'))
        };
        this.samRecord = Tine.Admin.samUserBackend.recordReader(response);
        // email user
        var emailResponse = {
            responseText: Ext.util.JSON.encode(this.record.get('emailUser'))
        };
        this.emailRecord = Tine.Admin.emailUserBackend.recordReader(emailResponse);
        
        // format dates
        var dateTimeDisplayFields = ['accountLastLogin', 'accountLastPasswordChange', 'logonTime', 'logoffTime', 'pwdLastSet'];
        for (var i = 0; i < dateTimeDisplayFields.length; i += 1) {
            if (dateTimeDisplayFields[i] === 'accountLastLogin' || dateTimeDisplayFields[i] === 'accountLastPasswordChange') {
                this.record.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.record.get(dateTimeDisplayFields[i])));
            } else {
                this.samRecord.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.samRecord.get(dateTimeDisplayFields[i])));
            }
        }

        this.getForm().loadRecord(this.emailRecord);
        this.getForm().loadRecord(this.samRecord);
        this.record.set('sambaSAM', this.samRecord.data);

        if (Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            if (this.emailRecord.get('emailAliases')) {
                this.aliasesGrid.setStoreFromArray(this.emailRecord.get('emailAliases'));
            }
            if (this.emailRecord.get('emailForwards')) {
                this.forwardsGrid.setStoreFromArray(this.emailRecord.get('emailForwards'));
            }
        }
        if (Tine.Tinebase.registry.get('manageImapEmailUser')) {
            if (!this.emailRecord.get('emailMailQuota')) this.getForm().findField('emailMailQuota').setValue(null);
        }

        // load stores for memberships
        if (this.record.id) {
            this.storeGroups.loadData(this.record.get('groups'));
            this.storeRoles.loadData(this.record.get('accountRoles'));
        }

        var fileSystem = this.record.get('effectiveAndLocalQuota');
        if (fileSystem && fileSystem.localUsage) {
            this.getForm().findField('personalFSSize').setValue(parseInt(fileSystem.localUsage));
        }

        var xprops = this.record.get('xprops');
        xprops = Ext.isObject(xprops) ? xprops : {};
        if (xprops.personalFSQuota) {
            this.getForm().findField('personalFSQuota').setValue(xprops.personalFSQuota);
        }

        Tine.Admin.UserEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * @private
     */
    onRecordUpdate: function () {
        Tine.Admin.UserEditDialog.superclass.onRecordUpdate.call(this);
        
        Tine.log.debug('Tine.Admin.UserEditDialog::onRecordUpdate()');
        
        var form = this.getForm();
        form.updateRecord(this.samRecord);
        if (this.samRecord.dirty) {
            // only update sam record if something changed
            this.unsetLocalizedDateTimeFields(this.samRecord, ['logonTime', 'logoffTime', 'pwdLastSet']);
            this.record.set('sambaSAM', '');
            this.record.set('sambaSAM', this.samRecord.data);
        }

        form.updateRecord(this.emailRecord);
        // get aliases / forwards
        if (Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            // forcing blur of quickadd grids
            this.aliasesGrid.doBlur();
            this.forwardsGrid.doBlur();
            this.emailRecord.set('emailAliases', this.aliasesGrid.getFromStoreAsArray());
            this.emailRecord.set('emailForwards', this.forwardsGrid.getFromStoreAsArray());
            Tine.log.debug('Tine.Admin.UserEditDialog::onRecordUpdate() -> setting aliases and forwards in email record');
            Tine.log.debug(this.emailRecord);
        }
        this.unsetLocalizedDateTimeFields(this.emailRecord, ['emailLastLogin']);
        this.record.set('emailUser', '');
        this.record.set('emailUser', this.emailRecord.data);
        
        var newGroups = [],
            newRoles = [];
        
        this.storeGroups.each(function (rec) {
            newGroups.push(rec.data.id);
        });
        // add selected primary group to new groups if not exists
        if (newGroups.indexOf(this.record.get('accountPrimaryGroup')) === -1) {
            newGroups.push(this.record.get('accountPrimaryGroup'));
        }
         
        this.storeRoles.each(function (rec) {
            newRoles.push(rec.data.id);
        });
        
        this.record.set('groups', newGroups);
        this.record.set('accountRoles', newRoles);
        
        this.unsetLocalizedDateTimeFields(this.record, ['accountLastLogin', 'accountLastPasswordChange']);

        var xprops = {};
        xprops.personalFSQuota = this.getForm().findField('personalFSQuota').getValue();
        Tine.Tinebase.common.assertComparable(xprops);
        this.record.set('xprops', xprops);
    },
    
    /**
     * need to unset localized datetime fields before saving
     * 
     * @param {Object} record
     * @param {Array} dateTimeDisplayFields
     */
    unsetLocalizedDateTimeFields: function(record, dateTimeDisplayFields) {
        Ext.each(dateTimeDisplayFields, function (dateTimeDisplayField) {
            record.set(dateTimeDisplayField, '');
        }, this);
    },

    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var result = Tine.Admin.UserEditDialog.superclass.isValid.call(this);
        if (! result) {
            return false;
        }
        
        if (Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            var emailValue = this.getForm().findField('accountEmailAddress').getValue();
            if (! Tine.Tinebase.common.checkEmailDomain(emailValue)) {
                result = false;
                this.getForm().markInvalid([{
                    id: 'accountEmailAddress',
                    msg: this.app.i18n._("Domain is not allowed. Check your SMTP domain configuration.")
                }]);
            }
        }

        if (Tine.Tinebase.appMgr.get('Admin').featureEnabled('featurePreventSpecialCharInLoginName')) {
            if (! this.validateLoginName(this.getForm().findField('accountLoginName').getValue())) {
                result = false;
                this.getForm().markInvalid([{
                    id: 'accountLoginName',
                    msg: this.app.i18n._("Special characters are not allowed in login name.")
                }]);
            }
        }
        
        return result;
    },
    
    /**
     * Validate confirmed password
     */
    onPasswordConfirm: function () {
        var confirmForm = this.passwordConfirmWindow.items.first().getForm(),
            confirmValues = confirmForm.getValues(),
            passwordStatus = confirmForm.findField('passwordStatus'),
            passwordField = (this.getForm()) ? this.getForm().findField('accountPassword') : null;
        
        if (! passwordField) {
            // oops: something went wrong, this should not happen
            return false;
        }
        
        if (confirmValues.passwordRepeat !== passwordField.getValue()) {
            passwordStatus.el.setStyle('color', 'red');
            passwordStatus.setValue(this.app.i18n.gettext('Passwords do not match!'));
            
            passwordField.passwordsMatch = false;
            passwordField.markInvalid(this.app.i18n.gettext('Passwords do not match!'));
        } else {
            passwordStatus.el.setStyle('color', 'green');
            passwordStatus.setValue(this.app.i18n.gettext('Passwords match!'));
                        
            passwordField.passwordsMatch = true;
            passwordField.clearInvalid();
        }
        
        return passwordField.passwordsMatch ? passwordField.passwordsMatch : passwordStatus.getValue();
    },
    
    /**
     * Get current primary group (selected from combobox or default primary group)
     * 
     * @return {String} - id of current primary group
     */
    getCurrentPrimaryGroupId: function () {
        return this.getForm().findField('accountPrimaryGroup').getValue() || this.record.get('accountPrimaryGroup').id;
    },
    
    /**
     * Init User groups picker grid
     * 
     * @return {Tine.widgets.account.PickerGridPanel}
     */
    initUserGroups: function () {
        this.storeGroups = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Admin.Model.Group
        });
        
        var self = this;
        
        this.pickerGridGroups = new Tine.widgets.account.PickerGridPanel({
            border: false,
            frame: false,
            store: this.storeGroups,
            selectType: 'group',
            selectAnyone: false,
            selectTypeDefault: 'group',
            groupRecordClass: Tine.Admin.Model.Group,
            getColumnModel: function () {
                return new Ext.grid.ColumnModel({
                    defaults: { sortable: true },
                    columns:  [
                        {id: 'name', header: self.app.i18n._('Name'), dataIndex: this.recordPrefix + 'name', renderer: function (val, meta, record) {
                            return record.data.id === self.getCurrentPrimaryGroupId() ? (record.data.name + '<span class="x-item-disabled"> (' + self.app.i18n.gettext('Primary group') + ')<span>') : record.data.name;
                        }}
                    ]
                });
            }
        });
        // disable remove of group if equal to current primary group
        this.pickerGridGroups.selModel.on('beforerowselect', function (sm, index, keep, record) {
            if (record.data.id === this.getCurrentPrimaryGroupId()) {
                return false;
            }
        }, this);
        
        return this.pickerGridGroups;
    },
    
    /**
     * Init User roles picker grid
     * 
     * @return {Tine.widgets.account.PickerGridPanel}
     */
    initUserRoles: function () {
        this.storeRoles = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Role
        });
        
        this.pickerGridRoles = new Tine.widgets.grid.PickerGridPanel({
            border: false,
            frame: false,
            autoExpandColumn: 'name',
            store: this.storeRoles,
            recordClass: Tine.Tinebase.Model.Role,
            columns: [{id: 'name', header: this.app.i18n.gettext('Name'), sortable: true, dataIndex: 'name'}],
            initActionsAndToolbars: function () {
                // for now removed abillity to edit role membership
//                Tine.widgets.grid.PickerGridPanel.prototype.initActionsAndToolbars.call(this);
//                
//                this.comboPanel = new Ext.Container({
//                    layout: 'hfit',
//                    border: false,
//                    items: this.getSearchCombo(),
//                    columnWidth: 1
//                });
//                
//                this.tbar = new Ext.Toolbar({
//                    items: this.comboPanel,
//                    layout: 'column'
//                });
            },
            onAddRecordFromCombo: function (recordToAdd) {
                // check if already in
                if (! this.recordStore.getById(recordToAdd.id)) {
                    this.recordStore.add([recordToAdd]);
                }
                this.collapse();
                this.clearValue();
                this.reset();
            }
        });
        // remove listeners for this grid selection model
        this.pickerGridRoles.selModel.purgeListeners();
        
        return this.pickerGridRoles;
    },
    
    /**
     * Init Fileserver tab items
     * 
     * @return {Array} - array ff fileserver tab items
     */
    initFileserver: function () {
        if (this.ldapBackend) {
            return [{
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Unix'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Home Directory'),
                        name: 'accountHomeDirectory',
                        columnWidth: 0.666
                    }, {
                        fieldLabel: this.app.i18n.gettext('Login Shell'),
                        name: 'accountLoginShell'
                    }]]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Windows'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Home Drive'),
                        name: 'homeDrive',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Logon Time'),
                        name: 'logonTime',
                        emptyText: this.app.i18n.gettext('never logged in'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Home Path'),
                        name: 'homePath',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Logoff Time'),
                        name: 'logoffTime',
                        emptyText: this.app.i18n.gettext('never logged off'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Profile Path'),
                        name: 'profilePath',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Password Last Set'),
                        name: 'pwdLastSet',
                        emptyText: this.app.i18n.gettext('never'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Logon Script'),
                        name: 'logonScript',
                        columnWidth: 0.666
                    }], [{
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Password Can Change'),
                        name: 'pwdCanChange',
                        emptyText: this.app.i18n.gettext('not set')
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Password Must Change'),
                        name: 'pwdMustChange',
                        emptyText: this.app.i18n.gettext('not set')
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Kick Off Time'),
                        name: 'kickoffTime',
                        emptyText: this.app.i18n.gettext('not set')
                    }]]
                }]
            }];
        }
        
        return [];
    },

    /**
     * Init Filesystem tab items
     *
     * @return {Array} - array of tab items
     */
    initFilesystem: function () {
        return [{
            xtype: 'fieldset',
            title: this.app.i18n.gettext('Filesystem Quota'),
            autoHeight: true,
            checkboxToggle: true,
            layout: 'hfit',
            listeners: {
                scope: this,
                collapse: function () {
                    this.getForm().findField('personalQuota').setValue(null);
                }
            },
            items: [{
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    columnWidth: 0.666
                },
                items: [[{
                    fieldLabel: this.app.i18n.gettext('Quota'),
                    emptyText: this.app.i18n.gettext('no quota set'),
                    name: 'personalFSQuota',
                    xtype: 'extuxbytesfield'
                }], [{
                    fieldLabel: this.app.i18n.gettext('Current Filesystem usage'),
                    name: 'personalFSSize',
                    xtype: 'extuxbytesfield',
                    disabled: true
                }]]
            }]
        }];
    },

    /**
     * Init IMAP tab items
     * 
     * @return {Array} - array of IMAP tab items
     */
    initImap: function () {
        if (Tine.Tinebase.registry.get('manageImapEmailUser')) {
            return [{
                xtype: 'fieldset',
                title: this.app.i18n.gettext('IMAP Quota'),
                autoHeight: true,
                checkboxToggle: true,
                layout: 'hfit',
                listeners: {
                    scope: this,
                    collapse: function() {
                        this.getForm().findField('emailMailQuota').setValue(null);
                    }
                },
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        columnWidth: 0.666
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Quota'),
                        emptyText: this.app.i18n.gettext('no quota set'),
                        name: 'emailMailQuota',
                        xtype: 'extuxbytesfield'
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Current Mailbox size'),
                        name: 'emailMailSize',
                        xtype: 'extuxbytesfield',
                        disabled: true
                    }]]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Sieve Quota'),
                autoHeight: true,
                checkboxToggle: true,
                layout: 'hfit',
                listeners: {
                    scope: this,
                    collapse: function() {
                        this.getForm().findField('emailSieveQuota').setValue(null);
                    }
                },
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        columnWidth: 0.666
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Quota'),
                        emptyText: this.app.i18n.gettext('no quota set'),
                        name: 'emailSieveQuota',
                        xtype: 'extuxbytesfield'
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Current Sieve size'),
                        name: 'emailSieveSize',
                        xtype: 'extuxbytesfield',
                        disabled: true
                    }]
                    ]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Information'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'displayfield',
                        anchor: '100%',
                        columnWidth: 0.666,
                        style: this.displayFieldStyle
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Last Login'),
                        name: 'emailLastLogin'
                    }]]
                }]
            }];
        }
        
        return [];
    },
    
    /**
     * @private
     * 
     * init email grids
     * @return Array
     * 
     * TODO     add ctx menu
     */
    initSmtp: function () {
        if (! Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            return [];
        }
        
        var commonConfig = {
            autoExpandColumn: 'email',
            quickaddMandatory: 'email',
            frame: false,
            useBBar: true,
            dataField: 'email',
            height: 200,
            columnWidth: 0.5,
            recordClass: Ext.data.Record.create([
                { name: 'email' }
            ])
        };
        
        var smtpPrimarydomain = Tine.Tinebase.registry.get('primarydomain');
        var smtpSecondarydomains = Tine.Tinebase.registry.get('secondarydomains');

        var domains = (smtpSecondarydomains && smtpSecondarydomains.length) ? smtpSecondarydomains.split(',') : [];
        if (smtpPrimarydomain.length) {
            domains.push(smtpPrimarydomain);
        }
        var app = this.app,
            record = this.record;
            
        this.aliasesGrid = new Tine.widgets.grid.QuickaddGridPanel(
            Ext.apply({
                onNewentry: function(value) {
                    var split = value.email ? value.email.split('@') : [];
                    if (split.length != 2 || split[1].split('.').length < 2) {
                        return false;
                    }
                    var domain = split[1];
                    if (domains.indexOf(domain) > -1) {
                        Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.call(this, value);
                    } else {
                        Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.i18n._('Domain not allowed'),
                            msg: String.format(app.i18n._('The domain {0} of the alias {1} you tried to add is neither configured as primary domain nor set as a secondary domain in the setup.'
                                + ' Please add this domain to the secondary domains in SMTP setup or use another domain which is configured already.'),
                                '<b>' + domain + '</b>', '<b>' + value.email + '</b>')
                        });
                        return false;
                    }
                },
                cm: new Ext.grid.ColumnModel([{
                    id: 'email', 
                    header: this.app.i18n.gettext('Email Alias'),
                    dataIndex: 'email', 
                    width: 300, 
                    hideable: false, 
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.app.i18n.gettext('Add an alias address...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false})
                }])
            }, commonConfig)
        );
        this.aliasesGrid.render(document.body);
        
        var aliasesStore = this.aliasesGrid.getStore();

        this.forwardsGrid = new Tine.widgets.grid.QuickaddGridPanel(
            Ext.apply({
                onNewentry: function(value) {
                    if (value.email === record.get('accountEmailAddress') || aliasesStore.find('email', value.email) !== -1) {
                        Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.i18n._('Forwarding to self'),
                            msg: app.i18n._('You are not allowed to set a forward email address that is identical to the users primary email or one of his aliases.')
                        });
                        return false;
                    } else {
                        Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.call(this, value);
                    }
                },
                cm: new Ext.grid.ColumnModel([{
                    id: 'email', 
                    header: this.app.i18n.gettext('Email Forward'),
                    dataIndex: 'email', 
                    width: 300, 
                    hideable: false, 
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.app.i18n.gettext('Add a forward address...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false}) 
                }])
            }, commonConfig)
        );
        this.forwardsGrid.render(document.body);
        
        return [
            [this.aliasesGrid, this.forwardsGrid],
            [{hidden: true},
             {
                fieldLabel: this.app.i18n.gettext('Forward Only'),
                name: 'emailForwardOnly',
                xtype: 'checkbox',
                readOnly: false
            }]
        ];
    },

    initPasswordConfirmWindow: function() {
        this.passwordConfirmWindow = new Ext.Window({
            title: this.app.i18n.gettext('Password confirmation'),
            closeAction: 'hide',
            modal: true,
            width: 300,
            height: 150,
            items: [{
                xtype: 'form',
                bodyStyle: 'padding: 5px;',
                buttonAlign: 'right',
                labelAlign: 'top',
                anchor: '100%',
                monitorValid: true,
                defaults: { anchor: '100%' },
                items: [{
                    xtype: 'tw-passwordTriggerField',
                    inputType: 'password',
                    autocomplete: 'new-password',
                    id: 'passwordRepeat',
                    fieldLabel: this.app.i18n.gettext('Repeat password'),
                    name: 'passwordRepeat',
                    validator: this.onPasswordConfirm.createDelegate(this),
                    listeners: {
                        scope: this,
                        specialkey: function (field, event) {
                            if (event.getKey() === event.ENTER) {
                                // call OK button handler
                                this.passwordConfirmWindow.items.first().buttons[1].handler.call(this);
                            }
                        }
                    }
                }, {
                    xtype: 'displayfield',
                    hideLabel: true,
                    id: 'passwordStatus',
                    value: this.app.i18n.gettext('Passwords do not match!')
                }],
                buttons: [{
                    text: i18n._('Cancel'),
                    iconCls: 'action_cancel',
                    scope: this,
                    handler: function () {
                        this.passwordConfirmWindow.hide();
                    }
                }, {
                    text: i18n._('Ok'),
                    formBind: true,
                    iconCls: 'action_saveAndClose',
                    scope: this,
                    handler: function () {
                        var confirmForm = this.passwordConfirmWindow.items.first().getForm();

                        // check if confirm form is valid (we need this if special key called button handler)
                        if (confirmForm.isValid()) {
                            this.passwordConfirmWindow.hide();
                            // focus email field
                            this.getForm().findField('accountEmailAddress').focus(true, 100);
                        }
                    }
                }]
            }],
            listeners: {
                scope: this,
                show: function (win) {
                    var confirmForm = this.passwordConfirmWindow.items.first().getForm();

                    confirmForm.reset();
                    confirmForm.findField('passwordRepeat').focus(true, 500);
                }
            }
        });
        this.passwordConfirmWindow.render(document.body);
    },

    /**
     * @private
     */
    getFormItems: function () {
        this.displayFieldStyle = {
            border: 'silver 1px solid',
            padding: '3px',
            height: '11px'
        };

        if (Tine.Tinebase.appMgr.get('Admin').featureEnabled('featureForceRetypePassword')) {
            this.initPasswordConfirmWindow();
        }

        var config = {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n.gettext('Account'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('First name'),
                        name: 'accountFirstName',
                        columnWidth: 0.5,
                        tabIndex: 1,
                        listeners: {
                            render: function (field) {
                                field.focus(false, 250);
                                field.selectText();
                            }
                        }
                    }, {
                        fieldLabel: this.app.i18n.gettext('Last name'),
                        name: 'accountLastName',
                        allowBlank: false,
                        tabIndex: 2,
                        columnWidth: 0.5
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Login name'),
                        name: 'accountLoginName',
                        allowBlank: false,
                        tabIndex: 3,
                        columnWidth: 0.5
                    }, {
                        fieldLabel: this.app.i18n.gettext('Password'),
                        xtype: 'tw-passwordTriggerField',
                        id: 'accountPassword',
                        name: 'accountPassword',
                        inputType: 'password',
                        autocomplete: 'new-password',
                        columnWidth: 0.5,
                        tabIndex: 4,
                        passwordsMatch: true,
                        enableKeyEvents: true,
                        listeners: {
                            scope: this,
                            blur: function (field) {
                                var fieldValue = field.getValue();
                                if (fieldValue !== '' && this.passwordConfirmWindow) {
                                    // show password confirmation
                                    // NOTE: we can't use Ext.Msg.prompt because field has to be of inputType: 'password'
                                    this.passwordConfirmWindow.show.defer(100, this.passwordConfirmWindow);
                                }
                            },
                            destroy: function () {
                                if (this.passwordConfirmWindow) {
                                    this.passwordConfirmWindow.destroy();
                                }
                            },
                            keydown: function (field) {
                                if (this.passwordConfirmWindow) {
                                    field.passwordsMatch = false;
                                }
                            }
                        },
                        validateValue: function (value) {
                            return (this.passwordsMatch);
                        }
                    }], [{
                        vtype: 'email',
                        fieldLabel: this.app.i18n.gettext('Email'),
                        tabIndex: 5,
                        name: 'accountEmailAddress',
                        id: 'accountEmailAddress',
                        columnWidth: 0.5
                    }, {
                        //vtype: 'email',
                        fieldLabel: this.app.i18n.gettext('OpenID'),
                        emptyText: '(' + this.app.i18n.gettext('Login name') + ')',
                        tabIndex: 6,
                        name: 'openid',
                        columnWidth: 0.5
                    }], [{
                        xtype: 'tinerecordpickercombobox',
                        fieldLabel: this.app.i18n.gettext('Primary group'),
                        tabIndex: 7,
                        listWidth: 250,
                        name: 'accountPrimaryGroup',
                        blurOnSelect: true,
                        allowBlank: false,
                        recordClass: Tine.Admin.Model.Group,
                        listeners: {
                            scope: this,
                            'select': function (combo, record, index) {
                                // refresh grid
                                if (this.pickerGridGroups) {
                                    this.pickerGridGroups.getView().refresh();
                                }
                            }
                        }
                    }, {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n.gettext('Status'),
                        name: 'accountStatus',
                        mode: 'local',
                        triggerAction: 'all',
                        allowBlank: false,
                        tabIndex: 8,
                        editable: false,
                        store: [
                            ['enabled',  this.app.i18n.gettext('enabled')],
                            ['disabled', this.app.i18n.gettext('disabled')],
                            ['expired',  this.app.i18n.gettext('expired')],
                            ['blocked',  this.app.i18n.gettext('blocked')]
                        ],
                        listeners: {
                            scope: this,
                            select: function (combo, record) {
                                switch (record.data.field1) {
                                    case 'blocked':
                                        Ext.Msg.alert(this.app.i18n._('Invalid Status'),
                                            this.app.i18n._('Blocked status is only valid if the user tried to login with a wrong password to often. It is not possible to set this status here.'));
                                        combo.setValue(combo.startValue);
                                        break;
                                    case 'expired':
                                        this.getForm().findField('accountExpires').setValue(new Date());
                                        break;
                                    case 'enabled':
                                        var expiryDateField = this.getForm().findField('accountExpires'),
                                            expiryDate = expiryDateField.getValue(),
                                            now = new Date();
                                            
                                        if (expiryDate < now) {
                                            expiryDateField.setValue('');
                                        }
                                        break;
                                    default:
                                        // do nothing
                                }
                            }
                        }
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Expires'),
                        name: 'accountExpires',
                        tabIndex: 9,
                        emptyText: this.app.i18n.gettext('never')
                    }], [{
                        xtype: 'combo',
                        fieldLabel: this.app.i18n.gettext('Visibility'),
                        name: 'visibility',
                        mode: 'local',
                        tabIndex: 10,
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: [['displayed', this.app.i18n.gettext('Display in addressbook')], ['hidden', this.app.i18n.gettext('Hide from addressbook')]],
                        listeners: {
                            scope: this,
                            select: function (combo, record) {
                                // disable container_id combo if hidden
                                var addressbookContainerCombo = this.getForm().findField('container_id');
                                addressbookContainerCombo.setDisabled(record.data.field1 === 'hidden');
                                if (addressbookContainerCombo.getValue() === '') {
                                    addressbookContainerCombo.setValue(null);
                                }
                            }
                        }
                    }, {
                        xtype: 'tinerecordpickercombobox',
                        fieldLabel: this.app.i18n.gettext('Saved in Addressbook'),
                        name: 'container_id',
                        blurOnSelect: true,
                        tabIndex: 11,
                        allowBlank: false,
                        forceSelection: true,
                        listWidth: 250,
                        recordClass: Tine.Tinebase.Model.Container,
                        disabled: this.record.get('visibility') === 'hidden',
                        recordProxy: Tine.Admin.sharedAddressbookBackend,
                        listeners: {
                            specialkey: function(combo, e) {
                                if (e.getKey() == e.TAB && ! e.shiftKey) {
                                    // move cursor to first input field (skip display fields)
                                    // @see 0008226: when tabbing in user edit dialog, wrong tab content is displayed
                                    e.preventDefault();
                                    e.stopEvent();
                                    this.getForm().findField('accountFirstName').focus();
                                }
                            },
                            scope: this
                        }
                    }]] 
                }, {
                    xtype: 'fieldset',
                    title: this.app.i18n.gettext('Information'),
                    autoHeight: true,
                    checkboxToggle: false,
                    layout: 'hfit',
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'displayfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 0.333,
                            style: this.displayFieldStyle
                        },
                        items: [[{
                            fieldLabel: this.app.i18n.gettext('Last login at'),
                            name: 'accountLastLogin',
                            emptyText: this.ldapBackend ? this.app.i18n.gettext("don't know") : this.app.i18n.gettext('never logged in')
                        }, {
                            fieldLabel: this.app.i18n.gettext('Last login from'),
                            name: 'accountLastLoginfrom',
                            emptyText: this.ldapBackend ? this.app.i18n.gettext("don't know") : this.app.i18n.gettext('never logged in')
                        }, {
                            fieldLabel: this.app.i18n.gettext('Password set'),
                            name: 'accountLastPasswordChange',
                            emptyText: this.app.i18n.gettext('never')
                        }]]
                    }]
                }]
            }, {
                title: this.app.i18n.gettext('User groups'),
                border: false,
                frame: true,
                layout: 'fit',
                items: this.initUserGroups()
            }, {
                title: this.app.i18n.gettext('User roles'),
                border: false,
                frame: true,
                layout: 'fit',
                items: this.initUserRoles()
            }, {
                title: this.app.i18n.gettext('Fileserver'),
                disabled: !this.ldapBackend,
                border: false,
                frame: true,
                items: this.initFileserver()
            }, {
                title: this.app.i18n.gettext('Filesystem'),
                border: false,
                frame: true,
                items: this.initFilesystem()
            }, {
                title: this.app.i18n.gettext('IMAP'),
                disabled: ! Tine.Tinebase.registry.get('manageImapEmailUser'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'hfit',
                items: this.initImap()
            }, {
                xtype: 'columnform',
                title: this.app.i18n.gettext('SMTP'),
                disabled: ! Tine.Tinebase.registry.get('manageSmtpEmailUser'),
                border: false,
                frame: true,
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 0.5
                },
                items: this.initSmtp()
            }]
        };
        return config;
    },

    /**
     * @param value
     * @return boolean
     */
    validateLoginName: function (value) {
        return value.match(/^[a-z\d._-]+$/i) !== null;
    }
});

/**
 * User Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.UserEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Admin.UserEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.UserEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
