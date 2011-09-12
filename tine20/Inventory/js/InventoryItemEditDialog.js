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
     * overwrite update toolbars function (we don t have record grants yet)
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
     * check validity of activ numer field
     */
    
    isValid: function () {
        var form = this.getForm();
        var isValid = true;
        if (form.findField('total_number').getValue() < form.findField('active_number').getValue()) {
            var invalidString = String.format(this.app.i18n._('The active number must be less than or equal total number.'));
            form.findField('active_number').markInvalid(invalidString);
            isValid = false;
        }
        return isValid && Tine.Inventory.InventoryItemEditDialog.superclass.isValid.apply(this, arguments);
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
                title: this.app.i18n._('Inventory Item'),
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
                        xtype: 'tine.widget.field.AutoCompleteField',
                        recordClass: this.recordClass,
                        fieldLabel: this.app.i18n._('Name'),
                        name: 'name',
                        columnWidth: 0.5,
                        allowBlank: false,
                        maxLength: 100
                        }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Inventory',
                            keyFieldName: 'inventoryType',
                            fieldLabel: this.app.i18n._('Type'),
                            name: 'type',
                            columnWidth: 0.5,
                            allowBlank: false,
                            maxLength: 100
                        })],
                        [{
                            columnWidth: 1,
                            fieldLabel: this.app.i18n._('ID'),
                            name: 'inventory_id',
                            maxLength: 100
                        }],
                        [{
                        	xtype: 'textarea',
                            name: 'description',
                            fieldLabel: this.app.i18n._('Description'),
                            grow: false,
                            preventScrollbars: false,
                            columnWidth: 1,
                            height: 150,
                            emptyText: this.app.i18n._('Enter description')
                        }],
                        [{
                            columnWidth: 0.5,
                            xtype: 'tine.widget.field.AutoCompleteField',
                            recordClass: this.recordClass,
                            fieldLabel: this.app.i18n._('Location'),
                            name: 'location',
                            maxLength: 255
                            
                        },
                        {
                        	xtype: 'extuxclearabledatefield',
                            columnWidth: 0.5,
                            fieldLabel: this.app.i18n._('Added'),
                            name: 'add_time'
                            
                        }],
                        [{
                        	
                        	xtype:'numberfield',
                            columnWidth: 0.5,
                            fieldLabel: this.app.i18n._('Total number'),
                            name: 'total_number',
                            //value: null,
                            value: 1,
                            //minValue: 1
                            
                        },
                        {
                        	xtype:'numberfield',
                            columnWidth: 0.5,
                            fieldLabel: this.app.i18n._('Active number'),
                            name: 'active_number',
                            //value: null,
                            value: 1,
                            //minValue: 0
                            
                        }],
                        
                    ] 
                   
                },
                {
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
