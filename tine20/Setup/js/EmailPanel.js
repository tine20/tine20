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
                name: 'imap_name',
                fieldLabel: this.app.i18n._('Default account name')
            }]
    //'useAsDefault' => true,
    //'secure_connection' => 'tls',
        }, {
            title: this.app.i18n._('Smtp'),
            id: 'setup-smtp-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'smtp_host',
                fieldLabel: this.app.i18n._('Hostname')
            }, {
                name: 'smtp_user',
                fieldLabel: this.app.i18n._('Username')
            }, {
                name: 'smtp_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }, {
                name: 'smtp_port',
                fieldLabel: this.app.i18n._('Port')
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
