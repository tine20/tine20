/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Contract edit dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Contract Edit Dialog</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ContractGridPanel
 */
Tine.Sales.ContractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 800,
    windowHeight: 490,

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
    addressPicker: null,
    
    /**
     * if true, number will be readOnly and will be generated automatically
     * @type {Boolean} autoGenerateNumber
     */
    autoGenerateNumber: null,
    
    /**
     * how should the number be validated text/integer possible
     * @type {String} validateNumber
     */
    validateNumber: null,
    
    initComponent: function() {
        this.autoGenerateNumber = (Tine.Sales.registry.get('config').contractNumberGeneration.value == 'auto') ? true : false;
        this.validateNumber = Tine.Sales.registry.get('config').contractNumberValidation.value;
        Tine.Sales.ContractEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * called on multiple edit
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
    },
    
    /**
     * extra validation for the number field, calls parent
     * @return {Boolean}
     */
    isValid: function() {
        var valid = Tine.Sales.ContractEditDialog.superclass.isValid.call(this);
        var isValid = this.autoGenerateNumber ? true : (this.validateNumber == 'integer') ? Ext.isNumber(Ext.num(this.getForm().findField('number').getValue())) : true;
        if (!isValid) {
            this.getForm().findField('number').markInvalid(this.app.i18n._('Please use a decimal number here!'));
        }
        return isValid && valid;
    },
    
    /**
<<<<<<< HEAD
=======
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
        
        Tine.Sales.ContractEditDialog.superclass.onRecordLoad.call(this);
        
        if (this.record.id) {
            var relations = this.record.get('relations'), foundCustomer = false;
            
            if (Ext.isArray(relations)) {
                for (var index = 0; index < relations.length; index++) {
                    if (relations[index].related_model == 'Sales_Model_Customer') {
                        if (relations[index].type == 'CUSTOMER') {
                            foundCustomer = relations[index].related_record;
                        }
                    }
                }
            }
            
            if (foundCustomer) {
                this.addressPicker.enable();
                var ba = this.record.get('billing_address_id');
                
                if (ba && ba.hasOwnProperty('locality')) {
                    var record = new Tine.Sales.Model.Address(ba);
                    this.onAddressLoad(null, record, null);
                } else {
                    this.setAddressPickerFilter();
                }
            }
        }
    },
    
    /**
     * sets the filter used for the addresspicker by the selected customer
     */
    setAddressPickerFilter: function() {
        this.addressPicker.additionalFilters = [
            {field: 'type', operator: 'not', value: 'delivery'},
            {field: 'customer_id', operator: 'AND', value: [
                {field: ':id', operator: 'in', value: [this.customerPicker.getValue()] }
            ]}
        ];
    },
    
    /**
     * loads the full-featured record, if a contract gets selected
     * 
     * @param {Tine.widgets.relation.PickerCombo} combo
     * @param {Tine.Sales.Model.Contract} record
     * @param {Number} index
     */
    onCustomerLoad: function(combo, record, index) {
        if (this.addressPicker.disabled) {
            this.addressPicker.enable();
        } else {
            this.addressPicker.reset();
        }
        
        this.addressPicker.lastQuery = null;
        
        this.setAddressPickerFilter();
    },
    
    /**
>>>>>>> create invoice module
     * 
     * @param {Tine.widgets.relation.PickerCombo} combo
     * @param {Tine.Sales.Model.Address} record
     * @param {number} index
     */
    onAddressLoad: function(combo, record, index) {
        this.getForm().findField('show_city').setValue(record.get('postalcode') + ' ' + record.get('locality'));
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Contract', 'Contracts', 1),
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
                        columnWidth: .25,
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        multiEditable: false,
                        readOnly: this.autoGenerateNumber,
                        allowBlank: this.autoGenerateNumber
                    },{
                        columnWidth: .75,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                    }], [{
                        columnWidth: 1,
                        editDialog: this,
                        xtype: 'tinerelationpickercombo',
                        allowBlank: true,
                        app: 'Sales',
                        recordClass: Tine.Sales.Model.Customer,
                        relationType: 'CUSTOMER',
                        relationDegree: 'sibling',
                        modelUnique: true,
                        listeners: {
                            scope: this,
                            select: this.onCustomerLoad
                        },
                        ref: '../../../../../customerPicker',
                        fieldLabel: this.app.i18n._('Customer')
                    }], [ Tine.widgets.form.RecordPickerManager.get('Sales', 'Address', {
                            fieldLabel: this.app.i18n._('Billing Address'),
                            name: 'billing_address_id',
                            ref: '../../../../../addressPicker',
                            columnWidth: 1/2,
                            disabled: true,
                            allowBlank: true,
                            listeners: {
                                scope: this,
                                select: this.onAddressLoad
                            }
                        }), {
                            fieldLabel: this.app.i18n._('Billing Address Locality'),
                            columnWidth: 1/2,
                            name: 'show_city',
                            readOnly: true
                        }], [{
                                xtype: 'datefield',
                                name: 'start_date',
                                columnWidth: 1/6,
                                fieldLabel: this.app.i18n._('Start Date'),
                                allowBlank: false
                            }, {
                                xtype: 'datefield',
                                name: 'end_date',
                                fieldLabel: this.app.i18n._('End Date'),
                                columnWidth: 1/6
                            }, {
                                columnWidth: 2/6,
                                name: 'interval',
                                fieldLabel: this.app.i18n._('Billing Interval'),
                                xtype: 'combo',
                                store: [0,1,2,3,4,5,6,7,8,9,10,11,12]
                            }, {
                                name: 'billing_point',
                                fieldLabel: this.app.i18n._('Billing Point'),
                                xtype: 'combo',
                                store: [
                                    ['begin', this.app.i18n._('begin') ],
                                    [  'end', this.app.i18n._('end') ]
                                ],
                                columnWidth: 2/6
                        }], [{
                            columnWidth: 1/3,
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Contract Contact'),
                            editDialog: this,
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'CUSTOMER',
                            relationDegree: 'sibling',
                            modelUnique: true
                        }, {
                            columnWidth: 1/3,
                            editDialog: this,
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Account Manager'),
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'RESPONSIBLE',
                            relationDegree: 'sibling',
                            modelUnique: true
                        }, {
                            columnWidth: 1/3,
                            editDialog: this,
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Lead Cost Center'),
                            allowBlank: true,
                            app: 'Sales',
                            recordClass: Tine.Sales.Model.CostCenter,
                            relationType: 'LEAD_COST_CENTER',
                            relationDegree: 'sibling',
                            modelUnique: true
                        }],[
                        
                    ], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }]] 
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
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});
