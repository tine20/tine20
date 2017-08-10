/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler.Model');
    
/**
 * @namespace   Tine.MailFiler.Model
 * @class       Tine.MailFiler.Model.Node
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.MailFiler.Model.Node = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.Tree_NodeArray.concat([
    { name: 'message'}
]), {
    appName: 'MailFiler',
    modelName: 'Node',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('File', 'Files', n); gettext('File');
    recordName: 'File',
    recordsName: 'Files',
    // ngettext('Folder', 'Folders', n); gettext('Folder');
    containerName: 'Folder',
    containersName: 'Folders',
    
    /**
     * virtual nodes are part of the tree but don't exists / are editable
     *
     * NOTE: only "real" virtual node is node with path "otherUsers". all other nodes exist
     *
     * @returns {boolean}
     */
    isVirtual: function() {
        var _ = window.lodash;

        return _.indexOf(['/', Tine.Tinebase.container.getMyFileNodePath(), '/personal', '/shared'], this.get('path')) >= 0;
    }
});
    
/**
 * create Node from File
 * 
 * @param {File} file
 */
Tine.MailFiler.Model.Node.createFromFile = function(file) {
    return new Tine.MailFiler.Model.Node({
        name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
        size: file.size || 0,
        type: 'file',
        contenttype: file.type ? file.type : file.fileType, // missing if safari and chrome 
        revision: 0
    });
};

/**
 * file record backend
 */
Tine.MailFiler.fileRecordBackend = new Tine.Filemanager.FileRecordBackend({
    appName: 'MailFiler',
    modelName: 'Node',
    recordClass: Tine.MailFiler.Model.Node
});


/**
 * get filtermodel of Node records
 * 
 * @namespace Tine.MailFiler.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.MailFiler.Model.Node.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('MailFiler');
       
    return [
        {label : i18n._('Quick Search'), field : 'query', operators : [ 'contains' ]},
//        {label: app.i18n._('Type'), field: 'type'}, // -> should be a combo
        {label: app.i18n._('Contenttype'), field: 'contenttype'},
        {label: app.i18n._('Creation Time'), field: 'creation_time', valueType: 'date'},
        {label: app.i18n._('Description'), field: 'description', operators: ['contains', 'notcontains']},
        {filtertype : 'tine.filemanager.pathfiltermodel', app : app}, 
        {filtertype : 'tinebase.tag', app : app},
        {label: app.i18n._('Subject'),     field: 'subject',       operators: ['contains']},
        {label: app.i18n._('From (Email)'),field: 'from_email',    operators: ['contains']},
        {label: app.i18n._('From (Name)'), field: 'from_name',     operators: ['contains']},
        {label: app.i18n._('To'),          field: 'to',            operators: ['contains']},
        {label: app.i18n._('Cc'),          field: 'cc',            operators: ['contains']},
        {label: app.i18n._('Bcc'),         field: 'bcc',           operators: ['contains']},
        {label: app.i18n._('Flags'),       field: 'flags',         filtertype: 'tinebase.multiselect', app: app, multiselectFieldConfig: {
            valueStore: Tine.Felamimail.loadFlagsStore()
        }},
        {label: app.i18n._('Received'),    field: 'received',      valueType: 'date', pastOnly: true}
    ];
};
