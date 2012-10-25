/*
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sipgate');

/**
 * Account edit dialog
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.AccountEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 */
Tine.Sipgate.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'AccountEditWindow_',
    appName: 'Sipgate',
    recordClass: Tine.Sipgate.Model.Account,
    recordProxy: Tine.Sipgate.accountBackend,
    
    evalGrants: false,
    
    lineGridPanel: null,
    
    toggleFields: ['type', 'accounttype', 'username', 'password', 'password_repeat'],
    credentialFields: ['username','password','password_repeat'],
    
    onRender: function(ct, position) {
        Tine.Sipgate.AccountEditDialog.superclass.onRender.call(this, ct, position);
        this.validateMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Validating account...')});
        this.syncMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Synchronizing lines...')});
    },
    
    initButtons: function() {
        this.fbar = [
            '->',
            this.action_validateAccount,
            this.action_saveAccount,
            this.action_syncAccount,
            this.action_cancel,
            this.action_saveAndClose
       ];
    },
    
    initActions: function() {
        this.action_saveAccount = new Ext.Action({
            text: this.app.i18n._('Save Account'),
            allowMultiple: false,
            tooltip : this.app.i18n._('Saves the account (without closing window)'),
            handler : this.onSaveAccount,
            iconCls : 'action_Save',
            scope : this
        });
        this.action_syncAccount = new Ext.Action({
            text: this.app.i18n._('Synchronize Lines'),
            allowMultiple: false,
            tooltip : this.app.i18n._('Synchronizes the selected account (creates or updates the associated lines)'),
            handler : this.onSyncAccount,
            iconCls : 'action_Sync',
            scope : this
        });
        this.action_validateAccount = new Ext.Action({
            text: this.app.i18n._('Validate Account'),
            allowMultiple: false,
            tooltip : this.app.i18n._('Validates the selected account (creates or updates the associated lines)'),
            handler : this.onValidateAccount,
            iconCls : 'action_Validate',
            disabledClass: 'action_ValidateSuccessful',
            scope : this
        });
        Tine.Sipgate.AccountEditDialog.superclass.initActions.call(this);
    },
    
    onSyncAccount: function() {
        if (this.record.get('created_by')) {
            if(this.isValid(null)) {
                this.syncMask.show();
                this.recordProxy.syncLines(this.record.get('id'), this.onSyncSuccess, this.onRequestFailed, this);
            }
        } else {
            Ext.Msg.show({
               title:   this.app.i18n._('The account is not saved already!'),
               msg:     this.app.i18n._('Please save yout account before syncing!'),
               icon:    Ext.MessageBox.INFO,
               buttons: Ext.Msg.OK
            });
        }
    },
    
    onSyncSuccess: function(record) {
        this.lineGridPanel.enable();
        this.record = record;
        this.lineGridPanel.onRecordLoad();
        if(this.syncMask) this.syncMask.hide();
        Ext.Msg.show({
               title:   this.app.i18n._('The lines have been synced sucessfully!'),
               msg:     String.format(this.app.i18n._('The account has now {0} lines. Go on assigning user accounts to the new lines in the next tab.'), record.get('lines').length),
               icon:    Ext.MessageBox.INFO,
               buttons: Ext.Msg.OK
            });
    },
    
    onSaveAccount: function() {
        if(this.isValid(null, true)) {
            Tine.Sipgate.AccountEditDialog.superclass.onApplyChanges.call(this, null, null, false);
        }
    },
    
    onValidateAccount: function() {
        if(this.isValid(true)) {
            this.validateMask.show();
            this.onRecordUpdate();
            this.recordProxy.validateAccount(this.record, this.onValidateSuccess, this.onRequestFailed, this);
        } else {
            Ext.MessageBox.alert(_('Errors'), this.getValidationErrorMessage());
        }
        
    },
    
    onValidateSuccess: function() {
        this.record.set('is_valid', true);
        this.setValidated(true);
        this.validateMask.hide();
    },
    
    resetValidated: function(manual) {
        this.getForm().findField('is_valid').setValue(0);
        this.getForm().findField('is_valid').disable();
        
        Ext.each(this.credentialFields, function(fieldname) {
                this.getForm().findField(fieldname).allowBlank = false;
            }, this);

        Ext.each(this.toggleFields, function(fieldname) {
            this.getForm().findField(fieldname).enable();
        }, this);
        
        this.action_validateAccount.enable();
        this.action_validateAccount.setIconClass('action_Validate');
    },
    
    setValidated: function(s) {
        if(s) {
            Ext.each(this.credentialFields, function(fieldname) {
                this.getForm().findField(fieldname).allowBlank = true;
                this.getForm().findField(fieldname).reset();
            }, this);
        }
        this.getForm().findField('is_valid').setValue(1);
        Ext.each(this.toggleFields, function(fieldname) {
            this.getForm().findField(fieldname).disable();
        }, this);
        this.getForm().findField('is_valid').enable();
        this.action_validateAccount.disable();
        this.action_validateAccount.setIconClass('action_ValidateSuccessful');
    },

    onRecordUpdate: function() {
        // update record from form only when not validated
        if(! this.getForm().findField('is_valid').getValue()) {
            Tine.Sipgate.AccountEditDialog.superclass.onRecordUpdate.call(this);
        } else { // sync lines, when validated already
            if(this.lineGridPanel.store) {
                var lines = [];
                this.lineGridPanel.store.purgeListeners();
                this.lineGridPanel.store.each(function(record) {
                    record.set('account_id', this.record.get('id'));
                    record.set('user_id', record.get('user_id') ? record.get('user_id').accountId : null);
                    lines.push(record.data);
                }, this);
                this.record.set('lines', lines);
                this.record.set('mobile_number', this.getForm().findField('mobile_number').getValue());
                this.record.set('description',   this.getForm().findField('description').getValue());
            }
        }
    },
    
    onRecordLoad: function() {
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
       
        Tine.Sipgate.AccountEditDialog.superclass.onRecordLoad.call(this);
        if(!this.record.get('is_valid')) {
            this.resetValidated();
        } else {
            this.setValidated(true);
        }
        
        if(this.record.get('lines') && this.record.get('lines').length > 0) {
            this.lineGridPanel.onRecordLoad();
        } else {
            this.lineGridPanel.disable();
        }
    },
    
    onRequestFailed: function(exception) {
        this.resetValidated();
        this.validateMask.hide();
        this.syncMask.hide();
        if(this.loadMask) this.loadMask.hide();
        Tine.Sipgate.handleRequestException(exception);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        
        this.lineGridPanel = new Tine.Sipgate.AssignLinesGrid({app: this.app, editDialog: this});
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Account', 'Account', 1),
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
                        columnWidth: .333,
                        allowBlank: false
                    },
                    items: [[
                        { 
                            fieldLabel: this.app.i18n._('Description'),
                            name: 'description'
                        }, 
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                                fieldLabel: this.app.i18n._('Account Type'),
                                name: 'accounttype',
                                app: 'Sipgate',
                                value: 'plus',
                                keyFieldName: 'accountAccountType',
                                listeners: {
                                    scope: this,
                                    select: function(combo,b,c) {
                                        if(combo.getValue() == 'team') {
                                            this.getForm().findField('mobile_number').disable();
                                        } else {
                                            this.getForm().findField('mobile_number').enable();
                                        }
                                    }
                                }
                        }),
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                                fieldLabel: this.app.i18n._('Type'),
                                name: 'type',
                                value: 'shared',
                                app: 'Sipgate',
                                keyFieldName: 'accountType'
                        })], [{
                            name: 'mobile_number',
                            fieldLabel: this.app.i18n._('Mobile Number')
                        }], 
                        [{
                            fieldLabel: this.app.i18n._('Sipgate Username'),
                            name: 'username',
                            emptyText: this.app.i18n._('Hidden due to security reasons.')
                        }, {
                            fieldLabel: this.app.i18n._('Password'),
                            name: 'password',
                            inputType:'password'
                        }, {
                            fieldLabel: this.app.i18n._('Passwort Repeat'),
                            name: 'password_repeat',
                            inputType:'password'
                        }], [{ 
                            xtype: 'checkbox',
                            name: 'is_valid',
                            fieldLabel: this.app.i18n._('Is Validated'),
                            listeners: {
                                scope:this,
                                check: function(check) {
                                    if (check.getValue() == false) {
                                        this.resetValidated(1);
                                    }
                                }
                            }
                        }
                        ]] 
                }]
            }, this.lineGridPanel
            ]
        };
    },
    
    isValid: function(calledFromOnValidate) {
        var isValid = true;
        if(!this.record.get('is_valid')) {
            isValid = isValid & Tine.Sipgate.AccountEditDialog.superclass.isValid.call(this);
            
            if(this.getForm().findField('password').getValue() != this.getForm().findField('password_repeat').getValue()) {
                this.getForm().findField('password').markInvalid('The Fields "Password" and "Password Repeat" must have the same values!');
                this.getForm().findField('password_repeat').markInvalid('The Fields "Password" and "Password Repeat" must have the same values!');
                isValid = false;
            }
            if(this.getForm().findField('username').getValue() != this.usernameEmptyText) {
                if(this.getForm().findField('username').getValue() == '') {
                    this.getForm().findField('username').markInvalid();
                    isValid = false;
                }
            }
        } else {
            isValid = true;
        }
        if(!calledFromOnValidate && isValid) {
            if(!this.getForm().findField('is_valid').getValue()) {
                Ext.MessageBox.alert(_('Errors'), this.app.i18n._('Please validate the account settings before saving!'));
                isValid = false;
            }
        }
        return isValid;
    }
});

/**
 * Sipgate Edit Popup
 */
Tine.Sipgate.AccountEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Sipgate.AccountEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Sipgate.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
