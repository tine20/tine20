/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.Application
 * @extends     Tine.Tinebase.Application
 *
 * <p>Expressomail application obj</p>
 * <p>
 * </p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 *
 * @constructor
 * Create a new  Tine.Expressomail.Application
 */
Tine.Expressomail.Application = Ext.extend(Tine.Tinebase.Application, {

    /**
     * auto hook text _('New Mail')
     */
    addButtonText: 'New Mail',

    /**
     * @property checkMailsDelayedTask
     * @type Ext.util.DelayedTask
     */
    checkMailsDelayedTask: null,

    /**
     * @property defaultAccount
     * @type Tine.Expressomail.Model.Account
     */
    defaultAccount: null,

    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,

    /**
     * @type Ext.data.JsonStore
     */
    accountStore: null,

    /**
     * @property updateInterval user defined update interval (milliseconds)
     * @type Number
     */
    updateInterval: null,

    /**
    * @property cleanTrash already run.
    * @type Number
    */
    cleanTrash: null,
    
    /**
     * @property mailStore store with contacts for quick search
     * @type Tine.Tinebase.data.RecordStore
     */
    mailStore: null,

    /**
     * transaction id of current update message cache request
     * @type Number
     */
    updateMessageCacheTransactionId: null,

    getFolderStatusTransactionInProgress: false,

    /**
     * unreadcount in default account inbox
     * @type Number
     */
    unreadcountInDefaultInbox: 0,

    /**
     * returns title (Email)
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Email');
    },


   /**
   * Verify trash folder for clean ...
   */
    verifyTrashToClean: function(account_id) {
        if(!account_id) return;
        if(!Tine.Expressomail.registry.get('preferences').get('deleteFromTrash')) return;
        Ext.MessageBox.wait(_('Please wait'), _('Verifying trash folder'));
        var params = {
          method: 'Expressomail.deleteMsgsBeforeDate'
        };
        params.accountId = account_id;
        Ext.Ajax.request({
            params: params,
            scope: this,
            timeout: 150000, // 2 minutes
            success: function(result, request){
                //alert(result.msgs + ' Removed from trash folder');
                Ext.MessageBox.hide();
            }
        });
        return;
    },

    /**
     * start delayed task to init folder store / updateFolderStore
     */
    init: function() {
        Tine.log.info('initialising app');
        Tine.Expressomail.MimeDisplayManager.register('text/readconf', Tine.Expressomail.ReadConfirmationDetailsPanel);
        this.checkMailsDelayedTask = new Ext.util.DelayedTask(this.checkMails, this);

        this.updateInterval = parseInt(Tine.Expressomail.registry.get('preferences').get('updateInterval')) * 60000;
        Tine.log.debug('user defined update interval is "' + this.updateInterval/1000 + '" seconds');

        this.defaultAccount = Tine.Expressomail.registry.get('preferences').get('defaultEmailAccount');
        Tine.log.debug('default account is "' + this.defaultAccount);

        if (window.isMainWindow) {
            if (Tine.Tinebase.appMgr.getActive() != this && this.updateInterval) {
                var delayTime = this.updateInterval/20;
                Tine.log.debug('start preloading mails in "' + delayTime/1000 + '" seconds');
                this.checkMailsDelayedTask.delay(delayTime);
            }

            this.showActiveVacation();
            var adbHook1 = new Tine.Expressomail.GridPanelHook({
                app: this,
                foreignAppName: 'Addressbook',
                recordTypeName: 'Contact'
            });
            var adbHook2 = new Tine.Expressomail.GridPanelHook({
                app: this,
                foreignAppName: 'Addressbook',
                recordTypeName: 'List'
            });

            this.mailStore = Tine.Expressomail.getMailStore();
            this.mailStore.load();
        }
        
        Tine.Expressomail.registry.get('preferences').on('replace', this.onPreferenceChange.createDelegate());
    },

    /**
     * init app auto hooks 
     */
    initAutoHooks: function() {
        Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', {
            text: this.i18n._hidden(this.addButtonText), 
            iconCls: this.getIconCls(),
            scope: this,
            handler: function(button) {
                var ms = this.getMainScreen(),
                    cp = ms.getCenterPanel();

                cp.onEditInNewWindow.call(cp, {encrypted:false});
            }
        });
        
        if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1' && Tine.Tinebase.registry.get('preferences').get('windowtype')=='Ext') {
            Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', {
                text: this.i18n._hidden('New Mail (encrypted)'), 
                iconCls: this.getIconCls(),
                scope: this,
                handler: function(button) {
                    var ms = this.getMainScreen(),
                        cp = ms.getCenterPanel();

                    cp.onEditInNewWindow.call(cp, {encrypted:true});
                }
            });
        }

    },

    /**
     * show notification with active vacation information
     */
    showActiveVacation: function () {
        var accountsWithActiveVacation = this.getAccountStore().query('sieve_vacation_active', true);
        accountsWithActiveVacation.each(function(item) {
                Ext.MessageBox.alert(this.i18n._('Active Vacation Message'), String.format(this.i18n._('Email account "{0}" has an active vacation message.'), item.get('name')));
        }, this);
    },

    /**
     * check mails
     *
     * if no folder is given, we find next folder to update ourself
     *
     * @param {Tine.Expressomail.Model.Folder} [folder]
     * @param {Function} [callback]
     */
    checkMails: function(folder, callback) {
        this.checkMailsDelayedTask.cancel();
        
        if (! this.getFolderStore().getCount() && this.defaultAccount) {
            this.fetchSubfolders('/' + this.defaultAccount);
            return;
        }

        Tine.log.info('checking mails' + (folder ? ' for folder ' + folder.get('localname') : '') + ' now: ' + new Date());

        // if no folder is given, see if there is a folder to check in the folderstore
        if (! folder) {
            folder = this.getNextFolderToUpdate();
        }

        // for testing purposes
        //console.log('update disabled');
        //return;

        if (folder) {
            var executionTime = folder.isCurrentSelection() ? 10 : Math.min(this.updateInterval/1000, 120);

            if (this.updateMessageCacheTransactionId && Tine.Expressomail.folderBackend.isLoading(this.updateMessageCacheTransactionId)) {
                var currentRequestFolder = this.folderStore.query('cache_status', 'pending').first(),
                    expectedResponseIn = Math.floor((this.updateMessageCacheTransactionExpectedResponse.getTime() - new Date().getTime())/1000);

                if (currentRequestFolder && (currentRequestFolder !== folder || expectedResponseIn > executionTime)) {
                    Tine.log.debug('aborting current update message request (expected response in ' + expectedResponseIn + ' seconds)');
                    Tine.Expressomail.folderBackend.abort(this.updateMessageCacheTransactionId);
                    currentRequestFolder.set('cache_status', 'incomplete');
                    currentRequestFolder.commit();
                } else {
                    Tine.log.debug('a request updating message cache for folder "' + folder.get('localname') + '" is in progress -> wait for request to return');
                    return;
                }
            }

            Tine.log.debug('Updating message cache for folder "' + folder.get('localname') + '" of account ' + folder.get('account_id'));
            Tine.log.debug('Max execution time: ' + executionTime + ' seconds');

            this.updateMessageCacheTransactionExpectedResponse = new Date().add(Date.SECOND, executionTime);
            folder.set('cache_status', 'pending');
            folder.commit();

            this.updateMessageCacheTransactionId = Tine.Expressomail.folderBackend.updateMessageCache(folder.id, executionTime, {
                scope: this,
                callback: callback,
                failure: this.onBackgroundRequestFail,
                success: function(folder) {
                    this.getAccountStore().getById(folder.get('account_id')).setLastIMAPException(null);
                    this.getFolderStore().updateFolder(folder);

                    if (folder.get('cache_status') === 'updating') {
                        Tine.log.debug('updating message cache for folder "' + folder.get('localname') + '" is in progress on the server (folder is locked)');
                        return this.checkMailsDelayedTask.delay(this.updateInterval);
                    }
                    this.checkMailsDelayedTask.delay(0);
                }
            });
        } else {
            var allFoldersFetched = this.fetchSubfolders();

            if (allFoldersFetched) {
                Tine.log.info('nothing more to do -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
                if (this.updateInterval > 0) {
                    this.checkMailsDelayedTask.delay(this.updateInterval);
                }
            } else {
                this.checkMailsDelayedTask.delay(20000);
            }
        }
    },
//    
//    /**
//     * Get mail Store
//     */
//    getMailStore: function() {
//        
//        if (!this.mailStore) {
//            // Load mailStore
//            this.mailStore = Tine.Expressomail.getMailStore();
//        }
//        return this.mailStore;
//    },

    /**
     * fetch subfolders by parent path
     * - if parentPath param is empty, it loops all accounts and account folders to find the next folders to fetch
     *
     * @param {String} parentPath
     * @return {Boolean} true if all folders of all accounts have been fetched
     */
    fetchSubfolders: function(parentPath) {
        var folderStore = this.getFolderStore(),
            accountStore = this.getAccountStore(),
            doQuery = true,
            allFoldersFetched = false;

        var index = accountStore.findExact('all_folders_fetched', false),
        account = accountStore.getAt(index);

        if(!this.cleanTrash){
            this.verifyTrashToClean(account.id);
            this.cleanTrash = 1;
        }

        if (! parentPath) {
            // find first account that has unfetched folders
            var index = accountStore.findExact('all_folders_fetched', false),
                account = accountStore.getAt(index);

            if (account) {
                // determine the next level of folders that is not fetched
                parentPath = '/' + account.id;

                var recordsOfAccount = folderStore.query('account_id', account.id);
                if (recordsOfAccount.getCount() > 0) {
                    // loop account folders and find the next folder path that hasn't been queried and has children
                    var path, found = false;
                    recordsOfAccount.each(function(record) {
                        // Get parent_path from folder record
                        parentPath = record.get('parent_path');
                        path = parentPath + '/' + record.id;
                        if (! folderStore.isLoadedOrLoading('parent_path', path) && (! account.get('has_children_support') || record.get('has_children'))) {
                            parentPath = path;
                            found = true;
                            Tine.log.debug('fetching next level of subfolders for ' + record.get('globalname'));
                            return false;
                        }
                        return true;
                    }, this);

                    if (! found) {
                        Tine.log.debug('all folders of account ' + account.get('name') + ' have been fetched ...');
                        account.set('all_folders_fetched', true);
                        return false;
                    }
                } else {
                    Tine.log.debug('fetching first level of folders for account ' + account.get('name'));
                }

            } else {
                Tine.log.debug('all folders of all accounts have been fetched ...');
                return true;
            }
        } else {
            Tine.log.debug('no folders in store yet, fetching first level ...');
        }

        if (! folderStore.queriesPending || folderStore.queriesPending.length == 0) {
            folderStore.asyncQuery('parent_path', parentPath, this.checkMails.createDelegate(this, []), [], this, folderStore);
        } else {
            this.checkMailsDelayedTask.delay(0);
        }

        return false;
    },

    /**
     * get folder store
     *
     * @return {Tine.Expressomail.FolderStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            Tine.log.debug('creating folder store');
            this.folderStore = new Tine.Expressomail.FolderStore({
                listeners: {
                    scope: this,
                    update: this.onUpdateFolder
                }
            });
        }

        return this.folderStore;
    },

    /**
     * gets next folder which needs to be checked for mails
     *
     * @return {Model.Folder/null}
     */
    getNextFolderToUpdate: function() {
        var currNode = this.getMainScreen().getTreePanel().getSelectionModel().getSelectedNode(),
            currFolder = currNode ? this.getFolderStore().getById(currNode.id) : null;

        // current selection has highest prio!
        if (currFolder && currFolder.needsUpdate(this.updateInterval)) {
            return currFolder;
        }

        // check if inboxes need updates
        var inboxes = this.folderStore.queryBy(function(folder) {
            return folder.isInbox() && folder.needsUpdate(this.updateInterval);
        }, this);
        if (inboxes.getCount() > 0) {
            return inboxes.first();
        }

        // check for incompletes
        var incompletes = this.folderStore.queryBy(function(folder) {
            return (['complete', 'updating', 'disconnect'].indexOf(folder.get('cache_status')) === -1 && folder.get('is_selectable'));
        }, this);
        if (incompletes.getCount() > 0) {
            Tine.log.debug('Got ' + incompletes.getCount() + ' incomplete folders.');
            var firstIncomplete = incompletes.first();
            Tine.log.debug('First ' + firstIncomplete.get('cache_status') + ' folder to check: ' + firstIncomplete.get('globalname'));
            return firstIncomplete;
        }

        // check for outdated
        if (! this.getFolderStatusTransactionInProgress) {
            this.getStatusOfOutdatedFolders();
        } else {
            Tine.log.debug('getFolderStatus() already running ... wait a little more.');
        }

        // nothing to update
        return null;
    },

    /**
     * collects outdated folders and calls getFolderStatus on server to fetch all folders that need to be updated
     */
    getStatusOfOutdatedFolders: function() {
        var outdated = this.folderStore.queryBy(function(folder) {
            if (! folder.get('is_selectable')) {
                return false;
            }

            var timestamp = folder.get('client_access_time');
            if (! Ext.isDate(timestamp)) {
                return true;
            }
            // update inboxes more often than other folders
            if (timestamp.getElapsed() > this.updateInterval) {
                return true;
            }
            return false;
        }, this);

        if (outdated.getCount() > 0) {
            Tine.log.debug('Still got ' + outdated.getCount() + ' outdated folders to update');

            // call Expressomail.getFolderStatus() with ids of outdated folders -> update folder store on success
            // get only max 50 folders at once
            var rangeOfFolders = (outdated.getCount() > 50) ? outdated.getRange(0, 49) : outdated.getRange(),
                ids = [],
                now = new Date();
            Ext.each(rangeOfFolders, function(folder) {
                folder.set('client_access_time', now);
                ids.push(folder.id);
            });

            var filter = [{field: 'id', operator: 'in', value: ids}];
            Tine.log.debug('Requesting folder status of ' + rangeOfFolders.length + ' folders ...');
            Tine.Expressomail.getFolderStatus(filter, this.onGetFolderStatusSuccess.createDelegate(this));
            this.getFolderStatusTransactionInProgress = true;
        }
    },

    /**
     * get folder status returned -> set folders that need an update to pending status
     *
     * @param {Array} response
     */
    onGetFolderStatusSuccess: function(response) {
        this.getFolderStatusTransactionInProgress = false;
        Tine.log.debug('Tine.Expressomail.Application::onGetFolderStatusSuccess() -> Folder status update successful.');
        Tine.log.debug(response);

        if (response && response.length > 0) {
            Tine.log.debug('Tine.Expressomail.Application::onGetFolderStatusSuccess() -> Got ' + response.length + ' folders that need an update.');

            Ext.each(response, function(folder) {
                var folderToUpdate = this.folderStore.getById(folder.id);
                folderToUpdate.set('cache_status', 'pending');
            }, this);

            this.checkMailsDelayedTask.delay(1000);
        } else {
            Tine.log.debug('Tine.Expressomail.Application::onGetFolderStatusSuccess() -> No folders for update found.');
        }
    },

    /**
     * executed when updateMessageCache requests fail
     *
     * NOTE: We show the credential error dlg and this only for the first error
     *
     * @param {Object} exception
     */
    onBackgroundRequestFail: function(exception) {
        var currentRequestFolder = this.folderStore.query('cache_status', 'pending').first();
        var accountId     = currentRequestFolder.get('account_id'),
            account       = accountId ? this.getAccountStore().getById(accountId): null,
            imapStatus    = account ? account.get('imap_status') : null,
            grid          = this.getMainScreen().getCenterPanel(),
            manualRefresh = grid && grid.manualRefresh;

        if (manualRefresh) {
            grid.manualRefresh = false;
            grid.pagingToolbar.refresh.enable();
        }

        if (exception.code == 913) {
            // folder not found -> remove folder from store and tree panel
            var treePanel = this.getMainScreen().getTreePanel(),
                node = treePanel.getNodeById(currentRequestFolder.id);
            if (node) {
                node.remove();
            }
            this.getFolderStore().remove(currentRequestFolder);
        } else if (account && (manualRefresh ||
            //  do not show exclamation mark for timeouts and connection losses
            (exception.code !== 520 && exception.code !== 510))
        ) {
            account.setLastIMAPException(exception);

            this.getFolderStore().each(function(folder) {
                if (folder.get('account_id') === accountId) {
                    folder.set('cache_status', 'disconnect');
                    folder.commit();
                }
            }, this);

            if (exception.code == 912 && imapStatus !== 'failure' && Tine.Tinebase.appMgr.getActive() === this) {
                Tine.Expressomail.folderBackend.handleRequestException(exception);
            }
        }

        Tine.log.info((manualRefresh ? 'Manual' : 'Background') + ' update failed (' + exception.message
            + ') for folder ' + currentRequestFolder.get('globalname')
            + ' -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
        Tine.log.debug(exception);
        this.checkMailsDelayedTask.delay(this.updateInterval);
    },

    /**
     * executed right before this app gets activated
     */
    onBeforeActivate: function() {
        Tine.log.info('activating expressomail now');
        // abort preloading/old actions and force fresh fetch
        this.checkMailsDelayedTask.delay(0);
    },

    /**
     * on update folder
     *
     * @param {Tine.Expressomail.FolderStore} store
     * @param {Tine.Expressomail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolder: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT) {
            if (record.isModified('cache_status')) {
                Tine.log.info('Tine.Expressomail.Application::onUpdateFolder(): Folder "' + record.get('localname') + '" updated with cache_status: ' + record.get('cache_status'));

                // as soon as we get a folder with status != complete we need to trigger checkmail soon!
                if (['complete', 'pending'].indexOf(record.get('cache_status')) === -1) {
                    this.checkMailsDelayedTask.delay(1000);
                }

                if (record.isInbox() && record.isModified('cache_unreadcount')) {
                    this.showNewMessageNotification(record);
                }
            }

            if (record.isInbox()) {
                if (this.isDefaultAccountId(record.get('account_id'))) {
                    if (record.isModified('cache_unreadcount') || record.get('cache_unreadcount') != this.unreadcountInDefaultInbox) {
                        this.setTitleWithUnreadcount(record.get('cache_unreadcount'));
                    }
                }

                if (record.isModified('quota_usage') || record.isModified('quota_limit')) {
                    this.onUpdateFolderQuota(record);
                }
            }
        }
    },

    /**
     * executed when a value in Expressomail registry/preferences changed
     * @param {string} key
     * @param {value} oldValue
     * @param {value} newValue
     */
    onPreferenceChange: function (key, oldValue, newValue) {
        switch (key) {
            case 'enableEncryptedMessage':
                // reload mainscreen
                var reload = new Ext.util.DelayedTask(function(){window.location.reload(false);},this);
                reload.delay(500);
                
                break;
        }
    },
    
    /**
     * checks default account id
     *
     * @param {String} accountId
     * @return {Boolean}
     */
    isDefaultAccountId: function(accountId) {
        return accountId == Tine.Expressomail.registry.get('preferences').get('defaultEmailAccount');
    },

    /**
     * show notification for new messages
     *
     * @param {Tine.Expressomail.Model.Folder} record
     */
    showNewMessageNotification: function(record) {
        var recents = (record.get('cache_unreadcount') - record.modified.cache_unreadcount),
            account = this.getAccountStore().getById(record.get('account_id'));

        if (recents > 0 ) {
            Tine.log.info('Show notification: ' + recents + ' new mails.');
            var title = this.i18n._('New mails'),
                message = String.format(this.i18n._('You got {0} new mail(s) in folder {1} ({2}).'), recents, record.get('localname'), account.get('name'));

            if (record.isCurrentSelection()) {
                // need to defer the notification because the new messages are not shown yet
                // -> improve this with a callback fn or something like that / unread count should be updated when the messages become visible, too
                Ext.ux.Notification.show.defer(3500, this, [title, message]);
            } else {
                Ext.ux.Notification.show(title, message);
            }
        }
    },

    /**
     * write number of unread messages in all accounts into title
     *
     * @param {Number} unreadcount
     */
    setTitleWithUnreadcount: function(unreadcount) {
        if (! window.isMainWindow) {
            return;
        }

        this.unreadcountInDefaultInbox = unreadcount;
        if (this.unreadcountInDefaultInbox < 0) {
            this.unreadcountInDefaultInbox = 0;
        }

        Tine.log.info('Updating title with new unreadcount: ' + this.unreadcountInDefaultInbox);
        var currentTitle = document.title,
            unreadString = (this.unreadcountInDefaultInbox != 0) ? '(' + this.unreadcountInDefaultInbox + ') ' : '';

        if (currentTitle.match(/^\([0-9]+\) /)) {
            document.title = document.title.replace(/^\([0-9]+\) /, unreadString);
        } else {
            document.title = unreadString + currentTitle;
        }
    },

    /**
     * folder quota is updated
     *
     * @param {Tine.Expressomail.Model.Folder} record
     */
    onUpdateFolderQuota: function(record) {
        if (record.get('quota_usage')) {
            Tine.log.info('Folder "' + record.get('localname') + '" updated with quota values: '
                + record.get('quota_usage') + ' / ' + record.get('quota_limit'));

            this.getMainScreen().getCenterPanel().updateQuotaBar(record);
        }
    },

    /**
     * get active account
     * @return {Tine.Expressomail.Model.Account}
     */
    getActiveAccount: function() {
        var account = null;

        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            account = treePanel.getActiveAccount();
        }

        if (account === null) {
            account = this.getAccountStore().getById(Tine.Expressomail.registry.get('preferences').get('defaultEmailAccount'));
        }

        if (account === null) {
            // try to get first account in store
            account = this.getAccountStore().getAt(0);
        }

        return account;
    },

    /**
     * get account store
     *
     * @return {Ext.data.JsonStore}
     */
    getAccountStore: function() {
        if (! this.accountStore) {
            Tine.log.debug('creating account store');

            // create store (get from initial data)
            this.accountStore = new Ext.data.JsonStore({
                fields: Tine.Expressomail.Model.Account,
                data: Tine.Expressomail.registry.get('accounts'),
                autoLoad: true,
                id: 'id',
                root: 'results',
                totalProperty: 'totalcount',
                proxy: Tine.Expressomail.accountBackend,
                reader: Tine.Expressomail.accountBackend.getReader(),
                listeners: {
                    scope: this,
                    'add': function (store, records) {
                        Tine.log.info('Account added: ' + records[0].get(Tine.Expressomail.Model.Account.getMeta('titleProperty')));
                        this.getMainScreen().getCenterPanel().action_write.setDisabled(! this.getActiveAccount());
                    },
                    'remove': function (store, record) {
                        Tine.log.info('Account removed: ' + record.get(Tine.Expressomail.Model.Account.getMeta('titleProperty')));
                        this.getMainScreen().getCenterPanel().action_write.setDisabled(! this.getActiveAccount());
                    }
                }
            });
        }

        return this.accountStore;
    },

    /**
     * show expressomail credentials dialog
     *
     * @param {Tine.Expressomail.Model.Account} account
     * @param {String} username [optional]
     */
    showCredentialsDialog: function(account, username) {
        Tine.Expressomail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
            windowTitle: String.format(this.i18n._('IMAP Credentials for {0}'), account.get('name')),
            appName: 'Expressomail',
            credentialsId: account.id,
            i18nRecordName: this.i18n._('Credentials'),
            recordClass: Tine.Tinebase.Model.Credentials,
            record: new Tine.Tinebase.Model.Credentials({
                id: account.id,
                username: username ? username : ''
            }),
            listeners: {
                scope: this,
                'update': function(data) {
                    var folderStore = this.getFolderStore();
                    if (folderStore.queriesPending.length > 0) {
                        // reload all folders of account and try to select inbox
                        var accountId = folderStore.queriesPending[0].substring(16, 56),
                            account = this.getAccountStore().getById(accountId),
                            accountNode = this.getMainScreen().getTreePanel().getNodeById(accountId);

                        folderStore.resetQueryAndRemoveRecords('parent_path', '/' + accountId);
                        account.set('all_folders_fetched', true);

                        accountNode.loading = false;
                        accountNode.reload(function(callback) {
                            Ext.each(accountNode.childNodes, function(node) {
                                if (Ext.util.Format.lowercase(node.attributes.localname) == 'inbox') {
                                    node.select();
                                    return false;
                                }
                            }, this);
                        });
                    } else {
                        this.checkMailsDelayedTask.delay(0);
                    }
                }
            }
        });
    }
});

/**
 * get flags store
 *
 * @return {Ext.data.JsonStore}
 */
Tine.Expressomail.getMailStore = function() {
    Tine.log.debug('Tine.Expressomail.getMailStore() - getting mail store');
    var store = Ext.StoreMgr.get('ExpressomailMailStore');

    if (!store) {
        // create store (get from initial registry data)
        store = new Tine.Tinebase.data.RecordStore({
            autoLoad: false,
            readOnly: false,
            proxy: Tine.Addressbook.emailaddressBackend,
            recordClass: Tine.Addressbook.Model.EmailAddress,
            sortInfo: {field: 'email', direction: 'ASC'}
        });
        
        var filter = Tine.Addressbook.Model.EmailAddress.getFilterModel();
        filter.push({field: 'email_query', operator: 'contains', value: '@'});
        filter.push({field: 'container_id', operator: 'equals', value: '/personal/'
                + Tine.Tinebase.registry.get('currentAccount').accountId});
        
        store.baseParams.filter = filter;
        // TODO: acertar o modelo de Contatos / Listas para ser possível ordar por nome
        //store.baseParams.sort = 'n_fn';
        store.baseParams.sort = 'email';
        store.baseParams.dir = 'ASC';
        
        Ext.StoreMgr.add('ExpressomailMailStore', store);
    }

    return store;
};

/**
 * @namespace Tine.Expressomail
 * @class Tine.Expressomail.MainScreen
 * @extends Tine.widgets.MainScreen
 *
 * MainScreen Definition
 */
Tine.Expressomail.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    /**
     * adapter fn to get folder tree panel
     *
     * @return {Ext.tree.TreePanel}
     */
    getTreePanel: function() {
        return this.getWestPanel().getContainerTreePanel();
    }
});

/**
 * get flags store
 *
 * @param {Boolean} reload
 * @return {Ext.data.JsonStore}
 */
Tine.Expressomail.loadFlagsStore = function(reload) {

    var store = Ext.StoreMgr.get('ExpressomailFlagsStore');

    if (!store) {
        // create store (get from initial registry data)
        store = new Ext.data.JsonStore({
            fields: Tine.Expressomail.Model.Flag,
            data: Tine.Expressomail.registry.get('supportedFlags'),
            autoLoad: true,
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount'
        });

        Ext.StoreMgr.add('ExpressomailFlagsStore', store);
    }

    return store;
};

Tine.Expressomail.getMailStoreData = function(store) {
    
    var items = [];
    Ext.each(store.data.items, function(item){
        items.push(item.data);
    });
    
    return Ext.util.JSON.encode(items);
    
}

/**
 * Create a new Store from records
 */
Tine.Expressomail.createMailStore = function(json) {
    
    var items = Ext.util.JSON.decode(json);
    var records = [];
    Ext.each(items, function(item){
        records.push(new Tine.Addressbook.Model.EmailAddress(item));
    });
    
    var store = new Ext.data.SimpleStore({
        autoLoad: false,
        //data: items,
        readOnly: true,
        recordClass: Tine.Addressbook.Model.EmailAddress
    });
    
    store.add(records);
    
    Ext.apply(store, {
        createFilterFn: function(property, value, anyMatch, caseSensitive){
            if(Ext.isEmpty(value, false)){
                return false;
            }
            value = this.data.createValueMatcher(value, anyMatch, caseSensitive);
            return function(r){
                if (Ext.isArray(property)) {
                    var test = false;
                    Ext.each(property, function(p) {
                        test = value.test(r.data[p]) || test;
                    });
                    return test;
                } else {
                    return value.test(r.data[property]);
                }
            };
        } //,
//        filter : function(property, value, anyMatch, caseSensitive){
//            var fn = this.createFilterFn(property, value, anyMatch, caseSensitive);
//            return fn ? this.filterBy(fn) : this.clearFilter();
//        }
    });
    
    
    return store;
}

/**
 * add signature (get it from default account settings)
 *
 * @param {String} id
 * @return {String}
 */
Tine.Expressomail.getSignature = function(id) {

    var result = '',
        app = Tine.Tinebase.appMgr.get('Expressomail'),
        activeAccount = app.getMainScreen().getTreePanel().getActiveAccount();

    id = id || (activeAccount ? activeAccount.id : 'default');

    if (id === 'default') {
        id = Tine.Expressomail.registry.get('preferences').get('defaultEmailAccount');
    }

    var defaultAccount = app.getAccountStore().getById(id);
    var signature = (defaultAccount) ? defaultAccount.get('signature') : '';
    if (signature && signature != '') {
        // NOTE: signature is always in html, nl2br here would cause duplicate linebreaks!
        result = '<br><br><span id="expressomail-body-signature">-- <br>' + signature + '</span>';
    }

    return result;
};

/**
 * get email string (n_fn <email@host.tld>) from contact
 *
 * @param {Tine.Addressbook.Model.Contact} contact
 * @return {String}
 */
Tine.Expressomail.getEmailStringFromContact = function(contact) {
    Tine.log.debug('Tine.Expressomail.getEmailStringFromContact() - getting contact email');
    Tine.log.debug(contact);

    var result = contact.get('n_fn') + ' <';
    result += contact.getPreferedEmail();
    result += '>';

   if (contact.get('org_unit') != '' && contact.get('org_unit') != null ) {
        result += '  ' + contact.get('org_unit');
    }

    return result;
};

/**
 * generic exception handler for expressomail (used by folder and message backends and updateMessageCache)
 *
 * TODO move all 902 exception handling here!
 * TODO invent requery on 902 with cred. dialog
 *
 * @param {Tine.Exception|Object} exception
 */
Tine.Expressomail.handleRequestException = function(exception) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }

    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);

    var app = Tine.Tinebase.appMgr.get('Expressomail');

    switch(exception.code) {
        case 910: // Expressomail_Exception_IMAP
        case 911: // Expressomail_Exception_IMAPServiceUnavailable
            Ext.Msg.show({
               title:   app.i18n._('IMAP Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to IMAP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        case 912: // Expressomail_Exception_IMAPInvalidCredentials
            var accountId   = exception.account && exception.account.id ? exception.account.id : '',
                account     = accountId ? app.getAccountStore().getById(accountId): null,
                imapStatus  = account ? account.get('imap_status') : null;

            if (account) {
                account.set('all_folders_fetched', true);
                if (account.get('type') == 'system') {
                    // just show message box for system accounts
                    Ext.Msg.show({
                       title:   app.i18n._('IMAP Credentials Error'),
                       msg:     app.i18n._('Your email credentials are wrong. Please contact your administrator'),
                       icon:    Ext.MessageBox.ERROR,
                       buttons: Ext.Msg.OK
                    });
                } else {
                    app.showCredentialsDialog(account, exception.username);
                }
            } else {
                exception.code = 910;
                return this.handleRequestException(exception);
            }
            break;

        case 913: // Expressomail_Exception_IMAPFolderNotFound
            Ext.Msg.show({
               title:   app.i18n._('IMAP Error'),
               msg:     app.i18n._('One of your folders was deleted or renamed by another client. Please update the folder list of this account.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            // TODO reload account root node
            break;

        case 914: // Expressomail_Exception_IMAPMessageNotFound
            Tine.log.notice('Message was deleted by another client.');

            // remove message from store and select next message
            var requestParams = Ext.util.JSON.decode(exception.request).params,
                centerPanel = app.getMainScreen().getCenterPanel(),
                msg = centerPanel.getStore().getById(requestParams.id);

            if (msg) {
                var sm = centerPanel.getGrid().getSelectionModel(),
                    selectedMsgs = sm.getSelectionsCollection(),
                    nextMessage = centerPanel.getNextMessage(selectedMsgs);

                centerPanel.getStore().remove(msg);
                if (nextMessage) {
                    sm.selectRecords([nextMessage]);
                }
            }
            break;

        case 920: // Expressomail_Exception_SMTP
            Ext.Msg.show({
               title:   app.i18n._('SMTP Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to SMTP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        case 930: // Expressomail_Exception_Sieve
            Ext.Msg.show({
               title:   app.i18n._('Sieve Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to Sieve server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        case 931: // Expressomail_Exception_SievePutScriptFail
            Ext.Msg.show({
               title:   app.i18n._('Save Sieve Script Error'),
               msg:     app.i18n._('Could not save script on Sieve server.') + (exception.message ? ' (' + exception.message + ')' : ''),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        case 932: // Expressomail_Exception_IMAPCacheTooMuchResults
            Ext.Msg.show({
               title:   app.i18n._('IMAP Backend Warning'),
               msg:     app.i18n._(exception.message),
               icon:    Ext.MessageBox.WARNING,
               buttons: Ext.Msg.OK
            });
            break;

        case 933: // Expressomail_Exception_IMAPFolderDuplicated
            Ext.Msg.show({
               title:   app.i18n._('IMAP Backend Warning'),
               msg:     app.i18n._(exception.message),
               icon:    Ext.MessageBox.WARNING,
               buttons: Ext.Msg.OK
            });
        break;

        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};


Tine.Expressomail.fixIEUserAgent = function()
{
    if(!Ext.isNewIE){
        return navigator.userAgent;

    }else{
        var re = /\(.*\)/,
            result = re.exec(navigator.userAgent)[0],
            original = result.substring(1, result.length -1),
            elements = original.split('; '),
            newUserAgent = '';
        for(var i = 0; i < 4; i++){
            newUserAgent = newUserAgent+elements[i]+'; ';
        }
        return navigator.userAgent.replace(original,newUserAgent.substr(0,newUserAgent.length -2));
    }
}

Tine.Expressomail.onSecurityAppletError = function () {
    // hides all pending messagebox shown on screen
    Ext.MessageBox.hide(); // 'loading criptography components' messagebox
    var app = Tine.Tinebase.appMgr.get('Expressomail');
    app.mainScreen.GridPanel.detailsPanel.isDecrypting = false; // clear decrypting flag
    app.mainScreen.GridPanel.getLoadMask().hide(); // 'loading' messagebox on GridPanel
    app.mainScreen.GridPanel.detailsPanel.getLoadMask().hide(); // 'loading' messagebox on GridDetailsPanel
    // purge all pending event listeners
    app.mainScreen.GridPanel.detailsPanel.purgeListeners();
    // show error message
    if (app.mainScreen.GridPanel.detailsPanel.record) {
        app.mainScreen.GridPanel.detailsPanel.showErrorTemplate(app.i18n._('Failure decrypting the message, please try again.'));
    }
    // focus selected message on grid
    app.mainScreen.GridPanel.focusSelectedMessage();
}

/**
 * send data to Security Applet
 * 
 * @todo test if applet is ready() if exceptions occurs try again in a few miliseconds
 */
Tine.Expressomail.toSecurityApplet = function (id, data, operation, tries){
    var MAXTRIES = 5;
    tries = tries || 1;
    try {
        var applet = document.getElementById('SecurityApplet');
        try {
            applet.isActive();
        } catch (err) {
            Tine.log.debug('Tine.Expressomail.toSecurityApplet() -> Applet not Active: try ' + tries);
            if (tries < MAXTRIES) {
                Tine.Expressomail.toSecurityApplet.defer(500, this, [id, data, operation, ++tries]);
            } else {
                Tine.Expressomail.onSecurityAppletError();
                return false;
            }
        }
        
        switch (operation) {
            case 'SIGN_AND_ENCRYPT' :
                applet.signAndEncryptMessage(Tine.Expressomail.fixIEUserAgent(), id, data);
                break;
            case 'ENCRYPT' :
                applet.encryptMessage(Tine.Expressomail.fixIEUserAgent(), id, data);
                break;
            case 'DECRYPT' :
                // Slicing data to send to applet. Workaround to liveConnect issue
                // 1MB
                for (var chunkLength = 0x100000, i = 0; i < data.length; i += chunkLength) {
                    if (i+chunkLength < data.length) {
                        applet.decryptMessage(Tine.Expressomail.fixIEUserAgent(), id, data.slice(i, i+chunkLength), false);
                    } else {
                        applet.decryptMessage(Tine.Expressomail.fixIEUserAgent(), id, data.slice(i), true);
                    }
                }
                break;
        }
        
    } catch (err) {
        Tine.log.debug('Tine.Expressomail.MessageEditDialog::onSecurityApplet() -> error:' + err.message);
        return false;
    }
    
    return true;
    
};

/**
 * Add applet to component or center panel
 * 
 */
Tine.Expressomail.addSecurityApplet = function (id, panel, region) {
    var app = Tine.Tinebase.appMgr.get('Expressomail');
    var centerPanel = Tine.Tinebase.appMgr.get('Expressomail').getMainScreen().getCenterPanel();
    if (typeof(panel) === 'undefined') {
        region = 'east';
        panel = centerPanel.getLayout()[region].panel;
    }
    
    if (panel && typeof(panel.securityApplet) === 'undefined') {
        Tine.Expressomail.AppletLoadaded = false;
        Ext.MessageBox.wait(app.i18n._('Loading Criptography Components...'), _('Please wait!'));
        
        Tine.Expressomail.afterAppletLoad = function(){
            Tine.Expressomail.AppletLoadaded = true;
            if (typeof(this.window) != 'undefined' && Ext.isFunction(this.window.show)) {
                this.window.show();
            }
            //Tine.WindowFactory.windowManager.each(function(data){data.show()});
            Ext.MessageBox.hide();
        }.createDelegate(this);
        
        //var testAppletLoad = Tine.Expressomail.testAppletLoad.createDelegate(this);
        //testAppletLoad.defer(30000, this, [id,0,panel,loadindAppletMessageBox]);
        panel.securityApplet = Tine.Expressomail.getSecurityApplet(id, region);
        panel.add(panel.securityApplet);
        panel.doLayout();
        panel.securityApplet.doLayout();
        panel.securityApplet.show();
    }
};

//Tine.Expressomail.testAppletLoad = function(id,tries,panel,msgbox){
//    tries = tries || 1;
//    try{
//        var applet = document.getElementById(id);
//        applet.isActive();
//        Tine.Expressomail.AppletLoadaded = true;
//        msgbox.hide();
//        if (typeof(this.window) != 'undefined' && Ext.isFunction(this.window.show)) {
//            this.window.show();
//        }
//    }catch(e){
//        Tine.log.debug(e);
//        if(tries < 11){
//             Tine.log.debug('Tine.Expressomail.testAppletLoad() -> Applet not Active: try ' + tries);
//             Tine.Expressomail.testAppletLoad.defer(5000,this,[id,++tries,panel,msgbox]);
//            
//        }
//        else{
//            var app = Tine.Tinebase.appMgr.get('Expressomail');
//            msgbox.hide();
//            panel.remove(panel.securityApplet,true); 
//            delete(panel.securityApplet);
//            panel.doLayout();
//            Tine.log.debug('Tine.Expressomail.testAppletLoad() -> Applet not Active: Max tries ');
//            Ext.MessageBox.alert(app.i18n._('Warning'),app.i18n._('Failure loading criptography components, please try again.'));
//        }
//    }
//    
//}

/**
 * Get applet's html
 *
 */
Tine.Expressomail.getSecurityApplet = function(id, region, params) {
/*
    var archive =
        'httpclient-4.2.6.jar, '+
        'httpcore-4.2.5.jar, '+
        'commons-codec-1.6.jar, '+
        'commons-logging-1.1.1.jar, '+
        'commons-lang3-3.1.jar, '+
        'guava-15.0.jar, '+
        'jackson-core-asl-1.9.13.jar, '+
        'jackson-mapper-asl-1.9.13.jar, '+
//        'javax.mail-1.5.1.jar, '+
        'mail.jar, '+
        'bcprov-jdk15on-1.49.jar, '+
        'bcpkix-jdk15on-1.49.jar, '+
        'bcmail-jdk15on-1.49.jar, '+
        'jericho-html-3.3.jar, '+
        'apache-mime4j-dom-0.7.2.jar, '+
        'apache-mime4j-core-0.7.2.jar, '+
        'picocontainer-2.14.3.jar, '+
        'expressocert-1.0.35.jar, '+
        'ExpressoCertMail.jar';
*/
    var archive = 'ExpressoCertMail-all.jar';


    var ieHtml = '<object id="'+ id +'" ' +
            'name="' + id + '" ' +
            'classid="clsid:CAFEEFAC-0016-0000-FFFF-ABCDEFFEDCBA">' +
            '<param name="codebase" value="/Expressomail/Java/"/>' +
            '<param name="archive" value="'+archive+'"/>' +
            '<param name="code" value="br.gov.serpro.expresso.security.applet.SmimeApplet"/>' +
//            '<param name="java_version" value="1.6*">' +
            '<param name="mayscript" value="true"/>' +
        '</object>';
    var ffHtml = '<embed id="' + id + '" ' +
            'name="' + id + '" ' +
            'codebase="/Expressomail/Java/" ' +
            'archive="'+archive+'" ' +
            'code="br.gov.serpro.expresso.security.applet.SmimeApplet" ' +
            'type="application/x-java-applet" ' +
//            'java_version="1.6*">' +
            'mayscript ' +
        '</embed>';

    return new Tine.Expressomail.SignatureAppletPanel({
        record: this.record,
        width:  0,
        height: 0,
        region: region,
        layout: 'fit',
        border: false,
//        autoShow: true,
        html: Ext.isIE ? ieHtml : ffHtml
    });
};

/**
 * Stub method to receive data from applet and relay it to the rigth ext component
 * Highly experimental
 * 
 * @todo buffer inside ext component. Use id to get component (var extObj = Ext.ComponentMgr.get(id);)
 */
var appletStub = function(last, id, response) {
    if (typeof(appletBuffer) === 'undefined') {
        appletBuffer = new Array();
    }
    
    if (typeof(appletBuffer.id) === 'undefined') {
        appletBuffer.id = new Array();
    }
    var data = '';
    if (last === 'false') {
        appletBuffer.id.push(response);
        return;
    } else {
        appletBuffer.id.push(response);
        data = Ext.util.JSON.decode(appletBuffer.id.join('').trim());
        delete appletBuffer.id;
    }
    
    var extObj = Ext.ComponentMgr.get(id);
    if (data.success) {
        var app = Tine.Tinebase.appMgr.get('Expressomail');
        if (app.mainScreen.GridPanel.detailsPanel.record != null) {
            app.mainScreen.GridPanel.detailsPanel.record.data.error_message = ''; //
        }

        if (data.signerCertificate != null){
            // Verify
            var store = new Tine.Tinebase.data.RecordStore({
                autoLoad: false,
                readOnly: true,
                proxy: Tine.Tinebase.DigitalCertificateBackend,
                recordClass: Tine.Tinebase.Model.DigitalCertificate
            });

            var verifyFailed = function (result, component){
                var msgOptions = {
                    title:      component.app.i18n._("Error Verifying Signer's Digital Certificate!"),
                    msg:        '',
                    buttons:    Ext.Msg.OK,
                    width:      500
                }
                if (result.success && result.totalRecords == 1){
                    if (result.records && result.records[0]){
                        msgOptions.msg = '<br /><span style="font-weight:bold">'+
                            component.app.i18n._('Details:')+': </span><br />';
                        Ext.each(result.records[0].get('messages'), function(msg){
                            msgOptions.msg += '<span style="padding-left:2em;color:#FF0000">'+
                                component.app.i18n._(msg)+'</span><br />';
                        });
                    } else {
                        msgOptions.msg = '<span style="padding-left:2em;color:#FF0000">'+
                            component.app.i18n._("Unknown Error. Please notify an Administrator")+
                            '</span><br />';
                    }
                } else {
                    msgOptions.msg = '<span style="padding-left:2em;color:#FF0000">'+
                        component.app.i18n._("Unknown Error. Please notify an Administrator")+
                        '</span><br />';
                }

                Ext.MessageBox.show(msgOptions);
                component.loadMask.hide();
            };
            var verifySuccess = function (result, appletData, component){
                var msg = appletData.mimeMsg ? appletData.mimeMsg : appletData.exprMsg;
                if (result.totalRecords == 1 && result.records && result.records[0].get('success')) {
                    component.fromApplet(msg, result.records[0]);
                } else {
                    verifyFailed(result, component);
                }
            };

            store.proxy.verifyCertificates([data.signerCertificate], {
                success: verifySuccess.createDelegate(this, [data, extObj], true),
                failure: verifyFailed.createDelegate(this, [extObj], true)
            });
            
            return;
        }
// TODO: This can change in the future.
        extObj.fromApplet(data.mimeMsg ? data.mimeMsg : data.exprMsg);
    } 
    else {
        Tine.Expressomail.onSecurityAppletError();
        var exception = data.exception;
        switch (exception.message) {
            case 'OPERATION_CANCELLED' :
                Tine.log.debug(exception);
                break;
            default :
                var title;
                switch (exception.type) {
                    case 'SIGN': 
                        title = extObj.app.i18n._('Error Signing Message!');
                        break;
                    case 'ENCRYPT' :
                    case 'SIGN_AND_ENCRYPT' :
                        title = extObj.app.i18n._('Error Encrypting Message!');
                        break;
                    case 'DECRYPT' : 
                        title = extObj.app.i18n._('Error Decrypting Message!');
                        break;
                }
                
                Tine.log.error(exception.message);
                Ext.MessageBox.show({
                    title: title,
                    msg: exception.message,
                    buttons: Ext.Msg.OK
                });
        }
        
        extObj.loadMask.hide();
    }
};
