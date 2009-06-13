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
Date.msWEEK   =  7 * Date.msDAY;

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
        
        this.selModel = this.selModel || new Tine.Calendar.EventSelectionModel();
        
        this.autoScroll = false;
        this.autoWidth = false;
        
        this.relayEvents(this.view, ['changeView', 'changePeriod']);
        
        this.store.on('beforeload', this.onBeforeLoad, this);
    },
    
    getSelectionModel: function() {
        return this.selModel;
    },
    
    getStore: function() {
        return this.store;
    },
    
    getView: function() {
        return this.view;
    },
    
    onAddEvent: function(event) {
        this.setLoading(true);
        
        // remove temporary id
        if (event.get('id').match(/new/)) {
            event.set('id', '');
        }
        
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(createdEvent) {
                this.store.remove(event);
                this.store.add(createdEvent);
                this.setLoading(false);
                this.view.getSelectionModel().select(createdEvent);
            }
        });
    },
    
    onBeforeLoad: function(store, options) {
        options.params = options.params || {};
        
        var filter = options.params.filter ? options.params.filter : [];
        filter.push({field: 'period', operator: 'within', value: this.getView().getPeriod() });
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
                this.view.getSelectionModel().select(updatedEvent);
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
        
        this.view.on("click", this.onClick, this);
        this.view.on("dblclick", this.onDblClick, this);
        this.view.on("contextmenu", this.onContextMenu, this);
        
        //c.on("keydown", this.onKeyDown, this);
        //this.relayEvents(c, ["keypress"]);
        
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
    processEvent : function(name, event){
        console.log('Tine.Calendar.CalendarPanel::processEvent "' + name + '" on envent: ' + event.id );
    },
    
    /**
     * @private
     */
    onClick : function(event, e){
        this.processEvent("click", event);
    },

    /**
     * @private
     */
    onContextMenu : function(event, e){
        this.processEvent("contextmenu", event);
    },

    /**
     * @private
     */
    onDblClick : function(event, e){
        this.processEvent("dblclick", event);
    }
    
    /**
     * @private
     *
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    }
    */
    
});