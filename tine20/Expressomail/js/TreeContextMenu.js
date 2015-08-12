/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * get expressomail tree panel context menus
 * this is used in Tine.Expressomail.TreePanel (with createDelegate)
 *
 * TODO use Ext.apply to get this
 */
Tine.Expressomail.setTreeContextMenus = function() {

    // define additional actions
    var emptyFolderAction = {
        text: this.app.i18n._('Empty Folder'),
        iconCls: 'action_folder_emptytrash',
        scope: this,
        handler: function() {
            this.ctxNode.getUI().addClass("x-tree-node-loading");
            var folderId = this.ctxNode.attributes.folder_id,
                selectedNode = this.getSelectionModel().getSelectedNode(),
                isSelectedNode = (selectedNode && this.ctxNode.id == selectedNode.id);
            
            if(isSelectedNode){
                this.app.getMainScreen().getCenterPanel().grid.getSelectionModel().clearSelections();
                this.app.getMainScreen().getCenterPanel().getStore().removeAll();
            }
            Ext.Ajax.request({
                params: {
                    method: 'Expressomail.emptyFolder',
                    folderId: folderId
                },
                scope: this,
                success: function(result, request){
                    var selectedNode = this.getSelectionModel().getSelectedNode(),
                        isSelectedNode = (selectedNode && this.ctxNode.id == selectedNode.id),
                        account = this.app.getActiveAccount(),
                        dRegexp = new RegExp('\\'+account.get('delimiter')+'$'),
                        inboxName = account.get('ns_personal').replace(dRegexp, '').toUpperCase(),
                        folderStore = this.app.getFolderStore(),
                        inboxRecord = folderStore.getAt(folderStore.findExact('globalname', inboxName));

                    if (isSelectedNode) {
                        var folder = Tine.Expressomail.folderBackend.recordReader(result);
                        folder.set('cache_unreadcount', 0);
                        folder.set('cache_totalcount', 0);
                        this.app.getFolderStore().updateFolder(folder);
                    } else {
                        var folder = folderStore.getById(folderId);
                        folder.set('cache_unreadcount', 0);
                        folder.set('cache_totalcount', 0);
                    }
                    this.ctxNode.getUI().removeClass("x-tree-node-loading");
                    this.ctxNode.removeAll();

                    inboxRecord.set('cache_status', 'incomplete');
                    this.app.checkMailsDelayedTask.delay(2000);
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
                        method: 'Expressomail.addFolder',
                        name: _text
                    };

                    params.parent = '';
                    params.accountId = parentNode.id;

                    Ext.Ajax.request({
                        params: params,
                        scope: this,
                        timeout: 150000, // 2 minutes
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
        iconCls: 'ExpressomailIconCls',
        scope: this,
        disabled: ! Tine.Tinebase.common.hasRight('manage_accounts', 'Expressomail'),
        handler: function() {
            var record = this.accountStore.getById(this.ctxNode.attributes.account_id);
            var popupWindow = Tine.Expressomail.AccountEditDialog.openWindow({
                record: record,
                listeners: {
                    scope: this,
                    'update': function(record) {
                        var account = new Tine.Expressomail.Model.Account(Ext.util.JSON.decode(record));

                        // update tree node + store
                        this.ctxNode.setText(account.get('name'));
                        this.accountStore.reload();

                        // reload tree node + remove all folders of this account from store ?
                        this.folderStore.resetQueryAndRemoveRecords('parent_path', '/' + this.ctxNode.attributes.account_id);
                        this.ctxNode.reload(function(callback) {
                            this.selectInbox(account);
                        }, this);
                    }
                }
            });
        }
    };

    var editVacationAction = {
        text: this.app.i18n._('Edit Vacation Message'),
        iconCls: 'action_email_replyAll',
        scope: this,
        handler: function() {
            var accountId = this.ctxNode.attributes.account_id;
            var account = this.accountStore.getById(accountId);
            var record = new Tine.Expressomail.Model.Vacation({id: accountId}, accountId);

            var popupWindow = Tine.Expressomail.sieve.VacationEditDialog.openWindow({
                account: account,
                record: record
            });
        }
    };

    var editRulesAction = {
        text: this.app.i18n._('Edit Filter Rules'),
        iconCls: 'action_email_forward',
        scope: this,
        handler: function() {
            var accountId = this.ctxNode.attributes.account_id;
            var account = this.accountStore.getById(accountId);

            var popupWindow = Tine.Expressomail.sieve.RulesDialog.openWindow({
                account: account
            });
        }
    };

    var manageAclsAction = {
        text: this.app.i18n._('Share mailbox'),
        iconCls: 'action_managePermissions',
        scope: this,
        handler:function() {
                if (this.ctxNode) {
                    var node = this.ctxNode;
                    var folderId = this.ctxNode.attributes.globalname;
                    var account = this.ctxNode.attributes.account_id;
                    var window = Tine.Expressomail.AclsEditDialog.openWindow({
                        title: String.format(this.app.i18n._('Share mailbox')),
                        accountId: account,
                        // Using 'INBOX', can use folderId
                        globalName: 'INBOX'
                    });
                }
            }
    };
   var manageEmlImportAction = {
        text: this.app.i18n._('Import msg(eml)'),
        iconCls: 'action_import',
        scope: this,
        handler:function() {
                if (this.ctxNode) {
                    var window = Tine.Expressomail.ImportEmlDialog.openWindow({
                        title: String.format(_(this.ctxNode.attributes.globalname)),
                        account: this.ctxNode.attributes.account_id,
                        textName: this.ctxNode.text,
                        folderId: this.ctxNode.attributes.folder_id,
                        // Using 'INBOX', can use folderId
                        globalName: this.ctxNode.attributes.globalname
                    });
                }
            }
    };
    
    var deleteFolderChildAction = {
        text: this.app.i18n._('Delete Folder'),
        iconCls: 'action_delete',
        scope: this,
        handler:function() {
             if (this.ctxNode) {
                Ext.Msg.alert(this.app.i18n._('Warning'), this.app.i18n._('Delete your sub-folders first.'));
            }
        }
    };
    
    

    var markFolderSeenAction = {
        text: this.app.i18n._('Mark Folder as read'),
        iconCls: 'action_mark_read',
        scope: this,
        handler: function() {
            if (this.ctxNode) {
                var folderId = this.ctxNode.id,
                    filter = [{
                        field: 'folder_id',
                        operator: 'equals',
                        value: folderId
                    }, {
                        field: 'flags',
                        operator: 'notin',
                        value: ['\\Seen']
                    }
                ];

                var selectedNode = this.getSelectionModel().getSelectedNode(),
                    isSelectedNode = (selectedNode && this.ctxNode.id == selectedNode.id);

                Tine.Expressomail.messageBackend.addFlags(filter, '\\Seen', {
                    callback: function() {
                        this.app = Tine.Tinebase.appMgr.get('Expressomail');
                        var folder = this.app.getFolderStore().getById(folderId);
                        folder.set('cache_unreadcount', 0);
                        if (isSelectedNode) {
                            this.app.getMainScreen().getCenterPanel().loadGridData({
                                removeStrategy: 'keepBuffered'
                            });
                        }
                    }
                });
            }
        }
    };

    var updateFolderCacheAction = {
        text: this.app.i18n._('Update Folder List'),
        iconCls: 'action_update_cache',
        scope: this,
        handler: function() {
            if (this.ctxNode) {
                this.getSelectionModel().clearSelections();

                var folder = this.app.getFolderStore().getById(this.ctxNode.id),
                    account = folder ? this.app.getAccountStore().getById(folder.get('account_id')) :
                                       this.app.getAccountStore().getById(this.ctxNode.id);
                this.ctxNode.getUI().addClass("x-tree-node-loading");
                // call update folder cache
                Ext.Ajax.request({
                    params: {
                        method: 'Expressomail.updateFolderCache',
                        accountId: account.id,
                        folderName: folder ? folder.get('globalname') : ''
                    },
                    scope: this,
                    success: function(result, request){
                        this.ctxNode.getUI().removeClass("x-tree-node-loading");
                        // clear query to query server again and reload subfolders
                        this.folderStore.resetQueryAndRemoveRecords('parent_path', (folder ? folder.get('path') : '/') + account.id);
                        this.ctxNode.reload(function(callback) {
                            this.selectInbox(account);
                        }, this);
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
        nodeName: _('Folder'),
        scope: this,
        backend: 'Expressomail',
        backendModel: 'Folder'
    };

    // system folder ctx menu
    config.actions = [markFolderSeenAction, 'add',manageEmlImportAction];
    this.contextMenuSystemFolder = Tine.widgets.tree.ContextMenu.getMenu(config);

    // user folder ctx menu
    config.actions = [markFolderSeenAction, 'add', 'rename', 'delete',manageEmlImportAction];
    this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu(config);

    // trash ctx menu
    config.actions = [markFolderSeenAction, 'add', emptyFolderAction];
    this.contextMenuTrash = Tine.widgets.tree.ContextMenu.getMenu(config);

    // account ctx menu
    this.contextMenuAccount = Tine.widgets.tree.ContextMenu.getMenu({
        nodeName: this.app.i18n.n_('Account', 'Accounts', 1),
        actions: [addFolderToRootAction, manageAclsAction, updateFolderCacheAction, editVacationAction, editRulesAction, editAccountAction],
        scope: this,
        backend: 'Expressomail',
        backendModel: 'Account'
    });
    
    config.actions = [markFolderSeenAction, 'add', 'rename', deleteFolderChildAction , manageEmlImportAction];
    this.contextMenuUserFolderChildren = Tine.widgets.tree.ContextMenu.getMenu(config);

    // context menu for unselectable folders (like public/shared namespace)
    config.actions = ['add'];
    this.unselectableFolder = Tine.widgets.tree.ContextMenu.getMenu(config);
};
