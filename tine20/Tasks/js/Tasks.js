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
}


// Tasks main screen
Egw.Tasks.TaskGrid = function(){
    
    var sm, grid, store, tree, paging, filter;
	var ntStatus, ntPercent, ntSummaray, ntPriority, ntDue, ntOrganizer;
    var editing = false, focused = false, userTriggered = false;    
	
	// define handlers
	var handlers = {
		editInPopup: function(_button, _event){
			var taskId = '';
			if (_button.actionType == 'edit') {
			    var selectedRows = grid.getSelectionModel().getSelections();
                var task = selectedRows[0];
				taskId = task.data.identifier;
			}
            Egw.Egwbase.Common.openWindow('TasksEditWindow', 'index.php?method=Tasks.editTask&taskId='+taskId, 500, 500);
        },
		deleteTaks: function(_button, _event){
		    var selectedRows = grid.getSelectionModel().getSelections();
			if (selectedRows.length < 1) return;
			if (selectedRows.length > 1) {
				var identifiers = [];
				for (var i=0; i < selectedRows.length; i++) {
					identifiers.push(selectedRows[i].data.identifier);
				} 
				var params = {
                    method: 'Tasks.deleteTasks', 
                    identifiers: Ext.util.JSON.encode(identifiers),
                };
			} else {
				var params = {
                    method: 'Tasks.deleteTask', 
                    identifier: selectedRows[0].data.identifier,
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
	}
	// define actions
	var actions = {
        editInPopup: new Ext.Action({
            text: 'edit task',
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
            iconCls: 'action_delete'
        }),
		deleteMultiple: new Ext.Action({
            text: 'delete tasks',
            handler: handlers.deleteTaks,
            iconCls: 'action_delete'
        }),
    };
	
	// ------------- tree ----------
    
    
	tree =  new Egw.containerTreePanel({
        id: 'TasksTreePanel',
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        itemName: 'Tasks',
        appName: 'Tasks',
        border: false
    });
    
    tree.on('click', function(node){
        filter.nodeType = node.attributes.nodeType;
        filter.owner = node.attributes.owner;
        filter.container = node.attributes.container;
        
        store.load({
            params: paging
       });
    });
    
	// init of Tasks app
    tree.on('beforeexpand', function(panel) {
		initStore(); 
		initGrid();
        Egw.Egwbase.MainScreen.setActiveContentPanel(grid);
        Egw.Egwbase.MainScreen.setActiveToolbar(toolbar);
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
			//filter.organizer
			//filter.query
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
            nodeType: 'Personal',
            owner: Egw.Egwbase.Registry.get('currentAccount').accountId,
            query: '',
            due: false,
            container: false,
            organizer: false,
            tag: false,
        }
		
		paging = {
			start: 0,
			limit: 50,
			sort: 'due',
			dir: 'ASC'
		}
		
		store.load({
			params: paging
		});
		
	};

	
    // --------- toolbar -------------
    var quickSearchField = new Ext.app.SearchField({
        id:        'quickSearchField',
        width:     200,
        emptyText: 'enter searchfilter'
    }); 
    quickSearchField.on('change', function() {
        Ext.getCmp('gridCrm').getStore().load({params:{start:0, limit:50}});
    });
    
    
    var statusFilter = new Ext.app.ClearableComboBox({
        id: 'TasksStatusFilter',
        //name: 'statusFilter',
        hideLabel: true,            
        store: store,
        displayField: 'status',
        valueField: 'identifier',
        typeAhead: true,
        mode: 'local',
        triggerAction: 'all',
        emptyText: 'any',
        selectOnFocus: true,
        editable: false,
        width:150    
    });
    
    statusFilter.on('select', function(combo, record, index) {
       if (!record.data) {
           var _probability = '';       
       } else {
           var _probability = record.data.key;
       }
       
       combo.triggers[0].show();
    });
    
    var organizerFilter = new Ext.form.ComboBox({
        id: 'TasksorganizerFilter',
        emptyText: 'Cornelius Weiss'
    });

    var toolbar = new Ext.Toolbar({
        id: 'Tasks_Toolbar',
        split: false,
        height: 26,
        items: [
            actions.addInPopup,
            new Ext.Toolbar.Separator(),
            '->',
            'Status: ',
            ' ',
            statusFilter,
            'Organizer: ',
            ' ',
            organizerFilter,                
            new Ext.Toolbar.Separator(),
            '->',
            'Search:', ' ',
            ' ',
            quickSearchField
        ]
    });
	
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
		
		// custom template for the grid header
	    var headerTpl = new Ext.Template(
	        '<table border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
	        '<thead><tr class="x-grid3-hd-row">{cells}</tr></thead>',
	        '<tbody><tr class="new-task-row">',
	            '<td><div class="x-small-editor" id="new-task-status"></div></td>',
	            '<td><div class="x-small-editor" id="new-task-percent"></div></td>',
	            '<td><div class="x-small-editor" id="new-task-summaray"></div></td>',
	            '<td><div class="x-small-editor" id="new-task-priority"></div></td>',
	            '<td><div class="x-small-editor" id="new-task-due"></div></td>',
	            '<td><div class="x-small-editor" id="new-task-organizer"></div></td>',
	        '</tr></tbody>',
	        "</table>"
	    );
		
		grid = new Ext.grid.EditorGridPanel({
            id: 'TasksMainGrid',
            store: store,
			tbar: pagingToolbar,
			clicksToEdit: 'auto',
            enableColumnHide:false,
            enableColumnMove:false,
            region:'center',
			sm: new Ext.grid.RowSelectionModel(),
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
						autoExpand: true,
                        //allowBlank: false
                    })
				},
				{
					id: 'summaray',
					header: "Summaray",
					width: 400,
					sortable: true,
					dataIndex: 'summaray',
					editor: new Ext.form.TextField({
						allowBlank: false
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
                    })
				},
				{
					id: 'due',
					header: "Due Date",
					width: 50,
					sortable: true,
					dataIndex: 'due',
					renderer: Egw.Egwbase.Common.dateRenderer,
					editor: new Ext.form.DateField({
                        format : 'd.m.Y'
                    })
				}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ],
			autoExpandColumn: 'summaray',
			view: new Ext.grid.GridView({
                autoFill: true,
	            forceFit:true,
	            ignoreAdd: true,
	            emptyText: 'No Tasks to display',
	
	            templates: {
	                header: headerTpl
	            },
	/*
	            getRowClass : function(r){
	                var d = r.data;
	                if(d.status == 'DONE'){
	                    return 'task-completed';
	                }
	                if(d.due && d.due.getTime() < new Date().clearTime().getTime()){
	                    return 'task-overdue';
	                }
	                return '';
	            }
	*/
	        })
        });
		
		grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
			_eventObject.stopEvent();
			var numSelected = _grid.getSelectionModel().getCount();
			if (numSelected < 1) return;
			
			var items = numSelected > 1 ? [actions.deleteMultiple] : [
			    actions.editInPopup,
                actions.deleteSingle,
                '-',
                actions.addInPopup
			]
            
            //if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
            //    _grid.getSelectionModel().selectRow(_rowIndex);
            //}
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
        
		
		grid.on('render', function(){
			// The fields in the grid's header
			ntStatus = new Egw.Tasks.status.ComboBox({
                renderTo: 'new-task-status',
				autoExpand: true,
                disabled:true
          //      listClass:'x-combo-list-small',
            });
			ntPercent = new Egw.widgets.Percent.Combo({
				renderTo: 'new-task-percent',
				autoExpand: true,
				disabled: true
			});
			ntSummaray = new Ext.form.TextField({
	            renderTo: 'new-task-summaray',
	            emptyText: 'Add a task...'
	        });
			ntPriority = new Egw.widgets.Priority.Combo({
                renderTo: 'new-task-priority',
				autoExpand: true,
                disabled: true
            });
            ntDue = new Ext.form.DateField({
                renderTo: 'new-task-due',
                value: new Date(),
                disabled:true,
                format : "d.m.Y"
            });

			grid.on('resize', syncFields);
            grid.on('columnresize', syncFields);
            syncFields();
		
	
           var handlers = {
                focus: function(){
                    focused = true;
                },
                blur: function(){
                    focused = false;
                    doBlur.defer(250);
                },
                specialkey: function(f, e){
                    if(e.getKey()==e.ENTER){
                        userTriggered = true;
                        e.stopEvent();
                        f.el.blur();
                        if(f.triggerBlur){
                            f.triggerBlur();
                        }
                    }
                }
            }
            ntStatus.on(handlers);
            ntSummaray.on(handlers);
            ntPercent.on(handlers);
            ntPriority.on(handlers);
            ntDue.on(handlers);
            
            ntSummaray.on('focus', function(){
                focused = true;
                if(!editing){
                    ntStatus.enable();
                    ntPercent.enable();
                    ntPriority.enable();
                    ntDue.enable();
                    syncFields();
                    editing = true;
                }
            });
		}, this);
	
		function syncFields(){
            var cm = grid.getColumnModel();
            ntStatus.setSize(cm.getColumnWidth(0)-4);
            ntPercent.setSize(cm.getColumnWidth(1)-4);
            ntSummaray.setSize(cm.getColumnWidth(2)-2);
            ntPriority.setSize(cm.getColumnWidth(3)-4);
            ntDue.setSize(cm.getColumnWidth(4)-4);
        }
        
	    // when a field in the add bar is blurred, this determines
	    // whether a new task should be created
	    function doBlur(){
	        if(editing && !focused){
	            var summaray = ntSummaray.getValue();
	            if(!Ext.isEmpty(summaray)){
					task = new Egw.Tasks.Task({
						status: ntStatus.getValue(),
						percent: ntPercent.getValue(),
						summaray: summaray,
                        priority: ntPriority.getValue(),
						due: ntDue.getValue(),
						container: Egw.Tasks.DefaultContainer.container_id
					});
					
					Ext.Ajax.request({
                        params: {
                            method: 'Tasks.saveTask', 
                            task: Ext.util.JSON.encode(task.data),
							linkingApp: '',
                            linkedId: ''
                        },
                        success: function(_result, _request) {
                            ntSummaray.setValue('');
		                    Ext.StoreMgr.get('TaskGridStore').load({params: paging});
                        },
                        failure: function ( result, request) { 
                            Ext.MessageBox.alert('Failed', 'Could not save task.'); 
                        }
                    });
	            }
				ntStatus.disable();
                ntPercent.disable();
                ntPriority.disable();
                ntDue.disable();
	            editing = false;
	        }
	    }        
		//console.log(grid.getColumnModel().getColumnById('priority'));
    };
	
	return{
		getTreePanel: function(){return tree;},
		getToolbar: function() {return toolbar},        
		getGrid: function() {initStore(); initGrid(); return grid;},
		getStore: function() {return store;}
	}
}();


Egw.Tasks.EditDialog = function(task) {
	if (!arguments[0]) var task = {};
    
	// init task record    
    var task = new Egw.Tasks.Task(task);
		
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
	                //window.opener.Egw.Addressbook.reload();
	                if (closeWindow) {
						window.setTimeout("window.close()", 400);
					}
					dlg.action_delete.enable();
						// override task with returned data
						task = new Egw.Tasks.Task(Ext.util.JSON.decode(_result.responseText));
						// update form with this new data
						form.loadRecord(task);                    
					Ext.MessageBox.hide();
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
        Ext.MessageBox.wait('please wait', 'saving task');
    			Ext.Ajax.request({
                    params: {
    					method: 'Tasks.deleteTask',
    					identifier: task.data.identifier
    				},
                    success: function(_result, _request) {
    					window.setTimeout("window.close()", 400);
                        //store.load({params: paging});
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not delete task(s).');
    					Ext.MessageBox.hide();
                    }
    			});
    		}
		
	};
	
	var taskFormPanel = {
		layout: 'form',
		labelWidth: 75,
		title: 'Edit Task',
		bodyStyle: 'padding:15px',
		width: '100%',
		labelPad: 10,
		defaultType: 'textfield',
		defaults: {
			width: 230,
			msgTarget: 'side'
		},
		items: [
			{
				fieldLabel: 'summaray',
				xtype: 'textfield',
				name: 'summaray',
				allowBlank: false
			},  new Egw.widgets.Percent.Combo({
				fieldLabel: 'percentage',
				name: 'percent'
			}), new Egw.Tasks.status.ComboBox({
				fieldLabel: 'status',
				name: 'status',
			}), new Egw.widgets.Priority.Combo({
                fieldLabel: 'priority',
                name: 'priority'
            }), new Ext.form.DateField({
                fieldLabel: 'due date',
				name: 'due',
                format: "d.m.Y"
            }),	new Egw.widgets.container.selectionComboBox({
				fieldLabel: 'Container',
				name: 'container',
				itemName: 'Tasks',
                appName: 'Tasks',
				defaultContainer: Egw.Tasks.DefaultContainer
			}), {
				fieldLabel: 'notes',
				name: 'description',
				xtype: 'textarea',
				height: 100
			}
		]
	}
	
	var dlg = new Egw.widgets.dialog.EditRecord({
        id : 'TasksEditFormPanel',
        handler_applyChanges: handlers.applyChanges,
        handler_saveAndClose: handlers.saveAndClose,
        handler_pre_delete: handlers.pre_delete,
        items: taskFormPanel
    });
	
	var viewport = new Ext.Viewport({
        layout: 'border',
        items: dlg
    });
    
	// load form with initial data	
	dlg.getForm().loadRecord(task);
	if(task.get('identifier') > 0) dlg.action_delete.enable();
};


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

