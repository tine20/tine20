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
        var params = options.params || {};
        params.method = this.appName + '.delete' + this.modelName + 's';
        params.ids = this.getRecordIds(records);
        
        return Ext.Ajax.request({
            scope: this,
            params: params,
            success: function(response) {
                if (typeof options.success == 'function') {
                    options.success.call(options.scope);
                }
            },
            failure: function (response) {
                if (typeof options.failure == 'function') {
                    options.failure.call(options.scope);
                }
            }
        });
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
    }
});
