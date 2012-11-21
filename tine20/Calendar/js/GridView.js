/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.GridView
 * @extends     Ext.grid.GridPanel
 * 
 * Calendar grid view representing
 * 
 * @TODO generalize renderers and role out to displaypanel/printing etc.
 * @TODO add organiser and own status
 * 
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.GridView = Ext.extend(Ext.grid.GridPanel, {
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
    defaultSortInfo: {field: 'dtstart', direction: 'ASC'},
    
    layout: 'fit',
    border: false,
    stateful: true,
    stateId: 'Calendar-Event-GridPanel-Grid',
    enableDragDrop: true,
    ddGroup: 'cal-event',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.store.sort(this.defaultSortInfo.field, this.defaultSortInfo.direction);
        
        this.cm = this.initCM();
        this.selModel = this.initSM();
        this.view = this.initVIEW();
        
        this.on('rowcontextmenu', function(grid, row, e) {
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
        }, this);
        
        this.on('rowclick', Tine.widgets.grid.GridPanel.prototype.onRowClick, this);
        
        // activate grid header menu for column selection
        this.plugins = this.plugins ? this.plugins : [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        this.enableHdMenu = false;
        
        Tine.Calendar.GridView.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    initCM: function(){
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [{
                id: 'container_id',
                header: this.recordClass.getContainerName(),
                width: 150,
                renderer: Tine.widgets.grid.RendererManager.get('Calendar', 'Event', 'container_id')
            }, {
                id: 'class',
                header: this.app.i18n._("Private"),
                width: 50,
                dataIndex: 'class',
                renderer: function(transp) {
                    return Tine.Tinebase.common.booleanRenderer(transp == 'PRIVATE');
                }
            }, {
                id: 'tags',
                header: this.app.i18n._("Tags"),
                width: 50,
                dataIndex: 'tags',
                renderer: Tine.Tinebase.common.tagsRenderer
                
            }, {
                id: 'dtstart',
                header: this.app.i18n._("Start Time"),
                width: 120,
                dataIndex: 'dtstart',
                renderer: Tine.Tinebase.common.dateTimeRenderer
            }, {
                id: 'dtend',
                header: this.app.i18n._("End Time"),
                width: 120,
                dataIndex: 'dtend',
                renderer: Tine.Tinebase.common.dateTimeRenderer
            }, {
                id: 'is_all_day_event',
                header: this.app.i18n._("whole day"),
                width: 50,
                dataIndex: 'is_all_day_event',
                renderer: Tine.Tinebase.common.booleanRenderer
            }, {
                id: 'transp',
                header: this.app.i18n._("Blocking"),
                width: 50,
                dataIndex: 'transp',
                renderer: function(transp) {
                    return Tine.Tinebase.common.booleanRenderer(transp == 'OPAQUE');
                }
            }, {
                id: 'status',
                header: this.app.i18n._("Tentative"),
                width: 50,
                dataIndex: 'status',
                renderer: function(transp) {
                    return Tine.Tinebase.common.booleanRenderer(transp == 'TENTATIVE');
                }
            }, {
                id: 'summary',
                header: this.app.i18n._("Summary"),
                width: 200,
                dataIndex: 'summary'
            }, {
                id: 'location',
                header: this.app.i18n._("Location"),
                width: 200,
                hidden: true,
                dataIndex: 'location'
            }, {
                id: 'organizer',
                header: this.app.i18n._("Organizer"),
                width: 200,
                hidden: true,
                dataIndex: 'organizer',
                renderer: Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderUserName
            }, {
                id: 'description',
                header: this.app.i18n._("Description"),
                width: 200,
                hidden: true,
                dataIndex: 'description',
                renderer: function(description, metaData, record) {
                    metaData.attr = 'ext:qtip="' + Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(Ext.util.Format.htmlEncode(description))) + '"';
                    return Ext.util.Format.htmlEncode(description);
                }
            }/*, {
                id: 'attendee_status',
                header: this.app.i18n._("Status"),
                width: 100,
                sortable: true,
                dataIndex: 'attendee',
//                renderer: this.attendeeStatusRenderer.createDelegate(this)
            }*/]
        });
    },
    
    initSM: function() {
        return new Ext.grid.RowSelectionModel({
            allowMultiple: true,
            getSelectedEvents: function() {
                return this.getSelections();
            },
            /**
             * Select an event.
             * 
             * @param {Tine.Calendar.Model.Event} event The event to select
             * @param {EventObject} e (optional) An event associated with the selection
             * @param {Boolean} keepExisting True to retain existing selections
             * @return {Tine.Calendar.Model.Event} The selected event
             */
            select : function(event, e, keepExisting){
                if (! event || ! event.ui) {
                    return event;
                }
                
                var idx = this.grid.getStore.indexOf(event);
                
                this.selectRow(idx, keepExisting);
                return event;
            }
        });
    },
    
    initVIEW: function() {
        return new Ext.grid.GridView(Ext.apply({}, this.viewConfig, {
            grid: this,
            forceFit: true,
            
            getPeriod: function() {
                return this.grid.getTopToolbar().periodPicker.getPeriod();
            },
            updatePeriod: function(period) {
                this.startDate = period.from;
                var tbar = this.grid.getTopToolbar();
                if (tbar) {
                    tbar.periodPicker.update(this.startDate);
                    this.startDate = tbar.periodPicker.getPeriod().from;
                }
            },
            getTargetEvent: function(e) {
                var idx = this.findRowIndex(e.getTarget());
                
                return this.grid.getStore().getAt(idx);
            },
            getTargetDateTime: Ext.emptyFn,
            getSelectionModel: function() {
                return this.grid.getSelectionModel();
            },
            print: function() {
                Ext.ux.Printer.print(this.grid);
            },
            getRowClass: this.getViewRowClass
        }));
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
    },
    
    /**
     * Return CSS class to apply to rows depending upon due status
     * 
     * @param {Tine.Tasks.Task} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        return 'cal-status-' + record.get('status');
    }
});
