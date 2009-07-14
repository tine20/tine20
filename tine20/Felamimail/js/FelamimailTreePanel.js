/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         add unread count to intelligent folders?
 * TODO         reload folder status (and number of unread messages) every x minutes 
 *              -> via ping or ext.util.delayedtask ?
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
    border: false,
	
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
        this.initContextMenus();
        
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
            cls: 'felamimail-node-account',
            show_intelligent_folders: (record.get('show_intelligent_folders')) ? record.get('show_intelligent_folders') : 0,
            delimiter: record.get('delimiter'),
            ns_personal: record.get('ns_personal'),
            account_id: record.data.id,
            listeners: {
                scope: this,
                load: function(node) {
                    
                    // add 'intelligent' folders
                    if (node.attributes.show_intelligent_folders == 1) {
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
     */
    initContextMenus: function() {
        
        /***************** define additional actions *****************/
        
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
                        // update grid
                        this.filterPlugin.onFilterChange();
                    }
                });
            }
        };

        var emptyFolderAction = {
            text: this.app.i18n._('Empty Folder'),
            iconCls: 'action_folder_emptytrash',
            scope: this,
            handler: function() {
                this.app.mainScreen.gridPanel.grid.loadMask.show();
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.emptyFolder',
                        folderId: this.ctxNode.attributes.folder_id
                    },
                    scope: this,
                    success: function(_result, _request){
                        // update grid
                        this.filterPlugin.onFilterChange();
                        this.updateUnreadCount(null, 0, this.ctxNode);
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
                            this.ctxNode.attributes.show_intelligent_folders = account.get('show_intelligent_folders');
                            this.accountStore.reload();
                            
                            // reload tree node
                            this.ctxNode.reload(function(callback) {
                                //console.log('reload');
                            });
                            
                            // update grid
                            this.filterPlugin.onFilterChange();
                        }
                    }
                });        
            }
        };

        /***************** mutual config options *****************/
        
        var config = {
            nodeName: this.app.i18n._('Folder'),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Folder'
        };        
        
        /***************** system folder ctx menu *****************/

        config.actions = ['add', updateCacheConfigAction, 'reload'];
        this.contextMenuSystemFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        /***************** user folder ctx menu *****************/

        config.actions = ['add', 'rename', updateCacheConfigAction, 'reload', 'delete'];
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        /***************** trash ctx menu *****************/
        
        config.actions = ['add', emptyFolderAction, 'reload'];
        this.contextMenuTrash = Tine.widgets.tree.ContextMenu.getMenu(config);
        
        /***************** account ctx menu *****************/
        
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
                }
            });
        }
        
        return this.filterPlugin;
    },
    
    /**
     * update unread count
     * 
     * @param {} change
     * @param {} unreadcount [optional]
     * @param {} node [optional]
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
                if (node.attributes.unreadcount == 1 && change == 1) {
                    // 0 -> 1
                    node.getUI().addClass('felamimail-node-unread');
                }
            } else {
                node.setText(node.attributes.localname);
                node.getUI().removeClass('felamimail-node-unread');
            }
        }
    },
    
    /**
     * update folder status of all visible (?) folders
     * 
     * TODO make this work for multiple accounts
     * TODO make recursive work for delayed task or ping update
     * 
     * @param {} recursive
     * @param {} node [optional]
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
                    //console.log(folderData);
                    this.updateUnreadCount(null, folderData[0].unreadcount, node);
                }
            });
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
        
        if (node.expandable) {
            //console.log('expandable');
            node.expand();
        }
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
     */
    createNode: function(attr) {
        
        var account = Tine.Felamimail.loadAccountStore().getById(attr.account_id);
        
        //console.log(attr);
        
        // check for account setting
        attr.has_children = (
            account 
            && account.get('has_children_support') 
            && account.get('has_children_support') == '1'
        ) ? attr.has_children : true;
        
        var qtiptext = this.app.i18n._('Totalcount') + ': ' + attr.totalcount 
            + ' / ' + this.app.i18n._('Cache') + ': ' + attr.cache_status;

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
            qtip: qtiptext,
            systemFolder: (attr.system_folder == '1'),
            unreadcount: attr.unreadcount,
            
            // if it has no children, it shouldn't have an expand icon 
            expandable: attr.has_children,
            expanded: ! attr.has_children
    	};
        
        // if it has no children, it shouldn't have an expand icon 
        if (! attr.has_children) {
            node.children = [];
            node.cls = 'x-tree-node-collapsed';
        }

        // show trash icon for trash folder of account
        if (account) {
            if (account.get('trash_folder') == attr.globalname) {
                node.cls = 'felamimail-node-trash';
            }
        }

        if (attr.unreadcount > 0) {
            node.text = node.text + ' (' + attr.unreadcount + ')';
            node.cls = node.cls + ' felamimail-node-unread'; // x-tree-node-collapsed';
        }
        
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    },
    
    /**
     * request failed
     * 
     * @param {} response
     * @param {} request
     */
    onRequestFailed: function(response, request) {
        var responseText = Ext.util.JSON.decode(response.responseText);

        if (responseText.msg == 'cannot login, user or password wrong' ||
            responseText.msg == 'need at least user in params') {
            
            // get account id and update username/password
            var accountNode = request.argument.node;
            var accountId = accountNode.attributes.account_id;
                
            var credentialsWindow = Tine.widgets.dialog.CredentialsDialog.openWindow({
                title: String.format(this.app.i18n._('IMAP Credentials for {0}'), accountNode.text),
                appName: 'Felamimail',
                credentialsId: accountId,
                i18nRecordName: this.app.i18n._('Credentials'),
                listeners: {
                    scope: this,
                    'update': function(data) {
                        // update account node
                        accountNode.reload(function(callback) {
                            //console.log('reload');
                        });
                    }
                }
            });

        } else {

            // open standard exception dialog
            if (! Tine.Tinebase.exceptionDlg) {
                Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                    height: 300,
                    exceptionInfo: responseText,
                    listeners: {
                        close: function() {
                            Tine.Tinebase.exceptionDlg = null;
                        }
                    }
                });
                Tine.Tinebase.exceptionDlg.show();
            }
            
            /*
            Ext.MessageBox.alert(
                this.app.i18n._('Failed to connect'), 
                this.app.i18n._('Could not connect to account.') 
                    + ' (' + this.app.i18n._('Error:') + ' ' + responseText.msg + ')'
            );
            */ 
        }
    }
});
