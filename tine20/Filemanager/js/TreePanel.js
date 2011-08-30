/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.TreePanel
 * @extends Tine.widgets.container.TreePanel
 * 
 * @author Martin Jatho <m.jatho@metaways.de>
 */


Tine.Filemanager.TreePanel = function(config) {
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
        
    Tine.Filemanager.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Filemanager.TreePanel, Tine.widgets.container.TreePanel, {
    
    filterMode : 'filterToolbar',
    
    recordClass : Tine.Filemanager.Model.Node,
    
    allowMultiSelection : false, 
    
    ddGroup: 'fileDDGroup',
    
    enableDD: true,
       
    initComponent: function() {
        
//        this.on('containeradd', this.onFolderAdd, this);
//        this.on('containerrename', this.onFolderRename, this);
        this.on('containerdelete', this.onFolderDelete, this);
        this.on('nodedragover', this.onNodeDragOver, this);

        this.selModel = new Ext.ux.tree.FileTreeSelectionModel({});
                
        this.getSelectionModel().on('initDrag', this.onInitDrag, this);
//        this.getSelectionModel().on('dragEnter', this.onDragEnter, this);
//        this.getSelectionModel().on('dragDrop', this.onDragDrop, this);

        Tine.Filemanager.TreePanel.superclass.initComponent.call(this);

        // init drop zone
        this.dropConfig = {
            ddGroup: this.ddGroup || 'fileDDGroup',
            appendOnly: this.ddAppendOnly === true,
            /**
             * @todo check acl!
             */
            onNodeOver : function(n, dd, e, data) {
                
                var node = n.node;
                return node.attributes.nodeRecord.isWriteable() 
                    && (!dd.dragData.node || dd.dragData.node.attributes.nodeRecord.isDragable())
                    && node.id !== dd.dragData.node.id ? 'x-dd-drop-ok' : false;
            },
            isValidDropPoint: function(n, op, dd, e){
                return n.node.attributes.nodeRecord.isWriteable()
                            && (!dd.dragData.node || dd.dragData.node.attributes.nodeRecord.isDragable()
                            && n.node.id !== dd.dragData.node.id);
            },
            completeDrop: function(de) {
                var ns = de.dropNode, p = de.point, t = de.target;
                t.ui.endDrop();
                this.tree.fireEvent("nodedrop", de);
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
     */
    // Tine.widgets.tree.FilterPlugin
    // Tine.Filemanager.PathFilterPlugin
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
     * returns the personal root path
     * @returns {String}
     */
    getRootPath: function() {
        return Tine.Tinebase.container.getMyFileNodePath();
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
            method: 'Filemanager.searchNodes',
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
    
    /**
     * adopt attr
     * 
     * @param {Object} attr
     */
    onBeforeCreateNode: function(attr) {

        if (attr.accountDisplayName) {
            attr.name = attr.accountDisplayName;
            attr.path = '/personal/' + attr.accountId;
            attr.id = attr.accountId;
        }
        
        if (!attr.name && attr.path) {
            attr.name = Tine.Tinebase.container.path2name(attr.path, this.containerName, this.containersName);
        }
        
        if(attr.path && !attr.created_by) {
            var matches = attr.path.match(/^\/personal\/{0,1}([0-9a-z_\-]*)\/{0,1}/i);
            if (matches) {
                if (matches[1] != Tine.Tinebase.registry.get('currentAccount').accountLoginName && matches[1].length > 0) {
                    attr.id = matches[1];
                } 
                else if (matches[1] != Tine.Tinebase.registry.get('currentAccount').accountLoginName) {
                    attr.id = 'otherUsers';
                }
            }
        }
        
        if(attr.name && typeof attr.name == 'object') {
            Ext.applyIf(attr, {
                text: Ext.util.Format.htmlEncode(attr.name.name),
                qtip: Ext.util.Format.htmlEncode(attr.name.name),
                leaf: !(attr.type == 'folder')
                //allowDrop: (attr.type == 'folder')
            });
        }
        else {
            Ext.applyIf(attr, {
                text: Ext.util.Format.htmlEncode(attr.name),
                qtip: Ext.util.Format.htmlEncode(attr.name),
                leaf: !!attr.account_grants && !(attr.type == 'folder')
                //allowDrop: !!attr.account_grants && attr.account_grants.addGrant
            });
        }
        
        
        // copy 'real' data to a node record NOTE: not a full record as we have no record reader here
        var nodeData = Ext.copyTo({}, attr, Tine.Filemanager.Model.Node.getFieldNames());
        attr.nodeRecord = new Tine.Filemanager.Model.Node(nodeData);
        
    },
    
    /**
     * treePanel on click handler
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} e
     */
    onClick: function(node, e) {
             
        if(node && node.reload) {
            node.reload();
        }
                
        Tine.Filemanager.TreePanel.superclass.onClick.call(this, node, e);

    },
    
    /**
     * initiates tree context menues
     * 
     * @private
     */
    initContextMenu: function() {
        
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add', 'delete', 'rename'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });
            
        this.contextMenuRootFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });
        
        this.contextMenuContainerFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._(this.containerName),
            actions: ['add', 'delete', 'rename', 'grants'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });
    },
    
    /**
     * @private
     * - select default path
     */
    afterRender: function() {
                
        Tine.Filemanager.TreePanel.superclass.afterRender.call(this);

    },
    
    /**
     * show context menu
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} event
     */
    onContextMenu: function(node, event) {
        
        var currentAccount = Tine.Tinebase.registry.get('currentAccount');
        
        this.ctxNode = node;
        var container = node.attributes.nodeRecord.data,
            path = container.path,
            owner;
        
        if (! Ext.isString(path) || node.isRoot) {
            return;
        }
        
//        if (Tine.Tinebase.container.pathIsContainer(path)) {
//            if (container.account_grants && container.account_grants.adminGrant) {
//                this.contextMenuSingleContainer.showAt(event.getXY());
//            }
//        } else 
//            
        
        if (!Tine.Tinebase.container.pathIsContainer(path)) {
            this.contextMenuRootFolder.showAt(event.getXY());
        }
        else if (path.match(/^\/shared$/) && (Tine.Tinebase.common.hasRight('admin', this.app.appName) 
                || Tine.Tinebase.common.hasRight('manage_shared_folders', this.app.appName))){
            this.contextMenuUserFolder.showAt(event.getXY());
        } 
        // TODO: check auf richtigen user !!!
        else if (path.match(/^\/personal/) && path.match('/personal/' + currentAccount.accountLoginName)) {
            if(typeof container.name == 'object') {
                this.contextMenuContainerFolder.showAt(event.getXY());
            }
            else {
                this.contextMenuUserFolder.showAt(event.getXY());
            }
        }
    },
    
    
    /**
     * called when tree selection changes
     * 
     * @param {} sm     SelectionModel
     * @param {Ext.tree.TreeNode} node
     */
    onSelectionChange: function(sm, node) {
        Tine.log.debug('onSelectionChange');
        var grid = this.app.getMainScreen().getCenterPanel();
        
        grid.action_deleteRecord.disable();
        grid.action_upload.disable();
        
        if(!!node && !!node.isRoot) {
            grid.action_goUpFolder.disable();
        }
        else {
            grid.action_goUpFolder.enable();
        }
                
        if(!!node && !!node.attributes && !!node.attributes.account_grants && node.attributes.account_grants.addGrant) {
            grid.action_upload.enable();
            grid.action_createFolder.enable();
        }
        else {
            grid.action_upload.disable();
            grid.action_createFolder.disable();
        }
        
        grid.currentFolderNode = node; 
        Tine.Filemanager.TreePanel.superclass.onSelectionChange.call(this, sm, node);
    
    },
    
    /**
     * convert containerPath to treePath
     * 
     * @param {String} containerPath
     * @return {String} tree path
     */
    getTreePath: function(valueItem) {
        
        var containerPath = '';
        if(valueItem && !valueItem.id) return valueItem.path;


        if(valueItem) {
            var node = this.getNodeById(valueItem.id);
            if(node) {
                var returnPath = node.getPath().replace('personal/'  + Tine.Tinebase.registry.get('currentAccount').accountLoginName, 'personal');
                return returnPath;
            }

            containerPath = valueItem.path;
        }
        var treePath = '/' + this.getRootNode().id + (containerPath !== '/' ? containerPath : '');

        if(containerPath === '/shared') {
            Tine.log.debug(valueItem.path + ' | ' + treePath);
        }
            
        // replace personal with otherUsers if personal && ! personal/myaccountid
        var matches = containerPath.match(/^\/personal\/{0,1}([0-9a-z_\-]*)\/{0,1}/i);
        if (matches) {
            if (matches[1] != Tine.Tinebase.registry.get('currentAccount').accountLoginName) {
                treePath = treePath.replace('personal', 'otherUsers');
            } 
            else {
                treePath = treePath.replace('personal/'  + Tine.Tinebase.registry.get('currentAccount').accountLoginName, 'personal');
                
            }
        }
        
        return treePath;
    },
    
   
    /**
     * files/folder got dropped on node
     * 
     * @param {Object} dropEvent
     * @private
     */
    onBeforeNodeDrop: function(dropEvent) {

        Tine.log.debug("onBeforeNodeDrop");
        var nodes = dropEvent.data.selections,
            target = dropEvent.target;
            
        if(!nodes && dropEvent.data.node) {
            nodes = [dropEvent.data.node];
        }
        
        Tine.Filemanager.fileRecordBackend.copyNodes(nodes, target, !dropEvent.rawEvent.ctrlKey);
        
        dropEvent.dropStatus = true;
        return true;
    },

    /**
     * folder delete handler
     */
    onFolderDelete: function() {
        Tine.log.debug("onFolderDelete");
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
            
            var nodeData = Ext.copyTo({}, node.data, Tine.Filemanager.Model.Node.getFieldNames());
            var newNodeRecord = new Tine.Filemanager.Model.Node(nodeData);
             
            newPath = targetPath + '/' + nodeName;
            copy = new Ext.tree.AsyncTreeNode({text: node.data.name, path: newPath, name: node.data.name
                , nodeRecord: newNodeRecord, accountGrants: node.data.account_grants});           
        }
                
        copy.attributes.nodeRecord.beginEdit();
        copy.attributes.nodeRecord.set('path', newPath);
        copy.attributes.nodeRecord.endEdit();       
        
        copy.parentNode = target;
        return copy;
    },
    
    dropIntoTree: function(fileSelector) {
        Tine.log.debug(fileSelector.button_container.getCenterXY());
    }

});
