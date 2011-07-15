
Ext.ns('Ext.ux.file');

/**
 * a simple file upload manager
 * collects all chunked uploads
 * 
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.Uploadmanager
 */
Ext.ux.file.UploadManager = function(config) {

    return {
        
        uploadIdPrefix: "tine-upload-",

        uploads: new Object(),

        uploadCount: 0,

        queueUpload: function(upload) {
            var uploadId = this.uploadIdPrefix + (1000 + this.uploadCount++).toString(); 
            if(upload.id && upload.id > -1) {
                uploadId = upload.id;
            }
            upload.id = uploadId;
            this.uploads[uploadId] = upload;
            return uploadId;
        },

        upload: function(id) {
            return this.uploads[id].upload();
        },

        getUpload: function(id) {
            return this.uploads[id];
        },

        unregisterUpload: function(id) {
            delete this.uploads[id];
        },
        
        isHtml5ChunkedUpload: function() {
            
//          if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {
          console.log("isHtml5ChunkedUpload");
          
          if(window.File == undefined) return false;
          if(this.isHostMethod(File.prototype, 'mozSlice') || this.isHostMethod(File.prototype, 'webkitSlice')) {

              return true;
          }
          else {
              return false;
          }       
      },
      
      isHostMethod: function (object, property) {
          var t = typeof object[property];
          
          return t == 'function' || (!!(t == 'object' && object[property])) || t == 'unknown';
      }
    };
 };
