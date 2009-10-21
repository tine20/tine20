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
 
Ext.ns('Tine.Crm.Product');

/**
 * @namespace   Tine.Crm.Product
 * @class       Tine.Crm.Product.GridPanel
 * @extends     Ext.grid.EditorGridPanel
 * 
 * Lead Dialog Products Grid Panel
 * 
 * <p>
 * TODO         add product search combo
 * TODO         check if we need edit/add actions again
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.Product.GridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    clicksToEdit: 1,
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
        this.recordClass = Tine.Sales.Model.Product;
        
        this.storeFields = Tine.Sales.Model.ProductArray;
        this.storeFields.push({name: 'relation'});   // the relation object           
        this.storeFields.push({name: 'relation_type'});
        this.storeFields.push({name: 'remark_price'});
        this.storeFields.push({name: 'remark_description'});
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        //this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        //this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);
        this.onUpdate = Ext.emptyFn;

        // call delegates
        this.initStore();
        this.initActions();
        this.initGrid();
        
        // init store stuff
        this.store.setDefaultSort('name', 'asc');
        
        this.on('newentry', function(productData){
            // add new product to store
            var newProduct = [productData];
            this.store.loadData(newProduct, true);
            
            return true;
        }, this);
        
        Tine.Crm.Product.GridPanel.superclass.initComponent.call(this);
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
                id: 'name',
                dataIndex: 'name',
                width: 150
            }, {
                header: this.app.i18n._("Description"),
                id: 'remark_description',
                dataIndex: 'remark_description',
                width: 150,
                editor: new Ext.form.TextField({
                })
            }, {
                header: this.app.i18n._("Price"),
                id: 'remark_price',
                dataIndex: 'remark_price',
                width: 150,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    decimalSeparator: ','
                }),
                renderer: Ext.util.Format.euMoney
            }]
        });
    },
    
    /**
     * init actions
     */
    initActions: function() {

        this.actionUnlink = new Ext.Action({
            requiredGrant: 'editGrant',
            text: String.format(this.app.i18n._('Unlink {0}'), this.recordClass.getMeta('recordName')),
            tooltip: String.format(this.app.i18n._('Unlink selected {0}'), this.recordClass.getMeta('recordName')),
            disabled: true,
            iconCls: 'actionRemove',
            onlySingle: true,
            scope: this,
            handler: function(_button, _event) {                       
                var selectedRows = this.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    this.store.remove(selectedRows[i]);
                }           
            }
        });
        
        // init toolbars and ctx menut / add actions
        this.bbar = [                
            this.actionUnlink
        ];
        
        this.actions = [
            this.actionUnlink
        ];
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions
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

/**
 * product selection combo box
 * 
 * TODO use that again
 */
Tine.Crm.Product.ComboBox = Ext.extend(Ext.form.ComboBox, {

    /**
     * @cfg {bool} setPrice in price form field
     */
    setPrice: false,
    
    name: 'product_combo',
    hiddenName: 'id',
    displayField:'productsource',
    valueField: 'id',
    allowBlank: false, 
    typeAhead: true,
    editable: true,
    selectOnFocus: true,
    forceSelection: true, 
    triggerAction: "all", 
    mode: 'local', 
    lazyRender: true,
    listClass: 'x-combo-list-small',

    //private
    initComponent: function(){

        Tine.Crm.Product.ComboBox.superclass.initComponent.call(this);        

        if (this.setPrice) {
            // update price field
            this.on('select', function(combo, record, index){
                var priceField = Ext.getCmp('new-product_price');
                priceField.setValue(record.data.price);
                
            }, this);
        }
    }
        
});
