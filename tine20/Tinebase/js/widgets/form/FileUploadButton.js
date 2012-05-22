/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

/**
 * Upload Button
 * 
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.FileUploadButton
 * @extends     Ext.Button
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.form.FileUploadButton = Ext.extend(Ext.Button, {
    /**
     * @cfg {Array} allowedTypes
     * array with allowed file types (for example: ['jpg', 'gif', 'png']
     */
    allowedTypes: null,
    
    /**
     * @property browsePlugin
     * @type Ext.ux.file.BrowsePlugin
     */
    browsePlugin: null,
    
    // private config overrides
    iconCls: 'action_import',
    
    /**
     * init this upload button
     */
    initComponent: function() {
        this.origHandler = this.handler;
        this.origScope = this.scope;
        this.origIconCls = this.iconCls;
        
        this.handler = this.onFileSelect;
        this.scope = this;
        
        this.browsePlugin = new Ext.ux.file.BrowsePlugin({
            multiple: false
        });
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.browsePlugin);
        
        Tine.widgets.form.FileUploadButton.superclass.initComponent.call(this);
    },
    
    /**
     * called when a file got selected
     * 
     * @param {ux.BrowsePlugin} fileSelector
     * @param {Ext.EventObj} e
     */
    onFileSelect: function(fileSelector, event) {
        if (Ext.isArray(this.allowedTypes) && this.allowedTypes.indexOf(fileSelector.getFileCls()) < 0) {
            Ext.MessageBox.alert(_('Wrong File Type'), [_('Please select a file with one of the following extensions:'), '<br />', this.allowedTypes].join('')).setIcon(Ext.MessageBox.ERROR);
            return;
        }
        
        this.upload = new Ext.ux.file.Upload({
            fileSelector: fileSelector
        });
        this.upload.on('uploadcomplete', this.onUploadComplete, this);
        
        this.setIconClass('x-tinebase-uploading');
        this.upload.upload();
    },
    
    /**
     * called when the upload completed
     * 
     * @param {Ext.ux.file.Upload} upload
     * @param {Ext.ux.file.Upload.file} fileRecord
     */
    onUploadComplete: function(upload, fileRecord) {
        this.fileRecord = fileRecord;
        
        this.setText([fileRecord.get('name'), ' (', Tine.Tinebase.common.byteRenderer(fileRecord.get('size')), ')'].join(''));
//        this.setIconClass(this.origIconCls);
        this.setIconClass('action_saveAndClose');
        
        // autowidth does not work
        this.setWidth(Ext.util.TextMetrics.measure(this.btnEl, this.text).width + 20);
        
        if (Ext.isFunction(this.origHandler)) {
            this.origHandler.apply(this.origScope, arguments);
        }
    },
    
    getTempFileId: function() {
        return this.fileRecord.get('tempFile').id;
    }
    
});

Ext.reg('tw.uploadbutton', Tine.widgets.form.FileUploadButton);