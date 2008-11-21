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
 * Timetracker Edit Dialog
 */
Tine.Timetracker.TimesheetGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Timetracker.Timesheet,
    
    // grid specific
    defaultSortInfo: {field: 'creation_time', dir: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.JsonBackend;
        
        //this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
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
     * open timesheet edit dialog
     */
    onEditInNewWindow: function(_button, _event) {
        if (_button.actionType == 'edit') {
            var selectedRows = this.grid.getSelectionModel().getSelections();
            var record = selectedRows[0];
        } else {
        	var record = {};
        }
        //var containerId = Tine.Timetracker.registry.get('containerId'); 
        
        var popupWindow = Tine.Timetracker.TimesheetEditDialog.openWindow({
            record: record,
            //containerId: containerId,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.store.load({});
                }
            }
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