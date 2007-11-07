Ext.namespace('Egw.Crm');

Egw.Crm = function() {

   /**
     * onclick handler for edit action
     */
    var _deleteHandler = function(_button, _event) {
    	Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected access log entries?', function(_button) {
    		if(_button == 'yes') {
    			var logIds = Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].id);
                }
                
                new Ext.data.Connection().request( {
                    url : 'index.php',
                    method : 'post',
                    scope : this,
                    params : {
                        method : 'Admin.deleteAccessLogEntries',
                        logIds : Ext.util.JSON.encode(logIds)
                    },
                    callback : function(_options, _success, _response) {
                        if(_success == true) {
                        	var result = Ext.util.JSON.decode(_response.responseText);
                        	if(result.success == true) {
                                Ext.getCmp('gridAdminAccessLog').getStore().reload();
                        	}
                        }
                    }
                });
    		}
    	});
    }

    var _selectAllHandler = function(_button, _event) {
    	Ext.getCmp('gridAdminAccessLog').getSelectionModel().selectAll();
    }

    var _action_delete = new Ext.Action({
        text: 'delete entry',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_delete'
    });

    var _action_selectAll = new Ext.Action({
        text: 'select all',
        handler: _selectAllHandler
    });

    var _contextMenuGridAdminAccessLog = new Ext.menu.Menu({
        items: [
            _action_delete,
            '-',
            _action_selectAll 
        ]
    });

    var _initialTree = [{
		"text":"Projekte",
		"cls":"treemain",
		"allowDrag":false,
		"allowDrop":true,
		"id":"projekte",
		"icon":"images\/oxygen\/16x16\/apps\/package-multimedia.png",
		"application":"Crm",
		"datatype":"projekte",
		"children":[{
			"text":"Leads",
			"cls":"file",
			"allowDrag":false,
			"allowDrop":true,
			"id":"leads",
			"icon":false,
			"application":"Crm",
			"datatype":"leads",
			"children":[],
			"leaf":null,
			"contextMenuClass":"ctxMenuLeadsTree",
			"expanded":true,
			"owner":"currentuser",
			"jsonMethod":"Crm.getLeadsByOwner",
			"dataPanelType":"leads"
			},{
			"text":"Partner",
			"cls":"file",
			"allowDrag":false,
			"allowDrop":true,
			"id":"partner",
			"icon":false,
			"application":"Crm",
			"datatype":"partner",
			"children":[],
			"leaf":null,
			"contextMenuClass":"ctxMenuPartnerTree",
			"expanded":true,
			"owner":"currentuser",
			"jsonMethod":"Crm.getPartnerByOwner",
			"dataPanelType":"partner"
		}],
		"leaf":null,
		"contextMenuClass":"ctxMenuProject",
		"owner":"allprojects",
		"jsonMethod":"Crm.getProjectsByOwner",
		"dataPanelType":"projects"
	}];
        
    var _getCrmTree = function() 
    {
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method   = 'Crm.getSubTree';
            _loader.baseParams.node     = _node.id;
            _loader.baseParams.datatype = _node.attributes.datatype;
            _loader.baseParams.owner    = _node.attributes.owner;
            _loader.baseParams.location = 'mainTree';
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Crm',
            id: 'crm-tree',
            loader: treeLoader,
            rootVisible: false,
            border: false
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        treePanel.setRootNode(treeRoot);

        for(i=0; i<initialTree.Crm.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree.Crm[i]));
        }
   
        treePanel.on('click', function(_node, _event) {
            alert(_node);
            
        	var currentToolbar = Egw.Egwbase.getActiveToolbar();

        	switch(_node.attributes.dataPanelType) {
                case 'lead':
                    Egw.Crm.show();
                    if(currentToolbar != false && currentToolbar.id == 'toolbarCrmLead') {
                        Ext.getCmp('gridCrmLead').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Egw.Crm.Lead.show();
                    }
                    
                    break;
                    
                case 'partner':
                    Egw.Crm.show();
                    if(currentToolbar != false && currentToolbar.id == 'toolbarCrmPartner') {
                    	Ext.getCmp('gridCrmPartner').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Egw.Crm.Partner.show();
                    }
                        
                    break;
                    
                case 'projects':
                    Egw.Crm.show();
                        
                    break;    
                    
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() == null) {
                _panel.expandPath('/root/projekte');
                _panel.selectPath('/root/projekte');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
            /* switch(_node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(_event.getXY());
                    break;
            } */
        });

        return treePanel;
    }    
    
    
    var _createDataStore = function()
    {
        /**
         * the datastore for accesslog entries
         */
/*         var ds_crm = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Crm.getAllProjects'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'pj_id',
            fields: [
                {name: 'pj_name'},
                {name: 'pj_distributionphase'},
                {name: 'pj_customertype'},
                {name: 'pj_leadsource'},
                {name: 'pj_owner'},
                {name: 'pj_modifier'},
                {name: 'pj_start'},
                {name: 'pj_modified'},
                {name: 'pj_description'},
                {name: 'pj_end'},
                {name: 'pj_turnover'},
                {name: 'pj_probability'},
                {name: 'pj_end_scheduled'},
                {name: 'pj_lastread'},
                {name: 'pj_lastreader'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
     */
/* 
        var projects = [
			['Dentales','Henry Schein Dental Depot GmbH<br>Kenning, Sebastian','Grub, Jörg',''],
            
		]; */
		var ds_crm = new Ext.data.SimpleStore({
			fields: [
                {name: 'pj_name'},
                {name: 'pj_distributionphase'},
                {name: 'pj_customertype'},
                {name: 'pj_leadsource'},
                {name: 'pj_owner'},
                {name: 'pj_modifier'},
                {name: 'pj_start'},
                {name: 'pj_modified'},
                {name: 'pj_description'},
                {name: 'pj_end'},
                {name: 'pj_turnover'},
                {name: 'pj_probability'},
                {name: 'pj_end_scheduled'},
                {name: 'pj_lastread'},
                {name: 'pj_lastreader'}
            ],
		//	data : projects
		});     


    
        ds_crm.setDefaultSort('pj_name', 'asc');

        ds_crm.on('beforeload', function(_dataSource) {
        	_dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        	/*
        	var from = Date.parseDate(
        	   Ext.getCmp('adminApplications_dateFrom').getRawValue(),
        	   'm/d/y'
        	);
            _dataSource.baseParams.from   = from.format("Y-m-d\\T00:00:00");

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               'm/d/y'
            );
            _dataSource.baseParams.to     = to.format("Y-m-d\\T23:59:59");  */
        });        
        
        ds_crm.load({params:{start:0, limit:50}});
        
        return ds_crm;
    }

    var _showToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id:        'quickSearchField',
            width:     200,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('gridCrm').getStore().load({params:{start:0, limit:50}});
        });
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id:             'Crm_dateFrom',
            allowBlank:     false,
            validateOnBlur: false,
            value:          oneWeekAgo
        });
        var dateTo = new Ext.form.DateField({
            id:             'Crm_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            value:          currentDate
        });
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarCrm',
            split: false,
            height: 26,
            items: [
                _action_delete,'->',
                'Display from: ',
                ' ',
                dateFrom,
                new Ext.Toolbar.Spacer(),
                'to: ',
                ' ',
                dateTo,
                new Ext.Toolbar.Spacer(),
                '-',
                'Search:', ' ',
/*                new Ext.ux.SelectBox({
                  listClass:'x-combo-list-small',
                  width:90,
                  value:'Starts with',
                  id:'search-type',
                  store: new Ext.data.SimpleStore({
                    fields: ['text'],
                    expandData: true,
                    data : ['Starts with', 'Ends with', 'Any match']
                  }),
                  displayField: 'text'
                }), */
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.setActiveToolbar(toolbar);

        dateFrom.on('valid', function(_dateField) {
            var from = Date.parseDate(
               Ext.getCmp('Crm_dateFrom').getRawValue(),
               'm/d/y'
            );

            var to = Date.parseDate(
               Ext.getCmp('Crm_dateTo').getRawValue(),
               'm/d/y'
            );
            
            if(from.getTime() > to.getTime()) {
            	Ext.getCmp('Crm_dateTo').setRawValue(Ext.getCmp('Crm_dateFrom').getRawValue());
            }

            Ext.getCmp('gridCrm').getStore().load({params:{start:0, limit:50}});
        });
        
        dateTo.on('valid', function(_dateField) {
            var from = Date.parseDate(
               Ext.getCmp('Crm_dateFrom').getRawValue(),
               'm/d/y'
            );

            var to = Date.parseDate(
               Ext.getCmp('Crm_dateTo').getRawValue(),
               'm/d/y'
            );
            
            if(from.getTime() > to.getTime()) {
                Ext.getCmp('Crm_dateFrom').setRawValue(Ext.getCmp('Crm_dateTo').getRawValue());
            }

            Ext.getCmp('gridCrm').getStore().load({params:{start:0, limit:50}})
        });
    }
    
    var _renderResult = function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        switch (_value) {
            case '-3' :
                return 'invalid password';
                break;

            case '-2' :
                return 'ambiguous username';
                break;

            case '-1' :
                return 'user not found';
                break;

            case '0' :
                return 'failure';
                break;

            case '1' :
                return 'success';
                break;
        }
    }

    /**
     * creates the address grid
     * 
     */
    var _showGrid = function() 
    {
    	_action_delete.setDisabled(true);
    	
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying projects {0} - {1} of {2}',
            emptyMsg: "No projects to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: true, header: 'Projekt ID', id: 'pj_id', dataIndex: 'pj_id', width: 20, hidden: true},
            {resizable: true, header: 'Projektname', id: 'pj_name', dataIndex: 'pj_name', width: 200},
            {resizable: true, header: 'Partner', id: 'pj_partner', dataIndex: 'pj_partner', width: 150},
            {resizable: true, header: 'Lead', id: 'pj_lead', dataIndex: 'pj_lead', width: 150},
            {resizable: true, header: 'Status', id: 'pj_state', dataIndex: 'pj_state', width: 150},
            {resizable: true, header: 'Wahrscheinlichkeit', id: 'pj_probability', dataIndex: 'pj_probability', width: 50},
            {resizable: true, header: 'Umsatz', id: 'pj_turnover', dataIndex: 'pj_turnover', width: 100}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_delete.setDisabled(true);
            } else {
                _action_delete.setDisabled(false);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'Projektname',
            border: false
        });
        
        Egw.Egwbase.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                _action_delete.setDisabled(false);
            }
            _contextMenuGridAdminAccessLog.showAt(_eventObject.getXY());
        });
        
/*        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
        });*/
    }
        
    // public functions and variables
    return {
        show: function(_node) {
            _showToolbar();
          //  _showEventTree();
            _showGrid();            
        },
        
        getPanel: _getCrmTree
    }
    
}(); // end of application



Egw.Crm.ProjectEditDialog = function() {

    var dialog;
    
    /**
     * the dialog to display the contact data
     */
    var projectedit;
    
    // private functions and variables
    
    var handler_applyChanges = function(_button, _event) 
    {
		
    	var eventForm = Ext.getCmp('eventDialog').getForm();
		eventForm.render();
    	
    	if(eventForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.event_id = formData.values.event_id;
			}
			    		
			eventForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Eventscheduler.reload();
    			},
    			failure:function(form, action) {
    				//Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }

    var handler_saveAndClose = function(_button, _event) 
    {
    	var eventForm = Ext.getCmp('eventDialog').getForm();
		eventForm.render();
    	
    	if(eventForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.event_id = formData.values.event_id;
			}
			    		
			eventForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Eventscheduler.reload();
    				window.setTimeout("window.close()", 400);
    			},
    			failure:function(form, action) {
    				//Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }

    var handler_delete = function(_button, _event) 
    {
		var eventIds = Ext.util.JSON.encode([formData.values.event_id]);
			
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Eventscheduler.deleteEvents', 
				_eventIds: eventIds
			},
			text: 'Deleting event...',
			success: function(_result, _request) {
  				window.opener.Egw.Eventscheduler.reload();
   				window.setTimeout("window.close()", 400);
			},
			failure: function ( result, request) { 
				Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.'); 
			} 
		});
        			    		
    }


   	var action_saveAndClose = new Ext.Action({
		text: 'save and close',
		handler: handler_saveAndClose,
		iconCls: 'action_saveAndClose'
	});

   	var action_applyChanges = new Ext.Action({
		text: 'apply changes',
		handler: handler_applyChanges,
		iconCls: 'action_applyChanges'
	});

   	var action_delete = new Ext.Action({
		text: 'delete event',
		handler: handler_delete,
		iconCls: 'action_delete'
	});

    /**
     * display the event edit dialog
     *
     */
    var _displayDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';

        var disableButtons = true;
        if(formData.values) {
            disableButtons = false;
        }       
        
        var projectToolbar = new Ext.Toolbar({
        	region: 'south',
          	id: 'applicationToolbar',
			split: false,
			height: 26,
			items: [
				action_saveAndClose,
				action_applyChanges,
				action_delete
			]
		});

		var _setParameter = function(_dataSource)
		{
                _dataSource.baseParams.method = 'Crm.getEvents';
                _dataSource.baseParams.options = Ext.encode({
                });
        }
  
  

  var editWindow = new Ext.Window({
                                    title: 'Technikeintrag editieren',
                                    modal: true,
                                    width: 375,
                                    height: 500,
                                    closeAction: 'hide',
                                    layout: 'fit',
                                    plain:true,
                                    bodyStyle:'padding:5px;',
                                    buttonAlign:'center'
                                });	
 
 
    var _editHandler = function(_button, _event) {
        
        editWindow.show();
    	/* 
    			var logIds = Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].id);
                } */
    }; 
 
        var addEventDate = new Ext.Window({
                            title: 'neues Datum hinzuf&uuml;gen',
                            modal: true,
                            width: 375,
                            height: 500,
                            closeAction: 'hide',
                            layout: 'fit',
                            plain:true,
                            bodyStyle:'padding:5px;',
                            buttonAlign:'center'
                        });	
 
 
        var _action_edit = new Ext.Action({
            text: 'editieren',
            //disabled: true,
            handler: _editHandler,
            iconCls: 'action_edit'
        });
 
       var _contextMenuGridTechnik = new Ext.menu.Menu({
            items: [
                _action_edit
            ]
        }); 
 
       var cm_technik = new Ext.grid.ColumnModel([
            	{header: "Technik", width: 200, sortable: true, dataIndex: '1'},
            	{header: "Lieferant", width: 200, sortable: true, dataIndex: '2'},
            	{header: "St&uuml;ckzahl", width: 50, sortable: true, dataIndex: '3'},
            	{id:"status", header: "Status", dataIndex: '4', width: 100, sortable: true, resizable: false  }
             ]);
        cm_technik.defaultSortable = true;
        
        var cm_werbung = new Ext.grid.ColumnModel([
            	{header: "Aktion", width: 200, sortable: true, dataIndex: '1'},
            	{header: "Datum", width: 150, sortable: true, dataIndex: '2'},
            	{header: "Verantwortlicher", width: 150, sortable: true, dataIndex: '3'},
            	{header: "Status", width: 50, sortable: true, resizable: false, dataIndex: '4'}
             ]);
        cm_werbung.defaultSortable = true;        
        
        var cm_organisation = new Ext.grid.ColumnModel([
            	{header: "ToDo", width: 200, sortable: true, dataIndex: '1'},
            	{header: "Datum", width: 150, sortable: true, dataIndex: '2'},
            	{header: "Verantwortlicher", width: 150, sortable: true, dataIndex: '3'},
            	{header: "Status", width: 50, sortable: true, resizable: false, dataIndex: '4'}
             ]);
        cm_organisation.defaultSortable = true;        
        
        var cm_preise = new Ext.grid.ColumnModel([
            	{header: "Preisgruppe", width: 200, sortable: true, dataIndex: '1'},
            	{header: "Preis", width: 200, sortable: true, dataIndex: '2'}
             ]);
        cm_preise.defaultSortable = true; 

        function formatDate(value){
            return value ? value.dateFormat('M d, Y') : '';
        };
    
         var cm_termine = new Ext.grid.ColumnModel([
            	{id:'datum', header: "Datum", dataIndex: '1', width: 100, editor: new Ext.form.DateField({allowBlank: false, format: 'd.m.Y', renderer: formatDate}), sortable: true },
            	{id:'bemerkungen', header: "Bemerkungen", dataIndex: '2', width: 300, editor: new Ext.form.TextField({allowBlank: false}), sortable: true},
                {id:'preisgruppe', header: "Preisgruppe", dataIndex: '3', width: 100, sortable: true},
                {id:'veranstaltungsort', header: "Veranstaltungsort", dataIndex: '4', width: 100, sortable: true}
             ]);
        cm_termine.defaultSortable = true;  
 
        
        
        
        var dummy_store = new Ext.data.Store({
                        //  reader: reader,
                        //   data: xg.dummyData
                });
                
        var st_technik =  new Ext.data.SimpleStore({
                                fields: ['1','2','3','4'],
                                data: [
                                        ['Lampen E8','Lampen-Schulze','10','geordert'],
                                        ['Leiter','Leiter-Meier','1','Versand best&auml;tigt']
                                    ]
        });      
        
        var st_termine =  new Ext.data.SimpleStore({
                                fields: ['1','2','3','4'],
                                data: [
                                        ['01.05.2008','...','Preisgruppe B','Variete'],
                                        ['03.08.2008','','Preisgruppe A','Hauptbuehne']
                                    ]
        });         
        
        var grid_technik = new Ext.grid.EditorGridPanel({
                store: st_technik,
                cm: cm_technik,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '95% 100%',
                frame:false,
               // title:'Framed with Checkbox Selection and Horizontal Scrolling',
                iconCls:'icon-grid'
            });
 
        grid_technik.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
                    _eventObject.stopEvent();
                    if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                        _grid.getSelectionModel().selectRow(_rowIndex);
                    }
                    _contextMenuGridTechnik.showAt(_eventObject.getXY());
        });
        
        grid_technik.on('rowdblclick', function(_grid, _rowIndex, _eventObject) {
                    _eventObject.stopEvent();
                   editWindow.show();
        });
 
        var grid_werbung = new Ext.grid.GridPanel({
                store: dummy_store,
                cm: cm_werbung,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 100%',
                frame:false,
               // title:'Framed with Checkbox Selection and Horizontal Scrolling',
                iconCls:'icon-grid'
            });
        
        var grid_organisation = new Ext.grid.GridPanel({
                store: dummy_store,
                cm: cm_organisation,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 100%',
                frame:false,
               // title:'Framed with Checkbox Selection and Horizontal Scrolling',
                iconCls:'icon-grid'
            });  
            
        var grid_preise = new Ext.grid.GridPanel({
                store: dummy_store,
                cm: cm_preise,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '50% 100%',
                frame:false,
               // title:'Framed with Checkbox Selection and Horizontal Scrolling',
                iconCls:'icon-grid'
            });  

            
        var grid_termine = new Ext.grid.EditorGridPanel({
                store: st_termine,
                cm: cm_termine,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 80%',
                frame:false,
                clicksToEdit:1,
                tbar: [{
                    text: 'neuer Termin',
                    handler : function(){
                    addEventDate.show();
                        /* var p = new Plant({
                            common: 'New Plant 1',
                            light: 'Mostly Shade',
                            price: 0,
                            availDate: new Date(),
                            indoor: false
                        });
                        grid.stopEditing();
                        ds.insert(0, p);
                        grid.startEditing(0, 0); */
                    }
                }],
               // title:'Framed with Checkbox Selection and Horizontal Scrolling',
                iconCls:'icon-grid'
            });  
            
  	var venueTrigger = new Ext.form.TriggerField({
            fieldLabel:'Spielst&auml;tte', 
			id: 'em_spielstaette',
            anchor:'100%',
            allowBlank: false,
            readOnly:true
        });

        venueTrigger.onTriggerClick = function() {
         //   Egw.Eventscheduler.displayVenueSelectDialog('contact_owner');
            Egw.Eventscheduler.displayVenueSelectDialog();
        }
  
  
		var projectedit = new Ext.FormPanel({
			url:'index.php',
			baseParams: {method :'Crm.saveEvent'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
            anchor:'100%',
			region: 'center',
            id: 'projectDialog',
			tbar: projectToolbar, 
			deferredRender: false,
            items: [{
                xtype:'tabpanel',
	            plain:true,
	            activeTab: 0,
				deferredRender:false,
                anchor:'100% 100%',
	            defaults:{bodyStyle:'padding:10px'},
	            items:[{
	                title:'&Uuml;bersicht',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                        


                    
                        xtype:'textfield',
                        fieldLabel:'Titel', 
                        name:'em__title',
                        anchor:'100%'
                       } , {                    
                        layout:'column',
        	            border:false,
        				deferredRender:false,
                        anchor:'100%',
                        items:[{
                            columnWidth:.4,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'textfield',
                                fieldLabel:'K&uuml;nstler', 
                                name:'em_kuenstler',
                                anchor:'95%'
                            } , {
                                xtype:'textfield',
                                fieldLabel:'Veranstaltungsnummer', 
                                name:'em_veranstaltungsnummer',
                                anchor:'95%'
                            } ]
                        } , {
                            columnWidth:.2,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'combo',
                                fieldLabel:'Typ', 
                                name:'em_typ',
                                anchor:'95%'
                           } , {
                                xtype:'textfield',
                                fieldLabel:'Dauer', 
                                name:'em_dauer',
                                anchor:'95%'
                           } ]
                       } , {
                            columnWidth:.2,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'combo',
                                fieldLabel:'Status', 
                                name:'em_status',
                                anchor:'95%'
                           } , {
                                xtype:'textfield',
                                fieldLabel:'FSK', 
                                name:'em_fsk',
                                anchor:'95%'
                           }]
                       } , {
                            columnWidth:.2,
                            layout: 'form',
                            border:false,
                            items: [venueTrigger]
                       }]
                    } , {
                        xtype:'tabpanel',
                        plain:true,
                        activeTab: 0,
                        deferredRender:false,
                        anchor:'100% 100%',
                        defaults:{bodyStyle:'padding:10px'},
                        items:[{
                            title:'Inhalt',
                            layout:'column',
                            deferredRender:false,
                            border:false,
                            items:[{
                                columnWidth:.5,
                                layout: 'form',
                                anchor:'100% 100%',
                                border:false,
                                items: [{
                                    xtype:'textarea',
                                    fieldLabel:'Beschreibung', 
                                    name:'em_beschreibung',
                                    anchor:'100% 100%'
                                }]  
                            }]
                          } , {
                            title:'Technik',
                            layout:'form',
                            deferredRender:false,
                            border:false,
                            items:[{ 
                                layout:'column',
                	            border:false,
                				deferredRender:false,
                                anchor:'100% 100%',
                                items:[{
                                    columnWidth:.65,
                                    layout: 'form',
                                    anchor:'100% 100%',
                                    border:false,
                                    items: [ grid_technik ]
                                    } , {
                                    columnWidth:.35,
                                    layout: 'form',
                                    anchor:'100% 100%',
                                    border:false,
                                    items: [{ xtype:'textarea',
                                            fieldLabel:'Besonderheiten', 
                                            name:'em_besonderheiten',
                                            anchor:'100% 100%' }]
                                    }]
                                }]
                          } , {
                            title:'Werbung',
                            layout:'form',
                            deferredRender:false,
                            border:false,
                            items:[ grid_werbung ]
                          } , {
                            title:'Organisation',
                            layout:'form',
                            deferredRender:false,
                            border:false,
                            items:[ grid_organisation ]
                          } , {
                            title:'Preise',
                            layout:'form',
                            deferredRender:false,
                            border:false,
                            items:[ grid_preise ]
                          }]
                        }]
                    } , {
                    title:'Aktivit&auml;ten',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[
                            grid_termine
                        ]
                    } , {
                    title:'Produkte',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[
                            grid_termine
                        ]
                    }]
                }]
            });
            
    

            
            
            
            
            /*
            
	            layout:'form',
	            border:false,
				deferredRender:false,
                anchor:'100%',
	            items:[{
                    xtype:'textfield',
                    fieldLabel:'Title', 
                    name:'cal_title',
                    anchor:'100%'
                }, {
                    xtype:'textfield',
                    fieldLabel:'Location', 
                    name:'cal_location',
                    anchor:'100%'
                }, {
                    layout:'column',
    	            border:false,
    				deferredRender:false,
                    anchor:'100%',
                    items:[{
                        columnWidth:.2,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'datefield',
                            id:'startdate',
                            fieldLabel:'startdate', 
                            format: 'd.m.Y',
                             validator: function() {
                                var end_date = Ext.getCmp('enddate');
                                if( end_date.isValid() == true) {
                                    alert(end_date.getRawValue());
                                    alert(end_date.getValue());
                                    
                                return true
                                } else return true;
                            }, 
                            name:'cal_start',
                            anchor:'95%'
                        }]  
                    } , {
                        columnWidth:.2,
                        layout: 'form',
                        border:false,
                        items: [{   
                            xtype: 'timefield',
                            id: 'starttime',
                            fieldLabel: 'starttime',
                            name: 'time_start',
                            format: 'H:i',
                            minValue: '00:00',
                            maxValue: '23:45',
                            value: now_h_m,
                            anchor:'95%'
                            }]
                    } , {
                        columnWidth:.2,
                        layout: 'form',
                        labelAlign: 'left',
                        border:false,
                        items: [
                                whole_day_box
                            ]
                    }]
                } , { 
                    layout:'column',
    	            border:false,
    				deferredRender:false,
                    anchor:'100%',
                    items:[{
                        columnWidth:.2,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'datefield',
                            id:'enddate', 
                            fieldLabel:'enddate', 
                            format: 'd.m.Y',
                            name:'cal_end',
                            anchor:'95%'
                        }]  
                    } , {
                        columnWidth:.2,
                        layout: 'form',
                        border:false,
                        items: [{   
                            xtype: 'timefield',
                            id: 'endtime',
                            fieldLabel: 'endtime',
                            name: 'time_end',
                            format: 'H:i',
                            minValue: '00:00',
                            maxValue: '23:45',
                            anchor:'95%'
                            }]
                    } , {
                        columnWidth:.2,
                        layout: 'form',
                        labelAlign: 'left',
                        border:false,
                        anchor: '95%',
                        items: [repeat_box]
                    }]
                } , { 
                    layout:'column',
    	            border:false,
    				deferredRender:false,
                    anchor:'100%',
                    items:[{
                        columnWidth:.5,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'calendar', 
                            name:'cal_end',
                            anchor:'95%'
                        }]  
                    } , {
                        columnWidth:.5,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'categorie', 
                            name:'cal_end',
                            anchor:'100%'
                        }]                      
                    }]
                } , {
                    xtype:'textarea',
                    fieldLabel:'description', 
                    name:'cal_location',
                    anchor:'100%'
                } , {
                    layout:'column',
    	            border:false,
    				deferredRender:false,
                    anchor:'100%',
                    items:[{
                        columnWidth:.5,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'participants', 
                            name:'cal_end',
                            anchor:'95%'
                        }]  
                    } , {
                        columnWidth:.5,
                        layout: 'form',
                        border:false,
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'protection', 
                            name:'cal_end',
                            anchor:'100%'
                        } , {
                            xtype:'textfield',
                            fieldLabel:'priority', 
                            name:'cal_end',
                            anchor:'100%'
                        } , {
                            xtype:'textfield',
                            fieldLabel:'state', 
                            name:'cal_end',
                            anchor:'100%'
                        } , {
                            xtype:'textfield',
                            fieldLabel:'alarm', 
                            name:'cal_end',
                            anchor:'100%'
                        }]                      
                    }]
                } , {
                    xtype:'textfield',
                    fieldLabel:'url', 
                    name:'cal_end',
                    anchor:'100%'
                }]
            }]
		});
	*/
    /*
    var startdate = Ext.getCmp('startdate');
    startdate.on('valid', function() {
                   repeat_box.enable();
            });
    startdate.on('invalid', function() {
                   repeat_box.disable();
            });            
    
    */
		var viewport = new Ext.Viewport({
			layout: 'border',
			items: projectedit
		});        

    }

    var setContactDialogValues = function(_formData) {
    	var form = Ext.getCmp('contactDialog').getForm();
    	
    	form.setValues(_formData);
    	
    	form.findField('contact_owner_name').setRawValue(formData.config.addressbookName);
    	
    	if(formData.config.oneCountryName) {
    		//console.log('set adr_one_countryname to ' + formData.config.oneCountryName);
	    	form.findField('adr_one_countryname').setRawValue(formData.config.oneCountryName);
    	}

    	if(formData.config.twoCountryName) {
    		//console.log('set adr_two_countryname to ' + formData.config.twoCountryName);
	    	form.findField('adr_two_countryname').setRawValue(formData.config.twoCountryName);
    	}
    }

    var _exportContact = function(_btn, _event) {
        Ext.MessageBox.alert('Export', 'Not yet implemented.');
    }
    
    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                setContactDialogValues(formData.values);
            }
        }
        
    }
    
}(); // end of application