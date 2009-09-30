/*
 * Tine 2.0
 * lead type edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         remove/refactor admin lead source
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadType');

/**
 * @namespace Tine.Crm.LeadType
 * @class Tine.Crm.LeadType.Model
 * @extends Ext.data.Record
 * 
 * lead type model
 */ 
Tine.Crm.LeadType.Model = Ext.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'leadtype'}
]);

/**
 * get lead type store
 * 
 * @return  {Ext.data.JsonStore}
 */
Tine.Crm.LeadType.getStore = function() {
	
	var store = Ext.StoreMgr.get('CrmLeadTypeStore');
	if (!store) {

		store = new Ext.data.JsonStore({
            fields: Tine.Crm.LeadType.Model,
            baseParams: {
                method: 'Crm.getLeadtypes',
                sort: 'LeadType',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if ( Tine.Crm.registry.get('LeadTypes') ) {
        	store.loadData(Tine.Crm.registry.get('LeadTypes'));
        }
        	
        Ext.StoreMgr.add('CrmLeadTypeStore', store);
	}
	return store;
};

/**
 * @deprecated
 */
Tine.Crm.LeadType.EditDialog = function() {
    var Dialog = new Ext.Window({
        title: 'Leadtypes',
        id: 'leadtypeWindow',
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
        
    var columnModelLeadtype = new Ext.grid.ColumnModel([
            { id:'id', 
              header: "id", 
              dataIndex: 'id', 
              width: 25, 
              hidden: true 
            },
            { id:'leadtype_id', 
              header: 'leadtype', 
              dataIndex: 'leadtype', 
              width: 170, 
              hideable: false, 
              sortable: false, 
              editor: new Ext.form.TextField({allowBlank: false}) 
            }                    
    ]);            
    
    var handlerLeadtypeAdd = function(){
        var p = new Tine.Crm.LeadType.Model({
            id: 'NULL',
            leadtype: ''
        });
        leadtypeGridPanel.stopEditing();
        Tine.Crm.LeadType.getStore().insert(0, p);
        leadtypeGridPanel.startEditing(0, 0);
        leadtypeGridPanel.fireEvent('celldblclick',this, 0, 1);                
    };
                
    var handlerLeadtypeDelete = function(){
        var leadtypeGrid  = Ext.getCmp('editLeadtypeGrid');
        var leadtypeStore = Tine.Crm.LeadType.getStore();
        
        var selectedRows = leadtypeGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            leadtypeStore.remove(selectedRows[i]);
        }   
    };                        
                  
    var handlerLeadtypeSaveClose = function(){
        var leadtypeStore =Tine.Crm.LeadType.getStore();        
        var leadtypeJson = Tine.Tinebase.common.getJSONdata(leadtypeStore); 
    
        Ext.Ajax.request({
            params: {
                method: 'Crm.saveLeadtypes',
                optionsData: leadtypeJson
            },
            text: 'Saving leadtypes...',
            success: function(_result, _request) {
                leadtypeStore.reload();
                leadtypeStore.rejectChanges();
            },
            failure: function(form, action) {
                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
            }
        });          
    };          
    
    var leadtypeGridPanel = new Ext.grid.EditorGridPanel({
        store: Tine.Crm.LeadType.getStore(),
        id: 'editLeadtypeGrid',
        cm: columnModelLeadtype,
        autoExpandColumn:'leadtype',
        frame:false,
        viewConfig: {
            forceFit: true
        },
        sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
        clicksToEdit:2,
        tbar: [{
            text: 'new item',
            iconCls: 'actionAdd',
            handler : handlerLeadtypeAdd
            },{
            text: 'delete item',
            iconCls: 'actionDelete',
            handler : handlerLeadtypeDelete
            },{
            text: 'save',
            iconCls: 'actionSaveAndClose',
            handler : handlerLeadtypeSaveClose 
            }]  
        });            
                
    Dialog.add(leadtypeGridPanel);
    Dialog.show();          
};

