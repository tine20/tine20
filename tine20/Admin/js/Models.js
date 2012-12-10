/*
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    // ngettext('Access Log', 'Access Logs', n);
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
    recordsName: 'Groups'
});

/**
 * returns default group data
 * 
 * @namespace Tine.Admin.Model.Group
 * @static
 * @return {Object} default data
 */
Tine.Admin.Model.Group.getDefaultData = function () {
    var internalAddressbook = Tine.Admin.registry.get('defaultInternalAddressbook');
    
    return {
        visibility: (internalAddressbook !== null) ? 'displayed' : 'hidden',
        container_id: internalAddressbook
    };
};

/**
 * @namespace Tine.Admin.Model
 * @class     Tine.Admin.Model.Application
 * @extends   Tine.Admin.data.Record
 * 
 * Model of an application
 */
Tine.Admin.Model.Application = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'status'},
    {name: 'order'},
    {name: 'app_tables'},
    {name: 'version'}
], {
    appName: 'Admin',
    modelName: 'Application',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Application', 'Applications', n); gettext('Applications');
    recordName: 'Application',
    recordsName: 'Applications'
});
