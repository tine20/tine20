/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine', 'Tine.Tinebase', 'Tine.Tinebase.Model');

/**
 * Model of the tine (simple) user account
 */
Tine.Tinebase.Model.User = Ext.data.Record.create([
    { name: 'accountId' },
    { name: 'accountDisplayName' },
    { name: 'accountLastName' },
    { name: 'accountFirstName' },
    { name: 'accountFullName' }
]);

/**
 * Model of a user group account
 */
Tine.Tinebase.Model.Group = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
    // @todo add accounts array to group model?
]);

/**
 * Model of a role
 */
Tine.Tinebase.Model.Role = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
]);

/**
 * Model of a generalised account (user or group)
 */
Tine.Tinebase.Model.Account = Ext.data.Record.create([
    {name: 'id'},
    {name: 'type'},
    {name: 'name'},
    {name: 'data'} // todo: throw away data
]);

/**
 * Model of a grant
 */
Tine.Tinebase.Model.Grant = Ext.data.Record.create([
    {name: 'id'},
    {name: 'accountId'},
    {name: 'accountType'},
    {name: 'readGrant',   type: 'boolean'},
    {name: 'addGrant',    type: 'boolean'},
    {name: 'editGrant',   type: 'boolean'},
    {name: 'deleteGrant', type: 'boolean'},
    {name: 'adminGrant',  type: 'boolean'}
]);

/**
 * Model of a tag
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.Tag = Ext.data.Record.create([
    {name: 'id'         },
    {name: 'app'        },
    {name: 'owner'      },
    {name: 'name'       },
    {name: 'type'       },
    {name: 'description'},
    {name: 'color'      },
    {name: 'occurrence' },
]);