/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Tasks');

/**
 * Tasks grid panel
 * 
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.TaskGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Tasks Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tasks.TaskGridPanel
 */
Tine.Tasks.TaskGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Tasks.Model.Task} recordClass
     */
    recordClass: Tine.Tasks.Model.Task,
    
    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'due', dir: 'ASC'},
    gridConfig: {
        clicksToEdit: 'auto',
        quickaddMandatory: 'summary',
        resetAllOnNew: false,
        autoExpandColumn: 'summary',
        // drag n drop
        enableDragDrop: true,
        ddGroup: 'containerDDGroup'
    },
    
    // specialised translations
    // ngettext('Do you really want to delete the selected task?', 'Do you really want to delete the selected tasks?', n);
    i18nDeleteQuestion: ['Do you really want to delete the selected task?', 'Do you really want to delete the selected tasks?'],
    
    /**
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Tasks.JsonBackend;
        this.gridConfig.cm = this.getColumnModel();
        
        this.defaultFilters = [
            {field: 'container_id', operator: 'equals', value: {path: Tine.Tinebase.container.getMyNodePath()}}
        ];
        Tine.Tasks.TaskGridPanel.superclass.initComponent.call(this);
        
        // the editGrids onEditComplete calls the focusCell after a edit operation
        // this leads to a 'flicker' effect we dont want!
        // mhh! but disabling this, breaks keynav 
        //this.grid.view.focusCell = Ext.emptyFn;
    },
    
    /**
     * returns cm
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        
        var columns = [
            {id: 'attachments', header: window.i18n._('Attachments'), tooltip: window.i18n._('Attachments'), dataIndex: 'attachments', width: 20, sortable: false, resizable: false, renderer: Tine.widgets.grid.attachmentRenderer, hidden: false},
            {id: 'tags', header: this.app.i18n._('Tags'), width: 40,  dataIndex: 'tags', sortable: false, renderer: Tine.Tinebase.common.tagsRenderer}
        ];
        
        if (Tine.hasOwnProperty('Crm') && Tine.Tinebase.common.hasRight('run', 'Crm')) {
            columns.push({
                id: 'lead',
                header: this.app.i18n._('Lead name'),
                width: 150,
                dataIndex: 'relations',
                renderer: Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'lead'),
                sortable: false
            });
        }
        
        columns = columns.concat([{
            id: 'summary',
            header: this.app.i18n._("Summary"),
            width: 400,
            dataIndex: 'summary',
            quickaddField: new Ext.form.TextField({
                emptyText: this.app.i18n._('Add a task...')
            })
        }, {
            id: 'due',
            header: this.app.i18n._("Due Date"),
            width: 145,
            dataIndex: 'due',
            renderer: Tine.Tinebase.common.dateTimeRenderer,
            editor: new Ext.ux.form.DateTimeField({
                defaultTime: '12:00',
                allowBlank: true
            }),
            quickaddField: new Ext.ux.form.DateTimeField({
                defaultTime: '12:00',
                allowBlank: true
            })
        }, {
            id: 'priority',
            header: this.app.i18n._("Priority"),
            width: 65,
            dataIndex: 'priority',
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskPriority'),
            editor: {
                xtype: 'widget-keyfieldcombo',
                app: 'Tasks',
                keyFieldName: 'taskPriority'
            },
            quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                app: 'Tasks',
                keyFieldName: 'taskPriority',
            })
        }, {
            id: 'percent',
            header: this.app.i18n._("Percent"),
            width: 50,
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
            id: 'status',
            header: this.app.i18n._("Status"),
            width: 85,
            dataIndex: 'status',
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus'),
            editor: {
                xtype: 'widget-keyfieldcombo',
                app: 'Tasks',
                keyFieldName: 'taskStatus',
                allowEmpty: false
            },
            quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                app: 'Tasks',
                keyFieldName: 'taskStatus',
                value: 'NEEDS-ACTION',
                // TODO make this work ...
                allowEmpty: false
            })
        }, {
            id: 'completed',
            header: this.app.i18n._("Completed"),
            hidden: true,
            width: 70,
            dataIndex: 'completed',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        }, {
            id: 'organizer',
            header: this.app.i18n._('Responsible'),
            width: 200,
            dataIndex: 'organizer',
            renderer: Tine.Tinebase.common.accountRenderer,
            quickaddField: Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                userOnly: true,
                useAccountRecord: true,
                blurOnSelect: true,
                selectOnFocus: true,
                allowEmpty: true,
                value: Tine.Tinebase.registry.get('currentAccount')
            })
        }].concat(this.getModlogColumns().concat(this.getCustomfieldColumns())));
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns
        });
    },

    /**
     * Return CSS class to apply to rows depending upon due status
     * 
     * @param {Tine.Tasks.Model.Task} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var due = record.get('due');
         
        var className = '';
        
        if(record.get('status') == 'COMPLETED') {
            className += 'tasks-grid-completed';
        } else  if (due) {
            var dueDay = due.format('Y-m-d');
            var today = new Date().format('Y-m-d');

            if (dueDay == today) {
                className += 'tasks-grid-duetoday';
            } else if (dueDay < today) {
                className += 'tasks-grid-overdue';
            }
            
        }
        return className;
    }
});
