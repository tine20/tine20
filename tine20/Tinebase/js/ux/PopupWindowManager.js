/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 

/**
 * @class Ext.ux.PopupWindowGroup
 * An object that represents a group of {@link Ext.ux.PopupWindow}
 * @constructor
 */
Ext.ux.PopupWindowGroup = function(){
    var list = {};
    var accessList = [];
    var front = null;

    // private
    var cleanupClosedWindows = function() {
        var doc;
        for(var id in list){
            try {
                doc = list[id].popup.document;
                if (Ext.isChrome && ! doc.defaultView) {
                    doc = false;
                }
            } catch(e)  {
                doc = false;
            }
            
            if (! doc) {
                accessList.remove(list[id]);
                delete list[id];
                
            }
        }
    };
    /*
    // private
    var sortWindows = function(d1, d2){
        return (!d1._lastAccess || d1._lastAccess < d2._lastAccess) ? -1 : 1;
    };

    // private
    var orderWindows = function(){
        var a = accessList, len = a.length;
        if(len > 0){
            a.sort(sortWindows);
            var seed = a[0].manager.zseed;
            for(var i = 0; i < len; i++){
                var win = a[i];
                if(win && !win.hidden){
                    win.setZIndex(seed + (i*10));
                }
            }
        }
        activateLast();
    };

    // private
    var setActiveWin = function(win){
        if(win != front){
            if(front){
                front.setActive(false);
            }
            front = win;
            if(win){
                win.setActive(true);
            }
        }
    };

    // private
    var activateLast = function(){
        for(var i = accessList.length-1; i >=0; --i) {
            if(!accessList[i].hidden){
                setActiveWin(accessList[i]);
                return;
            }
        }
        // none to activate
        setActiveWin(null);
    };
*/
    return {

        register : function(win){
            cleanupClosedWindows();
            if (! win.popup) {
                console.error('pure window instead of Ext.ux.PopupWindow got registered');
            }
            list[win.name] = win;
            accessList.push(win);
            //win.on('hide', activateLast);
        },

        unregister : function(win){
            delete list[win.name];
            //win.un('hide', activateLast);
            accessList.remove(win);
        },

        /**
         * Gets a registered window by name.
         * @param {String/Object} name The name of the window or a browser window object
         * @return {Ext.ux.PopupWindow}
         */
        get : function(name){
            cleanupClosedWindows();
            name = typeof name == "object" ? name.name : name;
            return list[name];
        },

        /**
         * Brings the specified window to the front of any other active windows.
         * @param {String/Object} win The id of the window or a {@link Ext.Window} instance
         * @return {Boolean} True if the dialog was brought to the front, else false
         * if it was already in front
         */
        bringToFront : function(win){
            win = this.get(win);
            if(win != front){
                win._lastAccess = new Date().getTime();
                win.popup.focus();
                //orderWindows();
                return true;
            }
            return false;
        },
        
        /**
         * Sends the specified window to the back of other active windows.
         * @param {String/Object} win The id of the window or a {@link Ext.Window} instance
         * @return {Ext.Window} The window
         */
        /*
        sendToBack : function(win){
            win = this.get(win);
            win._lastAccess = -(new Date().getTime());
            orderWindows();
            return win;
        },
        */
        
        /**
         * Hides all windows in the group.
         */
        /*
        hideAll : function(){
            for(var id in list){
                if(list[id] && typeof list[id] != "function" && list[id].isVisible()){
                    list[id].hide();
                }
            }
        },
        */
        
        /**
         * Gets the currently-active window in the group.
         * @return {Ext.Window} The active window
         */
        /*
        getActive : function(){
            return front;
        },
        */

        /**
         * Returns zero or more windows in the group using the custom search function passed to this method.
         * The function should accept a single {@link Ext.ux.PopupWindow} reference as its only argument and should
         * return true if the window matches the search criteria, otherwise it should return false.
         * @param {Function} fn The search function
         * @param {Object} scope (optional) The scope in which to execute the function (defaults to the window
         * that gets passed to the function if not specified)
         * @return {Array} An array of zero or more matching windows
         */
        getBy : function(fn, scope){
            cleanupClosedWindows();
            var r = [];
            for(var i = accessList.length-1; i >=0; --i) {
                var win = accessList[i];
                if(fn.call(scope||win, win) !== false){
                    r.push(win);
                }
            }
            return r;
        },

        /**
         * Executes the specified function once for every window in the group, passing each
         * window as the only parameter. Returning false from the function will stop the iteration.
         * @param {Function} fn The function to execute for each item
         * @param {Object} scope (optional) The scope in which to execute the function
         */
        each : function(fn, scope){
            cleanupClosedWindows();
            for(var id in list){
                if(list[id] && typeof list[id] != "function"){
                    if(fn.call(scope || list[id], list[id]) === false){
                        return;
                    }
                }
            }
        }
    };
};

Ext.ux.PopupWindowGroup.MainWindowName = 'MainWindow';
/**
 * returns the main window
 * 
 * @todo move to WindowManager
 */
Ext.ux.PopupWindowGroup.getMainWindow = function() {
    var w = window;
    try {
        while ( w.name != Ext.ux.PopupWindowGroup.MainWindowName) {
            w = w.opener;
            if (! w) {
                return false;
            }
        }
    } catch (e) {
        // lets reuse this window
        w.name = Ext.ux.PopupWindowGroup.MainWindowName;
        return false;
    }
    return w;
};

/**
 * @class Ext.ux.PopupWindowMgr
 * @extends Ext.ux.PopupWindowGroup
 * The default global window group that is available automatically.  To have more than one group of 
 * popup windows, create additional instances of {@link Ext.ux.PopupWindowGroup} as needed.
 * @singleton
 */
var mainWindow = Ext.ux.PopupWindowGroup.getMainWindow();
if (! mainWindow || mainWindow == window) {
    Ext.ux.PopupWindowMgr = new Ext.ux.PopupWindowGroup();
    window.name = Ext.ux.PopupWindowGroup.MainWindowName;
    window.isMainWindow = true;
} else {
    Ext.ux.PopupWindowMgr = Ext.ux.PopupWindowGroup.getMainWindow().Ext.ux.PopupWindowMgr;
    window.isMainWindow = false;
}
