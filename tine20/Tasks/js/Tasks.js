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
        Tine.Tinebase.MainScreen.setActiveToolbar(this.gridPanel.actionToolbar, true);
        this.updateMainToolbar();
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
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
     * @property {Tine.Tinebase.widgets.app.GridPanel} gridPanel
     */
    gridPanel: null,
    /**
     * holds default paging information
     * @depricated, to be moved to gridPanel
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

	editInPopup: function(_button, _event){
		var taskId = -1;
		if (_button.actionType == 'edit') {
		    var selectedRows = this.gridPanel.grid.getSelectionModel().getSelections();
            var task = selectedRows[0];
		} else {
            var nodeAttributes = Ext.getCmp('TasksTreePanel').getSelectionModel().getSelectedNode().attributes || {};
        }
        var containerId = (nodeAttributes && nodeAttributes.container) ? nodeAttributes.container.id : -1;
        
        var popupWindow = Tine.Tasks.EditDialog.openWindow({
            record: task,
            containerId: containerId,
            listeners: {
                scope: this,
                'update': function(task) {
                    this.store.load({params: this.paging});
                }
            }
        });
    },
    
	initComponent: function() {
		
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
        this.filter.owner = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        // tmp hack for edit handler
        scopehelper = this;
        
        this.gridPanel = new Tine.Tinebase.widgets.app.GridPanel({
            // model generics
            appName: 'Tasks',
            modelName: 'Task',
            recordClass: Tine.Tasks.Task,
            titleProperty: 'summary',
            containerItemName: 'Task',
            containerItemsName: 'Tasks',
            containerName: 'to do list',
            containesrName: 'to do lists',
            
            // grid spechials
            actionToolbarItems: this.getToolbarItems(),
            defaultSortInfo: {field: 'due', dir: 'ASC'},
            recordProxy: Tine.Tasks.JsonBackend,
            gridConfig: {
                clicksToEdit: 'auto',
                enableColumnHide:false,
                enableColumnMove:false,
                region:'center',
                loadMask: true,
                quickaddMandatory: 'summary',
                autoExpandColumn: 'summary',
                columns: this.getColumns()
            },
            
            // tmp edit handler
            onEditInNewWindow: function(btn, e) {
                scopehelper.editInPopup(btn, e);
            },
            
        });
        this.store = this.gridPanel.store;
        this.initStoreEvents();
        this.initGridEvents();
    },
    
	initStoreEvents: function(){
		// prepare filter
		this.gridPanel.store.on('beforeload', function(store, options) {
            options.params = options.params || {};
            Ext.applyIf(options.params, this.paging);
            
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
		
		this.gridPanel.store.on('update', function(store, task, operation) {
			switch (operation) {
				case Ext.data.Record.EDIT:
                    Tine.Tasks.JsonBackend.saveRecord(task, {
                        scope: this,
                        success: function(updatedTask) {
                            store.commitChanges();
                            // update task in grid store to prevent concurrency problems
                            task.data = updatedTask.data;
    
                            // reloading the store feels like oldschool 1.x
                            // maybe we should reload if the sort critera changed, 
                            // but even this might be confusing
                            //store.load({params: this.paging});
                        }
                    });
				    break;
				case Ext.data.Record.COMMIT:
				    //nothing to do, as we need to reload the store anyway.
				    break;
			}
		}, this);
	},
            
    initGridEvents: function() {    
        this.gridPanel.grid.on('newentry', function(taskData){
            var selectedNode = this.tree.getSelectionModel().getSelectedNode();
            taskData.container_id = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.id : -1;
            var task = new Tine.Tasks.Task(taskData);
            
            Tine.Tasks.JsonBackend.saveRecord(task, {
                scope: this,
                success: function() {
                    this.store.load({params: this.paging});
                },
                failure: function () { 
                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
                }
            });
            return true;
        }, this);
        
        // hack to get percentage editor working
        this.gridPanel.grid.on('rowclick', function(grid,row,e) {
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
    },
	
	getToolbarItems: function(){
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
		
		return [
			new Ext.Toolbar.Separator(),
			'->',
			showClosedToggle,
			//'Status: ',	' ', statusFilter,
			//'Organizer: ', ' ',	organizerFilter,
			new Ext.Toolbar.Separator(),
			'->',
			this.translation._('Search:'), ' ', ' ', TasksQuickSearchField
        ];
	},
	
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
		return  [{
            id: 'summary',
            header: this.translation._("Summary"),
            width: 400,
            sortable: true,
            dataIndex: 'summary',
            //editor: new Ext.form.TextField({
            //  allowBlank: false
            //}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.translation._('Add a task...')
            })
        }, {
            id: 'due',
            header: this.translation._("Due Date"),
            width: 55,
            sortable: true,
            dataIndex: 'due',
            renderer: Tine.Tinebase.common.dateRenderer,
            editor: new Ext.ux.form.ClearableDateField({
                //format : 'd.m.Y'
            }),
            quickaddField: new Ext.ux.form.ClearableDateField({
                //value: new Date(),
                //format : "d.m.Y"
            })
        }, {
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
        }, {
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
        }, {
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
		}];
    },
    
    
    
    
    
    updateMainToolbar : function() {
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
            containersName: translation._('to do lists'),
            containerName: translation._('to do list'),
            appName: 'Tasks',
            border: false
        });
        
        
        this.tree.on('click', function(node){
            // note: if node is clicked, it is not selected!
            node.getOwnerTree().selectPath(node.getPath());
            this.store.load({params: this.paging});
        }, this);
        
        return this.tree;
    }
};

// Task model
Tine.Tasks.TaskArray = [
    // tine record fields
    { name: 'container_id', header: 'Container'                                     },
    { name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'created_by',         type: 'int'                  },
    { name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'last_modified_by',   type: 'int'                  },
    { name: 'is_deleted',         type: 'boolean'              },
    { name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'deleted_by',         type: 'int'                  },
    // task only fields
    { name: 'id' },
    { name: 'percent', header: 'Percent' },
    { name: 'completed', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'due', type: 'date', dateFormat: Date.patterns.ISO8601Long },
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
    { name: 'dtstart', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'duration', type: 'date', dateFormat: Date.patterns.ISO8601Long },
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

Tine.Tasks.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Task
});
