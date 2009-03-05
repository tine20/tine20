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
 * Record model fields
 */
Tine.ExampleApplication.Model.RecordArray = Tine.Tinebase.Model.genericFields.concat([
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
 * @type {Tine.Tinebase.Record}
 * record definition
 */
Tine.ExampleApplication.Model.Record = Tine.Tinebase.Record.create(Tine.ExampleApplication.Model.RecordArray, {
    appName: 'ExampleApplication',
    modelName: 'Record',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Record', 'Records', n);
    recordName: 'Record',
    recordsName: 'Records',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'record list',
    containersName: 'record lists',
    getTitle: function() {
        return this.get('number') ? (this.get('number') + ' ' + this.get('title')) : false;
    }
});

Tine.ExampleApplication.Model.Record.getDefaultData = function() { 
    return {
    	/*
        is_open: 1,
        is_billable: true
        */
    }
};
