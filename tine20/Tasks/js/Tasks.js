/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 *
 */
Ext.namespace('Egw.Tasks');

// entry point, required by egwbase
Egw.Tasks.getPanel = function() {
    
    var taskPanel =  new Ext.Panel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        items: new Ext.DatePicker({}),
        border: false
    });
    
    taskPanel.on('beforeexpand', function(_calPanel) {
        Egw.Egwbase.MainScreen.setActiveContentPanel(Egw.Tasks.TaskGrid.getGrid());
        //Egw.Egwbase.MainScreen.setActiveToolbar(Egw.Calendar.ToolBar.getToolBar());
    });
    
    return taskPanel;
}

Egw.Tasks.TaskGrid = function(){
    var sm;
    var grid;
    var store;
	
	initStore = function(){
		store = new Ext.data.JsonStore({
            baseParams: {
                method: 'Tasks.searchTasks'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'identifier',
            /*
			fields: [
			    // egw record fields
		        { name: 'container' },
		        { name: 'created_by' },
		        //{ name: 'creation_time', type: 'date', dateFormat: 'c' },
		        { name: 'last_modified_by' },
		        //{ name: 'last_modified_time', type: 'date', dateFormat: 'c' },
		        { name: 'is_deleted' },
		        //{ name: 'deleted_time', type: 'date', dateFormat: 'c' },
		        { name: 'deleted_by' },
		        // task only fields
		        //{ name: 'identifier' },
		        { name: 'percent' },
		        //{ name: 'completed', type: 'date', dateFormat: 'c' },
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
		        //{ name: 'dtstart', type: 'date', dateFormat: 'c' },
		        //{ name: 'duration', type: 'date', dateFormat: 'c' },
		        { name: 'recurid' },
		        // scheduleable interface fields with multiple appearance
		        { name: 'exdate' },
		        { name: 'exrule' },
		        { name: 'rdate' },
		        { name: 'rrule' }
            ],
            */
			fields: [
                // egw record fields
                { name: 'priority' },
                { name: 'summaray' },
				{ name: 'organizer' }
		    ],
            // turn on remote sorting
            remoteSort: true    
        });
		
		store.load({
			params:{
				query: '',
				due: false,
				container: false,
				organizer: false,
				tag: false
			}
		});
		
	};
	
    initGrid = function(){
        //sm = new Ext.grid.CheckboxSelectionModel();
        grid = new Ext.grid.GridPanel({
            store: store,
            cm: new Ext.grid.ColumnModel([
				{header: "Prio", width: 20, sortable: true, dataIndex: 'priority'},
				{header: "Summaray", width: 200, sortable: true, dataIndex: 'summaray'},
				{header: "Organizer", width: 200, sortable: true, dataIndex: 'organizer'}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ])
        })
    }
	
	return{
		getGrid: function() {initStore(); initGrid(); return grid;}
	}
}
();
