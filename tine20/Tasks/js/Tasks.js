/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Egw.Tasks');

// entry point, required by egwbase
Egw.Tasks.getPanel = function() {
    return Egw.Tasks.TaskGrid.getTreePanel();
};


// Tasks main screen
Egw.Tasks.TaskGrid = function(){
    
    var sm, grid, store, tree, paging, filter;
	
    
    // called after popups native onLoad
	var setupPopupEvents = function(popup){
		popup.Ext.onReady(function() {
			popup.Egw.Tasks.EditPopupEventProxy.on('update', function(task) {
				store.load({params: paging});
			}, this);
		}, this);
	}
	
    
	// define handlers
	var handlers = {
		editInPopup: function(_button, _event){
			var taskId = '';
			if (_button.actionType == 'edit') {
			    var selectedRows = grid.getSelectionModel().getSelections();
                var task = selectedRows[0];
				taskId = task.data.identifier;
			}
            var popup = Egw.Egwbase.Common.openWindow('TasksEditWindow', 'index.php?method=Tasks.editTask&taskId='+taskId+'&linkingApp=&linkedId=', 700, 300);
            
            if (popup.addEventListener) {
            	popup.addEventListener('load', function() {setupPopupEvents(popup);}, true);
            } else if (popup.attachEvent) {
            	popup.attachEvent('onload', function() {setupPopupEvents(popup);});
            } else {
            	popup.onload = function() {setupPopupEvents(popup);};
            }
        },
		deleteTaks: function(_button, _event){
			Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected task(s)', function(_button) {
                if(_button == 'yes') {
				    var selectedRows = grid.getSelectionModel().getSelections();
				    if (selectedRows.length < 1) {
				        return;
				    }
					if (selectedRows.length > 1) {
						var identifiers = [];
						for (var i=0; i < selectedRows.length; i++) {
							identifiers.push(selectedRows[i].data.identifier);
						} 
						var params = {
		                    method: 'Tasks.deleteTasks', 
		                    identifiers: Ext.util.JSON.encode(identifiers)
		                };
					} else {
						var params = {
		                    method: 'Tasks.deleteTask', 
		                    identifier: selectedRows[0].data.identifier
		                };
					}
				    
					Ext.Ajax.request({
		                params: params,
		                success: function(_result, _request) {
		                    store.load({params: paging});
		                },
		                failure: function ( result, request) { 
		                    Ext.MessageBox.alert('Failed', 'Could not delete task(s).'); 
		                }
		            });
				}
			});
		}
	};
	// define actions
	var actions = {
        editInPopup: new Ext.Action({
            text: 'edit task',
			disabled: true,
			actionType: 'edit',
            handler: handlers.editInPopup,
            iconCls: 'action_edit'
        }),
        addInPopup: new Ext.Action({
			actionType: 'add',
            text: 'add task',
            handler: handlers.editInPopup,
            iconCls: 'action_add'
        }),
        deleteSingle: new Ext.Action({
            text: 'delete task',
            handler: handlers.deleteTaks,
			disabled: true,
            iconCls: 'action_delete'
        }),
		deleteMultiple: new Ext.Action({
            text: 'delete tasks',
            handler: handlers.deleteTaks,
			disabled: true,
            iconCls: 'action_delete'
        })
    };
	
	// ------------- tree ----------
    
    
	tree =  new Egw.widgets.container.TreePanel({
        id: 'TasksTreePanel',
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        itemName: 'to do lists',
		folderName: 'to do list',
        appName: 'Tasks',
        border: false
    });
    
    tree.on('click', function(node){
        filter.containerType = node.attributes.containerType;
        filter.owner = node.attributes.owner ? node.attributes.owner.accountId : null;
        filter.container = node.attributes.container ? node.attributes.container.container_id : null;
        
        store.load({
            params: paging
       });
    });
    
	// init of Tasks app
    tree.on('beforeexpand', function(panel) {
		initStore(); 
		initGrid();
		Egw.Egwbase.MainScreen.setActiveToolbar(_getToolbar());
        Egw.Egwbase.MainScreen.setActiveContentPanel(grid);
    });
	
	// ----------- store --------------    
	var initStore = function(){
	    store = new Ext.data.JsonStore({
			idProperty: 'identifier',
            root: 'results',
            totalProperty: 'totalcount',
			successProperty: 'status',
			fields: Egw.Tasks.Task,
			remoteSort: true,
			baseParams: {
                method: 'Tasks.searchTasks'
            }
        });
		
		// register store
		Ext.StoreMgr.add('TaskGridStore', store);
		
		// prepare filter
		store.on('beforeload', function(store, options){
			// console.log(options);
			// for some reasons, paging toolbar eats sort and dir
			if (store.getSortState()) {
				filter.sort = store.getSortState().field;
				filter.dir = store.getSortState().direction;
			} else {
				filter.sort = paging.sort;
                filter.dir = paging.dir;
			}
			filter.start = options.params.start;
            filter.limit = options.params.limit;
			
			//filter.due
			filter.showClosed = Ext.getCmp('TasksShowClosed') ? Ext.getCmp('TasksShowClosed').pressed : false;
			filter.organizer = Ext.getCmp('TasksorganizerFilter') ? Ext.getCmp('TasksorganizerFilter').getValue() : '';
			filter.query = Ext.getCmp('quickSearchField') ? Ext.getCmp('quickSearchField').getValue() : '';
			filter.status = Ext.getCmp('TasksStatusFilter') ? Ext.getCmp('TasksStatusFilter').getValue() : '';
			//filter.tag
			options.params.filter = Ext.util.JSON.encode(filter);
		});
		
		store.on('update', function(store, task, operation) {
			switch (operation) {
				case Ext.data.Record.EDIT:
					Ext.Ajax.request({
	                params: {
	                    method: 'Tasks.saveTask', 
	                    task: Ext.util.JSON.encode(task.data),
						linkingApp: '',
						linkedId: ''					
	                },
	                success: function(_result, _request) {
						store.commitChanges();

						// we need to reload store, cause filters might be 
						// affected by the change!
						store.load({params: paging});
	                },
	                failure: function ( result, request) { 
	                    Ext.MessageBox.alert('Failed', 'Could not save task.'); 
	                }
				});
				break;
				case Ext.data.Record.COMMIT:
				    //nothing to do, as we need to reload the store anyway.
				break;
			}
			
			
			//store.commitChanges();
			//if (operation == Ext.data.Record.COMMIT) {
			//	console.log(task);
			//}
			
		});
		
		filter = {
            containerType: 'personal',
            owner: Egw.Egwbase.Registry.get('currentAccount').accountId,
            query: '',
            due: false,
            container: false,
            organizer: false,
            tag: false
        };
		
		paging = {
			start: 0,
			limit: 50,
			sort: 'due',
			dir: 'ASC'
		};
		
		store.load({
			params: paging
		});
		
	};

	
    // --------- toolbar -------------
	// toolbar must be generated each time this fn is called, 
	// as egwbase destroys the old toolbar when setting a new one.
	var _getToolbar = function(){
		var quickSearchField = new Ext.app.SearchField({
			id: 'quickSearchField',
			width: 200,
			emptyText: 'enter searchfilter'
		});
		quickSearchField.on('change', function(){
			if(filter.query != this.getValue()){
				store.load({params: paging});
			}
		});
		
		var showClosedToggle = new Ext.Button({
			id: 'TasksShowClosed',
			enableToggle: true,
			handler: function(){
				store.load({params: paging});
			},
			scope: this,
			text: 'show closed',
			iconCls: 'action_showArchived'
		});
		
		var statusFilter = new Ext.ux.ClearableComboBox({
			id: 'TasksStatusFilter',
			//name: 'statusFilter',
			hideLabel: true,
			store: Egw.Tasks.status.getStore(),
			displayField: 'status_name',
			valueField: 'identifier',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText: 'any',
			selectOnFocus: true,
			editable: false,
			width: 150
		});
		
		statusFilter.on('select', function(combo, record, index){
			store.load({params: paging});
			combo.triggers[0].show();
		});
		
		var organizerFilter = new Egw.widgets.AccountpickerField({
			id: 'TasksorganizerFilter',
			width: 200,
		    emptyText: 'any'
		});
		
		organizerFilter.on('select', function(combo, record, index){
            store.load({params: paging});
            //combo.triggers[0].show();
        });
		
		var toolbar = new Ext.Toolbar({
			id: 'Tasks_Toolbar',
			split: false,
			height: 26,
			items: [
			    actions.addInPopup,
				actions.editInPopup,
				actions.deleteSingle,
				new Ext.Toolbar.Separator(),
				'->',
				showClosedToggle,
				//'Status: ',	' ', statusFilter,
				//'Organizer: ', ' ',	organizerFilter,
				new Ext.Toolbar.Separator(),
				'->',
				'Search:', ' ', ' ', quickSearchField]
		});
	   
	    return toolbar;
	};
	
	// --------- grid ----------    
    var initGrid = function(){
        //sm = new Ext.grid.CheckboxSelectionModel();
        var pagingToolbar = new Ext.PagingToolbar({
	        pageSize: 50,
	        store: store,
	        displayInfo: true,
	        displayMsg: 'Displaying tasks {0} - {1} of {2}',
	        emptyMsg: "No tasks to display"
	    });
		
		grid = new Ext.ux.grid.QuickaddGridPanel({
            id: 'TasksMainGrid',
			border: false,
            store: store,
			tbar: pagingToolbar,
			clicksToEdit: 'auto',
            enableColumnHide:false,
            enableColumnMove:false,
            region:'center',
			sm: new Ext.grid.RowSelectionModel(),
			loadMask: true,
            columns: [
				{
					id: 'status',
					header: "Status",
					width: 40,
					sortable: true,
					dataIndex: 'status',
					renderer: Egw.Tasks.status.getStatusIcon,
                    editor: new Egw.Tasks.status.ComboBox({
		                autoExpand: true,
		                listClass: 'x-combo-list-small'
		            }),
		            quickaddField: new Egw.Tasks.status.ComboBox({
                        autoExpand: true,
                    })
				},
				{
					id: 'percent',
					header: "Percent",
					width: 50,
					sortable: true,
					dataIndex: 'percent',
					renderer: Egw.widgets.Percent.renderer,
                    editor: new Egw.widgets.Percent.Combo({
						autoExpand: true
                        //allowBlank: false
                    }),
                    quickaddField: new Egw.widgets.Percent.Combo({
                        autoExpand: true,
                    })
				},
				{
					id: 'summaray',
					header: "Summaray",
					width: 400,
					sortable: true,
					dataIndex: 'summaray',
					//editor: new Ext.form.TextField({
					//	allowBlank: false
					//}),
					quickaddField: new Ext.form.TextField({
                        emptyText: 'Add a task...'
                    })
				},
				{
					id: 'priority',
					header: "Priority",
					width: 30,
					sortable: true,
					dataIndex: 'priority',
					renderer: Egw.widgets.Priority.renderer,
                    editor: new Egw.widgets.Priority.Combo({
                        allowBlank: false,
						autoExpand: true
                    }),
                    quickaddField: new Egw.widgets.Priority.Combo({
                        autoExpand: true,
                    })
				},
				{
					id: 'due',
					header: "Due Date",
					width: 50,
					sortable: true,
					dataIndex: 'due',
					renderer: Egw.Egwbase.Common.dateRenderer,
					editor: new Ext.ux.ClearableDateField({
                        format : 'd.m.Y'
                    }),
                    quickaddField: new Ext.ux.ClearableDateField({
                        //value: new Date(),
                        format : "d.m.Y"
                    })
				}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ],
		    quickaddMandatory: 'summaray',
			autoExpandColumn: 'summaray',
			view: new Ext.grid.GridView({
                autoFill: true,
	            forceFit:true,
	            ignoreAdd: true,
	            emptyText: 'No Tasks to display',
	        })
        });
		
		grid.on('rowdblclick', function(grid, row, event){
			handlers.editInPopup({actionType: 'edit'});
		}, this);
		
		grid.getSelectionModel().on('selectionchange', function(sm){
			var disabled = sm.getCount() != 1;
			actions.editInPopup.setDisabled(disabled);
			actions.deleteSingle.setDisabled(disabled);
			actions.deleteMultiple.setDisabled(!disabled);
		}, this);
		
		grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
			_eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }

			var numSelected = _grid.getSelectionModel().getCount();
			//if (numSelected < 1) {
			//	return;
			//}
			
			var items = numSelected > 1 ? [actions.deleteMultiple] : [
			    actions.editInPopup,
                actions.deleteSingle,
                '-',
                actions.addInPopup
			];
            
			var ctxMenu = new Ext.menu.Menu({
		        //id:'ctxMenuAddress1', 
		        items: items
		    });
            ctxMenu.showAt(_eventObject.getXY());
        });
		
		grid.on('keydown', function(e){
	         if(e.getKey() == e.DELETE && !grid.editing){
	             handlers.deleteTaks();
	         }
	    });
        		
	    grid.on('newentry', function(taskData){
	    	var selectedNode = tree.getSelectionModel().getSelectedNode();
            taskData.container = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.container_id : Egw.Tasks.DefaultContainer.container_id;
	        task = new Egw.Tasks.Task(taskData);

	        Ext.Ajax.request({
                params: {
                    method: 'Tasks.saveTask', 
                    task: Ext.util.JSON.encode(task.data),
                    linkingApp: '',
                    linkedId: ''
                },
                success: function(_result, _request) {
                    Ext.StoreMgr.get('TaskGridStore').load({params: paging});
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Could not save task.'); 
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
		});
    };
	
	return{
		isRunning: function(){return grid ? true : false;},
		getPaging: function(){return paging;},
		getTreePanel: function(){return tree;},
		getToolbar: _getToolbar,        
		getGrid: function() {initStore(); initGrid(); return grid;},
		getStore: function() {return store;}
	};    
}();


Egw.Tasks.EditDialog = function(task) {
	// initialize event proxy
	Egw.Tasks.EditPopupEventProxy = new Ext.ux.PopupEventProxy();
	
	if (!arguments[0]) {
		task = {};
	}
	// check if task app is running
	var isTasks = window.opener.Egw.Tasks && window.opener.Egw.Tasks.TaskGrid.isRunning();
	var MainScreen = isTasks ? window.opener.Egw.Tasks : null;
	
	// init task record 
    task = new Egw.Tasks.Task(task);
    Egw.Tasks.fixTask(task);
    
	var DefaultContainer = Egw.Tasks.DefaultContainer;
	if (isTasks) {
		var selectedNode = MainScreen.TaskGrid.getTreePanel().getSelectionModel().getSelectedNode();
		if (selectedNode) {
			DefaultContainer = selectedNode.attributes.container;
		}
	}
	
	var handlers = {        
        applyChanges: function(_button, _event) {
			var closeWindow = arguments[2] ? arguments[2] : false;
			
			var dlg = Ext.getCmp('TasksEditFormPanel');
			var form = dlg.getForm();
			form.render();
	
			if(form.isValid()) {
				Ext.MessageBox.wait('please wait', 'saving task');
				
					// merge changes from form into task record
				form.updateRecord(task);
				
	            Ext.Ajax.request({
					params: {
		                method: 'Tasks.saveTask', 
		                task: Ext.util.JSON.encode(task.data),
						linkingApp: formData.linking.link_app1,
						linkedId: formData.linking.link_id1 //,
						//jsonKey: Egw.Egwbase.Registry.get('jsonKey')
		            },
		            success: function(_result, _request) {
		                
						dlg.action_delete.enable();
						// override task with returned data
						task = new Egw.Tasks.Task(Ext.util.JSON.decode(_result.responseText));
						Egw.Tasks.fixTask(task);
						
						// update form with this new data
						form.loadRecord(task);                    
						Egw.Tasks.EditPopupEventProxy.fireEvent('update', task);

						if (closeWindow) {
							Egw.Tasks.EditPopupEventProxy.purgeListeners();
                            window.setTimeout("window.close()", 1000);
                        } else {
                        	Ext.MessageBox.hide();
                        }
		            },
		            failure: function ( result, request) { 
		                Ext.MessageBox.alert('Failed', 'Could not save task.'); 
		            } 
				});
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
		},
		saveAndClose:  function(_button, _event) {
			handlers.applyChanges(_button, _event, true);
		},
		pre_delete: function(_button, _event) {
			Ext.MessageBox.confirm('Confirm', 'Do you really want to delete this task?', function(_button) {
                if(_button == 'yes') {
			        Ext.MessageBox.wait('please wait', 'saving task');
	    			Ext.Ajax.request({
	                    params: {
	    					method: 'Tasks.deleteTask',
	    					identifier: task.data.identifier
	    				},
	                    success: function(_result, _request) {
	    					Egw.Tasks.EditPopupEventProxy.fireEvent('update', null);
	    					Egw.Tasks.EditPopupEventProxy.purgeListeners();
	    					window.setTimeout("window.close()", 1000);
	                    },
	                    failure: function ( result, request) { 
	                        Ext.MessageBox.alert('Failed', 'Could not delete task(s).');
	    					Ext.MessageBox.hide();
	                    }
	    			});
				}
			});
		}
	};
	
	var taskFormPanel = {
		layout:'column',
		labelWidth: 90,
		border: false,

		items: [{
            columnWidth: 0.65,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%',
                xtype: 'textfield'
            },
			items:[{
				fieldLabel: 'summaray',
				hideLabel: true,
				xtype: 'textfield',
				name: 'summaray',
				emptyText: 'enter short name...',
				allowBlank: false
			}, {
				fieldLabel: 'notes',
				hideLabel: true,
                emptyText: 'enter description...',
				name: 'description',
				xtype: 'textarea',
				height: 150
			}]
		}, {
            columnWidth: 0.35,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%'
            },
            items:[ 
                new Egw.widgets.Percent.Combo({
                    fieldLabel: 'Percentage',
                    editable: false,
                    name: 'percent'
                }), 
                new Egw.Tasks.status.ComboBox({
                    fieldLabel: 'Status',
                    name: 'status'
                }), 
                new Egw.widgets.Priority.Combo({
                    fieldLabel: 'Priority',
                    name: 'priority'
                }), 
                new Ext.ux.ClearableDateField({
                    fieldLabel: 'Due date',
                    name: 'due',
                    format: "d.m.Y"
                }), 
                new Egw.widgets.container.selectionComboBox({
                    fieldLabel: 'Folder',
                    name: 'container',
                    itemName: 'Tasks',
                    appName: 'Tasks',
                    defaultContainer: DefaultContainer
                })
            ]
        }]
	};
	
	var dlg = new Egw.widgets.dialog.EditRecord({
        id : 'TasksEditFormPanel',
        handlerApplyChanges: handlers.applyChanges,
        handlerSaveAndClose: handlers.saveAndClose,
        handlerDelete: handlers.pre_delete,
        labelAlign: 'side',
        layout: 'fit',
        items: taskFormPanel
    });
	
	var viewport = new Ext.Viewport({
        layout: 'border',
        items: dlg
    });
    
    // load form with initial data
    dlg.getForm().loadRecord(task);
    
    if(task.get('identifier') > 0) {
        dlg.action_delete.enable();
    }
};


Ext.ux.PopupEventProxy = function() {
    this.addEvents({
        "update" : true,
        "close" : true
    });
}
Ext.extend(Ext.ux.PopupEventProxy, Ext.util.Observable);

// fixes a task
Egw.Tasks.fixTask = function(task) {
	if (task.data.container) {
        task.data.container = Ext.util.JSON.decode(task.data.container);
    }
    if (task.data.due) {
        task.data.due = Date.parseDate(task.data.due, 'c');
    }
}

// Task model
Egw.Tasks.Task = Ext.data.Record.create([
    // egw record fields
    { name: 'container' },
    { name: 'created_by' },
    { name: 'creation_time', type: 'date', dateFormat: 'c' },
    { name: 'last_modified_by' },
    { name: 'last_modified_time', type: 'date', dateFormat: 'c' },
    { name: 'is_deleted' },
    { name: 'deleted_time', type: 'date', dateFormat: 'c' },
    { name: 'deleted_by' },
    // task only fields
    { name: 'identifier' },
    { name: 'percent' },
    { name: 'completed', type: 'date', dateFormat: 'c' },
    { name: 'due', type: 'date', dateFormat: 'c' },
    // ical common fields
    { name: 'class' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'priority' },
    { name: 'status' },
    { name: 'summaray' },
    { name: 'url' },
    // ical common fields with multiple appearance
    { name: 'attach' },
    { name: 'attendee' },
    { name: 'categories' },
    { name: 'comment' },
    { name: 'contact' },
    { name: 'related' },
    { name: 'resources' },
    { name: 'rstatus' },
    // scheduleable interface fields
    { name: 'dtstart', type: 'date', dateFormat: 'c' },
    { name: 'duration', type: 'date', dateFormat: 'c' },
    { name: 'recurid' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    { name: 'exrule' },
    { name: 'rdate' },
    { name: 'rrule' }
]);
	
