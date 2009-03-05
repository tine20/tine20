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
    /*
    // add record fields here
    { name: 'container_id' },
    { name: 'title' },
    { name: 'number' },
    { name: 'description' },
    { name: 'budget' },
    { name: 'budget_unit' },
    { name: 'price' },
    { name: 'price_unit' },
    { name: 'is_open' },
    { name: 'is_billable' },
    { name: 'status' },
    { name: 'account_grants'},
    { name: 'grants'},
    */
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]);

/**
 * @type {Tine.Tinebase.ExampleRecord}
 * record definition
 */
Tine.ExampleApplication.Model.ExampleRecord = Tine.Tinebase.Record.create(Tine.ExampleApplication.Model.ExampleRecordArray, {
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
    }
};
