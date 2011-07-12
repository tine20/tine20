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
    
    /**
     * init
     * @private
     */
    initComponent: function () {
        this.record = this.record || null;
        this.id = this.id + Ext.id();
        
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
    
    // onUploadComplete
    
    /**
     * on upload failure
     * @private
     */
    onUploadComplete: function (uploader, fileRecord) {
        	
    	
    	var originalFileRecord = Tine.Tinebase.uploadManager.getOriginalFileRecord(fileRecord.get('uploadKey'));

    	console.log("reading fileRecord id " + originalFileRecord.id + " for name " + fileRecord.get('uploadKey'));
    	console.log("final progress was " + originalFileRecord.get("progress") + " %");
    	
    	Tine.Tinebase.uploadManager.finishUpload(fileRecord.get('uploadKey'));
    	
    	originalFileRecord.beginEdit();
    	originalFileRecord.set('status', 'complete');
    	originalFileRecord.endEdit();
    	
        if(this.loadMask) this.loadMask.hide();
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
     * on progress failure
     * @private
     */
    onUploadProgress: function (uploader, fileRecord, percent) {
    	
    	var originalFileRecord = Tine.Tinebase.uploadManager.getOriginalFileRecord(fileRecord.get('uploadKey'));

    	originalFileRecord.beginEdit();
    	originalFileRecord.set('progress', percent);
    	originalFileRecord.endEdit();
    	
    	console.log("upload in progress.. (" + originalFileRecord.get("progress") + " %)");
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
        }
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
        
//        this.tbar = (this.showTopToolbar === true) ? [
//            this.action_add,
//            this.action_remove
//        ] : [];
        this.tbar = [
            this.action_add,
            this.action_remove
        ];
        
        this.contextMenu = new Ext.menu.Menu({
            items:  this.action_remove
        });
    },
    
    /**
     * init store
     * @private
     */
    initStore: function () {
        this.store = new Ext.data.SimpleStore({
            fields: Ext.ux.file.Uploader.file
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
                var file = new Ext.ux.file.Uploader.file(files[i]);
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
        this.cm = new Ext.grid.ColumnModel([{
            resizable: true,
            id: 'name',
            dataIndex: 'name',
            width: 300,
            header: _('name'),
            renderer: function (value, metadata, record) {
                var val = value;
                if (record.get('status') !== 'complete') {
//                    val += ' (' + record.get('progress') + '%)';
                    metadata.css = 'x-tinebase-uploadrow';
                }
                
                return val;
            }
        }, {
            resizable: true,
            id: 'progress',
            dataIndex: 'progress',
            width: 70,
            header: _('progress'),
            renderer: Ext.ux.PercentRenderer
//            	function (value, metadata, record) {
//                return value + " %";        
//            }
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
        }]);
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
        }, this);
    },
    
    /**
     * upload new file and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function (fileSelector, e) {
    	
    	
    	// todo: bei jedem file select / drop uploader neu instanzieren nötig ??
        var uploader = new Ext.ux.file.Uploader({
            fileSelector: fileSelector
        });
        
        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {
 
        	var uploadKey = Tine.Tinebase.uploadManager.registerUpload(file);        	
            var fileRecord = uploader.upload(uploadKey);           
            fileRecord.set('uploadKey', uploadKey);           
			Tine.Tinebase.uploadManager.setOriginalFileRecord(uploadKey, fileRecord);
			
        	if(fileRecord.data.status !== 'failure' ) {
	            this.store.add(fileRecord);
        	}
        }, this);
        
        uploader.on('uploadfailure', this.onUploadFail, this);
        uploader.on('uploadcomplete', this.onUploadComplete, this);
        uploader.on('uploadprogress', this.onUploadProgress, this);


    },
    
    /**
     * returns true if files are uploading atm
     * 
     * @return {Boolean}
     */
    isUploading: function () {
        var uploadingFiles = this.store.query('status', 'uploading');
        return (uploadingFiles.getCount() > 0);
    }
});
