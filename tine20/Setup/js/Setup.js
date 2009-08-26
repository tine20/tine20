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
 * @namespace   Tine.Setup
 * @class       Tine.Setup.TreePanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>Setup TreePanel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.TreePanel
 */
Tine.Setup.TreePanel = Ext.extend(Ext.tree.TreePanel, {
    
    /**
     * tree panel cfg
     * 
     * @private
     */
    border: false,
    rootVisible: false, 
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Setup');
        
        var testsFailed   = !Tine.Setup.registry.get('setupChecks').success;
        var configMissing = !Tine.Setup.registry.get('configExists');
        var dbMissing     = !Tine.Setup.registry.get('checkDB');
        var setupRequired = Tine.Setup.registry.get('setupRequired');
        
        console.log(Tine.Setup.registry);
        
        this.root = {
            id: '/',
            children: [{
                text: this.app.i18n._('Setup Checks'),
                iconCls: testsFailed ? 'setup_checks_fail' : 'setup_checks_success',
                id: 'EnvCheckGridPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Config Manager'),
                iconCls: 'setup_config_manager',
                disabled: testsFailed,
                id: 'ConfigManagerPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Authentication/Accounts'),
                iconCls: 'setup_config_manager',
                disabled: testsFailed || configMissing || dbMissing,
                id: 'AuthenticationPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Application Manager'),
                iconCls: 'setup_application_manager',
                disabled: testsFailed || configMissing || dbMissing || setupRequired,
                id: 'ApplicationGridPanel',
                leaf: true
            }]
        };
        
        Tine.Setup.TreePanel.superclass.initComponent.call(this);
        
        this.on('click', this.onNodeClick, this);
    },
    
    /**
     * @private
     */
    onNodeClick: function(node) {
        if (! node.disabled) {
            this.app.getMainScreen().activePanel = node.id;
            this.app.getMainScreen().show();
        } else {
            return false;
        }
        
    },
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Setup.TreePanel.superclass.afterRender.call(this);
        
        var activeType = '';
        var contentTypes = this.getRootNode().childNodes;
        for (var i=0; i<contentTypes.length; i++) {
            if(! contentTypes[i].disabled) {
                activeType = contentTypes[i];
            }
        }
        
        activeType.select();
        this.app.getMainScreen().activePanel = activeType.id;
        
        Tine.Setup.registry.on('replace', this.applyRegistryState, this);
    },
    
    /**
     * apply registry state
     */
    applyRegistryState: function() {
        var setupChecks  = Tine.Setup.registry.get('setupChecks').success;
        var configExists = Tine.Setup.registry.get('configExists');
        var checkDB      = Tine.Setup.registry.get('checkDB');
        var setupRequired = Tine.Setup.registry.get('setupRequired');
        
        console.log(Tine.Setup.registry);
        
        var envNode = this.getNodeById('EnvCheckGridPanel');
        var envIconCls = setupChecks ? 'setup_checks_success' : 'setup_checks_fail';
        if (envNode.rendered) {
            var envIconEl = Ext.get(envNode.ui.iconNode);
            envIconEl.removeClass('setup_checks_success');
            envIconEl.removeClass('setup_checks_fail');
            envIconEl.addClass(envIconCls);
        } else {
            envNode.iconCls = envIconCls;
        }
        
        this.getNodeById('ConfigManagerPanel')[setupChecks ? 'enable': 'disable']();
        this.getNodeById('AuthenticationPanel')[setupChecks && configExists && checkDB ? 'enable': 'disable']();
        this.getNodeById('ApplicationGridPanel')[setupChecks && configExists && checkDB && !setupRequired ? 'enable': 'disable']();
    }
});

Ext.ns('Tine', 'Tine.Setup', 'Tine.Setup.Model');

/**
 * @namespace   Tine.Setup.Model
 * @class       Tine.Setup.Model.Application
 * @extends     Tine.Tinebase.data.Record
 * 
 * Application Record Definition
 */ 
Tine.Setup.Model.Application = Tine.Tinebase.data.Record.create([
    { name: 'id'              },
    { name: 'name'            },
    { name: 'status'          },
    { name: 'order'           },
    { name: 'version'         },
    { name: 'current_version' },
    { name: 'install_status'  },
    { name: 'depends'         }
], {
    appName: 'Setup',
    modelName: 'Application',
    idProperty: 'name',
    titleProperty: 'name',
    // ngettext('Application', 'Applications', n); gettext('Application');
    recordName: 'Application',
    recordsName: 'Applications'
});

/**
 * @namespace   Tine.Setup
 * @class       Tine.Setup.ApplicationBackend
 * @extends     Tine.Tinebase.data.RecordProxy
 * 
 * default application backend
 */ 
Tine.Setup.ApplicationBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Setup',
    modelName: 'Application',
    recordClass: Tine.Setup.Model.Application
});

/**
 * @namespace   Tine.Setup.Model
 * @class       Tine.Setup.Model.EnvCheck
 * @extends     Ext.data.Record
 * 
 * env check Record Definition
 */ 
Tine.Setup.Model.EnvCheck = Ext.data.Record.create([
    {name: 'key'},
    {name: 'value'},
    {name: 'message'}
]);
