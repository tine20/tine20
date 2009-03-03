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
 
Tine.Setup.ConfigManagerGridPanel = Ext.extend(Ext.FormPanel, {
    border: false,
    bodyStyle:'padding:5px 5px 0',
    labelAlign: 'left',
    labelSeparator: ':',
    labelWidth: 150,
    defaults: {
        xtype: 'fieldset',
        autoHeight: 'auto',
        defaults: {width: 210},
        defaultType: 'textfield'
    },
    
    
    // fake a store to satisfy grid panel
    store: {
        load: function() {
        
        }
    },
    
    
    initComponent: function() {
        this.initActions();
        
        this.items = this.getFormItems();
        /*
        if (Tine.Setup.registry.get('configExists')) {
            if (Tine.Setup.registry.get('checkDB')) {
                this.html = 'Config file found! Database is Accessable!';
            } else {
                this.html = 'A Config file exists, but the database could not be accessed. Please check the config file!';
            }
        } else {
            this.html = 'No config file found. You need to copy the config.inc.php.dist to config.inc.php and adopt its contents';
        }
        */
        Tine.Setup.ConfigManagerGridPanel.superclass.initComponent.call(this);
    },
    
    getFormItems: function() {
        return [{
            title: this.app.i18n._('Setup Authentication'),
            items: [{
                name: 'setupuser_username',
                fieldLabel: this.app.i18n._('Username')
            }, {
                name: 'setupuser_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }] 
        }, {
            title: this.app.i18n._('Database'),
            iconCls: 'setup_checks_fail',
            items: [{
                name: 'database_adapter',
                fieldLabel: this.app.i18n._('Adapter'),
                value: 'pdo_mysql',
                disabled: true
            }, {
                name: 'database_host',
                fieldLabel: this.app.i18n._('Hostname')
            }, {
                name: 'database_dbname',
                fieldLabel: this.app.i18n._('Database')
            }, {
                name: 'database_username',
                fieldLabel: this.app.i18n._('User')
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
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'logger_filename',
                fieldLabel: this.app.i18n._('Filename')
            }, {
                xtype: 'combo',
                width: 193, // late rendering bug
                listWidth: 210,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                store: [[0, 'Emergency'], [1,'Alert'], [2, 'Critical'], [3, 'Error'], [4, 'Warning'], [5, 'Notice'], [6, 'Informational'], [7, 'Debug']],
                name: 'logger_priority',
                fieldLabel: this.app.i18n._('Priority')
            }]
        }, {
            title: this.app.i18n._('Caching'),
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
                maxValue: 60
            }]
        }];
    },
    
    initActions: function() {
        this.action_saveConfig = new Ext.Action({
            text: this.app.i18n._('Save config'),
            iconCls: 'setup_action_save_config',
            disabled: true
        });
        
        this.action_downloadConfig = new Ext.Action({
            text: this.app.i18n._('Download config file'),
            iconCls: 'setup_action_download_config',
            disabled: true
        });
        
        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_saveConfig,
                this.action_downloadConfig
            ]
        });
    }
}); 