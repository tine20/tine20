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
 * Calendar grid view representing
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Calendar.GridView = Ext.extend(Ext.grid.GridPanel, {
    layout: 'fit',
    border: false,
    
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.cm = this.initCM();
        this.selModel = this.initSM();
        this.view = this.initVIEW();
        
        this.on('rowcontextmenu', function(grid, row, e) {
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
        }, this);
        
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
//                renderer: this.attendeeStatusRenderer.createDelegate(this)
            }]
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
            getPeriod: function() {
                return this.grid.getTopToolbar().periodPicker.getPeriod();
            },
            updatePeriod: function() {
                //this.getStore().load();
            },
            getTargetEvent: function(e) {
                var idx = this.findRowIndex(e.getTarget());
                
                return this.grid.getStore().getAt(idx);
            },
            getTargetDateTime: Ext.emptyFn,
            getSelectionModel: function() {
                return this.grid.getSelectionModel();
            }
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
    }
});