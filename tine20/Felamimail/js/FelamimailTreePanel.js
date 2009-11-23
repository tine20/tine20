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
     * @property updateMessagesTask
     * @type Ext.util.DelayedTask
     */
    updateMessagesTask: null,
    
    /**
     * refresh time in milliseconds
     * 
     * @property updateFolderRefreshTime
     * @type Number
     */
    updateFolderRefreshTime: 60000, // 1 min
    
    /**
     * refresh time in milliseconds
     * 
     * @property updateMessageRefreshTime
     * @type Number
     */
    updateMessageRefreshTime: 60000, // 1 min
    
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
        var updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval'));
        if (updateInterval > 0) {
            // convert to milliseconds
            this.updateMessageRefreshTime = 60000*updateInterval;
            this.updateMessagesTask = new Ext.util.DelayedTask(this.updateMessages, this);
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
                    // trigger updateMessageCache
                    this.updateMessageCache(this.ctxNode);
                    
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
        
        // start delayed tasks
        if (this.updateMessagesTask !== null) {
            this.updateMessagesTask.delay(this.updateMessageRefreshTime);
        }
        if (this.updateFoldersTask !== null) {
            this.updateFoldersTask.delay(this.updateFolderRefreshTime);
        }
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
     * - calls updateFolderStatus and updateMessageCache
     */
    updateMessages: function() {
        var node = this.getSelectionModel().getSelectedNode();
        if (node) {
            this.updateMessageCache(node, false, true);
            this.updateFolderStatus(true, node, true);
        }
        this.updateMessagesTask.delay(this.updateMessageRefreshTime);
    },

    /**
     * delayed task function
     * - calls updateFolderStatus
     */
    updateFolders: function() {
        var node = this.getSelectionModel().getSelectedNode();
        if (node) {
            this.updateFolderStatus(true, node, false);
        }
        this.updateFoldersTask.delay(this.updateFolderRefreshTime);
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Boolean} multiple
     * @param {Ext.tree.AsyncTreeNode} node [optional]
     * @param {Boolean} updateMessageCache
     * 
     * TODO make this work for multiple accounts ?
     * TODO get all visible nodes of active account ?
     */
    updateFolderStatus: function(multiple, node, updateMessageCache) {
        
        if (multiple) {
            // get all nodes on the same level with the active node
            var parent = node.parentNode;
            
            if (parent.id == 'root') {
                return;
            }
            
            var folderIds = [];
            parent.eachChild(function(child) {
                if (child.id != node.id && child.attributes.localname != 'unread' && child.attributes.localname != 'marked') {
                    folderIds.push(child.id);
                }
            }, this);
            
        } else {
            // single node
            if (! node) {
                node = this.getSelectionModel().getSelectedNode();
            }
            
            var folderIds = [node.attributes.folder_id];
        }
        
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.updateFolderStatus',
                folderIds: Ext.util.JSON.encode(folderIds),
                accountId: node.attributes.account_id
            },
            scope: this,
            timeout: 60000, // 1 minute
            success: function(_result, _request) {
                var folderData = Ext.util.JSON.decode(_result.responseText);
                
                // update folders
                if (multiple) {
                    // update cache of one extra folder
                    var updating = false;
                    for (var i = 0; i < folderData.length; i++) {
                        var updateNode = this.getNodeById(folderData[i][0].id);
                        
                        // trigger updateMessageCache if needed (only if not already updating / cache pedning or different unreadcounts)
                        if (updateMessageCache && ! updating && (updateNode.attributes.cache_status == 'pending' || updateNode.attributes.unreadcount != folderData[i][0].unreadcount)) {
                            // calls updateUnreadCount if spomething changed
                            this.updateMessageCache(updateNode, false, true);
                            updating = true;
                        } else {
                            this.updateUnreadCount(null, folderData[i][0].unreadcount, updateNode);
                        }
                    }
                } else {
                    this.updateUnreadCount(null, folderData[0][0].unreadcount, node);
                }
            },
            failure: function() {
                // do nothing
            }
        });
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
     * TODO add custom exception / on failure / on timeout handler -> this should never show errors to the user
     * TODO add accountId?
     */
    updateMessageCache: function(node, force, delayedTask)
    {
        var folderId = node.attributes.folder_id;
        //var accountId = node.attributes.account_id;
        
        if (
            folderId 
            && (node.attributes.cache_status != 'complete' || force || delayedTask)
            && node.attributes.localname != 'unread' && node.attributes.localname != 'marked'
            /* && accountId*/
        ) {
            node.getUI().addClass("x-tree-node-loading");
            
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateMessageCache',
                    folderId: folderId
                    //accountId: accountId
                },
                scope: this,
                //timeout: 60000, // 1 minute
                timeout: 600000, // 10 minutes -> TODO lower timeout when caching is resumable
                success: function(_result, _request) {
                    // update folder counters / class
                    var folderData = Ext.util.JSON.decode(_result.responseText);
                    
                    // update node values
                    if (node.attributes.unreadcount != folderData.unreadcount || node.attributes.totalcount != folderData.totalcount) {

                        if (delayedTask) {
                            //if (node.attributes.unreadcount < folderData.unreadcount) {
                            if (folderData.recent && (folderData.unreadcount - node.attributes.unreadcount) > 0) {
                                // show toast window on new mails
                                Ext.ux.Notification.show(
                                    this.app.i18n._('New mails'), 
                                    String.format(this.app.i18n._('You got {0} new mail(s) in Folder '), 
                                        folderData.unreadcount - node.attributes.unreadcount) 
                                        + node.attributes.localname
                                );
                                // update only if something changed
                                if (this.getSelectionModel().getSelectedNode().id = node.id) {
                                    this.filterPlugin.onFilterChange();
                                }
                            }
                        }
                        
                        node.attributes.totalcount = folderData.totalcount;
                        this.updateUnreadCount(null, folderData.unreadcount, node);
                    }
                    node.attributes.cache_status = folderData.cache_status;
                    
                    // update grid and remove style
                    if (! delayedTask) {
                        if (this.getSelectionModel().getSelectedNode().id = node.id) {
                            this.filterPlugin.onFilterChange();
                        }
                    }
                    if (folderData.cache_status == 'complete') {
                        node.getUI().removeClass("x-tree-node-loading");
                    }
                },
                failure: function(response, options) {
                    // call handle failure in tree loader and show credentials dialog / reload account afterwards
                    this.loader.handleFailure(response, options, node.parentNode, false);
                    node.getUI().removeClass("x-tree-node-loading");
                }
            });
        } else if (! delayedTask) {
            // update message cache again if no delayed task is set
            if (this.updateMessagesTask === null) {
                node.attributes.cache_status = 'pending';
                this.updateMessageCache.defer(2000, this, [node]);
            }
            
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
