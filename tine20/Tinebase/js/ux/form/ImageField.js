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
     * @cfg {bool}
     */
    border: true,
    /**
     * @cfg {String}
     */
    defaultImage: 'images/empty_photo.png',
    
    defaultAutoCreate : {tag:'input', type:'hidden'},
    
    initComponent: function() {
        this.plugins = this.plugins || [];
        this.scope = this;
        this.handler = this.onFileSelect;
        
        this.browsePlugin = new Ext.ux.file.BrowsePlugin({});
        this.plugins.push(this.browsePlugin);
        
        Ext.ux.form.ImageField.superclass.initComponent.call(this);
        this.imageSrc = this.defaultImage;
        if(this.border === true) {
            this.width = this.width;
            this.height = this.height;
        }
    },
    onRender: function(ct, position) {
        Ext.ux.form.ImageField.superclass.onRender.call(this, ct, position);
        
        // the container for the browe button
        this.buttonCt = Ext.DomHelper.insertFirst(ct, '<div>&nbsp;</div>', true);
        this.buttonCt.applyStyles({
            border: this.border === true ? '1px solid #B5B8C8' : '0'
        });
        this.buttonCt.setSize(this.width, this.height);
        this.buttonCt.on('contextmenu', this.onContextMenu, this);
        
        this.loadMask = new Ext.LoadMask(this.buttonCt, {msg: _('Loading'), msgCls: 'x-mask-loading'});
        
        // the click to edit text container
        var clickToEditText = _('click to edit');
        this.textCt = Ext.DomHelper.insertFirst(this.buttonCt, '<div class="x-ux-from-imagefield-text">' + clickToEditText + '</div>', true);
        this.textCt.setSize(this.width, this.height);
        var tm = Ext.util.TextMetrics.createInstance(this.textCt);
        tm.setFixedWidth(this.width);
        this.textCt.setStyle({top: ((this.height - tm.getHeight(clickToEditText)) / 2) + 'px'});
        
        // the image container
        // NOTE: this will atm. always be the default image for the first few miliseconds
        this.imageCt = Ext.DomHelper.insertFirst(this.buttonCt, '<img src="' + this.imageSrc + '"/>' , true);
        this.imageCt.setOpacity(0.2);
        this.imageCt.setStyle({
            position: 'absolute',
            top: '18px'
        });
        
        Ext.apply(this.browsePlugin, {
            buttonCt: this.buttonCt,
            renderTo: this.buttonCt
        });
    },
    getValue: function() {
        var value = Ext.ux.form.ImageField.superclass.getValue.call(this);
        return value;
    },
    setValue: function(value) {
        Ext.ux.form.ImageField.superclass.setValue.call(this, value);
        if (! value || value == this.defaultImage) {
            this.imageSrc = this.defaultImage;
        } else {
            this.imageSrc = Ext.ux.util.ImageURL.prototype.parseURL(value);
            this.imageSrc.width = this.width;
            this.imageSrc.height = this.height;
            this.imageSrc.ratiomode = 0;
        }
        this.updateImage();
    },
    /**
     * @private
     */
    onFileSelect: function(bb) {
        var input = bb.detachInputFile();
        var uploader = new Ext.ux.file.Uploader({
            input: input
        });
        if(! uploader.isImage()) {
            Ext.MessageBox.alert(_('Not An Image'), _('Plase select an image file (gif/png/jpeg)')).setIcon(Ext.MessageBox.ERROR);
            return;
        }
        uploader.on('uploadcomplete', function(uploader, record){
            this.imageSrc = new Ext.ux.util.ImageURL({
                id: record.get('tempFile').id,
                width: this.width,
                height: this.height,
                ratiomode: 0
            });
            this.setValue(this.imageSrc);
            
            this.updateImage();
        }, this);
        uploader.on('uploadfailure', this.onUploadFail, this);
        
        this.loadMask.show();
        uploader.upload();
        
        if (this.ctxMenu) {
            this.ctxMenu.hide();
        }
    },
    /**
     * @private
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(_('Upload Failed'), _('Could not upload image. Please notify your Administrator')).setIcon(Ext.MessageBox.ERROR);
    },
    /**
     * executed on image contextmenu
     * @private
     */
    onContextMenu: function(e, input) {
        e.preventDefault();
        
        var ct = Ext.DomHelper.append(this.buttonCt, '<div>&nbsp;</div>', true);
        
        var upload = new Ext.menu.Item({
            text: _('Change Image'),
            iconCls: 'action_uploadImage',
            handler: this.onFileSelect,
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({})]
        });
        
        this.ctxMenu = new Ext.menu.Menu({
            items: [
            upload,
            {
                text: _('Crop Image'),
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
                        title: _('Crop Image'),
                        layout: 'fit',
                        items: dlg
                    });
                    win.show();
                }
            
            },{
                text: _('Delete Image'),
                iconCls: 'action_delete',
                disabled: this.imageSrc == this.defaultImage,
                scope: this,
                handler: function() {
                    this.setValue('');
                }
                
            },{
                text: _('Show Original Image'),
                iconCls: 'action_originalImage',
                disabled: this.imageSrc == this.defaultImage,
                scope: this,
                handler: this.downloadImage
                
            }]
        });
        this.ctxMenu.showAt(e.getXY());
    },
    
    downloadImage: function() {
        var url = Ext.apply(this.imageSrc, {
            height: -1,
            width: -1
        }).toString();
        
        Tine.Tinebase.common.openWindow('showImage', url, 800, 600);
    },
    
    updateImage: function() {
        // only update when new image differs from current
        if(this.imageCt.dom.src.substr(-1 * this.imageSrc.length) != this.imageSrc) {
            var ct = this.imageCt.up('div');
            var img = Ext.DomHelper.insertAfter(this.imageCt, '<img src="' + this.imageSrc + '"/>' , true);
            // replace image after load
            img.on('load', function(){
                this.imageCt.remove();
                this.imageCt = img;
                this.textCt.setVisible(this.imageSrc == this.defaultImage);
                this.imageCt.setOpacity(this.imageSrc == this.defaultImage ? 0.2 : 1);
                this.loadMask.hide();
            }, this);
            img.on('error', function() {
                Ext.MessageBox.alert(_('Image Failed'), _('Could not load image. Please notify your Administrator')).setIcon(Ext.MessageBox.ERROR);
                this.loadMask.hide();
            }, this);
        }
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
        location: 'tempFile',
        width: 90,
        height: 120,
        ratiomode: 0
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

