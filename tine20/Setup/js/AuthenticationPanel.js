/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ConfigManagerGridPanel.js 7153 2009-03-03 20:21:52Z c.weiss@metaways.de $
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');
 
/**
 * Setup Authentication Manager
 * 
 * @namespace   Tine.Setup
 * @class       Tine.Setup.AuthenticationPanel
 * @extends     Tine.Tinebase.widgets.form.ConfigPanel
 * 
 * <p>Authentication Panel</p>
 * <p><pre>
 * TODO         move to next step after install?
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ConfigManagerGridPanel.js 7153 2009-03-03 20:21:52Z c.weiss@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.AuthenticationPanel
 */
Tine.Setup.AuthenticationPanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {
    
    /**
     * @property idPrefix DOM Id prefix
     * @type String
     */
    idPrefix: null,
    
    /**
     * authProviderPrefix DOM Id prefix
     * 
     * @property authProviderIdPrefix
     * @type String
     */
    authProviderIdPrefix: null,
    
    /**
     * accountsStorageIdPrefix DOM Id prefix
     * 
     * @property accountsStorageIdPrefix
     * @type String
     */
    accountsStorageIdPrefix: null,
    
    /**
     * combo box containing the authentication backend selection
     * 
     * @property authenticationBackendCombo
     * @type Ext.form.ComboBox 
     */
    authenticationBackendCombo: null,

    /**
     * combo box containing the accounts storage selection
     * 
     * @property accountsStorageCombo
     * @type Ext.form.ComboBox
     */
    accountsStorageCombo: null,

    /**
     * @private
     * panel cfg
     */
    saveMethod: 'Setup.saveAuthentication',
    registryKey: 'authenticationData',
    
    /**
     * @private
     */
    initComponent: function() {
        this.idPrefix                   = Ext.id();
        this.authProviderIdPrefix       = this.idPrefix + '-authProvider-',
        this.accountsStorageIdPrefix    = this.idPrefix + '-accountsStorage-',
        
        Tine.Setup.AuthenticationPanel.superclass.initComponent.call(this);
    },
    
    /**
     * Change card layout depending on selected combo box entry
     */
    onChangeAuthProvider: function() {
        var authProvider = this.authenticationBackendCombo.getValue();
        
        var cardLayout = Ext.getCmp(this.authProviderIdPrefix + 'CardLayout').getLayout();
        cardLayout.setActiveItem(this.authProviderIdPrefix + authProvider);
    },
    
    /**
     * Change card layout depending on selected combo box entry
     */
    onChangeAccountsStorage: function() {
        var AccountsStorage = this.accountsStorageCombo.getValue();
        var cardLayout = Ext.getCmp(this.accountsStorageIdPrefix + 'CardLayout').getLayout();
        cardLayout.setActiveItem(this.accountsStorageIdPrefix + AccountsStorage);
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Setup.AuthenticationPanel.superclass.onRender.call(this, ct, position);
        
        this.onChangeAuthProvider.defer(250, this);
        this.onChangeAccountsStorage.defer(250, this);
    },
    
   /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function() {
        var setupRequired = Tine.Setup.registry.get('setupRequired');
        
        this.authenticationBackendCombo = new Ext.form.ComboBox({
            width: 300,
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [['Sql', 'Sql'], ['Ldap','Ldap']],
                name: 'authentication_backend',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'Sql',
                listeners: {
                    scope: this,
                    change: this.onChangeAuthProvider,
                    select: this.onChangeAuthProvider
                }
            });
            
       this.accountsStorageCombo = new Ext.form.ComboBox({
                xtype: 'combo',
                width: 300,
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [['Sql', 'Sql'], ['Ldap','Ldap']],
                name: 'accounts_backend',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'Sql',
                listeners: {
                    scope: this,
                    change: this.onChangeAccountsStorage,
                    select: this.onChangeAccountsStorage
                }
            });
        
        return [ {
            xtype:'fieldset',
            collapsible: false,
            autoHeight:true,
            title: this.app.i18n._('Authentication provider'),
            items: [
                this.authenticationBackendCombo,
                {
                id: this.authProviderIdPrefix + 'CardLayout',
                layout: 'card',
                activeItem: this.authProviderIdPrefix + 'Sql',
                border: false,
                defaults: {
                    border: false
                },
                items: [ {
                    id: this.authProviderIdPrefix + 'Sql',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield',
                        inputType: 'password'
                    },
                    items: [ {
                        inputType: 'text',
                        name: 'authentication_Sql_adminLoginName',
                        fieldLabel: this.app.i18n._('Initial admin login name'),
                        disabled: !setupRequired
                    }, {
                        name: 'authentication_Sql_adminPassword',
                        fieldLabel: this.app.i18n._('Initial admin Password'),
                        disabled: !setupRequired
                    }, {
                        name: 'authentication_Sql_adminPasswordConfirmation',
                        fieldLabel: this.app.i18n._('Password confirmation'),
                        disabled: !setupRequired
                    } ]
                }, {
                    id: this.authProviderIdPrefix + 'Ldap',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield'
                    },
                    items: [{
                        inputType: 'text',
                        name: 'authentication_Ldap_host',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'authentication_Ldap_username',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'authentication_Ldap_password',
                        fieldLabel: this.app.i18n._('Password'),
                        inputType: 'password'
                    }, {
                        xtype: 'combo',
                        width: 283, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['1', 'Yes'], ['0','No']],
                        name: 'authentication_Ldap_bindRequiresDn',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'authentication_Ldap_baseDn',
                        fieldLabel: this.app.i18n._('Base DN')
                    }, {
                        name: 'authentication_Ldap_accountCanonicalForm',
                        fieldLabel: this.app.i18n._('Account canonical form')
                    }]
                }]
            } ]
          }, {
            xtype:'fieldset',
            collapsible: false,
            autoHeight:true,
            title: this.app.i18n._('Accounts storage'),
            items: [
                this.accountsStorageCombo,
                {
                id: this.accountsStorageIdPrefix + 'CardLayout',
                layout: 'card',
                activeItem: this.accountsStorageIdPrefix + 'Sql',
                border: false,
                defaults: {
                    border: false
                },
                items: [ {
                    id: this.accountsStorageIdPrefix + 'Sql',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield'
                    },
                    items: [ {
                        name: 'accounts_Sql_defaultUserGroupName',
                        fieldLabel: this.app.i18n._('Default user group name')
                    }, {
                        name: 'accounts_Sql_defaultAdminGroupName',
                        fieldLabel: this.app.i18n._('Default admin group name')
                    }, {
                        xtype: 'combo',
                        width: 300, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['1', 'Yes'], ['0','No']],
                        name: 'accounts_Sql_changepw',
                        fieldLabel: this.app.i18n._('User can change password'),
                        value: '0'
                    } ]
                }, {
                    id: this.accountsStorageIdPrefix + 'Ldap',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield'
                    },
                    items: [{
                        inputType: 'text',
                        name: 'accounts_Ldap_host',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'accounts_Ldap_username',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'accounts_Ldap_password',
                        fieldLabel: this.app.i18n._('Password'),
                        inputType: 'password'
                    }, {
                        xtype: 'combo',
                        width: 283, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['1', 'Yes'], ['0','No']],
                        name: 'accounts_Ldap_bindRequiresDn',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'accounts_Ldap_userDn',
                        fieldLabel: this.app.i18n._('User DN')
                    }, {
                        name: 'accounts_Ldap_groupsDn',
                        fieldLabel: this.app.i18n._('Groups DN')
                    }, {
                        xtype: 'combo',
                        width: 283, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['CRYPT', 'CRYPT'], ['SHA','SHA'], ['MD5','MD5']],
                        name: 'accounts_Ldap_pwEncType',
                        fieldLabel: this.app.i18n._('Password encoding'),
                        value: 'CRYPT'
                    }, {
                        xtype: 'combo',
                        width: 283, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['1', 'Yes'], ['0','No']],
                        name: 'accounts_Ldap_useRfc2307bis',
                        fieldLabel: this.app.i18n._('Use Rfc 2307 bis'),
                        value: '0'
                    }, {
                        name: 'accounts_Ldap_minUserId',
                        fieldLabel: this.app.i18n._('Min User Id')
                    }, {
                        name: 'accounts_Ldap_maxUserId',
                        fieldLabel: this.app.i18n._('Max User Id')
                    }, {
                        name: 'accounts_Ldap_minGroupId',
                        fieldLabel: this.app.i18n._('Min Group Id')
                    }, {
                        name: 'accounts_Ldap_maxGroupId',
                        fieldLabel: this.app.i18n._('Max Group Id')
                    }, {
                        name: 'accounts_Ldap_groupUUIDAttribute',
                        fieldLabel: this.app.i18n._('Group UUID Attribute name')
                    }, {
                        name: 'accounts_Ldap_userUUIDAttribute',
                        fieldLabel: this.app.i18n._('User UUID Attribute name')
                    }, {
                        name: 'accounts_Ldap_defaultUserGroupName',
                        fieldLabel: this.app.i18n._('Default user group name')
                    }, {
                        name: 'accounts_Ldap_defaultAdminGroupName',
                        fieldLabel: this.app.i18n._('Default admin group name')
                    }, {
                        xtype: 'combo',
                        width: 283, //late rendering bug
                        listWidth: 300,
                        mode: 'local',
                        forceSelection: true,
                        allowEmpty: false,
                        triggerAction: 'all',
                        selectOnFocus:true,
                        store: [['1', 'Yes'], ['0','No']],
                        name: 'accounts_Ldap_changepw',
                        fieldLabel: this.app.i18n._('Allow user to change her password?'),
                        value: '0'
                    } ]
                }]
            } ]
          } ];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_saveConfig.setDisabled(!this.isValid());
        
        if (Tine.Setup.registry.get('setupRequired')) {
            this.action_saveConfig.setText(this.app.i18n._('Save config and install'));
        } else {
            this.action_saveConfig.setText(this.app.i18n._('Save config'));
            this.getForm().findField('authentication_Sql_adminPassword').setDisabled(true);
            this.getForm().findField('authentication_Sql_adminPasswordConfirmation').setDisabled(true);
            this.getForm().findField('authentication_Sql_adminLoginName').setDisabled(true);
        }
    },
    
    /**
     * checks if form is valid
     * - password fields are equal
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var form = this.getForm();

        if (form.findField('authentication_Sql_adminPassword') 
            && form.findField('authentication_Sql_adminPassword').getValue() != form.findField('authentication_Sql_adminPasswordConfirmation').getValue()) 
        {
            form.markInvalid([{
                id: 'authentication_Sql_adminPasswordConfirmation',
                msg: this.app.i18n._("Passwords don't match")
            }]);
            return false;
        }
        
        return form.isValid();
    }
});
