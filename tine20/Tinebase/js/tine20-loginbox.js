/* $Id$ */

/*
Example html code to include Tine 2.0 login box on an external webpage

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            
    <!-- ext-core library -->
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/ext-core/3/ext-core.js"></script>
    
    <!-- use protocol and fqdn -->
    <script type="text/javascript" src="http://localhost/Tinebase/js/tine20-loginbox.js"></script>
                            
</head>
<body>

<div id="tine20-login" style="width:100px;"></div>

</body>
</html>
*/

Ext.namespace('Tine20.login');

Tine20.login = {
    detectBrowserLanguage : function () {
        var result = 'en';
        var userLanguage;

        if (navigator.userLanguage) {// Explorer
            userLanguage = navigator.userLanguage;
        } else if (navigator.language) {// FF
            userLanguage = navigator.language;
        }
        
        if(Tine20.login.translations[userLanguage]) {
            result = userLanguage;
        }

        return result;
    },
    
    /**
     * figures out the host to send our auth request 
     */
    getConfig: function() {
        var src = Ext.DomQuery.selectNode('script[src*=tine20-loginbox.js]').src;
        
        var config = {
            userLanguage: Tine20.login.detectBrowserLanguage(),
            authUrl: src.substring(0, src.indexOf('Tinebase'))  + 'index.php'
        };
        
        /* parse additional params here */
        var parts = src.split('?');
        
        return config;
    },
    
    checkAuth: function(config, username, password, cb) {
        var conn = new Ext.ux.data.jsonp({});
        
        conn.request({
            url: config.authUrl,
            params: {
                method: 'Tinebase.authenticate',
                username: username,
                password: password
            },
            success: cb
        });
    },
    
    translations: {
        'en' : {
            'loginname' : 'Username',
            'password'  : 'Password',
            'login'     : 'Login'
        },
        'de' : {
            'loginname' : 'Benutzername',
            'password'  : 'Passwort',
            'login'     : 'Anmelden'
        }
    }
}

Ext.onReady(function(){
    var config = Tine20.login.getConfig();
    
    var t = new Ext.Template (
        '<form name="{formId}" id="{formId}" method="POST">',
            '<fieldset>',
                '<label>{loginname}:</label><br>',
                '<input type="text" name="username"><br>',
                '<label>{password}:</label><br>',
                '<input type="password" name="password"><br>' +
                '<br><br>',
            '<div class="tine20loginbutton">{login}</div><br>',
        '</form>'
    );
    
    var loginBoxEl = t.append('tine20-login', {
        formId: 'tine20loginform', 
        loginname: Tine20.login.translations[config.userLanguage].loginname,
        password: Tine20.login.translations[config.userLanguage].password,
        login: Tine20.login.translations[config.userLanguage].login
    }, true);
    
    Ext.get(Ext.DomQuery.selectNode('div[class=tine20loginbutton]'), loginBoxEl).on('click', function(e, target) {
        var form = Ext.get(target).parent('form');
        
        var username = Ext.DomQuery.selectNode('input[name=username]', form.dom).value;
        var password = Ext.DomQuery.selectNode('input[name=password]', form.dom).value;
        
        Tine20.login.checkAuth(config, username, password, function(data) {
            if (data.status == 'success') {
                console.log(data);
                console.log(form);
                // post data
            } else {
                // show fail message
            }
        });
    });
});

Ext.ns('Ext.ux.data');

/**
 * @namespace   Ext.ux.data
 * @class       Ext.ux.data.jsonp
 * @extends     Ext.util.Observable
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id:$
 * 
 * @param {Object} config
 * 
 * Simple jsonp communication class
 * 
 */
Ext.ux.data.jsonp = function(config) {
    Ext.ux.data.jsonp.superclass.constructor.call(this, config);
    
    this.extraParams = config.extraParams || {};
};
Ext.ux.data.jsonp.TRANSACTIONID = 1000;

Ext.extend(Ext.ux.data.jsonp, Ext.util.Observable, {
    
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
        var transactionId = 'jsonp' + (++Ext.ux.data.jsonp.TRANSACTIONID);
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