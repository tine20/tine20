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
 * Class for handling of native browser popup window.
 * <p>This class is intended to make the usage of a native popup window as easy as dealing with a modal window.<p>
 * <p>Example usage:</p>
 * <pre><code>
 var win = new Ext.ux.PopupWindow({
     name: 'TasksEditWindow',
     width: 700,
     height: 300,
     url:index.php?method=Tasks.editTask&taskId=5
 });
 * </code></pre>
 */
Ext.ux.PopupWindow = function(config) {
    Ext.apply(this, config);
    Ext.ux.PopupWindow.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.PopupWindow, Ext.Component, {
	/**
	 * @cfg 
	 * @param {String} url
	 * @desc  url to open
	 */
	url: null,
	/**
	 * @cfg {String} internal name of new window
	 */
	name: 'new window',
	/**
	 * @cfg {Int} width of new window
	 */
	width: 500,
	/**
	 * @cfg {Int} height of new window
	 */
	height: 500,
    /**
     * @cfg {Bolean}
     */
    modal: false,
    /**
     * @cfg {String}
     */
    layout: 'fit',
    /**
     * @cfg {String}
     */
    title: null,
    /**
     * @cfg {String} Name of a constructor to create item property
     */
    contentPanelConstructor: null,
    /**
     * @cfg {Object} Config object to pass to itemContructor
     */
    contentPanelConstructorConfig: {},
    /**
     * @property {Browser Window}
     */
    popup: null,
    /**
     * @property {Ext.ux.PopupWindowMgr}
     */
    windowManager: null,
    
	/**
	 * @private
	 */
	initComponent: function(){
        if (! this.title) {
            this.title = Tine.title;
        }
        
        this.windowManager = Ext.ux.PopupWindowMgr;
        Ext.ux.PopupWindow.superclass.initComponent.call(this);
        
        //limit the window size
        this.width = Math.min(screen.availWidth, this.width);
        this.height = Math.min(screen.availHeight, this.height);

        // open popup window first to save time
        this.popup = Tine.Tinebase.common.openWindow(this.name, this.url, this.width, this.height);
        
        //. register window ( in fact register complete PopupWindow )
        this.windowManager.register(this);
        
        // does not work on reload!
        //this.popup.PopupWindow = this;
        
        // strange problems in FF
        //this.injectFramework(this.popup);

        this.addEvents({
            /**
             * @event beforecolse
             * @desc Fires before the Window is closed. A handler can return false to cancel the close.
             * @param {Ext.ux.PopupWindow}
             */
            "beforeclose" : true,
            /**
             * @event render
             * @desc  Fires after the viewport in the popup window is rendered
             * @param {Ext.ux.PopupWindow} 
             */
            "render" : true,
            /**
             * @event close
             * @desc  Fired, when the window got closed
             */
            "close" : true
        });
        
        // NOTE: Do not register unregister with this events, 
        //       as it would be broken on window reloads!
        /*
        if (this.popup.addEventListener) {
            this.popup.addEventListener('load', this.onLoad, true);
            this.popup.addEventListener('unload', this.onClose, true);
        } else if (this.popup.attachEvent) {
            this.popup.attachEvent('onload', this.onLoad);
            this.popup.attachEvent('onunload', this.onClose);
        } else {
            this.popup.onload = this.onLoad;
            this.popup.onunload = this.onClose;
        }
        */
	},
    
    /**
     * rename window name
     * 
     * @param {String} new name
     */
    rename: function(newName) {
        this.windowManager.unregister(this);
        this.name = this.popup.name = newName;
        this.windowManager.register(this);
    },
    
    /**
     * Sets the title text for the panel and optionally the icon class.
     * 
     * @param {String} title The title text to set
     * @param {String} iconCls (optional) iconCls A user-defined CSS class that provides the icon image for this panel
     */
    setTitle: function(title, iconCls) {
        if (this.popup && this.popup.document) {
            this.popup.document.title = title;
        }
    },
    
    /**
     * Closes the window, removes it from the DOM and destroys the window object. 
     * The beforeclose event is fired before the close happens and will cancel 
     * the close action if it returns false.
     */
    close: function() {
        if(this.fireEvent("beforeclose", this) !== false){
            this.fireEvent('close', this);
            this.purgeListeners();
            this.popup.close();
        }
    },
    
	/**
	 * @private
     * 
	 * called after this.popups native onLoad
     * note: 'this' references the popup, whereas window references the parent
	 */
	onLoad: function() {
        this.Ext.onReady(function() {
        	//console.log(this);
        	//console.log(window);
        }, this);
    },
    
    /**
     * @private
     * 
     * note: 'this' references the popup, whereas window references the parent
     */
    onClose: function() {

    }
});

