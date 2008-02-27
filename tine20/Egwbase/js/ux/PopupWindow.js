/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux');

Ext.ux.PopupWindow = Ext.extend(Ext.Component, {
	url: null,
	name: 'new window',
	width: 500,
	height: 500,
	initComponent: function(){
        Ext.ux.PopupWindow.superclass.initComponent.call(this);
        this.addEvents({
            "update" : true,
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
	// called after this.popups native onLoad
	setupPopupEvents: function(){
		// Attention, complicated stuff!
		// 'this' references the popup, whereas window references the parent
        this.Ext.onReady(function() {
        	//console.log(this);
        	//console.log(window);
        }, this);
    }
});

