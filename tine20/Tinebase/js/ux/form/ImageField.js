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
        var buttonCt = Ext.DomHelper.insertFirst(ct, '<div>&nbsp;</div>', true);
        buttonCt.setSize(90,100);
        
        //var imgHtml = '<img src="' + this.getValue() + '" width="90px">';
        
        this.imageCt = Ext.DomHelper.insertFirst(buttonCt, this.getImgTpl().apply(this), true);

        var bb = new Ext.ux.form.BrowseButton({
            buttonCt: buttonCt,
            renderTo: buttonCt,
            scope: this,
            handler: this.onFileSelect,
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
            this.imageSrc = 'index.php?method=' + method + '&id=' + record.get('tempFile').id;
            var ct = this.imageCt.up('div');
            var img = Ext.DomHelper.insertFirst(ct, this.getImgTpl().apply(this), true);
            this.imageCt.remove();
            this.imageCt = img;
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
    }
});