/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux');

/**
 * @class Ext.ux.WindowFactory
 * @contructor
 * 
 */
/**
 * @cfg {String} windowType type of window {Ext|Browser|Air}
 */
Ext.ux.WindowFactory = function(config) {
    Ext.apply(this, config);
    
    switch (this.windowType) {
        case 'Browser' :
            this.windowClass = Ext.ux.PopupWindow;
            this.windowManager = Ext.ux.PopupWindowMgr;
            break;
        case 'Ext' :
            this.windowClass = Ext.Window;
            this.windowManager = Ext.WindowMgr;
            break;
        case 'Air' :
            this.windowClass = Ext.air.NativeWindow;
            this.windowManager = Ext.air.NativeWindowManager;
            break;
        default :
            console.error('No such windowType: ' + this.windowType);
            break;
    }
    //Ext.ux.WindowFactory.superclass.constructor.call(this);
};

/**
 * @class Ext.ux.WindowFactory
 */
Ext.ux.WindowFactory.prototype = {
    
    /**
     * @private
     */
    windowClass: null,
    /**
     * @private
     */
    windowManager: null,
    
    /**
     * @rivate
     */
    getBrowserWindow: function(config) {
        var win = Ext.ux.PopupWindowMgr.get(config.name);
        
        if (! win) {
            win = new this.windowClass(config);
        }
        
        Ext.ux.PopupWindowMgr.bringToFront(win);
        return win;
    },
    
    /**
     * @private
     */
    getExtWindow: function(c) {
        // add titleBar
        c.height = c.height + 20;
        
        c.items = this.getWindowItems(c);
        
        var win = new Ext.Window(c);
        win.show();
        return win;
    },
    
    /**
     * constructs window items from config properties
     */
    getWindowItems: function(config) {
        var items;
        if (config.itemsConstructor) {
            var parts = config.itemsConstructor.split('.');
            var ref = window;
            for (var i=0; i<parts.length; i++) {
                ref = ref[parts[i]];
            }
            var items = new ref(config.itemsConstructorConfig);
        } else {
            items = config.items ? config.items : {};
        }
        
        return items;
    },
    
    /**
     * getWindow
     * 
     * creates new window if not already exists.
     * brings window to front
     */
    getWindow: function(config) {
        switch (this.windowType) {
            case 'Browser' :
                return this.getBrowserWindow(config);
                break;
            case 'Ext' :
                return this.getExtWindow(config);
                break;
            case 'Air' :
                return this.getAirWindow(config);
                break;
            default :
                console.error('No such windowType: ' + this.windowType);
                break;
        }
    }
};