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
         * @param {Ext.Record} Ext.ux.file.Uploader.file
         */
         'uploadcomplete',
         
        /**
         * @event uploadfailure
         * Fires when the upload failed 
         * @param {Ext.ux.file.Uploader} this
         * @param {Ext.Record} Ext.ux.file.Uploader.file
         */
         'uploadfailure'
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
     * 
     * @param  {index} idx which file (optional for html5 uploads)
     * @return {Ext.ux.file.Uploader.file}
     */
    upload: function(idx) {
        if (XMLHttpRequest) {
            return this.html5upload(idx);
        } else {
            return this.html4upload();
        }
    },
    
    /**
     * 2010-01-26 Current Browsers implemetation state of:
     *  http://dev.w3.org/2006/webapi/FileAPI/
     *  
     *  Interface: File | Blob | FileReader | FileReaderSync | FileError
     *  FF       : yes  | no   | no         | no             | no       
     *  safari   : yes  | no   | no         | no             | no       
     *  chrome   : yes  | no   | no         | no             | no       
     *  
     *  => no json rpc style upload possible
     *  => no chunked uploads posible
     *  
     *  But all of them implement XMLHttpRequest:
     *   http://www.w3.org/TR/XMLHttpRequest/
     *  => the only way of uploading is using the XMLHttpRequest.
     */
    html5upload: function(idx) {
        var file = this.input.dom.files[idx || 0];
        
        var xhr = new XMLHttpRequest,
            upload = xhr.upload;
        
        this.record = new Ext.ux.file.Uploader.file({
            name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
            type: (file.type ? file.type : file.fileType) || this.getFileCls(), // missing if safari and chrome
            size: (file.size ? file.size : file.fileSize) || 0, // non standard but all have it ;-)
            status: 'uploading',
            progress: 0,
            input: this.input,
            request: xhr
        });
        
        var scope = this;
        upload.onload = function(e) {
            if(xhr.readyState === 4){
                scope.onUploadSuccess.call(scope, xhr);
            } else {
                setTimeout(arguments.callee, 15);
            }
        };
        
        xhr.open("post", this.url + '?method=Tinebase.uploadTempFile', true);
        xhr.setRequestHeader("Cache-Control", "no-cache");
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("X-File-Name", this.record.get('name'));
        xhr.setRequestHeader("X-File-Type", this.record.get('type'));
        xhr.setRequestHeader("X-File-Size", this.record.get('size'));
        xhr.setRequestHeader("Content-Type", "multipart/form-data");
        xhr.send(file);
        
        return this.record;
    },
    
    /**
     * uploads in a html4 fashion
     * 
     * @return {Ext.ux.file.Uploader.file}
     */
    html4upload: function() {
        var form = this.createForm();
        form.appendChild(this.input);
        this.record = new Ext.ux.file.Uploader.file({
            name: this.getName(),
            size: 0,
            type: this.getFileCls(),
            input: this.input,
            form: form,
            status: 'uploading',
            progress: 0
        });
        
        var request = Ext.Ajax.request({
            isUpload: true,
            method:'post',
            form: form,
            scope: this,
            success: this.onUploadSuccess,
            //failure: this.onUploadFail,
            params: {
                method: 'Tinebase.uploadTempFile',
                requestType: 'HTTP'
            }
        });
        
        this.record.set('request', request);
        return this.record;
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
        response = Ext.util.JSON.decode(response.responseText);
        if (response.status && response.status !== 'success') {
            this.onUploadFail();
        } else {
            this.record.set('status', 'complete');
            this.record.set('tempFile', response.tempFile);
            
            this.fireEvent('uploadcomplete', this, this.record);
        }
    },
    /**
     * executed if a file upload failed
     */
    onUploadFail: function(response, request) {
        this.record.set('status', 'failure');
        
        this.fireEvent('uploadfailure', this, this.record);
    },
    /**
     * get file name
     * @return {String}
     */
    getFileName:function() {
        return this.input.getValue().split(/[\/\\]/).pop();
    },
    /**
     * get file path (excluding the file name)
     * @return {String}
     */
    getFilePath:function() {
        return this.input.getValue().replace(/[^\/\\]+$/,'');
    },
    /**
     * returns file class based on name extension
     * @return {String} class to use for file type icon
     */
    getFileCls: function() {
        var fparts = this.getFileName().split('.');
        if(fparts.length === 1) {
            return '';
        }
        else {
            return fparts.pop().toLowerCase();
        }
    },
    isImage: function() {
        var cls = this.getFileCls();
        return (cls == 'jpg' || cls == 'gif' || cls == 'png' || cls == 'jpeg');
    }
});

Ext.ux.file.Uploader.file = Ext.data.Record.create([
    {name: 'id', type: 'text', system: true},
    {name: 'name', type: 'text', system: true},
    {name: 'size', type: 'number', system: true},
    {name: 'type', type: 'text', system: true},
    {name: 'status', type: 'text', system: true},
    {name: 'progress', type: 'number', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'request', system: true},
    {name: 'tempFile', system: true}
]);