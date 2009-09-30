/*
 * Tine 2.0
 * lead source edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         remove/refactor admin lead source
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadSource');

/**
 * @namespace Tine.Crm.LeadSource
 * @class Tine.Crm.LeadSource.Model
 * @extends Ext.data.Record
 * 
 * lead source model
 */ 
Tine.Crm.LeadSource.Model = Ext.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'leadsource'}
]);

/**
 * get lead source store
 * if available, load data from LeadSources
 * 
 * @return {Ext.data.JsonStore}
 */
Tine.Crm.LeadSource.getStore = function() {
    
    var store = Ext.StoreMgr.get('CrmLeadSourceStore');
    if (!store) {

        store = new Ext.data.JsonStore({
            fields: Tine.Crm.LeadSource.Model,
            baseParams: {
                method: 'Crm.getLeadsources',
                sort: 'LeadSource',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if ( Tine.Crm.registry.get('LeadSources') ) {
            store.loadData(Tine.Crm.registry.get('LeadSources'));
        }
            
        Ext.StoreMgr.add('CrmLeadSourceStore', store);
    }
    return store;
};

/**
 * @deprecated
 */
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
    
    var handlerLeadsourceAdd = function(){
        var p = new Tine.Crm.LeadSource.Model({
            id: 'NULL',
            leadsource: ''
        });
        leadsourceGridPanel.stopEditing();
        Tine.Crm.LeadSource.getStore().insert(0, p);
        leadsourceGridPanel.startEditing(0, 0);
        leadsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
    };
                
    var handlerLeadsourceDelete = function(){
        var leadsourceGrid  = Ext.getCmp('editLeadsourceGrid');
        var leadsourceStore = Tine.Crm.LeadSource.getStore();
        
        var selectedRows = leadsourceGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            leadsourceStore.remove(selectedRows[i]);
        }   
    };                                        
  
    var handlerLeadsourceSaveClose = function(){
        var leadsourceStore = Tine.Crm.LeadSource.getStore();        
        var leadsourceJson = Tine.Tinebase.common.getJSONdata(leadsourceStore); 

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
        store: Tine.Crm.LeadSource.getStore(),
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

