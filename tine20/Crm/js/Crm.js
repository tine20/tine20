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

    actions: {
        addLead: null,
        editLead: null,
        deleteLead: null,
        exportLead: null,
        addTask: null
    },

	handlers: {
		/**
		 * edit lead
		 */
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId=' + _rowIndex[0].id, 900, 700);
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
            var selectedRows = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            var leadId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadId=' + leadId, 768, 1024);
        },
        
        /**
         * add task handler
         */
        handlerAddTask: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            
            popupWindow = new Tine.Tasks.EditPopup({
                relatedApp: 'crm',
                relatedId: _rowIndex[0].id
            });
        },
        
    },
        
    /**
     * createDataStore function
     */
    createDataStore: function()
    {
        var storeCrm = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadsByOwner',
                owner: 'all'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Crm.Model.Lead,
            // turn on remote sorting
            remoteSort: true
        });
        
        storeCrm.setDefaultSort('lead_name', 'asc');

        storeCrm.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter           = Ext.getCmp('quickSearchField').getRawValue();
            _dataSource.baseParams.leadstate        = Ext.getCmp('filterLeadstate').getValue();
            _dataSource.baseParams.probability      = Ext.getCmp('filterProbability').getValue();
            _dataSource.baseParams.getClosedLeads   = Ext.getCmp('crmShowClosedLeadsButton').pressed;
        });        
        
        //storeCrm.load({params:{start:0, limit:50}});
        
        return storeCrm;
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
        quickSearchField.on('change', function(){
            Ext.getCmp('gridCrm').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                    //state: Ext.getCmp('filterLeadstate').getValue(),
                    //probability: Ext.getCmp('filterProbability').getValue()                   
                }
            });
        });

            
       var filterComboLeadstate = new Ext.ux.form.ClearableComboBox({
            fieldLabel: this.translation._('Leadstate'), 
            id:'filterLeadstate',
           //id:'id',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            blankText: this.translation._('Leadstate') + '...',
            hiddenName: 'leadstate_id',
            store: Tine.Crm.LeadState.getStore(),
            displayField: 'leadstate',
            valueField: 'leadstate_id',
            typeAhead: true,
            mode: 'remote',
            triggerAction: 'all',
            emptyText: this.translation._('leadstate') + '...',
            selectOnFocus:true,
            editable: false 
       });          
       filterComboLeadstate.on('select', function(combo, record, index) {
            var _leadState = '';
            if (record.data) {
                _leadstate = record.data.leadstate_id;
            }
           
            Ext.getCmp('gridCrm').getStore().load({
                params: {                
	                start: 0,
	                limit: 50,
					leadstate: _leadstate,
					probability: Ext.getCmp('filterProbability').getValue()
                }
            });
        });
      
        var filterComboProbability = new Ext.ux.PercentCombo({
            fieldLabel: this.translation._('Probability'), 
            blankText: this.translation._('Probability') + '...',            
            emptyText: this.translation._('Probability') + '...',
            id: 'filterProbability',
            name:'probability',
            hideLabel: true,            
            width:90            	
        });
                
		filterComboProbability.on('select', function(combo, record, index) {
            var _probability = '';       
		    if (record.data) {
		       _probability = record.data.key;
		    }           
		   
		    Ext.getCmp('gridCrm').getStore().load({
			    params: {                    
			        start: 0,
			        limit: 50
		        }
		    });         
		});      
		
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
        var dataStore = this.createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
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
            {resizable: true, header: this.translation._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100,
                //renderer: function(leadState) {return leadState.leadstate;}
                renderer: Tine.Crm.LeadState.Renderer
            },
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
               this.actions.addTask.setDisabled(false);
            }    
            if(rowCount > 1) {                
               this.actions.editLead.setDisabled(true);
               this.actions.deleteLead.setDisabled(false);
               this.actions.exportLead.setDisabled(true);
               this.actions.addTask.setDisabled(true);
            }
        }, this);
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: dataStore,
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
                actions.actionDelete.setDisabled(false);
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+record.data.id, 900, 700);            
        });
       
       return;
    },
    
    /**
     * loadData function
     */
    loadData: function(_node)
    {       
        var dataStore = Ext.getCmp('gridCrm').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.containerType) {
            case 'shared':
                dataStore.baseParams.method = 'Crm.getSharedLeads';
                break;
                  
            case 'otherUsers':
                dataStore.baseParams.method = 'Crm.getOtherPeopleLeads';
                break;

            case 'all':
                dataStore.baseParams.method = 'Crm.getAllLeads';
                break;

            case 'personal':
                dataStore.baseParams.method = 'Crm.getLeadsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner.accountId;
                break;

            case 'singleContainer':
                dataStore.baseParams.method        = 'Crm.getLeadsByFolder';
                dataStore.baseParams.folderId = _node.attributes.container.id;
                break;
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50
                //state: Ext.getCmp('filterLeadstate').getValue(),
                //probability: Ext.getCmp('filterProbability').getValue()                                                                               
            }
        });
    },    
      
    /**
     * initComponent
     * set translation and actions
     */
    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
    
        this.actions.addLead = new Ext.Action({
            text: this.translation._('Add lead'),
            tooltip: this.translation._('Add new lead'),
            iconCls: 'actionAdd',
            handler: function(){
                //  var tree = Ext.getCmp('venues-tree');
                //  var curSelNode = tree.getSelectionModel().getSelectedNode();
                //  var RootNode   = tree.getRootNode();
                Tine.Tinebase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 900, 700);
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
        }
        this.loadData(_node);
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
            {text: 'leadstate', handler: Tine.Crm.LeadState.EditDialog},
            {text: 'leadsource', handler: Tine.Crm.LeadType.EditDialog},
            {text: 'leadtype', handler: Tine.Crm.LeadSource.EditDialog},
            {text: 'product', handler: Tine.Crm.Product.EditDialog}
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
                var org = ( _data[0].org_name != null ) ? _data[0].org_name : '';
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
                    
                    contactDetails = contactDetails + '<table width="100%" height="100%" class="' + _style + '">'
                                         + '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(org_name) + '</td></tr>'
                                         + '<tr><td colspan="2"><b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b></td></tr>'
                                         + '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_street) + '</td></tr>'
                                         + '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + adr_one_locality + '</td></tr>'
                                         + '<tr><td width="50%">phone: </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_work) + '</td></tr>'
                                         + '<tr><td width="50%">cellphone: </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_cell) + '</td></tr>'
                                         + '</table> <br />';
                }
                
                return contactDetails;
            }
        }
    }    
}; // end of application (CRM MAIN DIALOG)
  
/*************************************** LEAD EDIT DIALOG ****************************************/

Tine.Crm.LeadEditDialog = {
	
    /**
     * event handlers
     */
    handlers: {   
    	
    	/**
    	 * apply changes
    	 */
        applyChanges: function(_button, _event, _closeWindow) 
        {
            //var grid_products          = Ext.getCmp('grid_choosenProducts');

            var leadForm = Ext.getCmp('leadDialog').getForm();
            
            if(leadForm.isValid()) {  
                Ext.MessageBox.wait(Tine.Crm.LeadEditDialog.translation._('Please wait'), Tine.Crm.LeadEditDialog.translation._('Saving lead') + '...');                
                leadForm.updateRecord(lead);
                
                // get linked stuff
                var additionalData = Tine.Crm.LeadEditDialog.getAdditionalData();
                
                Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveLead', 
                        lead: Ext.util.JSON.encode(lead.data),
                        linkedContacts: additionalData.linkedContacts,
                        linkedTasks:    additionalData.linkedTasks,
                        // @todo send links via json again
                        products:       Ext.util.JSON.encode([])
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Crm) {
                            window.opener.Tine.Crm.Main.reload();
                        } 
                        if (_closeWindow === true) {
                            window.setTimeout("window.close()", 400);
                        }
                        
                        // fill form with returned lead
                        /*
                        lead = new Tine.Crm.Model.Lead(Ext.util.JSON.decode(_result.updatedData));
                        Tine.Crm.Model.Lead.FixDates(lead);
                        leadForm.loadRecord(lead);
                        */
                        
                        //dlg.action_delete.enable();
                        //_add_task.enable();
                        //_export_lead.enable();
                        
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
        }
    },       

    /**
     * getAdditionalData
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     * @return  Object additionalData (json encoded)
     * @todo    add other stores and stuff
     */
    getAdditionalData: function()
    {
        var additionalData = {};          
        
        // collect data of assosicated contacts
        var linksContacts = new Array();
        var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
        
        storeContacts.each(function(record) {
            linksContacts.push({ 
                recordId: record.id, 
                remark: record.data.link_remark          
            });
        });

        additionalData.linkedContacts = Ext.util.JSON.encode(linksContacts);
        
        var linksTasks = new Array();
        var storeTasks = Ext.StoreMgr.lookup('TasksStore');
        
        storeTasks.each(function(record) {
            linksTasks.push(record.data.id);          
        });
        
        additionalData.linkedTasks = Ext.util.JSON.encode(linksTasks);        

        /*
        var store_products      = Ext.getCmp('grid_choosenProducts').getStore();       
        additionalData.products = Tine.Tinebase.Common.getJSONdata(store_products);

        // the start date (can not be empty)
        var startDate = Ext.getCmp('start').getValue();
        additionalData.start = startDate.format('c');

        // the end date
        var endDate = Ext.getCmp('end').getValue();
        if(typeof endDate == 'object') {
            additionalData.end = endDate.format('c');
        } else {
            additionalData.end = null;
        }

        // the estimated end
        var endScheduledDate = Ext.getCmp('end_scheduled').getValue();
        if(typeof endScheduledDate == 'object') {
            additionalData.end_scheduled = endScheduledDate.format('c');
        } else {
            additionalData.end_scheduled = null;
        }
        
        */
        
        return additionalData;
        
    },
    
    /**
     * get task status icon
     * @todo    use all stati store (see: Tasks/js/Status.js)
     */    
    getTaskStatusIcon: function(status_icon) {   
    	
    	//console.log(statusIcon);
    	var status_realname = 'xxx'; 
    	
        //return '<div class="TasksMainGridStatus-' + statusName + '" ext:qtip="' + statusName + '"></div>';
    	//return '<img class="TasksMainGridStatus" src="' + status_icon + '" ext:qtip="' + status_realname + '">';
    	return '<img class="TasksMainGridStatus" src="' + status_icon + '">';
    },
    
    /**
     * getLinksGrid
     * get the grids for contacts/tasks/products/...
     * 
     * @param   string  type
     * @param   string  grid title
     * @return  grid object
     * 
     * @todo    add products grid
     * @todo    move to LeadEditDialog.js ?
     */
    getLinksGrid: function(_type, _title)
    {
    	// set the column model
        if ( _type === 'Contacts' ) {
        	// @todo   move that to renderer/addressbook
        	// @todo   add icon in remark column
            var columnModel = new Ext.grid.ColumnModel([
                {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
                {id:'n_fileas', header: this.translation._('Name'), dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                    function(val, meta, record) {
                        var org_name = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : '&nbsp;';
                        
                        var formated_return = '<b>' + Ext.util.Format.htmlEncode(record.data.n_fileas) + '</b><br />' + Ext.util.Format.htmlEncode(org_name);
                        
                        return formated_return;
                    }
                },
                {id:'contact_one', header: this.translation._("Address"), dataIndex: 'adr_one_locality', width: 160, sortable: false, renderer: function(val, meta, record) {
                    var formated_return =  
                        Ext.util.Format.htmlEncode(record.data.adr_one_street) + '<br />' + 
                        Ext.util.Format.htmlEncode(record.data.adr_one_postalcode) + ' ' + Ext.util.Format.htmlEncode(record.data.adr_one_locality);
                    
                        return formated_return;
                    }
                },
                {id:'tel_work', header: this.translation._("Contactdata"), dataIndex: 'tel_work', width: 160, sortable: false, renderer: function(val, meta, record) {
                    var formated_return = '<table>' + 
                        '<tr><td>Phone: </td><td>' + Ext.util.Format.htmlEncode(record.data.tel_work) + '</td></tr>' + 
                        '<tr><td>Cellphone: </td><td>' + Ext.util.Format.htmlEncode(record.data.tel_cell) + '</td></tr>' + 
                        '</table>';
                    
                        return formated_return;
                    }
                },    
                {id:'link_remark', header: this.translation._("Type"), dataIndex: 'link_remark', width: 70, sortable: false}
            ]);
            
            var autoExpand = 'n_fileas';

        } else if ( _type === 'Tasks' ) {

            // tasks grid
            var columnModel = new Ext.grid.ColumnModel([
                {   id:'id', 
                    header: this.translation._("Identifier"), 
                    dataIndex: 'id', 
                    width: 5, 
                    sortable: true, 
                    hidden: true 
                }, {
                    id: 'status_id',
                    header: this.translation._("Status"),
                    width: 45,
                    sortable: true,
                    dataIndex: 'status_icon',
                    //dataIndex: 'status_realname',
                    renderer: this.getTaskStatusIcon
                }, {
                    id: 'percent',
                    header: this.translation._("Percent"),
                    width: 50,
                    sortable: true,
                    dataIndex: 'percent',
                    renderer: Ext.ux.PercentRenderer
                }, {
                    id: 'summary',
                    header: this.translation._("Summary"),
                    width: 200,
                    sortable: true,
                    dataIndex: 'summary'
                }, {
                    id: 'due',
                    header: this.translation._("Due date"),
                    width: 80,
                    sortable: true,
                    dataIndex: 'due',
                    // @todo fix date
                    hidden: true,
                    renderer: Tine.Tinebase.Common.dateRenderer
                }, {
                    id: 'creator',
                    header: this.translation._("Creator"),
                    width: 130,
                    sortable: true,
                    dataIndex: 'creator'
                }, {
                    id: 'description',
                    header: this.translation._("Description"),
                    width: 240,
                    sortable: false,
                    dataIndex: 'description',
                    hidden: true
                }                               
            ]);                       	
        	
            var autoExpand = 'summary';

        } else if ( _type === 'Products' ) {
            var columnModel = new Ext.grid.ColumnModel([
                {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true }
            ]);
            
            var autoExpand = '';

        }

        // get store and create grid
        var gridStore = Ext.StoreMgr.lookup(_type + 'Store');        
        var grid = {
            xtype:'grid',
            id: 'crm_grid' + _type,
            title: _title,
            cm: columnModel,
            store: gridStore,
            autoExpandColumn: autoExpand
        };
        
        if ( _type === 'Products' ) {
        	grid.disabled = true;
        }
        
        return grid;       
    },
    
    /**
     * get linked contacts store and put it into store manager
     * 
     * @param   array _contacts
     */
    loadContactsStore: function(_contacts)
    {
        var storeContacts = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Crm.Model.ContactLink
        });
            
        if(_contacts) {
            storeContacts.loadData(_contacts);                    
            storeContacts.setDefaultSort('remark', 'asc');     
        }
        
        Ext.StoreMgr.add('ContactsStore', storeContacts);
    },

    /**
     * get linked tasks store and put it into store manager
     * 
     * @param   array _tasks
     */
    loadTasksStore: function(_tasks)
    {
        var storeTasks = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Crm.Model.TaskLink
        });
            
        if(_tasks) {
            storeTasks.loadData(_tasks);                    
            //storeTasks.setDefaultSort('remark', 'asc');     
        }
        
        //console.log(storeTasks);
        
        Ext.StoreMgr.add('TasksStore', storeTasks);
    },

    /**
     * get linked products store and put it into store manager
     * 
     * @param   array _products
     * @todo    implement + use
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
        
        Ext.StoreMgr.add('ProductsStore', storeProducts);
    },
    
    /**
     * initComponent
     * sets the translation object and actions
     */
    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
        
        // @todo add actions here?
    },
    
    /**
     * display the event edit dialog
     *
     * @param   array   _lead
     */
    display: function(_lead) 
    {	
    	this.initComponent();
    	
        // put lead data into model
        lead = new Tine.Crm.Model.Lead(_lead);
        Tine.Crm.Model.Lead.FixDates(lead);  
        
        //console.log(lead);
    	
        /*********** INIT STORES *******************/
        
        this.loadContactsStore(lead.data.contacts);        
        this.loadTasksStore(lead.data.tasks);
        this.loadProductsStore(lead.data.products);
                
        /*********** the EDIT dialog ************/
        
        var leadEdit = new Tine.widgets.dialog.EditRecord({
            id : 'leadDialog',
            //tbarItems: [_add_task, _export_lead],
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            labelAlign: 'top',
            items: Tine.Crm.LeadEditDialog.getEditForm([
                        this.getLinksGrid('Contacts', this.translation._('Contacts')),
                        this.getLinksGrid('Tasks', this.translation._('Tasks')),
                        this.getLinksGrid('Products', this.translation._('Products'))
                    ])             
        });
        
        // fix to have the tab panel in the right height accross browsers
        Ext.getCmp('editMainTabPanel').on('afterlayout', function(container){
            var height = Ext.getCmp('leadDialog').getInnerHeight();
            Ext.getCmp('editMainTabPanel').setHeight(height-10);
        });
        
        var viewport = new Ext.Viewport({
            layout: 'border',
            id: 'editViewport',
            items: leadEdit
        });

        leadEdit.getForm().loadRecord(lead);
                
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
//    {name: 'leadstate',     type: 'int'},
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
    {name: 'contacts'},
    {name: 'tasks'},
    {name: 'products'},
    {name: 'tags'}
//    {name: 'leadstate'},
//    {name: 'leadtype'},
//    {name: 'leadsource'},
  //  {name: 'partner'},
  //  {name: 'customer'}
  //  {name: 'leadpartner_linkId'},
  //  {name: 'leadpartner_detail'},                
  //  {name: 'leadlinkId'},
  //  {name: 'leaddetail'}  
]);

// contact link
Tine.Crm.Model.ContactLink = Ext.data.Record.create([
    {name: 'link_id'},              
    {name: 'link_remark'},                        
    {name: 'id'},
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
    {name: 'status_id'},
    {name: 'status_realname'},
    {name: 'status_icon'},
    {name: 'percent'},
    {name: 'summary'},
    {name: 'due'},
    {name: 'creator'},
    {name: 'description'}
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
        
