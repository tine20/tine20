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
                allowBlank: false
            }, {
                name: 'setupuser_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password',
                allowBlank: false
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
                allowBlank: false
            }, {
                name: 'database_port',
                fieldLabel: this.app.i18n._('Port'),
                allowBlank: false,
                xtype: 'numberfield'
            }, {
                name: 'database_dbname',
                fieldLabel: this.app.i18n._('Database'),
                allowBlank: false
            }, {
                name: 'database_username',
                fieldLabel: this.app.i18n._('User'),
                allowBlank: false
            }, {
                name: 'database_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }, {
                name: 'database_tableprefix',
                fieldLabel: this.app.i18n._('Prefix')
            }]
        }, {
            title: this.app.i18n._('Logging'),
            id: 'setup-logger-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'logger_filename',
                fieldLabel: this.app.i18n._('Filename')
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
                fieldLabel: this.app.i18n._('Priority')

            }]
        }, {
            title: this.app.i18n._('Caching'),
            id: 'setup-caching-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'caching_path',
                fieldLabel: this.app.i18n._('Path')
            }, {
                name: 'caching_lifetime',
                fieldLabel: this.app.i18n._('Lifetime (seconds)'),
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 3600
            }]
        }, {
            title: this.app.i18n._('Other'),
            items: [{
                name: 'tmpdir',
                fieldLabel: this.app.i18n._('Temporary Files Path')
            }]
        }];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_saveConfig.setDisabled(!Tine.Setup.registry.get('configWritable'));
        Ext.getCmp('setup-database-group').setIconClass(Tine.Setup.registry.get('checkDB') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-logger-group').setIconClass(Tine.Setup.registry.get('checkLogger') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-caching-group').setIconClass(Tine.Setup.registry.get('checkCaching') ? 'setup_checks_success' : 'setup_checks_fail');
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
