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
    
    this.extraParams = config.extraParams || {};
};
Ext.ux.data.windowNameConnection.TRANSACTIONID = 1000;

/**
 * @type String PROXY_URL
 * 
 * location of the proxy HTML file of the foreign domain
 */
Ext.ux.data.windowNameConnection.PROXY_URL = 'http://foreignhost/tt/tine20/Tinebase/js/ux/data/windowNameConnection.html';

Ext.extend(Ext.ux.data.windowNameConnection, Ext.util.Observable, {
    
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
    
    request: function(options) {
        var transactionId = 'Ext.ux.data.windowNameConnection' + (++Ext.ux.data.windowNameConnection.TRANSACTIONID);
        var doc = document;
        // TODO: make this a parameter
        var sameDomainUrl = window.location.href.replace(window.location.pathname, '') + '/blank.html';
        
        var requestData = Ext.encode({
            method:   options.method,
            params:   options.params,
            timeout:  options.timeout,
            headers:  options.headers,
            xmlData:  options.xmlData,
            jsonData: options.jsonData,
            
            sameDomainUrl: sameDomainUrl
        });
        
        var frame = doc.createElement(Ext.isIE ? "<iframe name='" + requestData + "' onload='Ext.ux.data.windowNameConnection[\"" + transactionId + "\"]()'>" : 'iframe');
        Ext.ux.data.windowNameConnection[transactionId] = frame.onload = function() {
            try {
                if (frame.contentWindow.location.href.match('blank.html') && frame.contentWindow.name === requestData) {
                    frame.contentWindow.location.href = Ext.ux.data.windowNameConnection.PROXY_URL + '?' + new Date().getTime();
                } else if (frame.contentWindow.location.href.match('blank.html')) {
                    
                    alert(frame.contentWindow.name);
                }
            } catch(e) {
                
            }
        };
        
        frame.id = transactionId;
        frame.name = requestData;
        frame.style.position = 'absolute';
        frame.style.top = '-10000px'; 
        frame.style.left = '-10000px'; 
        frame.style.visability = 'hidden';
        frame.src = sameDomainUrl;
        
        doc.body.appendChild(frame);
    },
    
    /**
     * do window name proxy request
     * 
     * @private
     * @param {String} url
     * @param {Object} params
     * @param {Function} callback
     * @param {Object} scope
     * @param {Object} arg
     */
    doRequest: function(url, params, callback, scope, arg) {
        /*
        var transactionId = 'Ext.ux.data.windowNameConnection' + (++Ext.ux.data.windowNameConnection.TRANSACTIONID);
            doc = document,
            frame = doc.createElement('iframe');
        
        frame.id = transactionId;
        frame.style.position = 'absolute';
        frame.style.top = '-10000px'; 
        frame.style.left = '-10000px'; 
        frame.style.visability = 'hidden';
        frame.src = Ext.ux.data.windowNameConnection.PROXY_URL;
        
        frame.name = Ext.encode({
            params: {
                method: 'Tinebase.authenticate',
                username: 'user',
                password: 'pass'
            }
        
        });
        
        doc.body.appendChild(frame);
        
        
        //if ()
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
        */
        //window[transaction.cb] = this.createCallback(transaction);
        //src += '&' + this.callbackParam + '=' + transaction.cb;
        
        //transaction.scriptTag = Ext.DomHelper.append(Ext.DomQuery.selectNode('head'), {tag: 'script', type: 'text/javascript', src: src, id: transactionId}, true);
    }
});

Ext.ux.data.windowNameConnection.doProxyRequest = function() {
    var requestOptions = Ext.decode(window.name);
    var sameDomainUrl = requestOptions.sameDomainUrl;
    delete requestOptions.sameDomainUrl;
    
    Ext.Ajax.request(Ext.apply(requestOptions, {
        url: 'http://foreignhost/tt/tine20/index.php',
        callback: function() {
            window.name = Ext.encode({
                sucess: true,
                msg: 'welcome to Tine 2.0'
            });
            
            window.location.href = sameDomainUrl;
        }
    }));
    
    //window.history.back();
}