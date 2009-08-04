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
            var configData = this.form2config();
            
            this.loadMask.show();
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Setup.saveConfig',
                    data: Ext.util.JSON.encode(configData)
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
    onChangeAuthProvider: function(field, record) {
        var authProvider = field.getValue();
        var cardLayout = Ext.getCmp(this.authProviderIdPrefix + 'CardLayout').getLayout();
        cardLayout.setActiveItem(this.authProviderIdPrefix + authProvider);
    },
    
    /**
     * Change card layout depending on selected combo box entry
     */
    onChangeAccountsStorage: function(field, record) {
        var AccountsStorage = field.getValue();
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
        //var formData = this.config2form.defer(250, this, [Tine.Setup.registry.get('configData')]);
        
        //Tine.Setup.registry.on('replace', this.applyRegistryState, this);
        this.loadMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Transfering Configuration...')});
    },
    
    /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function() {
        return [ {
            xtype:'fieldset',
            collapsible: false,
            autoHeight:true,
            title: this.app.i18n._('Authentication provider'),
            items: [{
                xtype: 'combo',
                width: 300,
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [['sql', 'Sql'], ['ldap','Ldap']],
                name: 'authentication[backend]',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'sql',
                listeners: {
                    scope: this,
                    change: this.onChangeAuthProvider,
                    select: this.onChangeAuthProvider
                }
            }, {
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
                        name: 'authentication[sql][loginName]',
                        fieldLabel: this.app.i18n._('Login name')
                    }, {
                        name: 'authentication[sql][password]',
                        fieldLabel: this.app.i18n._('Password')
                    }, {
                        name: 'authentication[sql][passwordConfirmation]',
                        fieldLabel: this.app.i18n._('Password confirmation')
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
                        name: 'authentication[ldap][host]',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'authentication[ldap][username]',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'authentication[ldap][password]',
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
                        name: 'authentication[ldap][bindRequiresDn]',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'authentication[ldap][baseDn]',
                        fieldLabel: this.app.i18n._('Base DN')
                    }, {
                        name: 'authentication[ldap][accountCanonicalForm]',
                        fieldLabel: this.app.i18n._('Account canonical form')
                    }]
                }]
            } ]
          }, {
            xtype:'fieldset',
            collapsible: false,
            autoHeight:true,
            title: this.app.i18n._('Accounts storage'),
            items: [{
                xtype: 'combo',
                width: 300,
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [['sql', 'Sql'], ['ldap','Ldap']],
                name: 'accounts[backend]',
                fieldLabel: this.app.i18n._('Backend'),
                value: 'sql',
                listeners: {
                    scope: this,
                    change: this.onChangeAccountsStorage,
                    select: this.onChangeAccountsStorage
                }
            }, {
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
                    },
                    items: [ {
                        inputType: 'text',
                        name: 'accounts[sql][loginName]',
                        fieldLabel: this.app.i18n._('Login name')
                    }, {
                        name: 'accounts[sql][password]',
                        fieldLabel: this.app.i18n._('Password')
                    }, {
                        name: 'accounts[sql][passwordConfirmation]',
                        fieldLabel: this.app.i18n._('Password confirmation')
                    } ]
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
                        name: 'accounts[ldap][host]',
                        fieldLabel: this.app.i18n._('Host')
                    },
                    {
                        inputType: 'text',
                        name: 'accounts[ldap][username]',
                        fieldLabel: this.app.i18n._('Login name')
                    },{
                        name: 'accounts[ldap][password]',
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
                        name: 'accounts[ldap][bindRequiresDn]',
                        fieldLabel: this.app.i18n._('Bind requires DN'),
                        value: '1'
                    }, {
                        name: 'accounts[ldap][userDn]',
                        fieldLabel: this.app.i18n._('User DN')
                    }, {
                        name: 'accounts[ldap][groupsDn]',
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
                        store: [['SHA', 'SHA'], ['MD5','MD5']],
                        name: 'accounts[ldap][pwEncType]',
                        fieldLabel: this.app.i18n._('Password encoding'),
                        value: 'SHA'
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
                        name: 'accounts[ldap][useRfc2307bis]',
                        fieldLabel: this.app.i18n._('Use Rfc 2307 bis'),
                        value: '0'
                    }, {
                        name: 'accounts[ldap][minUserId]',
                        fieldLabel: this.app.i18n._('Min User Id')
                    }, {
                        name: 'accounts[ldap][maxUserId]',
                        fieldLabel: this.app.i18n._('Max User Id')
                    }, {
                        name: 'accounts[ldap][minGroupId]',
                        fieldLabel: this.app.i18n._('Min Group Id')
                    }, {
                        name: 'accounts[ldap][maxGroupId]',
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
                        
                        // is group active?
                        keyGroup = Ext.getCmp('setup-' + keyPart + '-group');
                        if (keyGroup && keyGroup.checkboxToggle) {
                            dataPath[keyPart].active = !keyGroup.collapsed;
                        }
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
        
        var keyGroup;
        for (key in configData) {
            if(typeof configData[key] == 'object') {
                this.config2form(configData[key], formData, currKey ? currKey + '_' + key : key);
            } else {
                formData[currKey + '_' + key] = configData[key];
                
                // activate group?
                keyGroup = Ext.getCmp('setup-' + currKey + '-group');
                if (keyGroup && key == 'active' && configData.active) {
                    keyGroup.expand();
                }
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
        this.action_saveConfig.setDisabled(!Tine.Setup.registry.get('configWritable'));
        Ext.getCmp('setup-database-group').setIconClass(Tine.Setup.registry.get('checkDB') ? 'setup_checks_success' : 'setup_checks_fail');
    },
    
    /**
     * @private
     */
    initActions: function() {
        
        this.action_applyConfig = new Ext.Action({
            text: this.app.i18n._('Apply changes'),
            iconCls: 'setup_action_save_config',
            scope: this,
            handler: this.onApplyChanges,
            disabled: true
        });
        
        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_applyConfig
            ]
        });
    }
}); 