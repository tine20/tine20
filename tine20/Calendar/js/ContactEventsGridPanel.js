/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Calendar');

/**
 * show all events, given contact record is attender of
 * 
 * NOTE: This Grid does not show recuings yet!
 *       If we want to display recurings we need to add a period filter.
 *       We might be able to do so after we have a generic calendar list widget
 *       with a generic period paging tbar
 *       
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.ContactEventsGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Calendar.ContactEventsGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: Tine.Calendar.Model.Event,
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: Tine.Calendar.backend,
    /**
     * grid specific
     * @private
     */
    stateful: false,
    defaultSortInfo: {field: 'dtstart', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'summary'
    },
    
    /**
     * @cfg {Bool} hasDetailsPanel 
     */
    hasDetailsPanel: true,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.title = this.app.i18n._('Events');
        this.record = this.editDialog.record;

        this.gridConfig.cm = this.getColumnModel();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Calendar.ContactEventsGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * perform the initial load of grid data
     */
    initialLoad: function() {
        this.store.load.defer(10, this.store, [
            typeof this.autoLoad == 'object' ?
                this.autoLoad : undefined]);
    },
//    
//    /**
//     * called before store queries for data
//     */
//    onStoreBeforeload: function(store, options) {
//        
//        Tine.Calendar.ContactEventsGridPanel.superclass.onStoreBeforeload.apply(this, arguments);
//        if (! this.record.id) return false;
//        
//        options.params.filter.push({field: 'attender', operator: 'equals', value: {user_type: 'user', user_id: this.record.id}});
//    },
    
    /**
     * initialises filter toolbar
     *  @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            recordClass: this.recordClass,
            neverAllowSaving: true,
            filterModels: Tine.Calendar.Model.Event.getFilterModel(),
            defaultFilter: 'query',
            filters: [
                {field: 'query', operator: 'contains', value: ''},
                {field: 'attender', operator: 'in', value: [{user_type: 'user', user_id: this.record.id ? this.record.data : ''}]}
            ]
        });
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [{
                id: 'summary',
                header: this.app.i18n._("Summary"),
                width: 350,
                sortable: true,
                dataIndex: 'summary'
            } ,{
                id: 'dtstart',
                header: this.app.i18n._("Start Time"),
                width: 150,
                sortable: true,
                dataIndex: 'dtstart',
                renderer: Tine.Tinebase.common.dateTimeRenderer
            },{
                id: 'attendee_status',
                header: this.app.i18n._("Status"),
                width: 100,
                sortable: true,
                dataIndex: 'attendee',
                renderer: this.attendeeStatusRenderer.createDelegate(this)
            }]
        });
    },
    
    attendeeStatusRenderer: function(attendee) {
        var store = new Tine.Calendar.Model.Attender.getAttendeeStore(attendee),
        attender = null;
        
        store.each(function(a) {
            if (a.getUserId() == this.record.id && a.get('user_type') == 'user') {
                attender = a;
                return false;
            }
        }, this);
        
        if (attender) {
            return Tine.Tinebase.widgets.keyfield.Renderer.render('Calendar', 'attendeeStatus', attender.get('status'));
        }
    }
});

