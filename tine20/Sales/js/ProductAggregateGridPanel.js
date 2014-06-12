/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

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
    autoExpandColumn: 'product_id',
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
        this.store.addSorted(new this.recordClass(recordData));
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
            allowBlank: false,
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
                maxValue: 12,
                allowDecimals: false
            })
        });
        
        this.productQuickadd = Tine.widgets.form.RecordPickerManager.get('Sales', 'Product', {allowBlank: true});
        this.quantityQuickadd = new Ext.ux.form.Spinner({
            fieldLabel: this.app.i18n._('Quantity'),
            name: 'quantity',
            allowBlank: false,
            value: 1,
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
                maxValue: 12,
                allowDecimals: false
            })
        });
        
        var columns = [
            {id: 'product_id', dataIndex: 'product_id', type: Tine.Sales.Model.ProductAggregate, header: this.app.i18n._('Product'),
                 quickaddField: this.productQuickadd, renderer: this.renderProductAggregate,
                 editor: this.productEditor, scope: this, doSort: function(a,b,c){console.warn(a,b,c)}
            },
            {id: 'quantity', editor: this.quantityEditor, quickaddField: this.quantityQuickadd, dataIndex: 'quantity', header: this.app.i18n._('Quantity'),  scope: this, width: 90},
            {id: 'interval', editor: this.intervalEditor, quickaddField: this.intervalQuickadd, dataIndex: 'interval', header: this.app.i18n._('Interval'),  scope: this, width: 90}
        ];
        
        if (Tine.Tinebase.common.hasRight('manage_invoices', 'Sales')) {
            columns.push({
                id: 'last_autobill',
                editor: new Ext.ux.form.ClearableDateField(),
                quickaddField: new Ext.ux.form.ClearableDateField(),
                dataIndex: 'last_autobill',
                header: this.app.i18n._('Last Billed'),
                scope: this,
                width: 180
            });
        }
        
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
     * is called on after edit to set related records
     * @param {} o
     */
    onAfterEdit: function(o) {
        switch (o.field) {
            case 'quantity':
                o.record.set('quantity', this.quantityEditor.getValue());
                break;
            case 'product_id':
                var relatedRecord = this.productEditor.store.getById(this.productEditor.getValue());
                o.record.set('product_id', relatedRecord.data);
                break;
            case 'interval':
                o.record.set('interval', this.intervalEditor.getValue());
            default: // do nothing
        }
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
    }
});

