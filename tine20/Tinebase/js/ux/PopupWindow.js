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
    title: 'Tine 2.0',
    /**
     * @cfg {String} Name of a constructor to create item property
     */
    itemsConstructor: null,
    /**
     * @cfg {Object} Config object to pass to itemContructor
     */
    itemsConstructorConfig: {},
    /**
     * @property {Browser Window}
     */
    popup: null,
    /**
     * @prperty {Ext.ux.PopupWindowMgr}
     */
    windowManager: null,
	/**
	 * @private
	 */
	initComponent: function(){
        this.windowManager = Ext.ux.PopupWindowMgr;
        Ext.ux.PopupWindow.superclass.initComponent.call(this);

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
             * @event render
             * @desc  Fires after the viewport in the popup window is rendered
             * @param {window} 
             */
            "render" : true,
        	/**
             * @event update
             * @desc  Fired when a record in the window got updated
             * @param {Ext.data.record} data data of the new entry
             */
            "update" : true,
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

    },
    /**
     * injects document with framework html (head)
     * NOTE: has strange layout problems in FF
     */
    injectFramework: function(popup) {
        var framework = new Ext.XTemplate('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">\n' ,
            '<html><head>{head}</head><body></body></html>'
        );
        
        var head = Ext.getDoc().dom.documentElement.firstChild.innerHTML;
        head = head.replace(/Ext\.onReady[^<]*/m, 'Ext.onReady(function(){formData={"linking":{"link_app1":"","link_id1":"-1"}}; Tine.Tinebase.initFramework();' + this.onReadyFn + 'window.focus();});');
        
        var doc = popup.document;
        doc.open("text/html; charset=utf-8", "replace");
        doc.write(framework.apply({
            head:  head
        }));
        doc.close();
    }
});

