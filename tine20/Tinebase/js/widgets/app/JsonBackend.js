/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets.app');

/**
 * Generic JSON Backdend for an model/datatype of an application
 * 
 * @class Tine.Tinebase.widgets.app.JsonBackend
 * @constructor 
 */
Tine.Tinebase.widgets.app.JsonBackend = function(config) {
    Ext.apply(this, config);
}

Ext.apply(Tine.Tinebase.widgets.app.JsonBackend.prototype, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    /**
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    
    /**
     * loads a single 'full featured' record
     * 
     * @param   {String} id
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    loadRecord: function(id, options) {
        
    },
    
    /**
     * searches all (lightway) records matching filter
     * 
     * @param   {Object} filter
     * @param   {Object} paging
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Object} root:[recrods], totalcount: number
     */
    searchRecords: function(filter, paging, options) {
        
    },
    
    /**
     * saves a single record
     * 
     * 
     * @param   {Ext.data.Record} record
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Ext.data.Record}
     */
    saveRecord: function(record, options) {
        options = options || {};
        options.beforeSuccess = this.recordReader;
        
        var p = options.params = options.params || {};
        p.method = this.appName + '.save' + this.modelName;
        p.recordData = Ext.util.JSON.encode(record.data);
        
        this.request(options);
    },
    
    /**
     * deletes multiple records identified by their ids
     * 
     * @param   {Array} records Array of records or ids
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success 
     */
    deleteRecords: function(records, options) {
        options = options || {};
        options.params = options.params || {};
        options.params.method = this.appName + '.delete' + this.modelName + 's';
        options.params.ids = Ext.util.JSON.encode(this.getRecordIds(records));
        
        this.request(options);
    },
    
    /**
     * updates multiple records with the same data
     * 
     * @param   {Array} records Array of records or ids
     * @param   {Object} updates
     * @return  {Number} Ext.Ajax transaction id
     * @success {Array} updated records
     */
    updateRecords: function(records, updates, options) {
        
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
     * reads a single 'fully featured' record from json data
     * 
     * NOTE: You might want to overwride this method if you have a more complex record
     * 
     * @param  XHR response
     * @return {Ext.data.Record}
     */
    recordReader: function(response) {
        if(!this.jsonReader) {
            this.jsonReader = new Ext.data.JsonReader({id: this.idProperty, root: 'root'}, this.recordClass);
        }
        
        var recordData = Ext.util.JSON.decode('{root: [' + response.responseText + ']}');
        var data = this.jsonReader.readRecords(recordData);
        
        return data.records;
    },
    
    /**
     * performs an Ajax request
     */
    request: function(options) {
        return Ext.Ajax.request({
            scope: this,
            params: options.params,
            success: function(response) {
                if (typeof options.success == 'function') {
                    var args = [];
                    if (typeof options.beforeSuccess == 'function') {
                        args = options.beforeSuccess.call(this, response);
                    }
                    
                    options.success.apply(options.scope, args);
                }
            },
            failure: function (response) {
                if (typeof options.failure == 'function') {
                    var args = [];
                    if (typeof options.beforeFailure == 'function') {
                        args = options.beforeFailure.call(this, response);
                    }
                
                    options.failure.apply(options.scope, args);
                }
            }
        });
    }
});
