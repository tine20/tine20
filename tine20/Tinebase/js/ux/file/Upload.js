/* Tine 2.0
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
Ext.ux.file.Upload = function(config, file, id) {
    Ext.apply(this, config);
    
    Ext.ux.file.Upload.superclass.constructor.apply(this, arguments);
    
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
        
    this.file = file;
    this.maxChunkSize = this.maxPostSize - 16384;
    
    if(id && id > -1) {
        this.id = id;
    }
    
    this.tempFiles = new Array();
    
};
 

Ext.extend(Ext.ux.file.Upload, Ext.util.Observable, {
    
    id: -1,
    
    /**
     * @cfg {Int} maxFileUploadSize the maximum file size for traditinal form updoads
     */
    maxFileUploadSize: 20971520, // 20 MB
    /**
     * @cfg {Int} maxPostSize the maximum post size used for html5 uploads
     */
    maxPostSize: 20971520, // 20 MB
    /**
     * @cfg {Int} maxChunkSize the maximum chunk size used for html5 uploads
     */
    maxChunkSize: 20955136,
    /**
     * @cfg {Int} minChunkSize the minimal chunk size used for html5 uploads
     */
    minChunkSize: 102400,
    
    /**
     *  max number of upload retries
     */
    MAX_RETRY_COUNT: 10,
    
    /**
     * @cfg {String} url the url we upload to
     */
    url: 'index.php',
    /**
     * @cfg {Ext.ux.file.BrowsePlugin} fileSelector
     * a file selector
     */
    fileSelector: null,
    
    
    fileRecord: null,
    
    
    currentChunk: null,
    
    
    file: null,
    
    
    paused: false,
    
    
    tempFiles: new Array(),
    
    uploadSuccesFunction: null,
    
    uploadFailureFunction: null,
    
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
                value: this.maxFileUploadSize
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
    upload: function() {
                           
        if ((
                (! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
                (Ext.isGecko && window.FileReader) // FF
        ) && this.file) {

            if (this.isHtml5ChunkedUpload()) {

                // calculate optimal maxChunkSize       
                var fileSize = (this.file.size ? this.file.size : this.file.fileSize);
                var chunkMax = this.maxChunkSize;
                var chunkMin = this.minChunkSize;       
                var actualChunkSize = this.maxChunkSize;

                if(fileSize > 5 * chunkMax) {
                    actualChunkSize = chunkMax;
                }
                else {
                    actualChunkSize = Math.max(chunkMin, fileSize / 5);
                }       
                this.maxChunkSize = actualChunkSize;
                
                this.createFileRecord(false);
                this.html5ChunkedUpload();
                return this.fileRecord;

            } else {
                this.createFileRecord(false);
                this.html5upload();
                return this.fileRecord;
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
    html5upload: function() {
                    
        if(this.maxPostSize/1 < this.file.size/1 && !this.isHtml5ChunkedUpload()) {
            this.fileRecord.html5upload = true;
            this.onUploadFail(null, null, this.fileRecord);
            return this.fileRecord;
        }
        
        var defaultHeaders = {
            "Content-Type"          : "application/x-www-form-urlencoded",
            "X-Tine20-Request-Type" : "HTTP",
            "X-Requested-With"      : "XMLHttpRequest"
        };
        
        var xmlData = this.file;
               
        if(this.isHtml5ChunkedUpload()) {
            defaultHeaders["X-Chunk-Count"] = this.fileRecord.get('currentChunkIndex');
            defaultHeaders["X-Chunk-finished"] = this.fileRecord.get('lastChunk');
            xmlData = this.currentChunk;
        }

        var conn = new Ext.data.Connection({
            disableCaching: true,
            method: 'POST',
            url: this.url + '?method=Tinebase.uploadTempFile',
            timeout: 300000, // 5 mins
            defaultHeaders: defaultHeaders
        });
                
        var transaction = conn.request({
            headers: {
                "X-File-Name"           : this.fileRecord.get('name'),
                "X-File-Type"           : this.fileRecord.get('type'),
                "X-File-Size"           : this.fileRecord.get('size')
            },
            xmlData: xmlData,
            success: this.onUploadSuccess.createDelegate(this, null, true),
            failure: this.onUploadFail.createDelegate(this, null, true) 
        });       

        return this.fileRecord;
    },

    html5ChunkedUpload: function(resumeUpload) {
                    
        if(!resumeUpload) {
            this.prepareChunk();        
        }       
        this.html5upload();                
    },
    
    resumeUpload: function() {
        this.setPaused(false);
        this.html5ChunkedUpload(true);
    },
    
    prepareChunk: function() {
        
        this.fileRecord.beginEdit();
        
        if(this.fileRecord.get('lastChunkUploadFailed')) {
            this.fileRecord.set('currentChunkPosition', Math.max(0
                    , this.fileRecord.get('currentChunkPosition') - this.fileRecord.get('currentChunkSize')));

            this.fileRecord.set('currentChunkSize', Math.max(this.minChunkSize, this.fileRecord.get('currentChunkSize') / 2));
        }
        else {
            this.fileRecord.set('currentChunkSize', Math.min(this.maxChunkSize, this.fileRecord.get('currentChunkSize') * 2));
        }
        
        var nextChunkPosition = Math.min(this.file.fileSize, this.fileRecord.get('currentChunkPosition') +  this.fileRecord.get('currentChunkSize'));
        var newChunk = this.sliceFile(this.file, this.fileRecord.get('currentChunkPosition'), nextChunkPosition);
        
        if(nextChunkPosition/1 == this.file.fileSize/1) {
            this.fileRecord.set('lastChunk', true);
        }
                     
        this.fileRecord.set('currentChunkPosition', nextChunkPosition);
        this.fileRecord.set('currentChunk', newChunk);
       
        this.fileRecord.endEdit();
        this.currentChunk = newChunk;

    },
    
    finishUploadRecord: function(success) {
        
        if(success) {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'complete');
            this.fileRecord.set('progress', 100);
            this.fileRecord.commit(false);
        }
        else {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'failure');
            this.fileRecord.set('progress', -1);
            this.fileRecord.commit(false);
        }
        
        
        // Tine.Tinebase.uploadManager.unregisterUpload(this.id);
        
    },
    
   
    /**
     * executed if a chunk or file got uploaded successfully
     */
    onUploadSuccess: function(response, options, fileRecord) {
        
        response = Ext.util.JSON.decode(response.responseText);
        
        this.fileRecord.beginEdit();
        this.fileRecord.set('tempFile', response.tempFile);
        this.fileRecord.set('name', response.tempFile.name);
        this.fileRecord.set('size', response.tempFile.size);
        this.fileRecord.set('type', response.tempFile.type);
        this.fileRecord.set('path', response.tempFile.path);
        
        if(!this.isHtml5ChunkedUpload()) {
            this.fileRecord.set('status', 'complete');
        }
        
        this.fileRecord.commit(false);
        
        if(!this.isHtml5ChunkedUpload()) {
            this.fireEvent('uploadcomplete', this, this.fileRecord);
            if(response.status && response.status !== 'success') {
                this.onUploadFail(response, options, fileRecord);
            } 
        }       
        else {
            if(response.status && response.status !== 'success') {
                this.onChunkUploadFail(response, options, fileRecord);
            } 
            else if(!this.isPaused()) {

                this.addTempfile(this.fileRecord.get('tempFile'));

                var percent = parseInt(this.fileRecord.get('currentChunkPosition') * 100 / this.fileRecord.get('size')/1);

                if(this.fileRecord.get('lastChunk')) {
                    percent = 99;
                }

                this.fileRecord.beginEdit();
                this.fileRecord.set('progress', percent);
                this.fileRecord.commit(false);


                if(this.fileRecord.get('lastChunk')) {

                    this.fireEvent('uploadcomplete', this, this.fileRecord);               
//                  Tine.Tinebase.joinTempFiles(this.tempFiles, this.finishUploadRecord.createDelegate(this));
                    Ext.Ajax.request({
                        timeout: 10*60*1000,
                        params: {
                            method: 'Tinebase.joinTempFiles',
                            tempFilesData: this.tempFiles
                        },
                        success: this.finishUploadRecord.createDelegate(this, [true]), 
                        failure: this.finishUploadRecord.createDelegate(this, [false])
                    });
                }
                else {
                    this.prepareChunk();
                    this.html5upload();
                }                                               
            }  
        }
    },
    
      
    /**
     * executed if a chunk / file upload failed
     */
    onUploadFail: function(response, options, fileRecord) {

        if (this.isHtml5ChunkedUpload()) {
            this.fileRecord.set('lastChunkUploadFailed', true);
            this.fileRecord.set('retryCount', this.fileRecord.get('retryCount') + 1);
            if (this.fileRecord.get('retryCount') > this.MAX_RETRY_COUNT) {
                alert("Upload failed!");
            }
            else {
                this.fileRecord.set('lastChunkUploadFailed', true);
                this.html5upload();
            }
        }
        else {
            this.fileRecord.set('status', 'failure');
            this.fireEvent('uploadfailure', this, fileRecord);
        }
    },
    
    
    /**
     * uploads in a html4 fashion
     * 
     * @return {Ext.data.Connection}
     */
    html4upload: function() {
                
        alert("html4upload");
        
        var form = this.createForm();
        var input = this.getInput();
        form.appendChild(input);
        
        this.fileRecord = new Ext.ux.file.Uploader.file({
            name: this.fileSelector.getFileName(),          
            size: 0,
            type: this.fileSelector.getFileCls(),
            input: input,
            form: form,
            status: 'uploading',
            progress: 0
        });
        
        
        if(this.maxFileUploadSize/1 < this.file.size/1) {
            this.fileRecord.html4upload = true;
            this.onUploadFail(null, null, this.fileRecord);
            return this.fileRecord;
        }
        
        Ext.Ajax.request({
            fileRecord: this.fileRecord,
            isUpload: true,
            method:'post',
            form: form,
            success: this.onUploadSuccess.createDelegate(this, [this.fileRecord], true),
            failure: this.onUploadFail.createDelegate(this, [this.fileRecord], true),
            params: {
                method: 'Tinebase.uploadTempFile',
                requestType: 'HTTP'
            }
        });
        
        return this.fileRecord;
    },
    
    createFileRecord: function(pending) {
               
        var status = "uploading";
        if(pending) {
            status = "pending";
        }

        this.fileRecord = new Ext.ux.file.Uploader.file({
            name: this.file.name ? this.file.name : this.file.fileName,  // safari and chrome use the non std. fileX props
            type: (this.file.type ? this.file.type : this.file.fileType), // missing if safari and chrome
            size: (this.file.size ? this.file.size : this.file.fileSize) || 0, // non standard but all have it ;-)
            status: status,
            progress: 0,
            input: this.getInput(),
            currentChunkSize: this.maxChunkSize,
            currentChunkPosition: 0,
            currentChunkIndex: 0,
            lastChunkUploadFailed: false,
            uploadKey: this.id
        });
    },
    
    addTempfile: function(tempFile) {
              
        this.tempFiles.push(tempFile);               
        return true;
    },
    
    getTempfiles: function() {
        return this.tempFiles;
    },
    
    finishUpload: function(uploadKey) {     
        Tine.Tinebase.joinTempFiles(this.uploads[uploadKey].tempFileArray);
        this.removeUploadSet(uploadKey);
    },
    
    setPaused: function(paused) {
        this.paused = paused;
    },
    
    isPaused: function() {
        return this.paused;
    },
    
    isHtml5ChunkedUpload: function() {
        
//        if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {
        console.log("isHtml5ChunkedUpload");
        
        if(window.File == undefined) return false;
        if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {

            return true;
        }
        else {
            return false;
        }       
    },
    
    // private
    getInput: function() {
        if (! this.input) {
            this.input = this.file;
        }
        
        return this.input;
    },

    sliceFile: function(file, start, end, type) {
        
        if(file.mozSlice) {
            return file.mozSlice(start, end, type);
        }
        else if(file.webkitSlice) {
            return file.webkitSlice(start, end);
        }
        else {
            return false;
        }
        
    }
            
});

Ext.ux.file.Uploader.file = Ext.data.Record.create([
    {name: 'id', type: 'text', system: true},
    {name: 'uploadKey', type: 'number', system: true},
    {name: 'name', type: 'text', system: true},
    {name: 'size', type: 'number', system: true},
    {name: 'type', type: 'text', system: true},
    {name: 'status', type: 'text', system: true},
    {name: 'progress', type: 'number', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'request', system: true},
    {name: 'chunkContext', system: true},
    {name: 'path', system: true},
    {name: 'tempFile', system: true},
    {name: 'lastChunkUploadFailed', system: true},
    {name: 'currentChunkIndex', type: 'number', system: true},
    {name: 'currentChunkPosition', type: 'number', system: true},
    {name: 'currentChunk', system: true},
    {name: 'retryCount', type: 'number', system: true},
    {name: 'currentChunkSize', type: 'number', system: true},
    {name: 'lastChunk', system: true}
]);

Ext.ux.file.Uploader.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type']);
};
