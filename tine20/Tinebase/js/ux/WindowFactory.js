/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext*/

Ext.ns('Ext.ux');

/**
 * @namespace   Ext.ux
 * @class       Ext.ux.WindowFactory
 * @contructor
 *
 * @cfg {String} windowType type of window {Ext|Browser|Air}
 */
Ext.ux.WindowFactory = function (config) {
    Ext.apply(this, config);
    
    switch (this.windowType) 
    {
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
    getBrowserWindow: function (config) {
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
    getExtWindow: function (c) {
        // add titleBar
        c.height = c.height + 20;
        // border width
        c.width = c.width + 16;
        
        //limit the window size
        c.height = Math.min(Ext.getBody().getBox().height, c.height);
        c.width = Math.min(Ext.getBody().getBox().width, c.width);
        
        c.layout = c.layout || 'fit';
        c.items = {
            layout: 'card',
            border: false,
            activeItem: 0,
            isWindowMainCardPanel: true,
            items: [this.getCenterPanel(c)]
        }
        
        // we can only handle one window yet
        c.modal = true;
        
        var win = new Ext.Window(c);
        c.items.items[0].window = win;
        
        // if initShow property is present and it is set to false don't show window, just return reference
        if (c.hasOwnProperty('initShow') && c.initShow === false) {
    return win;
    }
        
        win.show();
        return win;
    },
    
    /**
     * constructs window items from config properties
     */
    getCenterPanel: function (config) {
        var items;
        if (config.contentPanelConstructor) {
            config.contentPanelConstructorConfig = config.contentPanelConstructorConfig || {};

            /*
             * IE fix for listeners
             * 
             * In IE we have two problems when dealing with listeners across windows
             * 1. listeners (functions) are defined in the parent window. a typeof (some function from parent) returns object in IE
             *    the Ext.Observable code can't deal with this
             * 2. listeners get executed by fn.apply(scope, arguments). For some reason in IE this dosn't work with functions defined
             *    in an other window.
             *    
             * To work around this, we create new fresh listeners in the new window and proxy the event calls
             * 
             * TODO there is a bug in this function -> it does not work correctly if scope is defined in listeners object and is not the first entry ... :(
             */
            var ls = config.contentPanelConstructorConfig.listeners;
            if (ls /* && Ext.isIE */) {
                var lsProxy = {};
                for (var p in ls) {
                    if (ls.hasOwnProperty(p) && p !== 'scope') {
                        // NOTE apply dosn't work here for some strange reason, so we hope that there are not more than 5 params
                        if (ls[p].fn) {
lsProxy[p] = function () {
ls[p].fn.call(ls[p].scope, arguments[0], arguments[1], arguments[2], arguments[3], arguments[4]);
};
                        } else {
lsProxy[p] = function () {
ls[p].call(ls.scope, arguments[0], arguments[1], arguments[2], arguments[3], arguments[4]);
};
                        }
                    }
                }
                config.contentPanelConstructorConfig.listeners = lsProxy;
            }
            
            // place a reference to current window class in the itemConstructor.
            // this may be overwritten depending on concrete window implementation
            config.contentPanelConstructorConfig.window = config;
            
            // (re-) create auto apps on BrowserWindows
            if(this.windowType == 'Browser') {
                Tine.Tinebase.ApplicationStarter.init();
            }
            // find the constructor in this context
            var parts = config.contentPanelConstructor.split('.'),
            ref = window;
            
            for (var i = 0; i < parts.length; i += 1) {
                ref = ref[parts[i]];
            }
            
            // finally construct the content panel
            items = new ref(config.contentPanelConstructorConfig);
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
    getWindow: function (config) {
        var windowType = (config.modal) ? 'Ext' : this.windowType;
        
        // Names are only allowed to be alnum
        config.name = Ext.isString(config.name) ? config.name.replace(/[^a-zA-Z0-9_]/g, '') : config.name;
        
        if (! config.title && config.contentPanelConstructorConfig && config.contentPanelConstructorConfig.title) {
        config.title = config.contentPanelConstructorConfig.title;
            delete config.contentPanelConstructorConfig.title;
        }
        
        switch (windowType) 
        {
        case 'Browser' :
            return this.getBrowserWindow(config);
        case 'Ext' :
            return this.getExtWindow(config);
        case 'Air' :
            return this.getAirWindow(config);
        default :
            console.error('No such windowType: ' + this.windowType);
            break;
        }
    }
};
