/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        activate different gridpanels if subapp from treepanel is clicked
 */
 
Ext.namespace('Tine.Timetracker');

// default mainscreen
Tine.Timetracker.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
	/*
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.setTreePanel();
            this.setContentPanel();
            this.setToolbar();
            this.updateMainToolbar();
            
            this.fireEvent('show', this);
        }
        return this;
    },*/
    setContentPanel: function() {
        if(!this.gridPanel) {
            var plugins = [];
            if (typeof(this.treePanel.getFilterPlugin) == 'function') {
                plugins.push(this.treePanel.getFilterPlugin());
            }
            
            this.gridPanel = new Tine[this.app.appName].TimesheetGridPanel({
                app: this.app,
                plugins: plugins
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
        this.gridPanel.store.load();
    }    
});

Tine.Timetracker.TreePanel = Ext.extend(Ext.tree.TreePanel,{
    initComponent: function() {
    	this.root = new Ext.tree.TreeNode({
            text: this.app.i18n._('Timesheets'),
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'root',
            icon: false
        });
    	Tine.Timetracker.TreePanel.superclass.initComponent.call(this);
	}
});
    
// Timesheet model
Tine.Timetracker.TimesheetArray = [
    // tine record fields
    { name: 'container_id', header: 'Container'                                     },
    { name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'created_by',         type: 'int'                  },
    { name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'last_modified_by',   type: 'int'                  },
    { name: 'is_deleted',         type: 'boolean'              },
    { name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
    { name: 'deleted_by',         type: 'int'                  },
    // timesheet only fields
    // @todo add more fields
    { name: 'id' },
    { name: 'description' },
    { name: 'account_id' },
    { name: 'contract_id' },
    // tine 2.0 notes field
    { name: 'notes'}
];

/**
 * Timesheet record definition
 */
Tine.Timetracker.Timesheet = Tine.Tinebase.Record.create(Tine.Timetracker.TimesheetArray, {
    appName: 'Timetracker',
    modelName: 'Timesheet',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Timesheet', 'Timesheets', n);
    recordName: 'Timesheets',
    recordsName: 'Timesheets',
    containerProperty: 'container_id',
    // ngettext('timesheets list', 'timesheets lists', n);
    containerName: 'timesheets list',
    containersName: 'timesheets lists'
});

/**
 * default timesheets backend
 */
Tine.Timetracker.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: Tine.Timetracker.Timesheet
});
