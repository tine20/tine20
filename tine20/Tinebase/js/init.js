/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/** --------------------- Ultra Geneirc Javacipt Stuff --------------------- **/

/**
 * create console pseudo object when firebug is disabled/not installed
 */
if (! ("console" in window) || !("firebug" in console)) {
    window.console = {
        log: null , debug: null, info: null, warn: null, error: null, assert: null, dir: null, dirxml: null, group: null,
        groupEnd: null, time: null, timeEnd: null, count: null, trace: null, profile: null, profileEnd: null
    };
    for (f in window.console) {
        window.console[f] = function() {};
    }
}


/** -------------------- Extjs Framework Initialisation -------------------- **/

/**
 * don't fill the ext stats
 */
Ext.BLANK_IMAGE_URL = "ExtJS/resources/images/default/s.gif";
/**
 * init ext quick tips
 */
Ext.QuickTips.init();
/**
 * html encode all grid columns per defaut
 */
Ext.grid.ColumnModel.defaultRenderer = Ext.util.Format.htmlEncode;
/**
 * init the window handling
 */
Ext.ux.PopupWindow.prototype.url = 'index.php';

/**
 * Main entry point of each Tine 2.0 window
 * 
 */
Ext.onReady(function(){
    // Tine Framework initialisation for each window
    Tine.Tinebase.initFramework();
    /** temporary login **/
    if (!Tine.Tinebase.Registry.get('currentAccount')) {
        Tine.Login.showLoginDialog(Tine.Tinebase.Registry.get('defaultUsername'), Tine.Tinebase.Registry.get('defaultPassword'));
        return;
    }
    
    
    if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
        // mainscreen request
        window.name = Ext.ux.PopupWindowGroup.MainScreenName;
        Ext.ux.PopupWindowMgr.register({
            name: window.name,
            popup: window
        });
        Tine.Tinebase.MainScreen = new Tine.Tinebase.MainScreenClass();
        Tine.Tinebase.MainScreen.render();
        window.focus();
    } else {
        // @todo move PopupWindowMgr to generic WindowMgr
        // init WindowMgr like registry!
        var c = Ext.ux.PopupWindowMgr.get(window) || {};
        
        if (!c.itemsConstructor && window.exception) {
            switch (exception.code) {
                
                // autorisation required
                case 401:
                    Tine.Login.showLoginDialog(Tine.Tinebase.Registry.get('defaultUsername'), Tine.Tinebase.Registry.get('defaultPassword'));
                    return;
                    break;
                
                // generic exception
                default:
                    // we need to wait to grab initialData from mainscreen
                    //var win = new Tine.Tinebase.ExceptionDialog({});
                    //win.show();
                    return;
                    break;
            }
            
        }

        window.document.title = c.title;

        var items;
        if (c.itemsConstructor) {
            var parts = c.itemsConstructor.split('.');
            var ref = window;
            for (var i=0; i<parts.length; i++) {
                ref = ref[parts[i]];
            }
            var items = new ref(c.itemsConstructorConfig);
        } else {
            items = c.items ? c.items : {}
        }
        
        /** temporary Tine.onRady for smooth transition to new window handling **/
        if (typeof(Tine.onReady) == 'function') {
            Tine.onReady();
        } else {
            c.viewport = new Ext.Viewport({
                title: c.title,
                layout: c.layout ? c.layout : 'border',
                items: items
            });
        }
        window.focus();
    }
});


/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

Ext.namespace('Tine');
Tine.Build = '$Build: $';

/**
 * initialise window types
 */
Tine.WindowFactory = new Ext.ux.WindowFactory({
    windowType: 'Browser'
});

/**
 * initialise state provider
 */
Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
    // fill store from registry / initial data
    // Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
} else {
    // take main windows store
    Ext.state.Manager.getProvider().setStateStore(Ext.ux.PopupWindowGroup.getMainScreen().Ext.state.Manager.getProvider().getStateStore());
}


