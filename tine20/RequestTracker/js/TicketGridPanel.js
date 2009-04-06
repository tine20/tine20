/*
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Phone.css 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 */
 
 Ext.ns('Tine.RequestTracker');
 
 Tine.RequestTracker.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.RequestTracker.Model.Ticket,
    recordProxy: Tine.RequestTracker.ticketBackend,
    defaultSortInfo: {field: 'LastUpdated', direction: 'DESC'},
    evalGrants: false,
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'Subject'
    },
    
    initComponent: function() {
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.RequestTracker.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                //{label: this.app.i18n._('Ticket'), field: 'query', operators: ['contains']},
                {label: this.app.i18n._('Owner'), field: 'owner'},
                {label: this.app.i18n._('Queue'), field: 'queue'},
                {label: this.app.i18n._('Status'), field: 'status'},
                {label: this.app.i18n._('Ticket ID'), field: 'id', valueType: 'number'}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
             ],
             defaultFilter: 'id',
             filters: [
                {field: 'queue', operator: 'equals', value: 'service'},
                {field: 'status', operator: 'equals', value: 'open'}
             ]
        });
    },    
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [
            { id: 'id', header: this.app.i18n._("Ticket ID"), width: 70, sortable: true, dataIndex: 'id'
        },{
            id: 'Subject',
            header: this.app.i18n._("Subject"),
            width: 350,
            sortable: true,
            dataIndex: 'Subject'
        },{
            id: 'Status',
            header: this.app.i18n._("Status"),
            width: 70,
            sortable: true,
            dataIndex: 'Status'
        },{
            id: 'FinalPriority',
            header: this.app.i18n._("Final Priority"),
            width: 70,
            sortable: true,
            dataIndex: 'FinalPriority'
        },{
            id: 'Due',
            header: this.app.i18n._("Due"),
            width: 100,
            sortable: true,
            dataIndex: 'Due',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        }];
    }
});
