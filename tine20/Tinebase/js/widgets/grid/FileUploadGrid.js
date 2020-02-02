/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id
 */

/*global Ext, Tine*/

Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FileUploadGrid
 * @extends     Ext.grid.GridPanel
 *
 * <p>FileUpload grid for dialogs</p>
 * <p>
 * </p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 *
 * @constructor Create a new  Tine.widgets.grid.FileUploadGrid
 */
Tine.widgets.grid.FileUploadGrid = Ext.extend(Ext.grid.EditorGridPanel, {

    /**
     * @cfg filesProperty
     * @type String
     */
    filesProperty: 'files',

    /**
     * @cfg showTopToolbar
     * @type Boolean
     * TODO     think about that -> when we deactivate the top toolbar, we lose the dropzone for files!
     */
    //showTopToolbar: null,

    /**
     * @cfg {Bool} readOnly
     */
    readOnly: false,

    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    autoExpandColumn: 'name',
    showProgress: true,

    i18nFileString: null,


    fileSelectionDialog: null,

    /**
     * init
     * @private
     */
    initComponent: function () {
        this.i18nFileString = this.i18nFileString ? this.i18nFileString : i18n._('File');

        this.record = this.record || null;

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            evalGrants: false
        });

        this.initToolbarAndContextMenu();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();


        if (!this.plugins) {
            this.plugins = [];
        }

        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        this.enableHdMenu = false;

        Tine.widgets.grid.FileUploadGrid.superclass.initComponent.call(this);

        this.on('rowcontextmenu', function (grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if (!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            this.contextMenu.showAt(e.getXY());
        }, this);

        if (!this.record || this.record.id === 0) {
            this.on('celldblclick', function (grid, rowIndex, columnIndex, e) {
                // Don't download if the cell has an editor, just go on with the event
                if (grid.getColumns()[columnIndex] && grid.getColumns()[columnIndex].editor) {
                    return true;
                }

                // In case cell has no editor, just assume a download is intended
                e.stopEvent();
                this.onDownload()
            }, this);
        }
    },

    setReadOnly: function (readOnly) {
        this.readOnly = readOnly;
        this.action_add.setDisabled(readOnly);
        this.action_remove.setDisabled(readOnly);
    },

    /**
     * on upload failure
     * @private
     */
    onUploadFail: function (uploader, fileRecord) {

        var dataSize;
        if (fileRecord.html5upload) {
            dataSize = Tine.Tinebase.registry.get('maxPostSize');
        }
        else {
            dataSize = Tine.Tinebase.registry.get('maxFileUploadSize');
        }

        Ext.MessageBox.alert(
            i18n._('Upload Failed'),
            i18n._('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size:') + ' ' + parseInt(dataSize, 10) / 1048576 + ' MB'
        ).setIcon(Ext.MessageBox.ERROR);

        this.getStore().remove(fileRecord);
        if (this.loadMask) this.loadMask.hide();
    },

    /**
     * on remove
     * @param {} button
     * @param {} event
     */
    onRemove: function (button, event) {

        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i += 1) {
            this.store.remove(selectedRows[i]);
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            if (upload) {
                upload.setPaused(true);
            }
        }
    },


    /**
     * on pause
     * @param {} button
     * @param {} event
     */
    onPause: function (button, event) {

        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            if (upload) {
                upload.setPaused(true);
            }
        }
        this.getSelectionModel().deselectRange(0, this.getSelectionModel().getCount());
    },


    /**
     * on resume
     * @param {} button
     * @param {} event
     */
    onResume: function (button, event) {

        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.resumeUpload();
        }
        this.getSelectionModel().deselectRange(0, this.getSelectionModel().getCount());
    },


    /**
     * init toolbar and context menu
     * @private
     */
    initToolbarAndContextMenu: function () {
        var me = this;

        this.action_add = new Ext.Action(this.getAddAction());

        this.action_remove = new Ext.Action({
            text: String.format(i18n._('Remove {0}'), this.i18nFileString),
            iconCls: 'action_remove',
            scope: this,
            disabled: true,
            handler: this.onRemove
        });

        this.action_pause = new Ext.Action({
            text: i18n._('Pause upload'),
            iconCls: 'action_pause',
            scope: this,
            handler: this.onPause,
            actionUpdater: this.isPauseEnabled
        });

        this.action_resume = new Ext.Action({
            text: i18n._('Resume upload'),
            iconCls: 'action_resume',
            scope: this,
            handler: this.onResume,
            actionUpdater: this.isResumeEnabled
        });

        this.tbar = [
            this.action_add
        ];

        this.tbar.push(this.action_remove);

        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tinebase-MainContextMenu'
            }],
            items: [
                this.action_remove,
                this.action_pause,
                this.action_resume
            ]
        });

        this.actionUpdater.addActions([
            this.action_pause,
            this.action_resume
        ]);

    },

    /**
     * init store
     * @private
     */
    initStore: function () {
        this.store = new Ext.data.SimpleStore({
            fields: Ext.ux.file.Upload.file
        });

        this.store.on('add', this.onStoreAdd, this);

        this.loadRecord(this.record);
    },

    onStoreAdd: function (store, records, idx) {
        Ext.each(records, function (attachment) {
            if (attachment.get('url')) {
                // we can't use Ext.data.connection here as we can't control xhr obj. directly :-(
                var me = this,
                    url = attachment.get('url'),
                    name = url.split('/').pop(),
                    xhr = new XMLHttpRequest();

                xhr.open('GET', url, true);
                xhr.responseType = 'blob';

                store.suspendEvents();
                attachment.set('name', name);
                attachment.set('type', name.split('.').pop());
                store.resumeEvents();

                xhr.onprogress = function (e) {
                    var progress = Math.floor(100 * e.loaded / e.total) + '% loaded';
                    console.log(e);
                };


                xhr.onload = function (e) {
//                    attachment.set('type', xhr.response.type);
//                    attachment.set('size', xhr.response.size);

                    var upload = new Ext.ux.file.Upload({
                        file: new File([xhr.response], name),
                        type: xhr.response.type,
                        size: xhr.response.size
                    });
                    // work around chrome bug which dosn't take type from blob
                    upload.file.fileType = xhr.response.type;

                    var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);
                    var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);

                    upload.on('uploadfailure', me.onUploadFail, me);
                    upload.on('uploadcomplete', me.onUploadComplete, fileRecord);
                    upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, me);

                    store.remove(attachment);
                    store.add(fileRecord);

                };

                xhr.send();

            }
        }, this);
    },

    /**
     * download file
     *
     * @param {} button
     * @param {} event
     */
    onDownload: function (button, event) {
        var _ = window.lodash,
            selectedRows = this.getSelectionModel().getSelections(),
            fileRow = selectedRows[0],
            recordId = _.get(this.record, 'id', false),
            tempFile = fileRow.get('tempFile');

        if (recordId !== false && (!recordId || (Ext.isObject(tempFile && tempFile.status !== 'complete')))) {
            Tine.log.debug('Tine.widgets.grid.FileUploadGrid::onDownload - file not yet available for download');
            return;
        }

        Tine.log.debug('Tine.widgets.grid.FileUploadGrid::onDownload - selected file:');
        Tine.log.debug(fileRow);

        if (Ext.isObject(tempFile)) {
            this.downloadTempFile(tempFile.id);
        } else {
            this.downloadNode(recordId, fileRow.id)
        }
    },

    /**
     * returns add action
     *
     * @return {Object} add action config
     */
    getAddAction: function () {
        var me = this;

        return {
            text: String.format(i18n._('Add {0}'), me.i18nFileString),
            iconCls: 'action_add',
            scope: me,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDialog: false,
                dropElSelector: 'div[id=' + this.id + ']',
                handler: this.onFilesSelect.createDelegate(me)
            }],
            handler: me.openDialog
        };
    },

    // Constructs a new dialog and opens it. Better to construct a new one everyt
    openDialog: function () {
        this.fileSelectionDialog = new Tine.Tinebase.FileSelectionDialog({
            handler: this.onFilesSelect.createDelegate(this)
        });

        this.fileSelectionDialog.openWindow()
    },

    /**
     * populate grid store
     *
     * @param {} record
     */
    loadRecord: function (record) {
        if (record && record.get(this.filesProperty)) {
            var files = record.get(this.filesProperty);
            for (var i = 0; i < files.length; i += 1) {
                var file = new Ext.ux.file.Upload.file(files[i]);
                file.data.status = 'complete';
                this.store.add(file);
            }
        }
    },

    /**
     * init cm
     */
    initColumnModel: function () {
        this.cm = new Ext.grid.ColumnModel(this.getColumns());
    },

    getColumns: function () {
        var columns = [{
            resizable: true,
            id: 'name',
            dataIndex: 'name',
            width: 300,
            header: i18n._('name'),
            renderer: Ext.ux.PercentRendererWithName
        }, {
            resizable: true,
            id: 'size',
            dataIndex: 'size',
            width: 70,
            header: i18n._('size'),
            renderer: Ext.util.Format.fileSize
        }, {
            resizable: true,
            id: 'type',
            dataIndex: 'type',
            width: 70,
            header: i18n._('type')
            // TODO show type icon?
            //renderer: Ext.util.Format.fileSize
        }];

        return columns;
    },

    /**
     * init sel model
     * @private
     */
    initSelectionModel: function () {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect: true});

        this.selModel.on('selectionchange', function (selModel) {
            var rowCount = selModel.getCount();
            this.action_remove.setDisabled(this.readOnly || rowCount === 0);
            this.actionUpdater.updateActions(selModel);

        }, this);
    },

    /**
     * upload new file and add to store
     *
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function (fileSelector, e) {
        if (window.lodash.isArray(fileSelector)) {
            this.onFileSelectFromFilemanager(fileSelector);
            return;
        }


        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {

            var upload = new Ext.ux.file.Upload({
                file: file,
                fileSelector: fileSelector
            });

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);
            var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);

            upload.on('uploadfailure', this.onUploadFail, this);
            upload.on('uploadcomplete', this.onUploadComplete, fileRecord);
            upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);

            if (fileRecord.get('status') !== 'failure') {
                this.store.add(fileRecord);
            }


        }, this);

    },

    /**
     * Add one or more files from filemanager
     *
     * @param nodes
     */
    onFileSelectFromFilemanager: function (nodes) {
        var me = this;

        Ext.each(nodes, function (node) {
            var record = new Tine.Filemanager.Model.Node(node);

            if (me.store.find('name', record.get('name')) === -1) {
                me.store.add(record);
            } else {
                Ext.MessageBox.show({
                    title: i18n._('Failure'),
                    msg: i18n._('This file is already attached to this record.'),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
            }
        });
    },

    onUploadComplete: function (upload, fileRecord) {
        fileRecord.beginEdit();
        fileRecord.set('status', 'complete');
        fileRecord.set('progress', 100);
        try {
            fileRecord.commit(false);
        } catch (e) {
            console.log(e);
        }
        Tine.Tinebase.uploadManager.onUploadComplete();
    },

    /**
     * returns true if files are uploading atm
     *
     * @return {Boolean}
     */
    isUploading: function () {
        var uploadingFiles = this.store.query('status', 'uploading');
        return (uploadingFiles.getCount() > 0);
    },

    isPauseEnabled: function (action, grants, records) {

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }

        for (var i = 0; i < records.length; i++) {
            if (!records[i].get('status') || (records[i].get('type ') !== 'folder' && records[i].get('status') !== 'paused'
                    && records[i].get('status') !== 'uploading' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }

        action.show();

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if (records[i].get('status') && records[i].get('status') !== 'uploading') {
                action.setDisabled(true);
            }

        }
    },

    isResumeEnabled: function (action, grants, records) {
        for (var i = 0; i < records.length; i++) {
            if (records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }

        for (var i = 0; i < records.length; i++) {
            if (!records[i].get('status') || (records[i].get('type ') !== 'folder' && records[i].get('status') !== 'uploading'
                    && records[i].get('status') !== 'paused' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }

        action.show();

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if (records[i].get('status') && records[i].get('status') !== 'paused') {
                action.setDisabled(true);
            }

        }
    },

    downloadNode: function (recordId, id) {
        new Ext.ux.file.Download({
            params: {
                method: 'Tinebase.downloadRecordAttachment',
                requestType: 'HTTP',
                nodeId: id,
                recordId: recordId,
                modelName: this.app.name + '_Model_' + this.editDialog.modelName
            }
        }).start();
    },

    downloadTempFile: function (id) {
        new Ext.ux.file.Download({
            params: {
                method: 'Tinebase.downloadTempfile',
                requestType: 'HTTP',
                tmpfileId: id
            }
        }).start();
    }
});
