/*
 * Tine 2.0
 * 
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3 @author
 * Alexander Stintzing <a.stintzing@metaways.de> @copyright Copyright (c) 2013
 * Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.PurchaseInvoiceEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 * 
 * <p>
 * Invoice Compose Dialog
 * </p>
 * <p>
 * </p>
 * 
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param {Object}
 *            config
 * @constructor Create a new Tine.Sales.PurchaseInvoiceEditDialog
 */
Tine.Sales.PurchaseInvoiceEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    evalGrants: false,
    
    windowWidth: 900,
    windowHeight: 700,
    displayNotes: true,
    
    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
    isValid: function() {
        return Tine.Sales.PurchaseInvoiceEditDialog.superclass.isValid.call(this);
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
        
        Tine.Sales.PurchaseInvoiceEditDialog.superclass.onRecordLoad.call(this);

        if (this.copyRecord) {
            this.doCopyRecord();
            this.window.setTitle(this.app.i18n._('Copy Purchase Invoice'));
        } else {
            if (! this.record.id) {
                this.window.setTitle(this.app.i18n._('Add New Purchase Invoice'));
            } else {
                this.window.setTitle(String.format(this.app.i18n._('Edit Purchase Invoice "{0}"'), this.record.getTitle()));
            }
        }
        
        // mark some fields as read only
        /*if (this.record.id) {
            var form = this.getForm();
            var ar = ['type', 'contract'];
            for (var index = 0; index < ar.length; index++) {
                form.findField(ar[index]).setReadOnly(1);
            }
        }*/
        
        // mark some more fields as read only
        /*if (this.record.get('cleared') == 'CLEARED') {
            var ar = ['credit_term', 'costcenter_id', 'cleared', 'type'];
            for (var index = 0; index < ar.length; index++) {
                form.findField(ar[index]).setReadOnly(1);
            }
        }*/
    },
    
    onUpdatePriceNet: function() {
        this.calcTax();
        this.calcGross();
        this.calcTotal();
    },
    
    onUpdateSalesTax: function() {
        this.calcTax();
        this.calcGross();
        this.calcTotal();
    },
    
    onUpdatePriceTax: function() {
        this.calcTaxPercent();
        this.calcGross();
        this.calcTotal();
    },
    
    onUpdatePriceGross: function() {
        this.calcTaxFromGross();
        this.calcTotal();
    },
    
    onUpdateDateOfInvoice: function() {
        var dateOfInvoice = this.dateOfInvoiceField.getValue();
        var dueInDays     = parseInt(this.dueInDaysField.getValue());
        
        var dueDate =  dateOfInvoice.clone().add(Date.DAY, dueInDays);
        
        this.dueAtField.setValue(dueDate);
    },
    
    onUpdateInDays: function() {
        this.onUpdateDateOfInvoice();
    },
    
    onUpdateDueAt: function() {
        var dateOfInvoice = this.dateOfInvoiceField.getValue();
        var dueAt         = this.dueAtField.getValue();
        
        // @todo needs improvement 
        // result is incorrect when dueAt is before dateOfInvoice
        // Math.round is used mitigate timeshift changes
        var timeDiff = Math.abs(dueAt.getTime() - dateOfInvoice.getTime());
        var diffDays = Math.round(timeDiff / (1000 * 3600 * 24));
        
        this.dueInDaysField.setValue(diffDays);
    },

    /**
     * calculates total prices by price gross, additional price gross, discount
     */
    calcTotal: function() {
        var priceGross      = parseFloat(this.priceGrossField.getValue());
        var priceGross2     = parseFloat(this.priceGross2Field.getValue());
        var discount        = parseFloat(this.discountField.getValue());

        var total = (priceGross + priceGross2) * (1 - discount/100) * 100;
        var negative = false;
        if (total < 0) {
            negative = true;
            total = Math.abs(total);
        }
        total = Math.round( total ) / 100;
        if ( negative ) {
            total = 0 - total;
        }

        this.priceTotalField.setValue( total );
    },

    /**
     * calculates price gross by price net and tax
     */
    calcGross: function() {
        var netPrice = parseFloat(this.priceNetField.getValue());
        var tax      = parseFloat(this.priceTaxField.getValue());
        
        this.priceGrossField.setValue(netPrice + tax);
    },
    
    /**
     * calculates price gross by price net and tax
     */
    calcTax: function() {
        var netPrice   = parseFloat(this.priceNetField.getValue());
        var taxPercent = parseFloat(this.salesTaxField.getValue());
        
        var tax        = netPrice * (taxPercent / 100);
        var negative = false;
        if (tax < 0) {
            tax = Math.abs(tax);
            negative = true;
        }
        var roundedTax = Math.round(tax * 100) / 100;
        if ( negative ) {
            roundedTax = 0 - roundedTax;
        }
        
        this.priceTaxField.setValue(roundedTax);
    },

    /**
     * Calculate Tax and Tax percent from Gross and Net
     */
    calcTaxFromGross: function() {
        var grossPrice = parseFloat(this.priceGrossField.getValue());
        var netPrice   = parseFloat(this.priceNetField.getValue());
        if (!netPrice) {
            return;
        }
        var tax = grossPrice - netPrice;
        var taxPercent =  tax * 100 / netPrice;
        
        this.priceTaxField.setValue(tax);
        this.salesTaxField.setValue(taxPercent.toFixed(2));
    },
    
    /**
     * calculates price gross by price net and tax
     */
    calcTaxPercent: function() {
        var netPrice = parseFloat(this.priceNetField.getValue());
        var tax      = parseFloat(this.priceTaxField.getValue());
        
        var taxPercent = tax / netPrice * 100;

        var roundedPercent = Math.round(Math.abs(taxPercent) * 100) / 100;

        this.salesTaxField.setValue(roundedPercent);
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
        
        this.dateOfInvoiceField = Ext.create({
            xtype: 'datefield',
            name: 'date',
            fieldLabel: this.app.i18n._('Date of invoice'),
            columnWidth: 1/4,
            allowBlank: false,
            listeners: {
                scope: this,
                blur: this.onUpdateDateOfInvoice.createDelegate(this)
            }
        });
        
        this.dueInDaysField = Ext.create({
            fieldLabel: this.app.i18n._('Due in'),
            columnWidth: 1/4,
            name: 'due_in',
            allowBlank: false,
            xtype: 'uxspinner',
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                alternateIncrementValue: 10,
                minValue: 0,
                maxValue: 1024,
                allowDecimals: false
            }),
            listeners: {
                scope: this,
                blur: this.onUpdateInDays.createDelegate(this)
            }
        });
        
        this.dueAtField = Ext.create({
            xtype: 'datefield',
            name: 'due_at',
            allowBlank: false,
            fieldLabel: this.app.i18n._('Due date'),
            columnWidth: 1/4,
            listeners: {
                scope: this,
                blur: this.onUpdateDueAt.createDelegate(this)
            }
        });
        
        this.priceNetField = new Ext.ux.form.MoneyField({
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Price Net'),
            name: 'price_net',
            columnWidth: 1/4,
            listeners: {
                scope: this,
                blur: this.onUpdatePriceNet.createDelegate(this)
            }
        });
        
        this.priceGrossField = new Ext.ux.form.MoneyField({
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Price Gross'),
            name: 'price_gross',
            columnWidth: 1/4,
            listeners: {
                scope: this,
                blur: this.onUpdatePriceGross.createDelegate(this)
            }
        });

        this.priceGross2Field = new Ext.ux.form.MoneyField({
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Additional Price Gross'),
            name: 'price_gross2',
            columnWidth: 1/4,
            listeners: {
                scope: this,
                blur: this.calcTotal.createDelegate(this)
            }
        });

        this.priceTotalField = new Ext.ux.form.MoneyField({
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Total Price'),
            name: 'price_total',
            columnWidth: 1 / 4,
            listeners: {
                scope: this,
                blur: this.calcTotal.createDelegate(this)
            }
        });
        
        this.priceTaxField = new Ext.ux.form.MoneyField({
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Price Tax'),
            name: 'price_tax',
            disabled: false,
            columnWidth: 1 / 4,
            listeners: {
                scope: this,
                blur: this.onUpdatePriceTax.createDelegate(this)
            }
        });
        
        this.salesTaxField = Ext.create({
            xtype: 'uxspinner',
            decimalPrecision: 2,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                alternateIncrementValue: 0.1,
                minValue: 0,
                maxValue: 100,
                allowDecimals: 2
            }),
            name: 'sales_tax',
            decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
            fieldLabel: this.app.i18n._('Sales Tax (percent)'),
            columnWidth: 1/4,
            regex: /^[0-9]+\.?[0-9]*$/,
            listeners: {
                scope: this,
                spin: this.onUpdateSalesTax.createDelegate(this),
                blur: this.onUpdateSalesTax.createDelegate(this)
            }
        });
        
        this.discountField = Ext.create({
            xtype: 'uxspinner',
            decimalPrecision: 2,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                minValue: 0,
                maxValue: 100,
                allowDecimals: 0
            }),
            name: 'discount',
            decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
            fieldLabel: this.app.i18n._('Discount (%)'),
            columnWidth: 1/4,
            value: 0,
            suffix: ' %',
            listeners: {
                scope: this,
                spin: this.calcTotal.createDelegate(this),
                blur: this.calcTotal.createDelegate(this)
            }
        });
        
        var items = [{
            title: this.app.i18n._('Purchase Invoice'),
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
                        items: [
                            [{
                                name: 'number',
                                fieldLabel: this.app.i18n._('Invoice Number'),
                                columnWidth: 1/4,
                                allowBlank: false
                                //readOnly: ! Tine.Tinebase.common.hasRight('set_invoice_number', 'Sales'),
                                //emptyText: this.app.i18n._('automatically set...')
                            }, {
                                fieldLabel: this.app.i18n._('Supplier'),
                                columnWidth: 3/4,
                                editDialog: this,
                                xtype: 'tinerelationpickercombo',
                                allowBlank: false,
                                app: 'Sales',
                                recordClass: Tine.Sales.Model.Supplier,
                                relationType: 'SUPPLIER',
                                relationDegree: 'sibling',
                                modelUnique: true,
                                ref: '../../../../../../../supplierPicker',
                                //readOnly: true,
                                name: 'supplier'
                            }], [
                                this.dateOfInvoiceField,
                                this.dueInDaysField,
                                this.dueAtField
                                /*{
                                    xtype: 'extuxclearabledatefield',
                                    name: 'overdue_at',
                                    fieldLabel: this.app.i18n._('Overdue date'),
                                    columnWidth: 1/4
                                    //emptyText: (this.record.get('is_auto') == 1) ? this.app.i18n._('automatically set...') : ''
                            }*/], [
                                this.priceNetField,
                                this.salesTaxField,
                                this.priceTaxField,
                                this.priceGrossField
                            ], [
                                this.priceGross2Field,
                                this.discountField,
                                {
                                    xtype: 'extuxclearabledatefield',
                                    name: 'discount_until',
                                    //allowBlank: false,
                                    fieldLabel: this.app.i18n._('Discount until'),
                                    columnWidth: 1/4
                                    //emptyText: (this.record.get('is_auto') == 1) ? this.app.i18n._('automatically set...') : ''
                                }
                            ], [
                                this.priceTotalField
                            ]
                        ]
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
                            [
                                {
                                    xtype: 'extuxclearabledatefield',
                                    name: 'dunned_at',
                                    fieldLabel: this.app.i18n._('Dun date'),
                                    columnWidth: 1/4
                                }, {
                                    xtype: 'extuxclearabledatefield',
                                    name: 'payed_at',
                                    //allowBlank: false,
                                    fieldLabel: this.app.i18n._('Payed at'),
                                    columnWidth: 1/4
                                    //emptyText: (this.record.get('is_auto') == 1) ? this.app.i18n._('automatically set...') : ''
                                },
                                // is_payed
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'Sales',
                                    keyFieldName: 'paymentMethods',
                                    fieldLabel: this.app.i18n._('Method of payment'),
                                    name: 'payment_method',
                                    columnWidth: 1/4
                                })
                            ], [
                                {
                                    columnWidth: 2/4,
                                    editDialog: this,
                                    xtype: 'tinerelationpickercombo',
                                    fieldLabel: this.app.i18n._('Approver'),
                                    allowBlank: true,
                                    app: 'Addressbook',
                                    recordClass: Tine.Addressbook.Model.Contact,
                                    relationType: 'APPROVER',
                                    relationDegree: 'sibling',
                                    modelUnique: true
                                }, {
                                    columnWidth: 1/4,
                                    editDialog: this,
                                    xtype: 'tinerelationpickercombo',
                                    fieldLabel: this.app.i18n._('Lead Cost Center'),
                                    allowBlank: true,
                                    app: 'Sales',
                                    recordClass: Tine.Sales.Model.CostCenter,
                                    relationType: 'COST_CENTER',
                                    relationDegree: 'sibling',
                                    modelUnique: true
                                }
                                /*new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'Sales',
                                    keyFieldName: 'invoiceCleared',
                                    fieldLabel: this.app.i18n._('Cleared'),
                                    name: 'cleared',
                                    allowBlank: false,
                                    columnWidth: 1/3
                                })*/
                            ]
                        ]
                    }]
                }]
            }, {
                // activities and tags
                layout: 'ux.multiaccordion',
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
                        title: this.app.i18n._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        border: false,
                        items: [{
                            style: 'margin-top: -4px; border 0px;',
                            labelSeparator: '',
                            xtype: 'textarea',
                            name: 'description',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars: false,
                            anchor: '100% 100%',
                            emptyText: this.app.i18n._('Enter description'),
                            requiredGrant: 'editGrant'
                        }]
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Sales',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        }];
        
        items.push(new Tine.widgets.activities.ActivitiesTabPanel({
            app:           this.appName,
            record_id:     this.record.id,
            record_model: 'Sales_Model_PurchaseInvoice'
        }));
        
        return {
            xtype:     'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border:    false,
            plain:     true,
            activeTab: 0,
            items:     items
        };
    }
});
