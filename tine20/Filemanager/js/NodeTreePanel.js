/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

require('./nodeContextMenu');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.NodeTreePanel
 * @extends Tine.widgets.container.TreePanel
 *
 * @author Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.NodeTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {

    filterMode : 'filterToolbar',

    recordClass : Tine.Filemanager.Model.Node,

    allowMultiSelection : false,

    ddGroup: 'fileDDGroup',
    enableDD: true,

    dataSafeAreaName: 'Tinebase.datasafe',
    dataSafeEnabled: false,

    hasGrid: true,

    initComponent: function() {
        this.on('nodedragover', this.onNodeDragOver, this);

        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }

        if (this.readOnly) {
            this.enableDD = false;
        }

        // NOTE: fm tree is initially loaded from grid!
        // this.defaultContainerPath = Tine.Tinebase.container.getMyFileNodePath();

        this.dragConfig = {
            ddGroup: this.ddGroup,
            scroll: this.ddScroll,
            onBeforeDrag: this.onBeforeDrag.createDelegate(this)
        };

        this.dropConfig = {
            ddGroup: this.ddGroup,
            appendOnly: this.ddAppendOnly === true,
            onNodeOver: this.onNodeOver.createDelegate(this)
        };

        Tine.Filemanager.NodeTreePanel.superclass.initComponent.call(this);

        this.plugins = this.plugins || [];

        if (!this.readOnly && this.enableDD) {
            this.plugins.push({
                ptype : 'ux.browseplugin',
                enableFileDialog: false,
                multiple : true,
                handler : this.dropIntoTree
            });
        }

        postal.subscribe({
            channel: "recordchange",
            topic: [this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), '*'].join('.'),
            callback: this.onRecordChanges.createDelegate(this)
        });

        this.dataSafeEnabled = Tine.Tinebase.areaLocks.hasLock(this.dataSafeAreaName);
        if (this.dataSafeEnabled) {
            postal.subscribe({
                channel: 'areaLocks',
                topic: this.dataSafeAreaName +'.*',
                callback: this.applyDataSafeState.createDelegate(this)
            });

            this.dataSafeIsLocked = Tine.Tinebase.areaLocks.isLocked(this.dataSafeAreaName)
        }
    },

    onRecordChanges: function(data, e) {
        if (data.type == 'folder') {
            var _ = window.lodash,
                me = this,
                path = data.path,
                parentPath = path.replace(new RegExp(_.escapeRegExp(data.name) + '$'), ''),
                node = this.getNodeById(data.id),
                pathChange = node && node.attributes && node.attributes.nodeRecord.get('path') != path;

            if (node && e.topic.match(/\.delete/)) {
                try {
                    node.cancelExpand();
                    node.remove(true);
                } catch (e) {}
                return;
            }

            if (node) {
                node.setText(Ext.util.Format.htmlEncode(data.name));
                // NOTE: qtip dosn't work, but implementing is not worth the effort...
                node.qtip = Tine.Tinebase.common.doubleEncode(data.name);
                Ext.apply(node.attributes, data);
                node.attributes.nodeRecord = new this.recordClass(data);

                // in case of path change we need to reload the node (children) as well
                // as the path of all children changed as well
                if (node.hasChildNodes() && pathChange && ! node.loading) {
                    if (! node.bufferedReload) {
                        node.bufferedReload = Function.createBuffered(node.reload, 100, node);
                    }
                    node.bufferedReload();
                }
            }

            // add / remount node
            try {
                me.expandPath(parentPath, '', function (sucess, parentNode) {
                    var childNode = parentNode.findChild('name', data.name);
                    if (!childNode) {
                        parentNode.appendChild(node || me.loader.createNode(data));
                    } else if (childNode != node) {
                        // node got duplicated by expand load
                        try {
                            node.cancelExpand();
                            node.remove(true);
                        } catch (e) {
                        }
                    }
                });
            } catch (e) {}
        }
    },

    applyDataSafeState: function() {
        var me = this;

        Tine.Tinebase.areaLocks.isLocked(me.dataSafeAreaName).then(function(isLocked) {
            me.dataSafeIsLocked.then(function(wasLocked) {
                me.dataSafeIsLocked = Tine.Tinebase.areaLocks.isLocked(me.dataSafeAreaName);
                if (isLocked != wasLocked) {
                    var rootNode = me.getRootNode(),
                        selectedNode = me.getSelectionModel().getSelectedNode();

                    me.getSelectionModel().suspendEvents();
                    rootNode.collapse(true);
                    // NOTE: the grid reload expands the tree as well!
                    // not clear yet how to detect if a grid is on board as well
                    // if (selectedNode) {
                    //     me.selectPath(selectedNode.attributes.path, {}, me.getSelectionModel().resumeEvents.bind(me));
                    // } else {
                    //     rootNode.expand(false, true,  me.getSelectionModel().resumeEvents.bind(me));
                    // }

                }
            })

        });
    },

    /**
     * autosort new nodes
     *
     * @param tree
     * @param parent
     * @param appendedNode
     * @param idx
     */
    onAppendNode: function(tree, parent, appendedNode, idx) {
        if (parent.getDepth() > 0) {
            parent.sort(function (n1, n2) {
                return n1.text.localeCompare(n2.text);
            });
        }
    },

    /**
     * An empty function by default, but provided so that you can perform a custom action before the initial
     * drag event begins and optionally cancel it.
     * @param {Object} data An object containing arbitrary data to be shared with drop targets
     * @param {Event} e The event object
     * @return {Boolean} isValid True if the drag event is valid, else false to cancel
     */
    onBeforeDrag : function(data, e) {
        var _ = window.lodash,
            requiredGrant = e.ctrlKey || e.altKey ? 'readGrant' : 'editGrant';

        // @TODO: rethink: do I need delte on the record or parent?
        return !! _.get(data, 'node.attributes.nodeRecord.data.account_grants.' + requiredGrant);
    },

    onNodeOver : function(n, dd, e, data) {
        var action = e.ctrlKey || e.altKey ? 'copy' : 'move',
            cls = Ext.tree.TreeDropZone.prototype.onNodeOver.apply(this.dropZone, arguments);

        return cls != this.dropZone.dropNotAllowed ?
            'tinebase-dd-drop-ok-' + action :
            this.dropZone.dropNotAllowed;
    },

    /**
     * @param {Object} dragOverEvent
     *
     * tree - The TreePanel
     * target - The node being targeted for the drop
     * data - The drag data from the drag source
     * point - The point of the drop - append, above or below
     * source - The drag source
     * rawEvent - Raw mouse event
     * dropNode - Drop node(s) provided by the source.
     * cancel - Set this to true to signal drop not allowed.
     */
    onNodeDragOver: function(dragOverEvent) {
        var _ = window.lodash,
            cancel = this.readOnly
                || ! dragOverEvent.target.expanded
                || dragOverEvent.target == dragOverEvent.source.dragData.node
                || ! _.get(dragOverEvent, 'target.attributes.nodeRecord.data.account_grants.addGrant');

        dragOverEvent.cancel = cancel;
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

        Tine[this.appName].fileRecordBackend.copyNodes(nodes, target, !(dropEvent.rawEvent.ctrlKey  || dropEvent.rawEvent.altKey));

        dropEvent.dropStatus = true;
        return true;
    },

    /**
     * load everything from server
     * @returns {Object} root node definition
     */
    getRoot: function() {
        return {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool'
        };
    },

    /**
     * Tine.widgets.tree.FilterPlugin
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.Filemanager.PathFilterPlugin({
                treePanel: this,
                field: 'path',
                nodeAttributeField: 'path'
            });
        }

        return this.filterPlugin;
    },

    /**
     * returns params for async request
     *
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    onBeforeLoad: function(node) {
        var path = node.attributes.path;
        var type = Tine.Tinebase.container.path2type(path);
        var owner = Tine.Tinebase.container.pathIsPersonalNode(path);
        var loginName = Tine.Tinebase.registry.get('currentAccount').accountLoginName;

        if (type === 'personal' && owner != loginName) {
            type = 'otherUsers';
        }

        var newPath = path;

        if (type === 'personal' && owner) {
            var pathParts = path.toString().split('/');
            newPath = '/' + pathParts[1] + '/' + loginName;
            if(pathParts[3]) {
                newPath += '/' + pathParts[3];
            }
        }

        var params = {
            method: this.recordClass.getMeta('appName') + '.searchNodes',
            application: this.app.appName,
            owner: owner,
            filter: [
                     {field: 'path', operator:'equals', value: newPath},
                     {field: 'type', operator:'equals', value: 'folder'}
                     ],
            paging: {dir: 'ASC', sort: 'name'}
        };

        return params;
    },

    onBeforeCreateNode: function(attr) {
        Tine.Filemanager.NodeTreePanel.superclass.onBeforeCreateNode.apply(this, arguments);

        attr.leaf = false;

        if(attr.name && typeof attr.name == 'object') {
            Ext.apply(attr, {
                text: Ext.util.Format.htmlEncode(attr.name.name),
                qtip: Tine.Tinebase.common.doubleEncode(attr.name.name)
            });
        }

        // copy 'real' data to a node record NOTE: not a full record as we have no record reader here
        var nodeData = Ext.copyTo({}, attr, this.recordClass.getFieldNames());
        attr.nodeRecord = new this.recordClass(nodeData);

        if(this.dataSafeEnabled && !!attr.nodeRecord.get('pin_protected_node')) {
            attr.cls = 'x-type-data-safe';
        }
    },

    /**
     * initiates tree context menus
     *
     * @private
     */
    initContextMenu: function() {
        this.ctxMenu = Tine.Filemanager.nodeContextMenu.getMenu({
            actionMgr: Tine.Filemanager.nodeActionsMgr,
            nodeName: this.recordClass.getContainerName(),
            actions: ['reload', 'createFolder', 'delete', 'rename', 'move', 'edit', 'publish', 'systemLink'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            actions: this.ctxMenu.items
        });
    },

    /**
     * show context menu
     *
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} event
     */
    onContextMenu: function(node, event) {
        event.stopEvent();
        Tine.log.debug(node);

        // legacy for reload action
        this.ctxNode = node;

        //@TODO implement selection vs ctxNode if multiselect is allowed
        var record = new this.recordClass(node.attributes.nodeRecord.data);
        this.actionUpdater.updateActions([record]);

        this.ctxMenu.showAt(event.getXY());
    },

    /**
     * called when tree selection changes
     *
     * @param {} sm     SelectionModel
     * @param {Ext.tree.TreeNode} node
     */
    onSelectionChange: function(sm, node) {
        // this.updateActions(sm, node);
        var grid = this.app.getMainScreen().getCenterPanel(),
            gridSelectionModel = grid.selectionModel,
            actionUpdater = grid.actionUpdater,
            record = node ? new this.recordClass(window.lodash.get(node, 'attributes.nodeRecord.data')) : null,
            selection = record ? [record] : [];

        grid.currentFolderNode = node;

        if (this.hasGrid && gridSelectionModel) {
            gridSelectionModel.clearSelections();
        }

        if (actionUpdater) {
            actionUpdater.updateActions(selection);
        }

        Tine.Filemanager.NodeTreePanel.superclass.onSelectionChange.call(this, sm, node);
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
    getTreePath: function(containerPath) {
        var _ = window.lodash,
            treePath = '/' + this.getRootNode().id + containerPath
            .replace(new RegExp('^' + _.escapeRegExp(Tine.Tinebase.container.getMyFileNodePath())), '/myUser')
            .replace(/^\/personal/, '/otherUsers')
            .replace(/\/$/, '');

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
        if (! path.match(/^\/xnode-/)) {
            path = this.getTreePath(path);
        }

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
                var c = curNode.findChild('id', keys[index]);
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
     * Selects the node in this tree at the specified path. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the selection is complete. The callback will be called with
     * (bSuccess, oSelNode) where bSuccess is if the selection was successful and oSelNode is the selected node.
     */
    selectPath : function(path, attr, callback) {
        var node = this.expandPath(path, attr, function(bSuccess, oLastNode){
            if (oLastNode) {
                oLastNode.select();
                if (Ext.isFunction(callback)) {
                    callback.call(true, oLastNode);
                }
            }
        }.createDelegate(this));
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

            var nodeData = Ext.copyTo({}, node.data, this.recordClass.getFieldNames());
            var newNodeRecord = new this.recordClass(nodeData);

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

        }

        if(!targetNode.attributes.nodeRecord.isDropFilesAllowed()) {
            Ext.MessageBox.alert(
                    i18n._('Upload Failed'),
                    app.i18n._('It is not permitted to store files in this folder!')
                ).setIcon(Ext.MessageBox.ERROR);

            return;
        }

        var files = fileSelector.getFileList(),
            filePathsArray = [],
            fileTypesArray = [],
            uploadKeyArray = [],
            addToGridStore = false;

        Ext.each(files, function (file) {
            if ("" === file.type) {
                return true;
            }

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
            fileTypesArray.push('vnd.adobe.partial-upload; final_type=' + file.type);
            uploadKeyArray.push(uploadKey);

            addToGridStore = grid.currentFolderNode.id === targetNodeId;

        }, this);

        if (0 === uploadKeyArray.length) {
            return;
        }

        var params = {
                filenames: filePathsArray,
                type: "file",
                tempFileIds: [],
                forceOverwrite: false
        };
        Tine.Filemanager.fileRecordBackend.createNodes(params, uploadKeyArray, addToGridStore);
    },


    /**
     * returns true if node can accept contents
     *
     * @param nodeAttributes
     * @returns boolean
     */
    nodeAcceptsContents: function(nodeAttributes) {
        return !! nodeAttributes;
    }
});
