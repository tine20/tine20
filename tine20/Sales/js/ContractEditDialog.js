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
    windowHeight: 600,
    displayNotes: true,

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

        // register additional action for genericpickergridpanel
        if (Tine.Tinebase.common.hasRight('run', 'WebAccounting')) {
            // TODO define additional models / get from registry
            this.setAccountableAction = this.getSetAccountableAction('WebAccounting_Model_ProxmoxVM');
            Tine.log.info('Registering "Set Accountable" action in relation picker ctx menu ...');
            // Ext.ux.ItemRegistry.registerItem('Tinebase-MainContextMenu', this.setAccountableAction, 100);
            Tine.widgets.relation.MenuItemManager.register('WebAccounting', 'ProxmoxVM', this.setAccountableAction);
        }
    },
    
    /**
     * called on multiple edit
     *
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
    },

    /**
     * get action for setting relation accountables
     *
     * @param modelName
     * @returns {{iconCls: string, requiredGrant: string, text, itemId: string, allowMultiple: boolean,
      * scope: Tine.Sales.ContractEditDialog, handler: handler, actionUpdater: actionUpdater}}
     *
     * TODO move to separate file
     * TODO add tests
     */
    getSetAccountableAction: function(modelName) {
        return {
            iconCls:'SalesInvoice',
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Set as accountable'),
            itemId: 'setAccountableAction',
            // TODO prevent selection of multiple records (no action updater?)
            // TODO OR: allow multiple records to be selected!
            // allowMultiple: false,
            scope: this,

            handler: function(action) {
                var relation = action.grid.store.getAt(action.gridIndex),
                    productCombo = Tine.widgets.form.RecordPickerManager.get('Sales', 'ProductAggregate', {
                        name: 'product',
                        additionalFilters: [
                            {field: 'contract_id', operator: 'AND', value: [
                                {field: ':id', operator: 'equals', value: this.record.id}
                            ]}
                        ],
                        sortBy: 'start_date'
                    }),
                    dialog = new Tine.Tinebase.dialog.Dialog({
                        windowTitle: this.app.i18n._('Choose product for accountable'),
                        items: [productCombo],
                        modelName: modelName,
                        relation: relation,
                        contractDialog: this,
                        /**
                         * Creates a new pop up dialog/window (acc. configuration)
                         *
                         * @returns {null}
                         * TODO can we put this in the Tine.Tinebase.dialog.Dialog?
                         */
                        openWindow: function (config) {
                            if (this.window) {
                                return this.window;
                            }

                            config = config || {};

                            this.window = Tine.WindowFactory.getWindow(Ext.apply({
                                title: this.windowTitle,
                                closeAction: 'close',
                                modal: true,
                                width: 300,
                                height: 100,
                                plain: true,
                                layout: 'fit',
                                items: [
                                    this
                                ]
                            }, config));

                            return this.window;
                        },

                        getEventData: function (event) {
                            if (event === 'apply') {
                                return [{
                                    'model': this.modelName,
                                    // product combo is first item
                                    'id': this.items.get(0).getValue()
                                }, this.contractDialog, this.relation];
                            }
                        }
                    });

                dialog.openWindow();
                dialog.on('apply', (eventData) => {
                    let [product, contractDialog, relation] = eventData;
                    // link into product agg json attributes like this:
                    // 'accountables' -> [{"model":"WebAccounting_Model_ProxmoxVM","id":"12345abcde"}]
                    Tine.log.debug(product);
                    var productAgg = contractDialog.productGridPanel.getStore().getById(product.id);
                    if (productAgg) {
                        var attributes = productAgg.get('json_attributes'),
                            accountables = attributes.assignedAccountables && attributes.assignedAccountables != ""
                                ? JSON.parse(attributes.assignedAccountables)
                                : [],
                            accountable = {
                                'model': product.model,
                                'id': relation.get('related_id')
                            };
                        if (Ext.isArray(accountables)) {
                            accountables.push(accountable);
                        } else {
                            accountables = [accountable];
                        }
                        attributes.assignedAccountables = JSON.stringify(accountables);
                        productAgg.set(attributes);
                        productAgg.commit();
                    }
                });
            },
        };
    },
    
    /**
     * containerProperty (all contracts in one container) exists, so overwrite creating selector here
     */
    initContainerSelector: Ext.emptyFn,
    
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
                }
             
                this.setAddressPickerFilter();
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
        this.loadAddress(record);
    },
    
    /**
     * loads the first billing address or the postal 
     * address into the addresspicker on loading a customer
     * 
     * @param {Tine.Sales.Model.Customer} record
     */
    loadAddress: function(record) {
        var billingAddresses = record.get('billing');
        if (Ext.isArray(billingAddresses) && billingAddresses.length > 0) {
            var address = new Tine.Sales.Model.Address(billingAddresses[0]);
        } else {
            var address = new Tine.Sales.Model.Address(record.get('postal_id'));
        }
        
        this.addressPicker.setValue(address.data);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        
        this.productGridPanel = new Tine.Sales.ProductAggregateGridPanel({
            app: this.app,
            editDialog: this,
            title: this.app.i18n._('Positions'),
            editDialogRecordProperty: 'Positions'
        });
        
        var items = [[{
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
                columnWidth: 1,
                disabled: true,
                allowBlank: true
            })], [{
                    xtype: 'extuxclearabledatefield',
                    name: 'start_date',
                    columnWidth: 1/2,
                    fieldLabel: this.app.i18n._('Start Date'),
                    allowBlank: false
                }, {
                    xtype: 'extuxclearabledatefield',
                    name: 'end_date',
                    fieldLabel: this.app.i18n._('End Date'),
                    columnWidth: 1/2
                }], [{
                columnWidth: 1/3,
                xtype: 'tinerelationpickercombo',
                fieldLabel: this.app.i18n._('Contact Person (external)'),
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
                fieldLabel: this.app.i18n._('Contact Person (internal)'),
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
            }]];
            
        items.push([{
            columnWidth: 1,
            fieldLabel: this.app.i18n._('Description'),
            emptyText: this.app.i18n._('Enter description...'),
            name: 'description',
            xtype: 'textarea',
            height: 200
        }]);
        
        return {
            xtype: 'tabpanel',
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
                    items: items
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
                        new Tine.widgets.tags.TagPanel({
                            app: 'Sales',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, this.productGridPanel,
                new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: 'Sales_Model_Contract'
                })
            ]
        };
    }
});
