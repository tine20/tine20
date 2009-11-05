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
        
        this.record.set('accountLastLogin', Tine.Tinebase.common.dateTimeRenderer(this.record.get('accountLastLogin')));
        this.record.set('accountLastPasswordChange', Tine.Tinebase.common.dateTimeRenderer(this.record.get('accountLastPasswordChange')));
        
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
        
        var displayFieldStyle = {
            border: 'silver 1px solid',
            padding: '3px',
            height: '11px'
        };
        
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
                layout: 'hfit',
                items: [{
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
	                        columnWidth: .5,
	                        listeners: {
                    			render: function(field){
	                    			field.focus(false, 250); 
	                    			field.selectText();
                				}
            				}
	                    }, {
	                        fieldLabel: this.app.i18n._('Last Name'),
	                        name: 'accountLastName',
	                        allowBlank: false,
	                        columnWidth: .5
	                    }], [
		                    {
		                        fieldLabel: this.app.i18n._('Login Name'),
		                        name: 'accountLoginName',
		                        allowBlank: false,
		                        columnWidth: .5
		                    }, {
		                        fieldLabel: this.app.i18n._('Password'),
		                        name: 'accountPassword',
		                        inputType: 'password',
		                        emptyText: this.app.i18n._('no password set'),
		                        columnWidth: .5,
                                passwordsMatch: true,
                                listeners: {
                                    scope: this,
                                    blur: function(field) {
                                        var value = field.getValue();
                                        if (value != '') {
                                            // show password confirmation
                                            // TODO use password field here
                                            Ext.Msg.prompt(this.app.i18n._('Password confirmation'), this.app.i18n._('Please repeat the password:'), function(btn, text){
                                                if (btn == 'ok'){
                                                    if (text != value) {
                                                        field.markInvalid(this.app.i18n._('Passwords do not match!'));
                                                        field.passwordsMatch = false;
                                                    } else {
                                                        field.passwordsMatch = true;
                                                    }
                                                }
                                            }, this);
                                        }
                                    }
                                },
                                validateValue : function(value){
                                    return this.passwordsMatch;
                                }
		                    }
	                    ], [
	                     	{
		                        vtype: 'email',
		                        fieldLabel: this.app.i18n._('Emailaddress'),
		                        name: 'accountEmailAddress',
		                        columnWidth: .5
		                    }, {
		                        //vtype: 'email',
		                        fieldLabel: this.app.i18n._('OpenID'),
		                        name: 'openid',
		                        columnWidth: .5
		                    }
	                    ], [
	                    	new Tine.widgets.group.selectionComboBox({
	                            fieldLabel: this.app.i18n._('Primary group'),
	                            name: 'accountPrimaryGroup',
	                            displayField:'name',
	                            valueField:'id'
	                        }), {
	                            xtype: 'combo',
	                            fieldLabel: this.app.i18n._('Status'),
	                            name: 'accountStatus',
	                            mode: 'local',
	                            triggerAction: 'all',
	                            allowBlank: false,
	                            editable: false,
	                            store: [['enabled', this.app.i18n._('enabled')],['disabled', this.app.i18n._('disabled')],['expired', this.app.i18n._('expired')]],
	                            disabled: this.ldapBackend
	                        }, new Ext.ux.form.ClearableDateField({ 
	                            fieldLabel: this.app.i18n._('Expires'),
	                            name: 'accountExpires',
	                            emptyText: this.app.i18n._('never')
	                        })
                        ], [
							{
	                            xtype: 'combo',
	                            fieldLabel: this.app.i18n._('Visibility'),
	                            name: 'visibility',
	                            mode: 'local',
	                            triggerAction: 'all',
	                            allowBlank: false,
	                            editable: false,
	                            store: [['displayed', this.app.i18n._('Display in addressbook')], ['hidden', this.app.i18n._('Hide from addressbook')]]
							}                            
                        ]
                    ] 
                }, {
                    title: this.app.i18n._('Information'),
                    autoHeight: true,
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    layout: 'hfit',
                    items: [{
	                	xtype: 'columnform',
	                    labelAlign: 'top',
	                    formDefaults: {
                    		xtype: 'displayfield',
	                        anchor: '100%',
	                        labelSeparator: '',
	                        columnWidth: .333,
                    		style: displayFieldStyle
	                    },
	                    items: [[{
		                        fieldLabel: this.app.i18n._('Last login at'),
		                        name: 'accountLastLogin',
		                        emptyText: this.ldapBackend ? this.app.i18n._("don't know") : this.app.i18n._('never logged in')
		                    }, {
		                        fieldLabel: this.app.i18n._('Last login from'),
		                        name: 'accountLastLoginfrom',
		                        emptyText: this.ldapBackend ? this.app.i18n._("don't know") : this.app.i18n._('never logged in')
		                    }, {
		                        fieldLabel: this.app.i18n._('Password set'),
		                        name: 'accountLastPasswordChange',
		                        emptyText: this.app.i18n._('never')
		                    }]
	                    ]
	                }]
                }]
            }, {
                title: this.app.i18n._('Fileserver'),
                disabled: !this.ldapBackend,
                border: false,
                frame: true,
                items: [{
                    title: this.app.i18n._('Unix'),
                    autoHeight: true,
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    layout: 'hfit',
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype:'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .333
                        },
                        items: [[{
                            fieldLabel: this.app.i18n._('Home Directory'),
                            name: 'accountHomeDirectory',
                            columnWidth: .666
                        }, {
                            fieldLabel: this.app.i18n._('Login Shell'),
                            name: 'accountLoginShell'
                        }]]
                    }]
                }, {
                    title: this.app.i18n._('Windows'),
                    autoHeight: true,
                    xtype: 'fieldset',
                    checkboxToggle: false,
                    layout: 'hfit',
                    items: [{
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
                            xtype: 'displayfield',
                            fieldLabel: this.app.i18n._('Logon Time'),
                            name: 'logonTime',
                            emptyText: this.app.i18n._('never logged in'),
                            style: displayFieldStyle
                        }], [{
                            fieldLabel: this.app.i18n._('Home Path'),
                            name: 'homePath',
                            columnWidth: .666
                        }, {
                            xtype: 'displayfield',
                            fieldLabel: this.app.i18n._('Logoff Time'),
                            name: 'logoffTime',
                            emptyText: this.app.i18n._('never logged off'),
                            style: displayFieldStyle
                        }], [{
                            fieldLabel: this.app.i18n._('Profile Path'),
                            name: 'profilePath',
                            columnWidth: .666
                        }, {
                            xtype: 'displayfield',
                            fieldLabel: this.app.i18n._('Password Last Set'),
                            name: 'pwdLastSet',
                            emptyText: this.app.i18n._('never'),
                            style: displayFieldStyle
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
                    }]
                }]
            }, {
                title: this.app.i18n._('IMAP'),
                disabled: ! Tine.Admin.registry.get('manageImapEmailUser'),
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
                    fieldLabel: this.app.i18n._('Email UID'),
                    name: 'emailUID',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Email GID'),
                    name: 'emailGID',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Email Username'),
                    name: 'emailUserId',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Quota'),
                    name: 'emailMailQuota',
                    xtype:'numberfield',
                    columnWidth: .666,
                    readOnly: false
                }], [{
                    fieldLabel: this.app.i18n._('Current Mail Size'),
                    name: 'emailMailSize',
                    xtype:'numberfield',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Sieve Quota'),
                    name: 'emailSieveQuota',
                    xtype:'numberfield',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Current Sieve Size'),
                    name: 'emailSieveSize',
                    xtype:'numberfield',
                    columnWidth: .666
                }], [{
                    fieldLabel: this.app.i18n._('Last Login'),
                    name: 'emailLastLogin',
                    columnWidth: .666
                }]]
            }, {
                title: this.app.i18n._('SMTP'),
                disabled: ! Tine.Admin.registry.get('manageSmtpEmailUser'),
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
                    fieldLabel: this.app.i18n._('Aliases'),
                    name: 'emailAliases',
                    columnWidth: .666,
                    readOnly: false
                }], [{
                    fieldLabel: this.app.i18n._('Forwards'),
                    name: 'emailForwards',
                    columnWidth: .666,
                    readOnly: false
                }], [{
                    fieldLabel: this.app.i18n._('Forward Only'),
                    name: 'emailForwardOnly',
                    xtype:'checkbox',
                    columnWidth: .666,
                    readOnly: false
                }]]
            }]
        };
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
        width: 600,
        height: 400,
        name: Tine.Admin.Users.EditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.Users.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
