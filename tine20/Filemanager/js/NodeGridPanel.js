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
 * @class       Tine.Filemanager.NodeGridPanel
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
Tine.Filemanager.NodeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @cfg filesProperty
     * @type String
     */
    filesProperty: 'files',

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
    evalGrants: true,

    /**
     * grid specific
     * @private
     */
    ddGroup: 'fileDDGroup',
    currentFolderNode: '/',

    /**
     * Prevent download and edit of nodes/files and so on. Only allow the selection of items
     */
    selectOnly: false,

    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        Ext.applyIf(this.defaultSortInfo, {field: 'name', direction: 'DESC'});
        Ext.applyIf(this.gridConfig, {
            autoExpandColumn: 'name',
            enableFileDialog: false,
            enableDragDrop: true,
            ddGroup: 'fileDDGroup'
        });

        if (this.readOnly) {
            this.gridConfig.enableDragDrop = false;
        }

        this.recordProxy = Tine.Filemanager.fileRecordBackend;

        this.gridConfig.cm = this.getColumnModel();

        this.defaultFilters = [
            {field: 'query', operator: 'contains', value: ''},
            {field: 'path', operator: 'equals', value: '/'}
        ];

        this.plugins = this.plugins || [];

        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();

        this.plugins.push(this.filterToolbar);

        if (!this.readOnly) {
            this.plugins.push({
                ptype: 'ux.browseplugin',
                multiple: true,
                scope: this,
                enableFileDialog: false,
                handler: this.onFilesSelect.createDelegate(this)
            });
        }

        if (this.hasQuickSearchFilterToolbarPlugin) {
            this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push({field: 'path'});
        }

        Tine.Filemanager.NodeGridPanel.superclass.initComponent.call(this);
        this.getStore().on('load', this.onLoad.createDelegate(this));
    },

    /**
     * after render handler
     */
    afterRender: function () {
        Tine.Filemanager.NodeGridPanel.superclass.afterRender.call(this);

        if (this.action_upload) {
            this.action_upload.setDisabled(true);
        }

        if (!this.readOnly) {
            this.initDropTarget();
        }

        var treePanel = this.treePanel || this.app.getMainScreen().getWestPanel().getContainerTreePanel();

        this.currentFolderNode = treePanel.getRootNode();
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
                id: 'tags',
                header: this.app.i18n._('Tags'),
                dataIndex: 'tags',
                width: 50,
                renderer: Tine.Tinebase.common.tagsRenderer,
                sortable: false,
                hidden: false
            }, {
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
                renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, true], 3)
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

        if (Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
            columns.push({
                id: 'revision_size',
                header: this.app.i18n._("Revision Size"),
                tooltip: this.app.i18n._("Total size of all available revisions"),
                width: 40,
                sortable: true,
                dataIndex: 'revision_size',
                hidden: true,
                renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, true], 3)
            });
        }

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
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        Tine.Filemanager.NodeGridPanel.superclass.initGrid.call(this);

        if (this.usePagingToolbar) {
           this.initPagingToolbar();
        }
    },

    /**
     * inserts a quota Message when using old Browsers with html4upload
     */
    initPagingToolbar: function() {
        if(!this.pagingToolbar || !this.pagingToolbar.rendered) {
            this.initPagingToolbar.defer(50, this);
            return;
        }
        // old browsers
        if (!((! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || (Ext.isGecko && window.FileReader))) {
            var text = new Ext.Panel({padding: 2, html: String.format(this.app.i18n._('The max. Upload Filesize is {0} MB'), Tine.Tinebase.registry.get('maxFileUploadSize') / 1048576 )});
            this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
            this.pagingToolbar.insert(12, text);
            this.pagingToolbar.doLayout();
        }
    },

    /**
     * returns filter toolbar -> supress OR filters
     * @private
     */
    getFilterToolbar: function(config) {
        config = config || {};
        var plugins = [];

        if (this.hasQuickSearchFilterToolbarPlugin) {
            this.quickSearchFilterToolbarPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin();
            plugins.push(this.quickSearchFilterToolbarPlugin);
        }

        return new Tine.widgets.grid.FilterToolbar(Ext.apply(config, {
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'query',
            filters: this.defaultFilters || [],
            plugins: plugins
        }));
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

    initLayout: function() {
        this.supr().initLayout.call(this);

        var northPanel = lodash.find(this.items, function (i) {
            return i.region == 'north'
        });

        northPanel.tbar = new Tine.Filemanager.RecursiveFilter({gridPanel: this});
    },

    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * @private
     */
    initActions: function() {

        this.action_upload = new Ext.Action(this.getAddAction());

        this.action_editFile = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: false,
            text: this.app.i18n._('Edit Properties'),
            handler: this.onEditFile,
            iconCls: 'action_edit_file',
            disabled: false,
            actionType: 'edit',
            scope: this
        });

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
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: this.app.i18n._('Save locally'),
            handler: this.onDownload,
            iconCls: 'action_filemanager_save_all',
            disabled: true,
            scope: this
        });

        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            text: this.app.i18n._('Delete'),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });

        this.action_moveRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: true,
            text: this.app.i18n._('Move'),
            disabled: true,
            actionType: 'edit',
            handler: this.onMoveRecords,
            scope: this,
            iconCls: 'action_move'
        });

        this.action_publish = new Ext.Action({
            allowMultiple: false,
            singularText: this.app.i18n._('Publish'),
            folderText: this.app.i18n._('Publish'),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : i18n,
            text: this.app.i18n._('Publish'),
            handler: this.onPublishFile,
            disabled: true,
            iconCls: 'action_publish',
            scope: this,
            actionUpdater: function(action, grants, records) {
                if (records.length != 1) {
                    action.disable();
                } else {
                    action.enable();
                }
            }
        });

        this.contextMenu = Tine.Filemanager.GridContextMenu.getMenu({
            nodeName: Tine.Filemanager.Model.Node.getRecordName(),
            actions: ['delete', 'rename', this.action_moveRecord, 'download', 'resume', 'pause', this.action_editFile, 'publish'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });

        var treePanel = this.treepPanel || this.app.getMainScreen().getWestPanel().getContainerTreePanel();

        this.folderContextMenu = Tine.Filemanager.GridContextMenu.getMenu({
            nodeName: this.app.i18n._(treePanel.containerName),
            actions: ['delete', 'rename', this.action_moveRecord, this.action_editFile, 'publish'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });

        this.actionUpdater.addActions(this.contextMenu.items);
        this.actionUpdater.addActions(this.folderContextMenu.items);

        this.actionUpdater.addActions([
            this.action_createFolder,
            this.action_goUpFolder,
            this.action_download,
            this.action_deleteRecord,
            this.action_editFile,
            this.action_publish
        ]);
    },

    onPublishFile: function() {
        var selections = this.selectionModel.getSelections();

        if (selections.length != 1) {
            return;
        }

        var date = new Date();
        date.setDate(date.getDate() + 30);

        var record = new Tine.Filemanager.Model.DownloadLink({node_id: selections[0].id, expiry_time: date});
        Tine.Filemanager.downloadLinkRecordBackend.saveRecord(record, {
            success: function (record) {

                // TODO: add mail-button
                Ext.MessageBox.show({
                    title: selections[0].data.type == 'folder' ? this.app.i18n._('Folder has been published successfully') : this.app.i18n._('File has been published successfully'),
                    msg: String.format(this.app.i18n._("Url: {0}") + '<br />' + this.app.i18n._("Valid Until: {1}"), record.get('url'), record.get('expiry_time')),
                    minWidth: 900,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO,
                    scope: this
                });
            }, failure: Tine.Tinebase.ExceptionHandler.handleRequestException, scope: this
        });
    },

    /**
     * get the right contextMenu
     */
    getContextMenu: function(grid, row, e) {
        var r = this.store.getAt(row),
            type = r ? r.get('type') : null;

        return type === 'folder' ? this.folderContextMenu : this.contextMenu;
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
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    columns: 8,
                    defaults: {minWidth: 60},
                    items: [
                        this.splitAddButton ?
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
                                }, {
                                    ptype: 'ux.itemregistry',
                                    key:   'Tinebase-MainContextMenu'
                                }]
                            })
                        }) :
                        Ext.apply(new Ext.Button(this.action_upload), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),

                        Ext.apply(new Ext.Button(this.action_editFile), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
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
                        }),
                        Ext.apply(new Ext.Button(this.action_publish), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                 ]
                }, this.getActionToolbarItems()]
            });

            this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
            this.actionToolbar.on('show', this.onActionToolbarResize, this);

            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }

        return this.actionToolbar;
    },

    /**
     * opens the edit dialog
     */
    onEditFile: function() {
        var sel = this.getGrid().getSelectionModel().getSelections();

        if(sel.length == 1) {
            var record = new Tine.Filemanager.Model.Node(sel[0].data);
            var window = Tine.Filemanager.NodeEditDialog.openWindow({record: record});
        }

        window.on('saveAndClose', function() {
            this.getGrid().store.reload();
        }, this);
    },

    /**
     * create folder in current position
     *
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
     */
    onCreateFolder: function(button, event) {
        var app = this.app,
            me = this,
            nodeName = Tine.Filemanager.Model.Node.getContainerName();

        Ext.MessageBox.prompt(this.app.i18n._('New Folder'), this.app.i18n._('Please enter the name of the new folder:'), function(_btn, _text) {
            var currentFolderNode = me.currentFolderNode;
            if(currentFolderNode && _btn == 'ok') {
                if (! _text) {
                    Ext.Msg.alert(String.format(app.i18n._('No {0} added'), nodeName), String.format(app.i18n._('You have to supply a {0} name!'), nodeName));
                    return;
                }

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
    onDeleteRecords: function(button, event) {
        var app = this.app,
            nodeName = '',
            sm = this.selectionModel,
            nodes = sm.getSelections();

        if(nodes && nodes.length) {
            for(var i=0; i<nodes.length; i++) {
                var currNodeData = nodes[i].data;

                if(typeof currNodeData.name == 'object') {
                    nodeName += currNodeData.name.name + '<br />';
                }
                else {
                    nodeName += currNodeData.name + '<br />';
                }
            }
        }

        this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
            modal: true,
            allowCancel: false,
            height: 180,
            width: 300,
            title: app.i18n._('Do you really want to delete the following files?'),
            text: nodeName,
            scope: this,
            handler: function(button){
                if (nodes && button == 'yes') {
                    this.store.remove(nodes);
                    this.pagingToolbar.refresh.disable();
                    Tine.Filemanager.fileRecordBackend.deleteItems(nodes);
                }

                for(var i=0; i<nodes.length; i++) {
                    var node = nodes[i];

                    if(node.fileRecord) {
                        var upload = Tine.Tinebase.uploadManager.getUpload(node.fileRecord.get('uploadKey'));
                        upload.setPaused(true);
                        Tine.Tinebase.uploadManager.unregisterUpload(upload.id);
                    }

                }
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
        var currentFolderNode = this.currentFolderNode;

        if(currentFolderNode && currentFolderNode.parentNode) {
            this.currentFolderNode = currentFolderNode.parentNode;
            currentFolderNode.parentNode.select();
        }
    },

    /**
     * grid row doubleclick handler
     *
     * @param {Tine.Filemanager.NodeGridPanel} grid
     * @param {} row record
     * @param {Ext.EventObjet} e
     */
    onRowDblClick: function(grid, row, e) {
        var app = this.app;
        var rowRecord = grid.getStore().getAt(row);

        if(rowRecord.data.type == 'file' && !this.readOnly) {
            Tine.Filemanager.downloadFile(rowRecord);
        }

        else if (rowRecord.data.type == 'folder'){
            var treePanel = this.treePanel || app.getMainScreen().getWestPanel().getContainerTreePanel();

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
                this.currentFolderNode = currentFolderNode;
            } else {
                // get ftb path filter
                this.filterToolbar.filterStore.each(function(filter) {
                    var field = filter.get('field');
                    if (field === 'path') {
                        filter.set('value', '');
                        filter.set('value', rowRecord.data);
                        filter.formFields.value.setValue(rowRecord.get('path'));

                        this.filterToolbar.onFiltertrigger();
                        return false;
                    }
                }, this);
            }
        }
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

            if (upload) {
                upload.setPaused(true);
            }
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
                file.set('status', 'complete');
                file.set('nodeRecord', new Tine.Filemanager.Model.Node(file.data));
                this.store.add(file);
            }
        }
    },

    /**
     * upload new file and add to store
     *
     * @param {ux.BrowsePlugin} fileSelector
     * @param {} e
     */
    onFilesSelect: function (fileSelector, event) {
        var app = Tine.Tinebase.appMgr.get('Filemanager'),
            grid = this,
            targetNode = grid.currentFolderNode,
            gridStore = grid.store,
            rowIndex = false,
            targetFolderPath = grid.currentFolderNode.attributes.path,
            addToGrid = true,
            dropAllowed = false,
            nodeRecord = null;

        if(event && event.getTarget()) {
            rowIndex = grid.getView().findRowIndex(event.getTarget());
        }


        if(targetNode.attributes) {
            nodeRecord = targetNode.attributes.nodeRecord;
        }

        if(rowIndex !== false && rowIndex > -1) {
            var newTargetNode = gridStore.getAt(rowIndex);
            if(newTargetNode && newTargetNode.data.type == 'folder') {
                targetFolderPath = newTargetNode.data.path;
                addToGrid = false;
                nodeRecord = new Tine.Filemanager.Model.Node(newTargetNode.data);
            }
        }

        if(!nodeRecord.isDropFilesAllowed()) {
            Ext.MessageBox.alert(
                    i18n._('Upload Failed'),
                    app.i18n._('Putting files in this folder is not allowed!')
            ).setIcon(Ext.MessageBox.ERROR);

            return;
        }

        var files = fileSelector.getFileList();

        if(files.length > 0) {
            grid.pagingToolbar.refresh.disable();
        }

        var filePathsArray = [], uploadKeyArray = [];

        Ext.each(files, function (file) {
            var fileRecord = Tine.Filemanager.Model.Node.createFromFile(file),
                filePath = targetFolderPath + '/' + fileRecord.get('name');

            fileRecord.set('path', filePath);
            var existingRecordIdx = gridStore.find('name', fileRecord.get('name'));
            if(existingRecordIdx < 0) {
                gridStore.add(fileRecord);
            }

            var upload = new Ext.ux.file.Upload({
                fmDirector: grid,
                file: file,
                fileSelector: fileSelector,
                id: filePath
            });

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);

            filePathsArray.push(filePath);
            uploadKeyArray.push(uploadKey);

        }, this);

        var params = {
                filenames: filePathsArray,
                type: "file",
                tempFileIds: [],
                forceOverwrite: false
        };
        Tine.Filemanager.fileRecordBackend.createNodes(params, uploadKeyArray, true);
    },

    /**
     * download file
     *
     * @param {} button
     * @param {} event
     */
    onDownload: function(button, event) {
        var grid = this,
            selectedRows = grid.selectionModel.getSelections();

        Tine.Filemanager.downloadFile(selectedRows[0]);
    },

    /**
     * grid on load handler
     *
     * @param grid
     * @param records
     * @param options
     */
    onLoad: function(store, records, options){
        var grid = this;

        for(var i=records.length-1; i>=0; i--) {
            var record = records[i];
            if(record.get('type') == 'file' && (!record.get('size') || record.get('size') == 0)) {
                var upload = Tine.Tinebase.uploadManager.getUpload(record.get('path'));

                if(upload) {
                      if(upload.fileRecord && record.get('name') == upload.fileRecord.get('name')) {
                          grid.updateNodeRecord(record, upload.fileRecord);
                          record.afterEdit();
                    }
                }
            }
        }
    },

    /**
     * update grid nodeRecord with fileRecord data
     *
     * @param nodeRecord
     * @param fileRecord
     */
    updateNodeRecord: function(nodeRecord, fileRecord) {
        for(var field in fileRecord.fields) {
            nodeRecord.set(field, fileRecord.get(field));
        }
        nodeRecord.fileRecord = fileRecord;
    },

    /**
     * init grid drop target
     *
     * @TODO DRY cleanup
     */
    initDropTarget: function(){
        var me = this;
        var ddrow = new Ext.dd.DropTarget(this.getEl(), {
            ddGroup : 'fileDDGroup',

            notifyDrop : function(dragSource, e, data){

                if(data.node && data.node.attributes && !data.node.attributes.nodeRecord.isDragable()) {
                    return false;
                }

                var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),
                    grid = dragSource.grid,
                    treePanel = me.treePanel || app.getMainScreen().getWestPanel().getContainerTreePanel(),
                    dropIndex = grid.getView().findRowIndex(e.target),
                    target = grid.getStore().getAt(dropIndex),
                    nodes = data.selections ? data.selections : [data.node];

                if((!target || target.data.type === 'file') && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }

                if(!target) {
                    return false;
                }

                for(var i=0; i<nodes.length; i++) {
                    if(nodes[i].id == target.id) {
                        return false;
                    }
                }

                var targetNode = treePanel.getNodeById(target.id);
                if(targetNode && targetNode.isAncestor(nodes[0])) {
                    return false;
                }

                Tine.Filemanager.fileRecordBackend.copyNodes(nodes, target, !e.ctrlKey);
                return true;
            },

            notifyOver : function( dragSource, e, data ) {
                if(data.node && data.node.attributes && !data.node.attributes.nodeRecord.isDragable()) {
                    return false;
                }

                var app = Tine.Tinebase.appMgr.get(Tine.Filemanager.fileRecordBackend.appName),
                    grid = dragSource.grid,
                    dropIndex = me.getView().findRowIndex(e.target),
                    treePanel = me.treePanel || app.getMainScreen().getWestPanel().getContainerTreePanel(),
                    target= grid.getStore().getAt(dropIndex),
                    nodes = data.selections ? data.selections : [data.node];

                if((!target || (target.data && target.data.type === 'file')) && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }

                if(!target) {
                    return false;
                }

                for(var i=0; i<nodes.length; i++) {
                    if(nodes[i].id == target.id) {
                        return false;
                    }
                }

                var targetNode = treePanel.getNodeById(target.id);
                if(targetNode && targetNode.isAncestor(nodes[0])) {
                    return false;
                }

                return this.dropAllowed;
            }
        });
    },

    onMoveRecords: function() {
        var sm = this.grid.getSelectionModel(),
            records = sm.getSelections();

        var filePickerDialog = new Tine.Filemanager.FilePickerDialog({
            title: this.app.i18n._('Move Items'),
            singleSelect: true,
            constraint: 'folder'
        });

        filePickerDialog.on('selected', function(nodes) {
            var node = nodes[0];
            this.pagingToolbar.refresh.disable();
            Tine.Filemanager.fileRecordBackend.copyNodes(records, node.path, true);
        }, this);

        filePickerDialog.openWindow();
    }
});
