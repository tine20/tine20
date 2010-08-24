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
 * @class       Tine.Felamimail.Application
 * @extends     Tine.Tinebase.Application
 * 
 * <p>Felamimail application obj</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * 
 * @constructor
 * Create a new  Tine.Felamimail.Application
 */
Tine.Felamimail.Application = Ext.extend(Tine.Tinebase.Application, {
    
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
     * @property updateInterval user defined update interval (milliseconds)
     * @type Number
     */
    updateInterval: null,
    
    /**
     * transaction id of current update message cache request
     * @type Number
     */
    updateMessageCacheTransactionId: null,
    
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
        
        if (window.isMainWindow && Tine.Tinebase.appMgr.getActive() != this && this.updateInterval) {
            var delayTime = this.updateInterval/20;
            
            Tine.log.debug('start preloading mails in "' + delayTime/1000 + '" seconds');
            this.checkMailsDelayedTask.delay(delayTime);
        }
        
        this.showActiveVacation();
    },
    
    /**
     * show a message box with active vacation information
     * 
     * TODO only show message for first account?
     */
    showActiveVacation: function () {
        var accountsWithActiveVacation = Tine.Felamimail.loadAccountStore().query('sieve_vacation_active', true);
        if (window.isMainWindow && accountsWithActiveVacation.getCount() > 0) {
            var first = accountsWithActiveVacation.first();
            Ext.Msg.show({
               title:   this.i18n._('Active Vacation Message'),
               msg:     String.format(this.i18n._('Email account "{0}" has an active vacation message.'), first.get('name')),
               icon:    Ext.MessageBox.INFO,
               buttons: Ext.Msg.OK
            });
        }
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
            Tine.log.debug('no folders in store yet, fetching first level...');
            this.getFolderStore().asyncQuery('parent_path', '/' + this.defaultAccount, this.checkMails.createDelegate(this, []), [], this, this.getFolderStore());
            return;
        }
        
        Tine.log.info('checking mails' + (folder ? ' for folder ' + folder.get('localname') : '') + ' now: ' + new Date());
        
        // if no folder is given, see if there is a folder to check in the folderstore
        if (! folder) {
            folder = this.getNextFolderToUpdate();
        }
        
        if (folder) {
            if (this.updateMessageCacheTransactionId && Tine.Felamimail.folderBackend.isLoading(this.updateMessageCacheTransactionId)) {
                var currentRequestFolder = this.folderStore.query('cache_status', 'pending').first();
                
                if (currentRequestFolder && currentRequestFolder !== folder) {
                    Tine.log.debug('aborting current update message request');
                    Tine.Felamimail.folderBackend.abort(this.updateMessageCacheTransactionId);
                    currentRequestFolder.set('cache_status', 'incomplete');
                    currentRequestFolder.commit();
                } else {
                    Tine.log.debug('a request updateing message cache for folder "' + folder.get('localname') + '" is in progress -> wait for request to return');
                    return;
                }
            }
            
            var executionTime = folder.isCurrentSelection() ? 10 : Math.min(this.updateInterval, 120);
            Tine.log.debug('updateing message cache for folder "' + folder.get('localname') + '" with ' + executionTime + ' seconds max execution time');
            
            folder.set('cache_status', 'pending');
            folder.commit();
            
            this.updateMessageCacheTransactionId = Tine.Felamimail.folderBackend.updateMessageCache(folder.id, executionTime, {
                scope: this,
                callback: callback,
                failure: this.onBackgroundRequestFail,
                success: function(folder) {
                    Tine.Felamimail.loadAccountStore().getById(folder.get('account_id')).setLastIMAPException(null);
                    this.getFolderStore().updateFolder(folder);
                    
                    if (folder.get('cache_status') === 'updating') {
                        Tine.log.debug('updateing message cache for folder "' + folder.get('localname') + '" is in progress on the server (folder is locked)');
                        return this.checkMailsDelayedTask.delay(this.updateInterval);
                    }
                    this.checkMailsDelayedTask.delay(0);
                }
            });
        } else {
            Tine.log.info('nothing more to do -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
            if (this.updateInterval > 0) {
                this.checkMailsDelayedTask.delay(this.updateInterval);
            }
        }
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
        
        // current selection has highes prio!
        if (currFolder && currFolder.needsUpdate(this.updateInterval)) {
            return currFolder;
        }
        
        // check if inboxes need updates
        var inboxes = this.folderStore.queryBy(function(folder) {
            return Ext.util.Format.lowercase(folder.get('localname')) === 'inbox' && folder.needsUpdate(this.updateInterval);
        }, this);
        if (inboxes.getCount() > 0) {
            return inboxes.first();
        }
        
        // check for incompletes
        var incompletes = this.folderStore.queryBy(function(folder) {
            return folder.get('cache_status') !== 'complete';
        }, this);
        if (incompletes.getCount() > 0) {
            return incompletes.first();
        }
        
        // check for outdated
        var outdated = this.folderStore.queryBy(function(folder) {
            var timestamp = folder.get('client_access_time');
            return ! Ext.isDate(timestamp) || timestamp.getElapsed() > this.updateInterval;
        }, this);
        if (outdated.getCount() > 0) {
            return outdated.first();
        }
        
        // nothing to update
        return null;
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
        var accountId   = currentRequestFolder.get('account_id'),
            account     = accountId ? Tine.Felamimail.loadAccountStore().getById(accountId): null,
            imapStatus  = account ? account.get('imap_status') : null;
            
        if (account) {
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
        
        Tine.log.info('Background update failed (' + exception.message + ') for folder ' + currentRequestFolder.get('globalname') 
            + ' -> will check mails again in "' + this.updateInterval/1000 + '" seconds');
        Tine.log.debug(exception);
        this.checkMailsDelayedTask.delay(this.updateInterval);
    },
    
    /**
     * executed right before this app gets activated
     */
    onBeforeActivate: function() {
        Tine.log.info('activating felamimail now');
        // abort preloading/old actions and force frech fetch
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
            Tine.log.info('Folder "' + record.get('localname') + '" updated with cache_status: ' + record.get('cache_status'));
            
            // as soon as we get a folder with status != complete we need to trigger checkmail soon!
            if (['complete', 'pending'].indexOf(record.get('cache_status')) === -1) {
                this.checkMailsDelayedTask.delay(1000);
            }
            
            var changes = record.getChanges();
            
            if (record.isModified('cache_recentcount') && changes.cache_recentcount > 0) {
                //console.log('show notification');
                Ext.ux.Notification.show(
                    this.i18n._('New mails'), 
                    String.format(this.i18n._('You got {0} new mail(s) in Folder {1}.'), 
                        changes.cache_recentcount, record.get('localname'))
                );
            }
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
        
        if (account === null) {
            account = Tine.Felamimail.loadAccountStore().getById(Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount'));
        }
        
        return account;
    }
});

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
 * get account store
 *
 * @param {Boolean} reload
 * @return {Ext.data.JsonStore}
 */
Tine.Felamimail.loadAccountStore = function(reload) {
    
    var store = Ext.StoreMgr.get('FelamimailAccountStore');
    
    if (!store) {
        
        //console.log(Tine.Felamimail.registry.get('accounts'));
        
        // create store (get from initial data)
        store = new Ext.data.JsonStore({
            fields: Tine.Felamimail.Model.Account,

            // initial data from http request
            data: Tine.Felamimail.registry.get('accounts'),
            autoLoad: true,
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount',
            proxy: Tine.Felamimail.accountBackend,
            reader: Tine.Felamimail.accountBackend.getReader()
        });
        
        Ext.StoreMgr.add('FelamimailAccountStore', store);
    } 

    return store;
};

/**
 * add signature (get it from default account settings)
 * 
 * @param {String} id
 * @return {String}
 */
Tine.Felamimail.getSignature = function(id) {
        
    var result = '',
        activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getMainScreen().getTreePanel().getActiveAccount();
        
    id = id || (activeAccount ? activeAccount.id : 'default');
    
    if (id === 'default') {
        id = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    }
    
    var defaultAccount = Tine.Felamimail.loadAccountStore().getById(id);
    var signature = (defaultAccount) ? defaultAccount.get('signature') : '';
    if (signature && signature != '') {
        signature = Ext.util.Format.nl2br(signature);
        result = '<br><br><span id="felamimail-body-signature">--<br>' + signature + '</span>';
    }
    
    return result;
};

/**
 * generic exception handler for felamimail (used by folder and message backends and updateMessageCache)
 * 
 * TODO move all 902 exception handling here!
 * TODO invent requery on 902 with cred. dialog
 * 
 * @param {Tine.Exception} exception
 */
Tine.Felamimail.handleRequestException = function(exception) {
    Tine.log.warn('request exception :');
    Tine.log.warn(exception);
    
    var app = Tine.Tinebase.appMgr.get('Felamimail');
    
    switch(exception.code) {
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
                account     = accountId ? Tine.Felamimail.loadAccountStore().getById(accountId): null,
                imapStatus  = account ? account.get('imap_status') : null;
                
            if (account) {
                Tine.Felamimail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
                    title: String.format(app.i18n._('IMAP Credentials for {0}'), account.get('name')),
                    appName: 'Felamimail',
                    credentialsId: accountId,
                    i18nRecordName: app.i18n._('Credentials'),
                    recordClass: Tine.Tinebase.Model.Credentials,
                    record: new Tine.Tinebase.Model.Credentials({
                        id: account.id,
                        username: exception.username ? exception.username : ''
                    }),
                    listeners: {
                        scope: this,
                        'update': function(data) {
                            app.checkMailsDelayedTask.delay(0);
                        }
                    }
                });
            } else {
                exception.code = 910;
                return this.handleRequestException(exception);
            }
            break;
            
        case 913: // Felamimail_Exception_IMAPFolderNotFound
            Ext.Msg.show({
               title:   app.i18n._('IMAP Error'),
               msg:     app.i18n._('One of your folders was deleted from an other client, please reload you browser'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
            
        case 404: 
        case 914: // Felamimail_Exception_IMAPMessageNotFound
            // do nothing, this exception is handled by Tine.Tinebase.ExceptionHandler.handleRequestException
            exception.code = 404;
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
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

        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};
