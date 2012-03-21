
Ext.ns('Ext.ux.file');

/**
 * a simple file upload manager
 * collects all uploads
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.UploadManager
 */
Ext.ux.file.UploadManager = function(config) {
    
    Ext.apply(this, config);
    Ext.ux.file.UploadManager.superclass.constructor.apply(this, arguments);
    
    this.uploads = {};
    
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
};
    
Ext.extend(Ext.ux.file.UploadManager, Ext.util.Observable, {
        /**
         * @cfg {Int} number of allowed concurrent uploads
         */
        maxConcurrentUploads: 3,
        
        /**
         * current running uploads
         */
        runningUploads: 0,
        
        /**
         * @cfg (String) upload id prefix
         */
        uploadIdPrefix: "tine-upload-",

        /**
         * holds the uploads
         */
        uploads: null,

        /**
         * counts session uploads
         */
        uploadCount: 0,

        /**
         * every upload in the upload manager gets queued initially
         * 
         * @param upload {Ext.ux.file.Upload} Upload object
         * @returns {String} upload id
         */
        queueUpload: function(upload) {
            var uploadId = this.uploadIdPrefix + (1000 + this.uploadCount++).toString();
            if(upload.id && upload.id !== -1) {
                uploadId = upload.id;
            }
            upload.id = uploadId;
            
            this.uploads[uploadId] = upload;
            this.relayEvents(upload, ['update']);
            
            upload.on('uploadcomplete', this.onUploadComplete, this);
            
            return uploadId;
        },

        /**
         * starts the upload
         * 
         * @param id {String} upload id
         * @returns {Ext.ux.file.Upload.file} upload file record
         */
        upload: function(id) {
            return this.uploads[id].upload();
        },

        /**
         * returns upload by id
         * 
         * @param id {String} upload id
         * @returns (Ext.ux.file.Upload} Upload object
         */
        getUpload: function(id) {
            return this.uploads[id];
        },

        /**
         * remove upload from the upload manager
         * 
         * @param {String} upload id
         */
        unregisterUpload: function(id) {
            delete this.uploads[id];
        },
        
        
        /**
         * indicates whether the HTML5 chunked upload is available
         * 
         * @returns {Boolean}
         */
        isHtml5ChunkedUpload: function() {

          if(window.File == undefined) return false;
          if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {
              return true;
          }
          else {
              return false;
          }
          
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
         * are there as much as allowed uploads in progress
         * 
         * @returns {Boolean}
         */
        isBusy: function() {
            return (this.maxConcurrentUploads == this.runningUploads);
        },
        
        /**
         * are there uploads in progress or queued
         */
        isUploadsPending: function (){
            return (this.uploads.length > 0);
        },
        
        /**
         * on upload complete handler
         */
        onUploadComplete: function() {
            
            Tine.Tinebase.uploadManager.runningUploads 
                = Math.max(0, Tine.Tinebase.uploadManager.runningUploads - 1);
    
            for(var uploadKey in Tine.Tinebase.uploadManager.uploads){

                var upload = Tine.Tinebase.uploadManager.uploads[uploadKey];
                if(upload.isQueued() && !upload.isPaused() && !Tine.Tinebase.uploadManager.isBusy()) {
                    upload.setQueued(false);
                    upload.resumeUpload();
                    
                    break;
                }
           }
            
        }, 
        
        /**
         * on upload start handler
         */
        onUploadStart: function() {
            Tine.Tinebase.uploadManager.runningUploads 
                = Math.min(Tine.Tinebase.uploadManager.maxConcurrentUploads, Tine.Tinebase.uploadManager.runningUploads + 1);
        }
    
});
