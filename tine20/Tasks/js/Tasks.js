/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tasks');

/*********************************** MAIN DIALOG ********************************************/

/**
 * entry point, required by tinebase
 * This function is called once when Tinebase collect the available apps
 */
Tine.Tasks.getPanel =  function() {
    Tine.Tasks.mainGrid.initComponent();
	var tree = Tine.Tasks.mainGrid.getTree();
    
    // this function is called each time the user activates the Tasks app
    tree.on('beforeexpand', function(panel) {
        Tine.Tinebase.MainScreen.setActiveToolbar(this.toolbar, true);
        this.updateMainToolbar();
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.grid, true);
        this.store.load({
            params: this.paging
        });
    }, Tine.Tasks.mainGrid);
    
    return tree;
};


// Tasks main screen
Tine.Tasks.mainGrid = {
    /**
     * holds translation
     */
    translation: null,
	/**
     * holds instance of application tree
     */
    tree: null,
    /**
     * {Ext.Toolbar}
     */
    toolbar: null,
    /**
     * holds grid
     */
    grid: null,
    /**
     * holds selection model of grid
     */
    sm: null,
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
        sort: 'due',
        dir: 'ASC'
    },
    /**
     * holds current filters
     */
    filter: {
        containerType: 'personal',
        query: '',
        due: false,
        container: false,
        organizer: false,
        tag: false
    },

    handlers: {
		editInPopup: function(_button, _event){
			var taskId = -1;
			if (_button.actionType == 'edit') {
			    var selectedRows = this.grid.getSelectionModel().getSelections();
                var task = selectedRows[0];
			} else {
                var nodeAttributes = Ext.getCmp('TasksTreePanel').getSelectionModel().getSelectedNode().attributes || {};
            }
            var containerId = (nodeAttributes && nodeAttributes.container) ? nodeAttributes.container.id : -1;
            
            var popupWindow = Tine.Tasks.EditDialog.openWindow({
                task: task,
                containerId: containerId
            });
            
            popupWindow.on('update', function(task) {
            	this.store.load({params: this.paging});
            }, this);
            
        },
		deleteTasks: function(_button, _event){
            var selectedRows = this.grid.getSelectionModel().getSelections();
			Ext.MessageBox.confirm('Confirm', this.translation.ngettext(
                'Do you really want to delete the selected task', 
                'Do you really want to delete the selected task', 
                 selectedRows.length), function(_button) {
                
                if(_button == 'yes') {
				    if (selectedRows.length < 1) {
				        return;
				    }
					if (selectedRows.length > 1) {
						var identifiers = [];
						for (var i=0; i < selectedRows.length; i++) {
							identifiers.push(selectedRows[i].data.id);
						} 
						var params = {
		                    method: 'Tasks.deleteTask', 
		                    identifier: Ext.util.JSON.encode(identifiers)
		                };
					} else {
						var params = {
		                    method: 'Tasks.deleteTask', 
		                    identifier: selectedRows[0].data.id
		                };
					}
				    
					Ext.Ajax.request({
						scope: this,
		                params: params,
		                success: function(_result, _request) {
		                    this.store.load({params: this.paging});
		                },
		                failure: function ( result, request) { 
		                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not delete task(s).')); 
		                }
		            });
				}
			}, this);
		}
	},
    
        
	initComponent: function() {
		
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
    
    	this.actions = {
            editInPopup: new Ext.Action({
                requiredGrant: 'readGrant',
                
                text: this.translation._('Edit task'),
    			disabled: true,
    			actionType: 'edit',
                handler: this.handlers.editInPopup,
                iconCls: 'action_edit',
                scope: this
            }),
            addInPopup: new Ext.Action({
                requiredGrant: 'addGrant',
    			actionType: 'add',
                text: this.translation._('Add task'),
                handler: this.handlers.editInPopup,
                iconCls: 'TasksIconCls',
                scope: this
            }),
            deleteTasks: new Ext.Action({
                requiredGrant: 'deleteGrant',
                allowMultiple: true,
                singularText: 'Delete task',
                pluralText: 'Delete tasks',
                translationObject: this.translation,
                text: this.translation.ngettext('Delete task', 'Delete tasks', 1),
                handler: this.handlers.deleteTasks,
    			disabled: true,
                iconCls: 'action_delete',
                scope: this
            })
        };
        
        this.filter.owner = Tine.Tinebase.Registry.get('currentAccount').accountId;
        this.initStore();
        this.initToolbar();
        this.initGrid();
        
    },
    
	initStore: function(){
	    this.store = new Ext.data.JsonStore({
			id: 'id',
            root: 'results',
            totalProperty: 'totalcount',
			successProperty: 'status',
			fields: Tine.Tasks.Task,
			remoteSort: true,
			baseParams: {
                method: 'Tasks.searchTasks'
            },
            sortInfo: {
                field: 'due',
                dir: 'ASC'
            }
        });
		
		// register store
		Ext.StoreMgr.add('TaskGridStore', this.store);
		
		// prepare filter
		this.store.on('beforeload', function(store, options){
			// console.log(options);
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
            var nodeAttributes = Ext.getCmp('TasksTreePanel').getSelectionModel().getSelectedNode().attributes || {};
            this.filter.containerType = nodeAttributes.containerType ? nodeAttributes.containerType : 'all';
            this.filter.owner = nodeAttributes.owner ? nodeAttributes.owner.accountId : null;
            this.filter.container = nodeAttributes.container ? nodeAttributes.container.id : null;
            
            // toolbar
			this.filter.showClosed = Ext.getCmp('TasksShowClosed') ? Ext.getCmp('TasksShowClosed').pressed : false;
			this.filter.organizer = Ext.getCmp('TasksorganizerFilter') ? Ext.getCmp('TasksorganizerFilter').getValue() : '';
			this.filter.query = Ext.getCmp('TasksQuickSearchField') ? Ext.getCmp('TasksQuickSearchField').getValue() : '';
			this.filter.status_id = Ext.getCmp('TasksStatusFilter') ? Ext.getCmp('TasksStatusFilter').getValue() : '';
			//this.filter.due
			//this.filter.tag
			options.params.filter = Ext.util.JSON.encode(this.filter);
		}, this);
		
		this.store.on('update', function(store, task, operation) {
			switch (operation) {
				case Ext.data.Record.EDIT:
					Ext.Ajax.request({
    					scope: this,
    	                params: {
    	                    method: 'Tasks.saveTask', 
    	                    task: Ext.util.JSON.encode(task.data),
    						linkingApp: '',
    						linkedId: ''					
    	                },
    	                success: function(_result, _request) {
                            store.commitChanges();
                            
                            // update task in grid store to prevent concurrency problems
                            var updatedTask = new Tine.Tasks.Task(Ext.util.JSON.decode(_result.responseText));
                            Tine.Tasks.fixTask(updatedTask);
                            task.data = updatedTask.data;
    
    						// reloading the store feels like http 1.x
    						// maybe we should reload if the sort critera changed, 
    						// but even this might be confusing
    						//store.load({params: this.paging});
    	                },
    	                failure: function ( result, request) { 
    	                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
    	                }
    				});
				break;
				case Ext.data.Record.COMMIT:
				    //nothing to do, as we need to reload the store anyway.
				break;
			}
		}, this);
	},
	
    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('TasksTreePanel');

        adminButton.setDisabled(true);

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('TasksTreePanel');
        preferencesButton.setDisabled(true);
    },

	getTree: function() {
        var translation = new Locale.Gettext();
        translation.textdomain('Tasks');

        this.tree =  new Tine.widgets.container.TreePanel({
            id: 'TasksTreePanel',
            iconCls: 'TasksIconCls',
            title: translation._('Tasks'),
            itemName: translation._('to do lists'),
            folderName: 'to do list',
            appName: 'Tasks',
            border: false
        });
        
        
        this.tree.on('click', function(node){
        	// note: if node is clicked, it is not selected!
        	node.getOwnerTree().selectPath(node.getPath());
            this.store.load({params: this.paging});
        }, this);
        
        return this.tree;
    },
    
	// toolbar must be generated each time this fn is called, 
	// as tinebase destroys the old toolbar when setting a new one.
	initToolbar: function(){
		var TasksQuickSearchField = new Ext.ux.SearchField({
			id: 'TasksQuickSearchField',
			width: 200,
			emptyText: this.translation._('Enter searchfilter')
		});
		TasksQuickSearchField.on('change', function(field){
			if(this.filter.query != field.getValue()){
				this.store.load({params: this.paging});
			}
		}, this);
		
		var showClosedToggle = new Ext.Button({
			id: 'TasksShowClosed',
			enableToggle: true,
			handler: function(){
				this.store.load({params: this.paging});
			},
			scope: this,
			text: this.translation._('Show closed'),
			iconCls: 'action_showArchived'
		});
		
		var statusFilter = new Ext.ux.form.ClearableComboBox({
			id: 'TasksStatusFilter',
			//name: 'statusFilter',
			hideLabel: true,
			store: Tine.Tasks.status.getStore(),
			displayField: 'status_name',
			valueField: 'id',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText: 'any',
			selectOnFocus: true,
			editable: false,
			width: 150
		});
		
		statusFilter.on('select', function(combo, record, index){
			this.store.load({params: this.paging});
		},this);
		
		var organizerFilter = new Tine.widgets.AccountpickerField({
			id: 'TasksorganizerFilter',
			width: 200,
		    emptyText: 'any'
		});
		
		organizerFilter.on('select', function(combo, record, index){
            this.store.load({params: this.paging});
            //combo.triggers[0].show();
        }, this);
		
		this.toolbar = new Ext.Toolbar({
			id: 'Tasks_Toolbar',
			split: false,
			height: 26,
			items: [
			    this.actions.addInPopup,
				this.actions.editInPopup,
				this.actions.deleteTasks,
				new Ext.Toolbar.Separator(),
				'->',
				showClosedToggle,
				//'Status: ',	' ', statusFilter,
				//'Organizer: ', ' ',	organizerFilter,
				new Ext.Toolbar.Separator(),
				'->',
				this.translation._('Search:'), ' ', ' ', TasksQuickSearchField]
		});
	},
	
    initGrid: function(){
        //this.sm = new Ext.grid.CheckboxSelectionModel();
        var pagingToolbar = new Ext.PagingToolbar({
	        pageSize: 50,
	        store: this.store,
	        displayInfo: true,
	        displayMsg: this.translation._('Displaying tasks {0} - {1} of {2}'),
	        emptyMsg: this.translation._("No tasks to display")
	    });
		
		this.grid = new Ext.ux.grid.QuickaddGridPanel({
            id: 'TasksMainGrid',
			border: false,
            store: this.store,
			tbar: pagingToolbar,
			clicksToEdit: 'auto',
            enableColumnHide:false,
            enableColumnMove:false,
            region:'center',
			sm: new Ext.grid.RowSelectionModel(),
			loadMask: true,
            columns: [
				{
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
					width: 400,
					sortable: true,
					dataIndex: 'summary',
					//editor: new Ext.form.TextField({
					//	allowBlank: false
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
				}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ],
		    quickaddMandatory: 'summary',
			autoExpandColumn: 'summary',
			view: new Ext.grid.GridView({
                autoFill: true,
	            forceFit:true,
	            ignoreAdd: true,
	            emptyText: this.translation._('No Tasks to display')
	        })
        });
		
		this.grid.on('rowdblclick', function(grid, row, event){
			this.handlers.editInPopup.call(this, {actionType: 'edit'});
		}, this);
		
		this.grid.getSelectionModel().on('selectionchange', function(sm){
            Tine.widgets.ActionUpdater(sm, this.actions);
		}, this);
		
		this.grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
			_eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }

			var ctxMenu = new Ext.menu.Menu({
		        items: [
                    this.actions.editInPopup,
                    this.actions.deleteTasks,
                    '-',
                    this.actions.addInPopup
                ]
		    });
            
            ctxMenu.showAt(_eventObject.getXY());
        }, this);
		
		this.grid.on('keydown', function(e){
	         if(e.getKey() == e.DELETE && !this.grid.editing){
	             this.handlers.deleteTasks.call(this);
	         }
	    }, this);
        		
	    this.grid.on('newentry', function(taskData){
	    	var selectedNode = this.tree.getSelectionModel().getSelectedNode();
            taskData.container_id = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.id : -1;
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
                    Ext.StoreMgr.get('TaskGridStore').load({params: this.paging});
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
                }
            });
            return true;
	    }, this);
	    
		// hack to get percentage editor working
		this.grid.on('rowclick', function(grid,row,e) {
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
    }    
};

/*********************************** EDIT DIALOG ********************************************/

/**
 * Tasks Edit Dialog
 */
Tine.Tasks.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * @cfg {Tine.Addressbook.Model.Contact}
     */
    task: null,
    /**
     * @cfg {Number}
     */
    containerId: -1,
    /**
     * @cfg {String}
     */
    relatedApp: '',
    
    /**
     * @private
     */
    labelAlign: 'side',
    /**
     * @private
     */
    windowNamePrefix: 'TasksEditWindow_',
    /**
     * @private
     */
    initComponent: function() {
        this.task = this.task ? this.task : new Tine.Tasks.Task({}, 0);
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Tasks.getTask',
                uid: this.task.id,
                containerId: this.containerId,
                relatedApp: this.relatedApp
            }
        });
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
        
        this.items = this.getTaskFormPanel();
        Tine.Tasks.EditDialog.superclass.initComponent.call(this);
    },
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Tasks.EditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Task...'), _('Please Wait'));
    },
    /**
     * @private
     */
    onRecordLoad: function(response) {
        this.getForm().findField('summary').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.task.id) {
            window.document.title = this.translation.gettext('Add New Task');
        } else {
            window.document.title = sprintf(this.translation._('Edit Task "%s"'), this.task.get('summary'));
        }
        
        this.getForm().loadRecord(this.task);
        Ext.MessageBox.hide();
    },
    
    updateRecord: function(recordData) {
        this.task = new Tine.Tasks.Task(recordData, recordData.id ? recordData.id : 0);
        Tine.Tasks.fixTask(this.task);
    },
    /**
     * @private
     */
    handlerApplyChanges: function(_button, _event) {
		var closeWindow = arguments[2] ? arguments[2] : false;

        var form = this.getForm();
		if(form.isValid()) {
			Ext.MessageBox.wait(this.translation._('Please wait'), this.translation._('Saving Task'));
			
			// merge changes from form into task record
			form.updateRecord(this.task);
			
            Ext.Ajax.request({
                scope: this,
				params: {
	                method: 'Tasks.saveTask', 
	                task: Ext.util.JSON.encode(this.task.data)
	            },
	            success: function(response) {
					// override task with returned data
                    this.onRecordLoad(response);
					//this.updateRecord(Ext.util.JSON.decode(_result.responseText));
                    
                    var win = this.windowManager.get(window);
                    // free 0 namespace if record got created
                    win.rename(this.windowNamePrefix + this.task.id);
                    win.fireEvent('update', this.task);

					if (closeWindow) {
                        this.windowManager.get(window).purgeListeners();
                        window.setTimeout("window.close()", 1000);
                    } else {
                        // update form with this new data
                        form.loadRecord(this.task);
                        this.action_delete.enable();
                    	Ext.MessageBox.hide();
                    }
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
	            } 
			});
        } else {
            Ext.MessageBox.alert(this.translation._('Errors'), this.translation._('Please fix the errors noted.'));
        }
	},
    /**
     * @private
     */
	handlerDelete: function(_button, _event) {
		Ext.MessageBox.confirm(this.translation._('Confirm'), this.translation._('Do you really want to delete this task?'), function(_button) {
            if(_button == 'yes') {
		        Ext.MessageBox.wait(this.translation._('Please wait a moment...'), this.translation._('Saving Task'));
    			Ext.Ajax.request({
                    params: {
    					method: 'Tasks.deleteTask',
    					identifier: this.task.id
    				},
                    success: function(_result, _request) {
                        this.windowManager.get(window).fireEvent('update', this.task);
                        this.windowManager.get(window).purgeListeners();
    					window.setTimeout("window.close()", 1000);
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not delete task(s).'));
    					Ext.MessageBox.hide();
                    }
    			});
			}
		});
	},
	
	getTaskFormPanel: function() { return {
		layout:'column',
		autoHeight: true,
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
				fieldLabel: this.translation._('Summary'),
				hideLabel: true,
				xtype: 'textfield',
				name: 'summary',
				emptyText: this.translation._('Enter short name...'),
				allowBlank: false
			}, {
				fieldLabel: this.translation._('Notes'),
				hideLabel: true,
                emptyText: this.translation._('Enter description...'),
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
                new Ext.ux.PercentCombo({
                    fieldLabel: this.translation._('Percentage'),
                    editable: false,
                    name: 'percent'
                }), 
                new Tine.Tasks.status.ComboBox({
                    fieldLabel: this.translation._('Status'),
                    name: 'status_id'
                }), 
                new Tine.widgets.Priority.Combo({
                    fieldLabel: this.translation._('Priority'),
                    name: 'priority'
                }), 
                new Ext.ux.form.ClearableDateField({
                    fieldLabel: this.translation._('Due date'),
                    name: 'due'
                }), 
                new Tine.widgets.container.selectionComboBox({
                    fieldLabel: this.translation._('Folder'),
                    name: 'container_id',
                    itemName: 'Tasks',
                    appName: 'Tasks'
                })
            ]
        }]
	};},
});

/**
 * Tasks Edit Popup
 */
Tine.Tasks.EditDialog.openWindow = function (config) {
    config.task = config.task ? config.task : new Tine.Tasks.Task({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 300,
        name: Tine.Tasks.EditDialog.prototype.windowNamePrefix + config.task.id,
        layout: Tine.Tasks.EditDialog.prototype.windowLayout,
        itemsConstructor: 'Tine.Tasks.EditDialog',
        itemsConstructorConfig: config
    });
    return window;
};

// fixes a task
Tine.Tasks.fixTask = function(task) {
    if (task.data.due) {
        task.data.due = Date.parseDate(task.data.due, 'c');
    }
};

// Task model
Tine.Tasks.TaskArray = [
    // tine record fields
    { name: 'container_id'                                     },
    { name: 'creation_time',      type: 'date', dateFormat: 'c'},
    { name: 'created_by',         type: 'int'                  },
    { name: 'last_modified_time', type: 'date', dateFormat: 'c'},
    { name: 'last_modified_by',   type: 'int'                  },
    { name: 'is_deleted',         type: 'boolean'              },
    { name: 'deleted_time',       type: 'date', dateFormat: 'c'},
    { name: 'deleted_by',         type: 'int'                  },
    // task only fields
    { name: 'id' },
    { name: 'percent' },
    { name: 'completed', type: 'date', dateFormat: 'c' },
    { name: 'due', type: 'date', dateFormat: 'c' },
    // ical common fields
    { name: 'class_id' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'priority' },
    { name: 'status_id' },
    { name: 'summary' },
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
];
Tine.Tasks.Task = Ext.data.Record.create(
    Tine.Tasks.TaskArray
);
	
