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
        
        this.actions_importInventoryItem = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.app.i18n._('Import items'),
            disabled: false,
            handler: this.onImport,
            iconCls: 'action_import',
            scope: this,
            allowMultiple: true
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_exportInventoryItem,
            this.actions_importInventoryItem
        ]);
        
        
        Tine.Inventory.InventoryItemGridPanel.superclass.initActions.call(this);
    },
    
    /**
     * import inventory items
     * 
     * @param {Button} btn 
     * 
     * TODO generalize this & the import button
     */
    onImport: function(btn) {
        var popupWindow = Tine.widgets.dialog.ImportDialog.openWindow({
            appName: 'Inventory',
            modelName: 'InventoryItem',
            defaultImportContainer: this.app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer('defaultInventoryItemContainer'),
            listeners: {
                scope: this,
                'finish': function() {
                    this.loadGridData({
                        preserveCursor:     false, 
                        preserveSelection:  false, 
                        preserveScroller:   false,
                        removeStrategy:     'default'
                    });
                }
            }
        });
    },
       
    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            {
                xtype: 'buttongroup',
                columns: 1,
                frame: false,
                items: [
                    Ext.apply(new Ext.Button(this.actions_exportInventoryItem), {
                        scale: 'small',
                        rowspan: 1,
                        iconAlign: 'left'
                    }),
                    Ext.apply(new Ext.Button(this.actions_importInventoryItem), {
                        scale: 'small',
                        rowspan: 1,
                        iconAlign: 'left'
                    })
                ]
            }
        ];
    }
});
