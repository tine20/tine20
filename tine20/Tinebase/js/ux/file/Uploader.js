/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux.file');

/**
 * a simple file uploader
 * objects of this class represent a single file uplaod
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.Uploader
 * @extends     Ext.util.Observable
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
         'uploadfailure',
        /**
         * @event uploadprogress
         * Fires on upload progress (html5 only)
         * @param {Ext.ux.file.Uploader} this
         * @param {Ext.Record} Ext.ux.file.Uploader.file
         * @param {XMLHttpRequestProgressEvent}
         */
         'uploadprogress'
    );
};
 
Ext.extend(Ext.ux.file.Uploader, Ext.util.Observable, {
    /**
     * @cfg {Int} maxFileSize the maximum file size in bytes
     */
    maxFileSize: 20971520, // 20 MB
    /**
     * @cfg {String} url the url we upload to
     */
    url: 'index.php',
    /**
     * @cfg {Ext.ux.file.BrowsePlugin} fileSelector
     * a file selector
     */
    fileSelector: null,
    /**
     * @cfg {String} chunkName the prefix name for the uploaded chunks on the server
     */
    chunkName: 'tine_temp_chunks_',
    
    /**
     * The following is a conservative set of three functions you can use to test host objects.
     * These reduce the chance of false positives. You can make them more permissive if you 
     * learn about a browser with a different typeof result that should be allowed to pass the test.
     *
     * @see http://michaux.ca/articles/feature-detection-state-of-the-art-browser-scripting
     */
    isHostMethod: function (object, property) {
        var t = typeof object[property];
        
        return t == 'function' || (!!(t == 'object' && object[property])) || t == 'unknown';
    },
    
    isHostCollection: function (object, property) {
        var t = typeof object[property];  
        
        return (!!(t == 'object' && object[property])) || t == 'function';
    },
    
    isHostObject: function (object, property) {
        return !!(typeof(object[property]) == 'object' && object[property]);
    },
    
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
     * @param  {FILE} file to upload (optional for html5 uploads)
     * @return {Ext.Record} Ext.ux.file.Uploader.file
     */
    upload: function(file) {
        if ((
            (! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
            (Ext.isGecko && window.FileReader) // FF
        ) && file) {
            if (this.isHostMethod('Blob', 'slice')) {
                return this.html5ChunkedUpload(file);
            } else {
                return this.html5upload(file);
            }
        } else {
            return this.html4upload();
        }
    },
    
    /**
     * 2010-01-26 Current Browsers implemetation state of:
     *  http://www.w3.org/TR/FileAPI
     *  
     *  Interface: File | Blob | FileReader | FileReaderSync | FileError
     *  FF       : yes  | no   | no         | no             | no       
     *  safari   : yes  | no   | no         | no             | no       
     *  chrome   : yes  | no   | no         | no             | no       
     *  
     *  => no json rpc style upload possible
     *  => no chunked uploads posible
     *  
     *  But all of them implement XMLHttpRequest Level 2:
     *   http://www.w3.org/TR/XMLHttpRequest2/
     *  => the only way of uploading is using the XMLHttpRequest Level 2.
     */
    html5upload: function(file) {
        var fileRecord = new Ext.ux.file.Uploader.file({
            name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
            type: (file.type ? file.type : file.fileType) || this.fileSelector.getFileCls(), // missing if safari and chrome
            size: (file.size ? file.size : file.fileSize) || 0, // non standard but all have it ;-)
            status: 'uploading',
            progress: 0,
            input: this.getInput()
        });
        
        var conn = new Ext.data.Connection({
            disableCaching: true,
            method: 'POST',
            url: this.url + '?method=Tinebase.uploadTempFile',
            timeout: 300000, // 5 mins
            defaultHeaders: {
                "Content-Type"          : "application/x-www-form-urlencoded",
                "X-Tine20-Request-Type" : "HTTP",
                "X-Requested-With"      : "XMLHttpRequest"
            }
        });
        
        var transaction = conn.request({
            headers: {
                "X-File-Name"           : fileRecord.get('name'),
                "X-File-Type"           : fileRecord.get('type'),
                "X-File-Size"           : fileRecord.get('size')
            },
            xmlData: file,
            success: this.onUploadSuccess.createDelegate(this, [fileRecord], true),
            failure: this.onUploadFail.createDelegate(this, [fileRecord], true),
            fileRecord: fileRecord
        });
        
        var upload = transaction.conn.upload;
        
        upload['onprogress'] = this.onUploadProgress.createDelegate(this, [fileRecord], true);
        
        return fileRecord;
    },
    
    /**
     * 2011-03-04 Current Browsers implemetation state of:
     *  http://www.w3.org/TR/FileAPI
     *  
     *  Interface    : File | Blob                         | FileReader | FileReaderSync | FileError
     *  FF 3         : yes  | no                           | yes        | no             | yes      
     *  FF 4 beta 10 : yes  | yes                          | yes        | no             | yes      
     *  safari       : yes  | yes (without slice method)   | no         | no             | no       
     *  chrome       : yes  | yes                          | yes        | no             | yes      
     * 
     * Testet on Mac OS 10.6
     */
    html5ChunkedUpload: function(file) {
        var fileRecord = new Ext.ux.file.Uploader.file({
            name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
            type: (file.type ? file.type : file.fileType) || this.fileSelector.getFileCls(), // missing if safari and chrome
            size: (file.size ? file.size : file.fileSize) || 0, // non standard but all have it ;-)
            status: 'uploading',
            progress: 0,
            input: this.getInput()
        });
        
        // get the overall filesize
        this.uploadFileSize = fileRecord.get('size');
        
        // get the original filename
        this.uploadFilename = fileRecord.get('name');
        
        // get the original filetype
        this.uploadFiletype = fileRecord.get('type');
        
        // get the amount of chunks
        this.chunkCount = parseInt(this.uploadFileSize / this.maxFileSize, 10);
        
        // initialize some class member
        this.actualChunkNumber = 0;
        this.chunks = [];
        
        // prepare the chunks
        for (i = 0; i <= this.chunkCount; i++) {
            if (i === this.chunkCount) {
                this.chunks[i] = file.slice((i * this.maxFileSize), this.uploadFileSize);
            } else {
                this.chunks[i] = file.slice((i * this.maxFileSize), this.maxFileSize);
            }
        }
        
        // start the first part of the chunked upload
        this.chunkUpload(fileRecord);
    },
    
    /**
     * chunked upload method
     *
     * Need this to call it from the success method of ajax request
     */
    chunkUpload : function(fileRecord) {
        // Upload chunk if there is one left.
        if(this.actualChunkNumber <= this.chunkCount) {
            this.conn = new Ext.data.Connection({
                disableCaching: true,
                method: 'POST',
                //url: this.url + '?method=Tinebase.uploadTempFile',
                url: 'upload.php',
                timeout: 300000, // 5 mins
                defaultHeaders: {
                    "Content-Type"          : "application/x-www-form-urlencoded",
                    "X-Tine20-Request-Type" : "HTTP",
                    "X-Requested-With"      : "XMLHttpRequest"
                }
            });
            
            this.conn.request({
                headers: {
                    "X-Chunk-Count"         : this.chunks.length,
                    "X-Chunk-Number"        : this.actualChunkNumber,
                    "X-Chunk-Size"          : this.chunks[this.actualChunkNumber].size,
                    "X-Chunk-Name"          : this.chunkName
                },
                xmlData: this.chunks[this.actualChunkNumber],
                success: this.onChunkUploadSuccess.createDelegate(this, [fileRecord], true),
                failure: this.onChunkUploadFail.createDelegate(this, [fileRecord], true),
                fileRecord: fileRecord
            });
        } else {
            // There are no more chunk's. Now tell the server that upload is finished
            // and chunk's can concatinated.
            this.conn = new Ext.data.Connection({
                disableCaching: true,
                method: 'POST',
                //url: this.url + '?method=Tinebase.uploadTempFile',
                url: 'upload.php',
                timeout: 300000, // 5 mins
                defaultHeaders: {
                    "Content-Type"          : "application/x-www-form-urlencoded",
                    "X-Tine20-Request-Type" : "HTTP",
                    "X-Requested-With"      : "XMLHttpRequest"
                }
            });
            
            this.conn.request({
                headers: {
                    "X-Chunk-finished"      : true,
                    "X-Chunk-Count"         : this.chunks.length,
                    "X-Chunk-File-Name"     : this.uploadFilename,
                    "X-Chunk-File-Size"     : this.uploadFileSize,
                    "X-Chunk-File-Type"     : this.uploadFileType,
                    "X-Chunk-Name"          : this.chunkName
                }
            });
        }
    },
    
    /**
     * uploads in a html4 fashion
     * 
     * @return {Ext.data.Connection}
     */
    html4upload: function() {
        var form = this.createForm();
        var input = this.getInput();
        form.appendChild(input);
        
        var fileRecord = new Ext.ux.file.Uploader.file({
            name: this.fileSelector.getFileName(),
            size: 0,
            type: this.fileSelector.getFileCls(),
            input: input,
            form: form,
            status: 'uploading',
            progress: 0
        });
        
        Ext.Ajax.request({
            fileRecord: fileRecord,
            isUpload: true,
            method:'post',
            form: form,
            success: this.onUploadSuccess.createDelegate(this, [fileRecord], true),
            failure: this.onUploadFail.createDelegate(this, [fileRecord], true),
            params: {
                method: 'Tinebase.uploadTempFile',
                requestType: 'HTTP'
            }
        });
        
        return fileRecord;
    },
    
    /*
    onLoadStart: function(e, fileRecord) {
        this.fireEvent('loadstart', this, fileRecord, e);
    },
    */
    
    onUploadProgress: function(e, fileRecord) {
        var percent = Math.round(e.loaded / e.total * 100);
        fileRecord.set('progress', percent);
        this.fireEvent('uploadprogress', this, fileRecord, e);
    },
    
    /**
     * executed if a file got uploaded successfully
     */
    onUploadSuccess: function(response, options, fileRecord) {
        response = Ext.util.JSON.decode(response.responseText);
        if (response.status && response.status !== 'success') {
            this.onUploadFail(response, options, fileRecord);
        } else {
            fileRecord.beginEdit();
            fileRecord.set('status', 'complete');
            fileRecord.set('tempFile', response.tempFile);
            fileRecord.set('name', response.tempFile.name);
            fileRecord.set('size', response.tempFile.size);
            fileRecord.set('type', response.tempFile.type);
            fileRecord.set('path', response.tempFile.path);
            fileRecord.commit(false);
            this.fireEvent('uploadcomplete', this, fileRecord);
        }
    },
    
    /**
     * executed if a file upload failed
     */
    onUploadFail: function(response, options, fileRecord) {
        fileRecord.set('status', 'failure');
        
        this.fireEvent('uploadfailure', this, fileRecord);
    },
    
    /**
     * executed if a chunk got uploaded successfully
     */
    onChunkUploadSuccess: function(response, options, param) {
        response = Ext.util.JSON.decode(response.responseText);
        if(response.success !== true) {
            this.onChunkUploadFail(response, options, param);
        } else {
            this.actualChunkNumber++;
            this.chunkUpload();
        }
    },
    
    /**
     * executed if a chunk upload failed
     */
    onChunkUploadFail: Ext.emptyFn,
    
    // private
    getInput: function() {
        if (! this.input) {
            this.input = this.fileSelector.detachInputFile();
        }
        
        return this.input;
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
    {name: 'path', system: true},
    {name: 'tempFile', system: true}
]);

Ext.ux.file.Uploader.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type']);
};
