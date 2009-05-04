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
    view: null,
    border: false,
    
    initComponent: function() {
        Tine.Calendar.CalendarPanel.superclass.initComponent.call(this);
        
        this.autoScroll = false;
        this.autoWidth = false;
        
        /*
        this.addEvents({
            validatedrop:true,
            beforedragover:true,
            dragover:true,
            beforedrop:true,
            drop:true
        });
        */
    },
    
    initEvents2 : function(){
        Tine.Calendar.CalendarPanel.superclass.initEvents.call(this);
        //this.dd = new Ext.ux.Portal.DropZone(this, this.dropConfig);
        //Ext.dd.ScrollManager.register(this.body);
        this.dd = new Ext.dd.DropZone(this.view.dayCols[1], {
            getTargetFromEvent: function(e) {
                console.log(e);
            },
            onDrag: function() {
                console.log('onDrag');
            },
            notifyOver : function(dd, e, data) {
                console.log('notifyOver')
            },
            notifyOut : function() {
                console.log('notifyOut');
                //delete this.grid;
            },
            notifyDrop : function(dd, e, data) {
                console.log('notifyDrop');
            }
                //delete this.grid;
        });
    },
    
    onRender: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onRender.apply(this, arguments);
        
        var c = this.body;
        this.el.addClass('cal-panel');
        this.view.init(this);

        c.on("mousedown", this.onMouseDown, this);
        c.on("click", this.onClick, this);
        c.on("dblclick", this.onDblClick, this);
        c.on("contextmenu", this.onContextMenu, this);
        c.on("keydown", this.onKeyDown, this);

        this.relayEvents(c, ["mousedown","mouseup","mouseover","mouseout","keypress"]);
        
        this.view.render();
    },
    
    afterRender : function(){
        Tine.Calendar.CalendarPanel.superclass.afterRender.call(this);
        this.view.layout();
        this.view.afterRender();
        
        this.viewReady = true;
    },
    
    onResize: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onResize.apply(this, arguments);
        if(this.viewReady){
            this.view.layout();
        }
    },
    
    // private
    processEvent : function(name, e){
        this.fireEvent(name, e);
        var t = e.getTarget();
        var v = this.view;
        
        var date = v.getTargetDateTime(t);
        
        if (name == 'dblclick') {
            console.log('we should create a new event on dblclick ;-)');
        }
        
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
    
    // private
    onClick : function(e){
        this.processEvent("click", e);
    },

    // private
    onMouseDown : function(e){
        this.processEvent("mousedown", e);
    },

    // private
    onContextMenu : function(e, t){
        this.processEvent("contextmenu", e);
    },

    // private
    onDblClick : function(e){
        this.processEvent("dblclick", e);
    },
    
    // private
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    }
});