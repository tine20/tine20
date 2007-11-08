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
			['Dentales','Henry Schein Dental Depot GmbH<br>Kenning, Sebastian','Grub, J�rg',''],
            
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
        
	  	var action_add = new Ext.Action({
			text: 'add',
			handler: function () {
           //     var tree = Ext.getCmp('venues-tree');
		//		var curSelNode = tree.getSelectionModel().getSelectedNode();
			//	var RootNode   = tree.getRootNode();
            
                openWindow('CrmProjectWindow', 'index.php?method=Crm.editProject&_projectId=0&_eventId=NULL', 900, 700);
             },
			iconCls: 'action_add'
		});        
        
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarCrm',
            split: false,
            height: 26,
            items: [
                action_add,
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

    
    var openWindow = function(_windowName, _url, _width, _height) 
    {
        if (document.all) {
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else if (window.innerWidth) {
            w = window.innerWidth;
            h = window.innerHeight;
            x = window.screenX;
            y = window.screenY;
        }
        var leftPos = ((w - _width)/2)+y; 
        var topPos = ((h - _height)/2)+x;

        var popup = window.open(
            _url, 
            _windowName,
            'width=' + _width + ',height=' + _height + ',top=' + topPos + ',left=' + leftPos +
            ',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no'
        );
        
        return popup;
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
          //  _getCrmTree();
          //  _showGrid();            
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
  
  
        var st_leadstatus =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','noch nicht kontaktiert'],
                        ['1','versucht zu kontaktieren'],
                        ['2','kontaktiert'],
                        ['3','Lead verloren'],
                        ['4','in Zukunft wieder anrufen'],
                        ['5','unbrauchbarer Lead'],
                        ['6','abgeschlossen gewonnen']
                    ]
        });
        
        var st_leadtyp =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Mitbewerber'],
                        ['1','Kunde'],
                        ['2','Partner'],
                        ['3','Presse'],
                        ['4','Prospekt'],
                        ['5','Wiederverkäufer'],
                        ['6','Lieferant'],
                        ['7','anderer Kundentyp']
                    ]
        });
        
        var st_leadsource =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Werbung'],
                        ['1','Tip von Wiederverkäufer'],
                        ['2','InLabBus'],
                        ['3','Messe'],
                        ['4','andere Leadquelle'],
                        ['5','Kaltaquise'],
                        ['6','Empfehlung'],
                        ['7','Arbeitskreise'],
                        ['8','Telefonkontakt'],
                        ['9','Kongress']
                    ]
        });
        
        var st_owner =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Meier, Heinz'],
                        ['1','Schultze, Hans'],
                        ['2','Vorfelder, Meier']
                    ]
        });
        
        var st_activities = new Ext.data.SimpleStore({
                fields: ['id','status','status2','datum','titel','message','responsible'],
                data: [
                        ['0','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['1','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['2','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['3','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['4','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['5','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['6','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['7','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['8','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['9','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner']
                    ]
        });
     
        var st_probability = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0%'],
                        ['1','10%'],
                        ['2','20%'],
                        ['3','30%'],
                        ['4','40%'],
                        ['5','50%'],
                        ['6','60%'],
                        ['7','70%'],
                        ['8','80%'],
                        ['9','90%'],
                        ['10','100%']
                    ]
        });
     
        var st_contacts = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0%'],
                        ['1','10%'],
                        ['2','20%'],
                        ['3','30%'],
                        ['4','40%'],
                        ['5','50%'],
                        ['6','60%'],
                        ['7','70%'],
                        ['8','80%'],
                        ['9','90%'],
                        ['10','100%']
                    ]
        });
     
        var tpl_contacts = new Ext.Template(
            '<div class="search-item">',
                '<h3>{n_fileas}</h3>',
            '</div>'
        );
     
        var cm_activities = new Ext.grid.ColumnModel([
            	{id:'status', header: "Status", dataIndex: 'status', width: 25, sortable: true },
                {id:'status2', header: "Status2", dataIndex: 'status2', width: 25, sortable: true },
                {id:'datum', header: "Datum", dataIndex: 'datum', width: 100, sortable: true },
                {id:'titel', header: "Titel", dataIndex: 'titel', width: 170, sortable: true },
                {id:'message', header: "Message", dataIndex: 'message', width: 300, sortable: true },
                {id:'responsible', header: "Verantwortlicher", dataIndex: 'responsible', width: 300, sortable: true }
        ]);
        
        var activities_limited = new Ext.grid.GridPanel({
                store: st_activities,
                id:'grid_activities_limited',
                cm: cm_activities,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 100%',
                enableColumnHide: false,
                enableColumnMove: false,
                enableHdMenu: false,
                stripeRows: true, 
                frame:false,
                iconCls:'icon-grid'
        });  
  
  /*
        var st_productsAvailable = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','CEREC AE'],
                        ['1','CEREC MC XL'],
                        ['2','CEREC 3 SE'],
                        ['3','CEREC PPU'],
                        ['4','Inlab MC XL'],
                        ['5','Inlab'],
                        ['6','InEOS'],
                        ['7','InFire'],
                        ['8','InCoris'],
                        ['9','InFinident'],
                        ['10','PC']
                    ]
        });
  
        var cm_choosenProducts = new Ext.grid.ColumnModel([{
                header: "Produkt",
                dataIndex: 'products',
                width: 220,
                editor: new Ext.form.ComboBox({
                    allowBlank: false, 
                    store: st_productsAvailable, 
                    displayField:'value', 
                    valueField:'key', 
                    mode: 'local', 
                    emptyText:'',
                    lazyRender:true,
                    listClass: 'x-combo-list-small'
                    })
                } , {
                header: "Anzahl",
                dataIndex: 'product_count',
                width: 30,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    maxValue: 10
                    })
                } , { 
                header: "Seriennummer",
                dataIndex: 'product_serials',
                width: 30,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                }
        ]);
        
        var product = Ext.data.Record.create([
           {name: 'products'},
           {name: 'product_count', type: 'float'},
           {name: 'product_serials', type: 'string'}
        ]);
        
        var grid_choosenProducts = new Ext.grid.EditorGridPanel({
         //   store: st_choosenProducts,
            cm: cm_choosenProducts,
            anchor: '100% 100%',
            autoExpandColumn:'common',
            frame:false,
            clicksToEdit:1,
            tbar: [{
                text: 'Produkt hinzufügen',
                handler : function(){
                    var p = new product({
                        products: '',
                        product_count: 0,
                        product_serials:''
                    });
                    grid_choosenProducts.stopEditing();
        //            st_choosenProducts.insert(0, p);
                    grid_choosenProducts.startEditing(0, 0);
                    }
                }]
            });

          //  st_choosenProducts.load();
        
   */   
        
  
        var cm_choosenContacts = new Ext.grid.ColumnModel([
            	{id:'anschrift', header: "Anschrift", dataIndex: 'anschrift', width: 250, sortable: true },
                {id:'kontakt', header: "Kontakt", dataIndex: 'kontakt', width: 250, sortable: true }
        ]);
  
        var st_choosenContacts = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0%'],
                        ['1','10%']
                    ]
        });
  
        var grid_contact = new Ext.grid.GridPanel({
                store: st_choosenContacts,
                id:'grid_choosenContacts',
                cm: cm_choosenContacts,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 87%',
                enableColumnHide: false,
                enableColumnMove: false,
                enableHdMenu: false,
                stripeRows: true, 
                frame:false,
                iconCls:'icon-grid'
        });  
  
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
	                title:'Übersicht',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                        layout:'column',
        	            border:false,
        				deferredRender:false,
                        anchor:'100%',
                        items:[{
                            columnWidth:.5,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'fieldset',
                                title:'Projekt',
                                height: 270,
                                anchor:'99%',
                                items: [{
                                    xtype:'textfield',
                                    fieldLabel:'Projektname', 
                                    name:'pj_name',
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
                                            xtype:'combo',
                                            fieldLabel:'Leadstatus', 
                                            name:'pj_leadstatus',
            								store: st_leadstatus,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'96%'
                                        } , {
                                            xtype:'combo',
                                            fieldLabel:'Leadtyp', 
                                            name:'pj_leadtyp',
            								store: st_leadtyp,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'96%'
                                        }]
                                    } , {                               
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'combo',
                                            fieldLabel:'Leadquelle', 
                                            name:'pj_leadsource',
            								store: st_leadsource,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'100%'
                                        } , {
                                            xtype:'combo',
                                            fieldLabel:'Verantwortlicher', 
                                            name:'pj_owner',
            								store: st_owner,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'100%'
                                        }]
                                    }]
                                } , {
                                    xtype:'textarea',
                                    fieldLabel:'Notizen', 
                                    hideLabel: true,
                                    name:'pj_notes',
                                    height: 90,
                                    anchor:'100%'
                                }]
                            } , {
                                xtype:'fieldset',
                                title:'Projektdaten',
                                height: 75,
                                anchor:'99%',
                                items: [{
                                    layout:'column',
                    	            border:false,
                    				deferredRender:false,
                                    anchor:'100%',
                                    items:[{
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'Start', 
                                            name:'startDate',
                                            id:'startDate',
                                            format: 'd.m.Y',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'Ende', 
                                            name:'endDate',
                                            id:'endDate',
                                            format: 'd.m.Y',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'voraussichtl. Ende', 
                                            name:'exprectedEndDate',
                                            id:'expectedEndDate',
                                            format: 'd.m.Y',
                                            anchor:'95%'
                                        }]
                                    }]
                                }]
                            }]
                        } , {
                            columnWidth:.5,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'fieldset',
                                title:'Kontakt',
                                height: 270,
                                anchor:'100%',
                                items: [{
                                    xtype:'combo',
                                    fieldLabel:'Kontakt auswählen', 
                                    hideLabel: true,
                                    name:'pj_contacts',
                                    store: st_contacts,
                                    displayField:'value',
                                    valueField:'key',
                                    typeAhead: false,
                                    loadingText: 'Searching...',
                                    mode: 'local',
                                    hideTrigger: true,
                                    emptyText:'',
                                    selectOnFocus:true,
                                    anchor:'100%',
                                    pageSize:10,
                                    tpl: tpl_contacts,		
                            		onSelect: function(record) {
                            			alert(record);
                                    }
                                } , grid_contact
                                ]
                            } , {
                                xtype:'fieldset',
                                title:'Umsatz',
                                height: 75,
                                anchor:'100%',
                                items: [{
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
                                            fieldLabel:'erwarteter Umsatz', 
                                            name:'pj_turnover',
                                            anchor:'97%'
                                        }]
                                    } , {
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'combo',
                                            fieldLabel:'Wahrscheinlichkeit', 
                                            name:'pj_probability',
            								store: st_probability,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'100%'
                                        }]
                                    }]
                                }]
                            }]
                        }]
                    } , {
                        xtype:'fieldset',
                        title:'Produktübersicht',
                        height: 60,
                        anchor:'100%',
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'Produkte', 
                            hideLabel: true,
                            name:'pj_productoverview',
                            id:'productoverview',
                            disabled: true,
                            value:'1 Inlab MC XL, 1 InEOS, 1 InFire, 1 InCoris',
                            anchor:'100%'
                        }]
                    } , {
                        xtype:'fieldset',
                        title:'letzte 10 Aktivitäten',
                        anchor:'100%',
                        height: 190,
                        items: [
                                activities_limited
                        ]
                    }]
                } , {
	                title:'Aktivitäten',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                    }]
                } , {
	                title:'Produkte',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                        xtype:'fieldset',
                        title:'gewählte Produkte',
                        items: [{
    //                        grid_choosenProducts
                        }]
                    
                    }]
                }]
            }]
        });

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