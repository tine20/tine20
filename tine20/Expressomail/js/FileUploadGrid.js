Ext.ns('Tine.Expressomail');

Tine.Expressomail.FileUploadGrid = Ext.extend(Tine.widgets.grid.FileUploadGrid, {
    
    parent: null,
    messageRecord: null,

    initComponent : function() {
        Tine.Expressomail.FileUploadGrid.superclass.initComponent.call(this);
        this.store.on('update', this.onUpdateStore, this);
    },

    onFilesSelect: function (fileSelector, e) {
        
        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {

            var upload = new Ext.ux.file.Upload({
                isLocal: this.parent.encrypted,
                eid: this.parent.eid,
                file: file,
                fileSelector: fileSelector
            });

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);
            var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);
            
            fileRecord.fileUploadGrid = this;
            
            upload.on('uploadfailure', this.onUploadFail, this);
            upload.on('uploadcomplete', this.onUploadComplete, fileRecord);
            upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);

            if(fileRecord.get('status') !== 'failure' ) {
                this.store.add(fileRecord);
            }

            
        }, this);
        
    },

    onUploadComplete: function(upload, fileRecord) {
    
        fileRecord.beginEdit();
        fileRecord.set('status', 'complete');
        fileRecord.set('progress', 100);
        fileRecord.commit(false);
        Tine.Tinebase.uploadManager.onUploadComplete();
        
        // check if the last attachment violates maximum size, remove it it if it does
        if (!upload.isLocal) {
            fileRecord.fileUploadGrid.validateLastAttachment(fileRecord.fileUploadGrid);
        }
    },
    
    /**
     * store has been updated
     *
     * @param {} store
     * @param {} record
     * @param {} operation
     * @private
     */
    onUpdateStore: function(store, record, operation) {
        this.setModifiedFlag();
    },

    /**
     * set flag to indicate that the message has changed
     *
     * @private
     */
    setModifiedFlag: function() {
        if (this.messageRecord) {
            if (!this.messageRecord.modified) {
                this.messageRecord.modified = {};
            }
            this.messageRecord.modified['attachments'] = true;
        }
    },

    validateLastAttachment: function(that) {
        var messageContent = new Tine.Expressomail.Model.MessageContent(
            Tine.Expressomail.Model.MessageContent.getDefaultData(), 0);
        
        var idx = that.store.data.items.length-1;
        var att = that.store.data.items[idx];
        var attachments = [];
        attachments.push({
            tempFile: Ext.ux.file.Upload.file.getFileData(att).tempFile
        });
        messageContent.data.attachments = attachments;
        
        that.showMask(that.parent);
        Ext.Ajax.request({
            params: {
                method: 'Expressomail.calcMessageSize',
                recordData: messageContent
            },
            success: function (result) {
                var response = JSON.parse(result.responseText);
                if (response.maxMessageSize <= 0) {
                    // parameter maxMessageSize is not defined in config.inc.php
                    that.hideMask(that.parent);
                    return;
                }
                var totalSize = response.attachmentSize;
                var limitExceeded = totalSize > response.maxMessageSize;

                var maxMB = Ext.util.Format.number(response.maxMessageSize/(1024*1024), '0.00');
                var usedMB = Ext.util.Format.number(totalSize/(1024*1024), '0.00');

                if (limitExceeded) {
                    Ext.MessageBox.show({
                        title: that.parent.app.i18n._('Message Size Limit Exceeded'),
                        msg: String.format(that.parent.app.i18n._('Maximum allowed message size is {0} Mb.'), maxMB)
                                + '<br>' + 
                             String.format(that.parent.app.i18n._('The last attachment was removed.'), maxMB),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.INFO,
                        fn: function(btn) {
                            that.validateAllAttachment(that);
                        }
                    });
                    that.selModel.selectRow(idx);
                    that.onRemove();
                    that.hideMask(that.parent);
                }
                else {
                    that.hideMask(that.parent);
                    // if last file size passed, calculates the total attachment size
                    that.validateAllAttachment(that);
                }
            },
            failure: function (err, details) {
                Ext.MessageBox.alert(that.parent.app.i18n._('Failed'),
                    that.parent.app.i18n._('File size validation step failed.'));
                that.hideMask(that.parent);
            }
        });
    },
    
    validateAllAttachment: function(that) {
        var messageContent = new Tine.Expressomail.Model.MessageContent(
            Tine.Expressomail.Model.MessageContent.getDefaultData(), 0);
            
        var attachments = [];
        that.store.each(function(attachment) {
            attachments.push({
                tempFile: Ext.ux.file.Upload.file.getFileData(attachment).tempFile
            });
        }, this);
        messageContent.data.attachments = attachments;
        
        that.showMask(that.parent);
        Ext.Ajax.request({
            params: {
                method: 'Expressomail.calcMessageSize',
                recordData: messageContent
            },
            success: function (result) {
                var response = JSON.parse(result.responseText);
                if (response.maxMessageSize <= 0) {
                    // parameter maxMessageSize is not defined in config.inc.php
                    that.hideMask(that.parent);
                    return;
                }
                var totalSize = response.attachmentSize;
                var limitExceeded = totalSize > response.maxMessageSize;

                var maxMB = Ext.util.Format.number(response.maxMessageSize/(1024*1024), '0.00');
                var usedMB = Ext.util.Format.number(totalSize/(1024*1024), '0.00');

                if (limitExceeded) {
                    Ext.MessageBox.show({
                        title: that.parent.app.i18n._('Message Size Limit Exceeded'),
                        msg: String.format(that.parent.app.i18n._('Maximum allowed message size is {0} Mb.'), maxMB)
                                + '<br>' + 
                             String.format(that.parent.app.i18n._('This message total attachment size is {0} Mb.'), usedMB),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.INFO
                    });
                }
                that.hideMask(that.parent);
            },
            failure: function (err, details) {
                Ext.MessageBox.alert(that.parent.app.i18n._('Failed'),
                    that.parent.app.i18n._('File size validation step failed.'));
                that.hideMask(that.parent);
            }
        });
    },
    
    showMask: function(that) {
        try {
            that.contactsCheckMask = new Ext.LoadMask(that.ownerCt.body, {msg: that.app.i18n._('Calculating message size...')});
        }
        catch (e) {
            that.contactsCheckMask = new Ext.LoadMask(Ext.getBody(), {msg: that.app.i18n._('Calculating message size...')});
        }
        that.contactsCheckMask.show();
    },
    
    hideMask: function(that) {
        that.contactsCheckMask.hide();
    },
    
    onRemove: function (button, event) {
        var selectedRows = this.getSelectionModel().getSelections();
        var attachments = [];
        var messageAttachments = new Tine.Expressomail.Model.MessageAttachments(
            Tine.Expressomail.Model.MessageAttachments.getDefaultData(), 0)
        for (var i = 0; i < selectedRows.length; i += 1) {
            this.store.remove(selectedRows[i]);
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            if (typeof(upload) != "undefined") {
                upload.setPaused(true);
            }
            attachments.push({
                tempFile: selectedRows[i].data.tempFile
            });
        }
        messageAttachments.data.attachments = attachments;
        this.setModifiedFlag();
        Ext.Ajax.request({
            params: {
                method: 'Expressomail.removeTempFiles',
                recordData: messageAttachments
            },
            success: function (result) {
                var response = JSON.parse(result.responseText);
                console.log('attachments removed');
                console.log(response);
            },
            failure: function (err, details) {
                console.log(err);
                console.log(details);
            }
        });
    }
    
});
