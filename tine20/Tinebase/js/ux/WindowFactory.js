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
     * getWindow
     * 
     * creates new window if not already exists.
     * brings window to front
     */
    getWindow: function(config) {
        var window = this.windowManager.get(config.name);
        if (! window) {
            window = new this.windowClass(config);
        }
        
        this.windowManager.bringToFront(window);
        return window;
    }
};