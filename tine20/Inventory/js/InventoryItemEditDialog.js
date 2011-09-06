/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Inventory');

/**
 * @namespace   Tine.Inventory
 * @class       Tine.Inventory.InventoryItemEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>InventoryItem Compose Dialog</p>
 * <p></p>
 * 
 *  @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Inventory.InventoryItemEditDialog
 */
Tine.Inventory.InventoryItemEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'InventoryItemEditWindow_',
    appName: 'Inventory',
    recordClass: Tine.Inventory.Model.InventoryItem,
    recordProxy: Tine.Inventory.recordBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: true,
    showContainerSelector: true,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
    	// you can do something here

    	Tine.Inventory.InventoryItemEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Inventory.InventoryItemEditDialog.superclass.onRecordUpdate.call(this);
        
        // you can do something here    
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{               
                title: this.app.i18n._('InventoryItem'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Name'),
                        name: 'name',
                        allowBlank: false
                        }], [new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Inventory',
                            keyFieldName: 'inventoryStatus',
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'status',
                            columnWidth: 0.5
                        })]
                    ] 
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'Inventory',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Inventory',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Inventory Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Inventory.InventoryItemEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Inventory.InventoryItemEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Inventory.InventoryItemEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
