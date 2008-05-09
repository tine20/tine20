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

/**
 * a field which displayes a image of the given url and optionally supplies upload
 * button with the feature to display the newly uploaded image on the fly
 */
Ext.ux.form.ImageField = Ext.extend(Ext.form.Field, {
    defaultAutoCreate : {tag:'input', type:'hidden'},
    
    initComponent: function() {
        Ext.ux.form.ImageField.superclass.initComponent.call(this);
    },
    onRender: function(ct, position) {
        Ext.ux.form.ImageField.superclass.onRender.call(this, ct, position);
        var imgHtml = '<img src="' + this.getValue() + '" width="90px">';
        this.imageCt = Ext.DomHelper.insertFirst(ct, imgHtml, true);

        var bb = new Ext.ux.form.BrowseButton({
            buttonCt: ct,
            renderTo: this.imageCt,
            scope: this,
            handler: this.onFileSelect
            //debug: true
        });
    },
    getValue: function() {
        return 'images/empty_photo.jpg';
    },
    setValue: function(value) {
        
    },
    onFileSelect: function(bb) {
        var input = bb.detachInputFile();
        var uploader = new Ext.ux.file.Uploader({
            input: input
        }).upload();
        uploader.on('uploadcomplete', function(uploader, record){
            var method = Ext.util.Format.htmlEncode('Tinebase.getTempFileThumbnail');
            var ct = this.imageCt.up('div');
            var img = Ext.DomHelper.insertFirst(ct, '<img src="index.php?method=' + method + '&id=' + record.get('tempFile').id + '" width="90px">', true);
            this.imageCt.remove();
            this.imageCt = img;
        }, this);
    }
});