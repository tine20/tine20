/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Ext.ux', 'Ext.ux.direct');

/**
 * @namespace   Ext.ux.direct
 * @class       Ext.ux.direct.ZendFrameworkProvider
 * @extends     Ext.direct.RemotingProvider
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Ext.Direct provider for seamless integration with Zend_Json_Server
 * 
 *  Ext.Direct.addProvider(Ext.apply(Ext.app.JSONRPC_API, {
        'type'     : 'zfprovider',
        'url'      : Ext.app.JSONRPC_API
    }));
 * 
 */
Ext.ux.direct.ZendFrameworkProvider = Ext.extend(Ext.direct.RemotingProvider, {
    
    // private
    initAPI : function() {
        for (var method in this.services){
            var mparts = method.split('.');
            var cls = this.namespace[mparts[0]] || (this.namespace[mparts[0]] = {});
            cls[mparts[1]] = this.createMethod(mparts[0], Ext.apply(this.services[method], {
                name: mparts[1],
                len: this.services[method].parameters.length
            }));
        }
    },
    
    // private
    doCall : function(c, m, args) {
        // support named parameters
        if (args[args.length-1].paramsAsHash) {
            var o = args.shift();
            for (var i = 0; i < m.parameters.length; i++) {
                args.splice(i,0, o[m.parameters[i].name]);
            }
        }
        
        return Ext.ux.direct.ZendFrameworkProvider.superclass.doCall.call(this, c, m, args);
    },
    
    // private
    getCallData: function(t){
        return {
            jsonrpc: '2.0',
            method: t.action + '.' + t.method,
            params: t.data || [],
            id: t.tid
        };
    },
    
    // private
    onData: function(opt, success, xhr) {
        var rpcresponse = Ext.decode(xhr.responseText);
        xhr.responseText = {
            type: rpcresponse.result ? 'rpc' : 'exception',
            result: rpcresponse.result,
            tid: rpcresponse.id
        };
        
        return Ext.ux.direct.ZendFrameworkProvider.superclass.onData.apply(this, arguments);
    }
});

Ext.Direct.PROVIDERS['zfprovider'] = Ext.ux.direct.ZendFrameworkProvider;