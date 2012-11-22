/*
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Inventory');

/**
 * Inventory grid panel
 * 
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.InventoryGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Inventory Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Inventory.InventoryItemGridPanel
 */
Tine.Inventory.InventoryItemGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    multipleEdit: true,
    
    initActions: function() {
        this.actions_exportInventoryItem = new Ext.Action({
            text: this.app.i18n._('Export Inventory'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'exportGrant',
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'Inventory.exportInventoryItems',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as CSV'),
                        format: 'csv',
                        iconCls: 'tinebase-action-export-csv',
                        exportFunction: 'Inventory.exportInventoryItems',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ...'),
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'Inventory.exportInventoryItems',
                        showExportDialog: true,
                        gridPanel: this
                    })
                ]
            }
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_exportInventoryItem
        ]);
        
        Tine.Inventory.InventoryItemGridPanel.superclass.initActions.call(this);
    },
        /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actions_exportInventoryItem), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    }
});
