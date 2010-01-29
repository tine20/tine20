/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FileUploadGrid
 * @extends     Ext.grid.GridPanel
 * 
 * <p>FileUpload grid for dialogs</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * 
 * @constructor
 * Create a new  Tine.widgets.grid.FileUploadGrid
 */
Tine.widgets.grid.FileUploadGrid = Ext.extend(Ext.grid.GridPanel, {
    
    /**
     * @private
     * 
     * TODO: use dynamic id here?
     */
    id: 'tinebase-file-grid',
    
    /**
     * actions
     * 
     * @type {Object}
     * @private
     */
    actions: {
        add: null,
        remove: null
    },
    
    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    loadMask: true,
    autoExpandColumn: 'name',
    
    /**
     * init
     * @private
     */
    initComponent: function() {
        
        this.id = this.id + Ext.id();
        
        this.initToolbar();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();
        
        Tine.widgets.grid.FileUploadGrid.superclass.initComponent.call(this);
    },
    
    /**
     * on upload failure
     * @private
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(
            _('Upload Failed'), 
            _('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size: ') 
                + Tine.Tinebase.registry.get('maxFileUploadSize')
        ).setIcon(Ext.MessageBox.ERROR);
        this.loadMask.hide();
    },
    
    /**
     * on remove
     * @param {} _button
     * @param {} _event
     */
    onRemove: function(_button, _event) {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
        }                       
    },

    /**
     * init toolbar
     * @private
     */
    initToolbar: function() {
        this.actions.add = new Ext.Action({
            text: _('Add file'),
            iconCls: 'actionAdd',
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({
                multiple: true,
                dropElSelector: 'div[id=' + this.id + ']'
            })],
            handler: this.onFilesSelect
        });

        this.actions.remove = new Ext.Action({
            text: _('Remove file'),
            iconCls: 'actionRemove',
            scope: this,
            disabled: true,
            handler: this.onRemove
        });
        
        this.tbar = [                
            this.actions.add,
            this.actions.remove
        ]; 
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        this.store = new Ext.data.SimpleStore({
            fields: Ext.ux.file.Uploader.file
        });
        
        // init files (on forward)
        if (this.record.get('files')) {
            var files = this.record.get('files');
            for (var i=0; i < files.length; i++) {
                this.store.add(new Ext.data.Record(files[i]));
            }
        }
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'name',
                dataIndex: 'name',
                width: 300,
                header: 'name',
                renderer: function(value, metadata, record) {
                    var val = value;
                    if (record.get('status') !== 'complete') {
                        //val += ' (' + record.get('progress') + '%)';
                        metadata.css = 'x-fmail-uploadrow';
                    }
                    
                    return val;
                }
            },{
                resizable: true,
                id: 'size',
                dataIndex: 'size',
                width: 70,
                header: 'size',
                renderer: Ext.util.Format.fileSize
            },{
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 70,
                header: 'type'
                // TODO show type icon?
                //renderer: Ext.util.Format.fileSize
            }
        ]);
    },

    /**
     * init sel model
     * @private
     */
    initSelectionModel: function() {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        this.selModel.on('selectionchange', function(selModel) {
            var rowCount = selModel.getCount();
            this.actions.remove.setDisabled(rowCount == 0);
        }, this);
    },
    
    /**
     * upload new file and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function(fileSelector, e) {
        var uploader = new Ext.ux.file.Uploader({
            maxFileSize: 67108864, // 64MB
            fileSelector: fileSelector
        });
                
        uploader.on('uploadfailure', this.onUploadFail, this);
        
        var files = fileSelector.getFileList();
        Ext.each(files, function(file){
            var fileRecord = uploader.upload(file);
            this.store.add(fileRecord);
        }, this);
    }
});
