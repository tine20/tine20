/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.Viewport
 * @extends Ext.Container
 * A specialized container representing the viewable application area (the browser viewport).  It automatically
 * sizes itself to the size of the browser viewport and manages window resizing.  The Viewport does not provide
 * scrolling, so layout elements within the Viewport should provide for scrolling if needed.
 * @constructor
 * Create a new Viewport
 * @param {Object} config The config object
 */
Ext.Viewport = Ext.extend(Ext.Container, {
    initComponent : function() {
        Ext.Viewport.superclass.initComponent.call(this);
        document.getElementsByTagName('html')[0].className += ' x-viewport';
        this.el = Ext.getBody();
        this.el.setHeight = Ext.emptyFn;
        this.el.setWidth = Ext.emptyFn;
        this.el.setSize = Ext.emptyFn;
        this.el.dom.scroll = 'no';
        this.allowDomMove = false;
        this.autoWidth = true;
        this.autoHeight = true;
        Ext.EventManager.onWindowResize(this.fireResize, this);
        this.renderTo = this.el;
    },

    fireResize : function(w, h){
        this.fireEvent('resize', this, w, h, w, h);
    }
});
Ext.reg('viewport', Ext.Viewport);