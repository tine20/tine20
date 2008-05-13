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
 * @class Ext.ux.form.ImageField
 * 
 * <p>A field which displayes a image of the given url and optionally supplies upload
 * button with the feature to display the newly uploaded image on the fly</p>
 * <p>Example usage:</p>
 * <pre><code>
 var formField = new Ext.ux.form.ImageField({
     name: 'jpegimage',
     width: 90,
     height: 90
 });
 * </code></pre>
 */
Ext.ux.form.ImageField = Ext.extend(Ext.form.Field, {
    defaultAutoCreate : {tag:'input', type:'hidden'},
    
    initComponent: function() {
        Ext.ux.form.ImageField.superclass.initComponent.call(this);
        this.imageSrc = this.getValue();
        
    },
    onRender: function(ct, position) {
        Ext.ux.form.ImageField.superclass.onRender.call(this, ct, position);
        
        // the container for the browe button
        this.buttonCt = Ext.DomHelper.insertFirst(ct, '<div>&nbsp;</div>', true);
        this.buttonCt.setSize(this.width, this.height);
        
        // the image container        
        this.imageCt = Ext.DomHelper.insertFirst(this.buttonCt, this.getImgTpl().apply(this), true);
        
        var bb = new Ext.ux.form.BrowseButton({
            buttonCt: this.buttonCt,
            renderTo: this.buttonCt,
            scope: this,
            handler: this.onFileSelect,
            //debug: true
        });
    },
    getValue: function() {
        var value = Ext.ux.form.ImageField.superclass.getValue.call(this);
        if (!value) {
            value = 'images/empty_photo.jpg';
        }
        return value;
    },
    setValue: function(value) {
        Ext.ux.form.ImageField.superclass.setValue.call(this, value);
        this.imageSrc = value;
        this.updateImage();
    },
    onFileSelect: function(bb) {
        this.buttonCt.mask('Loading', 'x-mask-loading');
        var input = bb.detachInputFile();
        var uploader = new Ext.ux.file.Uploader({
            input: input
        }).upload();
        uploader.on('uploadcomplete', function(uploader, record){
            var method = Ext.util.Format.htmlEncode('Tinebase.getTempFileThumbnail');
            this.imageSrc = 'index.php?method=' + method + '&id=' + record.get('tempFile').id + '&width=' + this.width + '&height=' + this.height + '&ratiomode=0';
            //console.log(this.imageSrc);
            this.setValue(this.imageSrc);
            
            this.updateImage();
        }, this);
    },
    getImgTpl: function() {
        if (!this.imgTpl) {
            this.imgTpl = new Ext.XTemplate('<img ',
                'src="{imageSrc}" ',
                'width="{width}" ',
                'height="{height}" ',
                'style="border: 1px solid #B5B8C8;" ',
                ' >'
            ).compile();
        }
        return this.imgTpl;
    },
    updateImage: function() {
        var ct = this.imageCt.up('div');
        var img = Ext.DomHelper.insertAfter(this.imageCt, this.getImgTpl().apply(this), true);
        // replace image after load
        img.on('load', function(){
            this.imageCt.remove();
            this.imageCt = img;
            this.buttonCt.unmask();
        }, this);
    }
});