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

/**************************** Tree Panel *****************************/
Tine.Setup.TreePanel = Ext.extend(Ext.tree.TreePanel, {
    border: false,
    rootVisible: false, 
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Setup');
        
        var testsFailed   = !Tine.Setup.registry.get('setupChecks').success;
        var configMissing = !Tine.Setup.registry.get('configExists');
        var dbMissing     = !Tine.Setup.registry.get('checkDB');
        
        this.root = {
            id: '/',
            children: [{
                text: this.app.i18n._('Setup Checks'),
                iconCls: testsFailed ? 'setup_checks_fail' : 'setup_checks_success',
                id: 'EnvCheck',
                leaf: true
            }, {
                text: this.app.i18n._('Config Manager'),
                iconCls: 'setup_config_manager',
                disabled: testsFailed,
                id: 'ConfigManager',
                leaf: true
            }, {
                text: this.app.i18n._('Application Manager'),
                iconCls: 'setup_application_manager',
                disabled: testsFailed || configMissing || dbMissing,
                id: 'Application',
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
            this.app.getMainScreen().activeContentType = node.id;
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
        this.app.getMainScreen().activeContentType = activeType.id;
    }
});

/**************************** Models *****************************/
Ext.ns('Tine', 'Tine.Setup', 'Tine.Setup.Model');

Tine.Setup.Model.ApplicationArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id'              },
    { name: 'name'            },
    { name: 'status'          },
    { name: 'order'           },
    { name: 'version'         },
    { name: 'current_version' },
    { name: 'install_status'  }
]);

/**
 * Task record definition
 */
Tine.Setup.Model.Application = Tine.Tinebase.Record.create(Tine.Setup.Model.ApplicationArray, {
    appName: 'Setup',
    modelName: 'Application',
    idProperty: 'name',
    titleProperty: 'name',
    // ngettext('Application', 'Applications', n); gettext('Application');
    recordName: 'Application',
    recordsName: 'Applications'
});

/**
 * default tasks backend
 */
Tine.Setup.ApplicationBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Setup',
    modelName: 'Application',
    recordClass: Tine.Setup.Model.Application
});

/**
 * Model of a grant
 */
Tine.Setup.Model.EnvCheck = Ext.data.Record.create([
    {name: 'key'},
    {name: 'value'},
    {name: 'message'}
]);
