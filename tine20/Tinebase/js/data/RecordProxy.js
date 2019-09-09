/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Tinebase.data');

/**
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.RecordProxy
 * @extends     Ext.data.DataProxy
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * Generic record proxy for an model/datatype of an application
 * 
 * @constructor
 * @param {Object} config Config Object
 */
Tine.Tinebase.data.RecordProxy = function(c) {
    // we support all actions
    c.api = {read: true, create: true, update: true, destroy: true};
    
    Tine.Tinebase.data.RecordProxy.superclass.constructor.call(this, c);
    
    Ext.apply(this, c);
    this.appName    = this.appName    ? this.appName    : this.recordClass.getMeta('appName');
    this.modelName  = this.modelName  ? this.modelName  : this.recordClass.getMeta('modelName');
    this.idProperty = this.idProperty ? this.idProperty : this.recordClass.getMeta('idProperty');
    
    /* NOTE: in ExtJS records always are part of a store. The store is
             the only instance which triggers read/write actions.
             
             In our edit dialoges in contrast we work with single (store-less) 
             records and the handlers itselve trigger the read/write actions.
             This might change in future, but as long as we do so, we also need
             the reader/writer here.
     */
    this.jsonReader = new Ext.data.JsonReader(Ext.apply({
        id: this.idProperty,
        root: 'results',
        totalProperty: 'totalcount'
    }, c.readerConfig || {}), this.recordClass);
};

Ext.extend(Tine.Tinebase.data.RecordProxy, Ext.data.DataProxy, {
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    /**
     * @type String 
     * @property appName
     * internal/untranslated app name
     */
    appName: null,
    
    /**
     * default value for timeout on searches
     * 
     * @type Number
     */
    searchTimeout: 60000,
    
    /**
     * default value for timeout on saving
     * 
     * @type Number
     */
    saveTimeout: 300000,
    
    /**
     * default value for timeout on deleting by filter
     * 
     * @type Number
     */
    deleteByFilterTimeout: 300000,
    
    /**
     * default value for timeout on deleting
     * 
     * @type Number
     */
    deleteTimeout: 120000,
    
    /**
     * @type String 
     * @property idProperty
     * property of the id of the record
     */
    idProperty: null,
    
    /**
     * @type String 
     * @property modelName
     * name of the model/record 
     */
    modelName: null,
    
    /**
     * id of last transaction
     * @property transId
     */
    transId: null,

    /**
     * TODO is this really needed?
     */
    onDestroyRecords: Ext.emptyFn,
    removeFromBatch: Ext.emptyFn,
    
    /**
     * Aborts any outstanding request.
     * @param {Number} transactionId (Optional) defaults to the last transaction
     */
    abort : function(transactionId) {
        return Ext.Ajax.abort(transactionId);
    },
    
    /**
     * Determine whether this object has a request outstanding.
     * @param {Number} transactionId (Optional) defaults to the last transaction
     * @return {Boolean} True if there is an outstanding request.
     */
    isLoading : function(transId){
        return Ext.Ajax.isLoading(transId);
    },
        
    /**
     * loads a single 'full featured' record
     * 
     * @param   {Ext.data.Record} record
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    loadRecord: function(record, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            if (! options.suppressBusEvents) {
                _.defer(_.bind(this.postMessage, this), 'update', response.responseText);
            }
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.get' + this.modelName;
        p.id = Ext.isString(record) ? record : record.get(this.idProperty);
        
        return this.doXHTTPRequest(options);
    },

    promiseLoadRecord: function(record, options) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.loadRecord(record, Ext.apply(options || {}, {
                    success: function (r) {
                        fulfill(r);
                    },
                    failure: function (error) {
                        reject(new Error(error));
                    }
                }));
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
    },

    /**
     * searches all (lightweight) records matching filter
     * 
     * @param   {Object} filter
     * @param   {Object} paging
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Object} root:[records], totalcount: number
     */
    searchRecords: function(filter, paging, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        
        p.method = this.appName + '.search' + this.modelName + 's';
        p.filter = (filter) ? filter : [];
        p.paging = paging;

        options.beforeSuccess = function(response) {
            return [this.jsonReader.read(response)];
        };
        
        // increase timeout as this can take a longer (1 minute)
        options.timeout = this.searchTimeout;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * saves a single record
     * 
     * @param   {Ext.data.Record} record
     * @param   {Object} options
     * @param   {Object} additionalArguments
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    saveRecord: function(record, options, additionalArguments) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            if (! options.suppressBusEvents) {
                let recordData = JSON.parse(response.responseText);
                let action = (!_.get(record, 'data.id') && _.get(recordData, 'id')) ||
                    (!_.get(record, 'data.creation_time') && _.get(recordData, 'creation_time')) ?
                    'create' : 'update';

                _.defer(_.bind(this.postMessage, this), action, recordData);
            }
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.save' + this.modelName;
        p.recordData = record.data;
        if (additionalArguments) {
            Ext.apply(p, additionalArguments);
        }
        
        // increase timeout as this can take a longer (5 minutes)
        options.timeout = this.saveTimeout;
        
        return this.doXHTTPRequest(options);
    },

    promiseSaveRecord: function(record, options, additionalArguments) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.saveRecord(record, Ext.apply(options || {}, {
                    success: function (r) {
                        fulfill(r);
                    },
                    failure: function (error) {
                        reject(new Error(error));
                    }
                }), additionalArguments);
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
    },

    /**
     * deletes multiple records identified by their ids
     * 
     * @param   {Array} records Array of records or ids
     * @param   {Object} options
     * @param   {Object} additionalArguments
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecords: function(records, options, additionalArguments) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 's';
        options.params.ids = this.getRecordIds(records);
        if (additionalArguments) {
            Ext.apply(options.params, additionalArguments);
        }

        options.beforeSuccess = function(response) {
            var _ = window.lodash,
                me = this;

            if (! Ext.isArray(records)) {
                records = [records];
            }

            if (! options.suppressBusEvents) {
                _.each(records, function (record) {
                    me.postMessage('delete', record.data);
                });
            }
        };

        // increase timeout as this can take a long time (2 mins)
        options.timeout = this.deleteTimeout;
        
        return this.doXHTTPRequest(options);
    },

    promiseDeleteRecords: function(record, options, additionalArguments) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.deleteRecords(record, Ext.apply(options || {}, {
                    success: function (r) {
                        fulfill(r);
                    },
                    failure: function (error) {
                        reject(new Error(error));
                    }
                }), additionalArguments);
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
    },

    /**
     * deletes multiple records identified by a filter
     * 
     * @param   {Object} filter
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecordsByFilter: function(filter, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 'sByFilter';
        options.params.filter = filter;
        
        // increase timeout as this can take a long time (5 mins)
        options.timeout = this.deleteByFilterTimeout;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * updates multiple records with the same data
     * 
     * @param   {Array} filter filter data
     * @param   {Object} updates
     * @return  {Number} Ext.Ajax transaction id
     * @success
     */
    updateRecords: function(filter, updates, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.updateMultiple' + this.modelName + 's';
        options.params.filter = filter;
        options.params.values = updates;
        
        options.beforeSuccess = function(response) {
            return [Ext.util.JSON.decode(response.responseText)];
        };
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * returns an array of ids
     * 
     * @private 
     * @param  {Ext.data.Record|Array}
     * @return {Array} of ids
     */
    getRecordIds : function(records) {
        var ids = [];
        
        if (! Ext.isArray(records)) {
            records = [records];
        }
        
        for (var i=0; i<records.length; i++) {
            ids.push(records[i].id ? records[i].id : records.id);
        }
        
        return ids;
    },
    
    /**
     * required method for Ext.data.Proxy, used by store
     */
    load : function(params, reader, callback, scope, arg){
        if(this.fireEvent("beforeload", this, params) !== false){
            
            // 2017-11-03 cweiss - NOTE: some ext widgets directly work on the paging params.
            //   and don't update eventually existing params.paging properties. So we ALWAYS
            //   NEED to construct a new paging object from the params here!
            var paging = {
                sort:  params.sort,
                dir:   params.dir,
                start: params.start,
                limit: params.limit
            };
            
            this.searchRecords(params.filter, paging, {
                params: params,
                scope: this,
                success: function(records) {
                    callback.call(scope||this, records, arg, true);
                },
                failure: function(exception) {
                    //this.fireEvent('exception', this, 'remote', 'read', options, response, arg);
                    this.fireEvent('loadexception', this, 'remote',  exception, arg);
                    callback.call(scope||this, exception, arg, false);
                }
            });
            
        } else {
            callback.call(scope||this, null, arg, false);
        }
    },
    
    /**
     * do the request
     * 
     * @param {} action
     * @param {} rs
     * @param {} params
     * @param {} reader
     * @param {} callback
     * @param {} scope
     * @param {} options
     */
    doRequest : function(action, rs, params, reader, callback, scope, options) {
        var opts = {
            params: params, 
            callback: callback,
            scope: scope
        };
        
        switch (action) {
            case Ext.data.Api.actions.create:
                this.saveRecord(rs, opts);
                break;
            case Ext.data.Api.actions.read:
                this.load(params, reader, callback, scope, options);
                break;
            case Ext.data.Api.actions.update:
                this.saveRecord(rs, opts);
                break;
            case Ext.data.Api.actions.destroy:
                this.deleteRecords(rs, opts);
                break;
        }
    },

    
    /**
     * returns reader
     * 
     * @return {Ext.data.DataReader}
     */
    getReader: function() {
        return this.jsonReader;
    },
    
    /**
     * reads a single 'fully featured' record from json data
     * 
     * NOTE: You might want to override this method if you have a more complex record
     * 
     * @param  XHR response
     * @return {Ext.data.Record}
     */
    recordReader: function(response) {
        return Tine.Tinebase.data.Record.setFromJson(response.responseText, this.recordClass);
    },
    
    /**
     * is request still loading?
     * 
     * @param  {Number} Ext.Ajax transaction id
     * @return {Bool}
     */
    isLoading: function(tid) {
        return Ext.Ajax.isLoading(tid);
    },
    
    /**
     * performs an Ajax request
     */
    doXHTTPRequest: function(options) {
        var requestOptions = {
            scope: this,
            params: options.params,
            callback: options.callback,
            success: function(response) {
                var args = [];
                if (typeof options.beforeSuccess == 'function') {
                    args = options.beforeSuccess.call(this, response);
                }

                if (typeof options.success == 'function') {
                    options.success.apply(options.scope, args);
                }
            },
            // note incoming options are implicitly json-rpc converted
            failure: function (response, jsonrpcoptions) {
                var responseData = Ext.decode(response.responseText),
                    exception = responseData.data ? responseData.data : responseData;
                    
                exception.request = jsonrpcoptions.jsonData;
                exception.response = response.responseText;

                var args = [exception];
                if (typeof options.beforeFailure == 'function') {
                    args = options.beforeFailure.call(this, response);
                }
                if (typeof options.failure == 'function') {
                    Tine.log.debug('Tine.Tinebase.data.RecordProxy::doXHTTPRequest -> call failure fn');
                    options.failure.apply(options.scope, args);
                }
                // requests with callback need to define their own exception handling
                else if (! options.callback) {
                    Tine.log.debug('Tine.Tinebase.data.RecordProxy::doXHTTPRequest -> handle exception');
                    this.handleRequestException(exception);
                } else {
                    Tine.log.debug('Tine.Tinebase.data.RecordProxy::doXHTTPRequest -> call callback fn');
                }
            }
        };
        
        if (options.timeout) {
            requestOptions.timeout = options.timeout;
        }
        
        this.transId = Ext.Ajax.request(requestOptions);
        
        return this.transId;
    },
    
    /**
     * default exception handler
     * 
     * @param {Object} exception
     */
    handleRequestException: function(exception) {
        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    },

    /**
     * posts message about record crud action on central message bus
     * NOTE: we don't use the Ext internal write event as:
     *       a) to deal with cross window issues we only publish bare data
     *       b) to be able mix with other libraries
     *
     * @param {String} action [create|update|delete]
     * @param {Record} record
     */
    postMessage: function(action, record) {
        var _ = window.lodash,
            recordData = _.isFunction(record.beginEdit) ? record.data :
                         _.isString(record) ? JSON.parse(record) :
                         record;

        window.postal.publish({
            channel: "recordchange",
            topic: [this.appName, this.modelName, action].join('.'),
            data: recordData
        });
    }
});
