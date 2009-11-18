/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Product grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ProductGridPanel
 * @extends     Tine.Tinebase.widgets.app.GridPanel
 * 
 * <p>Product Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ProductGridPanel
 */
Tine.Sales.ProductGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Sales.Model.Product} recordClass
     */
    recordClass: Tine.Sales.Model.Product,
    
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: false,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'name', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'name'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Sales.productBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.contextMenuItems = [
        ];

        this.gridConfig.cm = this.getColumnModel();
        this.filterToolbar = this.getFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
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
    },
    
    /**
     * initialises filter toolbar
     * 
     * @return Tine.widgets.grid.FilterToolbar
     * @private
     */
    getFilterToolbar: function() {
        return new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.Sales.Model.Product.getFilterModel(),
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true
            },
            columns: [
                {header: this.app.i18n._('Tags'), id: 'tags', dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false},
                {header: this.app.i18n._('Name'), id: 'name', dataIndex: 'name', width: 200},
                {header: this.app.i18n._('Manufacturer'), id: 'manufacturer', dataIndex: 'manufacturer', width: 100},
                {header: this.app.i18n._('Category'), id: 'category', dataIndex: 'category', width: 100},
                {header: this.app.i18n._('Description'), id: 'description', dataIndex: 'description', width: 150, sortable: false, hidden: true},
                {header: this.app.i18n._('Price'), id: 'price', dataIndex: 'price', width: 75, renderer: Ext.util.Format.euMoney}
            ]
        });
    },

    /**
     * return additional tb items
     * @private
     */
    getToolbarItems: function(){
        
        return [
        ];
    }    
});
