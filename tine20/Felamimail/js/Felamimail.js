/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Felamimail');

require('Felamimail/js/MailDetailsPanel');

require('Tinebase/js/Application');
require('Tinebase/js/ux/ItemRegistry');
require('Tinebase/js/widgets/MainScreen');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.Application
 * @extends     Tine.Tinebase.Application
 * 
 * <p>Felamimail application obj</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * 
 * @constructor
 * Create a new  Tine.Felamimail.Application
 */
Tine.Felamimail.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Mail')
     */
    addButtonText: 'New Mail',
    
    /**
     * @property checkMailsDelayedTask
     * @type Ext.util.DelayedTask
     */
    checkMailsDelayedTask: null,
    
    /**
     * @property defaultAccount
     * @type Tine.Felamimail.Model.Account
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

    routes: {
        'MailTo/:params': 'mailto'
    },

    /**
     * returns title (Email)
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Email');
    },
    
    /**
     * start delayed task to init folder store / updateFolderStore
     */
    init: function() {
        Tine.log.info('initialising app');
        this.checkMailsDelayedTask = new Ext.util.DelayedTask(this.checkMails, this);
        
        this.updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval')) * 60000;
        Tine.log.debug('user defined update interval is "' + this.updateInterval/1000 + '" seconds');
        
        this.defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
        Tine.log.debug('default account is "' + this.defaultAccount);

        // we need to do this deferred as the account model is created in the app starter and might not be available yet
        // TODO can we use promises here?
        this.initDeferred.defer(500, this);
    },

    initDeferred: function()
    {
        this.initAccountModel();

        if (window.isMainWindow) {
            if (Tine.Tinebase.appMgr.getActive() != this && this.updateInterval) {
                var delayTime = this.updateInterval/20;
                Tine.log.debug('start preloading mails in "' + delayTime/1000 + '" seconds');
                this.checkMailsDelayedTask.delay(delayTime);
            }

            this.showActiveVacation();
            this.initGridPanelHooks();
            this.registerProtocolHandler();
            this.registerQuickLookPanel();
        }
    },
    
    /**
     * show notification with active vacation information
     */
    showActiveVacation: function () {
        var accountsWithActiveVacation = this.getAccountStore().query('sieve_vacation_active', true);
        accountsWithActiveVacation.each(function(item) {
            Ext.ux.Notification.show(
                this.i18n._('Active Vacation Message'), 
                String.format(this.i18n._('Email account "{0}" has an active vacation message.'), item.get('name'))
            );
        }, this);
    },

    /**
     * TODO can this be done in a more elegant way?
     */
    initAccountModel: function()
    {
        /**
         * @type Object
         */
        Tine.Felamimail.Model.Account.prototype.lastIMAPException = null;


        /**
         * get the last IMAP exception
         *
         * @return {Object}
         */
        Tine.Felamimail.Model.Account.prototype.getLastIMAPException = function() {
            return this.lastIMAPException;
        };

        /**
         * returns sendfolder id
         * -> needed as trash is saved as globname :(
         */
        Tine.Felamimail.Model.Account.prototype.getSendFolderId = function() {
            var app = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr.get('Felamimail'),
                sendName = this.get('sent_folder'),
                accountId = this.id,
                send = sendName ? app.getFolderStore().queryBy(function(record) {
                    return record.get('account_id') === accountId && record.get('globalname') === sendName;
                }, this).first() : null;

            return send ? send.id : null;
        };

        /**
         * returns trashfolder id
         * -> needed as trash is saved as globname :(
         */
        Tine.Felamimail.Model.Account.prototype.getTrashFolderId = function() {
            var app = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr.get('Felamimail'),
                trashName = this.get('trash_folder'),
                accountId = this.id,
                trash = trashName ? app.getFolderStore().queryBy(function(record) {
                    return record.get('account_id') === accountId && record.get('globalname') === trashName;
                }, this).first() : null;

            return trash ? trash.id : null;
        };

        /**
         * set or clear IMAP exception and update imap_state
         *
         * @param {Object} exception
         */
        Tine.Felamimail.Model.Account.prototype.setLastIMAPException = function(exception) {
            this.lastIMAPException = exception;
            this.set('imap_status', exception ? 'failure' : 'success');
            this.commit();
        };
    },

    /**
     * initialize grid panel hooks
     */
    initGridPanelHooks: function() {
        var adbHook = new Tine.Felamimail.GridPanelHook({
            app: this,
            foreignAppName: 'Addressbook',
            modelName: 'Contact'
        });
        var adbHook = new Tine.Felamimail.GridPanelHook({
            app: this,
            foreignAppName: 'Addressbook',
            modelName: 'List'
        });
        var crmHook = new Tine.Felamimail.GridPanelHook({
            app: this,
            foreignAppName: 'Crm',
            contactInRelation: true,
            relationType: 'CUSTOMER',
            modelName: 'Lead',
            subjectField: 'lead_name'
        });
        var calHook = new Tine.Felamimail.GridPanelHook({
            app: this,
            foreignAppName: 'Calendar',
            modelName: 'Event',
            subjectFn: function(record) {
                var _ = window.lodash;
                return _.get(record, 'data.poll.name', _.get(record, 'data.summary', '') , '');
            },
            bodyFn: function(record) {
                if (record && record.hasPoll()) {
                    return String.format(this.app.i18n._('Poll URL: {0}'), record.getPollUrl());
                } else {
                    return '';
                }
            },
            massMailingFlagFn: function(record) {
                return record && record.hasPoll();
            },
            addMailFromRecord: function(mailAddresses, record) {
                Ext.each(record.get('attendee'), function(attender) {
                    Tine.log.debug('Tine.Felamimail.Application::initGridPanelHooks/addMailFromRecord() - Calendar attender:');
                    Tine.log.debug(attender);
                    if (attender.user_type == 'user' || attender.user_type == 'groupmember') {
                        this.addMailFromAddressBook(mailAddresses, attender.user_id);
                    }
                }, this);
            }
        });
    },

    registerProtocolHandler: function() {
        var text = String.format(this.i18n._('{0} as default mailer'), Tine.title),
            enabled = true; //Tine.Tinebase.configManager.get('registerMailToHandler', 'Felamimail');
        Tine.Felamimail.registerProtocolHandlerAction.setText(text);

        if (! (enabled && Ext.isFunction(navigator.registerProtocolHandler))) {
            Tine.Felamimail.registerProtocolHandlerAction.setHidden(true);
        }
    },

    /**
     * initialize Filemanager email QuickLook
     *
     * @returns {boolean}
     */
    registerQuickLookPanel: function() {
        if (! Tine.Tinebase.common.hasRight('run', 'Filemanager')) {
            // needs Filemanager
            return false;
        }

        Tine.Filemanager.QuickLookRegistry.registerContentType('message/rfc822', 'felamimaildetailspanel');
        Tine.Filemanager.QuickLookRegistry.registerExtension('eml', 'felamimaildetailspanel');
        return true;
    },

    /**
     * check mails
     * 
     * if no folder is given, we find next folder to update ourself
     * 
     * @param {Tine.Felamimail.Model.Folder} [folder]
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
            
            if (this.updateMessageCacheTransactionId && Tine.Felamimail.folderBackend.isLoading(this.updateMessageCacheTransactionId)) {
                var currentRequestFolder = this.folderStore.query('cache_status', 'pending').first(),
                    expectedResponseIn = Math.floor((this.updateMessageCacheTransactionExpectedResponse.getTime() - new Date().getTime())/1000);
            
                if (currentRequestFolder && (currentRequestFolder !== folder || expectedResponseIn > executionTime)) {
                    Tine.log.debug('aborting current update message request (expected response in ' + expectedResponseIn + ' seconds)');
                    Tine.Felamimail.folderBackend.abort(this.updateMessageCacheTransactionId);
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
            
            this.updateMessageCacheTransactionId = Tine.Felamimail.folderBackend.updateMessageCache(folder.id, executionTime, {
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
     * @return {Tine.Felamimail.FolderStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            Tine.log.debug('creating folder store');
            this.folderStore = new Tine.Felamimail.FolderStore({
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
            if (folder.isInbox() && timestamp.getElapsed() > this.updateInterval) {
                return true;
            } else if (timestamp.getElapsed() > (this.updateInterval * 5)) {
                return true;
            }
            return false;
        }, this);
        
        if (outdated.getCount() > 0) {
            Tine.log.debug('Still got ' + outdated.getCount() + ' outdated folders to update');
            
            // call Felamimail.getFolderStatus() with ids of outdated folders -> update folder store on success
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
            Tine.Felamimail.getFolderStatus(filter, this.onGetFolderStatusSuccess.createDelegate(this));
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
        Tine.log.debug('Tine.Felamimail.Application::onGetFolderStatusSuccess() -> Folder status update successful.');
        Tine.log.debug(response);
        
        if (response && response.length > 0) {
            Tine.log.debug('Tine.Felamimail.Application::onGetFolderStatusSuccess() -> Got ' + response.length + ' folders that need an update.');
            
            Ext.each(response, function(folder) {
                var folderToUpdate = this.folderStore.getById(folder.id);
                folderToUpdate.set('cache_status', 'pending');
            }, this);
            
            this.checkMailsDelayedTask.delay(1000);
        } else {
            Tine.log.debug('Tine.Felamimail.Application::onGetFolderStatusSuccess() -> No folders for update found.');
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
                Tine.Felamimail.folderBackend.handleRequestException(exception);
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
        Tine.log.info('activating felamimail now');
        // abort preloading/old actions and force fresh fetch
        this.checkMailsDelayedTask.delay(0);
    },
    
    /**
     * on update folder
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolder: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT) {
            if (record.isModified('cache_status')) {
                Tine.log.info('Tine.Felamimail.Application::onUpdateFolder(): Folder "' + record.get('localname') + '" updated with cache_status: ' + record.get('cache_status'));
                
                // as soon as we get a folder with status != complete we need to trigger checkmail soon!
                if (['complete', 'pending'].indexOf(record.get('cache_status')) === -1) {
                    this.checkMailsDelayedTask.delay(1000);
                }
                
                // this should not be shown for messages that have been marked "unread" by another client
                if (record.isInbox() && record.isModified('cache_unreadcount') && record.isModified('cache_totalcount')) {
                    this.showNewMessageNotification(record);
                }
                
                if (record.isModified('imap_lastmodseq')) {
                    Tine.log.info('Tine.Felamimail.Application::onUpdateFolder(): flags have changed');
                    // TODO this needs to be reviewed: grid is loaded way to often, selection is lost. maybe we need to have the updated flags here or the messages with updated flags
                    // TODO maybe server needs to be reviewed, too as flags should not be synced if the tine client herself changes the flags
                    // TODO maybe we need cache_lastmodseq, too
                    //this.getMainScreen().getCenterPanel().getStore().reload();
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
     * checks default account id
     * 
     * @param {String} accountId
     * @return {Boolean}
     */
    isDefaultAccountId: function(accountId) {
        return accountId == Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    },
    
    /**
     * show notification for new messages
     * 
     * @param {Tine.Felamimail.Model.Folder} record
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
     * @param {Tine.Felamimail.Model.Folder} record
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
     * @return {Tine.Felamimail.Model.Account}
     */
    getActiveAccount: function() {
        var account = null;
            
        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            account = treePanel.getActiveAccount();
        }
        
        if (!account) {
            account = this.getAccountStore().getById(Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount'));
        }
        
        if (!account) {
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
                fields: Tine.Felamimail.Model.Account,
                data: Tine.Felamimail.registry.get('accounts'),
                autoLoad: true,
                id: 'id',
                root: 'results',
                totalProperty: 'totalcount',
                proxy: Tine.Felamimail.accountBackend,
                reader: Tine.Felamimail.accountBackend.getReader(),
                listeners: {
                    scope: this,
                    'add': function (store, records) {
                        Tine.log.info('Account added: ' + records[0].get(Tine.Felamimail.Model.Account.getMeta('titleProperty')));
                        this.getMainScreen().getCenterPanel().action_write.setDisabled(! this.getActiveAccount());
                    },
                    'remove': function (store, record) {
                        Tine.log.info('Account removed: ' + record.get(Tine.Felamimail.Model.Account.getMeta('titleProperty')));
                        this.getMainScreen().getCenterPanel().action_write.setDisabled(! this.getActiveAccount());
                    }
                }
            });
        } 
    
        return this.accountStore;
    },

    /**
     * gets default signature of given account
     *
     * @param {Tine.Felamimail.Model.Account} account
     * @return {Tine.Felamimail.Model.Signature}
     */
    getDefaultSignature: function(account) {
        account = _.isString(account) ? this.getAccountStore().getById(account) : account;

        let signatures = _.get(account, 'data.signatures', []);
        let signature =_.find(signatures, (s) => {return !!+_.get(s, 'is_default')}) || signatures[0];

        return signature ?
            Tine.Tinebase.data.Record.setFromJson(signature, Tine.Felamimail.Model.Signature) :
            null;
    },

    /**
     * show felamimail credentials dialog
     * 
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String} username [optional]
     */
    showCredentialsDialog: function(account, username) {
        Tine.Felamimail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
            windowTitle: String.format(this.i18n._('IMAP Credentials for {0}'), account.get('name')),
            appName: 'Felamimail',
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
    },

    /**
     * compose mail via mailto link
     *
     * @param params from mailto link
     */
    mailto: function(paramString) {
        var decodedParamString = decodeURIComponent(paramString).replace(/mailto:/, ''),
            parts = decodedParamString.split(/\?|&/),
            params = {};

        Ext.each(parts, function(part){
            var pair = part.split('='),
                param = pair[1] ? pair[0] : 'to',
                value = decodeURIComponent(pair[1] ? pair[1] : pair[0]);

            value = param.match(/(body)|(subject)/) ? value : value.split(',');
            param = param == 'body' ? 'msgBody' : param;

            params[param] = value.length > 1 ? value : value[0];
        });

        var activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
        params['accountId'] = activeAccount ? activeAccount.id : null;

        //@TODO: remove old url from popstate. its ugly!
        Tine.Tinebase.MainScreenPanel.show(this);
        Tine.Felamimail.MessageEditDialog.openWindow(params);
    }
});

Tine.Felamimail.registerProtocolHandlerAction = new Ext.Action({
    iconCls: 'FelamimailIconCls',
    handler: function() {
        var url = Tine.Tinebase.common.getUrl() + '#Felamimail/MailTo/%s';
        navigator.registerProtocolHandler('mailto', url, Ext.util.Format.stripTags(Tine.title));
    }
});
Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu.userActions', Tine.Felamimail.registerProtocolHandlerAction);


/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.MainScreen
 * @extends Tine.widgets.MainScreen
 * 
 * MainScreen Definition
 */ 
Tine.Felamimail.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
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
Tine.Felamimail.loadFlagsStore = function(reload) {
    
    var store = Ext.StoreMgr.get('FelamimailFlagsStore');
    
    if (!store) {
        // create store (get from initial registry data)
        store = new Ext.data.JsonStore({
            fields: Tine.Felamimail.Model.Flag,
            data: Tine.Felamimail.registry.get('supportedFlags'),
            autoLoad: true,
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount'
        });
        
        Ext.StoreMgr.add('FelamimailFlagsStore', store);
    } 

    return store;
};

/**
 * gets default signature text of given account
 *
 * @param {String|Tine.Felamimail.Model.Account} account
 * @return {String}
 */
Tine.Felamimail.getSignature = function(account, signature) {
    let app = Tine.Tinebase.appMgr.get('Felamimail');
    let signatureText = '';

    account = _.isString(account) ? app.getAccountStore().getById(account) : account;
    account = account || Tine.Felamimail.getActiveAccount();

    if (account) {
        Tine.log.info('Tine.Felamimail.getSignature() - Fetch signature of account ' + account.id + ' (' + account.name + ')');
        signature = signature || app.getDefaultSignature(account);

        if (signature && signature.id !== 'none') {
            // NOTE: signature is always in html, nl2br here would cause duplicate linebreaks!
            signatureText = '<br><br><span class="felamimail-body-signature">-- <br>' + _.get(signature, 'data.signature', '') + '</span>';
        }
    }

    return signatureText;
};

/**
 * get email string (n_fileas <email@host.tld>) from contact
 * 
 * @param {Tine.Addressbook.Model.Contact} contact
 * @return {String}
 */
Tine.Felamimail.getEmailStringFromContact = function(contact) {
    var result = contact.get('n_fileas') + ' <';
    result += contact.getPreferredEmail();
    result += '>';
    
    return result;
};

/**
 * generic exception handler for felamimail (used by folder and message backends and updateMessageCache)
 * 
 * TODO move all 902 exception handling here!
 * TODO invent requery on 902 with cred. dialog
 * 
 * @param {Tine.Exception|Object} exception
 */
Tine.Felamimail.handleRequestException = function(exception) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }

    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);
    
    var app = Tine.Tinebase.appMgr.get('Felamimail');
    
    switch (exception.code) {
        case 910: // Felamimail_Exception_IMAP
        case 911: // Felamimail_Exception_IMAPServiceUnavailable
            Ext.Msg.show({
               title:   app.i18n._('IMAP Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to IMAP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
            
        case 912: // Felamimail_Exception_IMAPInvalidCredentials
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
            
        case 913: // Felamimail_Exception_IMAPFolderNotFound
            Ext.Msg.show({
               title:   app.i18n._('IMAP Error'),
               msg:     app.i18n._('One of your folders was deleted or renamed by another client. Please update the folder list of this account.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            // TODO reload account root node
            break;
            
        case 914: // Felamimail_Exception_IMAPMessageNotFound
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
            
        case 920: // Felamimail_Exception_SMTP
            Ext.Msg.show({
               title:   app.i18n._('SMTP Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to SMTP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
            
        case 930: // Felamimail_Exception_Sieve
            Ext.Msg.show({
               title:   app.i18n._('Sieve Error'),
               msg:     exception.message ? exception.message : app.i18n._('No connection to Sieve server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        case 931: // Felamimail_Exception_SievePutScriptFail
            Ext.Msg.show({
               title:   app.i18n._('Save Sieve Script Error'),
               msg:     app.i18n._('Could not save script on Sieve server.') + (exception.message ? ' (' + exception.message + ')' : ''),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;

        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};

Ext.ns('Tine.Felamimail.Admin.emailaccounts');
/**
 * emailaccounts 'mainScreen' (Admin grid panel)
 *
 * @static
 *
 * TODO move to a separate file
 */
Tine.Felamimail.Admin.emailaccounts.show = function () {
    var app = Tine.Tinebase.appMgr.get('Felamimail');
    if (! Tine.Felamimail.Admin.emailAccountsGridPanel) {
        Tine.Felamimail.Admin.emailaccountsBackend = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'EmailAccount',
            recordClass: Tine.Felamimail.Model.Account,
            idProperty: 'id'
        });
        Tine.Felamimail.Admin.emailAccountsGridPanel = new Tine.Felamimail.AccountGridPanel({
            recordProxy: Tine.Felamimail.Admin.emailaccountsBackend,
            asAdminModule: true
        });
    } else {
        Tine.Felamimail.Admin.emailAccountsGridPanel.loadGridData.defer(100, Tine.Felamimail.Admin.emailAccountsGridPanel, []);
    }

    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Felamimail.Admin.emailAccountsGridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Felamimail.Admin.emailAccountsGridPanel.actionToolbar, true);
};
