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
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.Product');

Tine.Crm.Product.Model = Ext.data.Record.create([
    {name: 'id'},
    {name: 'productsource'},
    {name: 'price'}
]);

Tine.Crm.Product.getStore = function() {
	
	var store = Ext.StoreMgr.get('CrmProductStore');
	if (!store) {
		store = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getProducts',
                sort: 'Product',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Crm.Product.Model,
            remoteSort: false
        });
        //store.load();
        Ext.StoreMgr.add('CrmProductStore', store);
	}
	return store;
};

Tine.Crm.Product.EditStatesDialog = function() {
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
    
    var storeProductsource = new Ext.data.JsonStore({
        baseParams: {
            method: 'Crm.getProductsource',
            sort: 'productsource',
            dir: 'ASC'
        },
        root: 'results',
        totalProperty: 'totalcount',
        id: 'id',
        fields: Tine.Crm.Product.Model,
        // turn on remote sorting
        remoteSort: false
    });
    
    storeProductsource.load();
    
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
              editor: new Ext.form.TextField({allowBlank: false}) 
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
                  decimalSeparator: ','
                  }),
              renderer: Ext.util.Format.euMoney                    
            }
    ]);            
    
     var entry = Ext.data.Record.create([
       {name: 'id', type: 'int'},
       {name: 'productsource', type: 'varchar'},
       {name: 'price', type: 'number'}
    ]);
    
    var handlerProductsourceAdd = function(){
        var p = new entry({
            //productsource_id: 'NULL',
            'id': 'NULL',
            productsource: '',
            //productsource_price: '0,00'
            price: '0,00'
        });
        productsourceGridPanel.stopEditing();
        storeProductsource.insert(0, p);
        productsourceGridPanel.startEditing(0, 0);
        productsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
    };
                
    var handlerProductsourceDelete = function(){
        var productsourceGrid  = Ext.getCmp('editProductsourceGrid');
        var productsourceStore = productsourceGrid.getStore();
        
        var selectedRows = productsourceGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            productsourceStore.remove(selectedRows[i]);
        }   
    };                        
                
  
    var handlerProductsourceSaveClose = function(){
        var productsourceStore = Ext.getCmp('editProductsourceGrid').getStore();
        
        var productsourceJson = Tine.Tinebase.Common.getJSONdata(productsourceStore); 

         Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveProductsource',
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
        store: storeProductsource,
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

