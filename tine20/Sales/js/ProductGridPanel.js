/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Product grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ProductGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Product Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ProductGridPanel
 */
Tine.Sales.ProductGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        Tine.Sales.ProductGridPanel.superclass.initComponent.call(this);
        
        // actions depend on manage_products right
        this.selectionModel.on('selectionchange', function(sm) {
            var hasManageRight = Tine.Tinebase.common.hasRight('manage', 'Sales', 'products');

            if (hasManageRight) {
                Tine.widgets.actionUpdater(sm, this.actions, this.recordClass.getMeta('containerProperty'), !this.evalGrants);
                if (this.updateOnSelectionChange && this.detailsPanel) {
                    this.detailsPanel.onDetailsUpdate(sm);
                }
            } else {
                this.action_editInNewWindow.setDisabled(true);
                this.action_deleteRecord.setDisabled(true);
                this.action_tagsMassAttach.setDisabled(true);
            }
        }, this);

        this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Sales', 'products'));
    }
});
