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
 * Model of a language
 */
Tine.Tinebase.Model.Language = Ext.data.Record.create([
    { name: 'locale' },
    { name: 'language' },
    { name: 'region' }
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
    {name: 'rights'     },
    {name: 'contexts'   }
]);

/**
 * Model of a PickerRecord
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.PickerRecord = Ext.data.Record.create([
    {name: 'id'}, 
    {name: 'name'}, 
    {name: 'data'}
]);

/**
 * Model of a note
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.Note = Ext.data.Record.create([
    {name: 'id'             },
    {name: 'note_type_id'   },
    {name: 'note'           },
    {name: 'creation_time', type: 'date', dateFormat: 'c' },
    {name: 'created_by'     }
]);

/**
 * Model of a note type
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.NoteType = Ext.data.Record.create([
    {name: 'id'             },
    {name: 'name'           },
    {name: 'icon'           },
    {name: 'description'    },
    {name: 'is_user_type'   }
]);
