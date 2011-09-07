/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Inventory.Model');

/**
 * @namespace   Tine.Inventory.Model
 * @class       Tine.Inventory.Model.InventoryItem
 * @extends     Tine.Tinebase.data.Record
 * inventory record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Inventory.Model.InventoryItem = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    { name: 'type' },
    { name: 'inventory_id' },
    { name: 'location' },
    { name: 'description' },
    { name: 'add_time' },
    { name: 'total_number' },
    { name: 'active_number' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'notes'},
    { name: 'tags' }
]), {
    appName: 'Inventory',
    modelName: 'InventoryItem',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('inventory item', 'inventory records', n);
    recordName: 'inventory item',
    recordsName: 'inventory items',
    containerProperty: 'container_id',
    // ngettext('inventory record list', 'inventory record lists', n);
    containerName: 'inventory item list',
    containersName: 'inventory item lists'
});

/**
 * @namespace Tine.Inventory.Model
 * 
 * get default data for a new record
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Inventory.Model.InventoryItem.getDefaultData = function() { 
    var app = Tine.Tinebase.appMgr.get('Inventory');
    var defaultsContainer = Tine.Inventory.registry.get('defaultContainer');
    
    return {
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer()
        // [...] add more defaults
    };
};

/**
 * get filtermodel of record
 * 
 * @namespace Tine.Inventory.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.Inventory.Model.InventoryItem.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Inventory');
    
    return [
        {label: _('Quick search'),    field: 'query',       operators: ['contains']},
        {
            label: app.i18n._('Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'inventoryType'
        },
        {filtertype: 'tinebase.tag', app: app},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Inventory.Model.InventoryItem},
        {label: app.i18n._('Last modified'),                                            field: 'last_modified_time', valueType: 'date'},
        {label: app.i18n._('Last modifier'),                                            field: 'last_modified_by',   valueType: 'user'},
        {label: app.i18n._('Creation Time'),                                            field: 'creation_time',      valueType: 'date'},
        {label: app.i18n._('Creator'),                                                  field: 'created_by',         valueType: 'user'}
    ];
};

/**
 * default InventoryItem backend
 */
Tine.Inventory.recordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Inventory',
    modelName: 'InventoryItem',
    recordClass: Tine.Inventory.Model.InventoryItem
});
