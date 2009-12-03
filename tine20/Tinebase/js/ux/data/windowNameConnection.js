Ext.ns('Ext.ux.data');

/**
 * @namespace   Ext.ux.data
 * @class       Ext.ux.data.windowNameConnection
 * @extends     Ext.util.Observable
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @param {Object} config
 * 
 * window name communication class
 * 
 */
Ext.ux.data.windowNameConnection = function(config) {
    Ext.ux.data.windowNameConnection.superclass.constructor.call(this, config);
    
    Ext.apply(this, config);
    
    if (! this.blankUrl) {
        this.blankUrl = window.location.href.replace(window.location.pathname.substring(1, window.location.pathname.length), '') + 'blank.html';
    }
    
    if (! this.proxyUrl) {
        var src = Ext.DomQuery.selectNode('script[src*=windowNameConnection.js]').src;
        this.proxyUrl = src.substring(0, src.length -2) + 'html';
    }
};
Ext.ux.data.windowNameConnection.TRANSACTIONID = 1000;

Ext.extend(Ext.ux.data.windowNameConnection, Ext.util.Observable, {
    
    /**
     * @cfg {String} url (Optional) The default URL to be used for requests to the server. Defaults to undefined.
     * The url config may be a function which returns the URL to use for the Ajax request. The scope
     * (this reference) of the function is the scope option passed to the {@link #request} method.
     */
    
    /**
     * @cfg {String} blankUrl The default URL to a blank page on the page of the same origin (SOP) defaults to
     * blank.html on the SOP server.
     */
    
    /**
     * @cfg {String} proxyUrl The default URL to the external proxy html (windowNameConnection.html)
     */
    
    /**
     * create callback fn
     * 
     * @private
     * @param {Object} transaction
     * @return {Function}
     */
    createCallback : function(transaction) {
        var self = this;
        return function() {
            try {
                var frame = transaction.frame;
                if (frame.contentWindow.location.href === transaction.blankUrl) {
                    self.onData.call(self, transaction, frame.contentWindow.name);
                    self.destroyTransaction(transaction, true);
                }
            } catch(e){}
        };
    },
    
    /**
     * cleanup 
     * 
     * @private
     * @param {Object} transaction
     */
    destroyTransaction: function(transaction) {
        transaction.frame.contentWindow.onload = null;
        try {
            // we have to do this to stop the wait cursor in FF 
            var innerDoc = transaction.frame.contentWindow.document;
            innerDoc.write(" ");
            innerDoc.close();
        }catch(e){}
        
        Ext.fly(transaction.frame).remove();
        delete transaction.frame;
        
        window[transaction.cb] = undefined;
        try{
            delete Ext.ux.data.windowNameConnection[transaction.id];
        }catch(e){}
    },
    
    /**
     * called when data arrived
     * 
     * @private
     * @param {Object} transaction
     * @param {mixed} res
     */
    onData: function(transaction, res) {
        var resultData = Ext.decode(res);
        if (transaction.options.callback) {
            transaction.options.callback.call(transaction.scope, transaction.options, resultData.success, resultData.response);
        } else {
            var fn = resultData.success ? 'success' : 'fail';
            if (transaction.options[fn]) {
                transaction.options[fn].call(transaction.scope, resultData.response, transaction.options);
            }
        }
    },
    
    /**
     * performs request
     * 
     * @param {} options
     */
    request: function(options) {
        var transactionId = 'Ext.ux.data.windowNameConnection' + (++Ext.ux.data.windowNameConnection.TRANSACTIONID);
        var doc = document;
        
        var blankUrl = options.blankUrl || this.blankUrl;
        
        var url = options.url || this.url;
        if (Ext.isFunction(url)) {
            url = url.call(options.scope || WINDOW, options);
        }
        
        var requestData = Ext.encode({
            blankUrl: blankUrl,
            options: { // just a subset and ext-core has no copyTo :-(
                url:      url,
                method:   options.method,
                params:   options.params,
                timeout:  options.timeout,
                headers:  options.headers,
                xmlData:  options.xmlData,
                jsonData: options.jsonData
            }
        });
        
        var frame = doc.createElement(Ext.isIE ? "<iframe name='" + requestData + "' onload='Ext.ux.data.windowNameConnection[\"" + transactionId + "\"]()'>" : 'iframe');
        
        var transaction = {
            id         : transactionId,
            options    : options,
            scope      : options.scope || window,
            frame      : frame,
            blankUrl   : blankUrl
        };
        
        Ext.ux.data.windowNameConnection[transactionId] = frame.onload = this.createCallback(transaction);
        
        frame.id = transactionId;
        frame.name = requestData;
        frame.style.position = 'absolute';
        frame.style.top = '-10000px'; 
        frame.style.left = '-10000px'; 
        frame.style.visability = 'hidden';
        frame.src = this.proxyUrl + '?' + new Date().getTime();
        
        doc.body.appendChild(frame);
    }
});

/**
 * proxy request
 * - reads request data from window.name
 * - performs ajax request with proxy domain
 * - writes respponse to window.name
 * - navigates window back to same domain (blankUrl) of requestors page
 * 
 */
Ext.ux.data.windowNameConnection.doProxyRequest = function() {
    var requestOptions = Ext.decode(window.name);
    
    Ext.Ajax.request(Ext.apply(requestOptions.options, {
        callback: function(options, success, response) {
            window.name = Ext.encode({
                success: success,
                response: {
                    status:       response.status || 200,
                    statusText:   response.statusText,
                    responseText: response.responseText/*,
                    responseXML:  response.responseXML crahes in IE???*/
                }
            });
            
            window.location.href = requestOptions.blankUrl;
        }
    }));
    
};