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
Ext.ux.PopupWindow = Ext.extend(Ext.Component, {
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
	 * @private
	 */
	initComponent: function(){
        Ext.ux.PopupWindow.superclass.initComponent.call(this);
        this.addEvents({
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
        
        // open popup window
        this.popup = Tine.Tinebase.Common.openWindow(
            this.name, 
            this.url,
            this.width,
            this.height
        );
        
        // we need to store ourself in the popup, cause we loose scope by the native broweser event!
        this.popup.ParentEventProxy = this;
        
        /*
        if (this.popup.addEventListener) {
            this.popup.addEventListener('load', this.setupPopupEvents, true);
        } else if (this.popup.attachEvent) {
            this.popup.attachEvent('onload', this.setupPopupEvents);
        } else {
            this.popup.onload = this.setupPopupEvents;
        }
        */
	},
	/**
	 * @private
	 * called after this.popups native onLoad
	 */
	setupPopupEvents: function(){
		// Attention, complicated stuff!
		// 'this' references the popup, whereas window references the parent
        this.Ext.onReady(function() {
        	//console.log(this);
        	//console.log(window);
        }, this);
    }
});

