
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
    
//    this.addEvents(
//        /**
//         * @event uploadcomplete
//         * Fires when the upload was done successfully 
//         * @param {Ext.ux.file.Uploader} this
//         * @param {Ext.Record} Ext.ux.file.Uploader.file
//         */
//         'uploadcomplete',
//        /**
//         * z@event uploadfailure
//         * Fires when the upload failed 
//         * @param {Ext.ux.file.Uploader} this
//         * @param {Ext.Record} Ext.ux.file.Uploader.file
//         */
//         'uploadfailure',
//        /**
//         * @event uploadprogress
//         * Fires on upload progress (html5 only)
//         * @param {Ext.ux.file.Uploader} this
//         * @param {Ext.Record} Ext.ux.file.Uploader.file
//         * @param {XMLHttpRequestProgressEvent}
//         */
//         'uploadprogress'
//    );
    
};
 

Ext.extend(Ext.ux.file.UploadManager, Ext.util.Observable, {	
	
	uploads: {},
	
	uploadInProgress: false,
	
	registerUpload: function(file) {
		var uploadKey = file.name + new Date().getTime();
		this.uploads[uploadKey] = {file: file};	
		return uploadKey;
	},
	
	getUploadSet: function(uploadKey) {
		return this.uploads[uploadKey];
	},
	
	setChunkContext: function(uploadKey, chunkContext) {		
		this.uploads[uploadKey].chunkContext = chunkContext;
	},
	
	getChunkContext: function(uploadKey) {
		return this.uploads[uploadKey].chunkContext;
	},
		
	setOriginalFileRecord: function(uploadKey, fileRecord) {
		this.uploads[uploadKey].originalFileRecord = fileRecord;
	},
	
	getOriginalFileRecord: function(uploadKey) {
		return this.uploads[uploadKey].originalFileRecord;
	},
	
	setFileRecord: function(uploadKey, fileRecord) {
		this.uploads[uploadKey].fileRecord = fileRecord;
	},
	
	getFileRecord: function(uploadKey) {
		return this.uploads[uploadKey].fileRecord;
	},
	
	addTempfile: function(uploadKey, tempFile) {
	
		var currentUploadSet = {};
		
		if(this.uploads[uploadKey]) {
			currentUploadSet = this.uploads[uploadKey];
		}
			
		var tempFileArray = new Array();
			
		if(currentUploadSet.tempFileArray) {
			tempFileArray = currentUploadSet.tempFileArray;
		}
			
		tempFileArray.push(tempFile);
		currentUploadSet['tempFileArray'] = tempFileArray;
		
		this.uploads[uploadKey] = currentUploadSet;
		
		return true;
	},
	
	finishUpload: function(uploadKey) {		
		Tine.Tinebase.joinTempFiles(this.uploads[uploadKey].tempFileArray);
		this.removeUploadSet(uploadKey);
	},
	
	getTempfiles: function(uploadKey) {
		return this.uploads[uploadKey].tempFileArray;
	},
	
	getFile: function(uploadKey) {
		return this.uploads[uploadKey].file;
	},
	
	removeUploadSet: function(uploadKey) {
		delete this.uploads[uploadKey];
	},
	
	checkForPendingUploads: function() {
		
		var uploadKey = false;
		for (var key in this.uploads) {
			uploadKey = key;
			break;			
		}
		return uploadKey;
	}
	
});