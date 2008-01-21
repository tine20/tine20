/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 *
 */
Ext.namespace('Egw.Tasks');

// entry point, required by egwbase
Egw.Tasks.getPanel = function() {
    
	// init stati
    Egw.Tasks.Status.init();
	
    var taskPanel =  new Egw.containerTreePanel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
		itemName: 'Tasks',
		appName: 'Tasks',
        //items: new Ext.DatePicker({}),
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
			
				{id: 'status',    header: "Status",    width: 40,  sortable: true, dataIndex: 'status', renderer: Egw.Tasks.Status.getStatusIcon },
				{id: 'percent',   header: "Percent",   width: 50,  sortable: true, dataIndex: 'percent', renderer: _progressBar },
				{id: 'summary',   header: "Summaray",  width: 200, sortable: true, dataIndex: 'summaray'},
				{id: 'priority',  header: "Priority",  width: 20,  sortable: true, dataIndex: 'priority'},
				{id: 'due',       header: "Due Date",  width: 100, sortable: true, dataIndex: 'due', renderer: Egw.Egwbase.Common.dateRenderer },
				{id: 'organizer', header: "Organizer", width: 150, sortable: true, dataIndex: 'organizer'}
				//{header: "Completed", width: 200, sortable: true, dataIndex: 'completed'}
		    ]),
			autoExpandColumn: 'summary'
        });
		
		console.log(grid.getColumnModel().getColumnById('priority'));
    };
	
	_progressBar = function(percent) {
		return '<div class="x-progress-wrap TasksProgress">' +
                '<div class="x-progress-inner TasksProgress">' +
                    '<div class="x-progress-bar TasksProgress" style="width:' + percent + '%">' +
                        '<div class="TasksProgressText TasksProgress">' +
                            '<div>'+ percent +'%</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="x-progress-text x-progress-text-back TasksProgress">' +
                        '<div>&#160;</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
	};
	
	return{
		getGrid: function() {initStore(); initGrid(); return grid;}
	}
}();

Egw.Tasks.getToolbar = function() {

        var action_add = new Ext.Action({
        text: 'add',
        iconCls: 'action_add',
        handler: function () {
        //  var tree = Ext.getCmp('venues-tree');
        //  var curSelNode = tree.getSelectionModel().getSelectedNode();
        //  var RootNode   = tree.getRootNode();
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
        
        
        var statusFilter = new Ext.app.ClearableComboBox({
            id: 'TasksStatusFilter',
            //name: 'statusFilter',
            hideLabel: true,            
            store: Egw.Tasks.Status.Store,
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

