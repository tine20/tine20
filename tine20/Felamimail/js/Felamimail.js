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
 * TODO         move store to extra file/class
 * TODO         make message caching flow work again
 * TODO         add doQuery fn to store to decide if we need to get data from local or remote
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
        
        var delayTime = (Tine.Tinebase.appMgr.getActive() == this) ? 1000 : 15000;
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
     * @return {Ext.data.JsonStore}
     */
    getFolderStore: function() {
        if (! this.folderStore) {
            this.folderStore = new Ext.data.Store({
                fields: Tine.Felamimail.Model.Folder,
                listeners: {
                    scope: this,
                    update: this.onUpdateFolder,
                    beforeload: this.onStoreBeforeLoad,
                    load: this.onStoreLoad
                },
                proxy: Tine.Felamimail.folderBackend,
                reader: Tine.Felamimail.folderBackend.getReader(),
                queriesDone: new Ext.util.MixedCollection(),
                asyncQuery: function(field, value, callback, args, scope, store) {
                    
                    var result = store.query(field, value);
                    var queryObject = {field: field, value: value};
                    
                    if (result.getCount() == 0 && ! store.queriesDone.contains(queryObject)) {
                        // TODO do async request (only once)
                        console.log('async');
                        
                        store.queriesDone.add(queryObject);
                    } else {
                        //console.log('call callback fn');
                        if (Ext.isFunction(callback)) {
                            args.push(result);
                            callback.apply(scope, args);
                        }
                    }
                }
            });
            
            var defaultAccount = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
            this.folderStore.load({
                path: '/' + defaultAccount,
                params: {filter: [
                    {field: 'account_id', operator: 'equals', value: defaultAccount},
                    {field: 'globalname', operator: 'equals', value: ''}
                ]},
                callback: this.onStoreInitialLoad.createDelegate(this)
            });
        }
        
        return this.folderStore;
    },
    
    /**
     * initial load of folder store
     *
     * @param {} record
     * @param {} options
     * @param {} success
     */
    onStoreInitialLoad: function(record, options, success) {
        var folderName = 'INBOX';
        var treePanel = this.getMainScreen().getTreePanel();
        if (treePanel && treePanel.rendered) {
            var node = treePanel.getSelectionModel().getSelectedNode();
            if (node) {
                folderName = node.attributes.globalname;
            }
        } else {
            this.updateFolderStatus([folderName]);
        }        
    },
    
    /**
     * before load handler of folder store
     * 
     * @param {} store
     * @param {} options
     */
    onStoreBeforeLoad: function(store, options) {
        // set options.path
        //console.log(options);
    },
    
    /**
     * 
     * @param {} store
     * @param {} records
     * @param {} success
     */
    onStoreLoad: function(store, records, options) {
        Ext.each(records, function(record) {
            // compute paths
            var parent_path = options.path;
            record.set('parent_path', parent_path);
            record.set('path', parent_path + '/' + record.id);
            
        }, this);
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
     * delayed task function / messages
     * - calls updateMessageCache
     * 
     * @deprecated
     */
    updateMessages: function() {
        var refreshMode = (this.updateMessageCache()) ? 'slow' : 'fast';
        this.setCheckMailsRefreshTime(refreshMode);
        //console.log('start mc task with delay ' + this.checkMailDelayTime)
        
        this.updateMessagesTask.delay(this.checkMailDelayTime);
    },
    
    /**
     * delayed task function / folders
     * - calls updateFolderStatus
     * 
     * TODO start delayed message update task here?
     * @deprecated
     */
    updateFolders: function() {
        this.updateFolderStatus();
        /*
        if (this.updateMessagesTask !== null) {
            this.setCheckMailsRefreshTime('fast');
            this.updateMessagesTask.delay(this.checkMailDelayTime);
        }
        */
        this.updateFoldersTask.delay(this.updateFolderRefreshTime);
    },
    
    
    /**
     * set this.checkMailDelayTime
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
                // TODO what shall we de if pref is set to 0?
                this.checkMailDelayTime = 1200000; // 20 minutes
            }
        } else {
            this.checkMailDelayTime = 20000; // 20 seconds
        }
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
            folder = this.getFolderStore().find('globalname', folder);
        }
        
        var folderIds;
        if (! folder || typeof folder.get !== 'function') {
            var treePanel = this.getMainScreen().getTreePanel(),
                accountId;
                
            if (treePanel && treePanel.rendered) {
                accountId = treePanel.getActiveAccount().id;
            } else {
                accountId = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount')
            }
            folderIds = this.getFoldersForUpdateStatus(accountId);
            folder = null;
        } else {
            folderIds = [folder.id];
        }
        
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.updateFolderStatus',
                folderIds: folderIds,
                accountId: accountId
            },
            scope: this,
            timeout: 60000, // 1 minute
            success: function(_result, _request) {
                var result = Tine.Felamimail.folderBackend.getReader().readRecords(Ext.util.JSON.decode(_result.responseText));
                for (var i = 0; i < result.records.length; i++) {
                    this.updateFolderInStore(result.records[i]);
                }
                var result = this.updateMessageCache(folder);
            },
            failure: function() {
                // do nothing
            }
        });
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
        if (! folder && false /*this.getTreePanel()*/) {
            // get active node
            var node = this.getTreePanel().getSelectionModel().getSelectedNode();
            if (node && node.attributes.folder_id) {
                folder = this.folderStore.getById(node.id);
            }
        } else {
            singleFolderUpdate = true;
        }
        
        //console.log(folder);
        if (folder && (folder.get('cache_status') == 'incomplete' || folder.get('cache_status') == 'invalid')) {
            folderId = folder.id;
            
        } else if (! singleFolderUpdate) {
            folderId = this.getNextFolderToUpdate();
            if (folderId === null) {
                // nothing left to do for the moment! -> set refresh rate to 'slow'
                //console.log('finished for the moment');
                refreshRate = 'slow';
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
                scope: this,
                success: function(result, request) {
                    var newRecord = Tine.Felamimail.folderBackend.recordReader(result);
                    this.updateFolderInStore(newRecord);
                },
                failure: function(response, options) {
                    // call handle failure in tree loader and show credentials dialog / reload account afterwards
                    // TODO do nothing?
                    if (node.parentNode) {
                        this.loader.handleFailure(response, options, node.parentNode, false);
                    }
                }
            });           
        }
        
        // TODO add folder as arg
        var delayTime = this.setCheckMailsRefreshTime(refreshRate);
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
     * TODO iterate record fields
     */
    updateFolderInStore: function(newFolder) {
        
        var folder = this.getFolderStore().getById(newFolder.id);
        
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

/**
 * @namespace Tine.Felamimail
 * @class Tine.Felamimail.MainScreen
 * @extends Tine.Tinebase.widgets.app.MainScreen
 * 
 * MainScreen Definition (use default)
 */ 
Tine.Felamimail.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

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
 * @return {Tine.Felamimail.Model.Account}
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
        result = '<br><br><span class="felamimail-body-signature">--<br>' + signature + '</span>';
    }
    
    return result;
}

