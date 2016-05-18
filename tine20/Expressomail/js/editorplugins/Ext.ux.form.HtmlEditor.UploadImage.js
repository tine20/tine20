Ext.ux.form.HtmlEditor.UploadImage = Ext.extend(Ext.util.Observable, {
    text_title: 'Insert Image',
    text_error_msgbox_title: 'Error',
    text_error_file_type_not_permitted: 'This file type is not allowed.<br/>Please, select a file of the following extensions: {1}',
    text_note_upload_failed: 'Either a server internal error occurred or the service is unavailable.<br/>You may also check if the file size does not exceed {0} bytes.',
    text_note_upload_error: 'Upload error.<br/>Please check if the file size does not exceed {0} bytes.',
    text_note_uploading: 'Uploading...',
    url : null,
    method : 'Expressomail.uploadImage',
    base64: 'no',
    permitted_extensions: ['jpg', 'jpeg', 'png', 'bmp', 'gif'],
    form : null,
    isLocal: false,
    eid : null,

    init : function(cmp) {
        this.cmp = cmp;
        if(this.cmp.messageEdit) {
            this.eid = this.cmp.messageEdit.eid;
            this.cmp.messageEdit.record.set('embedded_images', this.cmp.messageEdit.record.get('embedded_images')||[]);
        }
        this.isLocal = this.cmp.messageEdit && this.cmp.messageEdit.encrypted;
        this.url = this.isLocal ? 'https://local.expressov3.serpro.gov.br:8998/upload' : 'index.php';
        this.method = this.isLocal ? '' : this.method;
        this.cmp.on('render', this.onRender, this);
        this.on('uploadsuccess', this.uploadsuccess || this.onUploadSuccess, this);
        this.on('uploaderror', this.uploaderror || this.onUploadError, this);
        this.on('uploadfailed', this.uploadfailed || this.onUploadFailed, this);

        // Registering dialog events.
        this.addEvents({
            'uploadsuccess' : true,
            'uploaderror' : true,
            'uploadfailed' : true,
            'uploadstart' : true,
            'uploadcomplete' : true
        });
    },

    setBase64 : function(base64) {
        this.base64 = base64;
    },

    createForm : function()	{
        this.form = Ext.DomHelper.append(this.body, {
            tag: 'form',
            method: 'post',
            action: this.url,
            style: 'position: absolute; left: -100px; top: -100px; width: 100px; height: 100px'
        });
    },

    recreateForm : function() {
        if (this.form) {
            this.form.parentNode.removeChild(this.form);
        }
        this.createForm();
    },

    onRender : function(ct, position) {
        this.cmp.getToolbar().addButton([new Ext.Toolbar.Separator()]);

        this.btn = this.cmp.getToolbar().addButton(new Ext.ux.UploadImage.BrowseButton({
            input_name: 'upload',
            iconCls: 'x-edit-image',
            handler: this.onFileSelected,
            scope: this,
            url: this.url,
            tooltip: {title: i18n._(this.text_title)},
            overflowText: i18n._(this.text_title)
        }));

        this.body = Ext.getBody();
        this.createForm();
    },

    onFileSelected : function() {
        if (this.isPermittedFile()) {
            var input_file = this.btn.detachInputFile();

            input_file.appendTo(this.form);
            input_file.setStyle('width', '100px');
            input_file.dom.disabled = true;

            this.image_file = {
                filename: input_file.dom.value,
                input_element: input_file
            };
            if (!Ext.isIE && !Ext.isNewIE) {
                this.image_file.file = (input_file.dom.files ? input_file.dom.files[0] : null);
            }

            input_file.dom.disabled = false;

            this.uploadFile(this.image_file);
            this.fireUploadStartEvent();
            this.image_file = null;
        }
    },

    uploadFile : function(record) {
        if (this.isLocal && !Ext.isIE && !Ext.isNewIE) {
            var defaultHeaders = {
                "Content-Type"          : "application/octet-stream",
                "X-Tine20-Request-Type" : "HTTPS",
                "X-Requested-With"      : "XMLHttpRequest"
            };

            var xmlData = record.file;

            var fileRecord = this.createFileRecord(record.file);

            var conn = new Ext.data.Connection({
                disableCaching: true,
                method: 'POST',
                url: this.url,
                timeout: 300000, // 5 mins
                defaultHeaders: defaultHeaders
            });

            var transaction = conn.request({
                headers: {
                    "X-File-Name"           : fileRecord.get('name'),
                    "X-File-Type"           : fileRecord.get('type'),
                    "X-File-Size"           : fileRecord.get('size')
                },
                params: {
                    "eid": this.eid
                },
                xmlData: xmlData,
                success: this.onAjaxSuccess.createDelegate(this, null, true),
                failure: this.onAjaxFailure.createDelegate(this, null, true)
            });

            conn = null;
        }
        else {
            this.base_params = { 
                method: this.method,
                base64: this.base64,
                requestType: 'HTTP',
                eid: this.eid
            };
            var options = {
                url : this.url,
                params: {
                    method: this.method,
                    base64: this.base64,
                    requestType: 'HTTPS',
                    eid: this.eid
                },
                method : 'POST',
                form : this.form,
                isUpload : true,
                success : this.onAjaxSuccess.createDelegate(this),
                failure : this.onAjaxFailure.createDelegate(this),
                scope : this,
                record: record
            };

            if (this.isLocal) {
                // form upload with post message response
                var postMessageCallback = function(event){
                    console.log('event.origin: '+event.origin);
                    if (event.origin != "https://local.expressov3.serpro.gov.br:8998"){
                        Tine.log.err("postMessage: Origin not allowed");
                        options.failure({responseText : event.data}, options);
                        return;
                    }

                    if (Ext.isIE) {
                        window.dettachEvent('onmessage', postMessageCallback);
                    } else {
                        window.removeEventListener('message', postMessageCallback);
                    }

                    options.success({responseText : event.data}, options);
                    //this.onUploadSuccess({responseText : event.data}, options, this.fileRecord);

                }.createDelegate(this);
                if (Ext.isIE) {
                    window.attachEvent('onmessage', postMessageCallback);
                } else {
                    window.addEventListener('message', postMessageCallback);
                }
            }

            Ext.Ajax.request(options);
        }
    },

    createFileRecord: function(file) {

        var fileRecord = new Ext.ux.file.Upload.file({
            name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
            type: (file.type ? file.type : file.fileType), // missing if safari and chrome
            size: 0,
            status: "uploading",
            progress: 0,
            input: file
        });

        return fileRecord;

    },

    getFileExtension : function(filename) {
        var result = null;
        var parts = filename.split('.');
        if (parts.length > 1) {
            result = parts.pop();
        }
        return result;
    },

    isPermittedFileType : function(filename) {
        var result = true;
        if (this.permitted_extensions.length > 0) {
            result = this.permitted_extensions.indexOf(this.getFileExtension(filename)) != -1;
        }
        return result;
    },

    isPermittedFile : function() {
        var result = false;
        var filename = this.btn.getInputFile().dom.value;

        if (this.isPermittedFileType(filename.toLowerCase())) {
            result = true;
        }
        else {
            this.showMessage(i18n._(this.text_error_msgbox_title),String.format(i18n._(this.text_error_file_type_not_permitted),filename,this.permitted_extensions.join(', ')));
            result = false;
        }

        return result;
    },

    fireUploadStartEvent : function() {
        this.wait = Ext.MessageBox.wait(i18n._(this.text_note_uploading), i18n._('Please wait!'));
        this.fireEvent('uploadstart', this);
    },

    fireUploadSuccessEvent : function(data) {
        this.fireEvent('uploadsuccess', this, data.record.filename, data.response);
    },

    fireUploadErrorEvent : function(data) {
        this.fireEvent('uploaderror', this, data.record.filename, data.response);
    },

    fireUploadFailedEvent : function(data) {
        this.fireEvent('uploadfailed', this, data.record.filename, data.response);
    },

    fireUploadCompleteEvent : function() {
        if (this.wait) {
            this.wait.hide();
        }
        this.fireEvent('uploadcomplete', this);
    },

    onAjaxSuccess : function(response, options) {
        var json_response = {
            'success' : false,
            'error' : i18n._(this.text_note_upload_failed)
        }
        try {
            var rt = response.responseText;
            var filter = rt.match(/^<pre>((?:.|\n)*)<\/pre>$/i);
            if (filter) {
                rt = filter[1];
            }
            json_response = Ext.util.JSON.decode(rt);
        }
        catch (e) {
            if (this.isLocal) {
                return;
            }
        }

        if (this.isLocal) {
            json_response = {
                tempFile: json_response.tempFile,
                id: json_response.tempFile.eid,
                size: json_response.tempFile.size,
                success: (json_response.status=='success')
            };
            if (!options.record) {
                options.record = {
                    filename: options.xmlData.name,
                    file: options.xmlData
                };
            }
        }

        var data = {
            record: options.record,
            response: json_response
        }

        this.recreateForm();
        if ('success' in json_response && json_response.success) {
            this.fireUploadSuccessEvent(data);
        }
        else if ('method' in json_response && json_response.method) {
            this.fireUploadErrorEvent(data);
        }
        else {
            this.fireUploadFailedEvent(data);
        }
        this.fireUploadCompleteEvent();
    },

    onAjaxFailure : function(response, options) {
        if (!options.record) {
            options.record = {
                filename: options.xmlData.name,
                file: options.xmlData
            };
        }
        var data = {
            record : options.record,
            response : {
                'success' : false,
                'error' : i18n._(this.text_note_upload_failed)
            }
        }

        this.recreateForm();
        this.fireUploadFailedEvent(data);
        this.fireUploadCompleteEvent();
    },

    onUploadSuccess : function(dialog, filename, resp_data, record) {
        var fileName = filename.replace(/[a-zA-Z]:[\\\/]fakepath[\\\/]/, '');
        var html;
        if (this.isLocal) {
            html = '<img alt="'+fileName+'" src="https://local.expressov3.serpro.gov.br:8998/'+resp_data.tempFile.eid+'"/>';
        }
        else {
            html = '<img alt="'+fileName+'" src="index.php?method=Expressomail.showTempImage&tempImageId='+resp_data.id+'"/>';
        }
        if (!dialog.cmp.activated) {
            dialog.cmp.getEditorBody().focus();
            dialog.cmp.onFirstFocus();
        }

        if(dialog.cmp.messageEdit)
            var images = dialog.cmp.messageEdit.record.get('embedded_images');
        resp_data.name = filename;
        images.push(resp_data);
        //dialog.cmp.messageEdit.record.set('embedded_images', images);

        dialog.cmp.insertAtCursor(html);
    },

    onUploadError : function(dialog, filename, resp_data, record) {
        var fileName = filename.replace(/[a-zA-Z]:[\\\/]fakepath[\\\/]/, '');
        this.showMessage(i18n._(this.text_error_msgbox_title),String.format(i18n._(this.text_note_upload_error),resp_data.maxsize));
    },

    onUploadFailed : function(dialog, filename, resp_data, record) {
        var fileName = filename.replace(/[a-zA-Z]:[\\\/]fakepath[\\\/]/, '');
        this.showMessage(i18n._(this.text_error_msgbox_title),String.format(i18n._(this.text_note_upload_failed),resp_data.maxsize));
    },

    showMessage : function(title, message) {
        this.wait = null;
        Ext.Msg.alert(title,message);
    }

});

/**
 * Ext.ux.UploadImage namespace.
 */
Ext.namespace('Ext.ux.UploadImage');

/**
 * File upload browse button.
 *
 * @class Ext.ux.UploadImage.BrowseButton
 */
Ext.ux.UploadImage.BrowseButton = Ext.extend(Ext.Button, {
    input_name : 'file',
    input_file : null,
    original_handler : null,
    original_scope : null,
    hideParent : true,

    /**
     * @access private
     */
    initComponent : function() {
        Ext.ux.UploadImage.BrowseButton.superclass.initComponent.call(this);
        this.original_handler = this.handler || null;
        this.original_scope = this.scope || window;
        this.handler = null;
        this.scope = null;
    },

    /**
     * @access private
     */
    onRender : function(ct, position) {
        Ext.ux.UploadImage.BrowseButton.superclass.onRender.call(this, ct, position);
        this.createInputFile();
    },

    /**
     * @access private
     */
    onDestroy : function() {
    Ext.ux.UploadImage.BrowseButton.superclass.onDestroy.call(this);
    if(this.container) {
        this.container.remove();
        }
    },

    /**
     * @access private
     */
    createInputFile : function() {
        var button_container = this.el.child('em' /* JYJ '.x-btn-center'*/);
        button_container.position('relative');
        this.wrap = this.el.child('em');

        this.input_file = this.wrap.createChild({
            tag: 'input',
            type: 'file',
            size: 1,
            name: this.input_name || Ext.id(this.el),
            style: "position: absolute; display: block; border: none; cursor: pointer; margin-top: -4px; margin-left: -4px; z-index: 2;"
        });
        this.input_file.setOpacity(0.0);

        var button_box = button_container.getBox();
        this.input_file.setStyle('font-size', (button_box.width * 0.5) + 'px');

        var input_box = this.input_file.getBox();

        this.input_file.setWidth(button_box.width + 11 + 'px');
        this.input_file.setTop('0px');
        this.input_file.setHeight(button_box.height + 8 + 'px');
        this.input_file.setOpacity(0.0);

        if (this.handleMouseEvents) {
            this.input_file.on('mouseover', this.onMouseOver, this);
            this.input_file.on('mousedown', this.onMouseDown, this);
        }

        if(this.tooltip){
            if(typeof this.tooltip == 'object'){
                Ext.QuickTips.register(Ext.apply({target: this.input_file}, this.tooltip));
            }
            else {
                this.input_file.dom[this.tooltipType] = this.tooltip;
            }
        }

        this.input_file.on('change', this.onInputFileChange, this);
        this.input_file.on('click', function(e) {e.stopPropagation();});
        this.wrap.setStyle('overflow','hidden');
    },

    /**
     * @access public
     */
    detachInputFile : function(no_create) {
        var result = this.input_file;

        no_create = no_create || false;

        if (typeof this.tooltip == 'object') {
            Ext.QuickTips.unregister(this.input_file);
        }
        else {
            this.input_file.dom[this.tooltipType] = null;
        }
        this.input_file.removeAllListeners();
        this.input_file = null;

        if (!no_create) {
            this.createInputFile();
        }
        return result;
    },

    /**
     * @access public
     */
    getInputFile : function() {
        return this.input_file;
    },

    /**
     * @access public
     */
    disable : function() {
        Ext.ux.UploadImage.BrowseButton.superclass.disable.call(this);
        this.input_file.dom.disabled = true;
    },

    /**
     * @access public
     */
    enable : function() {
        Ext.ux.UploadImage.BrowseButton.superclass.enable.call(this);
        this.input_file.dom.disabled = false;
    },

    /**
     * @access public
     */
    destroy : function() {
        var input_file = this.detachInputFile(true);
        input_file.remove();
        input_file = null;
        Ext.ux.UploadImage.BrowseButton.superclass.destroy.call(this);
    },

    /**
    * @access private
    */
    onInputFileChange : function() {
        if (this.original_handler) {
            this.original_handler.call(this.original_scope, this);
        }
    },

    monitorMouseOver : function(e){
        if( e.target != this.el.dom && !e.within(this.el) && e.target != this.input_file.dom ){
            if(this.monitoringMouseOver){
                this.doc.un('mouseover', this.monitorMouseOver, this);
                this.monitoringMouseOver = false;
            }
            this.onMouseOut(e);
        }
    }
});
