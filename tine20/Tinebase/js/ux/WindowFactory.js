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
        
        var centerPanel = this.getCenterPanel(c);
        
        c.layout = c.layout || 'fit';
        c.items = {
            layout: 'card',
            border: false,
            activeItem: 0,
            isWindowMainCardPanel: true,
            items: [centerPanel]
        }
        
        // we can only handle one window yet
        c.modal = true;
        
        var win = new Ext.Window(c);
        c.items.items[0].window = win;
        
        // relay events from center panel to window
        this.relayEvents(win, centerPanel, c.listeners);
        
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
     * Relay event from panel to window
     * 
     * @param {Ext.Window} win
     * @param {Ext.Panel} panel
     * @param {Object} listeners
     */
    relayEvents: function (win, panel, listeners) {
        if (! listeners) {
            return;
        }
        
        var events = [];
        for (var event in listeners) {
           if (event !== 'scope') {
               events.push(event);
           }
        }
        win.relayEvents.call(win, panel, events);
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