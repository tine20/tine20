/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.ProductGridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 * 
 * Lead Dialog Products Grid Panel
 * 
 * <p>
 * TODO         test + update that when products moved to ERP/Sales Mgmt (use relations then?)
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.ProductGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'product_desc',
    quickaddMandatory: 'product_id',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    loadMask: true,
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Crm.Model.Lead
     * @property record
     */
    record: null,
    
    /**
     * store to hold all contacts
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @type Array
     * @property otherActions
     */
    otherActions: null,
    
    /**
     * @type function
     * @property recordEditDialogOpener
     */
    recordEditDialogOpener: null,

    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: null,
    
    /**
     * @private
     */
    initComponent: function() {
        // init properties
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        this.title = this.app.i18n._('Products');
        //this.recordEditDialogOpener = Tine.Products.EditDialog.openWindow;
        this.recordEditDialogOpener = Ext.emptyFn;
        this.recordClass = Tine.Crm.Model.ProductLink;
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        //this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);
        this.onUpdate = Ext.emptyFn;

        // call delegates
        this.initStore();
        this.initActions();
        this.initGrid();
        
        // init store stuff
        this.store.setDefaultSort('product_desc', 'asc');
        
        this.on('newentry', function(productData){
            // add new product to store
            var newProduct = [productData];
            this.store.loadData(newProduct, true);
            
            return true;
        }, this);
        
        this.actionAdd.setDisabled(true);
        this.actionEdit.setDisabled(true);
        
        Tine.Crm.ProductGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
            {
                header: this.app.i18n._("Product"),
                id: 'product_id',
                dataIndex: 'product_id',
                sortable: true,
                width: 150,
                editor: new Tine.Crm.Product.ComboBox({
                    store: Tine.Crm.Product.getStore() 
                }),
                quickaddField: new Tine.Crm.Product.ComboBox({
                    emptyText: this.app.i18n._('Add a product...'),
                    store: Tine.Crm.Product.getStore(),
                    setPrice: true,
                    id: 'new-product_combo'
                }),
                renderer: Tine.Crm.Product.renderer
            }, {
                id: 'product_desc',
                header: this.app.i18n._("Description"),
                //width: 100,
                sortable: true,
                dataIndex: 'product_desc',
                editor: new Ext.form.TextField({
                    allowBlank: false
                }),
                quickaddField: new Ext.form.TextField({
                    allowBlank: false
                })
            }, {
                id: 'product_price',
                header: this.app.i18n._("Price"),
                dataIndex: 'product_price',
                width: 80,
                align: 'right',
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    decimalSeparator: ','
                    }),
                quickaddField: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    decimalSeparator: ',',
                    id: 'new-product_price'
                    }),  
                renderer: Ext.util.Format.euMoney
            }]
        });
    }
    
    // obsolete (?) code
    /*
    // update price if new product is chosen
            storeProducts.on('update', function(store, record, index) {
                if(record.data.product_id && !arguments[1].modified.product_price) {          
                    var st_productsAvailable = Tine.Crm.Product.getStore();
                    var preset_price = st_productsAvailable.getById(record.data.product_id);
                    record.data.product_price = preset_price.data.price;
                }
            }); 
     */
});
