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
    


/**
 * default timesheets backend
 */
Tine.Timetracker.timesheetBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: Tine.Timetracker.Model.Timesheet
});
