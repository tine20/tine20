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
    frame: false,
    layout: 'fit',
    border: true,
    anchor: '100% 100%',
    editDialogRecordProperty: 'positions',
    
    /**
     * holds the php model name of the accountable
     * 
     * @type string
     */
    accountable: null,
    
    accountableApplication: null,
    
    groupField: null,
    
    invoiceId: null,
    
    i18nModelsName: null,
    i18nUnitName: null,
    unitName: null,
    renderedSum: null,
    renderedSumsPerMonth: null,
    
    initComponent: function() {
        this.bbar = [];
        
        Tine.Sales.InvoicePositionGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
        
        this.grid.renderedSumsPerMonth = this.renderedSumsPerMonth;
        this.grid.view.renderedSumsPerMonth = this.renderedSumsPerMonth;
        
        if (this.groupField) {
            this.doGroup();
        }
    },
    
    /**
     * creates the view
     * 
     * @return {Object}
     */
    createView: function() {
        var view = Tine.Sales.InvoicePositionGridPanel.superclass.createView.call(this);
        view.renderedSumsPerMonth = this.renderedSumsPerMonth;
        return view;
    },
    
    doGroup: function() {
        if (! this.rendered) {
            this.doGroup.defer(200, this);
            return false;
        }
        
        this.store.sort(this.defaultSortInfo);
        this.store.groupBy(this.groupField);
    },
    
    /**
     * the export handler
     */
    onExport: function() {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Sales.exportInvoicePositions',
                requestType: 'HTTP',
                invoiceId: this.invoiceId,
                accountable: this.accountable
            }
        }).start();
    },
    
    initActions: function() {
        this.action_export = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'export',
            text: String.format(i18n._('Export Records from these Positions') + ' ({0})', this.i18nModelsName),
            handler: this.onExport.createDelegate(this),
            iconCls: 'action_export',
            scope: this
        });
    },
    
    /**
     * will be called in Edit Dialog Mode
     */
    fillBottomToolbar: function() {
        var bbar = this.getBottomToolbar();
        bbar.addButton(new Ext.Button(this.action_export));
        bbar.addItem('->');
        bbar.addItem({xtype: 'tbtext', text: this.i18nModelsName + ' (' + this.i18nUnitName + '): ' + '<b>' + this.renderedSum + '</b>'});
        bbar.addItem({xtype: 'tbspacer', width: 20});
    }
});
