/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * TODO         register tree on folder store (on update)
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
        
        // TODO register with folder store (onUpdate)
        // TODO unregister from folder store (on destroy)
        this.folderStore = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore(); 
    	
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
        // TODO use Ext.apply
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
        //this.expandPath('/root/' + defaultAccount + '/');
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
        
        if (node.id && node.id != '/' && node.attributes.globalname != '') {
            this.filterPlugin.onFilterChange();
            
            // TOOD updateFolderStatus!
            
            /*
            this.updateFolderStatus([node]);
            //this.updateMessageCache();
            if (this.updateMessagesTask !== null) {
                this.setMessageRefresh('fast');
                this.updateMessagesTask.delay(this.updateMessageRefreshTime);
            }
            */
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
            
            if (account && node.attributes.globalname == account.get('trash_folder') || node.attributes.globalname.match(/junk/i)) {
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
                // TODO return folder status of both folders here
                // update folder status of both folders
                //this.updateFolderStatus([dropEvent.target, selectedNode]);
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
        }
    },
    
    /**
     * folder store gets updated -> update grid/tree and show notifications
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        
        var changes = record.getChanges();
        //console.log(changes);

        // cache count changed
        if (record.isModified('cache_totalcount') || record.isModified('cache_job_actions_done')) {
            var selectedNode = this.getSelectionModel().getSelectedNode();
            
            // check if grid has to be updated
            if (selectedNode.id == record.id) {
                //console.log('update grid');
                this.filterPlugin.onFilterChange();
            }
        }
            
        if (record.isModified('cache_unreadcount')) {
            //console.log('update unread');
            this.updateUnreadCount(null, changes.cache_unreadcount, selectedNode);
        }
        
        // update pie / progress
        if (record.isModified('cache_status') || record.isModified('cache_job_actions_done')) {
            //console.log('update progress');
            this.updateCachingProgress(record);
        }

        // silent commit
        //record.commit(true);
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
     * TODO show totalcount?
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
            //var html = '<i> / ' + folder.get('cache_totalcount') + '</i>';;
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
     * add account record to root node
     * 
     * @param {Tine.Felamimail.Model.Account} record
     */
    addAccount: function(record) {
        
        var node = new Ext.tree.AsyncTreeNode({
            id: record.data.id,
            path: '/' + record.data.id,
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
    }
});
