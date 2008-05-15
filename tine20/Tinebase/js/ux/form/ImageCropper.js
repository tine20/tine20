/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux.form');

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

Ext.extend(Ext.ux.form.ImageCropper, Ext.Window, {
    width: 320,
    height: 320,
    title: 'Crop Image',
    layout: 'fit',
    initComponent: function() {
        var dlg = new Tine.widgets.dialog.EditRecord({
            handlerScope: this,
            handlerCancle: this.close,
            items: [{
                xtype: 'panel',
                html: '<img src="' + this.image + '" width="320" height="240">'
            }]
        });
        this.items = dlg;
        Ext.ux.form.ImageCropper.superclass.initComponent.call(this);
    }
});
