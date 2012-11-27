/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.ns('Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ImageField
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
    defaultImage: 'images/empty_photo_blank.png',
    
    
    defaultAutoCreate: {tag: 'div', cls: 'ux-imagefield-ct', cn: [
        {tag: 'img', cls: 'ux-imagefield-img'},
        {tag: 'div', cls: 'ux-imagefield-button'},
        {tag: 'div', cls: 'ux-imagefield-text'}
    ]},
    
    handleMouseEvents: true,
    
    initComponent: function () {
        this.plugins = this.plugins || [];
        this.scope = this;
        this.handler = this.onFileSelect;
        
        this.browsePlugin = new Ext.ux.file.BrowsePlugin({});
        this.plugins.push(this.browsePlugin);
        
        Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
        
        Ext.ux.form.ImageField.superclass.initComponent.call(this);
        this.value = this.defaultImage;
        
    },
    
    afterRender: function() {
        
        this.imageCt = this.el.child('img');
        this.buttonCt = this.el.child('div[class=ux-imagefield-button]');
        this.textCt = this.el.child('div[class=ux-imagefield-text]');
        
        if (this.border === false) {
            this.el.applyStyles({
                border: '0'
            });
        }
        
        this.loadMask = new Ext.LoadMask(this.buttonCt, {msg: _('Loading'), msgCls: 'x-mask-loading'});
        this.textCt.setSize(this.width, this.height);
        var clickToEditText = _('Click to edit');
        var tm = Ext.util.TextMetrics.createInstance(this.textCt);
        tm.setFixedWidth(this.width);
        this.textCt.applyStyles({
            top: ((this.height - tm.getHeight(clickToEditText)) / 2) + 'px'
        });
        this.textCt.insertHtml('afterBegin', clickToEditText);
        
        Ext.apply(this.browsePlugin, {
            buttonCt: this.buttonCt,
            renderTo: this.buttonCt
        });
        
        Ext.ux.form.ImageField.superclass.afterRender.apply(this, arguments);
    },
    
    /**
    * returns value or empty string
    */
    getValue: function () {
        if (! this.value || this.value === this.defaultImage) {
            return '';
        } else {
            return String(this.value);
        }
    },
    
    /**
    * set value (image)
    */
    setValue: function (value) {
        Ext.ux.form.ImageField.superclass.setValue.call(this, value);
        if (! value || value === this.defaultImage) {
            this.value = this.defaultImage;
        } else {
            if (value instanceof Ext.ux.util.ImageURL || (Ext.isString(value) && value.match(/&/))) {
                this.value = Ext.ux.util.ImageURL.prototype.parseURL(value);
                this.value.width = (this.border === false ? this.width : this.width-2);
                this.value.height = (this.border === false ? this.height : this.height-2);
                this.value.ratiomode = 0;
            } else {
                this.setDefaultImage(value);
            }
        }
        this.updateImage();
    },
    
    /**
    * show image
    */
    updateImage: function () {
        var img = Ext.DomHelper.insertAfter(this.imageCt, '<img src="' + this.value + '"/>' , true);
        this.imageCt.remove();
        this.imageCt = img;
        this.textCt.setVisible(this.value === this.defaultImage);
        this.imageCt.setOpacity(this.value === this.defaultImage ? 0.2 : 1);
        
        img.on('load', function () {
            if (this.value === this.defaultImage) {
                this.imageCt.setStyle({
                    position: 'absolute',
                    top: ((this.height - this.imageCt.getHeight()) /2)+ 'px',
                    left: ((this.width - this.imageCt.getWidth()) /2) + 'px'
                });
            }
            this.loadMask.hide();
        }, this);
        img.on('error', function () {
            Ext.MessageBox.alert(_('Image Failed'), _('Could not load image. Please notify your Administrator')).setIcon(Ext.MessageBox.ERROR);
            this.loadMask.hide();
        }, this);
    },
    
    /**
    * set new default image
    */
    setDefaultImage: function (image) {
        if (this.value === this.defaultImage) {
            this.defaultImage = image;
            this.setValue(this.defaultImage);
        } else {
            this.defaultImage = image;
        }
    },
    
    /**
     * @private
     */
    onFileSelect: function (fileSelector) {
        if (! fileSelector.isImage()) {
            Ext.MessageBox.alert(_('Not An Image'), _('Please select an image file (gif/png/jpeg)')).setIcon(Ext.MessageBox.ERROR);
            return;
        }
        
        var files = fileSelector.getFileList();
        this.uploader = new Ext.ux.file.Upload({
            file: files[0],
            fileSelector: fileSelector
        });
        
        this.uploader.on('uploadcomplete', this.onUploadComplete, this);
        this.uploader.on('uploadfailure', this.onUploadFail, this);
        
        this.loadMask.show();
        
        var uploadKey = Tine.Tinebase.uploadManager.queueUpload(this.uploader);
        var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);
        
        if (this.ctxMenu) {
            this.ctxMenu.hide();
        }
    },
    
    onUploadComplete: function(upload, record) {
        this.uploader.un('uploadcomplete', this.onUploadComplete, this);
        this.uploader.un('uploadfailure', this.onUploadFail, this);
        
        var value = new Ext.ux.util.ImageURL({
            id: record.get('tempFile').id,
            width: this.width,
            height: this.height,
            ratiomode: 0
        });
        this.setValue(value);
    },
    
    /**
     * @private
     */
    onUploadFail: function () {
        this.uploader.un('uploadcomplete', this.onUploadComplete, this);
        this.uploader.un('uploadfailure', this.onUploadFail, this);
        
        Ext.MessageBox.alert(_('Upload Failed'), _('Could not upload image. Please notify your Administrator')).setIcon(Ext.MessageBox.ERROR);
    },
    /**
     * executed on image contextmenu
     * @private
     */
    onContextMenu: function (e, input) {
        e.preventDefault();
        if (this.ctxMenu) {
            this.ctxMenu.destroy();
        }
        this.browsePlugin.hideLayer();
        var upload = new Ext.menu.Item({
            text: _('Change Image'),
            iconCls: 'action_uploadImage',
            handler: this.onFileSelect,
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({})]
        });
        this.ctxMenu = new Ext.menu.Menu({
            items: [upload, {
                text: _('Crop Image'),
                iconCls: 'action_cropImage',
                scope: this,
                disabled: true, 
                handler: function () {
                    var cropper = new Ext.ux.form.ImageCropper({
                        imageURL: this.value
                    });
                    
                    var dlg = new Tine.widgets.dialog.EditRecord({
                        handlerScope: this,
                        handlerCancle: this.close,
                        items: cropper
                    });
                    
                    var win = Tine.WindowFactory.getWindow({
                        width: 320,
                        height: 320,
                        title: _('Crop Image'),
                        layout: 'fit',
                        items: dlg
                    });
                }
            }, {
                text: _('Delete Image'),
                iconCls: 'action_delete',
                disabled: this.value === this.defaultImage,
                scope: this,
                handler: function () {
                    this.setValue('');
                }
                
            }, {
                text: _('Show Original Image'),
                iconCls: 'action_originalImage',
                disabled: this.value === this.defaultImage,
                scope: this,
                handler: this.downloadImage
                
            }]
        });
        this.ctxMenu.showAt(e.getXY());
        
    },
    
    downloadImage: function () {
        var url = Ext.apply(this.value, {
            height: -1,
            width: -1
        }).toString();
        
        var window = Tine.WindowFactory.getWindow({
            url: url,
            name: 'showImage',
            width: 800,
            height: 600
        });
    },
    
    /**
    * clean up
    */
    onDestroy: function() {
        this.imageCt.remove();
        this.buttonCt.remove();
        this.textCt.remove();
        this.imageCt = null;
        this.buttonCt = null;
        this.textCt = null;
        this.loadMask.destroy();
        this.ctxMenu.destroy();
        this.uploader = null;
        
        Ext.ux.form.ImageField.superclass.destroy.call(this);
    }
});

Ext.ns('Ext.ux.util');

/**
 * this class represents an image URL
 */
Ext.ux.util.ImageURL = function (config) {
    Ext.apply(this, config, {
        url: 'index.php',
        method: 'Tinebase.getImage',
        application: 'Tinebase',
        location: 'tempFile',
        width: 90,
        height: 120,
        ratiomode: 0,
        mtime: new Date().getTime()
    });
};
/**
 * generates an imageurl according to the class members
 * 
 * @return {String}
 */
Ext.ux.util.ImageURL.prototype.toString = function () {
    return this.url + 
        "?method=" + this.method + 
        "&application=" + this.application + 
        "&location=" + this.location + 
        "&id=" + this.id + 
        "&width=" + this.width + 
        "&height=" + this.height + 
        "&ratiomode=" + this.ratiomode + 
        "&mtime=" + this.mtime;
};
/**
 * parses an imageurl
 * 
 * @param  {String} url
 * @return {Ext.ux.util.ImageURL}
 */
Ext.ux.util.ImageURL.prototype.parseURL = function (url) {
    var urlString = url.toString(),
        params = {},
        lparams = urlString.substr(urlString.indexOf('?') + 1).split('&');
        
    for (var i = 0, j = lparams.length; i < j; i += 1) {
        var param = lparams[i].split('=');
        params[param[0]] = Ext.util.Format.htmlEncode(param[1]);
    }
    return new Ext.ux.util.ImageURL(params);
};

