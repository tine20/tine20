/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * list model
 */
Tine.Addressbook.Model.List = Tine.Tinebase.data.Record.create([
   {name: 'id'},
   {name: 'container_id'},
   {name: 'created_by'},
   {name: 'creation_time'},
   {name: 'last_modified_by'},
   {name: 'last_modified_time'},
   {name: 'is_deleted'},
   {name: 'deleted_time'},
   {name: 'deleted_by'},
   {name: 'name'},
   {name: 'emails'},
   {name: 'description'},
   {name: 'members'},
   {name: 'email'},
   {name: 'type'},
   {name: 'group_id'}
], {
    appName: 'Addressbook',
    modelName: 'List',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('List', 'Lists', n); gettext('Lists');
    recordName: 'List',
    recordsName: 'Lists',
    containerProperty: 'container_id',
    // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
    containerName: 'Addressbook',
    containersName: 'Addressbooks',
    copyOmitFields: ['group_id']
});

/**
 * get filtermodel of list model
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */ 
Tine.Addressbook.Model.List.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Addressbook');
    
    var typeStore = [['list', app.i18n._('List')], ['user', app.i18n._('User Account')]];
    
    return [
        {label: _('Quick search'),                                                      field: 'query',              operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Addressbook.Model.Contact},
        {filtertype: 'addressbook.listMember', app: app},
        {label: app.i18n._('Name'),                                               field: 'name' },
        {label: app.i18n._('Description'),                                                field: 'description'},
        {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
        {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
        {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * default list backend
 */
Tine.Addressbook.listBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Addressbook',
    modelName: 'List',
    recordClass: Tine.Addressbook.Model.List
});