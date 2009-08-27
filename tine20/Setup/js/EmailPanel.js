/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
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
 * TODO         add more fields
 * TODO         add dbmail fields
 * TODO         make loading from registry/db work
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.EmailPanel
 */
Tine.Setup.EmailPanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {
    
    /**
     * @private
     * panel cfg
     */
    saveMethod: 'Setup.saveEmailConfig',
    registryKey: 'emailData',
    defaults: {
        xtype: 'fieldset',
        autoHeight: 'auto',
        defaults: {width: 300},
        defaultType: 'textfield'
    },
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Setup.EmailPanel.superclass.initComponent.call(this);
    },
    
   /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function() {
        return [{
            title: this.app.i18n._('Imap'),
            id: 'setup-imap-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                xtype: 'combo',
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                value: 'standard',
                store: [['standard', this.app.i18n._('Standard IMAP')], ['dbmail', 'DBmail']],
                name: 'imap_backend',
                fieldLabel: this.app.i18n._('Backend')
            }, {
                name: 'imap_host',
                fieldLabel: this.app.i18n._('Hostname')
            }, {
                name: 'imap_user',
                fieldLabel: this.app.i18n._('Username')
            }, {
                name: 'imap_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }, {
                name: 'imap_port',
                fieldLabel: this.app.i18n._('Port')
            }, {
                fieldLabel: this.app.i18n._('Secure Connection'),
                name: 'imap_ssl',
                typeAhead     : false,
                triggerAction : 'all',
                lazyRender    : true,
                editable      : false,
                mode          : 'local',
                value: 'none',
                xtype: 'combo',
                listWidth: 300,
                store: [
                    ['none', this.app.i18n._('None')],
                    ['tls',  this.app.i18n._('TLS')],
                    ['ssl',  this.app.i18n._('SSL')]
                ]
            }, {
                xtype: 'combo',
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                value: 0,
                store: [[0, this.app.i18n._('No')], [1, this.app.i18n._('Yes')]],
                name: 'imap_useAsDefault',
                fieldLabel: this.app.i18n._('Use as default account')
            }, {
                name: 'imap_name',
                fieldLabel: this.app.i18n._('Default account name')
            }]
        }, {
            title: this.app.i18n._('Smtp'),
            id: 'setup-smtp-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'smtp_hostname',
                fieldLabel: this.app.i18n._('Hostname')
            }, {
                name: 'smtp_username',
                fieldLabel: this.app.i18n._('Username')
            }, {
                name: 'smtp_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }, {
                name: 'smtp_port',
                fieldLabel: this.app.i18n._('Port')
            }, {
                fieldLabel: this.app.i18n._('Secure Connection'),
                name: 'smtp_ssl',
                typeAhead     : false,
                triggerAction : 'all',
                lazyRender    : true,
                editable      : false,
                mode          : 'local',
                value: 'none',
                xtype: 'combo',
                listWidth: 300,
                store: [
                    ['none', this.app.i18n._('None')],
                    ['tls',  this.app.i18n._('TLS')],
                    ['ssl',  this.app.i18n._('SSL')]
                ]
            }, {
                fieldLabel: this.app.i18n._('Authentication'),
                name: 'smtp_auth',
                typeAhead     : false,
                triggerAction : 'all',
                lazyRender    : true,
                editable      : false,
                mode          : 'local',
                xtype: 'combo',
                listWidth: 300,
                value: 'login',
                store: [
                    ['none',    this.app.i18n._('None')],
                    ['login',   this.app.i18n._('Login')],
                    ['plain',   this.app.i18n._('Plain')]
                ]
            }]
        }];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_saveConfig.setDisabled(!this.isValid());
    }
});
