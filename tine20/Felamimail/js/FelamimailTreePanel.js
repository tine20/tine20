/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.TreePanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>Account/Folder Tree Panel</p>
 * <p>Tree of Accounts with folders</p>
 * <pre>
 * TODO         somehow we still get timeout errors when creating cache / selecting folders with lots of messages ... fix that
 * low priority:
 * TODO         only allow nodes as drop target (not 'between')
 * TODO         make inbox/drafts/templates configurable in account
 * TODO         save tree state? @see http://examples.extjs.eu/?ex=treestate
 * TODO         add unread count to intelligent folders
 * </pre>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.TreePanel
 * 
 */
Tine.Felamimail.TreePanel = Ext.extend(Ext.tree.TreePanel, {
	
    /**
     * @property app
     * @type Tine.Felamimail.Application
     */
    app: null,
    
    /**
     * @property accountStore
     * @type Ext.data.JsonStore
     */
    accountStore: null,
    
    /**
     * @property updateFoldersTask
     * @type Ext.util.DelayedTask
     */
    updateFoldersTask: null,
    
    /**
     * refresh time in milliseconds
     * 
     * @property updateFolderRefreshTime
     * @type Number
     */
    updateFolderRefreshTime: 60000, // 1 min
    
    /**
     * @cfg {String} containerName
     */
    containerName: 'Folder',
    
    /**
     * TreePanel config
     * @private
     */
	rootVisible: false,
	autoScroll: true,
    id: 'felamimail-tree',
    // drag n drop
    enableDrop: true,
    ddGroup: 'mailToTreeDDGroup',
    border: false,
    // somehow this does not work as expected (only allow nodes as drop target)
    //dropConfig: {appendOnly:true},
	
    /**
     * init
     * @private
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
        this.initContextMenus();
        
        // get folder update interval from preferences
        var updateInterval = Tine.Felamimail.registry.get('preferences').get('updateInterval');
        if (! isNaN(updateInterval)) {
            // convert to milliseconds
            this.updateFolderRefreshTime = 60000*updateInterval;
        }
        
        this.updateFoldersTask = new Ext.util.DelayedTask(this.updateFolders, this);
        
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);

    	// add handlers
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
        this.on('append', function(tree, node, appendedNode, index) {
            if (Ext.util.Format.lowercase(appendedNode.attributes.localname) == 'inbox') {
                appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
                    appendedNode.fireEvent('click', appendedNode);
                }, appendedNode.ui);
            }
        }, this);
	},
    
    /**
     * add accounts from registry as nodes to root node
     * @private
     */
    initAccounts: function() {
        this.accountStore = Tine.Felamimail.loadAccountStore();
        this.accountStore.each(this.addAccount, this);
    },
    
    /**
     * add account record to root node
     * 
     * @param {Tine.Felamimail.Model.Account} record
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
            cls: 'felamimail-node-account',
            intelligent_folders: (record.get('intelligent_folders')) ? record.get('intelligent_folders') : 0,
            delimiter: record.get('delimiter'),
            ns_personal: record.get('ns_personal'),
            account_id: record.data.id,
            listeners: {
                scope: this,
                load: function(node) {
                    
                    // add 'intelligent' folders
                    if (node.attributes.intelligent_folders == 1/* || node.attributes.intelligent_folders == '1'*/) {
                        var markedNode = new Ext.tree.TreeNode({
                            id: record.data.id + '/marked',
                            localname: 'marked', //this.app.i18n._('Marked'),
                            globalname: 'marked',
                            draggable: false,
                            allowDrop: false,
                            expanded: false,
                            text: this.app.i18n._('Marked'),
                            qtip: this.app.i18n._('Contains marked messages'),
                            leaf: true,
                            cls: 'felamimail-node-intelligent-marked',
                            account_id: record.data.id
                        });
                
                        node.appendChild(markedNode);
                    
                        var unreadNode = new Ext.tree.TreeNode({
                            id: record.data.id + '/unread',
                            localname: 'unread', //this.app.i18n._('Marked'),
                            globalname: 'unread',
                            draggable: false,
                            allowDrop: false,
                            expanded: false,
                            text: this.app.i18n._('Unread'),
                            qtip: this.app.i18n._('Contains unread messages'),
                            leaf: true,
                            cls: 'felamimail-node-intelligent-unread',
                            account_id: record.data.id
                        });
                
                        node.appendChild(unreadNode);
                    }
                }
            }
        });
        
        this.root.appendChild(node);
    },
    
    /**
     * init context menu
     * 
     * @private
     */
    initContextMenus: function() {
        
        // define additional actions
        
        // TODO show loading... icon next to folder
        var updateCacheConfigAction = {
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
                        if (this.ctxNode.id == this.getSelectionModel().getSelectedNode().id) {
                            // update grid
                            this.updateMessageCache(this.ctxNode, true);
                            this.filterPlugin.onFilterChange();
                        } else {
                            this.ctxNode.attributes.cache_status = 'pending';
                        }
                    }
                });
            }
        };

        // TODO show loading... icon next to folder
        var emptyFolderAction = {
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
                        if (this.ctxNode.id == this.getSelectionModel().getSelectedNode().id) {
                            // update grid
                            this.updateMessageCache(this.ctxNode, true);
                            this.filterPlugin.onFilterChange();
                        } else {
                            this.ctxNode.attributes.cache_status = 'pending';
                        }
                    },
                    timeout: 120000 // 2 minutes
                });
            }
        };
        
        // we need this for adding folders to account (root level)
        var addFolderToRootAction = {
            text: this.app.i18n._('Add Folder'),
            iconCls: 'action_add',
            scope: this,
            disabled: true,
            handler: function() {
                Ext.MessageBox.prompt(String.format(_('New {0}'), this.app.i18n._('Folder')), String.format(_('Please enter the name of the new {0}:'), this.app.i18n._('Folder')), function(_btn, _text) {
                    if( this.ctxNode && _btn == 'ok') {
                        if (! _text) {
                            Ext.Msg.alert(String.format(_('No {0} added'), this.app.i18n._('Folder')), String.format(_('You have to supply a {0} name!'), this.app.i18n._('Folder')));
                            return;
                        }
                        Ext.MessageBox.wait(_('Please wait'), String.format(_('Creating {0}...' ), this.app.i18n._('Folder')));
                        var parentNode = this.ctxNode;
                        
                        var params = {
                            method: 'Felamimail.addFolder',
                            name: _text
                        };
                        
                        params.parent = '';
                        params.accountId = parentNode.id;
                        
                        Ext.Ajax.request({
                            params: params,
                            scope: this,
                            success: function(_result, _request){
                                var nodeData = Ext.util.JSON.decode(_result.responseText);
                                var newNode = this.loader.createNode(nodeData);
                                parentNode.appendChild(newNode);
                                Ext.MessageBox.hide();
                            }
                        });
                        
                    }
                }, this);
            }
        };
        
        var editAccountAction = {
            text: this.app.i18n._('Edit Account'),
            iconCls: 'FelamimailIconCls',
            scope: this,
            disabled: ! Tine.Tinebase.common.hasRight('manage_accounts', 'Felamimail'),
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
                            this.ctxNode.attributes.intelligent_folders = account.get('intelligent_folders');
                            this.accountStore.reload();
                            
                            // reload tree node
                            this.ctxNode.reload(function(callback) {
                            });
                            
                            // update grid
                            this.filterPlugin.onFilterChange();
                        }
                    }
                });        
            }
        };
        
        var reloadFolderAction = {
            text: String.format(_('Reload {0}'), this.app.i18n._('Folder')),
            iconCls: 'x-tbar-loading',
            scope: this,
            handler: function() {
                if (this.ctxNode) {
                    // trigger updateFolderCache
                    this.updateFolderCache(this.ctxNode);
                    
                    this.ctxNode.reload(function(node) {
                        node.expand();
                        node.select();
                    });
                }
            }
        };

        // mutual config options
        
        var config = {
            nodeName: this.app.i18n._('Folder'),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Folder'
        };
        
        // system folder ctx menu

        config.actions = ['add', updateCacheConfigAction, reloadFolderAction];
        this.contextMenuSystemFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        // user folder ctx menu

        config.actions = ['add', 'rename', updateCacheConfigAction, reloadFolderAction, 'delete'];
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        // trash ctx menu
        
        config.actions = ['add', emptyFolderAction, reloadFolderAction];
        this.contextMenuTrash = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        // account ctx menu
        
        this.contextMenuAccount = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._('Account'),
            actions: [editAccountAction, addFolderToRootAction, 'reload', 'delete'],
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Account'
        });        
    },
       
    /**
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.TreePanel.superclass.afterRender.call(this);

        var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
        this.expandPath('/root/' + defaultAccount + '/');
        
        // start delayed task
        this.updateFoldersTask.delay(this.updateFolderRefreshTime);
    },
    
   /**
     * returns a filter plugin to be used in a grid
     * @private
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	var node = scope.getSelectionModel().getSelectedNode();
                    
                    if (node && node.attributes.globalname == 'marked') {
                        return [
                            {field: 'flags',        operator: 'equals', value: '\\Flagged' },
                            {field: 'account_id',   operator: 'equals', value: node.attributes.account_id }
                        ];
                    } else if (node && node.attributes.globalname == 'unread') {
                        return [
                            {field: 'flags',        operator: 'not', value: '\\Seen' },
                            {field: 'account_id',   operator: 'equals', value: node.attributes.account_id }
                        ];
                    } else {
                        return [
                            {field: 'folder_id',    operator: 'equals', value: (node && node.attributes.folder_id) ? node.attributes.folder_id : '' }
                        ];
                    }
                },
                // TODO use createSequence?
                onBeforeLoad: function(store, options) {
                    
                    options = options || {};
                    options.params = options.params || {};
                    options.params.filter = options.params.filter ? options.params.filter : [];

                    var value = this.getValue();
                    if (value && Ext.isArray(options.params.filter)) {
                        value = Ext.isArray(value) ? value : [value];
                        for (var i=0; i<value.length; i++) {
                            options.params.filter.push(value[i]);
                        }
                    }                
                    
                    // stop request if folder_id is empty in filter
                    if (options.params.filter[0] && options.params.filter[0].field == 'folder_id' && options.params.filter[0].value == '') {
                        return false;
                    }
                }
            });
        }
        
        return this.filterPlugin;
    },
    
    /**
     * update unread count of a folder node (use selected node per default)
     * 
     * @param {Number} change
     * @param {Number} unreadcount [optional]
     * @param {Ext.tree.AsyncTreeNode} node [optional]
     */
    updateUnreadCount: function(change, unreadcount, node) {
        
        if (! node) {
            var node = this.getSelectionModel().getSelectedNode();
        }
        
        if (! change ) {
            change = Number(unreadcount) - Number(node.attributes.unreadcount);
        }
        
        if (Number(change) != 0) {
            node.attributes.unreadcount = Number(node.attributes.unreadcount) + Number(change);
            
            if (node.attributes.unreadcount > 0) {
                node.setText(node.attributes.localname + ' (' + node.attributes.unreadcount + ')');
            } else {
                node.setText(node.attributes.localname);
            }
        }
        
        node.getUI().removeClass('felamimail-node-unread');
        if (node.attributes.unreadcount > 0) {
            node.getUI().addClass('felamimail-node-unread');
        }
    },
    
    /**
     * delayed task function
     * 
     * TODO call updateFolderStatus?
     */
    updateFolders: function() {
        var node = this.getSelectionModel().getSelectedNode();
        if (node) {
            this.updateMessageCache(node, false, true);
        }
        this.updateFoldersTask.delay(this.updateFolderRefreshTime);
    },
    
    /**
     * update folder status of all visible (?) folders
     * 
     * @param {Boolean} recursive
     * @param {Ext.tree.AsyncTreeNode} node [optional]
     * 
     * TODO make this work for multiple accounts
     * TODO make recursive work for delayed task or ping update
     */
    updateFolderStatus: function(recursive, node) {
        
        if (recursive) {
            Ext.Msg.alert('not implemented yet');
            return;
        }
        
        // get account and folder id
        if (! node) {
            node = this.getSelectionModel().getSelectedNode();
        }
        
        var folderId = node.attributes.folder_id;
        var accountId = node.attributes.account_id;
        
        // update folder status
        if (folderId && accountId) {
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateFolderStatus',
                    folderId: folderId,
                    accountId: accountId
                },
                scope: this,
                success: function(_result, _request) {
                    // update folder counters / class
                    var folderData = Ext.util.JSON.decode(_result.responseText);
                    this.updateUnreadCount(null, folderData[0].unreadcount, node);
                }
            });
        }
    },
    
    /**
     * get active account by checking selected node
     * @return Tine.Felamimail.Model.Account
     */
    getActiveAccount: function() {
        var node = this.getSelectionModel().getSelectedNode();
        var accountId = node.attributes.account_id;
        
        var result = this.accountStore.getById(accountId);
        
        return result;
    },
    
    /**
     * on click handler
     * 
     * - expand + select node
     * - update filter toolbar of grid
     * 
     * @param {Ext.tree.AsyncTreeNode} node
     * @private
     */
    onClick: function(node) {
        
        if (node.expandable) {
            node.expand();
        }
        node.select();
        
        if (node.id && node.id != '/') {
            this.updateMessageCache(node);
        }
    },
    
    /**
     * update message cache (and trigger reload store)
     * @param {Ext.tree.AsyncTreeNode} node
     * 
     * TODO add accountId?
     */
    updateMessageCache: function(node, force, delayedTask)
    {
        var folderId = node.attributes.folder_id;
        //var accountId = node.attributes.account_id;
        
        if (folderId && (node.attributes.cache_status != 'complete' || force || delayedTask) /* && accountId*/) {
            // add loadmask to grid
            if (! delayedTask) {
                var cacheMask = new Ext.LoadMask(this.app.mainScreen.gridPanel.grid.getEl(), {
                    msg:        this.app.i18n._('Initializing folder cache ...'),
                    removeMask: true
                });
                cacheMask.show();
            }
            
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateMessageCache',
                    folderId: folderId
                    //accountId: accountId
                },
                scope: this,
                timeout: 60000, // 1 minute
                success: function(_result, _request) {
                    // update folder counters / class
                    var folderData = Ext.util.JSON.decode(_result.responseText);
                    
                    // update node values
                    if (node.attributes.unreadcount != folderData.unreadcount || node.attributes.totalcount != folderData.totalcount) {

                        if (delayedTask) {
                            //if (node.attributes.unreadcount < folderData.unreadcount) {
                            if (folderData.recent && folderData.unreadcount > 0) {
                                // show toast window on new mails
                                Ext.ux.Notification.show(
                                    this.app.i18n._('New mails'), 
                                    String.format(this.app.i18n._('You got {0} new mail(s) in Folder '), 
                                        folderData.unreadcount - node.attributes.unreadcount) 
                                        + node.attributes.localname
                                ); 
                            }
                            
                            // update only if something changed
                            this.filterPlugin.onFilterChange();
                        }
                        
                        node.attributes.totalcount = folderData.totalcount;
                        this.updateUnreadCount(null, folderData.unreadcount);
                    }
                    node.attributes.cache_status = folderData.cache_status;
                    
                    // update grid and remove load mask
                    if (! delayedTask) {
                        this.filterPlugin.onFilterChange();
                        cacheMask.hide();
                    }
                }
            });
        } else {
            this.filterPlugin.onFilterChange();
        }
    },

    /**
     * update folder cache
     * @param {Ext.tree.AsyncTreeNode} node
     * 
     */
    updateFolderCache: function(node)
    {
        var globalname = node.attributes.globalname;
        var accountId = node.attributes.account_id;
        
        if (globalname && accountId) {
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateFolderCache',
                    folderNames: globalname,
                    accountId: accountId
                },
                scope: this,
                success: function(_result, _request) {
                }
            });
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
     * @private
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        
        if (! node.attributes.folderNode) {
            // edit/remove account
            if (node.attributes.account_id !== 'default') {
                
                // check account personal namespace -> disable 'add folder' if namespace is other than root 
                this.contextMenuAccount.items.each(function(item) {
                    if (item.iconCls == 'action_add') {
                        item.setDisabled(node.attributes.ns_personal != '');
                    }
                });
                
                this.contextMenuAccount.showAt(event.getXY());
            }
        } else {
            
            var account = Tine.Felamimail.loadAccountStore().getById(node.attributes.account_id);
            
            if (account && node.attributes.globalname == account.get('trash_folder')) {
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
     * @private
     */
    onBeforenodedrop: function(dropEvent) {
        
        var targetFolderId = dropEvent.target.attributes.folder_id;
        var ids = [];
        
        for (var i=0; i < dropEvent.data.selections.length; i++) {
            ids.push(dropEvent.data.selections[i].id);
        };
        
        // move messages to folder
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.moveMessages',
                folderId: targetFolderId,
                ids: Ext.util.JSON.encode(ids)
            },
            scope: this,
            success: function(_result, _request){
                // update grid
                this.filterPlugin.onFilterChange();
                
                // update folder status of both folders
                this.updateFolderStatus(false, dropEvent.target);
                this.updateFolderStatus(false);
            }
        });
        
        return true;
    }
});

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.TreeLoader
 * @extends     Tine.widgets.tree.Loader
 * 
 * <p>Felamimail Account/Folder Tree Loader</p>
 * <p></p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.TreeLoader
 * 
 */
Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
	
    // private
    method: 'Felamimail.searchFolders',

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
     * TODO     add qtip again (problem: it can't be changed later)?
     */
    createNode: function(attr) {
        
        var account = Tine.Felamimail.loadAccountStore().getById(attr.account_id);
        
        // check for account setting
        attr.has_children = (
            account 
            && account.get('has_children_support') 
            && account.get('has_children_support') == '1'
        ) ? attr.has_children : true;
        
        //var qtiptext = this.app.i18n._('Totalcount') + ': ' + attr.totalcount 
        //    + ' / ' + this.app.i18n._('Cache') + ': ' + attr.cache_status;

        var node = {
    		id: attr.id,
    		leaf: false,
    		text: attr.localname,
            localname: attr.localname,
    		globalname: attr.globalname,
    		account_id: attr.account_id,
            folder_id: attr.id,
    		folderNode: true,
            allowDrop: true,
            //qtip: qtiptext,
            systemFolder: (attr.system_folder == '1'),
            unreadcount: attr.unreadcount,
            totalcount: attr.totalcount,
            cache_status: attr.cache_status,
            
            // if it has no children, it shouldn't have an expand icon 
            expandable: attr.has_children,
            expanded: ! attr.has_children
    	};
        
        // if it has no children, it shouldn't have an expand icon 
        if (! attr.has_children) {
            node.children = [];
            node.cls = 'x-tree-node-collapsed';
        }

        // show standard folders icons 
        if (account) {
            if (account.get('trash_folder') == attr.globalname) {
                if (attr.totalcount > 0) {
                    node.cls = 'felamimail-node-trash-full';
                } else {
                    node.cls = 'felamimail-node-trash';
                }
            }
            if (account.get('sent_folder') == attr.globalname) {
                node.cls = 'felamimail-node-sent';
            }
        }
        if ('INBOX' == attr.globalname) {
            node.cls = 'felamimail-node-inbox';
        }
        if ('Drafts' == attr.globalname) {
            node.cls = 'felamimail-node-drafts';
        }
        if ('Templates' == attr.globalname) {
            node.cls = 'felamimail-node-templates';
        }
        if ('Junk' == attr.globalname) {
            node.cls = 'felamimail-node-junk';
        }

        // add unread class to node
        if (attr.unreadcount > 0) {
            node.text = node.text + ' (' + attr.unreadcount + ')';
            node.cls = node.cls + ' felamimail-node-unread'; // x-tree-node-collapsed';
        }
        
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    },
    
    /**
     * handle failure to show credentials dialog if imap login failed
     * 
     * @param {String} response
     * @param {Object} options
     */
    handleFailure: function(response, options) {
        var responseText = Ext.util.JSON.decode(response.responseText);
        
        if (responseText.message == 'cannot login, user or password wrong' ||
            responseText.message == 'need at least user in params') {
            
            // get account id and update username/password
            var accountNode = options.argument.node;
            var accountId = accountNode.attributes.account_id;
            
            // remove intelligent folders
            accountNode.attributes.intelligent_folders = 0;
            
            // cancel loading
            accountNode.loading = false;
            accountNode.ui.afterLoad(accountNode);
                        
            var credentialsWindow = Tine.widgets.dialog.CredentialsDialog.openWindow({
                windowTitle: String.format(this.app.i18n._('IMAP Credentials for {0}'), accountNode.text),
                appName: 'Felamimail',
                credentialsId: accountId,
                i18nRecordName: this.app.i18n._('Credentials'),
                recordClass: Tine.Tinebase.Model.Credentials,
                listeners: {
                    scope: this,
                    'update': function(data) {
                        // update account node
                        var account = Tine.Felamimail.loadAccountStore().getById(accountId);
                        accountNode.attributes.intelligent_folders = account.get('intelligent_folders');
                        accountNode.reload(function(callback) {
                        }, this);
                    }
                }
            });
            
        } else {
            Ext.Ajax.fireEvent('requestexception', Ext.Ajax, response, options);
        }
    }
});
