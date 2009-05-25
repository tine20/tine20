/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Date.msSECOND = 1000;
Date.msMINUTE = 60 * Date.msSECOND;
Date.msHOUR   = 60 * Date.msMINUTE;
Date.msDAY    = 24 * Date.msHOUR;

Ext.ns('Tine.Calendar');

Tine.Calendar.CalendarPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Calendar.someView} view
     */
    view: null,
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,
    /**
     * @cfg {Bool} border
     */
    border: false,
    /**
     * @private
     */
    initComponent: function() {
        Tine.Calendar.CalendarPanel.superclass.initComponent.call(this);
        
        this.autoScroll = false;
        this.autoWidth = false;
        
        this.relayEvents(this.view, ['changeView', 'changePeriod']);
    },
    
    getView: function() {
        return this.view;
    },
    
    getStore: function() {
        return this.store;
    },
    
    onAddEvent: function(event) {
        this.setLoading(true);
        //console.log('A new event has been added -> call backend saveRecord');
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(createdEvent) {
                //console.log('Backend returned newly created event -> replace event in view');
                this.store.remove(event);
                this.store.add(createdEvent);
                this.setLoading(false);
            }
        });
    },
    
    onUpdateEvent: function(event) {
        this.setLoading(true);
        //console.log('A existing event has been updated -> call backend saveRecord');
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(updatedEvent) {
                //console.log('Backend returned updated event -> replace event in view');
                this.store.remove(event);
                this.store.add(updatedEvent);
                this.setLoading(false);
            }
        });
    },
    
    setLoading: function(bool) {
        var tbar = this.getTopToolbar();
        if (tbar && tbar.loading) {
            tbar.loading[bool ? 'disable' : 'enable']();
        }
    },
    
    /*
    onRemoveEvent: function(store, event, index) {
        console.log(event);
        console.log('A existing event has been deleted -> call backend delete'); 
    },
    */
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onRender.apply(this, arguments);
        
        var c = this.body;
        this.el.addClass('cal-panel');
        this.view.init(this);
        
        // quick add/update actions
        this.view.on('addEvent', this.onAddEvent, this);
        this.view.on('updateEvent', this.onUpdateEvent, this);
        
        //c.on("mousedown", this.onMouseDown, this);
        //c.on("click", this.onClick, this);
        //c.on("dblclick", this.onDblClick, this);
        c.on("contextmenu", this.onContextMenu, this);
        c.on("keydown", this.onKeyDown, this);

        this.relayEvents(c, ["mousedown","mouseup","mouseover","mouseout","keypress"]);
        
        this.view.render();
    },
    
    /**
     * @private
     */
    afterRender : function(){
        Tine.Calendar.CalendarPanel.superclass.afterRender.call(this);
        this.view.layout();
        this.view.afterRender();
        
        this.viewReady = true;
    },
    
    /**
     * @private
     */
    onResize: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onResize.apply(this, arguments);
        if(this.viewReady){
            this.view.layout();
        }
    },
    
    /**
     * @private
     */
    processEvent : function(name, e){
        this.fireEvent(name, e);
        var v = this.view;
        
        var date = v.getTargetDateTime(e);
        if (! date) {
            // fetch event id;
            var event = v.getTargetEvent(e);
        }
        
        /*
        if (name == 'dblclick') {
            if (date) {
                // add new event
                this.store.add(new Tine.Calendar.Event({
                    id: Ext.id(),
                    dtstart: date,
                    dtend: date.add(Date.HOUR, date.is_all_day_event ? 24 : 1),
                    is_all_day_event: date.is_all_day_event
                }));
                e.preventDefault();
            }
        }
        */
        
        if (name == 'click') {
            if (event) {
                //this.view.setActiveEvent(event);
            }
        }
        
        /*
        if (name == 'mousedown' && date) {
            this.store.add(new Tine.Calendar.Event({
                id: Ext.id(),
                dtstart: date, 
                dtend: date.add(Date.MINUTE, 15),
                is_all_day_event: date.is_all_day_event
            }));
        }
        */
        
        /*
            var row = v.findRowIndex(t);
            var cell = v.findCellIndex(t);
            if(row !== false){
                this.fireEvent("row" + name, this, row, e);
                if(cell !== false){
                    this.fireEvent("cell" + name, this, row, cell, e);
                }
            }
        */
            
    },
    
    /**
     * @private
     */
    onClick : function(e){
        this.processEvent("click", e);
    },

    /**
     * @private
     */
    onMouseDown : function(e){
        this.processEvent("mousedown", e);
    },

    /**
     * @private
     */
    onContextMenu : function(e, t){
        this.processEvent("contextmenu", e);
    },

    /**
     * @private
     */
    onDblClick : function(e){
        this.processEvent("dblclick", e);
    },
    
    /**
     * @private
     */
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    }
    
});