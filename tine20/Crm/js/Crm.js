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

/**
 * entry point, required by tinebase
 * creates and returnes app tree panel
 */
Tine.Crm.getPanel = function(){
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
};

/*************************************** CRM MAIN DIALOG *****************************************/

// @todo remove closure

Tine.Crm.Main = function(){
    var translation = new Locale.Gettext();
    translation.textdomain('Crm');

    var handler = {
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId=' + _rowIndex[0].id, 900, 700);
        },
        handlerDelete: function(){
            Ext.MessageBox.confirm('Confirm', translation._('Are you sure you want to delete this lead?'), function(_button) {
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
                        text: translation._('Deleting lead') + '...',
                        success: function(_result, _request){
                            Ext.getCmp('gridCrm').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', translation._('Some error occured while trying to delete the lead.'));
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
        handlerAddTask: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            
            popupWindow = new Tine.Tasks.EditPopup({
                relatedApp: 'crm',
                relatedId: _rowIndex[0].id
            });
        }    
    };
    
    var actions = {
        actionAdd: new Ext.Action({
            text: 'Add lead',
            tooltip: 'Add new lead',
            iconCls: 'actionAdd',
            handler: function(){
                //  var tree = Ext.getCmp('venues-tree');
                //  var curSelNode = tree.getSelectionModel().getSelectedNode();
                //  var RootNode   = tree.getRootNode();
                Tine.Tinebase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 900, 700);
            }
        }),
        actionEdit: new Ext.Action({
            text: 'Edit lead',
            tooltip: 'Edit selected lead',
            disabled: true,
            handler: handler.handlerEdit,
            iconCls: 'actionEdit'
        }),
        actionDelete: new Ext.Action({
            text: 'Delete lead',
            tooltip: 'Delete selected leads',
            disabled: true,
            handler: handler.handlerDelete,
            iconCls: 'actionDelete'
        }),
        actionExport: new Ext.Action({
            text: 'Export as PDF',
            tooltip: 'Export selected lead as PDF',
            disabled: true,
            handler: handler.exportLead,
            iconCls: 'action_exportAsPdf',
            scope: this
        }),
        actionAddTask: new Ext.Action({
            text: 'Add task',
            tooltip: 'Add task for selected lead',
            handler: handler.handlerAddTask,
            iconCls: 'actionAddTask',
            disabled: true,
            scope: this
        })
    };
    
    /**
     * _createDataStore function
     */
    var _createDataStore = function()
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
    };

    /**
     * _showCrmToolbar function
     */
    var _showCrmToolbar = function()
    {
        for (var i in actions) {
            if (!actions[i].isTranlated) {
                actions[i].setText(translation._(actions[i].initialConfig.text));
                // tooltip happliy gets created on first mouseover
                actions[i].initialConfig.tooltip = translation._(actions[i].initialConfig.tooltip);
                actions[i].isTranslated = true;
            }
        }
        
        var storeProbability = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0 %'],
                        ['10','10 %'],
                        ['20','20 %'],
                        ['30','30 %'],
                        ['40','40 %'],
                        ['50','50 %'],
                        ['60','60 %'],
                        ['70','70 %'],
                        ['80','80 %'],
                        ['90','90 %'],
                        ['100','100 %']
                    ]
        });
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 200,
            emptyText: translation._('Enter searchfilter')
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
            fieldLabel: translation._('Leadstate'), 
            id:'filterLeadstate',
           //id:'id',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            blankText: translation._('Leadstate') + '...',
            hiddenName: 'leadstate_id',
            store: Tine.Crm.LeadState.getStore(),
            displayField: 'leadstate',
            valueField: 'leadstate_id',
            typeAhead: true,
            mode: 'remote',
            triggerAction: 'all',
            emptyText: translation._('leadstate') + '...',
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
      
       var filterComboProbability = new Ext.ux.form.ClearableComboBox({
            fieldLabel: translation._('Probability'), 
            id: 'filterProbability',
            name:'probability',
            hideLabel: true,            
            store: storeProbability,
            blankText: translation._('Probability') + '...',            
            displayField:'value',
            valueField:'key',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText: translation._('Probability') + '...',
            selectOnFocus:true,
            editable: false,
            renderer: Ext.util.Format.percentage,
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
                actions.actionAdd,
                actions.actionEdit,
                actions.actionDelete,
                actions.actionAddTask,
                actions.actionExport,
                '->',
                new Ext.Button({
                    tooltip: translation._('Show details'),
                    enableToggle: true,
                    id: 'crmShowDetailsButton',
                    iconCls: 'showDetailsAction',
                    cls: 'x-btn-icon',
                    handler: handlerToggleDetails
                }),                    
                '-',
                new Ext.Button({
                    tooltip: translation._('Show closed leads'),
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
    };
        
    /**
     * creates the grid
     * 
     */
    var _showGrid = function() 
    { 
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: translation._('Displaying leads {0} - {1} of {2}'),
            emptyMsg: translation._('No leads found.')
        }); 
        
        var ctxMenuGrid = new Ext.menu.Menu({
	        id:'ctxMenuGrid', 
	        items: [
	            actions.actionEdit,
	            actions.actionDelete,
	            actions.actionExport,
	            actions.actionAddTask
	        ]
	    });

/*        var expander = new Ext.grid.RowExpander({
            enableCaching: false,
            tpl : new Ext.Template(
                '<b>Notes:</b> {description}</div></td>',
                '<td class="x-grid3-col x-grid3-cell"><b>Activities:</b> </td>')
        }); */
        
        var columnModel = new Ext.grid.ColumnModel([
            
			{resizable: true, header: translation._('Lead id'), id: 'id', dataIndex: 'id', width: 20, hidden: true},
            {resizable: true, header: translation._('Lead name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
            {resizable: true, header: translation._('Partner'), id: 'lead_partner', dataIndex: 'partner', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: translation._('Customer'), id: 'lead_customer', dataIndex: 'customer', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: translation._('Leadstate'), id: 'leadstate', dataIndex: 'leadstate', sortable: false, width: 100,
                renderer: function(leadState) {return leadState.leadstate;}
            },
            {resizable: true, header: translation._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
            {resizable: true, header: translation._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                actions.actionDelete.setDisabled(true);
                actions.actionEdit.setDisabled(true);
                actions.actionExport.setDisabled(true);
                actions.actionAddTask.setDisabled(true);
            } 
            if (rowCount == 1) {
               actions.actionEdit.setDisabled(false);
               actions.actionDelete.setDisabled(false);               
               actions.actionExport.setDisabled(false);
               actions.actionAddTask.setDisabled(false);
            }    
            if(rowCount > 1) {                
               actions.actionEdit.setDisabled(true);
               actions.actionExport.setDisabled(true);
               actions.actionAddTask.setDisabled(true);
            }
        });
        
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
                emptyText: translation._('No leads found.')
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
    };
    
    /**
     * _loadData function
     */
    var _loadData = function(_node)
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
    };    
    
        
    // public functions and variables
    return {
        show:   function(_node) 
        {          
            this.translation = new Locale.Gettext();
            this.translation.textdomain('Crm');
            
            var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();
            if (currentToolbar === false || currentToolbar.id != 'crmToolbar') {
                _showCrmToolbar();
                _showGrid();
                this.updateMainToolbar();
            }
            _loadData(_node);
        },    
        
        updateMainToolbar : function() 
        {
            var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
            menu.removeAll();
            menu.add(
                {text: 'leadstate', handler: Tine.Crm.LeadState.EditStatesDialog},
                {text: 'leadsource', handler: Tine.Crm.Main.handlers.editLeadSource},
                {text: 'leadtype', handler: Tine.Crm.Main.handlers.editLeadType},
                {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
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
        
        handlers: 
        {
            editLeadSource: function(_button, _event) {
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
	            
	            var storeLeadsource = new Ext.data.JsonStore({
	                baseParams: {
	                    method: 'Crm.getLeadsources',
	                    sort: 'leadsource',
	                    dir: 'ASC'
	                },
	                root: 'results',
	                totalProperty: 'totalcount',
	                id: 'leadsource_id',
	                fields: [
	                    {name: 'id'},
	                    {name: 'leadsource'}
	                ],
	                // turn on remote sorting
	                remoteSort: false
	            });
	            
	            storeLeadsource.load();
	            
	            var columnModelLeadsource = new Ext.grid.ColumnModel([
	                    { id:'leadsource_id', 
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
	            
	            var entry = Ext.data.Record.create([
	               {name: 'leadsource_id', type: 'int'},
	               {name: 'leadsource', type: 'varchar'}
	            ]);
	            
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
            },
            
            editLeadType: function(_button, _event) {
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
	            
	            var storeLeadtype = new Ext.data.JsonStore({
	                baseParams: {
	                    method: 'Crm.getLeadtypes',
	                    sort: 'leadtype',
	                    dir: 'ASC'
	                },
	                root: 'results',
	                totalProperty: 'totalcount',
	                id: 'leadtype_id',
	                fields: [
	                    {name: 'id'},
	                    {name: 'leadtype'}
	                ],
	                // turn on remote sorting
	                remoteSort: false
	            });
	            
	            
	            storeLeadtype.load();
	            
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
	            
	            var entry = Ext.data.Record.create([
	               {name: 'id', type: 'int'},
	               {name: 'leadtype', type: 'varchar'}
	            ]);
	            
	            var handlerLeadtypeAdd = function(){
	                var p = new entry({
	                    leadtype_id: 'NULL',
	                    leadtype: ''
	                });
	                leadtypeGridPanel.stopEditing();
	                storeLeadtype.insert(0, p);
	                leadtypeGridPanel.startEditing(0, 0);
	                leadtypeGridPanel.fireEvent('celldblclick',this, 0, 1);                
	            };
	                        
	            var handlerLeadtypeDelete = function(){
	                var leadtypeGrid  = Ext.getCmp('editLeadtypeGrid');
	                var leadtypeStore = leadtypeGrid.getStore();
	                
	                var selectedRows = leadtypeGrid.getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    leadtypeStore.remove(selectedRows[i]);
	                }   
	            };                        
	                        
	          
	            var handlerLeadtypeSaveClose = function(){
	                var leadtypeStore = Ext.getCmp('editLeadtypeGrid').getStore();
	                
	                var leadtypeJson = Tine.Tinebase.Common.getJSONdata(leadtypeStore); 
	
	                 Ext.Ajax.request({
	                            params: {
	                                method: 'Crm.saveLeadtypes',
	                                optionsData: leadtypeJson
	                            },
	                            text: 'Saving leadtypes...',
	                            success: function(_result, _request){
	                                    leadtypeStore.reload();
	                                    leadtypeStore.rejectChanges();
	                               },
	                            failure: function(form, action) {
	                                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
	                                }
	                        });          
	            };          
	            
	            var leadtypeGridPanel = new Ext.grid.EditorGridPanel({
	                store: storeLeadtype,
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
                
            },
            
            editProductSource: function(_button, _event) {
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
	                fields: [
	                    {name: 'id'},
	                    {name: 'productsource'},
	                    {name: 'price'}
	                ],
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
            } 
        },
        reload: function() 
        {
                    if(Ext.ComponentMgr.all.containsKey('gridCrm')) {
                        setTimeout ("Ext.getCmp('gridCrm').getStore().reload()", 200);
                    }
        },
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
    };
}(); // end of application (CRM MAIN DIALOG)
  
/*************************************** LEAD EDIT DIALOG ****************************************/

Tine.Crm.LeadEditDialog = {
	
    /**
     * event handlers
     */
    handlers: {        
        applyChanges: function(_button, _event) 
        {
            //var grid_products          = Ext.getCmp('grid_choosenProducts');

            var closeWindow = arguments[2] ? arguments[2] : false;
            var leadForm = Ext.getCmp('leadDialog').getForm();
            
            if(leadForm.isValid()) {  
                Ext.MessageBox.wait(this.translation._('Please wait'), this.translation._('Saving lead') + '...');                
                leadForm.updateRecord(lead);
                
                // @todo add again
                //var additionalData = _getAdditionalData();
                
                Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveLead', 
                        lead: Ext.util.JSON.encode(lead.data),
                        linkedCustomer: Ext.util.JSON.encode([]),
                        linkedPartner:  Ext.util.JSON.encode([]),
                        linkedAccount:  Ext.util.JSON.encode([]),
                        linkedTasks:    Ext.util.JSON.encode([]),
                        products:       Ext.util.JSON.encode([])
                        /*
                        linkedCustomer: additionalData.linkedCustomer,
                        linkedPartner:  additionalData.linkedPartner,
                        linkedAccount:  additionalData.linkedAccount,
                        linkedTasks:    additionalData.linkedTasks,
                        products:       additionalData.products
                        */
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Crm) {
                            window.opener.Tine.Crm.Main.reload();
                        } 
                        if (closeWindow) {
                            window.setTimeout("window.close()", 400);
                        }
                        
                        // fill form with returned lead
                        lead = new Tine.Crm.Model.Lead(Ext.util.JSON.decode(_result.responseText));
                        Tine.Crm.Model.Lead.FixDates(lead);
                        leadForm.loadRecord(lead);
                        
                        //dlg.action_delete.enable();
                        //_add_task.enable();
                        //_export_lead.enable();
                        
                        Ext.MessageBox.hide();
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', this.translation._('Could not save lead.')); 
                    } 
                });
            } else {
                Ext.MessageBox.alert('Errors', this.translation._('Please fix the errors noted.'));
            }
        },
        saveAndClose: function(_button, _event) 
        {     
            handlers.applyChanges(_button, _event, true);
        }
    },       
         
    /**
     * _getAdditionalData function
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     * @return Object additionalData
     * 
     * @todo    check if that is needed
     */
    _getAdditionalData: function()
    {
    	/*
        var additionalData = {};
    
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
        
        
        // collect data of assosicated contacts
        var linksContactsCustomer = new Array();
        var storeContactsCustomer = Tine.Crm.LeadEditDialog.Stores.getContactsCustomer();
        
        storeContactsCustomer.each(function(record) {
            linksContactsCustomer.push(record.id);          
        });
        
        additionalData.linkedCustomer = Ext.util.JSON.encode(linksContactsCustomer);
        

        var linksContactsPartner = new Array();
        var storeContactsPartner = Tine.Crm.LeadEditDialog.Stores.getContactsPartner();
        
        storeContactsPartner.each(function(record) {
            linksContactsPartner.push(record.id);          
        });
        
        additionalData.linkedPartner = Ext.util.JSON.encode(linksContactsPartner);


        var linksContactsInternal = new Array();
        var storeContactsInternal = Tine.Crm.LeadEditDialog.Stores.getContactsInternal();
        
        storeContactsInternal.each(function(record) {
            linksContactsInternal.push(record.id);          
        });
        
        additionalData.linkedAccount = Ext.util.JSON.encode(linksContactsInternal);
        
        var linksTasks = new Array();
        var taskGrid = Ext.getCmp('gridActivities');
        var storeTasks = taskGrid.getStore();
        
        storeTasks.each(function(record) {
            linksTasks.push(record.data.id);          
        });
        
        additionalData.linkedTasks = Ext.util.JSON.encode(linksTasks);        
        
        return additionalData;
        */
    },

    /**
     * _getLinksGrid
     * get the grids for contacts/tasks/products/...
     * 
     * @param   string  type
     * @param   string  grid title
     * @return  grid object
     */
    _getLinksGrid: function(_type, _title)
    {
    	// only for testing
    	var grid = new Ext.Panel({
            title: _title,
        });
        
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
            var gridStore = Ext.StoreMgr.lookup('ContactsStore');
            
            //console.log(Ext.StoreMgr.lookup('ContactsStore'));

            var grid = {
                xtype:'grid',
                id: 'crm_grid' + _type,
                title: _title,
                cm: columnModel,
                store: gridStore,
                autoExpandColumn: 'n_fileas'
            };
        }
    	
        return grid;       
    },
    
    /**
     * _getOverviewPanel
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     * @param   array _tabpanels (contacts/activities/products)
     * @return  Object overview panel
     * 
     * @todo    move to extra file
     */
    _getOverviewPanel: function(_tabpanels)
    {
    	
        /*********** OVERVIEW form fields ************/

        var txtfld_leadName = new Ext.form.TextField({
            hideLabel: true,
            id: 'lead_name',
            //fieldLabel:'Projektname', 
            emptyText: this.translation._('Enter short name'),
            name:'lead_name',
            allowBlank: false,
            selectOnFocus: true,
            anchor:'100%'
            //selectOnFocus:true            
        }); 
 
        var combo_leadstatus = new Ext.form.ComboBox({
                fieldLabel: this.translation._('Leadstate'), 
                id:'leadstatus',
                name:'leadstate_id',
                store: Tine.Crm.LeadEditDialog.Stores.getLeadStatus(),
                displayField:'value',
                valueField:'key',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                listWidth: '25%',
                forceSelection: true,
                anchor:'95%'    
        });
        
        combo_leadstatus.on('select', function(combo, record, index) {
            if (record.data.probability !== null) {
                var combo_probability = Ext.getCmp('combo_probability');
                combo_probability.setValue(record.data.probability);
            }

            if (record.data.endslead == '1') {
                var combo_endDate = Ext.getCmp('end');
                combo_endDate.setValue(new Date());
            }
        });
        
        var combo_leadtyp = new Ext.form.ComboBox({
            fieldLabel: this.translation._('Leadtype'), 
            id:'leadtype',
            name:'leadtype_id',
            store: Tine.Crm.LeadEditDialog.Stores.getLeadType(),
            mode: 'local',
            displayField:'value',
            valueField:'key',
            typeAhead: true,
            triggerAction: 'all',
            listWidth: '25%',                
            editable: false,
            allowBlank: false,
            forceSelection: true,
            anchor:'95%'    
        });

        var st_leadsource = new Ext.data.JsonStore({
            data: formData.comboData.leadsources,
            autoLoad: true,
            id: 'key',
            fields: [
                {name: 'key', mapping: 'id'},
                {name: 'value', mapping: 'leadsource'}

            ]
        });     

        var combo_leadsource = new Ext.form.ComboBox({
                fieldLabel: this.translation._('Leadsource'), 
                id:'leadsource',
                name:'leadsource_id',
                store: st_leadsource,
                displayField:'value',
                valueField:'key',
                typeAhead: true,
                listWidth: '25%',                
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                anchor:'95%'    
        });

        var combo_probability =  new Ext.form.ComboBox({
            fieldLabel: this.translation._('Probability'), 
            id: 'combo_probability',
            name:'probability',
            store: Tine.Crm.LeadEditDialog.Stores.getProbability(),
            displayField:'value',
            valueField:'key',
            typeAhead: true,
            mode: 'local',
            listWidth: '25%',            
            triggerAction: 'all',
            emptyText:'',
            selectOnFocus:true,
            editable: false,
            renderer: Ext.util.Format.percentage,
            anchor:'95%'            
        });
        combo_probability.setValue('0');
             
        var date_start = new Ext.form.DateField({
            fieldLabel: this.translation._('Start'), 
            allowBlank: false,
            id: 'start',             
            anchor: '95%'
        });

        
        var date_scheduledEnd = new Ext.form.DateField({
            fieldLabel: this.translation._('Estimated end'), 
            id: 'end_scheduled',
            anchor: '95%'
        });
        
        var date_end = new Ext.form.DateField({
            xtype:'datefield',
            fieldLabel: this.translation._('End'), 
            id: 'end',
            anchor: '95%'
        });

        var folderTrigger = new Tine.widgets.container.selectionComboBox({
            fieldLabel: this.translation._('folder'),
            name: 'container',
            itemName: 'Leads',
            appName: 'crm',
            anchor:'95%'
        });
     
        /*********** OVERVIEW tab panel ************/

        var tabPanelOverview = {
            title: this.translation._('Overview'),
            layout:'border',
            layoutOnTabChange:true,
            defaults: {
                border: true,
                frame: true            
            },
            items: [{
                region: 'east',
                autoScroll: true,
                width: 300,
                items: [
                    new Ext.Panel({
                        title: this.translation._('Tags'),
                        height: 200
                    }),
                    new Ext.Panel({
                        title: this.translation._('History'),
                        height: 200
                    })
                    /*
                  new Ext.DataView({
                    tpl: ActivitiesTpl,       
                    autoHeight:true,                    
                    id: 'grid_activities_limited',
                    store: st_activities,
                    overClass: 'x-view-over',
                    itemSelector: 'activities-item-small'
                  })
                  */
                ]
            },{
                region:'center',
                layout: 'form',
                autoHeight: true,
                id: 'editCenterPanel',
                items: [
                    txtfld_leadName, 
                {
                    xtype:'textarea',
                    //fieldLabel:'Notizen',
                    id: 'lead_notes',
                    hideLabel: true,
                    name: 'description',
                    height: 120,
                    anchor: '100%',
                    emptyText: this.translation._('Enter description')
                }, {
                    layout:'column',
                    height: 140,
                    id: 'lead_combos',
                    anchor:'100%',                        
                    items: [{
                        columnWidth: .33,
                        items:[{
                            layout: 'form',
                            items: [
                                combo_leadstatus, 
                                combo_leadtyp,
                                combo_leadsource
                            ]
                        }]                          
                    },{
                        columnWidth: .33,
                        items:[{
                            layout: 'form',
                            border:false,
                            items: [
                            {
                                xtype:'numberfield',
                                fieldLabel: this.translation._('Expected turnover'), 
                                name: 'turnover',
                                selectOnFocus: true,
                                anchor: '95%'
                            },  
                                combo_probability,
                                folderTrigger 
                            ]
                        }]              
                    },{
                        columnWidth: .33,
                        items:[{
                            layout: 'form',
                            border:false,
                            items: [
                                date_start,
                                date_scheduledEnd,
                                date_end   
                            ]
                        }]
                    }]
                }, {
                    xtype: 'tabpanel',
                    style: 'margin-top: 10px;',
                    id: 'linkPanel',
                    //title: 'contacts panel',
                    activeTab: 0,
                    height: 273,
                    items: _tabpanels,
                }
                ]
            }]
        };        
        
        // return the panel
        return tabPanelOverview;       
    },    
    
    /**
     * initComponent
     * sets the translation object and actions
     */
    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
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
    	
    	// @todo use that?
    	/*
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';

        var disableButtons = true;

        var _setParameter = function(_dataSource) {
            _dataSource.baseParams.method = 'Crm.getEvents';
            _dataSource.baseParams.options = Ext.encode({
            });
        };
 
        var _editHandler = function(_button, _event) {
            editWindow.show();
        };  
  
        if (_leadData.data) {                    
            var _lead_id = _leadData.data.id;
        } else {
            var _lead_id = 'NULL';
        }
        */

        /*********** INIT STORES *******************/
        
        var contactsStore = Tine.Crm.LeadEditDialog.Stores.getContacts(lead.data.contacts);
        Ext.StoreMgr.add('ContactsStore', contactsStore);
        
        /*********** OVERVIEW tab panel ************/

        var tabPanelOverview = this._getOverviewPanel([
                        this._getLinksGrid('Contacts', this.translation._('Contacts')),
                        this._getLinksGrid('Tasks', this.translation._('Tasks')),
                        this._getLinksGrid('Products', this.translation._('Products'))
                    ]);
        
        /*********** HISTORY tab panel ************/

        // @todo    add implemented histoy tab panel
        var tabPanelHistory = {
            title: this.translation._('History'),
            disabled: true,
            layout:'border',
            layoutOnTabChange:true,
            defaults: {
                border: true,
                frame: true            
            },
        };
        
        /*********** the EDIT dialog ************/
        
        var leadEdit = new Tine.widgets.dialog.EditRecord({
            id : 'leadDialog',
            //tbarItems: [_add_task, _export_lead],
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            //handlerDelete: Tine.Crm.LeadEditDialog.Handler.handlerDelete,
            labelAlign: 'top',
            items: new Ext.TabPanel({
                plain:true,
                activeTab: 0,
                id: 'editMainTabPanel',
                layoutOnTabChange:true,  
                items:[
                    tabPanelOverview,
                    tabPanelHistory                    
                    //Tine.Crm.LeadEditDialog.Elements.getTabPanelManageContacts(),                    
                    //tabPanelActivities, 
                    //tabPanelProducts
                ]
            })
        });
        
        // disable for the moment
        // @todo enable again
        leadEdit.action_applyChanges.setDisabled(true);
        leadEdit.action_saveAndClose.setDisabled(true);
        
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

/*************************************** LEAD EDIT DIALOG HANDLER ********************************/

/*
Tine.Crm.LeadEditDialog.Handler = function() {
    return { 
        removeContact: function(_button, _event) 
        {
            //console.log('remove contact');           
            var currentContactsTab = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab();
            
            var selectedRows = currentContactsTab.getSelectionModel().getSelections();
            var currentContactsStore = currentContactsTab.getStore();

            for (var i = 0; i < selectedRows.length; ++i) {
                currentContactsStore.remove(selectedRows[i]);
            }
    
            //Ext.getCmp('Addressbook_Grants_SaveButton').enable();
            //Ext.getCmp('Addressbook_Grants_ApplyButton').enable();
        },

        addContact: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
        },    
        
        addContactToList: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('crm_editLead_SearchContactsGrid').getSelectionModel().getSelections();
            var currentContactsStore = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getStore();

            for (var i = 0; i < selectedRows.length; ++i) {
                if(currentContactsStore.getById(selectedRows[i].id) === undefined) {
                    //console.log('record ' + record.id + 'not found');
                    currentContactsStore.addSorted(selectedRows[i], selectedRows[i].id);
                }
            }
            
            var selectionModel = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getSelectionModel();
            
            selectionModel.selectRow(currentContactsStore.indexOfId(selectedRows[0].id));
        },  
              
        handlerDelete: function() 
        {
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete this lead?', function(_button) {
                if(_button == 'yes') {      
                    var leadIds;// = Ext.util.JSON.encode([formData.values.id]);
                    
                    Ext.Ajax.request({
                        params: {
                            method: 'Crm.deleteLeads',
                            _leadIds: leadIds
                        },
                        text: 'Deleting lead...',
                        success: function(_result, _request){
                            window.opener.Tine.Crm.reload();
                            window.setTimeout("window.close()", 400);
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the lead.');
                        }
                    });
                } 
            });
        }
    };
}();
*/

/*************************************** LEAD EDIT DIALOG STORES *********************************/

// @todo move that to contact edit dialog

Tine.Crm.LeadEditDialog.Stores = function() {
	
	// private attributes
	var _storeContacts = null;
	
    // public functions and variables
    return {
    	// @todo replace this with contact model?
        contactFields: [
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
            {name: 'email'},
            //{name: 'type'},
        ],
        
        /**
         * get linked contacts store
         * 
         * @param   array (?) contacts
         * @return  Ext.data.JsonStore
         * 
         * @todo    make it work!
         */
        getContacts: function (_contacts){  
                 
            if (_storeContacts === null) {
                _storeContacts = new Ext.data.JsonStore({
                    id: 'id',
                    fields: this.contactFields
                    //fields: Tine.Addressbook.Model.Contact
                });
            }                
                
            if(_contacts) {
                _storeContacts.loadData(_contacts);                    
                _storeContacts.setDefaultSort('remark', 'asc');     
            }
            
            
            return _storeContacts;            
        },

        /**
         * get lead status store
         * 
         * @return  Ext.data.JsonStore
         */
        getLeadStatus: function (){
            var store = new Ext.data.JsonStore({
                data: formData.comboData.leadstates,
                autoLoad: true,         
                id: 'key',
                fields: [
                    {name: 'key', mapping: 'id'},
                    {name: 'value', mapping: 'leadstate'},
                    {name: 'probability', mapping: 'probability'},
                    {name: 'endslead', mapping: 'endslead'}
                ]
            });
            
            return store;
        },
        
        /**
         * get lead type store
         * 
         * @return  Ext.data.JsonStore
         */
        getLeadType: function (){
            var store = new Ext.data.JsonStore({
                data: formData.comboData.leadtypes,
                autoLoad: true,
                id: 'key',
                fields: [
                    {name: 'key', mapping: 'id'},
                    {name: 'value', mapping: 'leadtype'}
    
                ]
            });     
            
            return store;
        },

        /**
         * get probability store
         * 
         * @return  Ext.data.SimpleStore
         */
        getProbability: function (){
            var store = new Ext.data.SimpleStore({
                    fields: ['key','value'],
                    data: [
                            ['0','0%'],
                            ['10','10%'],
                            ['20','20%'],
                            ['30','30%'],
                            ['40','40%'],
                            ['50','50%'],
                            ['60','60%'],
                            ['70','70%'],
                            ['80','80%'],
                            ['90','90%'],
                            ['100','100%']
                        ]
            });
            
            return store;
        }
    };
}();

/*************************************** CRM MODELS *********************************/

Ext.namespace('Tine.Crm.Model');

// lead
Tine.Crm.Model.Lead = Ext.data.Record.create([
    {name: 'id',            type: 'int'},
    {name: 'lead_name',     type: 'string'},
    {name: 'leadstate_id',  type: 'int'},
    {name: 'leadtype_id',   type: 'int'},
    {name: 'leadstate',     type: 'int'},
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
    {name: 'leadstate'},
    {name: 'leadtype'},
    {name: 'leadsource'},
    {name: 'partner'},
    {name: 'customer'}
  //  {name: 'leadpartner_linkId'},
  //  {name: 'leadpartner_detail'},                
  //  {name: 'leadlinkId'},
  //  {name: 'leaddetail'}  
]);

// work arround nasty ext date bug
// @todo is that still needed?
Tine.Crm.Model.Lead.FixDates = function(lead) {
    lead.data.start         = lead.data.start         ? Date.parseDate(lead.data.start, 'c')         : lead.data.start;
    lead.data.end           = lead.data.end           ? Date.parseDate(lead.data.end, 'c')           : lead.data.end;
    lead.data.end_scheduled = lead.data.end_scheduled ? Date.parseDate(lead.data.end_scheduled, 'c') : lead.data.end_scheduled;
};
        
