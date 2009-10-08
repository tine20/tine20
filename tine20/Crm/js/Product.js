/**
 * Tine 2.0
 * product edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         translate
 * TODO         remove deprecated code
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.Product');

Tine.Crm.Product.Model = Ext.data.Record.create([
    {name: 'id'},
    {name: 'productsource'},
    {name: 'price'}
]);

/**
 * get product store
 * if available, load data from Tine.Crm.registry.get('Products')
 *
 * @return Ext.data.JsonStore with products
 */
Tine.Crm.Product.getStore = function() {
    var store = Ext.StoreMgr.get('CrmProductStore');
    if (!store) {
        // create store
        store = new Ext.data.JsonStore({
            fields: Tine.Crm.Product.Model,
            baseParams: {
                method: 'Crm.getProducts',
                sort: 'productsource',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        // check if initital data available
        if ( Tine.Crm.registry.get('Products') ) {
            store.loadData(Tine.Crm.registry.get('Products'));
        }
        
        Ext.StoreMgr.add('CrmProductStore', store);
    }
    return store;
};

Tine.Crm.Product.EditDialog = function() {
    var Dialog = new Ext.Window({
        title: 'Products',
        id: 'productWindow',
        modal: true,
        width: 350,
        height: 500,
        minWidth: 300,
        minHeight: 500,
        layout: 'fit',
        plain:true,
        bodyStyle:'padding:5px;',
        buttonAlign:'center'
    }); 
        
    var columnModelProductsource = new Ext.grid.ColumnModel([
            { id:'id', 
              header: "id", 
              dataIndex: 'id', 
              width: 25, 
              hidden: true 
            },
            { id:'productsource', 
              header: 'entries', 
              dataIndex: 'productsource', 
              width: 170, 
              hideable: false, 
              sortable: false, 
              editor: new Ext.form.TextField({
                allowBlank: false,
                maxLength: 60
            }) 
            }, 
            {
              id: 'price',  
              header: "price",
              dataIndex: 'price',
              width: 80,
              align: 'right',
              editor: new Ext.form.NumberField({
                  allowBlank: false,
                  allowNegative: false,
                  decimalSeparator: ',',
                  maxValue: 999999999999.99
                  }),
              renderer: Ext.util.Format.euMoney                    
            }
    ]);            
        
    var handlerProductsourceAdd = function(){
        var p = new Tine.Crm.Product.Model({
            'id': 'NULL',
            productsource: '',
            price: '0.00'
        });
        productsourceGridPanel.stopEditing();
        Tine.Crm.Product.getStore().insert(0, p);
        productsourceGridPanel.startEditing(0, 0);
        productsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
    };
                
    var handlerProductsourceDelete = function(){
        var productsourceGrid  = Ext.getCmp('editProductsourceGrid');
        var productsourceStore = Tine.Crm.Product.getStore();
        
        var selectedRows = productsourceGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            productsourceStore.remove(selectedRows[i]);
        }   
    };                        
                
  
    var handlerProductsourceSaveClose = function(){
        var productsourceStore = Tine.Crm.Product.getStore();        
        var productsourceJson = Tine.Tinebase.common.getJSONdata(productsourceStore); 

         Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveProducts',
                        optionsData: productsourceJson
                    },
                    text: 'Saving productsource...',
                    success: function(_result, _request){
                            productsourceStore.reload();
                            productsourceStore.rejectChanges();
                       },
                    failure: function(form, action) {
                        //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                        }
                });          
    };          
    
    var productsourceGridPanel = new Ext.grid.EditorGridPanel({
        store: Tine.Crm.Product.getStore(),
        id: 'editProductsourceGrid',
        cm: columnModelProductsource,
        autoExpandColumn:'productsource',
        frame:false,
        viewConfig: {
            forceFit: true
        },
        sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
        clicksToEdit:2,
        tbar: [{
            text: 'new item',
            iconCls: 'actionAdd',
            handler : handlerProductsourceAdd
            },{
            text: 'delete item',
            iconCls: 'actionDelete',
            handler : handlerProductsourceDelete
            },{
            text: 'save',
            iconCls: 'actionSaveAndClose',
            handler : handlerProductsourceSaveClose 
            }]  
        });
     
    Dialog.add(productsourceGridPanel);
    Dialog.show();                          
};

/**
 * product selection combo box
 * 
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

/**
 * product renderer
 */
Tine.Crm.Product.renderer = function(data) {
                                                
    record = Tine.Crm.Product.getStore().getById(data);
    
    if (record) {
        return Ext.util.Format.htmlEncode(record.data.productsource);
    }
    else {
        return Ext.util.Format.htmlEncode(data);
    }
};
