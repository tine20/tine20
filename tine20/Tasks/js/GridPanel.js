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
 * Tasks grid panel
 */
Tine.Tasks.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Tasks.Task,
    
    // grid specific
    defaultSortInfo: {field: 'due', dir: 'ASC'},
    gridConfig: {
        clicksToEdit: 'auto',
        loadMask: true,
        quickaddMandatory: 'summary',
        autoExpandColumn: 'summary'
    },
    
    // spechialised translations
    // ngettext('Do you really want to delete the selected task?', 'Do you really want to delete the selected tasks?', n);
    i18nDeleteQuestion: ['Do you really want to delete the selected task?', 'Do you really want to delete the selected tasks?'],
    
    initComponent: function() {
        this.recordProxy = Tine.Tasks.JsonBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins.push(this.action_showClosedToggle, this.filterToolbar);
        
        Tine.Tasks.GridPanel.superclass.initComponent.call(this);
        
        // legacy
        this.initGridEvents();
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n.n_('Task', 'Tasks', 1),    field: 'query',    operators: ['contains']},
                {label: this.app.i18n._('Summary'), field: 'summary' },
                {label: this.app.i18n._('Due Date'), field: 'due', valueType: 'date', operators: ['within', 'before', 'after']},
                {label: this.app.i18n._('Responsible'), field: 'organizer', valueType: 'user'},
                new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'query',
             filters: []
        });
    },
    
    // legacy
    initGridEvents: function() {    
        this.grid.on('newentry', function(taskData){
            var selectedNode = Ext.getCmp('TasksTreePanel').getSelectionModel().getSelectedNode();
            taskData.container_id = selectedNode && selectedNode.attributes.container ? selectedNode.attributes.container.id : null;
            var task = new Tine.Tasks.Task(taskData);
            
            Tine.Tasks.JsonBackend.saveRecord(task, {
                scope: this,
                success: function() {
                    this.store.load({});
                },
                failure: function () { 
                    Ext.MessageBox.alert(this.app.i18n._('Failed'), this.app.i18n._('Could not save task.')); 
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
            header: this.app.i18n._("Summary"),
            width: 400,
            sortable: true,
            dataIndex: 'summary',
            //editor: new Ext.form.TextField({
            //  allowBlank: false
            //}),
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a task...')
            })
        }, {
            id: 'due',
            header: this.app.i18n._("Due Date"),
            width: 55,
            sortable: true,
            dataIndex: 'due',
            renderer: Tine.Tinebase.common.dateRenderer,
            editor: new Ext.ux.form.ClearableDateField({}),
            quickaddField: new Ext.ux.form.ClearableDateField({})
        }, {
            id: 'priority',
            header: this.app.i18n._("Priority"),
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
            header: this.app.i18n._("Percent"),
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
            header: this.app.i18n._("Status"),
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
        }, {
            id: 'creation_time',
            header: this.app.i18n._("Creation Time"),
            hidden: true,
            width: 90,
            sortable: true,
            dataIndex: 'creation_time',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        }, {
            id: 'organizer',
            header: this.app.i18n._('Responsible'),
            width: 150,
            sortable: true,
            dataIndex: 'organizer',
            renderer: Tine.Tinebase.common.accountRenderer,
            editor: new Tine.widgets.AccountpickerField({
                autoExpand: true,
                blurOnSelect: true
            }),
            quickaddField: new Tine.widgets.AccountpickerField({
                autoExpand: true
            })
        }];
    },
    
    /**
     * return additional tb items
     */
    getToolbarItems: function(){
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        
        return [
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
        ];
    }
});