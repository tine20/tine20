/**
 * Tine 2.0
 * lead source edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadSource');

/**
 * lead source model
 */
Tine.Crm.LeadSource.Model = Ext.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'leadsource'}
]);

/**
 * get lead source store
 * 
 * @return  Ext.data.Store
 * @todo    check if that should be reloaded from time to time
 */
Tine.Crm.LeadSource.getStore = function() {
	var store = Ext.StoreMgr.get('CrmLeadSourceStore');
	if (!store) {
        if ( Tine.Crm.LeadSources ) {
            store = new Ext.data.JsonStore({
                data: Tine.Crm.LeadSources,
                autoLoad: true,         
                id: 'id',
                fields: Tine.Crm.LeadSource.Model
            });            
        } else {
    		store = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getLeadsources',
                    sort: 'LeadSource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                fields: Tine.Crm.LeadSource.Model,
                remoteSort: false
            });
            store.load();
        }
        Ext.StoreMgr.add('CrmLeadSourceStore', store);
	}
	return store;
};

Tine.Crm.LeadSource.EditDialog = function() {
    var Dialog = new Ext.Window({
        title: 'Leadsources',
        id: 'leadsourceWindow',
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
    
    var storeLeadsource = Tine.Crm.LeadSource.getStore();
    
    storeLeadsource.load();
    
    var columnModelLeadsource = new Ext.grid.ColumnModel([
            { id:'id', 
              header: "id", 
              dataIndex: 'leadsource_id', 
              width: 25, 
              hidden: true 
            },
            { id:'leadsource', 
              header: 'entries', 
              dataIndex: 'leadsource', 
              width: 170, 
              hideable: false, 
              sortable: false, 
              editor: new Ext.form.TextField({allowBlank: false}) 
            }                    
    ]);            
    
    var entry = Tine.Crm.LeadSource.Model;
    
    var handlerLeadsourceAdd = function(){
        var p = new entry({
            leadsource_id: 'NULL',
            leadsource: ''
        });
        leadsourceGridPanel.stopEditing();
        storeLeadsource.insert(0, p);
        leadsourceGridPanel.startEditing(0, 0);
        leadsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
    };
                
    var handlerLeadsourceDelete = function(){
        var leadsourceGrid  = Ext.getCmp('editLeadsourceGrid');
        var leadsourceStore = leadsourceGrid.getStore();
        
        var selectedRows = leadsourceGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            leadsourceStore.remove(selectedRows[i]);
        }   
    };                                        
  
    var handlerLeadsourceSaveClose = function(){
        var leadsourceStore = Ext.getCmp('editLeadsourceGrid').getStore();
        
        var leadsourceJson = Tine.Tinebase.Common.getJSONdata(leadsourceStore); 

        Ext.Ajax.request({
            params: {
                method: 'Crm.saveLeadsources',
                optionsData: leadsourceJson
            },
            text: 'Saving leadsources...',
            success: function(_result, _request){
                    leadsourceStore.reload();
                    leadsourceStore.rejectChanges();
               },
            failure: function(form, action) {
                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                }
        });          
    };          
    
    var leadsourceGridPanel = new Ext.grid.EditorGridPanel({
        store: storeLeadsource,
        id: 'editLeadsourceGrid',
        cm: columnModelLeadsource,
        autoExpandColumn:'leadsource',
        frame:false,
        viewConfig: {
            forceFit: true
        },
        sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
        clicksToEdit:2,
        tbar: [{
            text: 'new item',
            iconCls: 'actionAdd',
            handler : handlerLeadsourceAdd
            },{
            text: 'delete item',
            iconCls: 'actionDelete',
            handler : handlerLeadsourceDelete
            },{
            text: 'save',
            iconCls: 'actionSaveAndClose',
            handler : handlerLeadsourceSaveClose 
            }]  
    });
            
                
    Dialog.add(leadsourceGridPanel);
    Dialog.show();                          
};

