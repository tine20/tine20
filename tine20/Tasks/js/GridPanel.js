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

/**
 * Tasks Edit Dialog
 */
Tine.Tasks.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Task,
    titleProperty: 'summary',
    containerItemName: 'Task',
    containerItemsName: 'Tasks',
    containerName: 'to do list',
    containesrName: 'to do lists',
    
    // grid specific
    defaultSortInfo: {field: 'due', dir: 'ASC'},
    gridConfig: {
        clicksToEdit: 'auto',
        enableColumnHide:false,
        enableColumnMove:false,
        loadMask: true,
        quickaddMandatory: 'summary',
        autoExpandColumn: 'summary'
        
    },
    
    // legacy
    filter: {
        containerType: 'personal',
        query: '',
        due: false,
        container: false,
        organizer: false,
        tag: false
    },
    
    initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();

        Tine.Tasks.GridPanel.superclass.initComponent.call(this);
        
        // legacy
        this.initStoreEvents();
        this.initGridEvents();
    },
    
    initStoreEvents: function(){
        // prepare filter
        this.store.on('beforeload', function(store, options) {
            options.params = options.params || {};
            Ext.applyIf(options.params, this.defaultPaging);
            
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
    },
            
    initGridEvents: function() {    
        this.grid.on('newentry', function(taskData){
            var selectedNode = Ext.getCmp('TasksTreePanel').getSelectionModel().getSelectedNode();
            taskData.container_id = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.id : -1;
            var task = new Tine.Tasks.Task(taskData);
            
            Tine.Tasks.JsonBackend.saveRecord(task, {
                scope: this,
                success: function() {
                    this.store.load({});
                },
                failure: function () { 
                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
                }
            });
            return true;
        }, this);
    },
    
    onEditInNewWindow: function(_button, _event){
        var taskId = -1;
        if (_button.actionType == 'edit') {
            var selectedRows = this.grid.getSelectionModel().getSelections();
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
                    this.store.load({});
                }
            }
        });
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
    
    /**
     * return additional tb items
     */
    getToolbarItems: function(){
        var TasksQuickSearchField = new Ext.ux.SearchField({
            id: 'TasksQuickSearchField',
            width: 200,
            emptyText: this.translation._('Enter searchfilter')
        });
        TasksQuickSearchField.on('change', function(field){
            if(this.filter.query != field.getValue()){
                this.store.load({});
            }
        }, this);
        
        var showClosedToggle = new Ext.Button({
            id: 'TasksShowClosed',
            enableToggle: true,
            handler: function(){
                this.store.load({});
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
            this.store.load({});
        },this);
        
        var organizerFilter = new Tine.widgets.AccountpickerField({
            id: 'TasksorganizerFilter',
            width: 200,
            emptyText: 'any'
        });
        
        organizerFilter.on('select', function(combo, record, index){
            this.store.load({});
            //combo.triggers[0].show();
        }, this);
        
        return [
            new Ext.Toolbar.Separator(),
            '->',
            showClosedToggle,
            //'Status: ',   ' ', statusFilter,
            //'Organizer: ', ' ',   organizerFilter,
            new Ext.Toolbar.Separator(),
            '->',
            this.translation._('Search:'), ' ', ' ', TasksQuickSearchField
        ];
    }
});