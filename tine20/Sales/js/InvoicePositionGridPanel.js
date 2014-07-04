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
    defaultSortInfo: {field: 'month', direction: 'ASC'},
    usePagingToolbar: false,
    frame: true,
    layout: 'fit',
    border: true,
    anchor: '100% 100%',
    editDialogRecordProperty: 'positions',
    plugins: [{
        ptype: 'grouping_grid'
    }],
    
    initComponent: function() {
        Tine.Sales.InvoicePositionGridPanel.superclass.initComponent.call(this);
        
        this.onAfterRender();
    },
    
    onAfterRender: function() {
        if (! this.rendered) {
            this.onAfterRender.defer(1000, this);
            return;
        }
        
        var elements = this.getEl().select('.action_export');
        
        elements.on('click', this.onExport.createDelegate(this));
        elements.setStyle('cursor', 'pointer');
    },
    
    onExport: function(event, element) {
        if (! event) {
            return;
        }
        var target = event.getTarget('.action_export');
        
        if (! target) {
            return;
        }
        
        var phpModelName = target.getAttribute('ext:model');
        
        if (phpModelName) {
            var downloader = new Ext.ux.file.Download({
                params: {
                    method: 'Sales.exportInvoicePositions',
                    requestType: 'HTTP',
                    invoiceId: this.editDialog.record.get('id'),
                    accountable: phpModelName
                }
            }).start();
        }
    },
    
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
            groupOnSort: false,
            remoteGroup: false,
            groupField: 'model'
        });
    },
    
    initActions: function(){},
    
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
        var tpl = '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "' + this.app.i18n._("Items") + '" : "' + this.app.i18n._("Item") + '"]})';
        var view = new Ext.grid.GroupingView({
            forceFit: true,
            // custom grouping text template to display the number of items per group
    
            groupTextTpl: tpl
        });
        
        return view;
    }
});
