/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Supplier grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.SupplierGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Supplier Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.SupplierGridPanel
 */
Tine.Sales.SupplierGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    initComponent: function() {
        this.initDetailsPanel();
        Tine.Sales.SupplierGridPanel.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Sales.SupplierDetailsPanel({
            grid: this,
            app: this.app
        });
    }
});
