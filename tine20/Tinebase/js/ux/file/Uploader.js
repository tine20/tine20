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
         'uploadcomplete',
        /**
         * @event uploadfailure
         * Fires when the upload failed 
         * @param {Ext.ux.file.Uploader} this
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
     * @return {Ext.ux.file.Uploader} this
     */
    upload: function() {
        if (XMLHttpRequest) {
            return this.html5upload();
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
    html5upload: function() {
        this.record = new Ext.ux.file.Uploader.file({
            input: this.input,
            status: 'uploading'
        });
        
        var file = this.input.dom.files[0];
        
        var xhr = new XMLHttpRequest,
            upload = xhr.upload;
        
        var scope = this;
        upload.onload = function(e) {
            setTimeout(function(){
                if(xhr.readyState === 4){
                    scope.onUploadSuccess.call(scope, xhr);
                } else
                    setTimeout(arguments.callee, 15);
            }, 15);
        };
        
        xhr.open("post", this.url + '?method=Tinebase.uploadTempFile', true);
        xhr.setRequestHeader("Cache-Control", "no-cache");
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("X-File-Name", file.name);
        xhr.setRequestHeader("X-File-Type", file.type);
        xhr.setRequestHeader("X-File-Size", file.size); // not standard!!!
        xhr.setRequestHeader("Content-Type", "multipart/form-data");
        xhr.send(file);
        
        return this;
    },
    
    /**
     * uploads in a html4 fashion
     * 
     * @return {this}
     */
    html4upload: function() {
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
            //failure: this.onUploadFail,
            params: {
                method: 'Tinebase.uploadTempFile',
                requestType: 'HTTP'
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
    {name: 'status', type: 'text', system: true},
    {name: 'tempFile', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'request', system: true}
]);