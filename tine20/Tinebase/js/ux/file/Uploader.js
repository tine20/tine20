/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux.file');

/**
 * a simple file uploader
 * 
 * objects of this class represent a single file uplaod
 */
Ext.ux.file.Uploader = function(config) {
    Ext.apply(this, config);

    Ext.ux.file.Uploader.superclass.constructor.apply(this, arguments);
    
    this.addEvents(
        /**
         * @event uploadcomplete
         * Fires when the upload was done successfully 
         * @param {Ext.ux.file.Uploader} this
         */
         'uploadcomplete'
    );
};
 
Ext.extend(Ext.ux.file.Uploader, Ext.util.Observable, {
    /**
     * @cfg {Int} maxFileSize the maximum file size in bytes
     */
    maxFileSize: 2097152,
    /**
     * @cfg {String} url the url we upload to
     */
    url: 'index.php',
    
    /**
     * creates a form where the upload takes place in
     * @private
     */
    createForm: function() {
        var form = Ext.getBody().createChild({
            tag:'form',
            action:this.url,
            method:'post',
            cls:'x-hidden',
            id:Ext.id(),
            cn:[{
                tag: 'input',
                type: 'hidden',
                name: 'MAX_FILE_SIZE',
                value: this.maxFileSize
            }]
        });
        return form;
    },
    /**
     * perform the upload
     * @return {Ext.ux.file.Uploader} this
     */
    upload: function() {
        var form = this.createForm();
        form.appendChild(this.input);
        this.record = new Ext.ux.file.Uploader.file({
            input: this.input,
            form: form,
            status: 'uploading'
        });
        
        var request = Ext.Ajax.request({
            isUpload: true,
            method:'post',
            form: form,
            scope: this,
            success: this.onUploadSuccess,
            params: {
                method: 'Tinebase.uploadTempFile',
            }
        });
        
        this.record.set('request', request);
        return this;
    },
    /**
     * returns record with info about this upload
     * @return {Ext.data.Record}
     */
    getRecord: function() {
        return this.record;
    },
    /**
     * executed if a file got uploaded successfully
     */
    onUploadSuccess: function(response, request) {
        var tempFile = Ext.util.JSON.decode(response.responseText).tempFile;
        this.record.set('status', 'complete');
        this.record.set('tempFile', tempFile);
        
        this.fireEvent('uploadcomplete', this, this.record);
    }
});

Ext.ux.file.Uploader.file = Ext.data.Record.create([
    {name: 'id', type: 'text', system: true},
    {name: 'status', type: 'text', system: true},
    {name: 'tempFile', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'request', system: true}
]);