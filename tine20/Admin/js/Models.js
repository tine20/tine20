/*
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Models.js 10409 2009-09-11 12:23:23Z p.schuele@metaways.de $
 *
 */
 
Ext.ns('Tine.Admin.Model');

/**
 * @namespace   Tine.Admin.Model
 * @class       Tine.Admin.Model.TagRight
 * @extends     Ext.data.Record
 * 
 * TagRight Record Definition
 */ 
Tine.Admin.Model.TagRight = Ext.data.Record.create([
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'account_data'},
    {name: 'view_right', type: 'boolean'},
    {name: 'use_right',  type: 'boolean'}
]);

/**
 * @namespace   Tine.Admin.Model
 * @class       Tine.Admin.Model.AccessLog
 * @extends     Tine.Tinebase.data.Record
 * 
 * AccessLog Record Definition
 */ 
Tine.Admin.Model.AccessLog = Tine.Tinebase.data.Record.create([
    {name: 'sessionid'},
    {name: 'login_name'},
    {name: 'ip'},
    {name: 'li', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'lo', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'id'},
    {name: 'account_id'},
    {name: 'result'},
    {name: 'clienttype'}
], {
    appName: 'Admin',
    modelName: 'AccessLog',
    idProperty: 'id',
    titleProperty: 'login_name',
    // ngettext('AccessLog', 'AccessLogs', n);
    recordName: 'AccessLog',
    recordsName: 'AccessLogs'
});

/**
 * AccessLog data proxy
 * 
 * @type Tine.Tinebase.data.RecordProxy
 */ 
Tine.Admin.accessLogBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'AccessLog',
    recordClass: Tine.Admin.Model.AccessLog,
    idProperty: 'id'
});

/**
 * @namespace Tine.Admin.Model
 * @class     Tine.Admin.Model.Group
 * @extends   Tine.Admin.data.Record
 * 
 * Model of a user group
 */
Tine.Admin.Model.Group = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'container_id'},
    {name: 'visibility'}
    //{name: 'groupMembers'}
], {
    appName: 'Admin',
    modelName: 'Group',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Group', 'Groups', n); gettext('Groups');
    recordName: 'Group',
    recordsName: 'Groups',
    containerProperty: null
});

