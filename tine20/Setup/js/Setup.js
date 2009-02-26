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

// default mainscreen
Tine.Setup.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

Tine.Setup.TreePanel = Ext.extend(Ext.Panel, {
    border: false,
    html: ''
});

Ext.ns('Tine', 'Tine.Setup', 'Tine.Setup.Model');

Tine.Setup.Model.ApplicationArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id'              },
    { name: 'name'            },
    { name: 'status'          },
    { name: 'order'           },
    { name: 'version'         },
    { name: 'current_version' }
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