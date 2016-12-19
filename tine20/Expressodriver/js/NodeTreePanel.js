/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

Ext.ns('Tine.Expressodriver');

/**
 * @namespace Tine.Expressodriver
 * @class Tine.Expressodriver.NodeTreePanel
 * @extends Tine.widgets.container.TreePanel
 *
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 */

Tine.Expressodriver.NodeTreePanel = function(config) {
    Ext.apply(this, config);

    this.addEvents(
        /**
         * @event containeradd
         * Fires when a folder was added
         * @param {folder} the new folder
         */
        'containeradd',
        /**
         * @event containerdelete
         * Fires when a folder got deleted
         * @param {folder} the deleted folder
         */
        'containerdelete',
        /**
         * @event containerrename
         * Fires when a folder got renamed
         * @param {folder} the renamed folder
         */
        'containerrename'
    );

    Tine.Expressodriver.NodeTreePanel.superclass.constructor.call(this);
};

/**
 * Extend node tree panel and tree panel *
 */
Ext.extend(Tine.Expressodriver.NodeTreePanel, Tine.widgets.container.TreePanel, {

    filterMode : 'filterToolbar',

    recordClass : Tine.Expressodriver.Model.Node,

    allowMultiSelection : false,

    defaultContainerPath: '/',

    ddGroup: 'fileDDGroup',

    enableDD: true,

    initComponent: function() {

        this.on('containeradd', this.onFolderAdd, this);
        this.on('containerrename', this.onFolderRename, this);
        this.on('containerdelete', this.onFolderDelete, this);
        this.on('nodedragover', this.onNodeDragOver, this);

        Tine.Tinebase.uploadManager.on('update', this.onUpdate);

        if (! this.appName && this.recordClass) {
            this.appName = this.recordClass.getMeta('appName');
        }
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }

        if (this.allowMultiSelection) {
            this.selModel = new Ext.tree.MultiSelectionModel({});
        }

        var containerName = this.recordClass ? this.recordClass.getContainerName() : 'container';
        var containersName = this.recordClass ? this.recordClass.getContainersName() : 'containers';

        this.containerName = this.containerName || this.app.i18n.n_hidden(containerName, containersName, 1);
        this.containersName = this.containersName || this.app.i18n._hidden(containersName);

        this.loader = this.loader || new Tine.widgets.tree.Loader({
            getParams: this.onBeforeLoad.createDelegate(this),
            inspectCreateNode: this.onBeforeCreateNode.createDelegate(this)
        });

        this.root = {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool',
            expanded: true,
            children: this.getExtraItems()
        };

        this.initContextMenu();

        this.getSelectionModel().on('beforeselect', this.onBeforeSelect, this);
        this.getSelectionModel().on('selectionchange', this.onSelectionChange, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforeNodeDrop, this);
        this.on('beforecontainerrename', this.onBeforeFolderRename, this);
        this.on('append', this.onAppendNode, this);

        Tine.widgets.container.TreePanel.superclass.initComponent.call(this);

        // init drop zone
        this.dropConfig = {
            ddGroup: this.ddGroup || 'fileDDGroup',
            appendOnly: this.ddAppendOnly === true,
            /**
             * @todo check acl!
             */
            onNodeOver : function(n, dd, e, data) {

                var preventDrop = false,
                    selectionContainsFiles = false;

                if(dd.dragData.selections) {
                    for(var i=0; i<dd.dragData.selections.length; i++) {
                        if(n.node.id == dd.dragData.selections[i].id) {
                            preventDrop = true;
                        } else if (this.isParentPath(dd.dragData.selections[i].data.path, n.node.attributes.path)) {
                            preventDrop = true;
                        }
                        if(dd.dragData.selections[i].data.type == 'file') {
                            selectionContainsFiles = true;
                        }
                    }
                }
                else if(dd.dragData.node && dd.dragData.node.id == n.node.id) {
                    preventDrop = true;
                }

                if(selectionContainsFiles && !n.node.attributes.account_grants) {
                    preventDrop = true;
                }

                if(n.node.isAncestor(dd.dragData.node)) {
                    preventDrop = true;
                }

                return n.node.attributes.nodeRecord.isCreateFolderAllowed()
                    && (!dd.dragData.node || dd.dragData.node.attributes.nodeRecord.isDragable())
                    && !preventDrop ? 'x-dd-drop-ok' : false;
            },

            isValidDropPoint: function(n, op, dd, e){

                var preventDrop = false,
                selectionContainsFiles = false;

                if(dd.dragData.selections) {
                    for(var i=0; i<dd.dragData.selections.length; i++) {
                        if(n.node.id == dd.dragData.selections[i].id) {
                            preventDrop = true;
                        } else if (this.isParentPath(dd.dragData.selections[i].data.path, n.node.attributes.path)) {
                            preventDrop = true;
                        }
                        if(dd.dragData.selections[i].data.type == 'file') {
                            selectionContainsFiles = true;
                        }
                    }
                }
                else if(dd.dragData.node && dd.dragData.node.id == n.node.id) {
                    preventDrop = true;
                }

                if(selectionContainsFiles && !n.node.attributes.account_grants) {
                    preventDrop = true;
                }

                if(n.node.isAncestor(dd.dragData.node)) {
                    preventDrop = true;
                }

                return n.node.attributes.nodeRecord.isCreateFolderAllowed()
                        && (!dd.dragData.node || dd.dragData.node.attributes.nodeRecord.isDragable())
                        && !preventDrop;
            },
            completeDrop: function(de) {
                var ns = de.dropNode, p = de.point, t = de.target;
                t.ui.endDrop();
                this.tree.fireEvent("nodedrop", de);
            },

            /**
             * checks if the path needle is a sub path of haystack
             */
            isSubPath: function(haystack, needle) {
                var h = haystack.split('/');
                var n = needle.split('/');
                var res = true;

                for (var index = 0; index < h.length; index++) {

                    if (n.length <= index) {
                        break;
                    }

                    if (h[index] != n[index]) {
                        res = false;
                    }
                }

                return res;
            },

            /**
             * checks if the path needle is parent path of haystack node
             */
            isParentPath: function(haystack, needle) {
                return haystack === needle + '/' + haystack.match(/[^\/]*$/);
            }
        };

        this.dragConfig = {
            ddGroup: this.ddGroup || 'fileDDGroup',
            scroll: this.ddScroll,
            /**
             * tree node dragzone modified, dragged node doesn't get selected
             *
             * @param e
             */
            onInitDrag: function(e) {
                var data = this.dragData;
                this.tree.eventModel.disable();
                this.proxy.update("");
                data.node.ui.appendDDGhost(this.proxy.ghost.dom);
                this.tree.fireEvent("startdrag", this.tree, data.node, e);
            }
        };

        this.plugins = this.plugins || [];
        this.plugins.push({
            ptype : 'ux.browseplugin',
            enableFileDialog: false,
            multiple : true,
            handler : this.dropIntoTree
        });
    },

    /**
     * Tine.widgets.tree.FilterPlugin
     * returns a filter plugin to be used in a grid
     *
     * Tine.widgets.tree.FilterPlugin
     * Tine.Expressodriver.PathFilterPlugin
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.Expressodriver.PathFilterPlugin({
                treePanel: this,
                field: 'path',
                nodeAttributeField: 'path'
            });
        }

        return this.filterPlugin;
    },

    /**
     * returns the personal root path
     * @returns {String}
     */
    getRootPath: function() {
        return '/';
    },

    /**
     * returns params for async request
     *
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    onBeforeLoad: function(node) {
        var owner = true;
        var path = node.attributes.path;
        var newPath = path;
        var params = {
            method: 'Expressodriver.searchNodes',
            application: this.app.appName,
            owner: owner,
            filter: [
                     {field: 'path', operator:'equals', value: newPath},
                     {field: 'type', operator:'equals', value: 'folder'}
                     ],
            paging: {dir: 'ASC', limit: 50, sort: 'name', start: 0}
        };

        return params;
    },

    onBeforeCreateNode: function(attr) {
        Tine.Expressodriver.NodeTreePanel.superclass.onBeforeCreateNode.apply(this, arguments);

        attr.leaf = false;

        if(attr.name && typeof attr.name == 'object') {
            Ext.apply(attr, {
                text: Ext.util.Format.htmlEncode(attr.name.name),
                qtip: Tine.Tinebase.common.doubleEncode(attr.name.name)
            });
        }

        // copy 'real' data to a node record NOTE: not a full record as we have no record reader here
        var nodeData = Ext.copyTo({}, attr, Tine.Expressodriver.Model.Node.getFieldNames());
        attr.nodeRecord = new Tine.Expressodriver.Model.Node(nodeData);
    },

    /**
     * initiates tree context menues
     *
     * @private
     */
    initContextMenu: function() {

        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add', 'reload', 'delete', 'rename'],
            scope: this,
            backend: 'Expressodriver',
            backendModel: 'Node'
        });

        this.contextMenuRootFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add', 'reload'],
            scope: this,
            backend: 'Expressodriver',
            backendModel: 'Node'
        });

        this.contextMenuOtherUserFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['reload'],
            scope: this,
            backend: 'Expressodriver',
            backendModel: 'Node'
        });

        this.contextMenuContainerFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add', 'reload', 'delete', 'rename', 'grants', 'properties'],
            scope: this,
            backend: 'Expressodriver',
            backendModel: 'Node'
        });

        this.contextMenuReloadFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['reload', 'properties'],
            scope: this,
            backend: 'Expressodriver',
            backendModel: 'Node'
        });
    },

    /**
     * @private
     * - select default path
     */
    afterRender: function() {
        Tine.Expressodriver.NodeTreePanel.superclass.afterRender.call(this);
    },

    /**
     * show context menu
     *
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} event
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        var container = node.attributes.nodeRecord.data,
            path = container.path;

        if (! Ext.isString(path) || node.isRoot) {
            return;
        }

        Tine.log.debug('Tine.Expressodriver.NodeTreePanel::onContextMenu - context node:');
        Tine.log.debug(node);

        if (node.parentNode && node.parentNode.isRoot) {
            this.contextMenuRootFolder.showAt(event.getXY());
        } else {
            this.contextMenuUserFolder.showAt(event.getXY());
        }
    },

    /**
     * updates grid actions
     * @todo move to grid / actionUpdater
     *
     * @param {} sm     SelectionModel
     * @param {Ext.tree.TreeNode} node
     */
    updateActions: function(sm, node) {
        var grid = this.app.getMainScreen().getCenterPanel();

        grid.action_deleteRecord.disable();
        grid.action_upload.disable();

        if(!!node && !!node.isRoot) {
            grid.action_goUpFolder.disable();
        }
        else {
            grid.action_goUpFolder.enable();
        }

        if(node && node.attributes && node.attributes.nodeRecord.isCreateFolderAllowed()) {
            grid.action_createFolder.enable();
        }
        else {
            grid.action_createFolder.disable();
        }

        if(node && node.attributes && node.attributes.nodeRecord.isDropFilesAllowed()) {
            grid.action_upload.enable();
        }
        else {
            grid.action_upload.disable();
        }
    },

        /**
     * called when tree selection changes
     *
     * @param {} sm     SelectionModel
     * @param {Ext.tree.TreeNode} node
     */
    onSelectionChange: function(sm, node) {
        this.updateActions(sm, node);
        var grid = this.app.getMainScreen().getCenterPanel();

        grid.currentFolderNode = node;
        Tine.Expressodriver.NodeTreePanel.superclass.onSelectionChange.call(this, sm, node);

    },

    getExtraItems: function(){
    //    return [];
    },

    /**
     * convert filesystem path to treePath
     *
     * NOTE: only the first depth gets converted!
     *       fs pathes of not yet loaded tree nodes can't be converted!
     *
     * @param {String} containerPath
     * @return {String} tree path
     */
    getTreePath: function(path) {
        var treePath = '/' + this.getRootNode().id;

        if (path && path != '/') {
           treePath += String(path);
        }

        return treePath;
    },

    /**
     * Expands a specified path in this TreePanel. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     *
     * NOTE: path does not consist of id's starting from the second depth
     *
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the expand is complete. The callback will be called with
     * (bSuccess, oLastNode) where bSuccess is if the expand was successful and oLastNode is the last node that was expanded.
     */
    expandPath : function(path, attr, callback){
        var keys = path.split(this.pathSeparator);
        var curNode = this.root;
        var curPath = curNode.attributes.path;
        var index = 1;
        var f = function(){
            if(++index == keys.length){
                if(callback){
                    callback(true, curNode);
                }
                return;
            }

            if (index > 2) {
                var c = curNode.findChild('path', curPath + '/' + keys[index]);
            } else {
                var c = curNode.findChild('name', keys[index]);
            }
            if(!c){
                if(callback){
                    callback(false, curNode);
                }
                return;
            }
            curNode = c;
            curPath = c.attributes.path;
            c.expand(false, false, f);
        };
        curNode.expand(false, false, f);
    },

    /**
     * files/folder got dropped on node
     *
     * @param {Object} dropEvent
     * @private
     */
    onBeforeNodeDrop: function(dropEvent) {
        var nodes, target = dropEvent.target;

        if(dropEvent.data.selections) {
            nodes = dropEvent.data.grid.selModel.selections.items;
        }

        if(!nodes && dropEvent.data.node) {
            nodes = [dropEvent.data.node];
        }

        Tine.Expressodriver.fileRecordBackend.copyNodes(nodes, target, !dropEvent.rawEvent.ctrlKey);

        dropEvent.dropStatus = true;
        return true;
    },

    /**
     * folder delete handler
     */
    onFolderDelete: function(node) {
        var grid = this.app.getMainScreen().getCenterPanel();
        if(grid.currentFolderNode.isAncestor && typeof grid.currentFolderNode.isAncestor == 'function'
            && grid.currentFolderNode.isAncestor(node)) {
            node.parentNode.select();
        }
        grid.getStore().reload();
    },

    /**
     * clone a tree node / create a node from grid node
     *
     * @param node
     * @returns {Ext.tree.AsyncTreeNode}
     */
    cloneTreeNode: function(node, target) {
        var targetPath = target.attributes.path,
            newPath = '',
            copy;

        if(node.attributes) {
            var nodeName = node.attributes.name;
            if(typeof nodeName == 'object') {
                nodeName = nodeName.name;
            }
            newPath = targetPath + '/' + nodeName;

            copy = new Ext.tree.AsyncTreeNode({text: node.text, path: newPath, name: node.attributes.name
                , nodeRecord: node.attributes.nodeRecord, account_grants: node.attributes.account_grants});
        }
        else {
            var nodeName = node.data.name;
            if(typeof nodeName == 'object') {
                nodeName = nodeName.name;
            }

            var nodeData = Ext.copyTo({}, node.data, Tine.Expressodriver.Model.Node.getFieldNames());
            var newNodeRecord = new Tine.Expressodriver.Model.Node(nodeData);

            newPath = targetPath + '/' + nodeName;
            copy = new Ext.tree.AsyncTreeNode({text: nodeName, path: newPath, name: node.data.name
                , nodeRecord: newNodeRecord, account_grants: node.data.account_grants});
        }

        copy.attributes.nodeRecord.beginEdit();
        copy.attributes.nodeRecord.set('path', newPath);
        copy.attributes.nodeRecord.endEdit();

        copy.parentNode = target;
        return copy;
    },

    /**
     * create Tree node by given node data
     *
     * @param nodeData
     * @param target
     * @returns {Ext.tree.AsyncTreeNode}
     */
    createTreeNode: function(nodeData, target) {
        var nodeName = nodeData.name;
        if(typeof nodeName == 'object') {
            nodeName = nodeName.name;
        }

        var newNodeRecord = new Tine.Expressodriver.Model.Node(nodeData);

        var newNode = new Ext.tree.AsyncTreeNode({
            text: nodeName,
            path: nodeData.path,
            name: nodeData.name,
            nodeRecord: newNodeRecord,
            account_grants: nodeData.account_grants,
            id: nodeData.id
        })

        newNode.attributes.nodeRecord.beginEdit();
        newNode.attributes.nodeRecord.set('path', nodeData.path);
        newNode.attributes.nodeRecord.endEdit();

        newNode.parentNode = target;
        return newNode;

    },

    /**
     * TODO: move to Upload class or elsewhere??
     * updating fileRecord after creating node
     *
     * @param response
     * @param request
     * @param upload
     */
    onNodeCreated: function(response, request, upload) {

        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
        grid = app.getMainScreen().getCenterPanel();

        var record = Ext.util.JSON.decode(response.responseText);

        var fileRecord = upload.fileRecord;
        fileRecord.beginEdit();
        fileRecord.set('contenttype', record.contenttype);
        fileRecord.set('created_by', Tine.Tinebase.registry.get('currentAccount'));
        fileRecord.set('creation_time', record.creation_time);
        fileRecord.set('revision', record.revision);
        fileRecord.set('last_modified_by', record.last_modified_by);
        fileRecord.set('last_modified_time', record.last_modified_time);
        fileRecord.set('status', 'complete');
        fileRecord.set('progress', 100);
        fileRecord.set('name', record.name);
        fileRecord.set('path', record.path);
        fileRecord.commit(false);

        upload.fireEvent('update', 'uploadfinished', upload, fileRecord);

        grid.pagingToolbar.refresh.enable();

    },

    /**
     * copies uploaded temporary file to target location
     *
     * @param upload    {Ext.ux.file.Upload}
     * @param file  {Ext.ux.file.Upload.file}
     */
    onUploadComplete: function(upload, file) {
        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel();

     // check if we are responsible for the upload
        if (upload.fmDirector != treePanel) return;

        // $filename, $type, $tempFileId, $forceOverwrite
        Ext.Ajax.request({
            timeout: 10*60*1000, // Overriding Ajax timeout - important!
            params: {
                method: 'Expressodriver.createNode',
                filename: upload.id,
                type: 'file',
                tempFileId: file.get('id'),
                forceOverwrite: true
            },
            success: treePanel.onNodeCreated.createDelegate(this, [upload], true),
            failure: treePanel.onNodeCreated.createDelegate(this, [upload], true)
        });

    },

    /**
     * on upload failure
     *
     * @private
     */
    onUploadFail: function () {
        Ext.MessageBox.alert(
            i18n._('Upload Failed'),
            i18n._('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size:') + ' ' + Tine.Tinebase.registry.get('maxFileUploadSize')
        ).setIcon(Ext.MessageBox.ERROR);
    },

    /**
     * add folder handler
     */
    onFolderAdd: function(nodeData) {

        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            grid = app.getMainScreen().getCenterPanel();

        grid.getStore().reload();
        if(nodeData.error) {
            Tine.log.debug(nodeData);
        }
    },

    /**
     * handles before tree node / aka folder renaming
     */
    onBeforeFolderRename: function(node) {
        var app = Tine.Tinebase.appMgr.get('Expressodriver');
        Ext.MessageBox.wait(i18n._('Please wait'), app.i18n._('Renaming nodes...' ));
    },

    /**
     * handles renaming of a tree node / aka folder
     */
    onFolderRename: function(nodeData, node, newName) {
        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            grid = app.getMainScreen().getCenterPanel();

        if(nodeData[0]) {
            nodeData = nodeData[0];
        };

        node.attributes.nodeRecord.beginEdit();
        node.attributes.nodeRecord.set('name', newName);
        node.attributes.nodeRecord.set('path', nodeData.path);
        node.attributes.path = nodeData.path;
        node.attributes.nodeRecord.commit(false);

        if(typeof node.attributes.name == 'object') {
            node.attributes.name.name = newName;
        }
        else {
            node.attributes.name = newName;
        }

        grid.currenFolderNode = node;

        Ext.MessageBox.hide();
        Tine.Expressodriver.NodeTreePanel.superclass.onSelectionChange.call(this, this.getSelectionModel(), node);

    },

    /**
     * upload update handler
     *
     * @param change {String} kind of change
     * @param upload {Ext.ux.file.Upload} upload
     * @param fileRecord {file} fileRecord
     *
     */
    onUpdate: function(change, upload, fileRecord) {

        var app = Tine.Tinebase.appMgr.get('Expressodriver'),
            grid = app.getMainScreen().getCenterPanel(),
            treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
            rowsToUpdate = grid.getStore().query('name', fileRecord.get('name'));

        if(change == 'uploadstart') {
            Tine.Tinebase.uploadManager.onUploadStart();
        }
        else if(change == 'uploadfailure') {
            treePanel.onUploadFail();
        }

        if(rowsToUpdate.get(0)) {
            if(change == 'uploadcomplete') {
                treePanel.onUploadComplete(upload, fileRecord);
            }
            else if(change == 'uploadfinished') {
                rowsToUpdate.get(0).set('size', upload.fileSize);
                rowsToUpdate.get(0).set('contenttype', fileRecord.get('contenttype'));
            }
            rowsToUpdate.get(0).afterEdit();
            rowsToUpdate.get(0).commit(false);
        }
    },

    /**
     * handels tree drop of object from outside the browser
     *
     * @param fileSelector
     * @param targetNodeId
     */
    dropIntoTree: function(fileSelector, event) {

        var treePanel = fileSelector.component,
            app = treePanel.app,
            grid = app.getMainScreen().getCenterPanel(),
            targetNode,
            targetNodePath;


        var targetNodeId;
        var treeNodeAttribute = event.getTarget('div').attributes['ext:tree-node-id'];
        if(treeNodeAttribute) {
            targetNodeId = treeNodeAttribute.nodeValue;
            targetNode = treePanel.getNodeById(targetNodeId);
            targetNodePath = targetNode.attributes.path;

        };

        if(!targetNode.attributes.nodeRecord.isDropFilesAllowed()) {
            Ext.MessageBox.alert(
                    i18n._('Upload Failed'),
                    app.i18n._('Putting files in this folder is not allowed!')
                ).setIcon(Ext.MessageBox.ERROR);

            return;
        };

        var files = fileSelector.getFileList(),
            filePathsArray = [],
            uploadKeyArray = [],
            addToGridStore = false;

        Ext.each(files, function (file) {

            var fileName = file.name || file.fileName,
                filePath = targetNodePath + '/' + fileName;

            var upload = new Ext.ux.file.Upload({
                fmDirector: treePanel,
                file: file,
                fileSelector: fileSelector,
                id: filePath
            });

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);

            filePathsArray.push(filePath);
            uploadKeyArray.push(uploadKey);

            addToGridStore = grid.currentFolderNode.id === targetNodeId;

        }, this);

        var params = {
                filenames: filePathsArray,
                type: "file",
                tempFileIds: [],
                forceOverwrite: false
        };
        Tine.Expressodriver.fileRecordBackend.createNodes(params, uploadKeyArray, addToGridStore);
    }
});
