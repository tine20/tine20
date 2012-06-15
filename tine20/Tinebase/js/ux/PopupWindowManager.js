/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 

/**
 * An object that represents a group of {@link Ext.ux.PopupWindow}
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.PopupWindowGroup
 * @constructor
 */
Ext.ux.PopupWindowGroup = function(config) {
    config = config || {};
    
    /**
     * @cfg {window} mainWindow
     */
    var mainWindow = config.mainWindow || window;
    
    // mark mainWindow
    mainWindow.isMainWindow = true;
    
    var list = {};
    var accessList = [];
    var front = null;
    
    

    // private
    var cleanupClosedWindows = function() {
        var doc;
        for(var id in list){
            try {
                var newDate = new Date().getTime();

                if(list[id].registerTime && (list[id].registerTime/1 + 2000 > newDate/1)) {
                    
                    continue;
                }
                
                doc = list[id].popup.document;
                if(!Ext.isIE && !doc.defaultView) {
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

            win.registerTime = new Date().getTime();
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
         * returns main window of this window group
         * 
         * @return window
         */
        getMainWindow: function() {
            return mainWindow;
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
                if(win.popup.focus) {
                    win.popup.focus();
                }
                
//                front = win;
                // NOTE: we don't recognise the front window yet
//                if (Ext.isOpera) {
//                    Ext.Msg.alert(_("The window you want to work with is backgrounded. Your browser doesn't support to foreground the window for you, so you need to use your operating systems window switching features. Please send complaints to your browser vendor!"))
//                }
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

/**
 * @class Ext.ux.PopupWindowMgr
 * @extends Ext.ux.PopupWindowGroup
 * The default global window group that is available automatically.  To have more than one group of 
 * popup windows, create additional instances of {@link Ext.ux.PopupWindowGroup} as needed.
 * @singleton
 */
try {
    Ext.ux.PopupWindowMgr = window.opener.Ext.ux.PopupWindowMgr ? 
        window.opener.Ext.ux.PopupWindowMgr :
        new Ext.ux.PopupWindowGroup();
} catch (e) {
    // we might have no access no opener
    Ext.ux.PopupWindowMgr = new Ext.ux.PopupWindowGroup();
}
