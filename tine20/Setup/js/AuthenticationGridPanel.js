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
 * Setup Configuration Manager
 * 
 * @package Setup
 * 
 * @class Tine.Setup.AuthenticationGridPanel
 * @extends Ext.FormPanel
 * 
 */
Tine.Setup.AuthenticationGridPanel = Ext.extend(Ext.FormPanel, {
    
    /**
     * @property idPrefix DOM Id prefix
     */
    idPrefix: null,
    
    /**
     * @property authProviderPrefix DOM Id prefix
     */
    authProviderIdPrefix: null,
    
    /**
     * @property accountsStorageIdPrefix DOM Id prefix
     */
    accountsStorageIdPrefix: null,
    
    /**
     * @property Ext.form.ComboBox combo box containing the authentication backend selection 
     */
    authenticationBackendCombo: null,

    /**
     * @property Ext.form.ComboBox combo box containing the accounts storage selection 
     */
    accountsStorageCombo: null,
    
    border: false,
    bodyStyle:'padding:5px 5px 0',
    labelAlign: 'left',
    labelSeparator: ':',
    labelWidth: 150,
    
    // fake a store to satisfy grid panel
    store: {load: Ext.emptyFn},
    
    /**
     * save config and update setup registry
     */
    onApplyChanges: function() {
        if (this.getForm().isValid()) {

            var authenticationData = this.form2config();
            
            this.loadMask.show();
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Setup.saveAuthentication',
                    data: Ext.util.JSON.encode(authenticationData)
                },
                success: function(response) {
                    var regData = Ext.util.JSON.decode(response.responseText);
                    // replace some registry data
                    for (key in regData) {
                        if (key != 'status') {
                            Tine.Setup.registry.replace(key, regData[key]);
                        }
                    }
                    this.loadMask.hide();
                }
            });
        } else {
            Ext.Msg.alert(this.app.i18n._('Invalid configuration'), this.app.i18n._('You need to correct the red marked fields before config could be saved'));
        }
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
    initComponent: function() {
        this.idPrefix                   = Ext.id();
        this.authProviderIdPrefix       = this.idPrefix + '-authProvider-',
        this.accountsStorageIdPrefix    = this.idPrefix + '-accountsStorage-',
        this.initActions();
        this.items = this.getFormItems();
        
        Tine.Setup.AuthenticationGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Setup.AuthenticationGridPanel.superclass.onRender.call(this, ct, position);
        
        // always the same shit! when form panel is rendered, the form fields itselv are not yet rendered ;-(
        this.config2form.defer(250, this, [Tine.Setup.registry.get('authenticationData')]);
        
        //Tine.Setup.registry.on('replace', this.applyRegistryState, this);
        this.loadMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Transfering Configuration...')});
        
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
                store: [['sql', 'sql'], ['ldap','ldap']],
                name: 'authentication_backend',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'sql',
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
                store: [['sql', 'sql'], ['ldap','ldap']],
                name: 'accounts_backend',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'sql',
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
                activeItem: this.authProviderIdPrefix + 'sql',
                border: false,
                defaults: {
                    border: false
                },
                items: [ {
                    id: this.authProviderIdPrefix + 'sql',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield',
                        inputType: 'password'
                    },
                    items: [ {
                        inputType: 'text',
                        name: 'authentication_sql_admin_loginName',
                        fieldLabel: this.app.i18n._('Initial admin login name'),
                        disabled: !setupRequired
                    }, {
                        name: 'authentication_sql_admin_password',
                        fieldLabel: this.app.i18n._('Initial admin Password'),
                        disabled: !setupRequired
                    }, {
                        name: 'authentication_sql_admin_passwordConfirmation',
                        fieldLabel: this.app.i18n._('Password confirmation'),
                        disabled: !setupRequired
                    } ]
                }, {
                    id: this.authProviderIdPrefix + 'ldap',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield'
                    },
                    items: [{
                        inputType: 'text',
                        name: 'authentication_ldap_host',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'authentication_ldap_username',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'authentication_ldap_password',
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
                        name: 'authentication_ldap_bindRequiresDn',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'authentication_ldap_baseDn',
                        fieldLabel: this.app.i18n._('Base DN')
                    }, {
                        name: 'authentication_ldap_accountCanonicalForm',
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
                activeItem: this.accountsStorageIdPrefix + 'sql',
                border: false,
                defaults: {
                    border: false
                },
                items: [ {
                    id: this.accountsStorageIdPrefix + 'sql',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield',
                        inputType: 'password'
                    }/*,
                    items: [ {
                        inputType: 'text',
                        name: 'accounts_sql_loginName',
                        fieldLabel: this.app.i18n._('Login name')
                    }, {
                        name: 'accounts_sql_password',
                        fieldLabel: this.app.i18n._('Password')
                    }, {
                        name: 'accounts_sql_passwordConfirmation',
                        fieldLabel: this.app.i18n._('Password confirmation')
                    } ] */
                }, {
                    id: this.accountsStorageIdPrefix + 'ldap',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield'
                    },
                    items: [{
                        inputType: 'text',
                        name: 'accounts_ldap_host',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'accounts_ldap_username',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'accounts_ldap_password',
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
                        name: 'accounts_ldap_bindRequiresDn',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'accounts_ldap_userDn',
                        fieldLabel: this.app.i18n._('User DN')
                    }, {
                        name: 'accounts_ldap_groupsDn',
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
                        name: 'accounts_ldap_pwEncType',
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
                        name: 'accounts_ldap_useRfc2307bis',
                        fieldLabel: this.app.i18n._('Use Rfc 2307 bis'),
                        value: '0'
                    }, {
                        name: 'accounts_ldap_minUserId',
                        fieldLabel: this.app.i18n._('Min User Id')
                    }, {
                        name: 'accounts_ldap_maxUserId',
                        fieldLabel: this.app.i18n._('Max User Id')
                    }, {
                        name: 'accounts_ldap_minGroupId',
                        fieldLabel: this.app.i18n._('Min Group Id')
                    }, {
                        name: 'accounts_ldap_maxGroupId',
                        fieldLabel: this.app.i18n._('Max Group Id')
                    } ]
                }]
            } ]
          } ];
    },
    
    /**
     * transforms form data into a config object
     * 
     * @param  {Object} formData
     * @return {Object} configData
     */
    form2config: function() {
        // getValues only returns RAW HTML content... and we don't want to 
        // define a record here
        var formData = {};
        this.getForm().items.each(function(field) {
            formData[field.name] = field.getValue();
        });
        
        var configData = {};
        var keyParts, keyPart, keyGroup, dataPath;
        for (key in formData) {
            keyParts = key.split('_');
            dataPath = configData;
            
            while (keyPart = keyParts.shift()) {
                if (keyParts.length == 0) {
                    dataPath[keyPart] = formData[key];
                } else {
                    if (!dataPath[keyPart]) {
                        dataPath[keyPart] = {};
                    }
                
                    dataPath = dataPath[keyPart];
                }
            }
        }
        return configData;
    },
    
    /**
     * loads form with config data
     * 
     * @param  {Object} configData
     */
    config2form: function(configData) {
        var formData = arguments[1] ? arguments[1] : {};
        var currKey  = arguments[2] ? arguments[2] : '';
        
        for (key in configData) {
            if(typeof configData[key] == 'object') {
                this.config2form(configData[key], formData, currKey ? currKey + '_' + key : key);
            } else {
                formData[currKey + '_' + key] = configData[key];
            }
        }

        // skip transform calls
        if (! currKey) {
            this.getForm().setValues(formData);
            this.applyRegistryState();
        }
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_applyChanges.setDisabled(!this.isValid());
    },
    
    isValid: function() {
        return this.getForm().isValid();
    },
    
    /**
     * @private
     */
    initActions: function() {
        
        this.action_applyChanges = new Ext.Action({
            text: this.app.i18n._('Apply changes'),
            iconCls: 'setup_action_save_config',
            scope: this,
            handler: this.onApplyChanges,
            disabled: true
        });
        
        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_applyChanges
            ]
        });
    }
}); 