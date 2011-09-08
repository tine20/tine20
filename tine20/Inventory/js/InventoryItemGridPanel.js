/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Inventory');

/**
 * InventoryItem grid panel
 * 
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.InventoryItemGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>InventoryItem Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Inventory.InventoryItemGridPanel
 */
Tine.Inventory.InventoryItemGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Inventory.Model.InventoryItem} recordClass
     */
    recordClass: Tine.Inventory.Model.InventoryItem,
   
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: true,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'inventory_id'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Inventory.recordBackend;
        
        this.gridConfig.cm = this.getColumnModel();
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Inventory.InventoryItemGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO    add more columns
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [{
                id: 'inventory_id',
                header: this.app.i18n._("ID"),
                width: 50,
                sortable: true,
                dataIndex: 'inventory_id'
            }, {
                id: 'name',
                header: this.app.i18n._("Name"),
                width: 50,
                sortable: true,
                dataIndex: 'name'
            }, {
                id: 'type',
                header: this.app.i18n._("Type"),
                width: 50,
                sortable: true,
                dataIndex: 'type',
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Inventory', 'inventoryType')
            },{
                id: 'add_time',
                header: this.app.i18n._("Added"),
                width: 50,
                sortable: true,
                dataIndex: 'add_time', 
                renderer: Tine.Tinebase.common.dateRenderer
            },{
                id: 'location',
                header: this.app.i18n._("Location"),
                width: 50,
                sortable: true,
                dataIndex: 'location'
            },{
                id: 'total_number',
                header: this.app.i18n._("Total number"),
                width: 50,
                sortable: true,
                dataIndex: 'total_number'
            },{
                id: 'active_number',
                header: this.app.i18n._("Active number"),
                width: 50,
                sortable: true,
                dataIndex: 'active_number'
            },{
                id: 'description',
                header: this.app.i18n._("Description"),
                width: 50,
                sortable: true,
                dataIndex: 'description'
            }
            
            
            
            
            
            /*,{
                id: 'title',
                header: this.app.i18n._("Title"),
                width: 350,
                sortable: true,
                dataIndex: 'title'
            },{
                id: 'budget',
                header: this.app.i18n._("Budget"),
                width: 100,
                sortable: true,
                dataIndex: 'budget'
            }*/].concat(this.getModlogColumns())
        });
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * return additional tb items
     * @private
     */
    getToolbarItems: function(){
    	/*
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        */
        
        return [
            /*
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
            */
        ];
    }    
});
