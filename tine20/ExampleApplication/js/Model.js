/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.ExampleApplication.Model');

/**
 * @namespace   Tine.ExampleApplication.Model
 * @class       Tine.ExampleApplication.Model.ExampleRecord
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.ExampleApplication.Model.ExampleRecord = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    { name: 'status' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]), {
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('example record', 'example records', n);
    recordName: 'example record',
    recordsName: 'example records',
    containerProperty: 'container_id',
    // ngettext('example record list', 'example record lists', n);
    containerName: 'example record list',
    containersName: 'example record lists'
});

/**
 * @namespace Tine.ExampleApplication.Model
 * 
 * get default data for a new record
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.ExampleApplication.Model.ExampleRecord.getDefaultData = function() { 
    var app = Tine.Tinebase.appMgr.get('ExampleApplication');
    var defaultsContainer = Tine.ExampleApplication.registry.get('defaultContainer');
    
    return {
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer()
        // [...] add more defaults
    };
};

/**
 * get filtermodel of record
 * 
 * @namespace Tine.ExampleApplication.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.ExampleApplication.Model.ExampleRecord.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('ExampleApplication');
    
    return [
        {label: _('Quick search'),    field: 'query',       operators: ['contains']},
        {
            label: app.i18n._('Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'exampleStatus'
        },
        {filtertype: 'tinebase.tag', app: app},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.ExampleApplication.Model.ExampleRecord},
        {label: app.i18n._('Last modified'),                                            field: 'last_modified_time', valueType: 'date'},
        {label: app.i18n._('Last modifier'),                                            field: 'last_modified_by',   valueType: 'user'},
        {label: app.i18n._('Creation Time'),                                            field: 'creation_time',      valueType: 'date'},
        {label: app.i18n._('Creator'),                                                  field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * default ExampleRecord backend
 */
Tine.ExampleApplication.recordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord
});

