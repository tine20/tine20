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
     * @todo: make this generally available (here its more general: Tine.HumanResources.EmployeeGridPanel)
     * 
     * returns additional toobar items
     * 
     * @return {Array} of Ext.Action
     */
    getActionToolbarItems: function() {
        return [];
        
        this.actions_export = new Ext.Action({
            // _('Export Invoices')
            text: this.app.i18n._hidden('Export Invoices'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'exportGrant',
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export selected invoices as ODS'),
                        singularText: this.app.i18n._('Export selected invoice as ODS'),
                        pluralText: this.app.i18n._('Export selected invoices as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'Sales.exportPurchaseInvoices',
                        gridPanel: this
                    })/*,
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ...'),
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'Sales.exportPuchaseInvoices',
                        showExportDialog: true,
                        gridPanel: this
                    })*/
                ]
            }
        });

        var exportButton = Ext.apply(new Ext.SplitButton(this.actions_export), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top',
            arrowAlign:'right'
        });
        
        var additionalActions = [this.actions_export];
        this.actionUpdater.addActions(additionalActions);
        return [exportButton];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_export
            ];
        
        return items;
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
