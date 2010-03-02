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
 * TODO         use pie for progress
 * TODO         fix drop target
 * low priority:
 * TODO         only allow nodes as drop target (not 'between')
 * TODO         make inbox/drafts/templates configurable in account
 * TODO         save tree state? @see http://examples.extjs.eu/?ex=treestate
 * TODO         add unread count to intelligent folders
 * TODO         disable delete action in account ctx menu if user has no manage_accounts right
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
     * @type Ext.data.JsonStore
     */
    folderStore: null,
    
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
    updateMessageRefreshTime: 20000, // 20 seconds
    
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
        
        // init folder store
        this.folderStore = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Felamimail.Model.Folder,
            listeners: {
                scope: this,
                update: this.onUpdateFolderStore
            },
            proxy: Tine.Felamimail.accountBackend,
            reader: Tine.Felamimail.accountBackend.getReader()            
        });
    	
        // init tree loader
        this.loader = new Tine.Felamimail.TreeLoader({
            folderStore: this.folderStore,
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
        var initCtxMenu = Tine.Felamimail.setTreeContextMenus.createDelegate(this);
        initCtxMenu();
        
        // init delayed tasks
        this.updateMessagesTask = new Ext.util.DelayedTask(this.updateMessages, this);
        this.updateFoldersTask = new Ext.util.DelayedTask(this.updateFolders, this);
        
        // call parent::initComponent
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);

    	// add handlers
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
        this.on('append', this.onAppend, this);
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
    
    /********************* event handler ******************/
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.TreePanel.superclass.afterRender.call(this);

        var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
        this.expandPath('/root/' + defaultAccount + '/');
        
        // start delayed tasks
        /*
        if (this.updateMessagesTask !== null) {
            this.updateMessagesTask.delay(this.updateMessageRefreshTime);
        }
        */
        if (this.updateFoldersTask !== null) {
            this.updateFoldersTask.delay(this.updateFolderRefreshTime);
        }
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
            this.filterPlugin.onFilterChange();
            
            this.updateFolderStatus([node]);
            //this.updateMessageCache();
            if (this.updateMessagesTask !== null) {
                this.setMessageRefresh('fast');
                this.updateMessagesTask.delay(this.updateMessageRefreshTime);
            }
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
     * 
     * TODO allow moving messages to another account
     */
    onBeforenodedrop: function(dropEvent) {
        
        // check if node has the same account_id like the active node
        var selectedNode = this.getSelectionModel().getSelectedNode();
        if (! dropEvent.target.attributes.account_id || ! selectedNode || dropEvent.target.attributes.account_id != selectedNode.attributes.account_id) {
            Ext.Msg.alert(this.app.i18n._('Invalid drop target'), this.app.i18n._('Could not move message(s).'));
            return false;
        }
        
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
                ids: ids
            },
            scope: this,
            success: function(_result, _request){
                // update folder status of both folders
                this.updateFolderStatus([dropEvent.target, selectedNode]);
            }
        });
        
        return true;
    },
    
    /**
     * on append node
     * 
     * @param {} tree
     * @param {} node
     * @param {} appendedNode
     * @param {} index
     */
    onAppend: function(tree, node, appendedNode, index) {
        if (Ext.util.Format.lowercase(appendedNode.attributes.localname) == 'inbox') {
            appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
                appendedNode.fireEvent('click', appendedNode);
            }, appendedNode.ui);
            // TODO update folder status of inbox ?
            //this.updateFolderStatus([appendedNode]);
        }
    },
    
    /**
     * folder store gets updated -> update grid/tree and show notifications
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     * 
     */
    onUpdateFolderStore: function(store, record, operation) {
        
        var changes = record.getChanges();
        //console.log(changes);

        // cache count changed
        if (record.isModified('cache_totalcount')) {
            var selectedNode = this.getSelectionModel().getSelectedNode();
            
            // check if grid has to be updated
            if (selectedNode.id == record.id) {
                //console.log('update grid');
                this.filterPlugin.onFilterChange();
            }
        }
            
        if (record.isModified('cache_unreadcount') && changes.cache_unreadcount > 0) {
            //console.log('update unread');
            this.updateUnreadCount(null, changes.cache_unreadcount, selectedNode);
        }
        
        if (record.isModified('cache_recentcount') && changes.cache_recentcount > 0) {
            //console.log('show notification');
            Ext.ux.Notification.show(
                this.app.i18n._('New mails'), 
                String.format(this.app.i18n._('You got {0} new mail(s) in Folder {1}.'), 
                    changes.cache_recentcount, record.get('localname'))
            );                
        }
            
        // update pie / progress
        if (record.isModified('cache_status') || record.isModified('cache_job_actions_done')) {
            //console.log('update progress');
            this.updateCachingProgress(record);
        }

        // silent commit
        record.commit(true);
    },
    
    /********************* cache control functions ******************/
    
    /**
     * delayed task function / messages
     * - calls updateMessageCache
     */
    updateMessages: function() {
        var refreshMode = (this.updateMessageCache()) ? 'slow' : 'fast';
        this.setMessageRefresh(refreshMode);
        //console.log('start mc task with delay ' + this.updateMessageRefreshTime)
        
        this.updateMessagesTask.delay(this.updateMessageRefreshTime);
    },
    
    /**
     * delayed task function / folders
     * - calls updateFolderStatus
     * 
     * TODO start delayed message update task here?
     */
    updateFolders: function() {
        this.updateFolderStatus();
        /*
        if (this.updateMessagesTask !== null) {
            this.setMessageRefresh('fast');
            this.updateMessagesTask.delay(this.updateMessageRefreshTime);
        }
        */
        this.updateFoldersTask.delay(this.updateFolderRefreshTime);
    },

    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Array} nodes array of Ext.tree.AsyncTreeNode
     * 
     * TODO abort request if another folder has been clicked
     * TODO move request to record proxy
     */
    updateFolderStatus: function(nodes) {
        
        var folderIds = [];
        var account = this.getActiveAccount();

        if (! nodes || nodes.length == 0) {
            //console.log('update multi');
            // get all folders of active account in store
            folderIds = this.getFoldersForUpdateStatus(account.id);
            if (folderIds.length == 0) {
                return;
            }
        } else if (nodes[0].attributes) {
            //console.log('update single');
            for (var i=0; i < nodes.length; i++) {
                folderIds.push(nodes[i].attributes.folder_id);
            }
        } else {
            return;
        }
        
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.updateFolderStatus',
                folderIds: folderIds,
                accountId: account.id
            },
            scope: this,
            timeout: 60000, // 1 minute
            success: function(_result, _request) {
                var folderData = Ext.util.JSON.decode(_result.responseText);
                for (var i = 0; i < folderData.length; i++) {
                    this.folderStore.loadData(folderData[i], true);
                    
                    // update message cache of selected folder/node
                    var selectedNode = this.getSelectionModel().getSelectedNode();
                    if (selectedNode.id == folderData[i].id) {
                        this.updateMessageCache(selectedNode);
                    }
                }
            },
            failure: function() {
                // do nothing
            }
        });
    },

    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {} node
     * @return boolean true if caching is complete
     */
    updateMessageCache: function(node) {
        
        /////////// select folder to update message cache for
        
        var folderId = null;
        var singleFolderUpdate = false;
        
        // get active node
        if (! node) {
            node = this.getSelectionModel().getSelectedNode();
        } else {
            //console.log('update single mc');
            singleFolderUpdate = true;
        }
        var folder = this.folderStore.getById(node.id);
        
        if (folder && (folder.get('cache_status') == 'incomplete' || folder.get('cache_status') == 'invalid')) {
            folderId = folder.id;
            
        } else if (! singleFolderUpdate) {
            folderId = this.getNextFolderToUpdate();
            if (folderId === null) {
                // nothing left to do for the moment! -> set refresh rate to 'slow'
                //console.log('finished for the moment');
                return true;
            }
        }
        
        if (folderId !== null) {
            /////////// do request
            
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateMessageCache',
                    folderId: node.attributes.folder_id,
                    time: 10
                },
                scope: this,
                success: function(_result, _request) {
                    this.folderStore.loadData(Ext.util.JSON.decode(_result.responseText), true);
                },
                failure: function(response, options) {
                    // call handle failure in tree loader and show credentials dialog / reload account afterwards
                    if (node.parentNode) {
                        this.loader.handleFailure(response, options, node.parentNode, false);
                    }
                }
            });           
        }
        return false;
    },
    
    /********************* helpers *****************************/

    /**
     * update progress pie
     * 
     * @param {} folder
     * 
     * TODO show if disconnected
     * TODO make css style work for class felamimail-node-progress 
     * TODO show initial incomplete status? 
     * TODO show pie progress?
     */
    updateCachingProgress: function(folder) {
        
        // get node ui
        var node = this.getNodeById(folder.id);
        if (! node) {
            return;
        }
        var nodeUI = node.getUI();
        
        // insert caching progress element
        //if (folder.get('cache_status') == 'complete' || folder.get('cache_job_actions_estimate') == 0) {
        if (folder.get('cache_status') == 'complete') {
            //var html = folder.get('cache_totalcount');
            var html = '';
        } else if (folder.get('cache_job_actions_estimate') == 0) {
            var html = '<i>0 %</i>';
        } else {
            var number = folder.get('cache_job_actions_done') / folder.get('cache_job_actions_estimate') * 100;
            var html = '<i>' + number.toFixed(0) + ' %</i>';
        }
        
        var domEl = {tag: 'span', html: html, cls: 'felamimail-node-progress'};

        var progressEl = Ext.DomQuery.select('span[class=felamimail-node-progress]', nodeUI.getEl());
        if (progressEl[0]) {
            Ext.DomHelper.overwrite(progressEl[0], html);
        } else {
            Ext.DomHelper.insertAfter(nodeUI.getTextEl(), domEl);
        }
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
     * set this.updateMessageRefreshTime
     * @param {} mode fast|slow
     */
    setMessageRefresh: function(mode) {
        if (mode == 'slow') {
            // get folder update interval from preferences
            var updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval'));
            if (updateInterval > 0) {
                // convert to milliseconds
                this.updateMessageRefreshTime = 60000*updateInterval;
            } else {
                // TODO what shall we de if pref is set to 0?
                this.updateMessageRefreshTime = 1200000; // 20 minutes
            }
        } else {
            this.updateMessageRefreshTime = 20000; // 20 seconds
        }
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
                    if (node.attributes.intelligent_folders == 1) {
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
     * get all folders to update of account in store
     * 
     * @param {String} accountId
     * 
     * TODO make date comparison work (record.get('cache_timestamp') should return a date object!
     */
    getFoldersForUpdateStatus: function(accountId) {
        var result = [];

        //console.log('# records: ' + this.folderStore.getCount());
        //console.log(this.folderStore);
        var accountFolders = this.folderStore.queryBy(function(record) {
            var timestamp = record.get('imap_timestamp');
            return (record.get('account_id') == accountId && timestamp.getElapsed() > 300000); // 5 minutes
        });
        //console.log(accountFolders);
        accountFolders.each(function(record) {
            result.push(record.id);
        });
        
        return result;
    },
    
    /**
     * get next folder for update message cache
     * 
     * @return {String|null}
     */
    getNextFolderToUpdate: function() {
        var result = null;
        
        var account = this.getActiveAccount();
        // look for folder to update
        //console.log(account.id);
        var candidates = this.folderStore.queryBy(function(record) {
            //console.log(record);
            //console.log(record.id + ' ' + record.get('cache_status'));
            return (
                record.get('account_id') == account.id 
                && (record.get('cache_status') == 'incomplete' || record.get('cache_status') == 'invalid')
            );
        });
        //console.log(candidates);
        if (candidates.getCount() > 0) {
            folder = candidates.first();
            result = folder.id;
        }
        
        return result;
    }
});
