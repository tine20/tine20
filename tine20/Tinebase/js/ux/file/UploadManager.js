
Ext.ns('Ext.ux.file');

/**
 * a simple file upload manager
 * collects all chunked uploads
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.Uploadmanager
 * @extends     Ext.util.Observable
 */
Ext.ux.file.UploadManager = function(config) {
    Ext.apply(this, config);
    
    Ext.ux.file.UploadManager.superclass.constructor.apply(this, arguments);
    
    this.addEvents(
        /**
         * @event uploadcomplete
         * Fires when the upload was done successfully 
         * @param {Ext.ux.file.Uploader} this
         * @param {Ext.Record} Ext.ux.file.Uploader.file
         */
         'uploadcomplete',
        /**
         * z@event uploadfailure
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
 

Ext.extend(Ext.ux.file.UploadManager, Ext.util.Observable, {	
	
	uploads: {},
	
	uploadInProgress: false,
	
	registerUpload: function(file) {		
		this.uploads[file.name] = {file: file};	
	},
	
	getUploadSet: function(fileId) {
		return this.uploads[fileId];
	},
	
	setChunkContext: function(fileId, chunkContext) {
		this.uploads[fileId].chunkContext = chunkContext;
	},
	
	getChunkContext: function chunkContext(fileId) {
		return this.uploads[fileId].chunkContext;
	},
		
	setOriginalFileRecord: function(fileId, fileRecord) {
		this.uploads[fileId].fileRecord = fileRecord;
	},
	
	getOriginalFileRecord: function chunkContext(fileId) {
		return this.uploads[fileId].fileRecord;
	},
	
	addTempfile: function(fileId, tempFile, uploadContext) {
	
		var currentUploadSet = {};
		
		if(this.uploads[fileId]) {
			currentUploadSet = this.uploads[fileId];
		}
			
		var tempFileArray = new Array();
			
		if(currentUploadSet.tempFileArray) {
			tempFileArray = currentUploadSet.tempFileArray;
		}
			
		tempFileArray.push(tempFile);
		currentUploadSet['tempFileArray'] = tempFileArray;
		currentUploadSet['uploadContext'] = uploadContext;
		
		this.uploads[fileId] = currentUploadSet;
		
		return true;
	},
	
	finishUpload: function(fileId) {		
		Tine.Tinebase.joinTempFiles(this.uploads[fileId].tempFileArray);
		this.removeUploadSet(fileId);
	},
	
	getTempfiles: function(fileId) {
		return this.uploads[fileId].tempFileArray;
	},
	
	getFile: function(fileId) {
		return this.uploads[fileId].file;
	},
	
	removeUploadSet: function(fileId) {
		delete this.uploads[fileId];
	},
	
	checkForPendingUploads: function() {
		
		var fileId = false;
		for (var key in this.uploads) {
			fileId = key;
			break;			
		}
		return fileId;
	}
	
});