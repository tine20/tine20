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
 * entry point, required by tinebase
 * This function is called once when Tinebase collect the available apps
 */
Tine.Tasks.getPanel =  function() {
    Tine.Tasks.mainGrid.initComponent();
	var tree = Tine.Tasks.mainGrid.tree;
    
    // this function is called each time the user activates the Tasks app
    tree.on('beforeexpand', function(panel) {
        Tine.Tinebase.MainScreen.setActiveToolbar(this.gridPanel.actionToolbar, true);
        this.updateMainToolbar();
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
        this.gridPanel.store.load({});
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
     * @private
     */
	initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
        
        this.tree = new Tine.widgets.container.TreePanel({
            id: 'TasksTreePanel',
            iconCls: 'TasksIconCls',
            title: this.translation._('Tasks'),
            containersName: this.translation._('to do lists'),
            containerName: this.translation._('to do list'),
            appName: 'Tasks',
            border: false
        });
        
        this.gridPanel = new Tine.Tasks.GridPanel({
            recordProxy: Tine.Tasks.JsonBackend,
            plugins: [this.tree.getFilterPlugin()]
        });
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
    { name: 'tags' },
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
    { name: 'rrule' },
    // tine 2.0 notes field
    { name: 'notes'}
];
Tine.Tasks.Task = Ext.data.Record.create(
    Tine.Tasks.TaskArray
);

Tine.Tasks.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Task
});
