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
    /**
     * @cfg {String}
     */
    defaultImage: 'images/empty_photo.jpg',
    
    defaultAutoCreate : {tag:'input', type:'hidden'},
    
    initComponent: function() {
        Ext.ux.form.ImageField.superclass.initComponent.call(this);
        this.imageSrc = this.defaultImage;
    },
    onRender: function(ct, position) {
        Ext.ux.form.ImageField.superclass.onRender.call(this, ct, position);
        
        // the container for the browe button
        this.buttonCt = Ext.DomHelper.insertFirst(ct, '<div>&nbsp;</div>', true);
        this.buttonCt.setSize(this.width, this.height);
        this.buttonCt.on('contextmenu', this.onContextMenu, this);
        
        // the image container        
        this.imageCt = Ext.DomHelper.insertFirst(this.buttonCt, this.getImgTpl().apply(this), true);
        
        this.bb = new Ext.ux.form.BrowseButton({
            buttonCt: this.buttonCt,
            renderTo: this.buttonCt,
            scope: this,
            handler: this.onFileSelect
            //debug: true
        });
    },
    getValue: function() {
        var value = Ext.ux.form.ImageField.superclass.getValue.call(this);
        return value;
    },
    setValue: function(value) {
        Ext.ux.form.ImageField.superclass.setValue.call(this, value);
        this.imageSrc = value ? Ext.ux.util.ImageURL.prototype.parseURL(value) : this.defaultImage;
        this.updateImage();
    },
    onFileSelect: function(bb) {
        var input = bb.detachInputFile();
        var uploader = new Ext.ux.file.Uploader({
            input: input
        });
        if(! uploader.isImage()) {
            Ext.MessageBox.alert('Not An Image', 'Plase select an image file (gif/png/jpeg)').setIcon(Ext.MessageBox.ERROR);
            return;
        }
        
        this.buttonCt.mask('Loading', 'x-mask-loading');
        uploader.upload();
        uploader.on('uploadcomplete', function(uploader, record){
            //var method = Ext.util.Format.htmlEncode('');
            this.imageSrc = new Ext.ux.util.ImageURL({
                id: record.get('tempFile').id,
                width: this.width,
                height: this.height -2,
                ratiomode: 0
            });
            this.setValue(this.imageSrc);
            
            this.updateImage();
        }, this);
    },
    /**
     * executed on image contextmenu
     * @private
     */
    onContextMenu: function(e, input) {
        e.preventDefault();
        
        var ct = Ext.DomHelper.append(this.buttonCt, '<div>&nbsp;</div>', true);
        
        var upload = new Ext.menu.Item({
            text: 'Change Image',
            iconCls: 'action_uploadImage'
        });
        upload.on('render', function(){
            var ct = upload.getEl();
            var bb = new Ext.ux.form.BrowseButton({
                buttonCt: ct,
                renderTo: ct,
                scope: this,
                handler: function(bb) {
                    this.ctxMenu.hide();
                    this.onFileSelect(bb);
                }
                //debug: true
            });
        }, this);
        
        this.ctxMenu = new Ext.menu.Menu({
            items: [
            upload,
            {
                text: 'Edit Image',
                iconCls: 'action_cropImage',
                scope: this,
                disabled: true, //this.imageSrc == this.defaultImage,
                handler: function() {
                    var cropper = new Ext.ux.form.ImageCropper({
                        imageURL: this.imageSrc
                    });
                    
                    var dlg = new Tine.widgets.dialog.EditRecord({
                        handlerScope: this,
                        handlerCancle: this.close,
                        items: cropper
                    });
                    
                    var win = new Ext.Window({
                        width: 320,
                        height: 320,
                        title: 'Crop Image',
                        layout: 'fit',
                        items: dlg
                    })
                    win.show();
                }
            
            },{
                text: 'Delete Image',
                iconCls: 'action_delete',
                disabled: this.imageSrc == this.defaultImage,
                scope: this,
                handler: function() {
                    this.setValue('');
                }
                
            }]
        });
        this.ctxMenu.showAt(e.getXY());
    },
    getImgTpl: function() {
        if (!this.imgTpl) {
            this.imgTpl = new Ext.XTemplate('<img ',
                'src="{imageSrc}" ',
                'width="{width}" ',
                'height="{height -2}" ',
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

Ext.namespace('Ext.ux.util');

/**
 * this class represents an image URL
 */
Ext.ux.util.ImageURL = function(config) {
    Ext.apply(this, config, {
        url: 'index.php',
        method: 'Tinebase.getImage',
        application: 'Tinebase',
        location: 'tempFile'
    }); 
};
/**
 * generates an imageurl according to the class members
 * 
 * @return {String}
 */
Ext.ux.util.ImageURL.prototype.toString = function() {
    return this.url + 
        "?method=" + this.method + 
        "&application=" + this.application + 
        "&location=" + this.location + 
        "&id=" + this.id + 
        "&width=" + this.width + 
        "&height=" + this.height + 
        "&ratiomode=" + this.ratiomode;
};
/**
 * parses an imageurl
 * 
 * @param  {String} url
 * @return {Ext.ux.util.ImageURL}
 */
Ext.ux.util.ImageURL.prototype.parseURL = function(url) {
    var url = url.toString();
    var params = {};
    var lparams = url.substr(url.indexOf('?')+1).split('&');
    for (var i=0, j=lparams.length; i<j; i++) {
        var param = lparams[i].split('=');
        params[param[0]] = Ext.util.Format.htmlEncode(param[1]);
    }
    return new Ext.ux.util.ImageURL(params);
};

