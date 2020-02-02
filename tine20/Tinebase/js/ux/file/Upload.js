/* Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux.file');

/**
 * a simple file upload
 * objects of this class represent a single file uplaod
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.Upload
 * @extends     Ext.util.Observable
 * 
 * @constructor
 * @param       Object config
 */
Ext.ux.file.Upload = function(config) {
    
    Ext.apply(this, config);
    
    Ext.ux.file.Upload.superclass.constructor.apply(this, arguments);
    
    this.addEvents(
        /**
         * @event uploadcomplete
         * Fires when the upload was done successfully 
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         */
         'uploadcomplete',
        /**
         * @event uploadfailure
         * Fires when the upload failed 
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         */
         'uploadfailure',
        /**
         * @event uploadprogress
         * Fires on upload progress (html5 only)
         * @param {Ext.ux.file.Upload} this
         * @param {Ext.Record} Ext.ux.file.Upload.file
         * @param {XMLHttpRequestProgressEvent}
         */
         'uploadprogress',
         /**
          * @event uploadstart
          * Fires on upload progress (html5 only)
          * @param {Ext.ux.file.Upload} this
          * @param {Ext.Record} Ext.ux.file.Upload.file
          */
          'uploadstart',
         /**
          * @event uploadstart
          * Fires on upload progress (html5 only)
          * @param {Ext.ux.file.Upload} this
          * @param {Ext.Record} Ext.ux.file.Upload.file
          */
         'update'
    );
        
    if (! this.file && this.fileSelector) {
        this.file = this.fileSelector.getFileList()[0];
    }
    
    this.fileSize = (this.file.size ? this.file.size : this.file.fileSize);

    this.maxChunkSize = this.maxPostSize - 16384;
    this.currentChunkSize = this.maxChunkSize;
    
    this.tempFiles = [];
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
     * @property maxChunkSize the maximum chunk size used for html5 uploads
     * @type Int
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
     *  retry timeout in milliseconds
     */
    RETRY_TIMEOUT_MILLIS: 3000,
    
    /**
     *  retry timeout in milliseconds
     */
    CHUNK_TIMEOUT_MILLIS: 100,
    
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
     * coresponding file record
     */
    fileRecord: null,
    
    /**
     * uploadPath
     */
    uploadPath: '/',
    
    /**
     * file to upload
     */
    file: null,
    
    /**
     * is this upload paused
     */
    paused: false,
    
    /**
     * is this upload queued
     */
    queued: false,    
    
    /**
     * collected tempforary files
     * @property {Array} tempFiles
     */
    tempFiles: null,
    
    /**
     * did the last chunk upload fail
     */
    lastChunkUploadFailed: false,
    
    /**
     * how many retries were made while trying to upload current chunk
     */
    retryCount: 0,
    
    /**
     * size of the current chunk
     */
    currentChunkSize: 0,
    
    /**
     * where the chunk begins in file (byte number)
     */
    currentChunkPosition: 0,
    
    /**
     * size of the file to upload
     */
    fileSize: 0,

    /**
     * method for uploading temp file
     */
    uploadTempFileMethod: 'Tinebase.uploadTempFile',
    
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
     * @return {Ext.Record} Ext.ux.file.Upload.file
     */
    upload: function() {
        if ((
                (! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
                (Ext.isGecko && window.FileReader) // FF
        ) && this.file) {
     
            // free browse plugin
            this.getInput();
            
            if (this.isHtml5ChunkedUpload()) {
                // calculate optimal maxChunkSize
                // TODO: own method for chunked upload
                
                var chunkMax = this.maxChunkSize;
                var chunkMin = this.minChunkSize;
                var actualChunkSize = this.maxChunkSize;

                if (this.fileSize > 5 * chunkMax) {
                    actualChunkSize = chunkMax;
                } else {
                    actualChunkSize = Math.max(chunkMin, this.fileSize / 5);
                }       
                this.maxChunkSize = actualChunkSize;
                
                if (Tine.Tinebase.uploadManager && Tine.Tinebase.uploadManager.isBusy()) {
                    this.createFileRecord(true);
                    this.setQueued(true);
                } else {
                    this.createFileRecord(false);
                    this.fireEvent('uploadstart', this);
                    this.fireEvent('update', 'uploadstart', this, this.fileRecord);
                    this.html5ChunkedUpload();
                }
                
                return this.fileRecord;

            } else {
                this.createFileRecord(false);
                this.fireEvent('uploadstart', this);
                this.fireEvent('update', 'uploadstart', this, this.fileRecord);
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
                 
        // TODO: move to upload method / checks max post size
        if(this.maxPostSize/1 < this.file.size/1 && !this.isHtml5ChunkedUpload()) {
            this.fileRecord.html5upload = true;
            this.onUploadFail(null, null, this.fileRecord);
            return this.fileRecord;
        }
        
        var defaultHeaders = {
            "Content-Type"          : "application/octet-stream",
            "X-Tine20-Request-Type" : "HTTP",
            "X-Requested-With"      : "XMLHttpRequest"
        };
        
        var xmlData = this.file;
               
        if(this.isHtml5ChunkedUpload()) {
            xmlData = this.currentChunk;
        }

        var conn = new Ext.data.Connection({
            disableCaching: true,
            method: 'POST',
            url: this.url + '?method=' + this.uploadTempFileMethod,
            timeout: 300000, // 5 mins
            defaultHeaders: defaultHeaders
        });
                
        var transaction = conn.request({
            headers: {
                "X-File-Name"           : window.btoa(unescape(encodeURIComponent(this.fileRecord.get('name')))),
                "X-File-Type"           : this.fileRecord.get('type'),
                "X-File-Size"           : this.fileRecord.get('size')
            },
            xmlData: xmlData,
            success: this.onUploadSuccess.createDelegate(this, null, true),
            failure: this.onUploadFail.createDelegate(this, null, true) 
        });

        return this.fileRecord;
    },

    /**
     * Starting chunked file upload
     * 
     * @param {Boolean} whether this restarts a paused upload
     */
    html5ChunkedUpload: function() {
        this.prepareChunk();
        this.html5upload();
    },
    
    /**
     * resume this upload
     */
    resumeUpload: function() {
        this.setPaused(false);
        this.html5ChunkedUpload();
    },
    
    /**
     * calculation the next chunk size and slicing file
     */
    prepareChunk: function() {
        
        if(this.lastChunkUploadFailed) {
            this.currentChunkPosition = Math.max(0
                    , this.currentChunkPosition - this.currentChunkSize);

            this.currentChunkSize = Math.max(this.minChunkSize, this.currentChunkSize / 2);
        }
        else {
            this.currentChunkSize = Math.min(this.maxChunkSize, this.currentChunkSize * 2);
        }
        this.lastChunkUploadFailed = false;
        
        var nextChunkPosition = Math.min(this.fileSize, this.currentChunkPosition 
                +  this.currentChunkSize);
        var newChunk = this.sliceFile(this.file, this.currentChunkPosition, nextChunkPosition);
        
        if(nextChunkPosition/1 == this.fileSize/1) {
            this.lastChunk = true;
        }
                     
        this.currentChunkPosition = nextChunkPosition;
        this.currentChunk = newChunk;
       
    },
    
    /**
     * Setting final fileRecord states
     */
    finishUploadRecord: function(response) {
        
        response = Ext.util.JSON.decode(response.responseText);
        if(response.tempFile) {
            response = response.tempFile;
        }
                
        if(response.error == 0) {
            this.fileRecord.beginEdit();
            this.fileRecord.set('size', response.size);
            this.fileRecord.set('id', response.id);
            this.fileRecord.set('progress', 99);
            this.fileRecord.set('tempFile', '');
            this.fileRecord.set('tempFile', response);
            try {
                this.fileRecord.commit(false);
            } catch (e) {
                console.log(e);
            }
            this.fireEvent('uploadcomplete', this, this.fileRecord);
            this.fireEvent('update', 'uploadcomplete', this, this.fileRecord);

        }       
        else {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'failure');
            this.fileRecord.set('progress', -1);
            this.fileRecord.set('tempFile', '');
            this.fileRecord.set('tempFile', response);
            this.fileRecord.commit(false);
            this.fireEvent('update', 'uploadfailure', this, this.fileRecord);
                       
        }
                
    },
    
   
    /**
     * executed if a chunk or file got uploaded successfully
     */
    onUploadSuccess: function(response, options, fileRecord) {
        
        var responseObj = Ext.util.JSON.decode(response.responseText);

        this.retryCount = 0;

        if(responseObj.status && responseObj.status !== 'success') {
            this.onUploadFail(responseObj, options, fileRecord);
        }

        this.fileRecord.beginEdit();
        this.fileRecord.set('tempFile', responseObj.tempFile);
        this.fileRecord.set('size', 0);
        try {
            this.fileRecord.commit(false);
        } catch (e) {
            console.log(e);
        }

        this.fireEvent('update', 'uploadprogress', this, this.fileRecord);
        
        if(! this.isHtml5ChunkedUpload()) {

            this.finishUploadRecord(response);
        }       
        else {

            this.addTempfile(this.fileRecord.get('tempFile'));
            var percent = parseInt(this.currentChunkPosition * 100 / this.fileSize/1);
            
            if(this.lastChunk) {
                percent = 98;
            }

            this.fileRecord.beginEdit();
            this.fileRecord.set('progress', percent);
            try {
                fileRecord.commit(false);
            } catch (e) {
                console.log(e);
            }

            if(this.lastChunk) {

                window.setTimeout((function() {
                    Ext.Ajax.request({
                        timeout: 10*60*1000, // Overriding Ajax timeout - important!
                        params: {
                        method: 'Tinebase.joinTempFiles',
                        tempFilesData: this.tempFiles
                    },
                    success: this.finishUploadRecord.createDelegate(this), 
                    failure: _.bind(function(response, request) {
                        let msg = formatMessage('Error while uploading "{fileName}". Please try again later.',
                            {fileName: this.fileRecord.get('name') });

                        Ext.MessageBox.alert(formatMessage('Upload Failed'), msg)
                            .setIcon(Ext.MessageBox.ERROR);

                    }, this)
                    });
                }).createDelegate(this), this.CHUNK_TIMEOUT_MILLIS);

            }
            else {
                window.setTimeout((function() {
                    if(!this.isPaused()) {
                        this.prepareChunk();
                        this.html5upload();
                    }
                }).createDelegate(this), this.CHUNK_TIMEOUT_MILLIS);
            }                                               

        }  

    },


    /**
     * executed if a chunk / file upload failed
     */
    onUploadFail: function(response, options, fileRecord) {

        if (this.isHtml5ChunkedUpload()) {
            
            this.lastChunkUploadFailed = true;
            this.retryCount++;
            
            if (this.retryCount > this.MAX_RETRY_COUNT) {
                
                this.fileRecord.beginEdit();
                this.fileRecord.set('status', 'failure');
                this.fileRecord.endEdit();

                this.fireEvent('update', 'uploadfailure', this, this.fileRecord);
            }
            else {
                window.setTimeout((function() {
                    this.prepareChunk();
                    this.html5upload();
                }).createDelegate(this), this.RETRY_TIMEOUT_MILLIS);
            }
        }
        else {
            this.fileRecord.beginEdit();
            this.fileRecord.set('status', 'failure');
            this.fileRecord.endEdit();

            this.fireEvent('update', 'uploadfailure', this, this.fileRecord);
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
        
        this.fileRecord = new Ext.ux.file.Upload.file({
            name: this.fileSelector.getFileName(),
            size: 0,
            type: this.fileSelector.getFileCls(),
            input: input,
            form: form,
            status: 'uploading',
            progress: 0
        });
        
        this.fireEvent('update', 'uploadprogress', this, this.fileRecord);
        
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
                method: this.uploadTempFileMethod,
                requestType: 'HTTP'
            }
        });
        
        return this.fileRecord;
    },
    
    /**
     * creating initial fileRecord for this upload
     */
    createFileRecord: function(pending) {
               
        var status = "uploading";
        if(pending) {
            status = "pending";
        }

        this.fileRecord = new Ext.ux.file.Upload.file({
            name: this.file.name ? this.file.name : this.file.fileName,  // safari and chrome use the non std. fileX props
            type: (this.file.type ? this.file.type : this.file.fileType), // missing if safari and chrome
            size: 0,
            status: status,
            progress: 0,
            input: this.file,
            uploadKey: this.id
        });
        
        this.fireEvent('update', 'uploadprogress', this, this.fileRecord);

    },
   
    /** 
     * adding temporary file to array 
     * 
     * @param tempfile to add
     */
    addTempfile: function(tempFile) {
        this.tempFiles.push(tempFile);
        return true;
    },
    
    /**
     * returns the temporary files
     * 
     * @returns {Array} temporary files
     */
    getTempfiles: function() {
        return this.tempFiles;
    },
    
    /**
     * pause oder resume file upload
     * 
     * @param paused {Boolean} set true to pause file upload
     */
    setPaused: function(paused) {
        this.paused = paused;
        
        var pausedState = 'paused';
        if(!this.paused) {
            pausedState = 'uploading';
        }
            
        this.fileRecord.beginEdit();
        this.fileRecord.set('status', pausedState);
        this.fileRecord.endEdit();
        this.fireEvent('update', 'uploadpaused', this, this.fileRecord);
    },
    
    /**
     * indicates whether this upload ist paused
     * 
     * @returns {Boolean}
     */
    isPaused: function() {
        return this.paused;
    },
    
    /**
     * checks for the existance of a method of an object
     * 
     * @param object    {Object}
     * @param property  {String} method name 
     * @returns {Boolean}
     */
    isHostMethod: function (object, property) {
        var t = typeof object[property];
        return t == 'function' || (!!(t == 'object' && object[property])) || t == 'unknown';
    },
    
    /**
     * indicates whether the current browser supports der File.slice method
     * 
     * @returns {Boolean}
     */
    isHtml5ChunkedUpload: function() {
                    
        if(window.File == undefined) return false;
        if(this.isHostMethod(File.prototype, 'slice') || this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {
            return this.fileSize > this.minChunkSize;
        }
        else {
            return false;
        }
    },
    
    // private
    getInput: function() {
        // NOTE: when a file got downloaded via url (CORS/xhr2) we don't have a input here
        if (! this.input && this.fileSelector && Ext.isFunction(this.fileSelector.detachInputFile)) {
            this.input = this.fileSelector.detachInputFile();
        }
        
        return this.input;
    },

    /**
     * slices the given file
     * 
     * @param file  File object
     * @param start start position
     * @param end   end position            
     * @param type  file type
     * @returns
     */
    sliceFile: function(file, start, end, type) {
        
        if(file.slice) {
            return file.slice(start, end);
        }
        else if(file.mozSlice) {
            return file.mozSlice(start, end, type);
        }
        else if(file.webkitSlice) {
            return file.webkitSlice(start, end);
        }
        else {
            return false;
        }
        
    },
    
    /**
     * sets dthe queued state of this upload
     * 
     * @param queued {Boolean}
     */
    setQueued: function (queued) {
        this.queued = queued;
        
        var queuedState = 'queued';
        if(!this.queued) {
            queuedState = 'uploading';
        }
            
        this.fileRecord.beginEdit();
        this.fileRecord.set('status', queuedState);
        this.fileRecord.endEdit();
        
        this.fireEvent('update', 'uploadqueued', this, this.fileRecord);
        
    },
    
    /**
     * indicates whether this upload is queued
     * 
     * @returns {Boolean}
     */
    isQueued: function() {
        return this.queued;
    }
    
            
});

/**
 * upload file record
 */
Ext.ux.file.Upload.file = Ext.data.Record.create([
    {name: 'id', type: 'text', system: true},
    {name: 'uploadKey', type: 'text', system: true},
    {name: 'name', type: 'text', system: true},
    {name: 'size', type: 'number', system: true},
    {name: 'type', type: 'text', system: true},
    {name: 'status', type: 'text', system: true},
    {name: 'progress', type: 'number', system: true},
    {name: 'form', system: true},
    {name: 'input', system: true},
    {name: 'url', system: true},
    {name: 'request', system: true},
    {name: 'path', system: true},
    {name: 'tempFile', system: true}
]);
Ext.ux.file.Upload.file.prototype.toString = function() {
    const data = Ext.ux.file.Upload.file.getFileData(this);
    return JSON.stringify(data);
};
Ext.ux.file.Upload.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type', 'id']);
};
