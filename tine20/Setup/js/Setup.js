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
        
        var termsFailed   = !Tine.Setup.registry.get('AcceptedTermsVersion') || Tine.Setup.registry.get('AcceptedTermsVersion') < Tine.Setup.CurrentTermsVersion;
        var testsFailed   = !Tine.Setup.registry.get('setupChecks').success;
        var configMissing = !Tine.Setup.registry.get('configExists');
        var dbMissing     = !Tine.Setup.registry.get('checkDB');
        var setupRequired = Tine.Setup.registry.get('setupRequired');
        
        this.root = {
            id: '/',
            children: [{
                text: this.app.i18n._('Terms and Conditions'),
                iconCls: termsFailed ? 'setup_checks_fail' : 'setup_checks_success',
                id: 'TermsPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Setup Checks'),
                iconCls: testsFailed ? 'setup_checks_fail' : 'setup_checks_success',
                disabled: termsFailed,
                id: 'EnvCheckGridPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Config Manager'),
                iconCls: 'setup_config_manager',
                disabled: termsFailed || testsFailed,
                id: 'ConfigManagerPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Authentication/Accounts'),
                iconCls: 'setup_authentication_manager',
                disabled: termsFailed || testsFailed || configMissing || dbMissing,
                id: 'AuthenticationPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Email'),
                iconCls: 'action_composeEmail',
                disabled: termsFailed || testsFailed || configMissing || dbMissing || setupRequired,
                id: 'EmailPanel',
                leaf: true
            }, {
                text: this.app.i18n._('Application Manager'),
                iconCls: 'setup_application_manager',
                disabled: termsFailed || testsFailed || configMissing || dbMissing || setupRequired,
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
        var termsChecks  = Tine.Setup.registry.get('AcceptedTermsVersion') >= Tine.Setup.CurrentTermsVersion;
        var setupChecks  = Tine.Setup.registry.get('setupChecks').success;
        var configExists = Tine.Setup.registry.get('configExists');
        var checkDB      = Tine.Setup.registry.get('checkDB');
        var setupRequired = Tine.Setup.registry.get('setupRequired');
        
        this.setNodeIcon('TermsPanel', termsChecks);
        this.setNodeIcon('EnvCheckGridPanel', setupChecks);
        
        this.getNodeById('EnvCheckGridPanel')[termsChecks ? 'enable': 'disable']();
        this.getNodeById('ConfigManagerPanel')[termsChecks && setupChecks ? 'enable': 'disable']();
        this.getNodeById('AuthenticationPanel')[termsChecks && setupChecks && configExists && checkDB ? 'enable': 'disable']();
        this.getNodeById('ApplicationGridPanel')[termsChecks && setupChecks && configExists && checkDB && !setupRequired ? 'enable': 'disable']();
        this.getNodeById('EmailPanel')[termsChecks && setupChecks && configExists && checkDB && !setupRequired ? 'enable': 'disable']();
    },
    
    setNodeIcon: function (nodeId, success) {
        var node = this.getNodeById(nodeId);
        var iconCls = success ? 'setup_checks_success' : 'setup_checks_fail';
        if (node.rendered) {
            var iconEl = Ext.get(node.ui.iconNode);
            iconEl.removeClass('setup_checks_success');
            iconEl.removeClass('setup_checks_fail');
            iconEl.addClass(iconCls);
        } else {
            envNode.iconCls = iconCls;
        }
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
