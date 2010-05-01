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
     * refresh time in milliseconds
     * 
     * @property checkMailDelayTime
     * @type Number
     */
    checkMailDelayTime: 20000, // 20 seconds

    /**
     * @property checkMailsDelayedTask
     * @type Ext.util.DelayedTask
     */
    checkMailsDelayedTask: null,
    
    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,
    
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
        this.checkMailsDelayedTask = new Ext.util.DelayedTask(this.checkMails, this);
        
        var delayTime = (Tine.Tinebase.appMgr.getActive() == this) ? /*1000*/ 0 : 15000;
        this.getFolderStore.defer(delayTime, this);
    },
    
    /**
     * check mails delayed task
     */
    checkMails: function() {
        this.getFolderStore();
        this.updateFolderStatus();
    },
    
    /**
     * get folder store
     * 
     * @return {Tine.Felamimail.FolderStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            this.folderStore = new Tine.Felamimail.FolderStore({
                listeners: {
                    scope: this,
                    update: this.onUpdateFolder
                }
            });
            
            var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
            if (defaultAccount != '') {
                this.folderStore.load({
                    path: '/' + defaultAccount,
                    params: {filter: [
                        {field: 'account_id', operator: 'equals', value: defaultAccount},
                        {field: 'globalname', operator: 'equals', value: ''}
                    ]},
                    callback: this.onStoreInitialLoad.createDelegate(this)
                });
            }
        }
        
        return this.folderStore;
    },
    
    /**
     * initial load of folder store
     *
     * @param {} record
     * @param {} options
     * @param {} success
     * 
     * TODO this could be obsolete, try to make it work without the initial load
     */
    onStoreInitialLoad: function(record, options, success) {
        var folderName = 'INBOX';
        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            var node = treePanel.getSelectionModel().getSelectedNode();
            if (node) {
                folderName = node.attributes.globalname;
            }
        } 
            
        this.updateFolderStatus(folderName);
    },
    
    /**
     * on update folder
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    onUpdateFolder: function(store, record, operation) {
        
        var changes = record.getChanges();
        
        if (record.isModified('cache_recentcount') && changes.cache_recentcount > 0) {
            //console.log('show notification');
            Ext.ux.Notification.show(
                this.i18n._('New mails'), 
                String.format(this.i18n._('You got {0} new mail(s) in Folder {1}.'), 
                    changes.cache_recentcount, record.get('localname'))
            );
        }
    },

    /**
     * set this.checkMailDelayTime
     * 
     * @param {} mode fast|slow
     */
    setCheckMailsRefreshTime: function(mode) {
        if (mode == 'slow') {
            // get folder update interval from preferences
            var updateInterval = parseInt(Tine.Felamimail.registry.get('preferences').get('updateInterval'));
            if (updateInterval > 0) {
                // convert to milliseconds
                this.checkMailDelayTime = 60000*updateInterval;
            } else {
                this.checkMailDelayTime = 1200000; // 20 minutes
            }
        } else {
            this.checkMailDelayTime = 25000; // 25 seconds
        }
        
        return this.checkMailDelayTime;
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {String/Tine.Felamimail.Model.Folder} [folder]
     * 
     * TODO abort request if another folder has been clicked
     * TODO move request to record proxy
     */
    updateFolderStatus: function(folder) {
        
        if (Ext.isString(folder)) {
            var index = this.getFolderStore().find('globalname', folder);
            if (index >= 0) {
                folder = this.getFolderStore().getAt(index);
            }
        } 
        
        //console.log(folder);
        
        var folderIds, accountId;
        if (! folder || typeof folder.get !== 'function') {
            var account = this.getActiveAccount();
            accountId = account.id;
            folderIds = this.getFoldersForUpdateStatus(accountId);
            folder = null;
        } else {
            folderIds = [folder.id];
            accountId = folder.get('account_id');
        }
        
        // don't update if we got no folder ids 
        if (folderIds.length > 0) {
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateFolderStatus',
                    folderIds: folderIds,
                    accountId: accountId
                },
                scope: this,
                timeout: 60000, // 1 minute
                success: function(result, request) {
                    var result = Tine.Felamimail.folderBackend.getReader().readRecords(Ext.util.JSON.decode(result.responseText));
                    //console.log(result);
                    for (var i = 0; i < result.records.length; i++) {
                        this.updateFolderInStore(result.records[i]);
                    }
                    var result = this.updateMessageCache(folder);
                },
                failure: this.handleFailure
            });
        } else {
            this.updateMessageCache();
        }
    },
    
    /**
     * update folder status of all visible / all node in one level or one folder(s)
     * 
     * @param {Tine.Felamimail.Model.Folder} [folder]
     * @return boolean true if caching is complete
     */
    updateMessageCache: function(folder) {

        /////////// select folder to update message cache for
        
        var refreshRate = 'fast';
        var folderId = null;
        var singleFolderUpdate = false;
        if (! folder && this.getMainScreen().getTreePanel()) {
            // get active node
            var node = this.getMainScreen().getTreePanel().getSelectionModel().getSelectedNode();
            if (node && node.attributes.folder_id) {
                folder = this.folderStore.getById(node.id);
            }
        } else {
            singleFolderUpdate = true;
        }
        
        //console.log(folder);
        if (folder && (folder.get('cache_status') == 'incomplete' || folder.get('cache_status') == 'invalid')) {
            folderId = folder.id;
            refreshRate = 'fast';
            
        } else if (! singleFolderUpdate) {
            folderId = this.getNextFolderToUpdate();
            //console.log('folder id:' + folderId);
            if (folderId === null) {
                // nothing left to do for the moment! -> set refresh rate to 'slow'
                //console.log('finished for the moment');
                refreshRate = 'slow';
            } else {
                refreshRate = 'fast';
            }
        }
        
        //console.log('update folder:' + folderId);
        if (folderId !== null) {
            /////////// do request
            
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.updateMessageCache',
                    folderId: folderId,
                    time: 10
                },
                timeout: 60000, // 1 minute
                scope: this,
                success: function(result, request) {
                    var newRecord = Tine.Felamimail.folderBackend.recordReader(result);
                    //console.log(newRecord);
                    this.updateFolderInStore(newRecord);
                },
                failure: function(response, options) {
                    // do nothing
                }
            });           
        }
        
        // TODO add folder as arg ?
        // start delayed task again
        var delayTime = this.setCheckMailsRefreshTime(refreshRate);
        //console.log('start delayed task again. time: ' + delayTime);
        this.checkMailsDelayedTask.delay(delayTime/*, folder?*/);
    },
   
    /**
     * get all folders to update of account in store
     * 
     * @param {String} accountId
     */
    getFoldersForUpdateStatus: function(accountId) {
        var result = [];

        //console.log('# records: ' + this.folderStore.getCount());
        //console.log(this.folderStore);
        var accountFolders = this.getFolderStore().queryBy(function(record) {
            var timestamp = record.get('imap_timestamp');
            return (record.get('account_id') == accountId && (timestamp == '' || timestamp.getElapsed() > 300000)); // 5 minutes
        });
        //console.log(accountFolders);
        accountFolders.each(function(record) {
            result.push(record.id);
        });
        
        return result;
    },
    
    /**
     * update folder in store
     * 
     * @param {Tine.Felamimail.Model.Folder} folderData
     * @return {Tine.Felamimail.Model.Folder}
     * 
     * TODO iterate record fields -> do it like this:
     * Ext.copyTo({}, attr, Tine.Tinebase.Model.Container.getFieldNames()); 
     */
    updateFolderInStore: function(newFolder) {
        
        var folder = this.getFolderStore().getById(newFolder.id);
        
        if (! folder) {
            return newFolder;
        }
        
        var fieldsToUpdate = ['imap_status','imap_timestamp','imap_uidnext','imap_uidvalidity','imap_totalcount',
            'cache_status','cache_uidnext','cache_totalcount', 'cache_recentcount','cache_unreadcount','cache_timestamp',
            'cache_job_actions_estimate','cache_job_actions_done'];

        // update folder store
        for (var j = 0; j < fieldsToUpdate.length; j++) {
            folder.set(fieldsToUpdate[j], newFolder.get(fieldsToUpdate[j]));
        }
        
        return folder;
    },
    
    /**
     * get next folder for update message cache
     * 
     * @return {String|null}
     */
    getNextFolderToUpdate: function() {
        var result = null;
        
        var account = this.getActiveAccount();
        if (account !== null) {
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
        }
        
        return result;
    },
    
    /**
     * handle failure to show credentials dialog if imap login failed
     * 
     * @param {String}  response
     * @param {Object}  options
     * @param {Node}    node optional account node
     * @param {Boolean} handleException
     */
    handleFailure: function(response, options) {
        var responseText = Ext.util.JSON.decode(response.responseText);
        
        if (responseText.data.code == 902) {
            
            var jsonData = Ext.util.JSON.decode(options.jsonData);
            var accountId = (jsonData.params.accountId) ? jsonData.params.accountId : Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
            var account = Tine.Felamimail.loadAccountStore().getById(accountId);
                        
            if (! Tine.Felamimail.credentialsDialog) {
                Tine.Felamimail.credentialsDialog = Tine.widgets.dialog.CredentialsDialog.openWindow({
                    windowTitle: String.format(this.i18n._('IMAP Credentials for {0}'), account.get('name')),
                    appName: 'Felamimail',
                    credentialsId: accountId,
                    i18nRecordName: this.i18n._('Credentials'),
                    recordClass: Tine.Tinebase.Model.Credentials,
                    listeners: {
                        scope: this,
                        'update': function(data) {
                            this.checkMails();
                        }
                    }
                });
            }
            
        } else {
            Ext.Msg.show({
               title:   this.i18n._('Error'),
               msg:     (responseText.data.message) ? responseText.data.message : this.i18n._('No connection to IMAP server.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });

            // TODO call default exception handler on specific exceptions?
            //var exception = responseText.data ? responseText.data : responseText;
            //Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
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
        return this.getContainerTreePanel();
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
        
    var result = '';
    
    if (! id || id == 'default') {
        id = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
    }
    
    var defaultAccount = Tine.Felamimail.loadAccountStore().getById(id);
    var signature = (defaultAccount) ? defaultAccount.get('signature') : '';
    if (signature && signature != '') {
        signature = Ext.util.Format.nl2br(signature);
        result = '<br><br><span id="felamimail-body-signature">--<br>' + signature + '</span>';
    }
    
    return result;
}

