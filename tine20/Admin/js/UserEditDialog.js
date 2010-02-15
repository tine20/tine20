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
 * <p>
 * TODO         use quota fieldset checkbox information (checked/unchecked) 
 * </p>
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
        this.samRecord.data.homeDrive = 'cyz';
        // email user
        var emailResponse = {
            responseText: Ext.util.JSON.encode(this.record.get('emailUser'))
        };
        this.emailRecord = Tine.Admin.emailUserBackend.recordReader(emailResponse);
        
        // format dates
        var dateTimeDisplayFields = ['accountLastLogin', 'accountLastPasswordChange', 'logonTime', 'logoffTime', 'pwdLastSet', 'kickoffTime'];
        for (var i=0; i < dateTimeDisplayFields.length; i++) {
            if (dateTimeDisplayFields[i] == 'accountLastLogin' || dateTimeDisplayFields[i] == 'accountLastPasswordChange') {
                this.record.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.record.get(dateTimeDisplayFields[i])));
            } else {
                this.samRecord.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.samRecord.get(dateTimeDisplayFields[i])));
            }
        }

        this.getForm().loadRecord(this.emailRecord);
        this.getForm().loadRecord(this.samRecord);
        this.record.set('sambaSAM', this.samRecord.data);

        if (Tine.Admin.registry.get('manageSmtpEmailUser')) {
            this.aliasesGrid.setStoreFromArray(this.emailRecord.get('emailAliases'));
            this.forwardsGrid.setStoreFromArray(this.emailRecord.get('emailForwards'));
        }
        
        Tine.Admin.Users.EditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Admin.Users.EditDialog.superclass.onRecordUpdate.call(this);
        
        var form = this.getForm();
        form.updateRecord(this.samRecord);
        if (this.samRecord.dirty) {
            // only update sam record if something changed
            this.record.set('sambaSAM', '');
            this.record.set('sambaSAM', this.samRecord.data);
        }

        form.updateRecord(this.emailRecord);
        // get aliases / forwards
        if (Tine.Admin.registry.get('manageSmtpEmailUser')) {
            this.emailRecord.set('emailAliases', this.aliasesGrid.getFromStoreAsArray());
            this.emailRecord.set('emailForwards', this.forwardsGrid.getFromStoreAsArray());
        }
        this.record.set('emailUser', '');
        this.record.set('emailUser', this.emailRecord.data);
    },

    /**
     * 'ok' handler for passwordConfirmWindow
     */
    onPasswordConfirm: function() {
        var confirmForm = this.passwordConfirmWindow.items.first().getForm();
        var confirmValues = confirmForm.getValues();
        var passwordField = this.getForm().findField('accountPassword');
        
        if (! passwordField) {
            // oops: something went wrong, this should not happen
            return;
        }
        
        if (confirmValues.passwordRepeat != passwordField.getValue()) {
            passwordField.markInvalid(this.app.i18n._('Passwords do not match!'));
            passwordField.passwordsMatch = false;
        } else {
            passwordField.passwordsMatch = true;
            passwordField.clearInvalid();
            
            // focus email field
            this.getForm().findField('accountEmailAddress').focus(true, 100);
        }
        
        this.passwordConfirmWindow.hide();
        confirmForm.reset();
    },
    
    /**
     * @private
     * 
     * init email grids
     * 
     * TODO     add ctx menu
     * TODO     make border work
     */
    initEmailGrids: function() {
        
        var commonConfig = {
            autoExpandColumn:'email',
            quickaddMandatory: 'email',
            //border: true,
            frame: false,
            useBBar: true,
            dataField: 'email',
            height: 200,
            columnWidth: .5,
            recordClass: Ext.data.Record.create([
                { name: 'email' }
            ])
        };
        
        this.aliasesGrid = new Tine.Tinebase.widgets.grid.QuickaddGridPanel(
            Ext.apply(commonConfig, {
                //title:this.app.i18n._('Aliases'),
                cm: new Ext.grid.ColumnModel([{ 
                    id:'email', 
                    header: this.app.i18n._('Email Alias'), 
                    dataIndex: 'email', 
                    width: 300, 
                    hideable: false, 
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.app.i18n._('Add an alias address...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false}) 
                }])
            })
        );

        this.forwardsGrid = new Tine.Tinebase.widgets.grid.QuickaddGridPanel(
            Ext.apply(commonConfig, {
                //title:this.app.i18n._('Forwards'),
                cm: new Ext.grid.ColumnModel([{ 
                    id:'email', 
                    header: this.app.i18n._('Email Forward'), 
                    dataIndex: 'email', 
                    width: 300, 
                    hideable: false, 
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.app.i18n._('Add a forward address...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false}) 
                }])
            })
        );
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
        
        this.initEmailGrids();
        
        this.passwordConfirmWindow = new Ext.Window({
            title: this.app.i18n._('Password confirmation'),
            closeAction: 'hide',
            modal: true,
            width: 300,
            height: 130,
            //layout: 'fit',
            //plain: true,
            items: new Ext.FormPanel({
                bodyStyle: 'padding:5px;',
                buttonAlign: 'right',
                labelAlign: 'top',
                anchor:'100%',
                items: [{
                    xtype: 'textfield',
                    inputType: 'password',
                    anchor: '100%',
                    id: 'passwordRepeat',
                    fieldLabel: this.app.i18n._('Repeat password'), 
                    name:'passwordRepeat',
                    listeners: {
                        scope: this,
                        specialkey: function(field, event){
                            if(event.getKey() == event.ENTER){
                                this.onPasswordConfirm();
                            }
                        }
                    }
                }],
                buttons: [{
                    text: _('Cancel'),
                    iconCls: 'action_cancel',
                    handler: function() {
                        this.passwordConfirmWindow.hide();
                    },
                    scope: this
                }, {
                    text: _('Ok'),
                    iconCls: 'action_saveAndClose',
                    handler: this.onPasswordConfirm,
                    scope: this
                }]
            }),
            listeners: {
                scope: this,
                show: function(win) {
                    var confirmForm = this.passwordConfirmWindow.items.first().getForm();
                    var confirmField = confirmForm.findField('passwordRepeat');
                    
                    confirmField.focus(true, 500);
                }
            }
        });
        this.passwordConfirmWindow.render(document.body);
        
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
                                id: 'accountPassword',
		                        name: 'accountPassword',
		                        inputType: 'password',
		                        emptyText: this.app.i18n._('no password set'),
		                        columnWidth: .5,
                                passwordsMatch: true,
                                listeners: {
                                    scope: this,
                                    blur: function(field) {
                                        var fieldValue = field.getValue();
                                        if (fieldValue != '') {
                                            // show password confirmation
                                            // NOTE: we can't use Ext.Msg.prompt because field has to be of inputType: 'password'
                                            this.passwordConfirmWindow.show.defer(100, this.passwordConfirmWindow);
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
                                id: 'accountEmailAddress',
		                        columnWidth: .5
		                    }, {
		                        //vtype: 'email',
		                        fieldLabel: this.app.i18n._('OpenID'),
		                        name: 'openid',
		                        columnWidth: .5
		                    }
	                    ], [
                            new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                                fieldLabel: this.app.i18n._('Primary group'),
                                name: 'accountPrimaryGroup',
                                blurOnSelect: true,
                                recordClass: Tine.Tinebase.Model.Group
                            }), {
	                            xtype: 'combo',
	                            fieldLabel: this.app.i18n._('Status'),
	                            name: 'accountStatus',
	                            mode: 'local',
	                            triggerAction: 'all',
	                            allowBlank: false,
	                            editable: false,
	                            store: [['enabled', this.app.i18n._('enabled')],['disabled', this.app.i18n._('disabled')],['expired', this.app.i18n._('expired')]]
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
                //disabled: !this.ldapBackend,
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
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'hfit',
                    items: [{
                        title: this.app.i18n._('Quota (MB)'),
                        autoHeight: true,
                        xtype: 'fieldset',
                        checkboxToggle: true,
                        layout: 'hfit',
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype: 'textfield',
                                anchor: '100%',
                                columnWidth: .666
                            },
                            items: [[{
                                fieldLabel: this.app.i18n._('Quota'),
                                name: 'emailMailQuota',
                                xtype:'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 10,
                                    allowDecimals : false
                                })
                            }], [{
                                fieldLabel: this.app.i18n._('Current Mail Size'),
                                name: 'emailMailSize',
                                xtype:'displayfield',
                                style: displayFieldStyle
                            }]
                            ]
                        }]
                    }, {
                        title: this.app.i18n._('Sieve Quota (MB)'),
                        autoHeight: true,
                        xtype: 'fieldset',
                        checkboxToggle: true,
                        layout: 'hfit',
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype: 'textfield',
                                anchor: '100%',
                                columnWidth: .666
                            },
                            items: [[{
                                fieldLabel: this.app.i18n._('Sieve Quota'),
                                name: 'emailSieveQuota',
                                xtype:'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 10,
                                    allowDecimals : false
                                })
                            }], [{
                                fieldLabel: this.app.i18n._('Sieve Size'),
                                name: 'emailSieveSize',
                                xtype:'displayfield',
                                style: displayFieldStyle
                            }]
                            ]
                        }]
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
                                columnWidth: .666,
                                style: displayFieldStyle
                            },
                            items: [[{
                                fieldLabel: this.app.i18n._('Last Login'),
                                name: 'emailLastLogin'
                            }]]
                        }]
                    }]
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
                items: [
                    [this.aliasesGrid, this.forwardsGrid],
                [{
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
