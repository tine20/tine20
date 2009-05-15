/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         show new mails and number of unread mails next to folder name
 * TODO         add multiple accounts + change account settings
 * TODO         add folder model?
 * TODO         save tree state? @see http://examples.extjs.eu/?ex=treestate
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * folder tree panel
 * 
 * @class Tine.Felamimail.TreePanel
 * @extends Ext.tree.TreePanel
 */
Tine.Felamimail.TreePanel = Ext.extend(Ext.tree.TreePanel, {
	
    /**
     * @cfg {application}
     */
    app: null,
    
    /**
     * @cfg {String}
     */
    containerName: 'Folder',
    
    /****** TreePanel config ******/
	rootVisible: true,
	autoScroll: true,
    id: 'felamimail-tree',
    // drag n drop
    enableDrop: true,
    ddGroup: 'mailToTreeDDGroup',
	
    /**
     * init
     */
    initComponent: function() {
    	
        this.loader = new Tine.Felamimail.TreeLoader({
            app: this.app
        });

        // set the root node
        this.root = new Ext.tree.AsyncTreeNode({
            text: 'default',
            globalname: '',
            account_id: 'default',
            draggable: false,
            allowDrop: false,
            expanded: false,
            leaf: false,
            id: '/'
            //iconCls: 'FelamimailMessage'
        });
        
        this.initContextMenu();
        
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
    	// add handlers
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
	},
    
    /**
     * init context menu
     * 
     * TODO add account context menu
     */
    initContextMenu: function() {
        var il8n = new Locale.Gettext();
        il8n.textdomain('Felamimail');
        
        /*
        this.contextMenuAccount = Tine.widgets.tree.ContextMenu.getMenu({
            il8n: il8n,
            nodeName: this.containerName,
            actions: ['add'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
        */
        
        var config = {
            il8n: il8n,
            nodeName: il8n._('Folder'),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Folder'
        };        
        
        var updateCacheConfig = {
            text: _('Update Cache'),
            iconCls: 'action_update_cache',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.refreshFolder',
                        folderId: this.ctxNode.id
                    },
                    scope: this,
                    success: function(_result, _request){
                        // update grid
                        this.filterPlugin.onFilterChange();
                    }
                });
            }
        };
        
        // system folder ctx menu
        config.actions = ['add', updateCacheConfig];
        this.contextMenuSystemFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        // user folder ctx menu
        config.actions = ['add', 'rename', updateCacheConfig, 'delete'];
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        // trash ctx menu
        config.actions = ['add', {
            text: _('Empty Folder'),
            iconCls: 'action_folder_emptytrash',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.emptyFolder',
                        folderId: this.ctxNode.id
                    },
                    scope: this,
                    success: function(_result, _request){
                        // update grid
                        this.filterPlugin.onFilterChange();
                    }
                });
            }
        }];
        this.contextMenuTrash = Tine.widgets.tree.ContextMenu.getMenu(config);
    },
    
    
    /**
     * returns a filter plugin to be used in a grid
     *
     * TODO use folder id here
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	//console.log(scope);
                	var node = scope.getSelectionModel().getSelectedNode();
                    return [
                        {field: 'folder_id',     operator: 'equals', value: (node) ? node.id : '' }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    },
    
    /***************** event handler *******************/
    
    /**
     * on click handler
     * 
     * - expand + select node
     * - update filter toolbar of grid
     * 
     * @param {} node
     */
    onClick: function(node) {
        node.expand();
        node.select();
        
        if (node.id && node.id != '/') {
            this.filterPlugin.onFilterChange();
        }
    },
    
    /**
     * show context menu for folder tree
     * 
     * items:
     * - create folder
     * - rename folder
     * - delete folder
     * - ...
     * 
     * @param {} node
     * @param {} event
     * 
     * TODO add account context menu
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        
        if (! node.attributes.folderNode) {
            // TODO add/edit/remove account
            return;
        } else {
            
            if (node.attributes.globalname == 'Trash') {
                this.contextMenuTrash.showAt(event.getXY());
            } else if (node.attributes.systemFolder) {
                this.contextMenuSystemFolder.showAt(event.getXY());    
            } else {
                this.contextMenuUserFolder.showAt(event.getXY());
            }
        }
    },
    
    /**
     * mail got dropped on folder node
     * 
     * @param {Object} dropEvent
     */
    onBeforenodedrop: function(dropEvent) {
        
        var folderId = dropEvent.target.id;
        var ids = [];
        
        for (var i=0; i < dropEvent.data.selections.length; i++) {
            ids.push(dropEvent.data.selections[i].id);
        };
        
        // move messages to folder
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.moveMessages',
                folderId: folderId,
                ids: Ext.util.JSON.encode(ids)
            },
            scope: this,
            success: function(_result, _request){
                // update grid
                this.filterPlugin.onFilterChange();
            }
        });
        
        return true;
    }
});

/**
 * tree loader
 * 
 * @class Tine.Felamimail.TreeLoader
 * @extends Tine.widgets.tree.Loader
 */
Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
	
    method: 'Felamimail.searchFolders',

    /**
     * @private
     */
    initComponent: function() {
        this.filter = [];
        
        Tine.Felamimail.TreeLoader.superclass.initComponent.call(this);
    },
    
    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback){
    	// add globalname to filter
    	this.filter = [
            {field: 'account_id', operator: 'equals', value: node.attributes.account_id},
            {field: 'globalname', operator: 'equals', value: node.attributes.globalname}
        ];
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * TODO try to disable '+' on nodes that don't have children / it looks like that leafs can't be drop targets :(
     * TODO what about equal folder names (=id) in different subtrees?
     * TODO generalize this?
     */
    createNode: function(attr) {
    	var node = {
    		id: attr.id,
    		leaf: false,
    		text: attr.localname,
    		globalname: attr.globalname,
    		account_id: attr.account_id,
    		folderNode: true,
            allowDrop: true,
            systemFolder: (attr.system_folder == '1')
            //expandable: (attr.has_children == '1'),
            //allowChildren: (attr.has_children == 1)
            //childNodes: []
    	};
        //console.log(node);
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});
