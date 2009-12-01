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

/**
 * @namespace   Tine20
 * @class       Tine20.login
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * Simple login form for remote Tine 2.0 logins
 */
Tine20.login = {
    /**
     * detect users language (fallback en)
     * 
     * @return {String} language code
     */
    detectBrowserLanguage : function () {
        var result = 'en';
        var userLanguage;

        if (navigator.userLanguage) {// Explorer
            userLanguage = navigator.userLanguage;
        } else if (navigator.language) {// FF
            userLanguage = navigator.language;
        }
        
        // some browser have a locale string as language
        if(Tine20.login.translations[userLanguage]) {
            result = userLanguage;
        } else if (userLanguage.match('-')) {
            userLanguage = userLanguage.split('-')[0];
            if(Tine20.login.translations[userLanguage]) {
                result = userLanguage;
            }
        }

        return result;
    },
    
    /**
     * gets config for this login-box
     * 
     * @return {Object}
     */
    getConfig: function() {
        if (! this.config) {
            var src = Ext.DomQuery.selectNode('script[src*=tine20-loginbox.js]').src;
            
            var config = {
                userLanguage: Tine20.login.detectBrowserLanguage(),
                tine20Url: src.substring(0, src.indexOf('Tinebase'))  + 'index.php'
            };
            
            /* parse additional params here */
            var parts = src.split('?');
            
            this.config = config;
        }
        
        return this.config;
    },
    
    /**
     * gets template for login form
     * 
     * @return {Ext.Template}
     */
    getLoginTemplate: function () {
        if (! this.loginTemplate) {
            this.loginTemplate = new Ext.Template(
                '<form name="{formId}" id="{formId}" method="POST">',
                    '<fieldset>',
                        '<label>{loginname}:</label><br>',
                        '<input type="text" name="username"><br>',
                        '<label>{password}:</label><br>',
                        '<input type="password" name="password"><br>',
                        '<input type="hidden" name="method" value="{method}">',
                    
                        
                        '<div class="tine20loginmessage">&#160;</div>',
                        '<br>',
                        '<div class="tine20loginbutton">{login}</div>',
                    '</fieldset>',
                '</form>'
            ).compile();
        }
        
        return this.loginTemplate;
    },
    
    /**
     * checks authentication from server
     * 
     * @param  {Object}   config
     * @param  {String}   username
     * @param  {String}   password
     * @param  {Function} cb       callback function
     * @return void
     */
    checkAuth: function(config, username, password, cb) {
        var conn = new Ext.ux.data.jsonp({});
        
        conn.request({
            url: config.tine20Url,
            params: {
                method: 'Tinebase.authenticate',
                username: username,
                password: password
            },
            success: cb
        });
    },
    
    /**
     * processes login response data
     *  - updates message box
     *  - posts form for login on success
     * 
     * @param  {Object} data
     * @return void
     */
    onLoginResponse: function(data) {
        var config = this.getConfig();
        
        if (data.status == 'success') {
            // show success message
            this.messageBoxEl.update(String.format('{0} <img src="{1}">', 
                this.translations[config.userLanguage].authsuccess,
                config.tine20Url.replace('index.php', 'images/wait.gif'))
            );
            
            // post data
            this.loginBoxEl.dom.action = data.loginUrl || config.tine20Url;
            this.loginBoxEl.dom.submit();
        } else {
            // show fail message
            var msg = this.translations[config.userLanguage].authfailed;
            this.messageBoxEl.update(msg);
            
            this.usernameEl.focus(100);
        }
    },
    
    /**
     * login button handler
     * 
     * @return void
     */
    onLoginPress: function() {
        var config = this.getConfig();
        
        var username = this.usernameEl.dom.value;
        var password = this.passwordEl.dom.value;
        
        this.messageBoxEl.update(String.format('{0} <img src="{1}">', 
            this.translations[config.userLanguage].authwait,
            config.tine20Url.replace('index.php', 'images/wait.gif'))
        );
        
        this.checkAuth(config, username, password, this.onLoginResponse.createDelegate(this));
    },
    
    /**
     * renders login form and initializes elements and listeners
     * 
     * @return void
     */
    renderLoginForm: function() {
        var t = this.getLoginTemplate();
        var config = this.getConfig();
        
        // render template
        this.loginBoxEl = t.append('tine20-login', {
            formId: 'tine20loginform',
            method: 'Tinebase.loginFromPost',
            loginname: Tine20.login.translations[config.userLanguage].loginname,
            password: Tine20.login.translations[config.userLanguage].password,
            login: Tine20.login.translations[config.userLanguage].login
        }, true);
        
        // init Elements
        var E = Ext.Element;
        this.usernameEl = new E(Ext.DomQuery.selectNode('input[name=username]', this.loginBoxEl.dom));
        this.passwordEl = new E(Ext.DomQuery.selectNode('input[name=password]', this.loginBoxEl.dom));
        this.buttonEl   = new E(Ext.DomQuery.selectNode('div[class=tine20loginbutton]', this.loginBoxEl.dom));
        this.messageBoxEl = this.loginBoxEl.child('div[class=tine20loginmessage]');
        
        // init listeners
        this.buttonEl.on('click', this.onLoginPress, this);
        this.passwordEl.on('keydown', function(e, target) {
            switch(e.getKey()) {
                case 10:
                case 13:
                    this.onLoginPress();
                    break;
                default:
                    // nothing
                    break;
            }
        }, this);
        
        // focus username field
        this.usernameEl.focus(500);
    },
    
    /**
     * static translations array
     * 
     * @type Object
     */
    translations: {
        'en' : {
            'loginname'   : 'Username',
            'password'    : 'Password',
            'login'       : 'Login',
            'authwait'    : 'Authenticating...',
            'authfailed'  : 'Username or password wrong',
            'authsuccess' : 'Successful authentication, login in now...'
        },
        'de' : {
            'loginname'   : 'Benutzername',
            'password'    : 'Passwort',
            'login'       : 'Anmelden',
            'authwait'    : 'Authentifizierung...',
            'authfailed'  : 'Benutzername oder Passwort falsch',
            'authsuccess' : 'Authentifizierung erfolgreich, anmeldung erfolgt...'
        }
    }
}

// register onReady listener
Ext.onReady(Tine20.login.renderLoginForm, Tine20.login);


Ext.ns('Ext.ux.data');

/**
 * @namespace   Ext.ux.data
 * @class       Ext.ux.data.jsonp
 * @extends     Ext.util.Observable
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
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