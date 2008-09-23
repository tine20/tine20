/*
 * Tine 2.0
 * 
 * @package     mobileClient
 * @subpackage  Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine', 'Tine.mobileClient');

Tine.mobileClient.Tasks = {
    
    /**
     * returns a record containing given data
     */
    updateRecord: function(recordData) {
        task = new this.record(recordData, recordData.id ? recordData.id : 0);
        if (task.data.due) {
            task.data.due = Date.parseDate(task.data.due, 'c');
        }
    },
    /**
     * @property {Ext.data.Record}
     */
    record: Ext.data.Record.create([
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
    ])
};

Tine.mobileClient.Tasks.MainGrid = Ext.extend(Ext.grid.GridPanel, {
     autoExpandColumn: 'summary',
     
    /**
     * @private
     */
    initComponent: function() {
        this.view = new Ext.grid.GridView({
            templates: {
                header: new Ext.Template('')
            }
        });
        
        this.store = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            //successProperty: 'status',
            fields: Tine.mobileClient.Tasks.record,
            remoteSort: true,
            baseParams: {
                method: 'Tasks.searchTasks',
                filter: Ext.util.JSON.encode({
                    containerType: 'all',
                    query: '',
                    due: false,
                    container: null,
                    organizer: null,
                    tag: false,
                    owner: null,
                    sort: 'due',
                    dir: 'ASC',
                    start: 0,
                    limit: 0,
                    showClosed: false,
                    statusId: ''
                })
            },
            sortInfo: {
                field: 'due',
                dir: 'ASC'
            }
        });
        this.store.load();
        
        this.columns = [{
            id: 'summary',
            sortable: false,
            dataIndex: 'summary',
            //renderer: this.renderRow
        }];
        
        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});
        
        Tine.mobileClient.Tasks.MainGrid.superclass.initComponent.call(this);
    },
    
    /**
     * render
     */
    //renderRow: function(value, metadata, record, colindex) {
    //    return value;
    //}
});

Tine.mobileClient.Tasks.getAppPanel = function () {

    return new Ext.Panel({
        id: 'mobileTaskAppPanel',
        layout: 'fit',
        border: false,
        buttonAlign: 'center',
        plugins: Ext.ux.tbarTitle, 
        tbarTitle: 'All Tasks',
        tbar: [
            {text: 'Settings', cls: 'x-btn-back', handler: function() {
                Ext.getCmp('mobileViewport').layout.setActiveItem('mobileSettingsPanel');
            }},
            '->',
            {text: 'Help', handler: function() {}}
        ],
        buttons: [
            {text: 'List', handler: function() {}, pressed: true },
            {text: 'Day', handler: function() {}},
            {text: 'Month', handler: function() {}}
        ],
        bbar: [
            {text: 'foo', handler: function() {}},
            {text: 'bar', handler: function() {}},
        ],
        items: new Tine.mobileClient.Tasks.MainGrid({})
    })
};
