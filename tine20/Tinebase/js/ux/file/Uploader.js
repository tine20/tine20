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
    
    // todo: entfernen, ist für DEBUG
    this.maxChunkSize = 1048576;
};
 
Ext.extend(Ext.ux.file.Uploader, Ext.util.Observable, {
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
    upload: function(file) {
        if ((
            (! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
            (Ext.isGecko && window.FileReader) // FF
        ) && file) {
        	
        	this.file = file;
            if (this.isHostMethod(file, 'mozSlice') || this.isHostMethod(file, 'webkitSlice')) {
                return this.html5ChunkedUpload();
            } else {
                return this.html5upload();
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
    html5upload: function(chunkContext) {
    	
    	var fileRecord = new Ext.ux.file.Uploader.file({
	            name: this.file.name ? this.file.name : this.file.fileName,  // safari and chrome use the non std. fileX props
	            type: (this.file.type ? this.file.type : this.file.fileType) || this.fileSelector.getFileCls(), // missing if safari and chrome
	            size: (this.file.size ? this.file.size : this.file.fileSize) || 0, // non standard but all have it ;-)
	            status: 'uploading',
	            progress: 0,
	            input: this.getInput()
	        });
    	
    	if(chunkContext) {
    		fileRecord.chunkContext = chunkContext;
    		fileRecord.size = fileRecord.currentChunkSize;
    	}
    	
    	if(this.maxPostSize/1 < this.file.size/1 && !fileRecord.chunkContext) {
    		fileRecord.html5upload = true;
    		this.onUploadFail(null, null, fileRecord);
    		return fileRecord;
    	}
    	
    	var defaultHeaders = {
            "Content-Type"          : "application/x-www-form-urlencoded",
            "X-Tine20-Request-Type" : "HTTP",
            "X-Requested-With"      : "XMLHttpRequest"
        };
    	
    	var xmlData = this.file;
    	
    	if(fileRecord.chunkContext) {
    		defaultHeaders["X-Chunk-Count"] = fileRecord.chunkContext.currentChunkIndex;
    		defaultHeaders["X-Chunk-finished"] = fileRecord.chunkContext.lastChunk;
    		xmlData = fileRecord.chunkContext.currentChunk;
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
                "X-File-Name"           : fileRecord.get('name'),
                "X-File-Type"           : fileRecord.get('type'),
                "X-File-Size"           : fileRecord.get('size')
            },
            xmlData: xmlData,
            success: this.onChunkUploadSuccess.createDelegate(this, [fileRecord], true),
            failure: this.onChunkUploadFail.createDelegate(this, [fileRecord], true),
            fileRecord: fileRecord
        });
        
        // todo: Registrierung ist an dieser Stelle zu spät
//        var upload = transaction.conn.upload;
//        upload["onprogress"] = this.onUploadProgress.createDelegate(this, [fileRecord], true);

        return fileRecord;
    },
    
//    onlineState: function() {
//    	
//    	// todo: korrekt bestimmen
//    	return (window.navigator.onLine == 'online');
//    },
    
    html5ChunkedUpload: function(file) {
    	    	
    	var chunkContext = {
    			lastChunkUploadFailed: false,
    			currentChunkIndex: 0,
    			currentChunkPosition: 0,
    			currentChunk: false,
    			retryCount: 0,
    			currentChunkSize: this.maxChunkSize,
    			lastChunk: false
    	};
    	    	
  		fileRecord = this.html5upload(this.prepareChunk(chunkContext));

  		return fileRecord;
    	
    },
    
    prepareChunk: function(chunkContext) {
    	
    	if(chunkContext.lastChunkUploadFailed){
    		chunkContext.currentChunkPosition = Math.max(0
    				, chunkContext.currentChunkPosition - chunkContext.currentChunkSize);
    	}
    	
    	if(chunkContext.lastChunkUploadFailed) {
    		chunkContext.currentChunkSize = Math.max(this.minChunkSize, chunkContext.currentChunkSize / 2);
    	}
    	else {
    		chunkContext.currentChunkSize = Math.min(this.maxChunkSize, chunkContext.currentChunkSize * 2);
    	}
    	    	
    	var nextChunkPosition = Math.min(this.file.fileSize, chunkContext.currentChunkPosition +  chunkContext.currentChunkSize);
    	var currentChunk = this.sliceFile(this.file, chunkContext.currentChunkPosition, nextChunkPosition);
    	
    	if(nextChunkPosition/1 == this.file.fileSize/1) {
    		chunkContext.lastChunk = true;
    	}
    	
    	chunkContext.currentChunkPosition = nextChunkPosition;
    	chunkContext.currentChunk = currentChunk;
    	
    	return chunkContext;
    	
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
        
        
        if(this.maxFileUploadSize/1 < file.size/1) {
    		fileRecord.html4upload = true;
        	this.onUploadFail(null, null, fileRecord);
    		return fileRecord;
    	}
        
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
    	
    	console.log("fired onUploadSuccess");
    	
    	try {
    		response = Ext.util.JSON.decode(response.responseText);
    	}
    	catch(e) {
    		this.onUploadFail(response, options, fileRecord);
    		return;
    	}
    		
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
    onChunkUploadSuccess: function(response, options, fileRecord) {
    	
    	console.log("onChunkUploadSuccess fired");
        response = Ext.util.JSON.decode(response.responseText);
        
        fileRecord.beginEdit();
        fileRecord.set('tempFile', response.tempFile);
        fileRecord.set('name', response.tempFile.name);
        fileRecord.set('size', response.tempFile.size);
        fileRecord.set('type', response.tempFile.type);
        fileRecord.set('path', response.tempFile.path);
        fileRecord.commit(false);
        
        if(response.status && response.status !== 'success') {
            this.onChunkUploadFail(response, options, fileRecord);
        } else {

        	Tine.Tinebase.uploadManager.addTempfile(fileRecord.get('name'), fileRecord.get('tempFile'));
        	
        	console.log("uploaded " 
        			+ parseInt(fileRecord.chunkContext.currentChunkPosition * 100 / fileRecord.get('size')/1) + " %");
        	
            if(fileRecord.chunkContext.lastChunk) {
                fileRecord.set('status', 'complete');

            	console.log("Last chunk uploaded");            
            	Tine.Tinebase.uploadManager.finishUpload(fileRecord.get('name'));
            	this.fireEvent('uploadcomplete', this, fileRecord);
            }
            else {
            	fileRecord = this.html5upload(this.prepareChunk(fileRecord.chunkContext));
            }           	            	            	
        }        
    },
    
    /**
     * executed if a chunk upload failed
     */
    onChunkUploadFail: function(response, options, fileRecord) {

		chunkContext.lastChunkUploadFailed = true;
    	chunkContext.retryCount++;
    	if (chunkContext.retryCount > this.MAX_RETRY_COUNT) {
    		alert("Upload failed!");
    	}
    	else {
    		chunkContext.lastChunkUploadFailed = true;
    		fileRecord = this.html5upload(this.prepareChunk(fileRecord.chunkContext));
    	}
    },
    
    // private
    getInput: function() {
        if (! this.input) {
            this.input = this.fileSelector.detachInputFile();
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
    {name: 'tempFile', system: true}
]);

Ext.ux.file.Uploader.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type']);
};
