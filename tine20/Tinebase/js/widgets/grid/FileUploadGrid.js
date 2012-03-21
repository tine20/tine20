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
Tine.widgets.grid.FileUploadGrid = Ext.extend(Ext.grid.GridPanel, {
    
    /**
     * @private
     */
    id: 'tinebase-file-grid',
    
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
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    autoExpandColumn: 'name',
    showProgress: true,
    
    /**
     * init
     * @private
     */
    initComponent: function () {
        
        this.record = this.record || null;
        this.id = this.id + Ext.id();
        
     // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            containerProperty: 'container_id', 
            evalGrants: false
        });
        
        
        this.initToolbarAndContextMenu();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();
        
    
        this.plugins = [ new Ext.ux.grid.GridViewMenuPlugin({}) ];
        this.enableHdMenu = false;
      
        Tine.widgets.grid.FileUploadGrid.superclass.initComponent.call(this);
        
        this.on('rowcontextmenu', function (grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if (! selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            this.contextMenu.showAt(e.getXY());
        }, this);
        
               
    },
    
    /**
     * on upload failure
     * @private
     */
    onUploadFail: function (uploader, fileRecord) {
        
        var dataSize;
        if (fileRecord.html5upload) {
            dataSize = dataSize = Tine.Tinebase.registry.get('maxPostSize');
        }
        else {
            dataSize = Tine.Tinebase.registry.get('maxFileUploadSize');
        }
        
        Ext.MessageBox.alert(
            _('Upload Failed'), 
            _('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size: ') + parseInt(dataSize, 10) / 1048576 + ' MB'
        ).setIcon(Ext.MessageBox.ERROR);
        
        this.getStore().remove(fileRecord);
        if(this.loadMask) this.loadMask.hide();
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
            upload.setPaused(true);
        }
    },
    
    
    /**
     * on pause
     * @param {} button
     * @param {} event
     */
    onPause: function (button, event) {
 
        var selectedRows = this.getSelectionModel().getSelections();
        for(var i=0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.setPaused(true);
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
        for(var i=0; i < selectedRows.length; i++) {
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
        
        this.action_add = new Ext.Action(this.getAddAction());

        this.action_remove = new Ext.Action({
            text: _('Remove file'),
            iconCls: 'action_remove',
            scope: this,
            disabled: true,
            handler: this.onRemove
        });
        
        this.action_pause = new Ext.Action({
            text: _('Pause upload'),
            iconCls: 'action_pause',
            scope: this,
//            disabled: true,
            handler: this.onPause,
            actionUpdater: this.isPauseEnabled
        });
        
        this.action_resume = new Ext.Action({
            text: _('Resume upload'),
            iconCls: 'action_resume',
            scope: this,
//            disabled: true,
            handler: this.onResume,
            actionUpdater: this.isResumeEnabled
        });
        
        this.tbar = [
            this.action_add,
            this.action_remove
        ];
        
        this.contextMenu = new Ext.menu.Menu({
            items:  [
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
        
        this.loadRecord(this.record);
    },
    
    /**
     * returns add action
     * 
     * @return {Object} add action config
     */
    getAddAction: function () {
        return {
            text: _('Add file'),
            iconCls: 'action_add',
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                dropElSelector: 'div[id=' + this.id + ']'
            }],
            handler: this.onFilesSelect
        };
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
     * @private
     */
    initColumnModel: function () {
        this.cm = new Ext.grid.ColumnModel(this.getColumns());
    },
    
    getColumns: function() {
        var columns = [{
            resizable: true,
            id: 'name',
            dataIndex: 'name',
            width: 300,
            header: _('name'),
            renderer: Ext.ux.PercentRendererWithName
        }, {
            resizable: true,
            id: 'size',
            dataIndex: 'size',
            width: 70,
            header: _('size'),
            renderer: Ext.util.Format.fileSize
        }, {
            resizable: true,
            id: 'type',
            dataIndex: 'type',
            width: 70,
            header: _('type')
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
            this.action_remove.setDisabled(rowCount === 0);
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
    
    isPauseEnabled: function(action, grants, records) {
         
        for(var i=0; i<records.length; i++) {
            if(records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }
        
        for(var i=0; i < records.length; i++) {
            if(!records[i].get('status') || (records[i].get('type ') !== 'folder' && records[i].get('status') !== 'paused'
                    &&  records[i].get('status') !== 'uploading' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
        for(var i=0; i < records.length; i++) {
            if(records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(records[i].get('status') && records[i].get('status') !== 'uploading'){
                action.setDisabled(true);
            }
            
        }             
    },

    isResumeEnabled: function(action, grants, records) {
        for(var i=0; i<records.length; i++) {
            if(records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }
       
        for(var i=0; i < records.length; i++) {
            if(!records[i].get('status') || (records[i].get('type ') !== 'folder' &&  records[i].get('status') !== 'uploading' 
                    &&  records[i].get('status') !== 'paused' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
        for(var i=0; i < records.length; i++) {
            if(records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(records[i].get('status') && records[i].get('status') !== 'paused'){
                action.setDisabled(true);
            }
            
        }   
    }
});
