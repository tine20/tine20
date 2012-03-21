/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.ns('Tine', 'Tine.Setup');
 
/**
 * Setup Email Config Manager
 * 
 * @namespace   Tine.Setup
 * @class       Tine.Setup.EmailPanel
 * @extends     Tine.Tinebase.widgets.form.ConfigPanel
 * 
 * <p>Email Config Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.EmailPanel
 */
Tine.Setup.EmailPanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {
    
    /**
     * @property idPrefix DOM Id prefix
     * @type String
     */
    idPrefix: null,
    
    /**
     * imapBackend DOM Id prefix
     * 
     * @property imapBackendIdPrefix
     * @type String
     */
    imapBackendIdPrefix: null,
    
    /**
     * combo box containing the imap backend selection
     * 
     * @property imapBackendCombo
     * @type Ext.form.ComboBox 
     */
    imapBackendCombo: null,

    /**
     * smtpBackend DOM Id prefix
     * 
     * @property smtpBackendIdPrefix
     * @type String
     */
    smtpBackendIdPrefix: null,
    
    /**
     * combo box containing the smtp backend selection
     * 
     * @property smtpBackendCombo
     * @type Ext.form.ComboBox 
     */
    smtpBackendCombo: null,

    /**
     * show type before db settings
     * @cfg {Boolean} showType
     */
    showType: false,
    
    /**
     * @private
     * panel cfg
     */
    saveMethod: 'Setup.saveEmailConfig',
    registryKey: 'emailData',
    
    defaults: {
        xtype: 'fieldset',
        autoHeight: 'auto',
        defaults: {width: 300}
    },
    
    /**
     * @private
     */
    initComponent: function () {
        this.idPrefix                  = Ext.id();
        this.imapBackendIdPrefix       = this.idPrefix + '-imapBackend-';
        this.smtpBackendIdPrefix       = this.idPrefix + '-smtpBackend-';

        Tine.Setup.EmailPanel.superclass.initComponent.call(this);
    },
    
    /**
     * Change IMAP card layout depending on selected combo box entry
     */
    onChangeImapBackend: function () {
        this.changeCard(this.imapBackendCombo, this.imapBackendIdPrefix);
    },

    /**
     * Change SMTP card layout depending on selected combo box entry
     */
    onChangeSmtpBackend: function () {
        this.changeCard(this.smtpBackendCombo, this.smtpBackendIdPrefix);
    },

    /**
     * @private
     */
    onRender: function (ct, position) {
        Tine.Setup.EmailPanel.superclass.onRender.call(this, ct, position);
        
        this.onChangeImapBackend.defer(250, this);
        this.onChangeSmtpBackend.defer(250, this);
    },
    
   /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function () {
        
        var backendComboConfig = {
            xtype: 'combo',
            width: 300,
            listWidth: 300,
            mode: 'local',
            forceSelection: true,
            allowEmpty: false,
            triggerAction: 'all',
            editable: false,
            value: 'standard',
            fieldLabel: this.app.i18n._('Backend')
        };
        
        // imap combo
        backendComboConfig.store = [['standard', this.app.i18n._('Standard IMAP')], ['dbmail', 'DBmail  MySQL'], ['ldap_imap', 'DBmail Ldap'], ['cyrus', 'Cyrus'], ['dovecot_imap', 'Dovecot MySQL']];
        backendComboConfig.name = 'imap_backend';
        backendComboConfig.listeners = {
            scope: this,
            change: this.onChangeImapBackend,
            select: this.onChangeImapBackend
        };
        this.imapBackendCombo = new Ext.form.ComboBox(backendComboConfig);
        
        // smtp combo
        backendComboConfig.store = [['standard', this.app.i18n._('Standard SMTP')], ['postfix', 'Postfix MySQL'], ['Ldapsmtpmail', 'Ldap (only mail attribute)'], ['ldapSmtp', 'Postfix Ldap (dbmail schema)'], ['ldapSmtpQmail', 'Postfix Ldap (qmail schema)']];
        backendComboConfig.name = 'smtp_backend';
        backendComboConfig.listeners = {
            scope: this,
            change: this.onChangeSmtpBackend,
            select: this.onChangeSmtpBackend
        };
        this.smtpBackendCombo = new Ext.form.ComboBox(backendComboConfig);

        // use backend combo config as common combo config
        var commonComboConfig = backendComboConfig;
        delete commonComboConfig.listeners;
        
        return [{
            title: this.app.i18n._('Imap'),
            id: 'setup-imap-group',
            checkboxToggle: true,
            collapsed: true,
            items: [
                this.imapBackendCombo, 
                {
                    name: 'imap_host',
                    fieldLabel: this.app.i18n._('Hostname'),
                    xtype: 'textfield'
                }, /*{
                    name: 'imap_user',
                    fieldLabel: this.app.i18n._('Username'),
                    xtype: 'textfield'
                }, {
                    name: 'imap_password',
                    fieldLabel: this.app.i18n._('Password'),
                    xtype: 'textfield',
                    inputType: 'password'
                }, */{
                    name: 'imap_port',
                    fieldLabel: this.app.i18n._('Port'),
                    xtype: 'numberfield'
                }, 
                Ext.applyIf({
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'imap_ssl',
                    value: 'none',
                    store: [['none', this.app.i18n._('None')], ['tls',  this.app.i18n._('TLS')], ['ssl',  this.app.i18n._('SSL')]]
                }, commonComboConfig), 
                Ext.applyIf({
                    name: 'imap_useSystemAccount',
                    fieldLabel: this.app.i18n._('Use system account'),
                    store: [[0, this.app.i18n._('No')], [1, this.app.i18n._('Yes')]],
                    value: 0
                }, commonComboConfig), 
                {
                    name: 'imap_domain',
                    fieldLabel: this.app.i18n._('Append domain to login name'),
                    xtype: 'textfield'
                }, {
                    id: this.imapBackendIdPrefix + 'CardLayout',
                    layout: 'card',
                    activeItem: this.imapBackendIdPrefix + 'standard',
                    border: false,
                    width: '100%',
                    defaults: {
                        border: false
                    },
                    items: [{
                        // nothing in here yet
                        id: this.imapBackendIdPrefix + 'standard',
                        layout: 'form',
                        items: []
                    }, {
                        // dbmail config options
                        id: this.imapBackendIdPrefix + 'dbmail',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: this.getDbConfigFields('imap', 'dbmail')
                    }, {
                        // nothing in here yet
                        id: this.imapBackendIdPrefix + 'ldap_imap',
                        layout: 'form',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: []
                    }, {
                        // cyrus config options
                        id: this.imapBackendIdPrefix + 'cyrus',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: [{
                            name: 'imap_cyrus_admin',
                            fieldLabel: this.app.i18n._('Cyrus Admin')
                        }, {
                            name: 'imap_cyrus_password',
                            fieldLabel: this.app.i18n._('Cyrus Admin Password'),
                            inputType: 'password'
                        }]
                    }, {
                        // dovecot config options
                        id: this.imapBackendIdPrefix + 'dovecot_imap',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: this.getDbConfigFields('imap', 'dovecot').concat(this.getDovecotExtraConfig('imap'))
                    }]
                }
            ]
        }, {
            title: this.app.i18n._('Smtp'),
            id: 'setup-smtp-group',
            checkboxToggle:true,
            collapsed: true,
            items: [
                this.smtpBackendCombo,
                {
                    name: 'smtp_hostname',
                    fieldLabel: this.app.i18n._('Hostname'),
                    xtype: 'textfield'
                }, {
                    name: 'smtp_port',
                    fieldLabel: this.app.i18n._('Port'),
                    xtype: 'numberfield'
                }, 
                Ext.applyIf({
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'smtp_ssl',
                    value: 'none',
                    store: [['none', this.app.i18n._('None')], ['tls', this.app.i18n._('TLS')], ['ssl', this.app.i18n._('SSL')]]
                }, commonComboConfig), 
                Ext.applyIf({
                    fieldLabel: this.app.i18n._('Authentication'),
                    name: 'smtp_auth',
                    value: 'login',
                    store: [['none', this.app.i18n._('None')], ['login', this.app.i18n._('Login')], ['plain', this.app.i18n._('Plain')]]
                }, commonComboConfig), 
                {
                    name: 'smtp_primarydomain',
                    fieldLabel: this.app.i18n._('Primary Domain'),
                    xtype: 'textfield'
                }, {
                    name: 'smtp_secondarydomains',
                    fieldLabel: this.app.i18n._('Secondary Domains (comma separated)'),
                    xtype: 'textfield'
                }, {
                    name: 'smtp_from',
                    fieldLabel: this.app.i18n._('Notifications service address'),
                    xtype: 'textfield'
                }, {
                    name: 'smtp_username',
                    fieldLabel: this.app.i18n._('Notification Username'),
                    xtype: 'textfield'
                }, {
                    name: 'smtp_password',
                    fieldLabel: this.app.i18n._('Notification Password'),
                    inputType: 'password',
                    xtype: 'textfield'
                }, {
                    name: 'smtp_name',
                    fieldLabel: this.app.i18n._('Notifications local client (hostname or IP address)'),
                    xtype: 'textfield',
                    value: 'localhost'
                }, {
                    id: this.smtpBackendIdPrefix + 'CardLayout',
                    layout: 'card',
                    activeItem: this.smtpBackendIdPrefix + 'standard',
                    border: false,
                    width: '100%',
                    defaults: {
                        border: false
                    },
                    items: [{
                        // nothing in here yet
                        id: this.smtpBackendIdPrefix + 'standard',
                        layout: 'form',
                        items: []
                    }, {
                        // nothing in here yet
                        id: this.smtpBackendIdPrefix + 'ldap_smtp_mail',
                        layout: 'form',
                        items: []
                    },{
                        // postfix config options
                        id: this.smtpBackendIdPrefix + 'postfix',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: this.getDbConfigFields('smtp', 'postfix')
                    }, {
                        // postfix config options
                        id: this.smtpBackendIdPrefix + 'ldap_smtp',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: []
                    }, {
                        // postfix ldap qmail user schema config options
                        id: this.smtpBackendIdPrefix + 'ldap_smtp_qmail',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 300,
                            xtype: 'textfield'
                        },
                        items: []
                    }]
                }
             ]
        }, {
            title: this.app.i18n._('SIEVE'),
            id: 'setup-sieve-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'sieve_hostname',
                fieldLabel: this.app.i18n._('Hostname'),
                xtype: 'textfield'
            }, {
                name: 'sieve_port',
                fieldLabel: this.app.i18n._('Port'),
                xtype: 'numberfield'
            }, 
            Ext.applyIf({
                fieldLabel: this.app.i18n._('Secure Connection'),
                name: 'sieve_ssl',
                value: 'none',
                store: [['none', this.app.i18n._('None')], ['tls', this.app.i18n._('TLS')], ['ssl', this.app.i18n._('SSL')]]
            }, commonComboConfig)]
        }];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function () {
        this.action_saveConfig.setDisabled(!this.isValid());
    },
    
    /**
     * get db config fields
     * 
     * @param {String} type1 (imap, smtp)
     * @param {String} type2 (dbmail, postfix, ...)
     * @return {Array}
     */
    getDbConfigFields: function (type1, type2) {
        var typeString = (this.showType) ? (Ext.util.Format.capitalize(type2) + ' ') : '';
        
        return [{
            name: type1 + '_' + type2 + '_host',
            fieldLabel: typeString + this.app.i18n._('MySql Hostname')
        }, {
            name: type1 + '_' + type2 + '_dbname',
            fieldLabel: typeString + this.app.i18n._('MySql Database')
        }, {
            name: type1 + '_' + type2 + '_username',
            fieldLabel: typeString + this.app.i18n._('MySql User')
        }, {
            name: type1 + '_' + type2 + '_password',
            fieldLabel: typeString + this.app.i18n._('MySql Password'),
            inputType: 'password'
        }, {
            name: type1 + '_' + type2 + '_port',
            fieldLabel: typeString + this.app.i18n._('MySql Port'),
            value: 3306
        }];
    },
    
    /**
     * get db config fields
     * 
     * @param {String} type1 (imap, smtp)
     * @return {Array}
     */
    getDovecotExtraConfig: function (type1) {
        var typeString = (this.showType) ? 'Dovecot ' : '';
        
        return [{
            name: type1 + '_dovecot_uid',
            fieldLabel: typeString + this.app.i18n._('User or UID')
        }, {
            name: type1 + '_dovecot_gid',
            fieldLabel: typeString + this.app.i18n._('Group or GID')
        }, {
            name: type1 + '_dovecot_home',
            fieldLabel: typeString + this.app.i18n._('Home Template')
        }, {
            fieldLabel: typeString + this.app.i18n._('Password Scheme'),
            name: type1 + '_dovecot_scheme',
            typeAhead     : false,
            triggerAction : 'all',
            lazyRender    : true,
            editable      : false,
            mode          : 'local',
            xtype: 'combo',
            listWidth: 300,
            value: 'PLAIN-MD5',
            store: [
                ['PLAIN-MD5',      this.app.i18n._('PLAIN-MD5')],
                ['MD5-CRYPT',    this.app.i18n._('MD5-CRYPT')],
                ['SHA',           this.app.i18n._('SHA1')],
                ['SHA256',       this.app.i18n._('SHA256')],
                ['SSHA256',      this.app.i18n._('SSHA256')],
                ['SHA512',       this.app.i18n._('SHA512')],
                ['SSHA512',       this.app.i18n._('SSHA512')],
                ['PLAIN',        this.app.i18n._('PLAIN')]
            ]
        }];
    }
});
