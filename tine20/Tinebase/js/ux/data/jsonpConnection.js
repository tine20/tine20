Ext.ns('Ext.ux.data');

/**
 * @namespace   Ext.ux.data
 * @class       Ext.ux.data.jsonpConnection
 * @extends     Ext.util.Observable
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @param {Object} config
 * 
 * Simple jsonp communication class
 * 
 */
Ext.ux.data.jsonpConnection = function(config) {
    Ext.ux.data.jsonpConnection.superclass.constructor.call(this, config);
    
    this.extraParams = config.extraParams || {};
};
Ext.ux.data.jsonpConnection.TRANSACTIONID = 1000;

Ext.extend(Ext.ux.data.jsonpConnection, Ext.util.Observable, {
    
    /**
     * @cfg {String} callbackParam
     */
    callbackParam : "jsonp",
    
    /**
     * @cfg {Object} extraParams
     */
    extraParams: null,
    
    /**
     * request
     * 
     * @param {Object} options
     */
    request: function(options) {
        this.doRequest(options.url, options.params, options.success || options.callback, options.scope, {})
    },
    
    /**
     * create callback fn
     * 
     * @private
     * @param {Object} transaction
     * @return {Function}
     */
    createCallback : function(transaction) {
        var self = this;
        return function(res) {
            self.destroyTransaction(transaction, true);
            self.onData.call(self, transaction, res);
        };
    },
    
    /**
     * cleanup scripttag and callback
     * 
     * @private
     * @param {Object} transaction
     */
    destroyTransaction: function(transaction) {
        transaction.scriptTag.remove();
        delete transaction.scriptTag;
        
        window[transaction.cb] = undefined;
        try{
            delete window[transaction.cb];
        }catch(e){}
    },
    
    /**
     * do jsonp request
     * 
     * @private
     * @param {String} url
     * @param {Object} params
     * @param {Function} callback
     * @param {Object} scope
     * @param {Object} arg
     */
    doRequest: function(url, params, callback, scope, arg) {
        var transactionId = 'jsonp' + (++Ext.ux.data.jsonpConnection.TRANSACTIONID);
        if(this.nocache){
            params['_dc'] = new Date().getTime();
        }
        var src = url + '?' + Ext.urlEncode(Ext.apply(params, this.extraParams));
        
        var transaction = {
            id: transactionId,
            cb: 'jsonpcb' + transactionId,
            params: params,
            callback: callback, 
            scope: scope,
            arg: arg
        };
        
        window[transaction.cb] = this.createCallback(transaction);
        src += '&' + this.callbackParam + '=' + transaction.cb;
        
        transaction.scriptTag = Ext.DomHelper.append(Ext.DomQuery.selectNode('head'), {tag: 'script', type: 'text/javascript', src: src, id: transactionId}, true);
    },
    
    /**
     * called when data arrived
     * 
     * @private
     * @param {Object} transaction
     * @param {mixed} res
     */
    onData: function(transaction, res) {
        var args = Ext.isArray(transaction.arg) ? [res].concat(transaction.arg) : [res];
        
        transaction.callback.apply(transaction.scope || window, args);
    }
});