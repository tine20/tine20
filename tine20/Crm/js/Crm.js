/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 *              redesign by Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Crm');

/*************************************** CRM TREE PANEL *****************************************/

Tine.Crm = {
	
    /**
     * entry point, required by tinebase
     * creates and returnes app tree panel
     */
	getPanel: function()
	{
    	var tree = new Tine.widgets.container.TreePanel({
            id: 'crmTree',
            iconCls: 'CrmIconCls',
            title: 'CRM',
            itemName: 'Leads',
            folderName: 'Leads',
            appName: 'Crm',
            border: false
        });
            
        tree.on('click', function(node){        	
            Tine.Crm.Main.show(node);
        }, this);
            
        tree.on('beforeexpand', function(panel) {
            if(panel.getSelectionModel().getSelectedNode() === null) {
                panel.expandPath('/root/all');
                panel.selectPath('/root/all');
            }
            panel.fireEvent('click', panel.getSelectionModel().getSelectedNode());
        }, this);
        
        return tree;
	}
};

/*************************************** CRM MAIN DIALOG *****************************************/

Tine.Crm.Main = {

	/**
	 * main screen actions
	 */
    actions: {
        addLead: null,
        editLead: null,
        deleteLead: null,
        exportLead: null,
        addTask: null
    },

    /**
     * holds underlaying store
     */
    store: null,
    
    /**
     * holds paging information
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'lead_name',
        dir: 'ASC'
    },
    
    /**
     * holds current filters
     */
    filter: {
        containerType: 'personal',
        query: '',
        container: false,
        tag: false,
        probability: 0,
        leadstate: false
    },    

	handlers: {
		/**
		 * edit lead
		 * @todo use Tine.Crm.EditPopup here
		 */
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId=' + _rowIndex[0].id, 1024, 768);
        },
        
        /**
         * delete lead
         */
        handlerDelete: function(){
            Ext.MessageBox.confirm('Confirm', Tine.Crm.Main.translation._('Are you sure you want to delete this lead?'), function(_button) {
                if(_button == 'yes') {            
                    var _rowIndexIds = Ext.getCmp('gridCrm').getSelectionModel().getSelections();            
                    var toDelete_Ids = [];
                
                    for (var i = 0; i < _rowIndexIds.length; ++i) {
                        toDelete_Ids.push(_rowIndexIds[i].id);
                    }
                    
                    var leadIds = Ext.util.JSON.encode(toDelete_Ids);
                
                    Ext.Ajax.request({
                        params: {
                            method: 'Crm.deleteLeads',
                            _leadIds: leadIds
                        },
                        text: Tine.Crm.Main.translation._('Deleting lead') + '...',
                        success: function(_result, _request){
                            Ext.getCmp('gridCrm').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', Tine.Crm.Main.translation._('Some error occured while trying to delete the lead.'));
                        }
                    });
                }
            });
        },

        /**
         * onclick handler for exportBtn
         */
        exportLead: function(_button, _event) {
            var _rowIndexIds = Ext.getCmp('gridCrm').getSelectionModel().getSelections();            
            var toExportIds = [];
        
            for (var i = 0; i < _rowIndexIds.length; ++i) {
                toExportIds.push(_rowIndexIds[i].data.id);
            }
            
            var leadIds = Ext.util.JSON.encode(toExportIds);

            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadIds=' + leadIds, 768, 1024);
        },
        
        /**
         * add task handler
         * 
         * @todo    save the link via json request here?
         */
        handlerAddTask: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            
            popupWindow = new Tine.Tasks.EditPopup({
                relatedApp: 'crm',
                relatedId: _rowIndex[0].id
            });
        }
        
    },        

    /**
     * showCrmToolbar function
     */
    showCrmToolbar: function()
    {                
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 200,
            emptyText: this.translation._('Enter searchfilter')
        });
        
        quickSearchField.on('change', function(field){
            if(this.filter.query != field.getValue()){
                this.store.load({params: this.paging});
            }
        }, this);

            
       var filterComboLeadstate = new Ext.ux.form.ClearableComboBox({
            fieldLabel: this.translation._('Leadstate'), 
            blankText: this.translation._('Leadstate') + '...',
            emptyText: this.translation._('leadstate') + '...',
            id:'filterLeadstate',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            store: Tine.Crm.LeadState.getStore(),
            hiddenName: 'leadstate_id',
            valueField: 'id',
            displayField: 'leadstate',
            typeAhead: true,
            triggerAction: 'all',
            selectOnFocus:true,
            editable: false 
        }); 
       
        filterComboLeadstate.on('select', function() {
       	    this.store.load({params: this.paging});
        }, this);
      
        var filterComboProbability = new Ext.ux.PercentCombo({
            fieldLabel: this.translation._('Probability'), 
            blankText: this.translation._('Probability') + '...',            
            emptyText: this.translation._('Probability') + '...',
            id: 'filterProbability',
            name:'probability',
            hideLabel: true,            
            width:90            	
        });
                
		filterComboProbability.on('select', function() {
			this.store.load({params: this.paging});
		}, this);      
		
        /**
         * handlerToggleDetails function
         */          
        var handlerToggleDetails = function(toggle) {
        	//console.log(toggle.pressed);
            var gridView         = Ext.getCmp('gridCrm').getView();
            var gridColumnModel = Ext.getCmp('gridCrm').getColumnModel();
            
            if(toggle.pressed === true) {
                
                gridColumnModel.setRenderer(1, function(value, meta, record) {
                    return '<b>' + value + '</b><br /><br />' + record.data.description;
                } );                
                
                gridColumnModel.setRenderer(2, Tine.Crm.Main.renderer.detailedContact);
                gridColumnModel.setRenderer(3, Tine.Crm.Main.renderer.detailedContact);
                  
               gridView.refresh();              
               
            } else {
                
                gridColumnModel.setRenderer(1, function(value, meta, record) {
                    return value;
                } );                
                
                gridColumnModel.setRenderer(2, Tine.Crm.Main.renderer.shortContact);
                gridColumnModel.setRenderer(3, Tine.Crm.Main.renderer.shortContact);
                  
               gridView.refresh();                                
            }
        };
        
        // toolbar
        var toolbar = new Ext.Toolbar({
            id: 'crmToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addLead,
                this.actions.editLead,
                this.actions.deleteLead,
                //actions.actionAddTask,
                this.actions.exportLead,
                '->',
                new Ext.Button({
                    tooltip: this.translation._('Show details'),
                    enableToggle: true,
                    id: 'crmShowDetailsButton',
                    iconCls: 'showDetailsAction',
                    cls: 'x-btn-icon',
                    handler: handlerToggleDetails
                }),                    
                '-',
                new Ext.Button({
                    tooltip: this.translation._('Show closed leads'),
                    enableToggle: true,
                    iconCls: 'showEndedLeadsAction',
                    cls: 'x-btn-icon',
                    id: 'crmShowClosedLeadsButton',
                    handler: function(toggle) {                        
                        Ext.getCmp('gridCrm').getStore().reload();
                    }                    
                }),
                ' ',                
                filterComboLeadstate,
                ' ',
                filterComboProbability,                
                new Ext.Toolbar.Separator(),
                '->',
                ' ',
                quickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    },
        
    /**
     * creates the grid
     * 
     */
    showGrid: function() 
    { 
        //var dataStore = this.createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying leads {0} - {1} of {2}'),
            emptyMsg: this.translation._('No leads found.')
        }); 
        
        var ctxMenuGrid = new Ext.menu.Menu({
	        id:'ctxMenuGrid', 
	        items: [
	            this.actions.editLead,
	            this.actions.deleteLead,
	            this.actions.exportLead,
	            this.actions.addTask
	        ]
	    });

/*        var expander = new Ext.grid.RowExpander({
            enableCaching: false,
            tpl : new Ext.Template(
                '<b>Notes:</b> {description}</div></td>',
                '<td class="x-grid3-col x-grid3-cell"><b>Activities:</b> </td>')
        }); */
        
        var columnModel = new Ext.grid.ColumnModel([
            
			{resizable: true, header: this.translation._('Lead id'), id: 'id', dataIndex: 'id', width: 20, hidden: true},
            {resizable: true, header: this.translation._('Lead name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
            {resizable: true, header: this.translation._('Partner'), id: 'lead_partner', dataIndex: 'partner', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: this.translation._('Customer'), id: 'lead_customer', dataIndex: 'customer', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: this.translation._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100, renderer: Tine.Crm.LeadState.Renderer},
            {resizable: true, header: this.translation._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
            {resizable: true, header: this.translation._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                this.actions.editLead.setDisabled(true);
                this.actions.deleteLead.setDisabled(true);
                this.actions.exportLead.setDisabled(true);
                this.actions.addTask.setDisabled(true);
            } 
            if (rowCount == 1) {
               this.actions.editLead.setDisabled(false);
               this.actions.deleteLead.setDisabled(false);               
               this.actions.exportLead.setDisabled(false);
               // @todo reactivate?
               //this.actions.addTask.setDisabled(false);
            }    
            if(rowCount > 1) {                
               this.actions.editLead.setDisabled(true);
               this.actions.deleteLead.setDisabled(false);
               this.actions.exportLead.setDisabled(false);
               this.actions.addTask.setDisabled(true);
            }
        }, this);
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar, 
            stripeRows: true,  
            viewConfig: {
                forceFit:true
            },  
            /* plugins: expander, */                    
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'lead_name',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation._('No leads found.')
            })            
        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                this.actions.actionDelete.setDisabled(false);
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            // @todo use generic popup
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+record.data.id, 1024, 768);            
        });
       
       return;
    },    
      
    /**
     * initComponent
     */
    initComponent: function()
    {
    	// set translation
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
    
        // set actions
        this.actions.addLead = new Ext.Action({
            text: this.translation._('Add lead'),
            tooltip: this.translation._('Add new lead'),
            iconCls: 'actionAdd',
            handler: function(){
                //  var tree = Ext.getCmp('venues-tree');
                //  var curSelNode = tree.getSelectionModel().getSelectedNode();
                //  var RootNode   = tree.getRootNode();
                Tine.Tinebase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 1024, 768);
            }
        });
        
        this.actions.editLead = new Ext.Action({
            text: this.translation._('Edit lead'),
            tooltip: this.translation._('Edit selected lead'),
            disabled: true,
            handler: this.handlers.handlerEdit,
            iconCls: 'actionEdit',
            scope: this
        });
        
        this.actions.deleteLead = new Ext.Action({
            text: this.translation._('Delete lead'),
            tooltip: this.translation._('Delete selected leads'),
            disabled: true,
            handler: this.handlers.handlerDelete,
            iconCls: 'actionDelete',
            scope: this
        });
        
        this.actions.exportLead = new Ext.Action({
            text: this.translation._('Export as PDF'),
            tooltip: this.translation._('Export selected lead as PDF'),
            disabled: true,
            handler: this.handlers.exportLead,
            iconCls: 'action_exportAsPdf',
            scope: this
        });
        
        this.actions.addTask = new Ext.Action({
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add task for selected lead'),
            handler: this.handlers.handlerAddTask,
            iconCls: 'actionAddTask',
            disabled: true,
            scope: this
        });
        
        // init grid store
        this.initStore();
    },

    /**
     * init the leads json grid store
     */
    initStore: function(){
        this.store = new Ext.data.JsonStore({
            idProperty: 'id',
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Crm.Model.Lead,
            remoteSort: true,
            baseParams: {
                method: 'Crm.searchLeads'
            },
            sortInfo: {
                field: 'lead_name',
                dir: 'ASC'
            }
        });
        
        // register store
        Ext.StoreMgr.add('LeadsGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            
            // for some reasons, paging toolbar eats sort and dir
            if (store.getSortState()) {
                this.filter.sort = store.getSortState().field;
                this.filter.dir = store.getSortState().direction;
            } else {
                this.filter.sort = this.store.sort;
                this.filter.dir = this.store.dir;
            }
            this.filter.start = options.params.start;
            this.filter.limit = options.params.limit;
            
            // container
            var nodeAttributes = Ext.getCmp('crmTree').getSelectionModel().getSelectedNode().attributes || {};
            this.filter.containerType = nodeAttributes.containerType ? nodeAttributes.containerType : 'all';
            this.filter.container = nodeAttributes.container ? nodeAttributes.container.id : null;
            this.filter.owner = nodeAttributes.owner ? nodeAttributes.owner.accountId : null;

            // toolbar
            this.filter.showClosed = Ext.getCmp('crmShowClosedLeadsButton') ? Ext.getCmp('crmShowClosedLeadsButton').pressed : false;
            this.filter.probability = Ext.getCmp('filterProbability') ? Ext.getCmp('filterProbability').getValue() : '';
            this.filter.query = Ext.getCmp('quickSearchField') ? Ext.getCmp('quickSearchField').getValue() : '';
            this.filter.leadstate = Ext.getCmp('filterLeadstate') ? Ext.getCmp('filterLeadstate').getValue() : '';

            options.params.filter = Ext.util.JSON.encode(this.filter);
        }, this);
                
        this.store.load({
            params: this.paging
        });
    },
    
    /**
     * show
     */
    show: function(_node) 
    {    	
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();
        if (currentToolbar === false || currentToolbar.id != 'crmToolbar') {
            this.initComponent();
            this.showCrmToolbar();
            this.showGrid();
            this.updateMainToolbar();
        } else {
            // note: if node is clicked, it is not selected!
            _node.getOwnerTree().selectPath(_node.getPath());
        	this.store.load({params: this.paging});
        }
        
    },    
        
    /**
     * updateMainToolbar
     */
    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        menu.add(
            // @todo    replace with standard popup windows
            {text: this.translation._('Lead states'), handler: Tine.Crm.LeadState.EditDialog},
            {text: this.translation._('Lead sources'), handler: Tine.Crm.LeadSource.EditDialog},
            {text: this.translation._('Lead types'), handler: Tine.Crm.LeadType.EditDialog},
            {text: this.translation._('Products'), handler: Tine.Crm.Product.EditDialog}
        );

    	var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('crmThumbnailApplication');
        if(Tine.Crm.rights.indexOf('admin') > -1) {
            adminButton.setDisabled(false);
        } else {
        	adminButton.setDisabled(true);
        }

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('crmThumbnailApplication');
        adminButton.setDisabled(false);
    },

    /**
     * reload
     */
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('gridCrm')) {
            setTimeout ("Ext.getCmp('gridCrm').getStore().reload()", 200);
        }
    },
    
    /**
     * renderer
     */
    renderer: 
    {
        shortContact: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            if( Ext.isArray(_data) && _data.length > 0 ) {
                var org = ( _data[0].org_name !== null ) ? _data[0].org_name : '';
                return '<b>' + Ext.util.Format.htmlEncode(org) + '</b><br />' + Ext.util.Format.htmlEncode(_data[0].n_fileas);
            }
        },          
        
        detailedContact: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            if(typeof(_data) == 'object' && !Ext.isEmpty(_data)) {
                var contactDetails = '';
                for(i=0; i < _data.length; i++){
                    var org_name           = Ext.isEmpty(_data[i].org_name) === false ? _data[i].org_name : ' ';
                    var n_fileas           = Ext.isEmpty(_data[i].n_fileas) === false ? _data[i].n_fileas : ' ';
                    var adr_one_street     = Ext.isEmpty(_data[i].adr_one_street) === false ? _data[i].adr_one_street : ' ';
                    var adr_one_postalcode = Ext.isEmpty(_data[i].adr_one_postalcode) === false ? _data[i].adr_one_postalcode : ' ';
                    var adr_one_locality   = Ext.isEmpty(_data[i].adr_one_locality) === false ? _data[i].adr_one_locality : ' ';
                    var tel_work           = Ext.isEmpty(_data[i].tel_work) === false ? _data[i].tel_work : ' ';
                    var tel_cell           = Ext.isEmpty(_data[i].tel_cell) === false ? _data[i].tel_cell : ' ';
                    
                    if(i > 0) {
                        _style = 'borderTop';
                    } else {
                        _style = '';
                    }
                    
                    contactDetails = contactDetails + '<table width="100%" height="100%" class="' + _style + '">' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(org_name) + '</td></tr>' +
                                         '<tr><td colspan="2"><b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b></td></tr>' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_street) + '</td></tr>' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + adr_one_locality + '</td></tr>' +
                                         '<tr><td width="50%">' + this.translation._('Phone') + ': </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_work) + '</td></tr>' +
                                         '<tr><td width="50%">' + this.translation._('Cellphone') + ': </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_cell) + '</td></tr>' +
                                         '</table> <br />';
                }
                
                return contactDetails;
            }
        }
    }    
}; // end of application (CRM MAIN DIALOG)
  
/*************************************** LEAD EDIT DIALOG ****************************************/

Tine.Crm.LeadEditDialog = {
	
	/**
	 * define actions
	 */
	actions: {
        addResponsible: null,
        addCustomer: null,
        addPartner: null,
        addContact: null,
        editContact: null,
        linkContact: null,
        unlinkContact: null,
        addTask: null,		
        editTask: null,
        linkTask: null,
        unlinkTask: null,
        unlinkProduct: null,
        exportLead: null
	},
	
    /**
     * event handlers
     */
    handlers: {   
    	
    	/**
    	 * apply changes
    	 */
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var leadForm = Ext.getCmp('leadDialog').getForm();
            
            if(leadForm.isValid()) {  
                Ext.MessageBox.wait(Tine.Crm.LeadEditDialog.translation._('Please wait'), Tine.Crm.LeadEditDialog.translation._('Saving lead') + '...');                
                leadForm.updateRecord(lead);
                
                // get linked stuff
                lead = Tine.Crm.LeadEditDialog.getAdditionalData(lead);

                Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveLead', 
                        lead: Ext.util.JSON.encode(lead.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Crm) {
                            window.opener.Tine.Crm.Main.reload();
                        } 
                        if (_closeWindow === true) {
                            window.setTimeout("window.close()", 400);
                        }
                        
                        // fill form with returned lead
                        lead = new Tine.Crm.Model.Lead(Ext.util.JSON.decode(_result.responseText).updatedData);
                        Tine.Crm.Model.Lead.FixDates(lead);
                        leadForm.loadRecord(lead);
                        
                        Ext.getCmp('crmGridTasks').setDisabled(false);

                        // update stores
                        Tine.Crm.LeadEditDialog.loadContactsStore(lead.data.responsible, lead.data.customer, lead.data.partner, true);        
                        Tine.Crm.LeadEditDialog.loadTasksStore(lead.data.tasks, true);
                        //Tine.Crm.LeadEditDialog.loadProductsStore(lead.data.products);
                        
                        //Ext.StoreMgr.lookup('ContactsStore').commitChanges();
                        //Ext.StoreMgr.lookup('TasksStore').commitChanges();
                                                
                        Ext.MessageBox.hide();
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', Tine.Crm.LeadEditDialog.translation._('Could not save lead.')); 
                    },
                    scope: this
                });
            } else {
                Ext.MessageBox.alert('Errors', Tine.Crm.LeadEditDialog.translation._('Please fix the errors noted.'));
            }
        },
        
        /**
         * save and close
         */
        saveAndClose: function(_button, _event) 
        {     
        	Tine.Crm.LeadEditDialog.handlers.applyChanges(_button, _event, true);
        },
        
        /**
         * unlink action handler for linked objects
         * 
         * remove selected objects from store
         * needs _button.gridId and _button.storeName
         */
        unlink: function(_button, _event)
        {        	        	                	
            var selectedRows = Ext.getCmp(_button.gridId).getSelectionModel().getSelections();
            var store = Ext.StoreMgr.lookup(_button.storeName);
            for (var i = 0; i < selectedRows.length; ++i) {
                store.remove(selectedRows[i]);
            }           
        },

        /**
         * onclick handler for addContact
         */
        addContact: function(_button, _event) 
        {
            var contactPopup = new Tine.Addressbook.EditPopup({
            });        	
            
            // update event handler
            contactPopup.on('update', function(contact) {
                // set id and link properties
                contact.id = contact.data.id;
                contact.data.link_id = null;
                switch ( _button.contactType ) {
                	case 'responsible':
                	   contact.data.link_remark = 'responsible';
                	   break;
                    case 'customer':
                       contact.data.link_remark = 'customer';
                       break;
                    case 'partner':
                       contact.data.link_remark = 'partner';
                       break;
                }
                
                // add contact to store
                var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
                storeContacts.add(contact);                              

            }, this);
        },
            
        /**
         * onclick handler for editContact
         */
        editContact: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('crmGridContacts').getSelectionModel().getSelections();
            var selectedContact = selectedRows[0];
            
            var contactPopup = new Tine.Addressbook.EditPopup({
                contactId: selectedContact.id
            });          
            
            // update event handler
            contactPopup.on('update', function(contact) {                
                // set link properties
                contact.id = contact.data.id;
                contact.data.link_id = selectedContact.data.link_id;
                contact.data.link_remark = selectedContact.data.link_remark;
                
                // add contact to store (remove the old one first)
                var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
                storeContacts.remove(selectedContact);
                storeContacts.add(contact);                                
            }, this);            
        },

        /**
         * linkContact
         * 
         * link an existing contact -> create and activate contact picker/search dialog
         * @todo    add bigger & better search dialog later
         */
        linkContact: function(_button, _event)
        {
        	// create new contact search grid
        	contactSearchGrid = this.getLinksGrid('ContactsSearch', this.translation._('Search Contacts'));
        	
        	// add grid to tabpanel & activate
        	var linkTabPanel = Ext.getCmp('linkPanel');
        	linkTabPanel.add(contactSearchGrid);
            linkTabPanel.activate(contactSearchGrid);            	
        },

        /**
         * onclick handler for add task
         * 
         */
        addTask: function(_button, _event) 
        {
            var taskPopup = new Tine.Tasks.EditPopup({
            	id: -1
                //relatedApp: 'crm'
            	//containerId:
                //relatedId: 
            });          
            
            // update event handler
            taskPopup.on('update', function(task) {
            	
            	//console.log(task);
            	
            	// set id and link properties
                task.id = task.data.id;
                task.data.link_id = null;
                
                // add contact to store
                var storeTasks = Ext.StoreMgr.lookup('TasksStore');
                storeTasks.add(task);                              
            }, this);
        },
            
        /**
         * onclick handler for editBtn
         * 
         */
        editTask: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('crmGridTasks').getSelectionModel().getSelections();
            var selectedTask = selectedRows[0];
            
            var taskPopup = new Tine.Tasks.EditPopup({
                id: selectedTask.id
            });          
            
            // update event handler
            taskPopup.on('update', function(task) {           
                // set link properties
                task.id = task.data.id;
                //task.data.link_id = selectedTask.data.link_id;
                
                // add task to store (remove the old one first)
                var storeContacts = Ext.StoreMgr.lookup('TasksStore');
                storeContacts.remove(selectedTask);
                storeContacts.add(task);                                
            }, this);
        },

        /**
         * linkTask
         * 
         * link an existing task, open 'object' picker dialog
         * @todo implement
         */
        linkTask: function(_button, _event)
        {
            
        },

        /**
         * onclick handler for exportBtn
         */
        exportLead: function(_button, _event) {         
        	
        	var leadId = Ext.util.JSON.encode([_button.leadId]);
        	
            Tine.Tinebase.Common.openWindow('exportWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadIds=' + leadId, 768, 1024);
        }
    },       

    /**
     * getAdditionalData
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     * @param   Tine.Crm.Model.Lead lead
     * @return  Tine.Crm.Model.Lead lead
     */
    getAdditionalData: function(lead)
    {
        // collect data of assosicated contacts
        var linksResponsible = [];
        var linksCustomer = [];
        var linksPartner = [];

        var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
        
        storeContacts.each(function(record) {        	
        	var link = {};
        	
        	if ( record.id !== null ) {
        		link = {id: record.id, link_id: record.data.link_id}; 
        	} else {
        		// add complete data array for new records 
        		link = record.data;
        	}
        	
        	//console.log(record.data);
        	
        	switch ( record.data.link_remark ) {
                case 'responsible':
                    linksResponsible.push(link);
                    break;
                case 'customer':
                    linksCustomer.push(link);
                    break;
                case 'partner':
                    linksPartner.push(link);
                    break;
        	}                            
        });
        
        lead.data.responsible = linksResponsible;
        lead.data.customer = linksCustomer;
        lead.data.partner = linksPartner;
        
        // add tasks
        var linksTasks = [];
        var storeTasks = Ext.StoreMgr.lookup('TasksStore');
        
        storeTasks.each(function(record) {
            link = {id: record.id, link_id: record.data.link_id}; 
            linksTasks.push(link);
        });
        
        lead.data.tasks = linksTasks;
        
        // add products
        var linksProducts = [];
        var storeProducts = Ext.StoreMgr.lookup('ProductsStore');       

        storeProducts.each(function(record) {
            linksProducts.push(record.data);
        });
        
        lead.data.products = linksProducts;
        
        return lead;        
    },
        
    /**
     * getLinksGrid
     * get the grids for contacts/tasks/products/...
     * 
     * @param   string  type
     * @param   string  grid title
     * @return  grid object
     * 
     * @todo    move to LeadEditDialog.js ?
     */
    getLinksGrid: function(_type, _title)
    {
    	// init vars
    	var storeName = _type + 'Store';
    	var columnModel = null;
    	var autoExpand = 'n_fileas';
    	var rowSelectionModel = null;
    	var bbarItems = [];
    	
    	// set the column / row selection model
    	switch ( _type ) {
    		
            /******************* contacts tabpanel ********************/                

    		case 'Contacts':
    		
                // @todo move that to renderer/addressbook ?
    		    // @todo remove null and &nbsp; in the grid display 
                columnModel = new Ext.grid.ColumnModel([
                    {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
                    {id:'n_fileas', header: this.translation._('Name'), dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                        function(val, meta, record) {
                            var org_name           = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : ' ';
                            var n_fileas           = Ext.isEmpty(record.data.n_fileas) === false ? record.data.n_fileas : ' ';                            
                            var formated_return = '<b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b><br />' + Ext.util.Format.htmlEncode(org_name);
                            
                            return formated_return;
                        }
                    },
                    {id:'contact_one', header: this.translation._("Address"), dataIndex: 'adr_one_locality', width: 160, sortable: false, renderer: function(val, meta, record) {
                            var adr_one_street     = Ext.isEmpty(record.data.adr_one_street) === false ? record.data.adr_one_street : ' ';
                            var adr_one_postalcode = Ext.isEmpty(record.data.adr_one_postalcode) === false ? record.data.adr_one_postalcode : ' ';
                            var adr_one_locality   = Ext.isEmpty(record.data.adr_one_locality) === false ? record.data.adr_one_locality : ' ';
                            var formated_return =  
                                Ext.util.Format.htmlEncode(adr_one_street) + '<br />' + 
                                Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + Ext.util.Format.htmlEncode(adr_one_locality);
                        
                            return formated_return;
                        }
                    },
                    {id:'tel_work', header: this.translation._("Contactdata"), dataIndex: 'tel_work', width: 160, sortable: false, renderer: function(val, meta, record) {
                            var tel_work           = Ext.isEmpty(record.data.tel_work) === false ? Tine.Crm.LeadEditDialog.translation._('Phone') + ': ' + record.data.tel_work : ' ';
                            var tel_cell           = Ext.isEmpty(record.data.tel_cell) === false ? Tine.Crm.LeadEditDialog.translation._('Cellphone') + ': ' + record.data.tel_cell : ' ';          
                            var formated_return = tel_work + '<br/>' + tel_cell + '<br/>';
                            return formated_return;
                        }                        
                    },    
                    {
                        id:'link_remark', 
                        header: this.translation._("Type"), 
                        dataIndex: 'link_remark', 
                        width: 75, 
                        sortable: true,
                        renderer: Tine.Crm.contactType.Renderer,
                        editor: new Tine.Crm.contactType.ComboBox({
                            autoExpand: true,
                            blurOnSelect: true,
                            listClass: 'x-combo-list-small'
                        })
                    }
                ]);
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();                    
                    if(rowCount < 1) {
                        this.actions.editContact.setDisabled(true);
                        this.actions.unlinkContact.setDisabled(true);
                    } 
                    if (rowCount == 1) {
                        this.actions.editContact.setDisabled(false);
                        this.actions.unlinkContact.setDisabled(false);
                    }    
                    if(rowCount > 1) {                
                        this.actions.editContact.setDisabled(true);
                        this.actions.unlinkContact.setDisabled(false);
                    }
                }, this);
                
                bbarItems = [                
                    this.actions.linkContact,                    
                    this.actions.addContact,
                    this.actions.unlinkContact
                ]; 
                
                break;
            
            /******************* contacts search tabpanel ********************/                
                
            case 'ContactsSearch':
            
                columnModel = new Ext.grid.ColumnModel([
                    {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
                    {id:'n_fileas', header: this.translation._('Name'), dataIndex: 'n_fileas', width: 120, sortable: true},
                    {id:'org_name', header: this.translation._('Organisation'), dataIndex: 'org_name', width: 120, sortable: true},
                    {
                        id:'link_remark', 
                        header: this.translation._("Type"), 
                        dataIndex: 'link_remark', 
                        width: 75, 
                        sortable: false,
                        renderer: Tine.Crm.contactType.Renderer,
                        editor: new Tine.Crm.contactType.ComboBox({
                            autoExpand: true,
                            blurOnSelect: true,
                            listClass: 'x-combo-list-small'
                        })
                    }
                ]);
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                
                storeName = 'ContactsStore';
                
                break;

            /******************* tasks tabpanel ********************/                

            case 'Tasks':
            
                columnModel = [{
                    id: 'status_id',
                    header: this.translation._("Status"),
                    width: 45,
                    sortable: true,
                    dataIndex: 'status_id',
                    renderer: Tine.Tasks.status.getStatusIcon,
                    editor: new Tine.Tasks.status.ComboBox({
                        autoExpand: true,
                        blurOnSelect: true,
                        listClass: 'x-combo-list-small'
                    }),
                    quickaddField: new Tine.Tasks.status.ComboBox({
                        autoExpand: true
                    })
                },
                {
                    id: 'percent',
                    header: this.translation._("Percent"),
                    width: 50,
                    sortable: true,
                    dataIndex: 'percent',
                    renderer: Ext.ux.PercentRenderer,
                    editor: new Ext.ux.PercentCombo({
                        autoExpand: true,
                        blurOnSelect: true
                    }),
                    quickaddField: new Ext.ux.PercentCombo({
                        autoExpand: true
                    })
                },
                {
                    id: 'summary',
                    header: this.translation._("Summary"),
                    width: 100,
                    sortable: true,
                    dataIndex: 'summary',
                    //editor: new Ext.form.TextField({
                    //  allowBlank: false
                    //}),
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.translation._('Add a task...')
                    })
                },
                {
                    id: 'priority',
                    header: this.translation._("Priority"),
                    width: 45,
                    sortable: true,
                    dataIndex: 'priority',
                    renderer: Tine.widgets.Priority.renderer,
                    editor: new Tine.widgets.Priority.Combo({
                        allowBlank: false,
                        autoExpand: true,
                        blurOnSelect: true
                    }),
                    quickaddField: new Tine.widgets.Priority.Combo({
                        autoExpand: true
                    })
                },
                {
                    id: 'due',
                    header: this.translation._("Due Date"),
                    width: 55,
                    sortable: true,
                    dataIndex: 'due',
                    renderer: Tine.Tinebase.Common.dateRenderer,
                    editor: new Ext.ux.form.ClearableDateField({
                        //format : 'd.m.Y'
                    }),
                    quickaddField: new Ext.ux.form.ClearableDateField({
                        //value: new Date(),
                        //format : "d.m.Y"
                    })
                }];
                
            	rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();                    
                    if(rowCount < 1) {
                        this.actions.editTask.setDisabled(true);
                        this.actions.unlinkTask.setDisabled(true);
                    } 
                    if (rowCount == 1) {
                        this.actions.editTask.setDisabled(false);
                        this.actions.unlinkTask.setDisabled(false);
                    }    
                    if(rowCount > 1) {                
                        this.actions.editTask.setDisabled(true);
                        this.actions.unlinkTask.setDisabled(false);
                    }
                }, this);
            	
                autoExpand = 'summary';
                
                bbarItems = [
                    this.actions.addTask,
                    this.actions.unlinkTask
                ]; 
                
                break;

            /******************* products tabpanel ********************/                
                
            case 'Products':
            
                columnModel = [
                {
                    header: this.translation._("Product"),
                    id: 'product_id',
                    dataIndex: 'product_id',
                    sortable: true,
                    width: 150,
                    editor: new Tine.Crm.Product.ComboBox({
                        store: Tine.Crm.Product.getStore() 
                    }),
                    quickaddField: new Tine.Crm.Product.ComboBox({
                        emptyText: this.translation._('Add a product...'),
                        store: Tine.Crm.Product.getStore(),
                        setPrice: true,
                        id: 'new-product_combo'
                    }),
                    renderer: Tine.Crm.Product.renderer
                },
                {
                    id: 'product_desc',
                    header: this.translation._("Description"),
                    //width: 100,
                    sortable: true,
                    dataIndex: 'product_desc',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    quickaddField: new Ext.form.TextField({
                        allowBlank: false
                    })
                },
                {
                    id: 'product_price',
                    header: this.translation._("Price"),
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
                }                
                ];
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();                    
                    if(rowCount < 1) {
                        this.actions.unlinkProduct.setDisabled(true);
                    } 
                    if (rowCount == 1) {
                        this.actions.unlinkProduct.setDisabled(false);
                    }    
                    if(rowCount > 1) {                
                        this.actions.unlinkProduct.setDisabled(false);
                    }
                }, this);
                
                autoExpand = 'product_desc';
                
                bbarItems = [
                    this.actions.unlinkProduct
                ]; 
                
                break;

        } // end switch

        // get store and create grid
        var gridStore = Ext.StoreMgr.lookup(storeName);  
        var grid = null;
        
        if ( _type === 'ContactsSearch' ) {
            grid = new Tine.widgets.GridPicker({
            	id: 'crmGrid' + _type,
            	title: _title,
                gridStore: gridStore,
                columnModel: columnModel,
                bbarItems: bbarItems,
                closable: true
            });
            
        } else if ( _type === 'Contacts' ) {
            grid = new Ext.grid.EditorGridPanel({
                id: 'crmGrid' + _type,
                title: _title,
                cm: columnModel,
                store: gridStore,
                selModel: rowSelectionModel,
                autoExpandColumn: autoExpand,
                bbar: bbarItems,
                clicksToEdit: 'auto'
            });            
            
        } else if ( _type === 'Tasks') {        	
            grid = new Ext.ux.grid.QuickaddGridPanel({
                title: _title,
                id: 'crmGrid' + _type,
                disabled: true,
                border: false,
                store: gridStore,
                clicksToEdit: 'auto',
                bbar: bbarItems,
                enableColumnHide:false,
                enableColumnMove:false,
                sm: rowSelectionModel,
                loadMask: true,
                quickaddMandatory: 'summary',
                autoExpandColumn: 'summary',
                columns: columnModel,
                view: new Ext.grid.GridView({
                    autoFill: true,
                    forceFit:true,
                    ignoreAdd: true,
                    emptyText: this.translation._('No Tasks to display')
                })
            });
            
            grid.on('newentry', function(taskData){

            	// @todo change that later -> we do not need this ajax request 
            	// because the new tasks should only be saved on _apply_ or _saveandclose_            	

                // add new task to store
            	/*
                var gridStore = Ext.StoreMgr.lookup('TasksStore');      
                var newTask = [taskData];
                gridStore.loadData(newTask, true);
                */
            	
                var gridStore = Ext.StoreMgr.lookup('TasksStore');                  	
                var task = new Tine.Tasks.Task(taskData);
    
                Ext.Ajax.request({
                    scope: this,
                    params: {
                        method: 'Tasks.saveTask', 
                        task: Ext.util.JSON.encode(task.data),
                        linkingApp: '',
                        linkedId: ''
                    },
                    success: function(_result, _request) {
                    	var newTask = [Ext.util.JSON.decode(_result.responseText)];                    	
                        gridStore.loadData(newTask, true);                        
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
                    }
                });
            	
                return true;
            }, this);
            
            // hack to get percentage editor working
            grid.on('rowclick', function(grid,row,e) {
                var cell = Ext.get(grid.getView().getCell(row,1));
                var dom = cell.child('div:last');
                while (cell.first()) {
                    cell = cell.first();
                    cell.on('click', function(e){
                        e.stopPropagation();
                        grid.fireEvent('celldblclick', grid, row, 1, e);
                    });
                }
            }, this);            

        } else if ( _type === 'Products') {        	
            grid = new Ext.ux.grid.QuickaddGridPanel({
                title: _title,
                id: 'crmGrid' + _type,
                border: false,
                store: gridStore,
                clicksToEdit: 'auto',
                bbar: bbarItems,
                enableColumnHide:false,
                enableColumnMove:false,
                sm: rowSelectionModel,
                loadMask: true,
                quickaddMandatory: 'product_id',
                autoExpandColumn: 'product_desc',
                columns: columnModel
            });
            
            grid.on('newentry', function(productData){

                // add new product to store
                var gridStore = Ext.StoreMgr.lookup('ProductsStore');      
                var newProduct = [productData];
                gridStore.loadData(newProduct, true);
                
                return true;
            }, this);
            
        } else {        	
            grid = {
                xtype:'grid',
                id: 'crmGrid' + _type,
                title: _title,
                cm: columnModel,
                store: gridStore,
                selModel: rowSelectionModel,
                autoExpandColumn: autoExpand,
                bbar: bbarItems
            };
        }
        	
        return grid;       
    },
    
    /**
     * set context menu for link grid
     * 
     * @param   string _type (Contacts|Tasks|Products)
     */
    setLinksContextMenu: function(_type)
    {
    	// init vars
    	var rowItems = [];
    	var gridItems = [];
    	
        switch ( _type ) {
            case 'Contacts':
                var addNewItems = {
                    text: this.translation._('Add new contact'),
                    iconCls: 'actionAdd',
                    contactType: 'partner',
                    handler: this.handlers.addContact,
                    menu: {
                        items: [
                            this.actions.addResponsible,
                            this.actions.addCustomer,
                            this.actions.addPartner
                        ]
                    }
                }; 
                // items for row context menu
                rowItems = [
                    this.actions.editContact,
                    this.actions.unlinkContact,
                    '-',
                    this.actions.linkContact,
                    addNewItems
                ];
                // items for all grid context menu
                gridItems = [
                    this.actions.linkContact,
                    addNewItems
                ];
                break;
                
            case 'Tasks':
                // items for row context menu
                rowItems = [
                    this.actions.editTask,
                    this.actions.unlinkTask,
                    '-',
                    //this.actions.linkTask,
                    this.actions.addTask
                ];
                // items for all grid context menu
                gridItems = [
                    //this.actions.linkTask,
                    this.actions.addTask
                ];
                break;
                
            case 'Products':
                // items for row context menu
                rowItems = [
                    this.actions.unlinkProduct
                ];
                break;
        }
        
        var grid = Ext.getCmp('crmGrid' + _type);
        grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();

            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            
            var ctxMenuGrid = new Ext.menu.Menu({
                id:'ctxMenuGridRow' + _type, 
                items: rowItems
            });

            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
        grid.on('contextmenu', function(_eventObject) {
            _eventObject.stopEvent();
            
            var ctxMenuGrid = new Ext.menu.Menu({
                id:'ctxMenuGrid' + _type, 
                items: gridItems
            });

            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
    },
    
    /**
     * get linked contacts store and put it into store manager
     * 
     * @param   array _responsible
     * @param   array _customer
     * @param   array _partner
     * @param   boolean _reload reload or create new store
     */
    loadContactsStore: function(_responsible, _customer, _partner, _reload)
    {
    	var storeContacts = null;
    	
    	if (_reload) {
    	   	storeContacts = Ext.StoreMgr.lookup('ContactsStore');

    	   	// empty store and fill with data
    	   	storeContacts.removeAll();
    	   	
            if(_responsible) {
                storeContacts.loadData(_responsible, true);                    
            }
    
            if(_customer) {
                storeContacts.loadData(_customer, true);                    
            }
            
            if(_partner) {
                storeContacts.loadData(_partner, true);                    
            }
    	   	
    	} else {
            storeContacts = new Ext.data.JsonStore({
                id: 'id',
                fields: Tine.Crm.Model.ContactLink
            });
                
            if(_responsible) {
                storeContacts.loadData(_responsible, true);                    
            }
    
            if(_customer) {
                storeContacts.loadData(_customer, true);                    
            }
            
            if(_partner) {
                storeContacts.loadData(_partner, true);                    
            }
    
            storeContacts.setDefaultSort('link_remark', 'asc');   
            
            // remove link_id on update
            /*
            storeContacts.on('update', function(store, record, operation) {
            	if (operation === Ext.data.Record.EDIT) {
                    record.data.link_id = null;
            	}
            }, this);
            */        
            
            Ext.StoreMgr.add('ContactsStore', storeContacts);
    	}
    },

    /**
     * get linked tasks store and put it into store manager
     * 
     * @param   array _tasks
     * @param   boolean _reload reload or create new store
     */
    loadTasksStore: function(_tasks, _reload)
    {
    	var storeTasks = null;
    	
    	if (_reload) {

    		storeTasks = Ext.StoreMgr.lookup('TasksStore');

            // empty store and fill with data
            storeTasks.removeAll();
    		
            if(_tasks) {
                storeTasks.loadData(_tasks);                    
            }

        } else {
            var storeTasks = new Ext.data.JsonStore({
                id: 'id',
                fields: Tine.Crm.Model.TaskLink
            });
                
            if(_tasks) {
                storeTasks.loadData(_tasks);                    
            }
            
            //console.log(storeTasks);
            
            Ext.StoreMgr.add('TasksStore', storeTasks);
    	}
    },

    /**
     * get linked products store and put it into store manager
     * 
     * @param   array _products
     */
    loadProductsStore: function(_products)
    {
        var storeProducts = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Crm.Model.ProductLink
        });
            
        if(_products) {
            storeProducts.loadData(_products);                    
            //storeProducts.setDefaultSort('remark', 'asc');     
        }
        
        // update price if new product is chosen
        storeProducts.on('update', function(store, record, index) {
            if(record.data.product_id && !arguments[1].modified.product_price) {          
                var st_productsAvailable = Tine.Crm.Product.getStore();
                var preset_price = st_productsAvailable.getById(record.data.product_id);
                record.data.product_price = preset_price.data.price;
            }
        }); 
        
        Ext.StoreMgr.add('ProductsStore', storeProducts);
    },    
    
    /**
     * initComponent
     * sets the translation object and actions
     * 
     * @param   Tine.Crm.Model.Lead lead lead data
     */
    initComponent: function(lead)
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
        
        /****** actions *******/
        
        // contacts
        this.actions.addResponsible = new Ext.Action({
        	contactType: 'responsible',
            text: this.translation._('Add responsible'),
            tooltip: this.translation._('Add new responsible contact'),
            iconCls: 'contactIconResponsible',
            //disabled: true,
            handler: this.handlers.addContact
        });
        
        this.actions.addCustomer = new Ext.Action({
            contactType: 'customer',
            text: this.translation._('Add customer'),
            tooltip: this.translation._('Add new customer contact'),
            iconCls: 'contactIconCustomer',
            //disabled: true,
            handler: this.handlers.addContact
        });
        
        this.actions.addPartner = new Ext.Action({
            contactType: 'partner',
            text: this.translation._('Add partner'),
            tooltip: this.translation._('Add new partner contact'),
            iconCls: 'contactIconPartner',
            //disabled: true,
            handler: this.handlers.addContact
        });

        // split button with all contact types
        this.actions.addContact = new Ext.SplitButton({
            contactType: 'customer',
            text: this.translation._('Add new contact'),
            tooltip: this.translation._('Add new customer contact'),
            iconCls: 'actionAdd',
            handler: this.handlers.addContact,
            menu: {
                items: [
                    this.actions.addResponsible,
                    this.actions.addCustomer,
                    this.actions.addPartner
                ]
            }
        }); 
        
        this.actions.editContact = new Ext.Action({
            text: this.translation._('Edit contact'),
            tooltip: this.translation._('Edit selected contact'),
            disabled: true,
            iconCls: 'actionEdit',
            handler: this.handlers.editContact
        });

        this.actions.linkContact = new Ext.Action({
            text: this.translation._('Add existing contact'),
            tooltip: this.translation._('Add existing contact to lead'),
            //disabled: true,
            iconCls: 'contactIconPartner',
            scope: this,
            handler: this.handlers.linkContact
        });
        
        this.actions.unlinkContact = new Ext.Action({
            text: this.translation._('Unlink contact'),
            tooltip: this.translation._('Unlink selected contacts'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridContacts',
            storeName: 'ContactsStore',            
            handler: this.handlers.unlink
        });
        
        // tasks
        this.actions.addTask = new Ext.Action({
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add new task'),
            iconCls: 'actionAdd',
            //disabled: true,
            handler: this.handlers.addTask
        });
        
        this.actions.editTask = new Ext.Action({
            text: this.translation._('Edit task'),
            tooltip: this.translation._('Edit selected task'),
            disabled: true,
            iconCls: 'actionEdit',
            handler: this.handlers.editTask
        });
        
        this.actions.linkTask = new Ext.Action({
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add existing task to lead'),
            disabled: true,
            iconCls: 'actionAddTask',
            scope: this,
            handler: this.handlers.linkTask
        });

        this.actions.unlinkTask = new Ext.Action({
            text: this.translation._('Remove tasks'),
            tooltip: this.translation._('Remove selected tasks'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridTasks',
            storeName: 'TasksStore',            
            handler: this.handlers.unlink
        });

        // products
        this.actions.unlinkProduct = new Ext.Action({
            text: this.translation._('Remove products'),
            tooltip: this.translation._('Remove selected products'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridProducts',
            storeName: 'ProductsStore',            
            handler: this.handlers.unlink
        });

        // other
        this.actions.exportLead = new Ext.Action({
            text: this.translation._('Export as PDF'),
            tooltip: this.translation._('Export as PDF'),
            iconCls: 'action_exportAsPdf',
            scope: this,
            handler: this.handlers.exportLead,
            leadId: lead.data.id
        });
    },
    
    /**
     * display the event edit dialog
     *
     * @param   array   _lead
     */
    display: function(_lead) 
    {	
        // put lead data into model
        lead = new Tine.Crm.Model.Lead(_lead);
        Tine.Crm.Model.Lead.FixDates(lead);  
        
        this.initComponent(lead);
        
        //console.log(lead.data);
        //console.log(lead.data.tasks);
        //console.log(lead.data.responsible);
    	
        /*********** INIT STORES *******************/
        
        this.loadContactsStore(lead.data.responsible, lead.data.customer, lead.data.partner);        
        this.loadTasksStore(lead.data.tasks);
        this.loadProductsStore(lead.data.products);
                
        /*********** the EDIT dialog ************/
        
        var leadEdit = new Tine.widgets.dialog.EditRecord({
            id : 'leadDialog',
            tbarItems: [
                this.actions.exportLead
            ],
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            labelAlign: 'top',
            items: Tine.Crm.LeadEditDialog.getEditForm([
                        this.getLinksGrid('Contacts', this.translation._('Contacts')),
                        this.getLinksGrid('Tasks', this.translation._('Tasks')),
                        this.getLinksGrid('Products', this.translation._('Products'))
                    ])             
        });

        // add context menu events
        this.setLinksContextMenu('Contacts');
        this.setLinksContextMenu('Tasks');
        this.setLinksContextMenu('Products');

        // fix to have the tab panel in the right height accross browsers
        Ext.getCmp('editMainTabPanel').on('afterlayout', function(container) {
            var height = Ext.getCmp('leadDialog').getInnerHeight();
            Ext.getCmp('editMainTabPanel').setHeight(height-10);
        });
        
        var viewport = new Ext.Viewport({
            layout: 'border',
            id: 'editViewport',
            items: leadEdit
        });

        leadEdit.getForm().loadRecord(lead);
        
        // disable tasks/products grids for new lead
        if (lead.data.id) {
        	Ext.getCmp('crmGridTasks').setDisabled(false);
        } 
                
    } // end of function display()
}; // end of application CRM LEAD EDIT DIALOG

/*************************************** CRM MODELS *********************************/

Ext.namespace('Tine.Crm.Model');

// lead
Tine.Crm.Model.Lead = Ext.data.Record.create([
    {name: 'id',            type: 'int'},
    {name: 'lead_name',     type: 'string'},
    {name: 'leadstate_id',  type: 'int'},
    {name: 'leadtype_id',   type: 'int'},
    {name: 'leadsource_id', type: 'int'},
    {name: 'container',     type: 'int'},
    {name: 'modifier',      type: 'int'},
    {name: 'start',         type: 'date', dateFormat: 'c'},
    {name: 'modified'},
    {name: 'description',   type: 'string'},
    {name: 'end',           type: 'date', dateFormat: 'c'},
    {name: 'turnover',      type: 'int'},
    {name: 'probability',   type: 'int'},
    {name: 'end_scheduled', type: 'date', dateFormat: 'c'},
    {name: 'lastread'},
    {name: 'lastreader'},
    {name: 'responsible'},
    {name: 'customer'},
    {name: 'partner'},
    {name: 'tasks'},
    {name: 'products'},
    {name: 'tags'}
]);

// contact link
Tine.Crm.Model.ContactLink = Ext.data.Record.create([
    {name: 'id'},
    {name: 'link_id'},              
    {name: 'link_remark'},                        
    {name: 'owner'},
    {name: 'n_family'},
    {name: 'n_given'},
    {name: 'n_middle'},
    {name: 'n_prefix'},
    {name: 'n_suffix'},
    {name: 'n_fn'},
    {name: 'n_fileas'},
    {name: 'org_name'},
    {name: 'org_unit'},
    {name: 'adr_one_street'},
    {name: 'adr_one_locality'},
    {name: 'adr_one_region'},
    {name: 'adr_one_postalcode'},
    {name: 'adr_one_countryname'},
    {name: 'tel_work'},
    {name: 'tel_cell'},
    {name: 'tel_fax'},
    {name: 'email'}
]);

// task link
Tine.Crm.Model.TaskLink = Ext.data.Record.create([
    {name: 'id'},
    {name: 'link_id'},
    {name: 'status_id'},
    {name: 'status_realname'},
    {name: 'status_icon'},
    {name: 'percent'},
    {name: 'summary'},
    {name: 'due'},
    {name: 'creator'},
    {name: 'description'},
    {name: 'priority'}
]);

// product link
Tine.Crm.Model.ProductLink = Ext.data.Record.create([
    {name: 'id'},
    {name: 'product_id'},
    {name: 'product_desc'},
    {name: 'product_price'}
]);


// work arround nasty ext date bug
// @todo is that still needed?
Tine.Crm.Model.Lead.FixDates = function(lead) {
    lead.data.start         = lead.data.start         ? Date.parseDate(lead.data.start, 'c')         : lead.data.start;
    lead.data.end           = lead.data.end           ? Date.parseDate(lead.data.end, 'c')           : lead.data.end;
    lead.data.end_scheduled = lead.data.end_scheduled ? Date.parseDate(lead.data.end_scheduled, 'c') : lead.data.end_scheduled;
};
        
