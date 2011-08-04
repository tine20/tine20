/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * File grid panel
 * 
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Node Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.FileGridPanel
 */
Tine.Filemanager.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {


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
    
    recordClass: Tine.Filemanager.Model.Node,
    hasDetailsPanel: false,
    evalGrants: false,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'name', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'name',
        enableFileDialog: false,
        enableDragDrop: true,
        ddGroup: 'fileDDGroup'
    },
     
    currentFolderNode : '/',
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {

        this.recordProxy = Tine.Filemanager.fileRecordBackend;
               
        this.gridConfig.cm = this.getColumnModel();

        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        this.plugins.push({
            ptype: 'ux.browseplugin',
            multiple: true,
            scope: this,
            handler: this.onFilesSelect //function(e) {alert("grid handler");}
        });

        Tine.Filemanager.GridPanel.superclass.initComponent.call(this);      

    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO    add more columns
     */
    getColumnModel: function(){
        
        var columns = [{
                id: 'name',
                header: this.app.i18n._("Name"),
                width: 70,
                sortable: true,
                dataIndex: 'name',
                renderer: Ext.ux.PercentRendererWithName
            },{
                id: 'size',
                header: this.app.i18n._("Size"),
                width: 40,
                sortable: true,
                dataIndex: 'size',
                renderer: function(value, metadata, record) {
                    if(record.data.progress < 100) {
                        return 0;                      
                    }
                    else {
                        return value;
                    }
                }
            },{
                id: 'contenttype',
                header: this.app.i18n._("Contenttype"),
                width: 50,
                sortable: true,
                dataIndex: 'contenttype'
            },{
                id: 'description',
                header: this.app.i18n._("Description"),
                width: 100,
                sortable: true,
                dataIndex: 'description'
            },{
                id: 'revision',
                header: this.app.i18n._("Revision"),
                width: 10,
                sortable: true,
                dataIndex: 'revision'
            },{
                id: 'creation_time',
                header: this.app.i18n._("Creation Time"),
                width: 50,
                sortable: true,
                dataIndex: 'creation_time',
                renderer: Tine.Tinebase.common.dateTimeRenderer
    
            },{
                id: 'created_by',
                header: this.app.i18n._("Created By"),
                width: 50,
                sortable: true,
                dataIndex: 'created_by',
                renderer: Tine.Tinebase.common.usernameRenderer                
            },{
                id: 'last_modified_time',
                header: this.app.i18n._("Last Modified Time"),
                width: 80,
                sortable: true,
                dataIndex: 'last_modified_time',
                renderer: Tine.Tinebase.common.dateTimeRenderer
            },{
                id: 'last_modified_by',
                header: this.app.i18n._("Last Modified By"),
                width: 50,
                sortable: true,
                dataIndex: 'last_modified_by',
                renderer: Tine.Tinebase.common.usernameRenderer 
            }
        ];

        
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns           
        });
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * return additional tb items
     * @private
     */
    getToolbarItems: function(){
    	
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
               
        return [
            
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
            
        ];
    },
    
    
    /**
     * returns add action / test
     * 
     * @return {Object} add action config
     */
    getAddAction: function () {
        return {
        	requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Upload'),
            handler: this.onFilesSelect,
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDrop: false
            }],
            iconCls: this.app.appName + 'IconCls'            
        };
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {

        this.action_upload = new Ext.Action(this.getAddAction());

        this.action_createFolder = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            allowMultiple: true,
            text: this.app.i18n._('Create Folder'),
            handler: this.onCreateFolder,
            iconCls: 'action_create_folder',
            disabled: true,
            scope: this
        });

        this.action_goUpFolder = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            actionType: 'goUpFolder',
            text: this.app.i18n._('Folder Up'),
            handler: this.onLoadParentFolder,
            iconCls: 'action_filemanager_folder_up',
            disabled: true,
            scope: this
        });

        this.action_save = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            actionType: 'saveLocaly',
            text: this.app.i18n._('Save locally'),
            actionUpdater: this.updateSaveAction,
            handler: function(){ alert("Save locally"); },
            iconCls: 'action_filemanager_save_all',
            disabled: true,
            scope: this
        });
             
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.app.i18n._('Delete'),
            pluralText: this.app.i18n._('Delete'),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
            text: this.app.i18n._('Delete'),
            handler: this.onDeleteItems,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.action_renameItem = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: false,
            singularText: this.app.i18n._('Rename'),
            pluralText: this.app.i18n._('Rename'),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
            text: this.app.i18n._('Rename'),
            handler: this.onRenameItem,
            disabled: false,
            iconCls: 'action_rename',
            scope: this
        });
        
        this.action_pause = new Ext.Action({
            text: _('Pause upload'),
            iconCls: 'action_pause',
            scope: this,
//            disabled: true,
            handler: this.onPause
        });
        
        this.action_resume = new Ext.Action({
            text: _('Resume upload'),
            iconCls: 'resume_pause',
            scope: this,
//            disabled: true,
            handler: this.onResume
        });
        
        this.actionUpdater.addActions([
            this.action_upload,
            this.action_deleteRecord,
            this.action_createFolder,
            this.action_goUpFolder,
            this.action_save
        ]);
        
        this.contextMenu = new Ext.menu.Menu({
            items: [
//                this.action_createFolder,
//                this.action_goUpFolder,
                this.action_save,
                this.action_renameItem,
                this.action_deleteRecord,
                this.action_pause,
                this.action_resume
            ]
        });
        
    },
    
    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    columns: 8,
                    items: [
                        Ext.apply(new Ext.SplitButton(this.action_upload), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                }]
                            })
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_createFolder), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_goUpFolder), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_save), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                 ]
                }, this.getActionToolbarItems()]
            });
            
            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }
        
        return this.actionToolbar;
    },
    
    /**
     * updates context menu
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateSaveAction: function(action, grants, records) {
        action.setDisabled(true);
    },
    
    
    /**
     * rename selected folder/file
     * 
     * @param button {Ext.Component}
     * @param event {Event object}
     */
    onRenameItem: function(button, event) {
        
        var app = this.app;
        var nodeName = app.i18n._('user file folder');
        
        var selectedNode = app.mainScreen.GridPanel.selectionModel.getSelections()[0];
        
        if (selectedNode) {
            var node = selectedNode;
            Ext.MessageBox.show({
                title: 'Rename ' + nodeName,
                msg: String.format(_('Please enter the new name of the {0}:'), nodeName),
                buttons: Ext.MessageBox.OKCANCEL,
                value: node.text,
                fn: function(_btn, _text){
                    if (_btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(_('Not renamed {0}'), nodeName), String.format(_('You have to supply a {0} name!'), nodeName));
                            return;
                        }
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Updating {0} "{1}"'), nodeName, node.text));
                                                
                        var filename = node.data.path;                        
                        var targetFilename = "/";
                        var sourceSplitArray = filename.split("/");
                        for (var i=1; i<sourceSplitArray.length-1; i++) {
                            targetFilename += sourceSplitArray[i] + '/'; 
                        }
                        
                        var params = {
                            method: app.appName + '.moveNodes',
                            newName: _text,
                            application: this.app.appName || this.appName,
                            sourceFilenames: [filename],
                            destinationFilenames: [targetFilename + _text]
                        };
                        
                        Ext.Ajax.request({
                            params: params,
                            scope: this,
                            success: function(_result, _request){
                                var nodeData = Ext.util.JSON.decode(_result.responseText);
                                
                                var currentFolderNode = app.mainScreen.GridPanel.currentFolderNode;
                                if(currentFolderNode){
                                    currentFolderNode.reload();
                                }                                
                                app.mainScreen.GridPanel.getStore().reload();
//                                this.fireEvent('containerrename', nodeData);
                                Ext.MessageBox.hide();
                            }
                        });
                    }
                },
                scope: this,
                prompt: true,
                icon: Ext.MessageBox.QUESTION
            });
        }
    },
    
    /**
     * create folder in current position
     * 
     * @param button {Ext.Component}
     * @param event {Event object}
     */
    onCreateFolder: function(button, event) {
        
        var app = this.app;
        var nodeName = app.i18n._('user file folder');
        
        Ext.MessageBox.prompt(String.format(_('New {0}'), nodeName), String.format(_('Please enter the name of the new {0}:'), nodeName), function(_btn, _text) {

            var currentFolderNode = app.mainScreen.GridPanel.currentFolderNode;
            if(currentFolderNode && _btn == 'ok') {
                if (! _text) {
                    Ext.Msg.alert(String.format(_('No {0} added'), nodeName), String.format(_('You have to supply a {0} name!'), nodeName));
                    return;
                }
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Creating {0}...' ), nodeName));

                var filename = currentFolderNode.attributes.path + '/' + _text;
                Tine.Filemanager.fileRecordBackend.createFolder(filename);
                
            }
        }, this);
        
        
    },

    /**
     * delete selected files / folders
     * 
     * @param button {Ext.Component}
     * @param event {Event object}
     */
    onDeleteItems: function(button, event) {

        var app = this.app;
        var nodeName = app.i18n._('user file folders');
        
        var selectedNodes = app.mainScreen.GridPanel.selectionModel.getSelections();
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the {0} ?'), nodeName), function(_btn){
            if (selectedNodes && _btn == 'yes') {
                
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting {0} ' ), nodeName ));
                Tine.Filemanager.fileRecordBackend.deleteItems(selectedNodes);
            }
        }, this);

    },
    
    /**
     * go up one folder
     * 
     * @param button {Ext.Component}
     * @param event {Event object}
     */
    onLoadParentFolder: function(button, event) {
     
        var app = this.app;
        var currentFolderNode = app.mainScreen.GridPanel.currentFolderNode;
        
        if(currentFolderNode && currentFolderNode.parentNode) {
            app.mainScreen.GridPanel.currentFolderNode = currentFolderNode.parentNode;
            currentFolderNode.parentNode.select();
        }       
    },
    
    /**
     * row doubleclick handler
     * 
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowDblClick: function(grid, row, e) {
        
        var app = this.app;
        var rowRecord = grid.getStore().getAt(row);

        var currentFolderNode = app.mainScreen.westPanel.containerTreePanel.getNodeById(rowRecord.id);
        if(currentFolderNode) {
            currentFolderNode.select();
            currentFolderNode.expand();
            app.mainScreen.GridPanel.currentFolderNode = currentFolderNode; 
        }
    }, 
    
    
    /**
     * on upload failure
     * @private
     */
    onUploadFail: function () {
        Ext.MessageBox.alert(
            _('Upload Failed'), 
            _('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size: ') + Tine.Tinebase.registry.get('maxFileUploadSize')
        ).setIcon(Ext.MessageBox.ERROR);
        this.loadMask.hide();
    },
    
    /**
     * on remove
     * @param {} button
     * @param {} event
     */
    onRemove: function (button, event) {
        
        var selectedRows = this.selectionModel.getSelections();
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
 
        var selectedRows = this.selectionModel.getSelections();    
        for(var i=0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.setPaused(true);
        }       
    },

    
    /**
     * on resume
     * @param {} button
     * @param {} event
     */
    onResume: function (button, event) {

        var selectedRows = this.selectionModel.getSelections();
        for(var i=0; i < selectedRows.length; i++) {
            var upload = Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.resumeUpload();
        }
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
    
    onUploadComplete: function(upload, response) {
      
        var fileName = response.tempFile.name;
        Tine.Tinebase.uploadManager.onUploadComplete();
        Tine.Filemanager.attachFileToNode(fileName, response.tempFile.id);

    },
    
    /**
     * upload new file and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function (fileSelector, e) {
       
        var app = Tine.Tinebase.appMgr.get('Filemanager');
        var grid = app.mainScreen.GridPanel; 
        var gridStore = grid.store;
        
        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {

            var fileName = file.name || file.fileName;
            Tine.Filemanager.createNode(grid.currentFolderNode.attributes.path + '/' + fileName, "file");
            
            var upload = new Ext.ux.file.Upload({}, file);

            upload.on('uploadfailure', grid.onUploadFail, this);
            upload.on('uploadcomplete', grid.onUploadComplete, this);
            upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);            
            var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);  
                    
            if(fileRecord.data.status !== 'failure' ) {
                gridStore.add(fileRecord);
            }

            
        }, this);
    }

});
