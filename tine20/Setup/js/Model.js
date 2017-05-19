/*
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 - 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

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
