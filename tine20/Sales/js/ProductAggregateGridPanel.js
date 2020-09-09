/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

require('./ProductAggregateLayerCombo');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ProductAggregateGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Product aggregate grid panel</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ProductAggregateGridPanel
 */
Tine.Sales.ProductAggregateGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    /*
     * config
     */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: {field: 'product_id', direction: 'DESC'},
    quickaddMandatory: 'product_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    recordClass: 'Sales.ProductAggregate',
    validate: true,

    /*
     * public
     */
    app: null,
    
    /**
     * the calling editDialog
     * Tine.Sales.ContractEditDialog
     */
    editDialog: null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        this.title = this.i18nTitle = this.app.i18n.ngettext('Product', 'Products', 2),
        
        Tine.Sales.ProductAggregateGridPanel.superclass.initComponent.call(this);
        this.store.sortInfo = this.defaultSortInfo;

        this.on('afteredit', this.onAfterEdit, this);
        this.editDialog.on('load', this.loadRecord, this);
        
        // custom sort for product_id
        this.store.sortData = (function(f, direction) {
            if (f !== 'product_id') {
                Ext.data.Store.prototype.sortData.call(this.store, f, direction);
            } else {
                direction = direction || 'ASC';
                var fn = function(r1, r2) {
                    var v1 = r1.data.product_id.description,
                        v2 = r2.data.product_id.description;
                    
                    return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
                };
                
                this.store.data.sort(direction, fn);
            }
        }).createDelegate(this);
        
        this.store.sort();
        
        this.on('beforeedit', this.onBeforeRowEdit, this);
        
        // sync record on these events
        this.store.on('update', this.syncStoreToRecord.createDelegate(this));
        this.store.on('add', this.syncStoreToRecord.createDelegate(this));
        this.store.on('remove', this.syncStoreToRecord.createDelegate(this));
    },
    
    /**
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    syncStoreToRecord: function(store, record, operation) {
        if (this.editDialog.record) {
            var items = [];
            store.each(function(item) {
                if (! item.data.last_autobill) {
                    item.data.last_autobill = null;
                }
                items.push(item.data);
            });
            this.editDialog.record.set('products', items);
            
            this.updateTitle(items.length);
        }
    },
    
    /**
     * updates the title of the tab by adding the number of containing records in braces
     * 
     * @param {Number} count
     */
    updateTitle: function(count) {
        count = Ext.isNumber(count) ? count : this.store.getCount();
        this.setTitle(this.i18nTitle + ' (' + count + ')');
    },
    
    /**
     * loads the existing ProductAggregates into the store
     */
    loadRecord: function() {
        var c = this.editDialog.record.get('products');
        if (Ext.isArray(c)) {
            Ext.each(c, function(ar) {
                this.store.addSorted(new this.recordClass(ar));
            }, this);
        }
    },
    
    /**
     * new entry event -> add new record to store
     * @see Tine.widgets.grid.QuickaddGridPanel
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        recordData.contract_id = this.editDialog.record.get('id');
        var relatedRecord = this.productQuickadd.store.getById(this.productQuickadd.getValue());
        recordData.product_id = relatedRecord.data;
        Tine.Sales.ProductAggregateGridPanel.superclass.onNewentry.call(this, recordData);  
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        this.productEditor = Tine.widgets.form.RecordPickerManager.get('Sales', 'Product', { allowBlank: true});
        
        this.quantityEditor = new Ext.ux.form.Spinner({
            fieldLabel: this.app.i18n._('Quantity'),
            name: 'quantity',
            allowBlank: true,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                minValue: 1,
                maxValue: 9999999,
                allowDecimals: false
            })
        });

        this.intervalEditor = new Ext.ux.form.Spinner({
            fieldLabel: this.app.i18n._('Interval'),
            name: 'interval',
            allowBlank: false,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                minValue: 1,
                maxValue: 36,
                allowDecimals: false
            })
        });
        
        this.productQuickadd = Tine.widgets.form.RecordPickerManager.get('Sales', 'Product', {allowBlank: true});
        this.quantityQuickadd = new Ext.ux.form.Spinner({
            fieldLabel: this.app.i18n._('Quantity'),
            name: 'quantity',
            allowBlank: true,
            value: null,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                minValue: 1,
                maxValue: 9999,
                allowDecimals: false
            })
        });
        this.intervalQuickadd = new Ext.ux.form.Spinner({
            fieldLabel: this.app.i18n._('Interval'),
            name: 'interval',
            allowBlank: false,
            value: 1,
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : 1,
                minValue: 1,
                maxValue: 36,
                allowDecimals: false
            })
        });
        
        var cmp = {
            name: 'billing_point',
            fieldLabel: this.app.i18n._('Billing Point'),
            xtype: 'combo',
            value: 'end',
            store: [
                ['begin', this.app.i18n._('begin') ],
                [  'end', this.app.i18n._('end') ]
            ]
        };
        
        this.billingPointEditor = new Ext.form.ComboBox(cmp);
        this.billingPointQuickadd = new Ext.form.ComboBox(cmp);
        
        var columns = [
            {id: 'product_id', dataIndex: 'product_id', type: Tine.Sales.Model.ProductAggregate, header: this.app.i18n._('Product'),
                 quickaddField: this.productQuickadd, renderer: this.renderProductAggregate,
                 editor: this.productEditor, scope: this, width: 150
            },
            {id: 'quantity', editor: this.quantityEditor, renderer: this.renderQuantity, quickaddField: this.quantityQuickadd, dataIndex: 'quantity', header: this.app.i18n._('Quantity'),  scope: this, width: 54},
            {id: 'interval', editor: this.intervalEditor, quickaddField: this.intervalQuickadd, dataIndex: 'interval', header: this.app.i18n._('Interval'),  scope: this, width: 60},
            {id: 'billing_point', renderer: this.renderBillingPoint , editor: this.billingPointEditor, quickaddField: this.billingPointQuickadd, dataIndex: 'billing_point', header: this.app.i18n._('Billing Point'),  scope: this, width: 140},
            {id: 'start_date', renderer: Tine.Tinebase.common.dateRenderer, editor: new Ext.ux.form.ClearableDateField(), quickaddField: new Ext.ux.form.ClearableDateField(), dataIndex: 'start_date', header: this.app.i18n._('Start Date'),  scope: this, width: 110},
            {id: 'end_date', renderer: Tine.Tinebase.common.dateRenderer, editor: new Ext.ux.form.ClearableDateField(), quickaddField: new Ext.ux.form.ClearableDateField(), dataIndex: 'end_date', header: this.app.i18n._('End Date'),  scope: this, width: 110},
            {id: 'json_attributes', renderer: this.renderAttributes, dataIndex: 'json_attributes', header: this.app.i18n._('Attributes'),  scope: this, width: 300},
            {id: 'last_autobill', renderer: Tine.Tinebase.common.dateRenderer, editor: new Ext.ux.form.ClearableDateField(), hidden: true, dataIndex: 'last_autobill', header: this.app.i18n._('Last Autobill'),  scope: this, width: 110},
            {id: 'creation_time',      header: i18n._('Creation Time'),         dataIndex: 'creation_time',         renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
            {id: 'created_by',         header: i18n._('Created By'),            dataIndex: 'created_by',            renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true },
            {id: 'last_modified_time', header: i18n._('Last Modified Time'),    dataIndex: 'last_modified_time',    renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
            {id: 'last_modified_by',   header: i18n._('Last Modified By'),      dataIndex: 'last_modified_by',      renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true }
        ];

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width: 160,
                editable: true
            }, 
            columns: columns
       });
    },

    /**
     * TODO let accountable define its presentation
     * TODO add qtip?
     *
     * @param value
     * @returns {string}
     */
    renderAttributes: function(value, cell, record) {
        // let qtipText = JSON.stringify(value);
        let result = [];

        _.forOwn(value, function(value, key) {
            if (key !== 'assignedAccountables') {
                result.push(key + ': ' + value);
            } else {
                result.push('#: ' + value.length);
            }
        });

        // let result = '<div ext:qtip="' + qtipText + '">' + attributes + '</div>';
        return result.join('/',);
    },
    
    /**
     * renders the billing point
     * 
     * @param {String} value
     * @return {String}
     */
    renderBillingPoint: function(value) {
        if (value == 'end') {
            return this.app.i18n._('end');
        } else {
            return this.app.i18n._('begin');
        }
    },
    
    renderQuantity: function(value, cell, record) {
        if (this.hasQuantity(record)) {
            return value;
        }
        
        return '';
    },

    /**
     * accountables do not have a quantity
     *
     * @param record
     * @returns {boolean}
     *
     * TODO this should be refactored...
     */
    hasQuantity: function(record) {
        // product does not bill an accountable -> return qty
        var ac = record.get('product_id').accountable;
        return (
            ! ac
            // TODO find a better way here to determine if record has quantity
            || ac == 'Sales_Model_ProductAggregate'
            || ac == 'Sales_Model_Product'
            || ac == 'WebAccounting_Model_ProxmoxVM'
        );
    },
    
    /**
     * is called on after edit to set related records
     * @param {} o
     */
    onAfterEdit: function(o) {  
        switch (o.field) {
            case 'quantity':
                o.record.set('quantity', o.value);
                break;
            case 'product_id':
                var relatedRecord = this.productEditor.store.getById(o.value);
                if (relatedRecord) {
                    o.record.set('product_id', relatedRecord.data);
                }
                break;
            case 'interval':
                o.record.set('interval', o.value);
                break;
            case 'billing_point':
                var val = o.value;
                if (Ext.isEmpty(val)) {
                    val = 'begin';
                }
                o.record.set('billing_point', val);
                break;
            default: // do nothing
        }
    },
    
    /**
     * creates the special editors
     * @param {} o
     */
    onBeforeRowEdit: function(o) {
        if (o.field == 'quantity') {
            if (this.hasQuantity(o.record)) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    },
    
    /**
     * renders the cost center
     * @param {Object} value
     * @param {Object} row
     * @param {Tine.Tinebase.data.Record} record
     * 
     * return {String}
     */
    renderProductAggregate: function(value, row, record) {
        return '<span class="tine-recordclass-gridicon SalesProduct">&nbsp;</span>' + (record ? record.getTitle() : '');
    },

    /**
     * @private
     */
    initActions: function() {
        Tine.Sales.ProductAggregateGridPanel.superclass.initActions.call(this);

        // TODO prevent multiselect and change of records without defined attributes
        this.editAttributesAction = new Ext.Action({
            text: this.app.i18n._('Edit Attributes'),
            iconCls: 'actionEdit',
            handler : this.onEditAttributes,
            scope: this
        });
    },

    /**
     * edit attributes: open modal window with key/value grid
     */
    onEditAttributes: function() {
        var selectedRows = this.getSelectionModel().getSelections(),
            selectedRecord = selectedRows[0],
            attributeKeys = this.getAttributeKeysFromAccountable(selectedRecord);

        if (attributeKeys.length == 0) {
            // no attribute keys -> return
            return;
        }

        this.initAttributesCombos(attributeKeys);
        var cols = [
            {
                id: 'id',
                // TODO pass this.app.i18n
                header: i18n._('Attribute'),
                dataIndex: 'id',
                hideable: false,
                sortable: false,
                editor: false,
                quickaddField: this.attributesQuickadd
            }, {
                id: 'value',
                header: i18n._('Value'),
                dataIndex: 'value',
                hideable: false,
                sortable: false,
                editor: new Ext.form.TextField({}),
                quickaddField: new Ext.form.TextField({
                    emptyText: i18n._('Add a New Value...')
                }),
                renderer: this.valueRenderer
            }
        ];

        this.attributesGrid = new Tine.Tinebase.widgets.keyfield.ConfigGrid({
            hasDefaultCheck: false,
            cols: cols
        });
        this.attributesGrid.on('beforeedit', this.onBeforeValueEdit, this);
        this.loadAttributesFromRecord(selectedRecord, attributeKeys);

        this.attributesWindow = Tine.WindowFactory.getWindow({
            modal: true,
            width: 500,
            height: 320,
            border: false,
            // maybe we should create a common ancestor for ConfigGrid and json attributes grid
            items: this.attributesGrid,
            fbar: ['->', {
                text: i18n._('Cancel'),
                minWidth: 70,
                scope: this,
                handler: this.onAttributesWindowClose,
                iconCls: 'action_cancel'
            }, {
                text: i18n._('OK'),
                minWidth: 70,
                scope: this,
                handler: this.onAttributesWindowOK,
                iconCls: 'action_applyChanges'
            }]
        });
    },

    onBeforeValueEdit: function(o) {
        if (o.field != 'value') {
            o.cancel = true;
        }

        else {
            var colModel = o.grid.getColumnModel(),
                type = o.record.get('id');

            if (type === 'assignedAccountables') {
                colModel.config[o.column].setEditor(new Tine.Sales.ProductAggregateAccountableLayerCombo({
                    recordClass: Tine.WebAccounting.Model.ProxmoxVM
                }));
            } else {
                colModel.config[o.column].setEditor(new Ext.form.TextField({}));
            }
        }
    },

    valueRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        if (record.get('id') === 'assignedAccountables') {
            var labels = [];
            _.each(value, function(item) {
                labels.push(item.vm_name);
            });
            return labels.join(', ');
        } else {
            return value;
        }
    },

    initAttributesCombos: function(attributeKeys) {
        var storeData = [];
        Ext.each(attributeKeys, function(key) {
            // TODO add translation?
            storeData.push([key, key]);
        });
        var cmp = {
            name: 'attributes',
            fieldLabel: this.app.i18n._('Attribute'),
            xtype: 'combo',
            value: 'vcpus',
            store: storeData
        };

        this.attributeEditor = new Ext.form.ComboBox(cmp);
        this.attributesQuickadd = new Ext.form.ComboBox(cmp);
    },

    getAttributeKeysFromAccountable: function(record) {
        var accountable = record.get('product_id').accountable;

        if (accountable == 'WebAccounting_Model_ProxmoxVM') {
            // TODO get accountable keys from modelconfig / registry (Tine.WebAccounting.registry.get('models')[MODEL])
            return ['vcpus', 'memory', 'storage', 'ssdstorage', 'assignedAccountables'];
        } else {
            return [];
        }
    },

    /**
     * load data from prod agg record into grid store
     */
    loadAttributesFromRecord: function(record, attributeKeys)
    {
        var attributes = record.get('json_attributes'),
            value = {'records': []};

        Ext.each(attributeKeys, function(key) {
            if (attributes && attributes[key]) {
                value.records.push({
                    id: key,
                    value: attributes[key]
                });
            }
        });
        this.attributesGrid.setValue(value);
    },

    /**
     * put data from grid into prod agg record
     */
    onAttributesWindowOK: function () {
        var selectedRows = this.getSelectionModel().getSelections(),
            selectedRecord = selectedRows[0],
            records = this.attributesGrid.getValue().records,
            attributes = {};

        Ext.each(records, function(attribute) {
            attributes[attribute.id] = attribute.value;
        }, this);

        selectedRecord.set('json_attributes', attributes);
        selectedRecord.commit();

        this.onAttributesWindowClose();
    },

    /**
     * Close store Window
     */
    onAttributesWindowClose: function () {
        this.attributesWindow.purgeListeners();
        this.attributesWindow.close();
    },

    getContextMenuItems: function() {
        return [this.editAttributesAction];
    }
});
