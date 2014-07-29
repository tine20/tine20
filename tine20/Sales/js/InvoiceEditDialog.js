/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoiceEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Invoice Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoiceEditDialog
 */
Tine.Sales.InvoiceEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    tbarItems: null,
    evalGrants: false,
    
    /**
     * autoset
     * 
     * @type combo
     */
    addressPicker: null,
    
    /**
     * autoset
     * 
     * @type combo
     */
    customerPicker: null,
    
    /**
     * autoset
     * 
     * @type combo
     */
    contractPicker: null,
    
    createReversal: false,
    
    windowWidth: 800,
    windowHeight: 600,
    
    initComponent: function() {
        this.tbarItems = [{xtype: 'widget-activitiesaddbutton'}];
        Tine.Sales.InvoiceEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = Tine.Sales.InvoiceEditDialog.superclass.isValid.call(this);
        return isValid;
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        if (this.createReversal) {
            var originalRecord = this.recordProxy.recordReader({responseText: Ext.encode(this.record.data)}); ;
            
            this.record.set('cleared', 'TO_CLEAR');
            this.record.set('number', null);
            this.record.set('date', null);
            this.record.set('type', 'REVERSAL');
            
            this.record.set('positions', []);
            
            var relations = this.record.get('relations');
            var newRelations = [];
            var allowedRelations = ['Sales_Model_Customer', 'Sales_Model_CostCenter', 'Sales_Model_Contract'];
            Ext.each(relations, function(relation, index) {
                if (allowedRelations.indexOf(relation.related_model) > -1) {
                    relation.id = null;
                    newRelations.push(relation);
                }
            });
            
            newRelations.push({
                own_degree:      'sibling',
                own_backend:     'Sql',
                related_id:      originalRecord.get('id'),
                related_record:  originalRecord.data,
                related_model:   'Sales_Model_Invoice',
                related_backend: 'Sql',
                own_model:       'Sales_Model_Invoice',
                type:            'REVERSAL'
            });
            
            this.record.set('relations', newRelations);
        }
        
        // this will start preparing the gridpanels for the invoice positions
        if (this.positionsPanel) {
            this.positionsPanel.positions = this.record.get('positions');
            this.positionsPanel.invoiceId = this.record.get('id');
        }
        
        Tine.Sales.InvoiceEditDialog.superclass.onRecordLoad.call(this);

        if (this.createReversal) {
            this.window.setTitle(this.app.i18n._('Create Reversal Invoice'));
            this.doCopyRecord();
            
        } else if (this.copyRecord) {
            this.doCopyRecord();
            this.window.setTitle(this.app.i18n._('Copy Invoice'));
        } else {
            if (! this.record.id) {
                this.window.setTitle(this.app.i18n._('Add New Invoice'));
            } else {
                this.window.setTitle(String.format(this.app.i18n._('Edit Invoice "{0}"'), this.record.getTitle()));
            }
        }

        if (this.record.id || this.createReversal) {
            var form = this.getForm();
            var ar = ['type', 'contract'];
            for (var index = 0; index < ar.length; index++) {
                form.findField(ar[index]).setReadOnly(1);
            }
        }
        
        if (this.record.get('cleared') == 'CLEARED') {
            var ar = ['credit_term', 'costcenter_id', 'cleared', 'type'];
            for (var index = 0; index < ar.length; index++) {
                form.findField(ar[index]).setReadOnly(1);
            }
        }
        
        this.onAddressLoad();
        if (this.positionsPanel) {
            this.positionsPanel.setTitle(this.app.i18n._('Positions') + ' (' +  (Ext.isArray(this.record.data.positions) ? this.record.data.positions.length : 0) + ')');
        }
    },
    
    /**
     * loads the address to the plaintext field
     */
    onAddressLoad: function(combo, record) {
        var ba = record ? record : ( this.record.get('address_id') ? new Tine.Sales.Model.Address(this.record.get('address_id')) : null);
        if (ba) {
            this.form.findField('fixed_address').setValue(Tine.Sales.renderAddress(ba));
        }
    },
    
    /**
     * loads the full-featured record, if a contract gets selected
     * 
     * @param {Tine.widgets.relation.PickerCombo} combo
     * @param {Tine.Sales.Model.Contract} record
     * @param {Number} index
     */
    onContractLoad: function(combo, record, index) {
        // here we fetch the record again to have the related customer, where we can find the address for
        var proxy = Tine.Sales.contractBackend;
        
        proxy.loadRecord(record, {
            scope: this,
            success: this.onAfterContractLoad,
            failure: Tine.Tinebase.ExceptionHandler.handleRequestException
        });
    },
    
    /**
     * 
     * @param {Tine.Sales.Model.Contract} record
     */
    onAfterContractLoad: function(record, customer) {
        var record = record ? record : this.record;
        var relations = record.get('relations'), foundCostCenter = false;
        var foundCustomer = customer ? customer : null;
        
        if (Ext.isArray(relations)) {
            for (var index = 0; index < relations.length; index++) {
                if (foundCostCenter && foundCustomer) {
                    break;
                }
                if (! foundCustomer && relations[index].related_model == 'Sales_Model_Customer' && relations[index].type == 'CUSTOMER') {
                    foundCustomer = relations[index].related_record;
                } else if (! foundCostCenter && relations[index].related_model == 'Sales_Model_CostCenter' && relations[index].type == 'LEAD_COST_CENTER') {
                    foundCostCenter = relations[index].related_record;
                }
            }
        }
        
        if (foundCustomer) {
            this.customerPicker.setValue(foundCustomer);
            this.customerPicker.combo.fireEvent('select');
            
            if (this.addressPicker.disabled) {
                this.addressPicker.enable();
                
                if (record.get('billing_address_id')) {
                    this.addressPicker.setValue(record.get('billing_address_id'));
                }
            } else {
                this.addressPicker.reset();
            }
            this.addressPicker.lastQuery = null;
            
            this.addressPicker.additionalFilters = [
                {field: 'type', operator: 'not', value: 'delivery'},
                {field: 'customer_id', operator: 'AND', value: [
                    {field: ':id', operator: 'in', value: [foundCustomer.id]}
                ]}
            ];
            
            this.getForm().findField('credit_term').setValue(foundCustomer.credit_term);
        } else {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('No customer assigned'), 
                msg: this.app.i18n._("The selected contract doesn't have a customer assigned, yet. Add a customer to the contract with the contract edit dialog.")
            });
        }
        
        if (foundCostCenter) {
            this.getForm().findField('costcenter_id').setValue(foundCostCenter);
        } else {
            if (! this.record.get('costcenter_id')) {
                Ext.MessageBox.show({
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING,
                    title: this.app.i18n._('No cost center assigned'), 
                    msg: this.app.i18n._("The selected contract doesn't have a cost center assigned, yet. Add a cost center to the contract with the contract edit dialog.")
                });
            }
        }
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
        var formFieldDefaults = {
            xtype: 'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .5
        };
        
        if (this.record.get('is_auto')) {
            this.positionsPanel = new Tine.Sales.InvoicePositionPanel({
                app: this.app,
                title: null
            });
        }
        
        var items = [{
            title: this.app.i18n._('Invoice'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                layout: 'hfit',
                border: false,
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Invoice'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [[
                        {
                            name: 'number',
                            fieldLabel: this.app.i18n._('Invoice Number'),
                            columnWidth: 1/3,
                            readOnly: ! Tine.Tinebase.common.hasRight('set_invoice_number', 'Sales'),
                            emptyText: this.app.i18n._('automatically set...')
                        }, {
                            xtype: 'datefield',
                            name: 'date',
                            fieldLabel: this.app.i18n._('Date'),
                            columnWidth: 1/3,
                            readOnly: true,
                            emptyText: this.app.i18n._('automatically set...')
                        }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Sales',
                            keyFieldName: 'invoiceType',
                            fieldLabel: this.app.i18n._('Type'),
                            name: 'type',
                            columnWidth: 1/3
                        })], [{
                            name: 'description',
                            fieldLabel: this.app.i18n._('Description'),
                            columnWidth: 1,
                            allowBlank: false
                        }], [{
                            fieldLabel: this.app.i18n._('Contract'),
                            columnWidth: 1,
                            listeners: {
                                scope: this,
                                select: this.onContractLoad
                            },
                            editDialog: this,
                            xtype: 'tinerelationpickercombo',
                            allowBlank: false,
                            app: 'Sales',
                            recordClass: Tine.Sales.Model.Contract,
                            relationType: 'CONTRACT',
                            relationDegree: 'sibling',
                            modelUnique: true,
                            ref: '../../../../../../../contractPicker',
                            name: 'contract'
                        }], [{
                            fieldLabel: this.app.i18n._('Customer'),
                            columnWidth: 1,
                            editDialog: this,
                            xtype: 'tinerelationpickercombo',
                            allowBlank: false,
                            app: 'Sales',
                            recordClass: Tine.Sales.Model.Customer,
                            relationType: 'CUSTOMER',
                            relationDegree: 'sibling',
                            modelUnique: true,
                            ref: '../../../../../../../customerPicker',
                            readOnly: true,
                            name: 'customer'
                        }],[
                            Tine.widgets.form.RecordPickerManager.get('Sales', 'Address', {
                                fieldLabel: this.app.i18n._('Billing Address'),
                                name: 'address_id',
                                ref: '../../../../../../../addressPicker',
                                columnWidth: 1,
                                disabled: true,
                                allowBlank: false,
                                listeners: {
                                    scope: this,
                                    select: this.onAddressLoad.createDelegate(this)
                                }
                            })
                        ], [{
                            columnWidth: 1,
                            fieldLabel: '',
                            name: 'fixed_address',
                            xtype: 'textarea',
                            height: 100,
                            readOnly: true
                        }
                        ]]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Miscellaneous'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [
                            [{
                                fieldLabel: this.app.i18n._('Credit Term'),
                                name: 'credit_term',
                                allowBlank: false,
                                xtype: 'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 1,
                                    alternateIncrementValue: 10,
                                    minValue: 0,
                                    maxValue: 1024,
                                    allowDecimals: false
                                })
                            }, Tine.widgets.form.RecordPickerManager.get('Sales', 'CostCenter', {
                                    columnWidth: 1/2,
                                    blurOnSelect: true,
                                    allowBlank: false,
                                    fieldLabel: this.app.i18n._('Cost Center'),
                                    name: 'costcenter_id'
                            })], [
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'Sales',
                                    keyFieldName: 'invoiceCleared',
                                    fieldLabel: this.app.i18n._('Cleared'),
                                    name: 'cleared',
                                    allowBlank: false,
                                    columnWidth: 1/3
                                }), {
                                    xtype: 'datefield',
                                    name: 'start_date',
                                    fieldLabel: this.app.i18n._('Interval Begins'),
                                    columnWidth: 1/3
                                }, {
                                    xtype: 'datefield',
                                    name: 'end_date',
                                    fieldLabel: this.app.i18n._('Interval Ends'),
                                    columnWidth: 1/3
                                }
                            ]
                        ]
                    }]
                }]
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
                        app: 'Sales',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Sales',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        }];
        
        if (this.positionsPanel) {
            items.push(this.positionsPanel);
        }
        
        items.push(new Tine.widgets.activities.ActivitiesTabPanel({
            app: this.appName,
            record_id: this.record.id,
            record_model: 'Sales_Model_Invoice'
        }));
        
        return {
            xtype: 'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border: false,
            plain: true,
            activeTab: 0,
            items: items
        };
    }
});
