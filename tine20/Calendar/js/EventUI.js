/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
 
Ext.ns('Tine.Calendar');

Tine.Calendar.EventUI = function(event) {
    this.event = event;
    this.domIds = [];
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.init();
};

Tine.Calendar.EventUI.prototype = {
    addClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.addClass(cls);
        });
    },
    
    blur: function() {
        Ext.each(this.getEls(), function(el){
            el.blur();
        });
    },
    
    clearDirty: function() {
        Ext.each(this.getEls(), function(el) {
            el.setOpacity(1, 1);
        });
    },
    
    focus: function() {
        Ext.each(this.getEls(), function(el){
            el.focus();
        });
    },
    
    /**
     * returns events dom
     * @return {Array} of Ext.Element
     */
    getEls: function() {
        var domEls = [];
        for (var i=0; i < this.domIds.length; i++) {
            var el = Ext.get(this.domIds[i]);
            if (el) {
                domEls.push(el);
            }
        }
        return domEls;
    },
    
    init: function() {
        // shortcut
        //this.colMgr = Tine.Calendar.colorMgr;
    },
    
    markDirty: function() {
        Ext.each(this.getEls(), function(el) {
            el.setOpacity(0.5, 1);
        });
    },
    
    markOutOfFilter: function() {
        Ext.each(this.getEls(), function(el) {
            el.setOpacity(0.5, 0);
            el.setStyle({'background-color': '#aaa', 'border-color': '#888'});
            Ext.DomHelper.applyStyles(el.dom.firstChild, {'background-color': '#888'});
            if (el.dom.firstChild.hasOwnProperty('firstChild') && el.dom.firstChild.firstChild) {
                Ext.DomHelper.applyStyles(el.dom.firstChild.firstChild, {'background-color': '#888'});
            }
        });
    },
    
    onSelectedChange: function(state){
        if(state){
            //this.focus();
            this.addClass('cal-event-active');
            this.setStyle({'z-index': 1000});
            
        }else{
            //this.blur();
            this.removeClass('cal-event-active');
            this.setStyle({'z-index': 100});
        }
    },
    
    /**
     * removes a event from the dom
     */
    remove: function() {
        var eventEls = this.getEls();
        for (var i=0; i<eventEls.length; i++) {
            if (eventEls[i] && typeof eventEls[i].remove == 'function') {
                eventEls[i].remove();
            }
        }
        if (this.resizeable) {
            this.resizeable.destroy();
            this.resizeable = null;
        }
        this.domIds = [];
    },
    
    removeClass: function(cls) {
        Ext.each(this.getEls(), function(el){
            el.removeClass(cls);
        });
    },
    
    render: function() {
        // do nothing
    },
    
    setOpacity: function(v) {
        Ext.each(this.getEls(), function(el){
            el.setStyle(v);
        });
    },
    
    setStyle: function(style) {
        Ext.each(this.getEls(), function(el){
            el.setStyle(style);
        });
    }
    
};

