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

Tine.Timetracker.TreePanel = Ext.extend(Ext.tree.TreePanel,{
    rootVisible: false,
    border: false,
    
    initComponent: function() {
        this.root = {
            id: 'root',
            children: [{
                text: this.app.i18n._('Timesheets'),
                id : 'timesheets',
                iconCls: 'TimetrackerTimesheet',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Timesheets'),
                    id: 'alltimesheets',
                    leaf: true,
                    listeners: {
                        scope: this,
                        click: function() {alert('timesheets');}
                    }
                }]
            }, {
                text: this.app.i18n._('Timeaccounts'),
                id: 'timeaccounts',
                iconCls: 'TimetrackerTimeaccount',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Timeaccounts'),
                    id: 'alltimeaccounts',
                    leaf: true,
                    listeners: {
                        scope: this,
                        click: function() {alert('timeaccounts');}
                    }
                }]
            }]
        };
        
    	Tine.Timetracker.TreePanel.superclass.initComponent.call(this);
	},
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Timetracker.TreePanel.superclass.afterRender.call(this);
        this.expandPath('/root/timesheets/alltimesheets');
        this.selectPath('/root/timesheets/alltimesheets');
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

/**
 * default timesaccounts backend
 */
Tine.Timetracker.timeaccountBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    recordClass: Tine.Timetracker.Model.Timeaccount
});