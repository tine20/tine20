/*
 * Tine 2.0
 * 
 * @license     MIT, BSD, and GPL
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Ext.ux.direct');

/**
 * @namespace   Ext.ux.direct
 * @class       Ext.ux.direct.JsonRpcProvider
 * @extends     Ext.direct.RemotingProvider
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Ext.Direct provider for seamless integration with JSON RPC servers
 * 
 *  Ext.Direct.addProvider(Ext.apply(Ext.app.JSONRPC_API, {
        'type'     : 'jsonrpcprovider',
        'url'      : Ext.app.JSONRPC_API
    }));
 * 
 */
Ext.ux.direct.JsonRpcProvider = Ext.extend(Ext.direct.RemotingProvider, {
    
    /**
     * @cfg {Boolean} paramsAsHash
     */
    //paramsAsHash: true,
    
    /**
     * @cfg {Boolean} useNamedParams
     */
    useNamedParams: false,

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
        const me = this;
        var promise = new Promise(function(fulfill, reject) {
            const paramArray = {};
            const method = `${c}.${m.name}`;
            _.each(_.map(m.parameters, 'name'), (paramName, i) => {
                paramArray[paramName] = args[i];
            });

            let cb;
            if (Ext.isFunction(args[args.length-1])) {
                // no scope, last arg is cb
                cb = args[args.length-1];
                args.push(window);
            } else if (Ext.isFunction(args[args.length-2])) {
                cb = args[args.length-2];
            } else {
                cb = Ext.emptyFn;
                args.push(cb);
                args.push(window);
            }

            args[args.length-2] = cb.createSequence(function(result, e) {
                if (e.status === 'failure') {
                    reject(e);
                } else if (e.type === 'exception') {
                    const errorData = e.error.data;
                    
                    //check for status code 650 (generic confirm)
                    if (errorData.code === 650) {
                        if (errorData.info) {
                            errorData.message += '<br />' + errorData.info;
                        }
                        
                        Ext.Msg.confirm(
                            errorData.title,
                            errorData.message,
                            async (button) => {
                                if (button === 'no') {
                                    fulfill(e);
                                }
                                if (button === 'yes') {
                                    const request = {
                                        params : paramArray,
                                        method: method
                                    }
                                    
                                    paramArray['method'] = method;
                                    
                                    Ext.Ajax.request({
                                        headers: {
                                            'X-TINE20-REQUEST-CONTEXT-CONFIRM' : true
                                        },
                                        params: paramArray,
                                        success : function(_result, _request) {
                                            const response = Ext.util.JSON.decode(_result.responseText);
                                            fulfill(response);
                                        },
                                        failure: function(exception) {
                                            if (! exception.code && exception.responseText) {
                                                // we need to decode the exception first
                                                const response = Ext.util.JSON.decode(exception.responseText);
                                                exception = response.data;
                                                exception.request = Ext.util.JSON.encode(request);
                                            }
                                            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                                        }
                                    });
                                }
                            });
                    } else {
                        reject (e.error);
                    }
                } else {
                    fulfill(result);
                }
            });
        });

        // support named/hashed parameters e.g. from DirectProxy
        if (args[args.length-1].paramsAsHash) {
            var o = args.shift();
            for (var i = 0; i < m.parameters.length; i++) {
                args.splice(i,0, o[m.parameters[i].name]);
            }
        }
        
        Ext.ux.direct.JsonRpcProvider.superclass.doCall.call(this, c, m, args);

        return promise;
    },
    
    // private
    getCallData: function(t){
        var method = t.action + '.' + t.method,
            m  = t.provider.services[method],
            data = t.data || [];
        
        if (this.useNamedParams) {
            var data = {};
            Ext.each(m.parameters, function(param, i) {
                data[param['name']] = t.data[i];
                
                /* NOTE: Ext.Direct and the automatically creaeted DirectFn's don't support optional params, cause 
                 * callback and scope are expected on fixed positions!
                 
                var value = typeof t.data[i] !== 'function' ? t.data[i] : undefined;
                
                if (value === undefined && ! param.optional) {
                    value = param['default'];
                }
                
                if (value !== undefined) {
                    data[param['name']] = value;
                }
                */
            }, this);
        }
        
        return {
            jsonrpc: '2.0',
            method: method,
            params: data,
            id: t.tid
        };
    },

    // private
    createMethod : function(c, m){
        var f;
        if(!m.formHandler){
            f = function(){
                return this.doCall(c, m, Array.prototype.slice.call(arguments, 0));
            }.createDelegate(this);
        }else{
            f = function(form, callback, scope){
                return this.doForm(c, m, form, callback, scope);
            }.createDelegate(this);
        }
        f.directCfg = {
            action: c,
            method: m
        };
        return f;
    },

    // private
    onData: function(opt, success, xhr) {
        var rs = [].concat(Ext.decode(xhr.responseText));
        
        xhr.responseText = [];
        Ext.each(rs, function(rpcresponse, i) {
            xhr.responseText[i] = {
                type: rpcresponse.hasOwnProperty('result') ? 'rpc' : 'exception',
                result: rpcresponse.result,
                tid: rpcresponse.id,
                error: rpcresponse.error
            };
            
            if (xhr.responseText[i].type === 'rpc') {
                delete xhr.responseText.error;
            }
        });
        
        return Ext.ux.direct.JsonRpcProvider.superclass.onData.apply(this, arguments);
    }
});

Ext.Direct.PROVIDERS['jsonrpcprovider'] = Ext.ux.direct.JsonRpcProvider;
