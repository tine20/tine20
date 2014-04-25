/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * InvoicePosition grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoicePositionGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>InvoicePosition Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoicePositionGridPanel
 */
Tine.Sales.InvoicePositionGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    storeRemoteSort: false,
    defaultSortInfo: {field: 'title', direction: 'DESC'},
    usePagingToolbar: false,
    frame: true,
    layout: 'fit',
    border: true,
    anchor: '100% 100%',
    editDialogRecordProperty: 'positions',
    plugins: [{
        ptype: 'grouping_grid' 
    }],
    
    initStore: function() {
        this.store = new Ext.data.GroupingStore({
            fields: this.recordClass,
            proxy: this.recordProxy,
            reader: this.recordProxy.getReader(),
            remoteSort: this.storeRemoteSort,
            sortInfo: this.defaultSortInfo,
            listeners: {
                scope: this,
                'add': this.onStoreAdd,
                'remove': this.onStoreRemove,
                'update': this.onStoreUpdate,
                'beforeload': this.onStoreBeforeload,
                'load': this.onStoreLoad,
                'beforeloadrecords': this.onStoreBeforeLoadRecords,
                'loadexception': this.onStoreLoadException
            },
            autoDestroy: true,
            groupOnSort: true,
            remoteGroup: true,
            groupField: 'model'
        });
    },
    
    /**
     * 
     * @return {}
     */
    getColumnModel: function()
    {
        return this.gridConfig.cm;
    },
    
    /**
     * creates and returns the view for the grid
     * 
     * @return {Ext.grid.GridView}
     */
    createView: function() {
        var view = new Ext.grid.GroupingView({
            forceFit: true,
            // custom grouping text template to display the number of items per group
    
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
        });
        
        return view;
    }
});
