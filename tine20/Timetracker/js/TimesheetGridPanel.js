/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timesheet grid panel
 */
Tine.Timetracker.TimesheetGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Timetracker.Model.Timesheet,
    
    // grid specific
    defaultSortInfo: {field: 'start_date', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timesheetBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        //this.actionToolbarItems = [{
        //    text: this.app.i18n._('Duplicate'),
        //    iconCls: 'action_duplicate'
        //}];
        
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Timesheet'),    field: 'query',    operators: ['contains']},
                new Tine.Timetracker.TimeAccountGridFilter(),
                {label: this.app.i18n._('Account'),      field: 'account_id', valueType: 'user'},
                {label: this.app.i18n._('Date'),         field: 'start_date', valueType: 'date'},
                {label: this.app.i18n._('Description'),  field: 'description' }
             ],
             defaultFilter: 'start_date',
             filters: []
        });
    },    
    
    /**
     * returns cm
     * @private
     * 
     * @todo    add more columns
     */
    getColumns: function(){
        return [{
            id: 'start_date',
            header: this.app.i18n._("Date"),
            width: 120,
            sortable: true,
            dataIndex: 'start_date',
            renderer: Tine.Tinebase.common.dateRenderer
        }, {
            id: 'start_time',
            hidden: true,
            header: this.app.i18n._("Start time"),
            width: 100,
            sortable: true,
            dataIndex: 'start_time',
            renderer: Tine.Tinebase.common.timeRenderer
        }, {
            id: 'timeaccount_id',
            header: this.app.i18n._("Time account"),
            width: 800,
            sortable: true,
            dataIndex: 'timeaccount_id',
            renderer: function(timeaccount) {
                return new Tine.Timetracker.Model.Timeaccount(timeaccount).getTitle();
            }
        },{
            id: 'account_id',
            header: this.app.i18n._("Account"),
            width: 350,
            sortable: true,
            dataIndex: 'account_id',
            renderer: Tine.Tinebase.common.usernameRenderer
        },{
            id: 'duration',
            header: this.app.i18n._("Duration"),
            width: 150,
            sortable: true,
            dataIndex: 'duration',
            renderer: Tine.Tinebase.common.minutesRenderer
        }];
    }  
});