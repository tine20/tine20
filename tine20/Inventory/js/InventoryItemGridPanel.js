/*
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Inventory');

/**
 * Inventory grid panel
 * 
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.InventoryGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Inventory Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Inventory.InventoryItemGridPanel
 */
Tine.Inventory.InventoryItemGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    multipleEdit: true,
    copyEditAction: true,
    gridConfig: {
        enableDragDrop: true,
        ddGroup: 'containerDDGroup'
    }
});
