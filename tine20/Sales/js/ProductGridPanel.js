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
    },
    
    /**
     * initialises filter toolbar
     * 
     * @return Tine.widgets.grid.FilterToolbar
     * @private
     */
    getFilterToolbar: function() {
        return new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Product'),        field: 'query',    operators: ['contains']},
                {label: this.app.i18n._('Product name'),   field: 'name' },
                {filtertype: 'tinebase.tag', app: this.app}
            ],
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
            /*
                {header: this.app.i18n._('Product id'), id: 'id', dataIndex: 'id', width: 20, hidden: true},
                {header: this.app.i18n._('Tags'), id: 'tags', dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false},
                {header: this.app.i18n._('Product name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
                {header: this.app.i18n._('Partner'), id: 'lead_partner', dataIndex: 'relations', width: 175, sortable: false, renderer: this.partnerRenderer},
                {header: this.app.i18n._('Customer'), id: 'lead_customer', dataIndex: 'relations', width: 175, sortable: false, renderer: this.customerRenderer},
                {header: this.app.i18n._('Productstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100, renderer: Tine.Sales.ProductState.Renderer},
                {header: this.app.i18n._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
                {header: this.app.i18n._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
                */
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
