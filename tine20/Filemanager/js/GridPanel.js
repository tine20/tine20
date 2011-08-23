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
     
    ddGroup : 'fileDDGroup',  
    currentFolderNode : '/',
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {

        this.recordProxy = Tine.Filemanager.fileRecordBackend;
               
        this.gridConfig.cm = this.getColumnModel();

        this.defaultFilters = [
                               {field: 'query', operator: 'contains', value: ''},
                               {field: 'path', operator: 'equals', value: '/'}
                               ];

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
        this.getStore().on('load', this.onLoad);
//        this.initDropZone();
        
    },
    
    afterRender: function() {
        
        Tine.Filemanager.GridPanel.superclass.afterRender.call(this);  
        this.action_upload.setDisabled(true);
        this.initDropZone();

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
                renderer: Ext.ux.file.Upload.fileSize               
            },{
                id: 'contenttype',
                header: this.app.i18n._("Contenttype"),
                width: 50,
                sortable: true,
                dataIndex: 'contenttype',
                renderer: function(value, metadata, record) {
                    
                    var app = Tine.Tinebase.appMgr.get('Filemanager');
                    if(record.data.type == 'folder') {
                        return app.i18n._("Folder");
                    }
                    else {
                        return value;
                    }
                }
            },{
                id: 'revision',
                header: this.app.i18n._("Revision"),
                width: 10,
                sortable: true,
                dataIndex: 'revision',
                renderer: function(value, metadata, record) {
                    if(record.data.type == 'folder') {
                        return '';
                    }
                    else {
                        return value;
                    }
                }
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
            disabled: true,
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDrop: false,
                disable: true
            }],
            iconCls: this.app.appName + 'IconCls'            
        };
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * @private
     */
    initActions: function() {

        this.action_upload = new Ext.Action(this.getAddAction());

        this.action_createFolder = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'reply',
            allowMultiple: true,
            text: this.app.i18n._('Create Folder'),
            handler: this.onCreateFolder,
            iconCls: 'action_create_folder',
            disabled: true,
            scope: this
        });

        this.action_goUpFolder = new Ext.Action({
//            requiredGrant: 'readGrant',
            allowMultiple: true,
            actionType: 'goUpFolder',
            text: this.app.i18n._('Folder Up'),
            handler: this.onLoadParentFolder,
            iconCls: 'action_filemanager_folder_up',
            disabled: true,
            scope: this
        });

        this.action_download = new Ext.Action({
            requiredGrant: 'exportGrant',
            allowMultiple: false,
            actionType: 'saveLocaly',
            text: this.app.i18n._('Save locally'),
            actionUpdater: this.updateSaveAction,
            handler: this.onDownload,
            iconCls: 'action_filemanager_save_all',
            disabled: true,
            scope: this
        });
//             
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
        
//        this.action_renameItem = new Ext.Action({
//            requiredGrant: 'editGrant',
//            allowMultiple: false,
//            singularText: this.app.i18n._('Rename'),
//            pluralText: this.app.i18n._('Rename'),
//            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
//            text: this.app.i18n._('Rename'),
//            handler: this.onRenameItem,
//            disabled: false,
//            iconCls: 'action_rename',
//            scope: this
//        });
        
//        this.action_pause = new Ext.Action({
//            text: _('Pause upload'),
//            iconCls: 'action_pause',
//            scope: this,
//            handler: this.onPause
//        });
//        
//        this.action_resume = new Ext.Action({
//            text: _('Resume upload'),
//            scope: this,
//            iconCls: 'action_resume',
//            handler: this.onResume
//        });
        
        
        Ext.apply(Tine.widgets.tree.ContextMenu, Tine.Filemanager.GridContextMenu);
        this.contextMenu = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.app.getMainScreen().getWestPanel().getContainerTreePanel().containerName),
            actions: ['delete', 'rename', 'download', 'resume', 'pause'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        }); 
        
        this.actionUpdater.addActions(this.contextMenu.items);

        this.actionUpdater.addActions([
                                       this.action_createFolder,
                                       this.action_goUpFolder,
                                       this.action_download,
//                                       this.action_renameItem,
                                       this.action_deleteRecord
                                       ]);

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
                        Ext.apply(new Ext.Button(this.action_download), {
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
        for(var i=0; i<records.length; i++) {
            if(records[i].data.type === 'folder') {
                action.setDisabled(true);
                return;
            }
        }
        action.setDisabled(false);
        
        var grid = this;
        var selectedRows = grid.selectionModel.getSelections(); 
        
        if(selectedRows.length > 1) {
            action.setDisabled(true);
        }
        else {
            action.setDisabled(false);
        }
    },
    
    
    /**
     * rename selected folder/file
     * 
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
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
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
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
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
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
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
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
     * grid row doubleclick handler
     * 
     * @param {Tine.Filemanager.GridPanel} grid
     * @param {} row record
     * @param {Ext.EventObjet} e
     */
    onRowDblClick: function(grid, row, e) {
        
        var app = this.app;
        var rowRecord = grid.getStore().getAt(row);
        var treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel();
        
        var currentFolderNode;
        if(rowRecord.data.path == '/personal/system') {
            currentFolderNode = treePanel.getNodeById('personal');
            
        }
        else if(rowRecord.data.path == '/shared') {
            currentFolderNode = treePanel.getNodeById('shared');
            
        }
        else if(rowRecord.data.path == '/personal') {
            currentFolderNode = treePanel.getNodeById('otherUsers');
            
        }
        else {
            currentFolderNode = treePanel.getNodeById(rowRecord.id);
        }
        
        if(currentFolderNode) {
            currentFolderNode.select();
            currentFolderNode.expand();
            app.mainScreen.GridPanel.currentFolderNode = currentFolderNode; 
        }
    }, 
    
    
    /**
     * on upload failure
     * 
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
     * on remove handler
     * 
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
     * copies uploaded temporary file to target location
     * 
     * @param upload    {Ext.ux.file.Upload}
     * @param response  {Http response} 
     */
    onUploadComplete: function(upload, response) {
              
        var app = Tine.Tinebase.appMgr.get('Filemanager');
        var grid = app.mainScreen.GridPanel; 
        var currentPath = app.getMainScreen().getCenterPanel().currentFolderNode.attributes.path;
        var fileName = response.name;
        Tine.Tinebase.uploadManager.onUploadComplete();
//        Tine.Filemanager.createNode(currentPath + '/' + fileName, "file", response.id, false, this.onNodeCreated);
        
        // $filename, $type, $tempFileId, $forceOverwrite
        Ext.Ajax.request({
            timeout: 10*60*1000, // Overriding Ajax timeout - important!
            params: {
                method: 'Filemanager.createNode',
                filename: currentPath + '/' + fileName,
                type: 'file',
                tempFileId: response.id,
                forceOverwrite: true
            },
            success: grid.onNodeCreated.createDelegate(this, [upload], true), 
            failure: grid.onNodeCreated.createDelegate(this, [upload], true)
        });
        
    },
    
    /**
     * updating fileRecord after creating node
     * 
     * @param response
     * @param request
     * @param upload
     */
    onNodeCreated: function(response, request, upload) {
       
        var record = Ext.util.JSON.decode(response.responseText);
        var app = Tine.Tinebase.appMgr.get('Filemanager');
        var grid = app.getMainScreen().getCenterPanel(); 
        
        var fileRecord = upload.fileRecord;
        fileRecord.beginEdit();
        fileRecord.set('contenttype', record.contenttype);
        fileRecord.set('created_by', Tine.Tinebase.registry.get('currentAccount'));
        fileRecord.set('creation_time', record.creation_time);
        fileRecord.set('revision', record.revision);
        fileRecord.set('last_modified_by', record.last_modified_by);
        fileRecord.set('last_modified_time', record.last_modified_time);
        fileRecord.set('size', record.size);
        fileRecord.set('name', record.name);
        fileRecord.set('path', record.path);
        fileRecord.commit(false);
        
        grid.pagingToolbar.enable();
       
    },
    
    /**
     * upload new file and add to store
     * 
     * @param {} btn
     * @param {} e
     */
    onFilesSelect: function (fileSelector, e) {
       
        var app = Tine.Tinebase.appMgr.get('Filemanager');
        var grid = app.getMainScreen().getCenterPanel(); 
        var gridStore = grid.store;
        
        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {

            var fileName = file.name || file.fileName;
            
            var filePath = grid.currentFolderNode.attributes.path + '/' + fileName;
            Tine.Filemanager.createNode(filePath, "file", [], false);
            
            var upload = new Ext.ux.file.Upload({}, file, fileSelector, filePath);

            upload.on('uploadfailure', grid.onUploadFail, this);
            upload.on('uploadcomplete', grid.onUploadComplete, this);
            upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);            
            var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);  
                    
            Tine.log.debug(uploadKey);
            
            if(fileRecord.data.status !== 'failure' ) {
                gridStore.add(fileRecord);
            }           
        }, this);
        
    },
    
    /**
     * download file
     * 
     * @param {} button
     * @param {} event
     */
    onDownload: function(button, event) {
        
        var app = Tine.Tinebase.appMgr.get('Filemanager');
        var grid = app.getMainScreen().getCenterPanel(); 
        var selectedRows = grid.selectionModel.getSelections();
        
        var fileRow = selectedRows[0];
               
        var downloadPath = fileRow.data.path;
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Filemanager.downloadFile',
                requestType: 'HTTP',
                path: downloadPath
            }
        }).start();
    },
    
    /**
     * grid on load handler
     * 
     * @param grid
     * @param records
     * @param options
     */
    onLoad: function(store, records, options){

        for(var i=records.length-1; i>=0; i--) {
            var record = records[i];
            if(record.data.type == 'file' && (!record.data.size || record.data.size == 0)) {
                var upload = Tine.Tinebase.uploadManager.getUpload(record.data.path);

                if(upload) {
                      if(record.get('name') === upload.fileRecord.get('name')) {
                        var index = this.indexOf(record);
                        this.remove(record);
                        this.insert(index, [upload.fileRecord]);
                    }
                }
            }
        }
    },
    
    /**
     * init grid drop arget
     */
    initDropZone: function(){
              
        var ddrow = new Ext.dd.DropTarget(this.getEl(), {  
            ddGroup : 'fileDDGroup',  
            notifyDrop : function(dragSource, e, data){  
                
                var dragData = dragSource.getDragData(e), 
                    nodes = data.selections,
                    app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),
                    grid = app.getMainScreen().getCenterPanel(),
                    target;

                var dropIndex;
                if(dragData) {
                    dropIndex = dragData.rowIndex;                   
                }
                else {
                    dropIndex = grid.getView().findRowIndex(e.target);
                }

                target = grid.getStore().getAt(dropIndex);    

              
                if(!target) {
                    return false;
                }
                
                if(target.data.type === 'file' && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }
                
                if(e.ctrlKey) {
                    Tine.Filemanager.fileRecordBackend.copyNodes(nodes, target);
                }
                else {
                    Tine.Filemanager.fileRecordBackend.moveNodes(nodes, target);
                }          
            },
            
            notifyOver : function( dragSource , e, data ) {
                
                var dragData = dragSource.getDragData(e), 
                    app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),
                    grid = app.getMainScreen().getCenterPanel(),
                    target;
                
                var dropIndex;
                if(dragData) {
                    dropIndex = dragData.rowIndex;                   
                }
                else {
                    dropIndex = grid.getView().findRowIndex(e.target);
                }
                    
                target = grid.getStore().getAt(dropIndex);    
               
                if(!target) {
                    return false;
                }
                
                if(target.data.type === 'file' && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }
                
                return this.dropAllowed;
                
            }
        });  
        

    }

});
