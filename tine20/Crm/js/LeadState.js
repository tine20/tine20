/**
 * Tine 2.0
 * lead state edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo translate
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadState');

/**
 * lead state model
 */
Tine.Crm.LeadState.Model = Ext.data.Record.create([
    {name: 'id'},
    {name: 'leadstate'},
    {name: 'probability'},
    {name: 'endslead', type: 'boolean'}
]);

/**
 * get lead state store
 * if available, load data from Tine.Crm.LeadStates
 *
 * @return Ext.data.JsonStore with lead states
 */
Tine.Crm.LeadState.getStore = function() {
	var store = Ext.StoreMgr.get('CrmLeadstateStore');
	if (!store) {
		// create store
		store = new Ext.data.JsonStore({
            fields: Tine.Crm.LeadState.Model,
            baseParams: {
                method: 'Crm.getLeadstates',
                sort: 'leadstate',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        // check if initital data available
        if ( Tine.Crm.LeadStates ) {
            store.loadData(Tine.Crm.LeadStates);
        }
        
        Ext.StoreMgr.add('CrmLeadstateStore', store);
	}
	return store;
};

/**
 * lead state renderer
 * 
 * @param   int _leadstateId
 * @return  string leadstate
 */
Tine.Crm.LeadState.Renderer = function(_leadstateId) {
	leadstateStore = Tine.Crm.LeadState.getStore();		
	record = leadstateStore.getById(_leadstateId);
	
	return record.data.leadstate;
}

/**
 * lead states edit dialog
 */
Tine.Crm.LeadState.EditDialog = function() {
    var isXlead = new Ext.ux.grid.CheckColumn({
        header: "X Lead?",
        dataIndex: 'endslead',
        width: 50
    });
    
    // @todo replace percentage combo with Ext.ux.PercentCombo
    var columnModelLeadstate = new Ext.grid.ColumnModel([
        { 
            id:'leadstate_id', 
            header: 'id', 
            dataIndex: 'id', 
            width: 25, 
            hidden: true 
        },
        { 
            id:'leadstate', 
            header: 'entries', 
            dataIndex: 'leadstate', 
            width: 170, 
            hideable: false, 
            sortable: false, 
            editor: new Ext.form.TextField({allowBlank: false}) 
        },
        { 
            id:'probability', 
            header: 'probability', 
            dataIndex: 'probability', 
            width: 50, 
            hideable: false, 
            sortable: false, 
            renderer: Ext.util.Format.percentage,
            editor: new Ext.ux.PercentCombo({
                name: 'probability',
                id: 'probability'
            }) 
        }, 
        isXlead
    ]);

    var handlerLeadstateAdd = function(){
        var p = new Tine.Crm.LeadState.Model({
            id: null,
            leadstate: '',
            probability: null,
            endslead: false
        });
        leadstateGridPanel.stopEditing();
        Tine.Crm.LeadState.getStore().insert(0, p);
        leadstateGridPanel.startEditing(0, 0);
        leadstateGridPanel.fireEvent('celldblclick',this, 0, 1);
    };
                
    var handlerLeadstateDelete = function(){
        var leadstateGrid  = Ext.getCmp('editLeadstateGrid');
        var leadstateStore = Tine.Crm.LeadState.getStore();
        
        var selectedRows = leadstateGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            leadstateStore.remove(selectedRows[i]);
        }   
    };                        
                
  
   var handlerLeadstateSaveClose = function(){
        var leadstateStore = Tine.Crm.LeadState.getStore();
        var leadstateJson = Tine.Tinebase.Common.getJSONdata(leadstateStore); 

         Ext.Ajax.request({
            params: {
                method: 'Crm.saveLeadstates',
                optionsData: leadstateJson
            },
            text: 'Saving leadstates...',
            success: function(_result, _request){
                leadstateStore.reload();                
                leadstateStore.rejectChanges();
                //Ext.getCmp('filterLeadstate').store.reload();
            },
            failure: function(form, action) {
                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
            }
        });          
    };          
    
    var leadstateGridPanel = new Ext.grid.EditorGridPanel({
        store: Tine.Crm.LeadState.getStore(),
        id: 'editLeadstateGrid',
        cm: columnModelLeadstate,
        autoExpandColumn:'leadstate',
        plugins: isXlead,
        frame:false,
        viewConfig: {
            forceFit: true
        },
        sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
        clicksToEdit:2,
        tbar: [{
            text: 'new item',
            iconCls: 'actionAdd',
            handler : handlerLeadstateAdd
            },{
            text: 'delete item',
            iconCls: 'actionDelete',
            handler : handlerLeadstateDelete
            },{
            text: 'save',
            iconCls: 'actionSaveAndClose',
            handler : handlerLeadstateSaveClose 
        }]  
    });
        
    var Dialog = new Ext.Window({
        title: 'Leadstates',
        id: 'leadstateWindow',
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
    Dialog.add(leadstateGridPanel);
    Dialog.show();
};

