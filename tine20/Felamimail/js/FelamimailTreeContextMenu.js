/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * get felamimail tree panel context menus
 * this is used in Tine.Felamimail.TreePanel (with createDelegate)
 * 
 * TODO update other actions again?
 * TODO use Ext.apply to get this
 */
Tine.Felamimail.setTreeContextMenus = function() {
        
    // define additional actions
    
    // inactive
    /*
    var updateCacheConfigAction = {
        text: String.format(_('Update {0} Cache'), this.app.i18n._('Message')),
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
                        // update message cache
                        //this.updateFolderStatus([this.ctxNode]);
                    }
                }
            });
        }
    };
    */

    var emptyFolderAction = {
        text: this.app.i18n._('Empty Folder'),
        iconCls: 'action_folder_emptytrash',
        scope: this,
        handler: function() {
            this.ctxNode.getUI().addClass("x-tree-node-loading");
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.emptyFolder',
                    folderId: this.ctxNode.attributes.folder_id
                },
                scope: this,
                success: function(result, request){
                    if (this.ctxNode.id == this.getSelectionModel().getSelectedNode().id) {
                        var newRecord = Tine.Felamimail.folderBackend.recordReader(result);
                        this.app.updateFolderInStore(newRecord);
                    }
                    this.ctxNode.getUI().removeClass("x-tree-node-loading");
                },
                failure: function() {
                    this.ctxNode.getUI().removeClass("x-tree-node-loading");
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
                            this.fireEvent('containeradd', nodeData);
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
                        
                        // reload tree node + remove all folders of this account from store ?
                        this.folderStore.resetQueryAndRemoveRecords('parent_path', '/' + this.ctxNode.attributes.account_id);
                        this.ctxNode.reload(function(callback) {
                        });
                    }
                }
            });
        }
    };
    
    // inactive
    /*
    var reloadFolderAction = {
        text: String.format(_('Reload {0}'), this.app.i18n._('Folder')),
        iconCls: 'x-tbar-loading',
        scope: this,
        handler: function() {
            if (this.ctxNode) {
                // call update folder status from felamimail app
                //this.updateFolderStatus([this.ctxNode]);
            }
        }
    };
    */

    var reloadFolderCacheAction = {
        text: String.format(_('Update {0} Cache'), this.app.i18n._('Folder')),
        iconCls: 'action_update_cache',
        scope: this,
        handler: function() {
            if (this.ctxNode) {
                this.ctxNode.getUI().addClass("x-tree-node-loading");
                // call update folder cache
                Ext.Ajax.request({
                    params: {
                        method: 'Felamimail.updateFolderCache',
                        accountId: this.ctxNode.attributes.account_id,
                        folderName: this.ctxNode.attributes.globalname
                    },
                    scope: this,
                    success: function(result, request){
                        this.ctxNode.getUI().removeClass("x-tree-node-loading");
                        // clear query to query server again and reload subfolders
                        var parentFolder = this.folderStore.getById(this.ctxNode.id);
                        this.folderStore.resetQueryAndRemoveRecords('parent_path', (parentFolder) ? parentFolder.get('path') : '/' + this.ctxNode.attributes.account_id);
                        this.ctxNode.reload(function(callback) {
                        });
                    },
                    failure: function() {
                        this.ctxNode.getUI().removeClass("x-tree-node-loading");
                    }
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

    config.actions = ['add', reloadFolderCacheAction /*, updateCacheConfigAction, reloadFolderAction*/];
    this.contextMenuSystemFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
    
    // user folder ctx menu

    config.actions = ['add', 'rename', reloadFolderCacheAction, /*updateCacheConfigAction, reloadFolderAction, */'delete'];
    this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
    
    // trash ctx menu
    
    config.actions = ['add', emptyFolderAction, reloadFolderCacheAction /*, reloadFolderAction*/];
    this.contextMenuTrash = Tine.widgets.tree.ContextMenu.getMenu(config);
    
    // account ctx menu
    
    this.contextMenuAccount = Tine.widgets.tree.ContextMenu.getMenu({
        nodeName: this.app.i18n._('Account'),
        actions: [editAccountAction, addFolderToRootAction, 'reload', 'delete'],
        scope: this,
        backend: 'Felamimail',
        backendModel: 'Account'
    });        
};
