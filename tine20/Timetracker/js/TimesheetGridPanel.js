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
    defaultSortInfo: {field: 'creation_time', dir: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timesheetBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Timetracker.TimesheetGridPanel.superclass.initComponent.call(this);
        
        // remove selectionchange listener with actionUpdater
        // @todo remove that when we have containers here
        this.grid.getSelectionModel().purgeListeners();
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Timesheet'),    field: 'query',    operators: ['contains']}
                //{label: this.app.i18n._('Summary'), field: 'summary' }
             ],
             defaultFilter: 'query',
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
            id: 'description',
            header: this.app.i18n._("Description"),
            width: 300,
            sortable: true,
            dataIndex: 'description'
        },{
            id: 'account_id',
            header: this.app.i18n._("Account"),
            width: 100,
            sortable: true,
            dataIndex: 'account_id'
        },{
            id: 'contract_id',
            header: this.app.i18n._("Contract"),
            width: 100,
            sortable: true,
            dataIndex: 'contract_id'
        }];
    }  
});