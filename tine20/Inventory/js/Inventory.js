/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Inventory');

/**
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Inventory.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Inventory');
    }
});

/**
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Inventory.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'InventoryItem'
});
    
/**
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Inventory.InventoryItemTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'Inventory_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.Inventory.Model.InventoryItem
});

/**
 * favorites panel
 * 
 * @class       Tine.Inventory.FilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 *  
 * @param {Object} config
 */
Tine.Inventory.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Inventory.FilterPanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Inventory.FilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Inventory_Model_InventoryItemFilter'}]
});
