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
        Egw.Egwbase.MainScreen.setActiveToolbar(Egw.Tasks.getToolbar());
    });
	
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
			dir: 'DESC'
		}
		
		store.load({
			params: paging
		});
		
	};

	
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
					renderer: Egw.widgets.Percent.ComboBox.progressBar,
                    editor: new Egw.widgets.Percent.ComboBox({
						autoExpand: true,
                        //allowBlank: false
                    })
				},
				{
					id: 'summaray',
					header: "Summaray",
					width: 200,
					sortable: true,
					dataIndex: 'summaray',
					editor: new Ext.form.TextField({
						allowBlank: false
					})
				},
				{
					id: 'priority',
					header: "Priority",
					width: 20,
					sortable: true,
					dataIndex: 'priority',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
				},
				{
					id: 'due',
					header: "Due Date",
					width: 100,
					sortable: true,
					dataIndex: 'due',
					renderer: Egw.Egwbase.Common.dateRenderer,
					editor: new Ext.form.DateField({
                        format : 'd.m.Y'
                    })
				},
				{
					id: 'organizer',
					header: "Organizer",
					width: 150,
					sortable: true,
					dataIndex: 'organizer',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
				}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ],
			autoExpandColumn: 'summary',
			view: new Ext.grid.GridView({
	            forceFit:true,
	            ignoreAdd: true,
	            emptyText: 'No Tasks to display',
	
	            templates: {
	                header: headerTpl
	            },
	
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
	        })
        });
		
		
		grid.on('render', function(){
			// The fields in the grid's header
			ntStatus = new Egw.Tasks.status.ComboBox({
                renderTo: 'new-task-status',
				autoExpand: true,
                disabled:true,
                listClass:'x-combo-list-small',
            });
			ntPercent = new Ext.form.ComboBox({
				renderTo: 'new-task-percent',
				disabled: true
			});
			ntSummaray = new Ext.form.TextField({
	            renderTo: 'new-task-summaray',
	            emptyText: 'Add a task...'
	        });
			ntPriority = new Ext.form.ComboBox({
                renderTo: 'new-task-priority',
                disabled: true
            });
            ntDue = new Ext.form.DateField({
                renderTo: 'new-task-due',
                value: new Date(),
                disabled:true,
                format : "d.m.Y"
            });
			ntOrganizer = new Ext.form.ComboBox({
                renderTo: 'new-task-organizer',
                disabled: true
            });
			//grid.on('resize', syncFields);
            //grid.on('columnresize', syncFields);
            syncFields();
		});
		
		function syncFields(){
            var cm = grid.getColumnModel();
            ntStatus.setSize(cm.getColumnWidth(0)-4);
            ntPercent.setSize(cm.getColumnWidth(1)-4);
            ntSummaray.setSize(cm.getColumnWidth(2)-2);
            ntPriority.setSize(cm.getColumnWidth(3)-4);
            ntDue.setSize(cm.getColumnWidth(4)-4);
            ntOrganizer.setSize(cm.getColumnWidth(5)-4);
        }
		//console.log(grid.getColumnModel().getColumnById('priority'));
    };
	
	return{
		getTreePanel: function(){return tree;},
		getGrid: function() {initStore(); initGrid(); return grid;},
		getStore: function() {return store;}
	}
}();

Egw.Tasks.getToolbar = function() {

        var action_add = new Ext.Action({
	        text: 'add',
	        iconCls: 'action_add',
	        handler: function () {
				data = {};
		        //  var tree = Ext.getCmp('venues-tree');
		        //  var curSelNode = tree.getSelectionModel().getSelectedNode();
		        //  var RootNode   = tree.getRootNode();
	            editWindow = Egw.Egwbase.Common.openWindow('TasksEditWindow', 'index.php?method=Tasks.editTask&taskId=', 500, 500);
	        }
        }); 
    
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
            store: Egw.Tasks.status.getStore(),
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
                action_add,
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
        
        return toolbar;
    }
  
Egw.Tasks.EditDialog = function(task) {
	if (!arguments[0]) var task = {};
    var task = new Egw.Tasks.Task(task);
		
	var handler_applyChanges = function(_button, _event) {
		var closeWindow = arguments[2] ? arguments[2] : false;
		
		var dlg = Ext.getCmp('TasksEditFormPanel');
		var form = dlg.getForm();
		form.render();

		if(form.isValid()) {
			Ext.MessageBox.wait('please wait', 'saving task');
			
			var task = new Egw.Tasks.Task({});
			form.updateRecord(task);
			//console.log(task);
			
            Ext.Ajax.request({
				params: {
	                method: 'Tasks.saveTask', 
	                task: Ext.util.JSON.encode(task.data),
					//jsonKey: Egw.Egwbase.Registry.get('jsonKey')
	            },
	            success: function(_result, _request) {
	                //window.opener.Egw.Addressbook.reload();
	                if (closeWindow) {
						window.setTimeout("window.close()", 400);
					}
					dlg.action_delete.enable();
					Ext.MessageBox.hide();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Could not save task.'); 
	            } 
			});
        } else {
            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
        }
	};
	var handler_saveAndClose = function(_button, _event) {
		handler_applyChanges(_button, _event, true);
	};
	var handler_pre_delete = function(_button, _event) {
		
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
			},  new Egw.widgets.Percent.ComboBox({
				fieldLabel: 'percentage',
				name: 'percent'
			}), new Egw.Tasks.status.ComboBox({
				fieldLabel: 'status',
				name: 'status',
			}), {
                fieldLabel: 'priority',
                xtype: 'combo',
                name: 'priority'
            }, new Ext.form.DateField({
                fieldLabel: 'due date',
				name: 'due',
                format: "d.m.Y"
            }),	new Egw.widgets.container.selectionComboBox({
				fieldLabel: 'Container',
				name: 'container',
				itemName: 'Tasks',
                appName: 'Tasks',
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
        handler_applyChanges: handler_applyChanges,
        handler_saveAndClose: handler_saveAndClose,
        handler_pre_delete: handler_pre_delete,
        items: taskFormPanel
    });
	
	var viewport = new Ext.Viewport({
        layout: 'border',
        items: dlg
    });
	
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

