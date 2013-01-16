/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
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
 * @author      Stefanie Stamer <s.stamer@metaways.de>
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
    modelName: 'InventoryItem',
    
    windowHeight: 470,
    windowWidth: 800,
    
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: true,
    showContainerSelector: true,
    multipleEdit: true,
    
    /**
     * check validity of activ number field
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
            plain: true,
            activeTab: 0,
            items: [{
                //Start first tab
                title: this.app.i18n._('General'),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        region: 'center',
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype:'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .333,
                            disabled: (this.useMultiple) ? true : false
                        },
                        //Start first line
                        items: [
                            [{
                                columnWidth: 1,
                                xtype: 'tw-uidtriggerfield',
                                fieldLabel: this.app.i18n._('ID'),
                                name: 'inventory_id',
                                maxLength: 100,
                                allowBlank: false
                            }],
                            [{
                                columnWidth: 1,
                                xtype: 'tine.widget.field.AutoCompleteField',
                                recordClass: this.recordClass,
                                fieldLabel: this.app.i18n._('Name'),
                                name: 'name',
                                maxLength: 100,
                                allowBlank: false
                            }]
                        ]
                    },
                    {
                        //Start second line
                        layout: 'hbox',
                        items: [{
                            flex: 1,
                            xtype: 'columnform',
                            autoHeight: true,
                            style:'padding-right: 5px;',
                            items: [
                                [{
                                    xtype: 'textarea',
                                    columnWidth: 1,
                                    name: 'description',
                                    fieldLabel: this.app.i18n._('Description'),
                                    grow: false,
                                    preventScrollbars: false,
                                    height: 150,
                                    emptyText: this.app.i18n._('Enter description')
                                }]
                            ]
                        }/*,
                            new Ext.ux.form.ImageField({
                                name: 'jpegphoto',
                                width: 160,
                                height: 150,
                                style: {
                                    'margin-top': '17px'
                                }
                            })*/
                        ]
                    },
                    {
                        //Start third line
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype:'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .333,
                            disabled: (this.useMultiple) ? true : false
                        },
                        items: [
                            [{
                                columnWidth: 1,
                                xtype: 'tine.widget.field.AutoCompleteField',
                                recordClass: this.recordClass,
                                fieldLabel: this.app.i18n._('Location'),
                                name: 'location',
                                maxLength: 255
                            }],
                            [{
                                xtype: 'extuxclearabledatefield',
                                columnWidth: 0.333,
                                fieldLabel: this.app.i18n._('Added'),
                                name: 'added_date'
                            },
                            {
                                xtype: 'datefield',
                                name: 'warranty',
                                fieldLabel: this.app.i18n._('Warranty'),
                                columnWidth: 0.333
                            },
                            {
                                xtype: 'extuxclearabledatefield',
                                columnWidth: 0.333,
                                fieldLabel: this.app.i18n._('Removed'),
                                name: 'removed_date'
                            }
                            ],
                            [{
                                xtype:'numberfield',
                                columnWidth: 0.333,
                                fieldLabel: this.app.i18n._('Total number'),
                                name: 'total_number',
                                value: 1,
                                minValue: 1
                            },
                            {
                                xtype:'numberfield',
                                columnWidth: 0.333,
                                fieldLabel: this.app.i18n._('Available number'),
                                name: 'active_number',
                                value: 1,
                                minValue: 0
                            },
                            new Tine.Tinebase.widgets.keyfield.ComboBox({
                                app: 'Inventory',
                                keyFieldName: 'inventoryStatus',
                                fieldLabel: this.app.i18n._('Status'),
                                name: 'status',
                                columnWidth: 0.333
                            })
                            ]
                        ]
                    }]
                    
                },
                {
                    //Start side
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
                         new Ext.Panel({
                            title: this.app.i18n._('Additional Information'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'adt_info',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: this.app.i18n._('Enter Information'),
                                requiredGrant: 'editGrant'
                            }]
                        }),
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
                        })
                    ]
                }]
            },
            {
                //Start second tab
                title: this.app.i18n._('Accounting'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [
                    {
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 1,
                        disabled: (this.useMultiple) ? true : false
                    },
                    items: [
                        [{
                            xtype: 'textfield',
                            name: 'price',
                            fieldLabel: this.app.i18n._('Price'),
                            columnWidth: 0.5
                        },
                        {
                            xtype: 'textfield',
                            name: 'costcentre',
                            fieldLabel: this.app.i18n._('Cost centre'),
                            columnWidth: 0.5
                        }],
                        [{
                            xtype: 'textfield',
                            name: 'invoice',
                            fieldLabel: this.app.i18n._('Invoice'),
                            columnWidth: 0.5
                        },
                        {
                            xtype: 'datefield',
                            name: 'invoice_date',
                            fieldLabel: this.app.i18n._('Invoice date'),
                            columnWidth: 0.5
                        }],
                        [{
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: this.app.i18n._('Depreciate'),
                                name: 'depreciate_status'
                        }]
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })
            ]
        }
    },
    isMultipleValid: function() {
        return true;
    }
});