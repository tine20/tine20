/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Invoice grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.PurchaseInvoiceGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Invoice Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.PurchaseInvoiceGridPanel
 */
Tine.Sales.PurchaseInvoiceGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    initComponent: function() {
        this.initDetailsPanel();
        Tine.Sales.PurchaseInvoiceGridPanel.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Sales.PurchaseInvoiceDetailsPanel({
            grid: this,
            app:  this.app
        });
    }
});
