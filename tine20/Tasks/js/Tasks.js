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

Egw.Tasks.getToolbar = function() {

	  	var action_add = new Ext.Action({
		text: 'add',
		iconCls: 'action_add',
		handler: function () {
       //     var tree = Ext.getCmp('venues-tree');
	//		var curSelNode = tree.getSelectionModel().getSelectedNode();
		//	var RootNode   = tree.getRootNode();
        
            Egw.Egwbase.Common.openWindow('TasksEditWindow', 'index.php?method=Tasks.editTask&_taskId=', 900, 700);
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
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id:             'Crm_dateFrom',
            allowBlank:     false,
            validateOnBlur: false,
            value:          oneWeekAgo
        });
        var dateTo = new Ext.form.DateField({
            id:             'Crm_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            value:          currentDate
        });
        

        
        var toolbar = new Ext.Toolbar({
            id: 'Crm_toolbar',
            split: false,
            height: 26,
            items: [
                action_add,
                new Ext.Toolbar.Separator(),
                '->',
                'Display from: ',
                ' ',
                dateFrom,
                'to: ',
                ' ',
                dateTo,                
                new Ext.Toolbar.Separator(),
                '->',
                'Search:', ' ',
                ' ',
                quickSearchField
            ]
        });
        
        return toolbar;
    }
  


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
        Egw.Egwbase.MainScreen.setActiveToolbar(Egw.Tasks.getToolbar());
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
            
			fields: [
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
			
				{id: 'status',    header: "Status",    width: 40,  sortable: true, dataIndex: 'status'},
				{id: 'percent',   header: "Percent",   width: 50,  sortable: true, dataIndex: 'percent'},
				{id: 'summary',   header: "Summaray",  width: 200, sortable: true, dataIndex: 'summaray'},
				{id: 'priority',  header: "Priority",  width: 20,  sortable: true, dataIndex: 'priority'},
				{id: 'duration',  header: "Due Date",  width: 150, sortable: true, dataIndex: 'duration'},
				{id: 'organizer', header: "Organizer", width: 150, sortable: true, dataIndex: 'organizer'}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ]),
			autoExpandColumn: 'summary'
        });
		
		console.log(grid.getColumnModel().getColumnById('priority'));
    }
	
	return{
		getGrid: function() {initStore(); initGrid(); return grid;}
	}
}();

Egw.Tasks.EditDialog = function(){
  
    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                setProjectDialogValues(formData);
            }
         }
        
    }

}
();

