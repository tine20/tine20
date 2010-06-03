/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ImageCropper
 * @extends     Ext.Component
 */
Ext.ux.form.ImageCropper = function(config) {
    Ext.apply(this, config);

    Ext.ux.form.ImageCropper.superclass.constructor.apply(this, arguments);
    
    this.addEvents(
        /**
         * @event uploadcomplete
         * Fires when the upload was done successfully 
         * @param {String} croped image
         */
         'imagecropped'
    );
};

Ext.extend(Ext.ux.form.ImageCropper, Ext.Component, {
    /**
     * @property {Object} natrual size of image
     */
    imageSize: false,
    
    width: 320,
    height: 320,
    
    initComponent: function() {
        this.imageURL.width = 290;
        this.imageURL.height = 240;
        this.imageURL.ratiomode = 1;

        Ext.ux.form.ImageCropper.superclass.initComponent.call(this);
    },
    onRender: function(ct, position) {
        Ext.ux.form.ImageCropper.superclass.onRender.call(this, ct, position);
        this.wrapEl = Ext.DomHelper.insertFirst(ct, {tag: 'div'}, true);
        //this.maskEl = Ext.DomHelper.insertFirst(this.wrapEl, {tag: 'div', style: 'background-color: #000000'}, true);
        
        // bg image
        this.bgImageEl = Ext.DomHelper.insertFirst(this.wrapEl, {tag: 'img', id: 'yui_img', src: this.imageURL}, true);
        this.bgImageEl.setOpacity(0.5);
        
        // yui cropper is very fast. 
        // cause of the window usage the yui crop mask does not work, so we use the mask obove
        /*
        var Dom = YAHOO.util.Dom, 
        Event = YAHOO.util.Event; 
    
        var crop = new YAHOO.widget.ImageCropper('yui_img');
        */
        
         // Ext only implementation is very slow!
         // the bad news is that the resizeable dosn't fire events while resizeing 
         // and that the dd onDrag looses scope
         this.fgImageEl = Ext.DomHelper.insertFirst(this.wrapEl, {tag: 'div', style: {
            width              : '100px',
            height             : '100px',
            position           : 'absolute',
            top                : '30px',
            left               : '30px',
            'background-image' : 'url(' + this.imageURL + ')',
            'background-repeat': 'no-repeat'
        }}, true);
        
        this.resizeable = new Ext.Resizable(this.fgImageEl, {
            wrap:true,
            pinned:true,
            handles: 's e se',
            draggable:true,
            dynamic:true
        });
        this.resizeable.dd.fgImageEl = this.fgImageEl;
        this.resizeable.dd.bgImageEl = this.bgImageEl;
        
        this.resizeable.dd.onDrag = this.syncImageEls;
        
        // fix opacity, which might be broken by the inherited window properties
        var rhs = this.resizeable.getEl().query('div.x-resizable-handle');
        for (var i=0, j=rhs.length; i<j; i++) {
            Ext.get(rhs[i]).setOpacity(1);
        }
        this.syncImageEls();
    },
    /**
     * sync fg image with bg image
     */
    syncImageEls: function() {
        var dx = this.bgImageEl.getX() - this.fgImageEl.getX();
        var dy = this.bgImageEl.getY() - this.fgImageEl.getY();
        this.fgImageEl.setStyle('background-position', dx + 'px ' + dy + 'px');
    }
});
