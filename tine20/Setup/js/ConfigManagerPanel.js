/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

/**
 * Setup Configuration Manager
 * 
 * @namespace   Tine.Setup
 * @class       Tine.Setup.ConfigManagerPanel
 * @extends     Tine.Tinebase.widgets.form.ConfigPanel
 * 
 * <p>Configuration Panel</p>
 * <p><pre>
 * TODO         make tabindex work correctly (there is some problem when tab is pressed in the setup username field, it takes 6x to reach the next field)
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.ConfigManagerPanel
 */
Tine.Setup.ConfigManagerPanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {

    /**
     * @private
     * panel cfg
     */
    saveMethod: 'Setup.saveConfig',
    registryKey: 'configData',
    defaults: {
        xtype: 'fieldset',
        autoHeight: 'auto',
        defaults: {width: 300},
        defaultType: 'textfield'
    },
    
    /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function() {
        return [/*{
            xtype: 'panel',
            title: this.app.i18n._('Informations'),
            iconCls: 'setup_info',
            html: ''
        },*/ {
            title: this.app.i18n._('Setup Authentication'),
            items: [{
                name: 'setupuser_username',
                fieldLabel: this.app.i18n._('Username'),
                allowBlank: false,
                listeners: {
                    afterrender: function(field) {
                        field.focus(true, 500);
                    }
                },
                tabIndex: 1
            }, {
                name: 'setupuser_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password',
                allowBlank: false,
                tabIndex: 2
            }] 
        }, {
            title: this.app.i18n._('Database'),
            id: 'setup-database-group',
            items: [{
                name: 'database_adapter',
                fieldLabel: this.app.i18n._('Adapter'),
                value: 'pdo_mysql',
                disabled: true
            }, {
                name: 'database_host',
                fieldLabel: this.app.i18n._('Hostname'),
                allowBlank: false,
                tabIndex: 3
            }, {
                name: 'database_port',
                fieldLabel: this.app.i18n._('Port'),
                allowBlank: false,
                xtype: 'numberfield',
                tabIndex: 4
            }, {
                name: 'database_dbname',
                fieldLabel: this.app.i18n._('Database'),
                allowBlank: false,
                tabIndex: 5
            }, {
                name: 'database_username',
                fieldLabel: this.app.i18n._('User'),
                allowBlank: false,
                tabIndex: 6
            }, {
                name: 'database_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password',
                tabIndex: 7
            }, {
                name: 'database_tableprefix',
                fieldLabel: this.app.i18n._('Prefix'),
                tabIndex: 8,
                maxLength: 9
            }]
        }, {
            title: this.app.i18n._('Logging'),
            id: 'setup-logger-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'logger_filename',
                fieldLabel: this.app.i18n._('Filename'),
                tabIndex: 9
            }, {
                xtype: 'combo',
                width: 283, // late rendering bug
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [[0, 'Emergency'], [1,'Alert'], [2, 'Critical'], [3, 'Error'], [4, 'Warning'], [5, 'Notice'], [6, 'Informational'], [7, 'Debug']],
                name: 'logger_priority',
                fieldLabel: this.app.i18n._('Priority'),
                tabIndex: 10

            }]
        }, {
            title: this.app.i18n._('Caching'),
            id: 'setup-caching-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'caching_path',
                fieldLabel: this.app.i18n._('Path'),
                tabIndex: 11
            }, {
                name: 'caching_lifetime',
                fieldLabel: this.app.i18n._('Lifetime (seconds)'),
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 3600,
                tabIndex: 12
            }]
        }, {
            title: this.app.i18n._('Temporary files'),
            id: 'setup-tmpDir-group',
            items: [{
                name: 'tmpdir',
                value: Tine.Setup.registry.get(this.registryKey).tmpdir,
                fieldLabel: this.app.i18n._('Temporary Files Path'),
                tabIndex: 13
            }]
        }, {
            title: this.app.i18n._('Session files'),
            id: 'setup-sessionDir-group',
            items: [{
                name: 'sessiondir',
                value: Tine.Setup.registry.get(this.registryKey)['sessiondir'],
                fieldLabel: this.app.i18n._('Session Files Path'),
                tabIndex: 14
            }]
        }];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_saveConfig.setDisabled(! Tine.Setup.registry.get('configWritable'));
        if (! Tine.Setup.registry.get('configWritable')) {
            this.action_saveConfig.setText(this.app.i18n._('Config file is not writable'));
        } else {
            this.action_saveConfig.setText(this.app.i18n._('Save config'));
        }
        Ext.getCmp('setup-database-group').setIconClass(Tine.Setup.registry.get('checkDB') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-logger-group').setIconClass(Tine.Setup.registry.get('checkLogger') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-caching-group').setIconClass(Tine.Setup.registry.get('checkCaching') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-tmpDir-group').setIconClass(Tine.Setup.registry.get('checkTmpDir') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-sessionDir-group').setIconClass(Tine.Setup.registry.get('checkSessionDir') ? 'setup_checks_success' : 'setup_checks_fail');
    },
    
    /**
     * @private
     */
    initActions: function() {       
        
        this.action_downloadConfig = new Ext.Action({
            text: this.app.i18n._('Download config file'),
            iconCls: 'setup_action_download_config',
            disabled: true
        });
        
        this.actionToolbarItems = [this.action_downloadConfig];
        
        Tine.Setup.ConfigManagerPanel.superclass.initActions.apply(this, arguments);
    }
});
