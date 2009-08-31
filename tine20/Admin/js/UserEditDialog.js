/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Admin', 'Tine.Admin.Users');

/**
 * @namespace   Tine.Admin.Users
 * @class       Tine.Admin.Users.EditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>User Edit Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.Users.EditDialog
 */
Tine.Admin.Users.EditDialog  = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'userEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.User,
    recordProxy: Tine.Admin.userBackend,
    evalGrants: false,
    
    /**
     * @private
     */
    initComponent: function() {
        var accountBackend = Tine.Tinebase.registry.get('accountBackend');
        this.ldapBackend = (accountBackend == 'Ldap');

        Tine.Admin.Users.EditDialog.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    onRecordLoad: function() {
        // samba user
        var response = {
            responseText: Ext.util.JSON.encode(this.record.get('sambaSAM'))
        };
        this.samRecord = Tine.Admin.samUserBackend.recordReader(response);
        this.getForm().loadRecord(this.samRecord);

        // email user
        var emailResponse = {
            responseText: Ext.util.JSON.encode(this.record.get('emailUser'))
        };
        this.emailRecord = Tine.Admin.emailUserBackend.recordReader(emailResponse);
        this.getForm().loadRecord(this.emailRecord);
        
        Tine.Admin.Users.EditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Admin.Users.EditDialog.superclass.onRecordUpdate.call(this);
        
        var form = this.getForm();
        
        form.updateRecord(this.samRecord);
        this.record.set('sambaSAM', '');
        this.record.set('sambaSAM', this.samRecord.data);

        form.updateRecord(this.emailRecord);
        this.record.set('emailUser', '');
        this.record.set('emailUser', this.emailRecord.data);
    },

    /**
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{               
                title: this.app.i18n._('Account'),
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
                        columnWidth: .333
                    },
                    items: [[{
                            fieldLabel: this.app.i18n._('First Name'),
                            name: 'accountFirstName',
                            columnWidth: .666,
                            listeners: {render: function(field){field.focus(false, 250);}},
                            tabIndex: 1
                        }, {
                            xtype: 'combo',
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'accountStatus',
                            mode: 'local',
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: [['enabled', this.app.i18n._('enabled')],['disabled', this.app.i18n._('disabled')]],
                            disabled: this.ldapBackend
                        }], [{
                            fieldLabel: this.app.i18n._('Last Name'),
                            name: 'accountLastName',
                            allowBlank: false,
                            columnWidth: .666,
                            tabIndex: 2
                        }, new Ext.ux.form.ClearableDateField({ 
                            fieldLabel: this.app.i18n._('Expires'),
                            name: 'accountExpires',
                            emptyText: this.app.i18n._('never')
                        })], [{
                            fieldLabel: this.app.i18n._('Login Name'),
                            name: 'accountLoginName',
                            allowBlank: false,
                            columnWidth: .666,
                            tabIndex: 3
                        }, {
                            xtype: 'datetimefield',
                            fieldLabel: this.app.i18n._('Last login at'),
                            name: 'accountLastLogin',
                            emptyText: this.ldapBackend ? this.app.i18n._("don't know") : this.app.i18n._('never logged in'),
                            hideTrigger: true,
                            readOnly: true,
                            tabIndex: -1
                        }], [{
                            fieldLabel: this.app.i18n._('Password'),
                            name: 'accountPassword',
                            inputType: 'password',
                            emptyText: this.app.i18n._('no password set'),
                            columnWidth: .666,
                            tabIndex: 4
                        }, {
                            xtype: 'textfield',
                            fieldLabel: this.app.i18n._('Last login from'),
                            name: 'accountLastLoginfrom',
                            emptyText: this.ldapBackend ? this.app.i18n._("don't know") : this.app.i18n._('never logged in'),
                            readOnly: true,
                            tabIndex: -1
                        }], [{
                            fieldLabel: this.app.i18n._('Password again'),
                            name: 'accountPassword2',
                            inputType: 'password',
                            emptyText: this.app.i18n._('no password set'),
                            columnWidth: .666,
                            tabIndex: 5
                        }, {
                            xtype: 'datetimefield',
                            fieldLabel: this.app.i18n._('Password set'),
                            name: 'accountLastPasswordChange',
                            emptyText: this.app.i18n._('never'),
                            hideTrigger: true,
                            readOnly: true,
                            tabIndex: -1
                        }], [new Tine.widgets.group.selectionComboBox({
                            fieldLabel: this.app.i18n._('Primary group'),
                            name: 'accountPrimaryGroup',
                            displayField:'name',
                            valueField:'id',
                            columnWidth: .666,
                            tabIndex: 6
                        })], [{
                            vtype: 'email',
                            fieldLabel: this.app.i18n._('Emailaddress'),
                            name: 'accountEmailAddress',
                            columnWidth: .666,
                            tabIndex: 7
                        }], [{
                            fieldLabel: this.app.i18n._('Home Directory'),
                            name: 'accountHomeDirectory',
                            columnWidth: .666,
                            tabIndex: 8
                        }], [{
                            fieldLabel: this.app.i18n._('Login Shell'),
                            name: 'accountLoginShell',
                            columnWidth: .666,
                            tabIndex: 9
                        }]
                    ] 
                }]
            }, {
                title: this.app.i18n._('Fileserver'),
                disabled: !this.ldapBackend,
                border: false,
                frame: true,
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: [[{
                    fieldLabel: this.app.i18n._('Home Drive'),
                    name: 'homeDrive',
                    columnWidth: .666
                }, {
                    xtype: 'datetimefield',
                    fieldLabel: this.app.i18n._('Logon Time'),
                    name: 'logonTime',
                    emptyText: this.app.i18n._('never logged in'),
                    hideTrigger: true,
                    readOnly: true
                }], [{
                    fieldLabel: this.app.i18n._('Home Path'),
                    name: 'homePath',
                    columnWidth: .666
                }, {
                    xtype: 'datetimefield',
                    fieldLabel: this.app.i18n._('Logoff Time'),
                    name: 'logoffTime',
                    emptyText: this.app.i18n._('never logged off'),
                    hideTrigger: true,
                    readOnly: true
                }], [{
                    fieldLabel: this.app.i18n._('Profile Path'),
                    name: 'profilePath',
                    columnWidth: .666
                }, {
                    xtype: 'datetimefield',
                    fieldLabel: this.app.i18n._('Password Last Set'),
                    name: 'pwdLastSet',
                    emptyText: this.app.i18n._('never'),
                    hideTrigger: true,
                    readOnly: true
                }], [{
                    fieldLabel: this.app.i18n._('Logon Script'),
                    name: 'logonScript',
                    columnWidth: .666
                }], [new Ext.ux.form.ClearableDateField({ 
                    fieldLabel: this.app.i18n._('Password Can Change'),
                    name: 'pwdCanChange',
                    emptyText: this.app.i18n._('not set')
                }), new Ext.ux.form.ClearableDateField({ 
                    fieldLabel: this.app.i18n._('Password Must Change'),
                    name: 'pwdMustChange',
                    emptyText: this.app.i18n._('not set')
                }), new Ext.ux.form.ClearableDateField({ 
                    fieldLabel: this.app.i18n._('Kick Off Time'),
                    name: 'kickoffTime',
                    emptyText: this.app.i18n._('not set')
                })]]
            }, {
                title: this.app.i18n._('IMAP'),
                disabled: ! Tine.Admin.registry.get('manageEmailUser'),
                border: false,
                frame: true,
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333,
                    readOnly: true
                },
                items: [[{
                    fieldLabel: this.app.i18n._('Email Username'),
                    name: 'emailUserId',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Quota'),
                    name: 'emailMailQuota',
                    columnWidth: .666,
                    readOnly: false
                }], [{
                    fieldLabel: this.app.i18n._('Current Mail Size'),
                    name: 'emailMailSize',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Sieve Quota'),
                    name: 'emailSieveQuota',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Current Sieve Size'),
                    name: 'emailSieveSize',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Last Login'),
                    name: 'emailLastLogin',
                    columnWidth: .666
                }]]
            }]
        };
    },

    /**
     * checks if form is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        // check if passwords match
        var form = this.getForm();
        if (form.findField('accountPassword').getValue() != form.findField('accountPassword2').getValue()) {
            form.markInvalid([{
                id: 'accountPassword2',
                msg: this.app.i18n._("Passwords don't match")
            }]);
            return false;
        }
        
        return Tine.Admin.Users.EditDialog.superclass.isValid.call(this);
    }
});

/**
 * User Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.Users.EditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 500,
        name: Tine.Admin.Users.EditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.Users.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
