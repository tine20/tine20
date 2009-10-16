/*
 * Tine 2.0
 * lead state edit dialog and model
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         remove/refactor admin lead state 
 * TODO         don't use json store anymore
 */

Ext.namespace('Tine.Crm', 'Tine.Crm.LeadState');

/**
 * @namespace Tine.Crm.LeadState
 * @class Tine.Crm.LeadState.Model
 * @extends Ext.data.Record
 * 
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
 * if available, load data from Tine.Crm.registry.get('leadstates')
 *
 * @return {Ext.data.JsonStore}
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
        if (Tine.Crm.registry.get('leadstates')) {
            store.loadData(Tine.Crm.registry.get('leadstates'));
        }
        
        Ext.StoreMgr.add('CrmLeadstateStore', store);
	}
	return store;
};

/**
 * lead state renderer
 * 
 * @param   {Number} _leadstateId
 * @return  {String} leadstate
 */
Tine.Crm.LeadState.Renderer = function(_leadstateId) {
	leadstateStore = Tine.Crm.LeadState.getStore();		
	record = leadstateStore.getById(_leadstateId);
	
	if (record) {
	   return record.data.leadstate;
	} else {
		return 'undefined';
	}
};

/**
 * @namespace   Tine.Crm.LeadState
 * @class       Tine.Crm.LeadState.GridPanel
 * @extends     Ext.grid.EditorGridPanel
 * 
 * lead states grid panel
 * 
 * <p>
 * TODO         finish (add buttons and more columns)
 * TODO         use quickadd grid?
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.LeadState.GridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    
    /**
     * @private
     * @cfg
     */
    autoExpandColumn:'leadstate',
    //plugins: isXlead,
    /*
    viewConfig: {
        forceFit: true
    },
    */
    clicksToEdit:'auto',

    /**
     * @private
     */
    initComponent: function() {
        
        this.store = Tine.Crm.LeadState.getStore();
        this.cm = this.getColumnModel();
        this.sm = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        Tine.Crm.LeadState.GridPanel.superclass.initComponent.call(this);
        
        /*
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
        */
    },
    
    // TODO replace percentage combo with Ext.ux.PercentCombo
    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
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
        }/*,
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
        }*///, 
        //isXlead
        ]);
    }
    /*
    var isXlead = new Ext.ux.grid.CheckColumn({
        header: "X Lead?",
        dataIndex: 'endslead',
        width: 50
    });
    
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
        var leadstateJson = Tine.Tinebase.common.getJSONdata(leadstateStore); 

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
    */
});

/**
 * @namespace Tine.Crm.LeadState
 * @class Tine.Crm.LeadState.Filter
 * @extends Tine.widgets.grid.FilterModel
 */
Tine.Crm.LeadState.Filter = Ext.extend(Tine.widgets.grid.FilterModel, {
    field: 'leadstate_id',
    valueType: 'number',    
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Crm.LeadState.Filter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Crm');
        this.label = this.app.i18n._("Leadstate");
        this.operators = ['equals'];
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Ext.form.ComboBox({
            store: Tine.Crm.LeadState.getStore(),
            displayField: 'leadstate',
            valueField: 'id',
            typeAhead: true,
            triggerAction: 'all',
            selectOnFocus:true,
            editable: false,
            
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        value.on('select', this.onFiltertrigger, this);
        
        return value;
    }
});
