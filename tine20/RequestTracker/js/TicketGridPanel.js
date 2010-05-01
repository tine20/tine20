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
 
 Tine.RequestTracker.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
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
        this.plugins.push(this.app.getMainScreen().getContainerTreePanel().getFilterPlugin());
        
        this.initDetailsPanel();
        
        Tine.RequestTracker.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Ticket'), field: 'query', operators: ['contains']},
                {label: this.app.i18n._('Ticket ID'), field: 'id', valueType: 'number'},
                {label: this.app.i18n._('Queue'), field: 'queue'},
                {label: this.app.i18n._('Subject'), field: 'subject', operators: ['contains', 'equals', 'not']},
                {label: this.app.i18n._('Requestors'), field: 'requestor', operators: ['contains', 'equals', 'not']},
                {label: this.app.i18n._('Owner'), field: 'owner', operators: ['contains', 'equals', 'not']},
                new Tine.RequestTracker.TicketGridStatusFilter({})
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
            ],
            defaultFilter: 'query',
            filters: [
                {field: 'status', operator: 'greater', value: 'open'},
                {field: 'query', operator: 'contains', value: ''}
            ],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },    
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [
            { id: 'id', header: this.app.i18n._("Ticket ID"), width: 40, sortable: true, dataIndex: 'id'
        },{
            id: 'Queue',
            header: this.app.i18n._("Queue"),
            width: 50,
            sortable: true,
            dataIndex: 'Queue'
        },{
            id: 'Subject',
            header: this.app.i18n._("Subject"),
            width: 250,
            sortable: true,
            dataIndex: 'Subject'
        },{
            id: 'Requestors',
            header: this.app.i18n._("Requestors"),
            width: 100,
            sortable: true,
            dataIndex: 'Requestors'
        },{
            id: 'Owner',
            header: this.app.i18n._("Owner"),
            width: 50,
            sortable: true,
            dataIndex: 'Owner'
        },{
            id: 'Status',
            header: this.app.i18n._("Status"),
            width: 40,
            sortable: true,
            dataIndex: 'Status'
        },{
            id: 'FinalPriority',
            header: this.app.i18n._("Final Priority"),
            width: 60,
            sortable: true,
            dataIndex: 'FinalPriority'
        },{
            id: 'Due',
            header: this.app.i18n._("Due"),
            width: 80,
            sortable: true,
            dataIndex: 'Due',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        }];
    },
    
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Tinebase.widgets.grid.DetailsPanel({
            defaultHeight: 375,
            gridpanel: this,
            currentId: null,
            
            updateDetails: function(record, body) {
                if (record.id !== this.currentId) {
                    this.currentId = record.id;
                    Tine.RequestTracker.ticketBackend.loadRecord(record, {
                        scope: this,
                        success: function(ticket) {
                            this.tpl.overwrite(body, ticket.data);
                            this.getEl().down('div').down('div').scrollTo('top', 0, false);
                            this.getLoadMask().hide();
                        }
                    });
                    this.getLoadMask().show();
                }
            },
            
            tpl: new Ext.XTemplate(
                '<div class="RequestTracker-History">',
                    '<tpl for="History">',
                        '<div class="RequestTracker-History-Item RequestTracker-History-{[this.encode(values.Type)]}">',
                            '<div class="RequestTracker-History-Created">{[this.encode(values.Created)]}</div>',
                            //'<div class="RequestTracker-History-Creator">{[this.encode(values.Creator)]}</div>',
                            '<div class="RequestTracker-History-Description">{[this.encode(values.Description)]}</div>',
                            '<div class="RequestTracker-History-Content">{[this.encode(values.Content)]}</div>',
                        '</div>',
                     '</tpl>',
                '</div>',{
                encode: function(value, type, prefix) {
                    if (value) {
                        if (type) {
                            switch (type) {
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 150);
                                    break;
                                default:
                                    value += type;
                            }                           
                        }
                        
                        var encoded = Ext.util.Format.htmlEncode(value);
                        encoded = Ext.util.Format.nl2br(encoded);
                        
                        return encoded;
                    } else {
                        return '';
                    }
                }
            })
        });
    }
});
