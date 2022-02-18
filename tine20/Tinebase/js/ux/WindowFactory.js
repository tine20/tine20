/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext*/

Ext.ns('Ext.ux');

/**
 * @namespace   Ext.ux
 * @class       Ext.ux.WindowFactory
 * @contructor
 *
 * @cfg {String} windowType type of window {Ext|Browser}
 */
Ext.ux.WindowFactory = function (config) {
    let me = this;
    Ext.apply(this, config);

    switch (this.windowType) {
        case 'Browser' :
            this.windowClass = Ext.ux.PopupWindow;
            this.windowManager = Ext.ux.PopupWindowMgr;
            break;

        case 'Ext' :
            this.windowClass = Ext.extend(Ext.Window, {
                confirmLeavSite: me.confirmLeavSite,

                // closing interception analog to browser windows
                close : function(force){
                    if(force || this.fireEvent('beforeclose', this) !== false){
                        if(this.hidden){
                            this.doClose();
                        }else{
                            this.hide(null, this.doClose, this);
                        }
                    } else {
                        this.confirmLeavSite(this);
                    }
                },
            });
            this.windowManager = Ext.WindowMgr;
            break;
        default :
            console.error('No such windowType: ' + this.windowType);
            break;
    }
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
            win.confirmLeavSite = this.confirmLeavSite;
        }
        
        Ext.ux.PopupWindowMgr.bringToFront(win);
        return win;
    },
    
    /**
     * @private
     */
    getExtWindow: function (c) {
        var win = Ext.WindowMgr.get(c.name);
        var winConstructor = c.modal ? Ext.Window : this.windowClass;

        if (! win) {
            c.id = c.name;

            // add titleBar
            c.height = c.height + 20;
            // border width
            c.width = c.width + 16;

            // save normal size
            c.normSize = { width: c.width, height: c.height };

            //limit the window size
            c.height = Math.min(Ext.getBody().getBox().height, c.height);
            c.width = Math.min(Ext.getBody().getBox().width, c.width);

            c.layout = c.layout || 'fit';
            const cp = this.getCenterPanel(c).then((cp) => {
                const cardPanel = win.items.get(0)
                cp.window = win;
                cardPanel.items.add(cp);
                cardPanel.layout.setActiveItem(0);
                cp.doLayout();
            });
            c.items = {
                layout: 'card',
                border: false,
                activeItem: 0,
                isWindowMainCardPanel: true,
                items: []
            };

            // NOTE: is this still true ?? -> we can only handle one window yet
            c.modal = true;

            win = new winConstructor(c);
            if (cp.onWindowInject) {
                cp.onWindowInject.call(cp, win);
            }
        }
        
        // if initShow property is present and it is set to false don't show window, just return reference
        if (! c.hasOwnProperty('initShow') || c.initShow !== false) {
            win.show();
        }
        
        return win;
    },
    
    /**
     * constructs window items from config properties
     */
     getCenterPanel: async function (config) {
        var items;

        if (config.contentPanelConstructor) {
            config.contentPanelConstructorConfig = config.contentPanelConstructorConfig || {};

            // place a reference to current window class in the itemConstructor.
            // this may be overwritten depending on concrete window implementation
            config.contentPanelConstructorConfig.window = config;
            
            // find the constructor in this context
            var parts = config.contentPanelConstructor.split('.'),
            ref = window;
            
            for (var i = 0; i < parts.length; i += 1) {
                ref = ref[parts[i]];
            }

            if (config.contentPanelConstructorConfig.contentPanelConstructorInterceptor) {
                await config.contentPanelConstructorConfig.contentPanelConstructorInterceptor(config.contentPanelConstructorConfig, window);
            }
            
            // finally construct the content panel
            Tine.log.info('WindowFactory::getCenterPanel - construct content panel');
            items = new ref(config.contentPanelConstructorConfig);

            // remove x-window reference
            config.contentPanelConstructorConfig.listeners = null;
        } else {
            items = new Ext.Container({layout: 'fit', border: false, items : config.items ? config.items : {}});
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
        var windowType = config.windowType || ((config.modal) ? 'Ext' : this.windowType);
        
        // Names are only allowed to be alnum
        config.name = Ext.isString(config.name) ? config.name.replace(/[^a-zA-Z0-9_]/g, '') : config.name;
        
        if (! config.title && config.contentPanelConstructorConfig && config.contentPanelConstructorConfig.title) {
        config.title = config.contentPanelConstructorConfig.title;
            delete config.contentPanelConstructorConfig.title;
        }
        
        switch (windowType) {
        case 'Browser' :
            try {
                return this.getBrowserWindow(config);
            } catch (e) {
                // fallthrough to Ext Window
            }
        case 'Ext' :
            return this.getExtWindow(config);
        default :
            console.error('No such windowType: ' + this.windowType);
            break;
        }
    },

    confirmLeavSite: function(scope) {
        let win = scope.popup ? scope.popup : window;
        win.Ext.MessageBox.show({
            title: i18n._('Leave site?'),
            msg: i18n._('Changes you made may not be saved.'),
            buttons: Ext.MessageBox.OKCANCEL,
            fn: function(buttonId) {
                if (buttonId === 'ok') {
                    scope.close(true);
                }
            },
            icon: Ext.MessageBox.WARNING
        });
    }
};
