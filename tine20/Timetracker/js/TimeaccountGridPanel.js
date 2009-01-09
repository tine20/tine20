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
 * Timeaccount grid panel
 */
Tine.Timetracker.TimeaccountGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Timetracker.Model.Timeaccount,
    
    // grid specific
    defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'title'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timeaccountBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        this.plugins.push(this.action_showClosedToggle, this.filterToolbar);        
        
        Tine.Timetracker.TimeaccountGridPanel.superclass.initComponent.call(this);
        
        this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'));
        this.action_editInNewWindow.requiredGrant = 'editGrant';
        
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Timeaccount'),    field: 'query',       operators: ['contains']},
                {label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']}
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
            id: 'number',
            header: this.app.i18n._("Number"),
            width: 100,
            sortable: true,
            dataIndex: 'number'
        },{
            id: 'title',
            header: this.app.i18n._("Title"),
            width: 400,
            sortable: true,
            dataIndex: 'title'
        },{
            id: 'budget',
            header: this.app.i18n._("Budget"),
            width: 100,
            sortable: true,
            dataIndex: 'budget'
        }];
    },
    
    /**
     * return additional tb items
     */
    getToolbarItems: function(){
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        
        return [
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
        ];
    }    
});
