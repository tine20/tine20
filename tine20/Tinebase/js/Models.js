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
 * Model of the tine (simple) account
 */
Tine.Tinebase.Model.Account = Ext.data.Record.create([
    { name: 'accountId' },
    { name: 'accountDisplayName' },
    { name: 'accountLastName' },
    { name: 'accountFirstName' },
    { name: 'accountFullName' },
]);

/**
 * Model of a grant
 */
Tine.Tinebase.Model.Grant = Ext.data.Record.create([
    {name: 'accountId'},
    {name: 'accountType'},
    {name: 'readGrant',   type: 'boolean'},
    {name: 'addGrant',    type: 'boolean'},
    {name: 'editGrant',   type: 'boolean'},
    {name: 'deleteGrant', type: 'boolean'},
    {name: 'adminGrant',  type: 'boolean'}
]);