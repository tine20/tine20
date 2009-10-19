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
 * TODO         don't use json store anymore?
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
 * @extends     Tine.Crm.Admin.QuickaddGridPanel
 * 
 * lead states grid panel
 * 
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.LeadState.GridPanel = Ext.extend(Tine.Crm.Admin.QuickaddGridPanel, {
    
    /**
     * @private
     * @cfg
     */
    autoExpandColumn:'leadstate',
    quickaddMandatory: 'leadstate',

    /**
     * @private
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        
        this.store = Tine.Crm.LeadState.getStore();
        this.recordClass = Tine.Crm.LeadState.Model;
        this.cm = this.getColumnModel();
        
        Tine.Crm.LeadState.GridPanel.superclass.initComponent.call(this);
    },
    
    getColumnModel: function() {
        return new Ext.grid.ColumnModel([
        { 
            id:'leadstate_id', 
            header: 'id', 
            dataIndex: 'id', 
            width: 25, 
            hidden: true 
        }, { 
            id:'leadstate', 
            header: 'entries', 
            dataIndex: 'leadstate', 
            width: 170, 
            hideable: false, 
            sortable: false,
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a Leadstate...')
            }),
            editor: new Ext.form.TextField({allowBlank: false}) 
        }, { 
            id:'probability', 
            header: 'probability', 
            dataIndex: 'probability', 
            width: 100, 
            hideable: false, 
            sortable: false, 
            renderer: Ext.util.Format.percentage,
            editor: new Ext.ux.PercentCombo({
                name: 'probability',
                id: 'probability'
            }),
            quickaddField: new Ext.ux.PercentCombo({
                autoExpand: true
            })
        }, {
            header: "X Lead?",
            id:'endslead',
            dataIndex: 'endslead',
            width: 50,
            editor: new Ext.form.Checkbox({}),
            quickaddField: new Ext.form.Checkbox({
                name: 'endslead'
            }),
            renderer: Tine.Tinebase.common.booleanRenderer
        }]);
    }
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
