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
 * @class       Tine.Felamimail.FolderStore
 * @extends     Ext.data.Store
 * 
 * <p>Felamimail folder store</p>
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
 * Create a new  Tine.Felamimail.FolderStore
 */
Tine.Felamimail.FolderStore = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    this.reader = Tine.Felamimail.folderBackend.getReader();
    this.queriesPending = [];
    this.queriesDone = [];

    Tine.Felamimail.FolderStore.superclass.constructor.call(this);
    
    this.on('load', this.onStoreLoad, this);
};

Ext.extend(Tine.Felamimail.FolderStore, Ext.data.Store, {
    
    fields: Tine.Felamimail.Model.Folder,
    proxy: Tine.Felamimail.folderBackend,
    
    /**
     * @property queriesDone
     * @type Array
     */
    queriesDone: null,
    
    /**
     * @property queriesPending
     * @type Array
     */
    queriesPending: null,
    
    /**
     * async query
     */
    asyncQuery: function(field, value, callback, args, scope, store) {
        var result = null,
            key = store.getKey(field, value);
        
        Tine.log.info('Tine.Felamimail.FolderStore.asyncQuery: ' + key);
        
        if (store.queriesDone.indexOf(key) >= 0) {
            Tine.log.debug('result already loaded -> directly query store');
            result = store.query(field, value);
            args.push(result);
            callback.apply(scope, args);
        } else if (store.queriesPending.indexOf(key) >= 0) {
            Tine.log.debug('result not in store yet, but async query already running -> wait a bit');
            this.asyncQuery.defer(250, this, [field, value, callback, args, scope, store]);
        } else {
            Tine.log.debug('result is requested the first time -> fetch from server');
            var accountId = value.match(/^\/([a-z0-9]*)/i)[1],
                folderIdMatch = value.match(/[a-z0-9]+\/([a-z0-9]*)$/i),
                folderId = (folderIdMatch) ? folderIdMatch[1] : null,
                folder = folderId ? store.getById(folderId) : null;
            
            if (folderId && ! folder) {
                Tine.log.warn('folder ' + folderId + ' not found -> performing no query at all');
                callback.apply(scope, args);
                return;
            }
            
            store.queriesPending.push(key);
            store.load({
                path: value,
                params: {filter: [
                    {field: 'account_id', operator: 'equals', value: accountId},
                    {field: 'globalname', operator: 'equals', value: (folder !== null) ? folder.get('globalname') : ''}
                ]},
                callback: function () {
                    store.queriesDone.push(key);
                    store.queriesPending.remove(key);
                    
                    // query store again (it should have the new folders now) and call callback function to add nodes
                    result = store.query(field, value);
                    args.push(result);
                    callback.apply(scope, args);
                },
                add: true
            });
        }
    },
    
    /**
     * get key to store query 
     * 
     * @param  {string} field
     * @param  {mixed} value
     * @return {string}
     */
    getKey: function(field, value) {
        return field + ' -> ' + value;
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
            record.beginEdit();
            record.set('parent_path', parent_path);
            record.set('path', parent_path + '/' + record.id);
            record.endEdit();
        }, this);
    },
    
    /**
     * resets the query and removes all records that match it
     * 
     * @param {String} field
     * @param {String} value
     */
    resetQueryAndRemoveRecords: function(field, value) {
        var toRemove = this.query(field, value);
        toRemove.each(function(record) {
            this.remove(record);
            this.queriesDone.remove(this.getKey(field, record.get(field)));
        }, this);
    },
    
    /**
     * update folder in this store
     * 
     * NOTE: parent_path and path are computed onLoad and must be preserved
     * 
     * @param {Array/Tine.Felamimail.Model.Folder} update
     * @return {Tine.Felamimail.Model.Folder}
     */
    updateFolder: function(update) {
        if (Ext.isArray(update)) {
            Ext.each(update, function(u) {this.updateFolder.call(this, u)}, this);
            return;
        }
        
        var folder = this.getById(update.id);
        
        if (folder) {
            folder.beginEdit();
            Ext.each(Tine.Felamimail.Model.Folder.getFieldNames(), function(f) {
                if (! f.match('path')) {
                    folder.set(f, update.get(f));
                }
            }, this);
            folder.endEdit();
            folder.commit();
            return folder;
        }
    }
});

