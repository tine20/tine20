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
        
    this.maxChunkSize = this.maxPostSize - 16384;
};
 
Ext.extend(Ext.ux.file.Uploader, Ext.util.Observable, {
	
//	uploadInProgress: false,
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
    upload: function(uploadKey) {
    	
    	var file = Tine.Tinebase.uploadManager.getFile(uploadKey); 
    	if(Tine.Tinebase.uploadManager.uploadInProgress) {
    		
    		return this.createFileRecord(true, uploadKey);
    	}
    	else {
    		Tine.Tinebase.uploadManager.uploadInProgress = uploadKey;

    		// calculate optimal maxChunkSize       
    		var fileSize = (file.size ? file.size : file.fileSize);
    		var chunkMax = this.maxChunkSize;
    		var chunkMin = this.minChunkSize;       
    		var actualChunkSize= this.maxChunkSize;

    		if(fileSize > 5 * chunkMax) {
    			actualChunkSize = chunkMax;
    		}
    		else {
    			actualChunkSize = Math.max(chunkMin, fileSize / 5);
    		}   	
    		this.maxChunkSize = actualChunkSize;

    		if ((
    				(! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || // safari, chrome, ...?
    						(Ext.isGecko && window.FileReader) // FF
    		) && file) {

    			if (Tine.Tinebase.uploadManager.isHtml5ChunkedUpload()) {
    				var fileRecord = this.html5ChunkedUpload(uploadKey);
    				return fileRecord;
    			} else {
    				Tine.Tinebase.uploadManager.setFileRecord(uploadKey, this.createFileRecord(false, uploadKey));
    				return this.html5upload(uploadKey);
    			}
    		} else {
    			return this.html4upload();
    		}
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
    html5upload: function(uploadKey) {
    	
    	var file = Tine.Tinebase.uploadManager.getFile(uploadKey);
    	var fileRecord = Tine.Tinebase.uploadManager.getFileRecord(uploadKey);
    	
    	console.log("html5upload " + uploadKey);
    	
    	var chunkContext = Tine.Tinebase.uploadManager.getChunkContext(uploadKey); 
    	if(chunkContext) {
//    		fileRecord.set('size', chunkContext.currentChunkSize);
    	}
    	
    	if(this.maxPostSize/1 < file.size/1 && !chunkContext) {
    		fileRecord.html5upload = true;
    		this.onUploadFail(null, null, fileRecord);
    		return fileRecord;
    	}
    	
    	var defaultHeaders = {
            "Content-Type"          : "application/x-www-form-urlencoded",
            "X-Tine20-Request-Type" : "HTTP",
            "X-Requested-With"      : "XMLHttpRequest"
        };
    	
    	var xmlData = file;
    	
    	if(chunkContext) {
    		defaultHeaders["X-Chunk-Count"] = chunkContext.currentChunkIndex;
    		defaultHeaders["X-Chunk-finished"] = chunkContext.lastChunk;
    		xmlData = chunkContext.currentChunk;
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
            failure: this.onChunkUploadFail.createDelegate(this, [fileRecord], true) //,
            // fileRecord: fileRecord
        });
        

        return fileRecord;
    },

    html5ChunkedUpload: function(uploadKey, resumeUpload) {
    	    
    	var fileRecord;
    	
    	if(resumeUpload) {
    		fileRecord = Tine.Tinebase.uploadManager.getFileRecord(uploadKey);
    		this.html5upload(uploadKey);
    	}
    	
    	else {
    	
	    	var chunkContext = {
	    			lastChunkUploadFailed: false,
	    			currentChunkIndex: 0,
	    			currentChunkPosition: 0,
	    			currentChunk: false,
	    			retryCount: 0,
	    			currentChunkSize: this.maxChunkSize,
	    			lastChunk: false
	    	};
	    	    	
	    	Tine.Tinebase.uploadManager.setChunkContext(uploadKey, chunkContext);
	    	  
	    	this.prepareChunk(uploadKey);
	  		fileRecord = this.createFileRecord(false, uploadKey);
			Tine.Tinebase.uploadManager.setFileRecord(uploadKey, fileRecord);
	
	  		this.html5upload(uploadKey);

    	}
  		return fileRecord;
    	
    },
    
    prepareChunk: function(uploadKey) {
    	
    	var chunkContext = Tine.Tinebase.uploadManager.getChunkContext(uploadKey);
    	
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
    	    	
    	var file = Tine.Tinebase.uploadManager.getFile(uploadKey);
    	var nextChunkPosition = Math.min(file.fileSize, chunkContext.currentChunkPosition +  chunkContext.currentChunkSize);
    	var currentChunk = this.sliceFile(file, chunkContext.currentChunkPosition, nextChunkPosition);
    	
    	if(nextChunkPosition/1 == file.fileSize/1) {
    		chunkContext.lastChunk = true;
    	}
    	
    	chunkContext.currentChunkPosition = nextChunkPosition;
    	chunkContext.currentChunk = currentChunk;
    	
    	Tine.Tinebase.uploadManager.setChunkContext(uploadKey, chunkContext);
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
    
//    onUploadProgress: function(e, fileRecord) {
//    	
//    	var percent = Math.round(e.loaded / e.total * 100);        
//    	fileRecord.set('progress', percent);
//        this.fireEvent('uploadprogress', this, fileRecord, e);
//    },
    
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
        Tine.Tinebase.uploadManager.uploadInProgress = false;
        this.fireEvent('uploadfailure', this, fileRecord);
    },
    
    /**
     * executed if a chunk got uploaded successfully
     */
    onChunkUploadSuccess: function(response, options, fileRecord) {
    	
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
        } 
        else if(!Tine.Tinebase.uploadManager.isInterrupted()) {

        	Tine.Tinebase.uploadManager.addTempfile(fileRecord.get('uploadKey'), fileRecord.get('tempFile'));

        	var chunkContext = Tine.Tinebase.uploadManager.getChunkContext(fileRecord.get('uploadKey'));

        	if(Tine.Tinebase.uploadManager.isHtml5ChunkedUpload()) {
	        	var percent = parseInt(chunkContext.currentChunkPosition * 100 / fileRecord.get('size')/1);
	            this.fireEvent('uploadprogress', this, fileRecord, percent);
        	}
            
            if(chunkContext && chunkContext.lastChunk) {
                
            	console.log("Finishing " + fileRecord.get('uploadKey') + "..");            
            	this.fireEvent('uploadcomplete', this, fileRecord);
            	Tine.Tinebase.uploadManager.uploadInProgress = false;
            	
            	var nextUploadKey = Tine.Tinebase.uploadManager.checkForPendingUploads();
            	if(nextUploadKey) {
                  	this.upload(nextUploadKey);
            	}
            	
            }
            else {
            	this.prepareChunk(fileRecord.get('uploadKey'));
            	fileRecord = this.html5upload(fileRecord.get('uploadKey'));
            }           	            	            	
        }        
    },
    
    /**
     * executed if a chunk upload failed
     */
    onChunkUploadFail: function(response, options, fileRecord) {

    	var chunkContext = Tine.Tinebase.uploadManager.getChunkContext(fileRecord.get('uploadKey'));
		chunkContext.lastChunkUploadFailed = true;
    	chunkContext.retryCount++;
    	if (chunkContext.retryCount > this.MAX_RETRY_COUNT) {
    		alert("Upload failed!");
    	}
    	else {
    		chunkContext.lastChunkUploadFailed = true;
    		fileRecord = this.html5upload(fileRecord.get('uploadKey'));
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
    	
    },
    
    createFileRecord: function(pending, uploadKey) {
    	
    	var file = Tine.Tinebase.uploadManager.getFile(uploadKey);

    	
    	var status = "uploading";
    	if(pending) {
    		status = "pending";
    	}

    	var fileRecord = new Ext.ux.file.Uploader.file({
            name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
            type: (file.type ? file.type : file.fileType) || this.fileSelector.getFileCls(), // missing if safari and chrome
            size: (file.size ? file.size : file.fileSize) || 0, // non standard but all have it ;-)
            status: status,
            progress: 0,
            input: this.getInput(),
            uploadKey: uploadKey
        });
    	
    	return fileRecord;
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
    {name: 'tempFile', system: true},
    {name: 'uploadKey', type: 'text', system: true}
]);

Ext.ux.file.Uploader.file.getFileData = function(file) {
    return Ext.copyTo({}, file.data, ['tempFile', 'name', 'path', 'size', 'type']);
};
