/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

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
 * TODO         add cache backend config(s)
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.ConfigManagerPanel
 */
Tine.Setup.ConfigManagerPanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {

    /**
     * @property idPrefix DOM Id prefix
     * @type String
     */
    idPrefix: null,
    
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
     * session backend DOM Id prefix
     * 
     * @property sessionBackendIdPrefix
     * @type String
     */
    sessionBackendIdPrefix: null,
    
    /**
     * @private
     * field index counter
     */
    tabIndexCounter: 1,
    
    /**
     * @private
     */
    initComponent: function () {
        this.idPrefix                  = Ext.id();
        this.sessionBackendIdPrefix    = this.idPrefix + '-sessionBackend-';

        Tine.Setup.ConfigManagerPanel.superclass.initComponent.call(this);
    },
    
    /**
     * Change session card layout depending on selected combo box entry
     */
    onChangeSessionBackend: function () {
        this.changeCard(this.sessionBackendCombo, this.sessionBackendIdPrefix);
    },
    
    /**
     * Change default ports when database adapter gets changed
     */
    onChangeSqlBackend: function () {
        // @todo change default port
    },
    
    /**
     * @private
     */
    onRender: function (ct, position) {
        Tine.Setup.EmailPanel.superclass.onRender.call(this, ct, position);
        
        this.onChangeSessionBackend.defer(250, this);
    },
    
    /**
     * get tab index for field
     * 
     * @return {Integer}
     */
    getTabIndex: function () {
        return this.tabIndexCounter++;
    },
    
    /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function () {
        // common config for all combos in this setup
        var commonComboConfig = {
            xtype: 'combo',
            listWidth: 300,
            mode: 'local',
            forceSelection: true,
            allowEmpty: false,
            triggerAction: 'all',
            editable: false,
            tabIndex: this.getTabIndex
        };
        
        this.sessionBackendCombo = new Ext.form.ComboBox(Ext.applyIf({
            name: 'session_backend',
            fieldLabel: this.app.i18n._('Backend'),
            value: 'File',
            // TODO add redis again when we are ready
            store: [['File', this.app.i18n._('File')]/*, ['Redis','Redis'] */],
            listeners: {
                scope: this,
                change: this.onChangeSessionBackend,
                select: this.onChangeSessionBackend
            }
        }, commonComboConfig));
        
        this.sqlBackendCombo = new Ext.form.ComboBox(Ext.applyIf({
            name: 'database_adapter',
            fieldLabel: this.app.i18n._('Adapter'),
            value: 'pdo_mysql',
            store: [
                ['pdo_mysql', 'MySQL'],
                ['pdo_pgsql', 'PostgreSQL'],
                ['oracle', 'Oracle']
            ],
            listeners: {
                scope: this,
                change: this.onChangeSqlBackend,
                select: this.onChangeSqlBackend
            }
        }, commonComboConfig));
        
        return [{
            title: this.app.i18n._('Setup Authentication'),
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'setupuser_username',
                fieldLabel: this.app.i18n._('Username'),
                allowBlank: false,
                listeners: {
                    afterrender: function (field) {
                        field.focus(true, 500);
                    }
                }
            }, {
                name: 'setupuser_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password',
                allowBlank: false
            }] 
        }, {
            title: this.app.i18n._('Database'),
            id: 'setup-database-group',
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [ 
                this.sqlBackendCombo, 
                {
                    name: 'database_host',
                    fieldLabel: this.app.i18n._('Hostname'),
                    allowBlank: false
                }, {
                    xtype: 'numberfield',
                    name: 'database_port',
                    fieldLabel: this.app.i18n._('Port')
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
                }
            ]
        }, {
            title: this.app.i18n._('Logging'),
            id: 'setup-logger-group',
            checkboxToggle: true,
            collapsed: true,
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'logger_filename',
                fieldLabel: this.app.i18n._('Filename')
            }, Ext.applyIf({
                name: 'logger_priority',
                fieldLabel: this.app.i18n._('Priority'),
                store: [[0, 'Emergency'], [1, 'Alert'], [2, 'Critical'], [3, 'Error'], [4, 'Warning'], [5, 'Notice'], [6, 'Informational'], [7, 'Debug'], [8, 'Trace']]
            }, commonComboConfig)]
        }, {
            title: this.app.i18n._('Caching'),
            id: 'setup-caching-group',
            checkboxToggle: true,
            collapsed: true,
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'caching_path',
                fieldLabel: this.app.i18n._('Path')
            }, {
                xtype: 'numberfield',
                name: 'caching_lifetime',
                fieldLabel: this.app.i18n._('Lifetime (seconds)'),
                minValue: 0,
                maxValue: 3600
            }]
        }, {
            title: this.app.i18n._('Temporary files'),
            id: 'setup-tmpDir-group',
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'tmpdir',
                fieldLabel: this.app.i18n._('Temporary Files Path'),
                value: Tine.Setup.registry.get(this.registryKey).tmpdir
            }]
        }, {
            title: this.app.i18n._('Session'),
            id: 'setup-session-group',
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'session_lifetime',
                fieldLabel: this.app.i18n._('Lifetime (seconds)'),
                xtype: 'numberfield',
                value: 86400, 
                minValue: 0
            }, this.sessionBackendCombo, 
            {
                id: this.sessionBackendIdPrefix + 'CardLayout',
                xtype: 'panel',
                layout: 'card',
                activeItem: this.sessionBackendIdPrefix + 'File',
                border: false,
                width: '100%',
                defaults: {border: false},
                items: [{
                    // file config options
                    id: this.sessionBackendIdPrefix + 'File',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield',
                        tabIndex: this.getTabIndex
                    },
                    items: [{
                        name: 'session_path',
                        fieldLabel: this.app.i18n._('Path')
                    }]
                }, {
                    // redis config options
                    id: this.sessionBackendIdPrefix + 'Redis',
                    layout: 'form',
                    autoHeight: 'auto',
                    defaults: {
                        width: 300,
                        xtype: 'textfield',
                        tabIndex: this.getTabIndex
                    },
                    items: [{
                        name: 'session_host',
                        fieldLabel: this.app.i18n._('Hostname'),
                        value: 'localhost'
                    }, {
                        name: 'session_port',
                        fieldLabel: this.app.i18n._('Port'),
                        xtype: 'numberfield',
                        minValue: 0,
                        value: 6379
                    }]
                }]
            }]
        }, {
            // TODO this should be not saved in the config.inc.php
            title: this.app.i18n._('Filestore directory'),
            id: 'setup-filesDir-group',
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [{
                name: 'filesdir',
                fieldLabel: this.app.i18n._('Filestore Path'),
                value: Tine.Setup.registry.get(this.registryKey)['filesdir']
            }]
        }, {
            // TODO move map panel config to common config panel -> it should not be saved in config.inc.php
            title: this.app.i18n._('Addressbook Map panel'),
            defaults: {
                width: 300,
                tabIndex: this.getTabIndex
            },
            items: [
                Ext.applyIf({
                    name: 'mapPanel',
                    fieldLabel: this.app.i18n._('Map panel'),
                    value: Tine.Setup.registry.get(this.registryKey)['mapPanel'],
                    store: [[0, this.app.i18n._('disabled')], [1,this.app.i18n._('enabled')]]
                }, commonComboConfig)
            ] 
        }];
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function () {
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
        Ext.getCmp('setup-session-group').setIconClass(Tine.Setup.registry.get('checkSessionDir') ? 'setup_checks_success' : 'setup_checks_fail');
        Ext.getCmp('setup-filesDir-group').setIconClass(Tine.Setup.registry.get('checkFilesDir') ? 'setup_checks_success' : 'setup_checks_fail');
    },
    
    /**
     * @private
     */
    initActions: function () {
        this.action_downloadConfig = new Ext.Action({
            text: this.app.i18n._('Download config file'),
            iconCls: 'setup_action_download_config',
            scope: this,
            handler: this.onDownloadConfig
            //disabled: true
        });
        
        this.actionToolbarItems = [this.action_downloadConfig];
        
        Tine.Setup.ConfigManagerPanel.superclass.initActions.apply(this, arguments);
    },
    
    onDownloadConfig: function() {
        if (this.isValid()) {
            var configData = this.form2config();
            
            var downloader = new Ext.ux.file.Download({
                url: Tine.Tinebase.tineInit.requestUrl,
                params: {
                    method: 'Setup.downloadConfig',
                    data: Ext.encode(configData)
                }
            });
            downloader.start();
        } else {
            this.alertInvalidData();
        }
    }
});