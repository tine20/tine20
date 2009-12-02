/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.ExampleApplication', 'Tine.ExampleApplication.Model');

/**
 * @type {Array}
 * ExampleRecord model fields
 */
Tine.ExampleApplication.Model.ExampleRecordArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]);

/**
 * @type {Tine.Tinebase.ExampleRecord}
 * record definition
 */
Tine.ExampleApplication.Model.ExampleRecord = Tine.Tinebase.data.Record.create(Tine.ExampleApplication.Model.ExampleRecordArray, {
    appName: 'ExampleApplication',
    modelName: 'ExampleRecord',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('ExampleRecord', 'ExampleRecords', n);
    recordName: 'ExampleRecord',
    recordsName: 'ExampleRecords',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

Tine.ExampleApplication.Model.ExampleRecord.getDefaultData = function() { 
    return {
    	/*
        is_open: 1,
        is_billable: true
        */
    };
};
