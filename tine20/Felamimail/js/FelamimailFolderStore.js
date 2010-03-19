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
    this.queriesDone = new Ext.util.MixedCollection();

    Tine.Felamimail.FolderStore.superclass.constructor.call(this);
    
    this.on('load', this.onStoreLoad, this);
};

Ext.extend(Tine.Felamimail.FolderStore, Ext.data.Store, {
    
    fields: Tine.Felamimail.Model.Folder,
    queriesDone: null,
    proxy: Tine.Felamimail.folderBackend,
    
    /**
     * async query
     */
    asyncQuery: function(field, value, callback, args, scope, store) {
        
        var result = null;
        var queryObject = {field: field, value: value};
        
        //console.log(queryObject);
        
        if (! store.queriesDone.contains(queryObject)) {
            // do async request (only once)
            var accountId = value.match(/^\/([a-z0-9]*)/i)[1];
            var folderIdMatch = value.match(/[a-z0-9]+\/([a-z0-9]*)$/i);
            var folderId = (folderIdMatch) ? folderIdMatch[1] : null;
            
            // TODO check folder loading -> non existent folder should not trigger request
            var folder = null;
            if (folderId !== null) {
                //console.log('folderid: ' + folderId);
                folder = store.getById(folderId);
                if (! folder) {
                    //console.log('folder not found: ' . folderId)
                    callback.apply(scope, args);
                    return;
                }
            }

            store.load({
                path: value,
                params: {filter: [
                    {field: 'account_id', operator: 'equals', value: accountId},
                    {field: 'globalname', operator: 'equals', value: (folder !== null) ? folder.get('globalname') : ''}
                ]},
                callback: function () {
                    // query store again (it should have the new folders now) and call callback function to add nodes
                    result = store.query(field, value);
                    args.push(result);
                    callback.apply(scope, args);
                },
                add: true
            });
            
            // save query
            store.queriesDone.add(queryObject);
            
        } else if (Ext.isFunction(callback)) {
            result = store.query(field, value);
            args.push(result);
            callback.apply(scope, args);
        }
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
     * resets the query and removes all records that match it
     * 
     * @param {String} field
     * @param {String} value
     */
    resetQueryAndRemoveRecords: function(field, value) {
        var toRemove = this.query(field, value);
        toRemove.each(function(record) {
            this.remove(record);
        }, this);
        
        var index = this.queriesDone.findIndex('value', value);
        if (index >= 0) {
            this.queriesDone.removeAt(index);
        }
    }
});

