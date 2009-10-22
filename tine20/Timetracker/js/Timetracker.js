/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         activate different gridpanels if subapp from treepanel is clicked
 * TODO         generalize this
 */
 
Ext.namespace('Tine.Timetracker');

Tine.Timetracker.TreePanel = Ext.extend(Tine.widgets.grid.PersistentFilterPicker, {
    
    filter: [{field: 'model', operator: 'equals', value: 'Timetracker_Model_TimesheetFilter'}],
    
    // quick hack to get filter saving grid working
    //recordClass: Tine.Timetracker.Model.Timesheet,
    initComponent: function() {
        this.filterMountId = 'Timesheet';
        
        this.root = {
            id: 'root',
            leaf: false,
            expanded: true,
            children: [{
                text: this.app.i18n._('Timesheets'),
                id : 'Timesheet',
                iconCls: 'TimetrackerTimesheet',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Timesheets'),
                    id: 'alltimesheets',
                    leaf: true
                }]
            }, {
                text: this.app.i18n._('Timeaccounts'),
                id: 'Timeaccount',
                iconCls: 'TimetrackerTimeaccount',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Timeaccounts'),
                    id: 'alltimeaccounts',
                    leaf: true
                }]
            }]
        };
        
    	Tine.Timetracker.TreePanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter != true) {
                var contentType = node.getPath().split('/')[2];
                
                this.app.getMainScreen().activeContentType = contentType;
                this.app.getMainScreen().show();
            }
        }, this);
	},
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Timetracker.TreePanel.superclass.afterRender.call(this);
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/' + type + '/alltimesheets');
        this.selectPath('/root/' + type + '/alltimesheets');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * ???
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    //var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        //{field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        //{field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        //{field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

//Tine.Timetracker.FilterPanel = Tine.widgets.grid.PersistentFilterPicker


/**
 * default timesheets backend
 */
Tine.Timetracker.timesheetBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: Tine.Timetracker.Model.Timesheet
});

/**
 * default timeaccounts backend
 */
Tine.Timetracker.timeaccountBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    recordClass: Tine.Timetracker.Model.Timeaccount
});