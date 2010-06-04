/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  sambaMachine
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.ns('Tine.Admin.Model');

Tine.Admin.Model.sambaMachineArray = [
    { name: 'accountId'            },
    { name: 'accountLoginName'     },
    { name: 'accountLastName'      },
    { name: 'accountFullName'      },
    { name: 'accountDisplayName'   },
    { name: 'accountPrimaryGroup'  },
    { name: 'accountHomeDirectory' },
    { name: 'accountLoginShell'    },
    { name: 'sid'                  },
    { name: 'primaryGroupSID'      },
    { name: 'acctFlags'            },
    //{ name: 'logonTime',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
    //{ name: 'logoffTime',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    //{ name: 'kickoffTime',   type: 'date', dateFormat: Date.patterns.ISO8601Long },
    //{ name: 'pwdLastSet',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    //{ name: 'pwdCanChange',  type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdMustChange', type: 'date', dateFormat: Date.patterns.ISO8601Long }
];

Tine.Admin.Model.SambaMachine = Tine.Tinebase.data.Record.create(Tine.Admin.Model.sambaMachineArray, {
    appName: 'Admin',
    modelName: 'SambaMachine',
    idProperty: 'accountId',
    titleProperty: 'accountDisplayName',
    // ngettext('Computer', 'Computers', n);
    recordName: 'Computer',
    recordsName: 'Computers'
});

Tine.Admin.sambaMachineBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'SambaMachine',
    recordClass: Tine.Admin.Model.SambaMachine,
    idProperty: 'accountId'
});
