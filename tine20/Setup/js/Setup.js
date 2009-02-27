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


Tine.Setup.TreePanel = Ext.extend(Ext.tree.TreePanel, {
    border: false,
    rootVisible: false, 
    rooot: {
        children: [{
            text: 'Setup Checks',
            leaf: true
        }, {
            text: 'Config Manager',
            leaf: true
        }, {
            text: 'Application Manager',
            leaf: true
        }]
    },
    
    initComponent: function() {
        this.root = {
            children: [{
                text: 'Setup Checks',
                iconCls: Tine.Setup.registry.get('setupChecks').success ? 'setup_checks_success' : 'setup_checks_fail',
                leaf: true
            }, {
                text: 'Config Manager',
                disabled: true,
                leaf: true
            }, {
                text: 'Application Manager',
                disabled: true,
                leaf: true
            }]
        };
        
        Tine.Setup.TreePanel.superclass.initComponent.call(this);
    }
});

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