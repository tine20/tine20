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
     * @todo: make this generally available (here its more general: Tine.HumanResources.EmployeeGridPanel)
     * 
     * returns additional toobar items
     * 
     * @return {Array} of Ext.Action
     */
    getActionToolbarItems: function() {
        this.actions_export = new Ext.Action({
            // i18n._('Export Suppliers')
            text: this.app.i18n._hidden('Export Suppliers'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'exportGrant',
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export selected suppliers as ODS'),
                        singularText: this.app.i18n._('Export selected supplier as ODS'),
                        pluralText: this.app.i18n._('Export selected suppliers as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'Sales.exportSuppliers',
                        gridPanel: this
                    })
                ]
            }
        });
        
        var button = Ext.apply(new Ext.Button(this.actions_export), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        var additionalActions = [this.actions_export];
        this.actionUpdater.addActions(additionalActions);
        return [button];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        return [
            '-',
            this.actions_export
        ];
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
