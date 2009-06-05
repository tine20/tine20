/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         reload folders (and number of unread messages) every x minutes 
 *              -> via ping or ext.util.delayedtask ?
 * TODO         show error message when username/password is wrong/missing
 * TODO         add ctx menu action for 'refreshing' of folders
 * TODO         refresh folders when account has changed
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
    
    accountStore: null,
    
    /****** TreePanel config ******/
	rootVisible: false,
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
        this.root = new Ext.tree.TreeNode({
            text: 'default',
            draggable: false,
            allowDrop: false,
            expanded: true,
            leaf: false,
            id: 'root'
        });

        // add account nodes and context menu
        this.initAccounts();
        this.initContextMenu();
        
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
    	// add handlers
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
	},
    
    /**
     * add accounts from registry as nodes to root node
     */
    initAccounts: function() {
        this.accountStore = Tine.Felamimail.loadAccountStore();
        this.accountStore.each(this.addAccount, this);
    },
    
    /**
     * add account record to root node
     * 
     * @param {Tine.Felamimail.Model.Account} record
     * 
     * @private
     */
    addAccount: function(record) {
        
        var node = new Ext.tree.AsyncTreeNode({
            id: record.data.id,
            record: record,
            globalname: '',
            draggable: false,
            allowDrop: false,
            expanded: false,
            text: record.get('name'),
            qtip: record.get('host'),
            leaf: false,
            account_id: record.data.id
        });
        
        //console.log(record);
        //console.log(node);
        
        this.root.appendChild(node);
    },
    
    /**
     * init context menu
     */
    initContextMenu: function() {
        var editAccount = {
            text: this.app.i18n._('Edit Account'),
            iconCls: 'FelamimailIconCls',
            scope: this,
            handler: function() {
                var record = this.accountStore.getById(this.ctxNode.attributes.account_id);
                var popupWindow = Tine.Felamimail.AccountEditDialog.openWindow({
                    record: record,
                    listeners: {
                        scope: this,
                        'update': function(record) {
                            var account = new Tine.Felamimail.Model.Account(Ext.util.JSON.decode(record));
                            
                            // update tree node + store
                            this.ctxNode.setText(account.get('name'));
                            this.accountStore.reload();
                        }
                    }
                });        
            }
        };
        
        this.contextMenuAccount = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._('Account'),
            actions: [editAccount, 'delete'],
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Account'
        });
        
        var config = {
            nodeName: this.app.i18n._('Folder'),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Folder'
        };        
        
        var updateCacheConfig = {
            text: this.app.i18n._('Update Cache'),
            iconCls: 'action_update_cache',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.refreshFolder',
                        folderId: this.ctxNode.attributes.folder_id
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
            text: this.app.i18n._('Empty Folder'),
            iconCls: 'action_folder_emptytrash',
            scope: this,
            handler: function() {
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.emptyFolder',
                        folderId: this.ctxNode.attributes.folder_id
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
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.TreePanel.superclass.afterRender.call(this);

        var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
        this.expandPath('/root/' + defaultAccount + '/');
        this.selectPath('/root/' + defaultAccount + '/');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     *
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	var node = scope.getSelectionModel().getSelectedNode();
                    return [
                        {field: 'folder_id',     operator: 'equals', value: (node) ? node.attributes.folder_id : '' }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    },
    
    /**
     * update unread count
     * 
     * @param {} change
     * 
     * TODO make css class update work (use add/remove class?)
     */
    updateUnreadCount: function(change) {
        //console.log('update unread count: ' + change);
        
        var node = this.getSelectionModel().getSelectedNode();
        node.attributes.unreadcount = Number(node.attributes.unreadcount) + Number(change);
        
        //console.log(node.attributes);
        
        if (node.attributes.unreadcount > 0) {
            node.setText(node.attributes.localname + ' (' + node.attributes.unreadcount + ')');
            if (node.attributes.unreadcount == 1 && change == 1) {
                // 0 -> 1
                node.getUI().addClass('node_unread');
            }
        } else {
            node.setText(node.attributes.localname);
            node.getUI().removeClass('node_unread');
        }
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
            
            //this.loader.load(node.parentNode, null);
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
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        
        if (! node.attributes.folderNode) {
            // edit/remove account
            if (node.attributes.account_id !== 'default') {
                this.contextMenuAccount.showAt(event.getXY());
            }
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
        
        var folderId = dropEvent.target.attributes.folder_id;
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
     */
    createNode: function(attr) {
    	var node = {
    		//id: attr.id,
            id: attr.localname,
    		leaf: false,
    		text: attr.localname,
            localname: attr.localname,
    		globalname: attr.globalname,
    		account_id: attr.account_id,
            folder_id: attr.id,
    		folderNode: true,
            allowDrop: true,
            systemFolder: (attr.system_folder == '1'),
            unreadcount: attr.unreadcount
            //expandable: (attr.has_children == '1'),
            //allowChildren: (attr.has_children == 1)
            //childNodes: []
    	};

        if (attr.unreadcount > 0) {
            node.text = node.text + ' (' + attr.unreadcount + ')';
            node.cls = 'node_unread';
        }
        
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});
