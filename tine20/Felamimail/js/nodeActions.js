/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Felamimail.nodeActions');


/**
 * @singleton
 */
Tine.Felamimail.nodeActionsMgr = new (Ext.extend(Tine.widgets.ActionManager, {
    actionConfigs: Tine.Felamimail.nodeActions,
    
    /**
     * generic action needs to set custom actionUpdater
     */
    getInitialConfig() {
        return {
            app : Tine.Tinebase.appMgr.get('Felamimail'),
            actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
        }
    }
}))();

Tine.Felamimail.nodeActions.accountActions = [
    'AddFolderAction',
    'UpdateFolderCacheAction',
    'EditVacationAction',
    'EditRulesAction',
    'EditNotificationAction',
    'EditAccountAction',
    'ApproveMigrationAction',
    'delete'
];

Tine.Felamimail.nodeActions.folderActions = [
    'MarkFolderSeenAction',
    'AddFolderAction',
    'RefreshFolderAction',
    'MoveFolderAction',
    'EmptyFolderAction',
    'rename',
    'delete',
];

/**
 * helper for disabled field
 *
 * @param action
 * @param grants
 * @param records
 * @param isFilterSelect
 * @param filteredContainers
 * @returns {*}
 */
Tine.Felamimail.nodeActions.actionUpdater = function(action, grants, records, isFilterSelect, filteredContainers) {
    // run default updater
    Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, grants, records, isFilterSelect);
    action.setDisabled(false);
    
    const node = records[0];
    const record = new Tine.Felamimail.Model.Account(node.attributes);
    const app = action.app;
    const folder = app.getFolderStore().getById(record.id);
    const account = folder ? app.accountStore.getById(folder.get('account_id')) :
        app.accountStore.getById(record.id);
    
    // action should always have account record
    action.account = account;
    action.folder = folder;
    action.node = node;
    
    const accountActions = Tine.Felamimail.nodeActions.accountActions;
    const folderActions = Tine.Felamimail.nodeActions.folderActions;

    const defaultHidden = !folder ? !accountActions.includes(action.itemId) : !folderActions.includes(action.itemId);
    action.baseAction.setHidden(defaultHidden);
    
    if (!folder) {
        if (account.get('ns_personal') !== 'default') {
            switch (action.itemId) {
                case 'EditAccountAction':
                case 'UpdateFolderCacheAction':
                    break;
                case 'AddFolderAction':
                    // check account personal namespace -> disable 'add folder' if namespace is other than root
                    action.setDisabled(account.get('ns_personal') !== '');
                    break;
                case 'EditVacationAction':
                case 'EditRulesAction':
                    // disable filter rules/vacation if no sieve hostname is set
                    action.setDisabled(account.get('sieve_hostname') == null || account.get('sieve_hostname') === '');
                    break;
                case 'EditNotificationAction':
                    if (account && account.get('type') !== 'system' && account.get('type') !== 'shared') {
                        action.baseAction.setHidden(true);
                    }
                    break;
                case 'ApproveMigrationAction':
                    action.setDisabled(
                        account.get('migration_approved') === 1
                        || account.get('type') === 'user'
                        || account.get('type') === 'shared'
                    );
    
                    if (!Tine.Tinebase.registry.get('manageImapEmailUser')
                        || !Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('accountMigration')
                    ) {
                        action.baseAction.setHidden(true);
                    }
                    break;
                case 'delete':
                    action.setDisabled(account.get('type') === 'system' || account.get('type') === 'shared');
                    break;
            }
        }
    } else {
        const isTrashFolder = (folder.get('globalname') === account.get('trash_folder') || folder.get('localname').match(/^junk$/i)) ?? false;
        const isFolderSelectable = folder.get('is_selectable');
        const isSystemFolder = folder.get('system_folder');
        
        action.baseAction.setHidden(!folderActions.includes(action.itemId) && !isFolderSelectable);
        
        switch (action.itemId) {
            case 'MarkFolderSeenAction':
            case 'AddFolderAction':
            case 'RefreshFolderAction':
                break;
            case 'EmptyFolderAction':
                action.baseAction.setHidden(!isTrashFolder);
                break;
            case 'MoveFolderAction':
            case 'rename':
            case 'delete':
                action.baseAction.setHidden(isSystemFolder || isTrashFolder);
                break;
        }
    }
};

/**
 * Empty Folder
 */
Tine.Felamimail.nodeActions.EmptyFolderAction = {
    app: 'Felamimail',
    text: 'Empty Folder',
    itemId: 'EmptyFolderAction',
    iconCls: 'action_folder_emptytrash',
    scope: this,
    handler: async function (action) {
        const app = action.app;
        const folder = action.folder;
        const selectedNode = action.node;
        
        try {
            if (selectedNode) {
                selectedNode.getUI().addClass("x-tree-node-loading");
                const result = await Tine.Felamimail.emptyFolder(folder.id);
                const folderRecord = Tine.Felamimail.folderBackend.recordReader({responseText: result});
                app.getFolderStore().updateFolder(folderRecord);
                selectedNode.removeAll();
                selectedNode.getUI().removeClass("x-tree-node-loading");
            } else {
                folder.set('cache_unreadcount', 0);
            }
        } catch (e) {
            Tine.Felamimail.folderBackend.handleRequestException(e);
        }
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

// we need this for adding folders to account (root level)
Tine.Felamimail.nodeActions.AddFolderAction = {
    app: 'Felamimail',
    text: 'Add Folder',
    itemId: 'AddFolderAction',
    iconCls: 'action_add',
    scope: this,
    handler: function(action) {
        if (!action.node) return;
        const app = action.app;
        const account = action.account;
        const selectedNode = action.node;
        const treePanel = selectedNode.ownerTree;
        
        Ext.MessageBox.prompt(String.format(i18n._('New {0}'), app.i18n._('Folder')), String.format(i18n._('Please enter the name of the new {0}:'), app.i18n._('Folder')), async (btn, folderName) => {
            if(btn === 'ok') {
                if (! folderName) {
                    return Ext.Msg.alert(String.format(i18n._('No {0} added'), app.i18n._('Folder')), String.format(i18n._('You have to supply a {0} name!'), app.i18n._('Folder')));
                }
                Ext.MessageBox.wait(i18n._('Please wait'), String.format(i18n._('Creating {0}...' ), app.i18n._('Folder')));
                if (treePanel) {
                    treePanel.fireEvent('containeradd', await Tine.Felamimail.addFolder(folderName, selectedNode.attributes.globalname, account.id), selectedNode);
                }
                Ext.MessageBox.hide();
            }
        }, this);
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.EditAccountAction = {
    app: 'Felamimail',
    text: 'Edit Account',
    itemId: 'EditAccountAction',
    iconCls: 'FelamimailIconCls',
    scope: this,
    disabled: false,
    handler: function(action) {
        if (!action.node) return;
        const app = action.app;
        const account = action.account;
        const selectedNode = action.node;
        const treePanel = selectedNode.ownerTree;
        
        const popupWindow = Tine.Felamimail.AccountEditDialog.openWindow({
            record: account,
            listeners: {
                'update': _.bind(function(record) {
                    const account = new Tine.Felamimail.Model.Account(Ext.util.JSON.decode(record));
                    // update tree node + store
                    selectedNode.setText(account.get('name'));
                    
                    // reload tree node + remove all folders of this account from store ?
                    app.folderStore.resetQueryAndRemoveRecords('parent_path', '/' + account.id);
                    selectedNode.reload(_.bind(function(callback) {
                        if (treePanel) {
                            const nodeToSelect = treePanel.getNodeById(_.get(selectedNode, 'id'), '');
                            if (nodeToSelect) {
                                treePanel.getSelectionModel().select(nodeToSelect);
                            }
                        }
                    }, this));
                }, this)
            }
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.EditVacationAction = {
    app: 'Felamimail',
    text: 'Edit Vacation Message',
    itemId: 'EditVacationAction',
    iconCls: 'action_email_replyAll',
    scope: this,
    handler: function(action) {
        const popupWindow = Tine.Felamimail.sieve.VacationEditDialog.openWindow({
            account: action.account,
            record: new Tine.Felamimail.Model.Vacation({id: action.account.id}, action.account.id)
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.EditRulesAction = {
    app: 'Felamimail',
    text: 'Edit Filter Rules',
    itemId: 'EditRulesAction',
    iconCls: 'action_email_forward',
    scope: this,
    handler: function(action) {
        const popupWindow = Tine.Felamimail.sieve.RulesDialog.openWindow({
            account: action.account,
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.EditNotificationAction = {
    app: 'Felamimail',
    text: 'Notifications',
    itemId: 'EditNotificationAction',
    iconCls: 'felamimail-action-sieve-notification',
    scope: this,
    handler: function(action) {
        const popupWindow = Tine.Felamimail.sieve.NotificationDialog.openWindow({
            record: action.account,
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.MarkFolderSeenAction = {
    app: 'Felamimail',
    text: 'Mark Folder as read',
    itemId: 'MarkFolderSeenAction',
    iconCls: 'action_mark_read',
    scope: this,
    handler: function(action) {
        if (!action.node) return;
        const app = action.app;
        const folder = action.folder;
        const selectedNode = action.node;
        const filter = [
            {field: 'folder_id', operator: 'equals', value: folder.id}, 
            {field: 'flags', operator: 'notin', value: ['\\Seen']}
        ];
        
        Tine.Felamimail.messageBackend.addFlags(filter, '\\Seen', {
            callback: function() {
                folder.set('cache_unreadcount', 0);
                if (selectedNode) {
                    app.getMainScreen().getCenterPanel().loadGridData({
                        removeStrategy: 'keepBuffered'
                    });
                }
            }
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.UpdateFolderCacheAction = {
    app: 'Felamimail',
    text: 'Update Folder List',
    itemId: 'UpdateFolderCacheAction',
    iconCls: 'action_update_cache',
    scope: this,
    handler: async function (action) {
        if (!action.node) return;
        const app = action.app;
        const account = action.account;
        const folder = action.folder;
        const selectedNode = action.node;
        const treePanel = selectedNode.ownerTree;
        
        try {
            treePanel.getSelectionModel().clearSelections();
            selectedNode.getUI().addClass("x-tree-node-loading");
            // call update folder cache
            const folderName = folder ? folder.get('globalname') : '';
            await Tine.Felamimail.updateFolderCache(account.id, folderName);
            
            // clear query to query server again and reload subfolders
            app.folderStore.resetQueryAndRemoveRecords('parent_path', (folder ? folder.get('path') : '/') + account.id);
            selectedNode.reload(function (callback) {
                treePanel.selectInbox(account);
            }, this);
        } catch (e) {
            Tine.Felamimail.folderBackend.handleRequestException(e);
        } finally {
            selectedNode.getUI().removeClass("x-tree-node-loading");
        }
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.ApproveMigrationAction = {
    app: 'Felamimail',
    text: 'Approve Migration',
    itemId: 'ApproveMigrationAction',
    iconCls: 'action_approve_migration',
    scope: this,
    qtip: 'Agree that this account can be transferred to other users',
    handler: async function(action) {
        if (!action.node) return;
        const app = action.app;
        const account = action.account;
        const selectedNode = action.node;
        
        if (await Ext.MessageBox.show({
            title: app.i18n._('Approve Migration'),
            msg: app.i18n._('I hereby agree that this mailbox may be converted to a shared mailbox or transferred to another user.'),
            buttons: Ext.MessageBox.YESNO,
            icon: Ext.MessageBox.QUESTION
        }) === 'yes') {
            try {
                selectedNode.getUI().addClass("x-tree-node-loading");
                await Tine.Felamimail.approveAccountMigration(account.id);
                account.set('migration_approved', 1);
            } catch (e) {
                Tine.Felamimail.folderBackend.handleRequestException(e);
            } finally {
                selectedNode.getUI().removeClass("x-tree-node-loading");
            }
        }
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.MoveFolderAction = {
    app: 'Felamimail',
    text: 'Move Folder',
    itemId: 'MoveFolderAction',
    iconCls: 'action_move',
    scope: this,
    handler: function(action) {
        if (!action.node) return;
        const app = action.app;
        const account = action.account;
        const folder = action.folder;
        const selectedNode = action.node;
        const treePanel = selectedNode.ownerTree;
        
        treePanel.getSelectionModel().clearSelections();
        const selectPanel = Tine.Felamimail.FolderSelectPanel.openWindow({
            account: account,
            listeners: {
                scope: this,
                async folderselect(newParentNode) {
                    selectPanel.close();
                    newParentNode = treePanel.getNodeById(newParentNode.id); // switch context
                    const parentGlobalname = newParentNode.attributes.globalname;

                    if (parentGlobalname.replace(new RegExp(`^${folder.get('globalname').replace('.', '\.')}`), '') !== parentGlobalname) {
                        return Ext.Msg.alert(app.i18n._('Invalid Selection'), app.i18n._('You cannot move the folder to an own sub folder!'));
                    }

                    const newGlobalName = _.compact([parentGlobalname, folder.get('localname')]).join(account.get('delimiter'));
                    
                    try {
                        selectedNode.getUI().addClass("x-tree-node-loading");
                        newParentNode.getUI().addClass("x-tree-node-loading");
                        
                        const result = await Tine.Felamimail.moveFolder(newGlobalName, folder.get('globalname'), account.id);
                        const folderStore = newParentNode.ownerTree.folderStore;
                        newParentNode.appendChild(newParentNode.ownerTree.loader.createNode(result));
                        selectedNode.remove();
                        folderStore.remove(folderStore.getById(selectedNode.id));
                        
                        const newRecord = Tine.Felamimail.folderBackend.recordReader({responseText: result});
                        folderStore.getById(newParentNode.id)?.set('has_children', true);
                        folderStore.add([newRecord]);

                        treePanel.initNewFolderNode(newRecord);
                        newParentNode.expand(false, false, () => {
                            treePanel.getNodeById(selectedNode.id).select();
                        });
                    } catch (e) {
                        Tine.Felamimail.folderBackend.handleRequestException(e);
                    } finally {
                        selectedNode.getUI().removeClass("x-tree-node-loading");
                        newParentNode.getUI().removeClass("x-tree-node-loading");
                    }
                }
            }
        });
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};

Tine.Felamimail.nodeActions.RefreshFolderAction = {
    app: 'Felamimail',
    text: 'Update Folder',
    itemId: 'RefreshFolderAction',
    iconCls: 'action_update_cache',
    scope: this,
    qtip: 'clear single folder message cache',
    handler: async function(action) {
        if (!action.node) return;
        const app = action.app;
        const folder = action.folder;
        const selectedNode = action.node;
        
        try {
            selectedNode.getUI().addClass("x-tree-node-loading");
            await Tine.Felamimail.refreshFolder(folder.get('id'));
            Ext.Msg.show({
                title: app.i18n._('Update Folder'),
                msg: app.i18n._('Folder cache has been cleared!'),
                icon: Ext.MessageBox.INFO,
                buttons: Ext.Msg.OK,
                scope: this
            });
        } catch (e) {
            Tine.Felamimail.folderBackend.handleRequestException(e);
        } finally {
            selectedNode.getUI().removeClass("x-tree-node-loading");
        }
    },
    actionUpdater: Tine.Felamimail.nodeActions.actionUpdater,
};
