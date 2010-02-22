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
            var tine20Url = src.match('Tinebase') ? 
                    src.substring(0, src.indexOf('Tinebase'))  + 'index.php' :
                    src.substring(0, src.indexOf('tine20-loginbox.js')) + 'index.php';
            
            var tine20ProxyUrl = src.substring(0, src.indexOf('tine20-loginbox.js')) + 'ux/data/windowNameConnection.html';
                    
            var config = {
                userLanguage: Tine20.login.detectBrowserLanguage(),
                tine20Url: tine20Url,
                tine20ProxyUrl: tine20ProxyUrl
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
                    '<div class="tine20login-fields">',
                        '<div class="tine20login-field-username">',
                            '<label>{loginname}:</label>',
                            '<input type="text" name="username">',
                        '</div>',
                        '<div class="tine20login-field-password">',
                            '<label>{password}:</label>',
                            '<input type="password" name="password">',
                        '</div>',
                        '<input type="hidden" name="method" value="{method}">',
                    '</div>',
                    '<div class="tine20login-progess"></div>',
                    '<div class="tine20login-message"></div>',
                    '<div class="tine20login-button">{login}</div>',
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
        var conn = new Ext.ux.data.windowNameConnection({
            proxyUrl: config.tine20ProxyUrl
        });
                
        conn.request({
            url: config.tine20Url,
            headers: {
                'X-Tine20-Request-Type' : 'JSON'
            },
            jsonData: Ext.encode({
                jsonrpc: '2.0',
                method: 'Tinebase.authenticate',
                id: ++Ext.Ajax.requestId,
                params: {
                    username: username,
                    password: password
                }
            }),
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
    onLoginResponse: function(response) {
        try {
            var data = Ext.decode(response.responseText).result;
        } catch (e) {
            var data = {};
        }
        
        var config = this.getConfig();
        if (data.status == 'success') {
            // show success message
            this.messageBoxEl.update(this.translations[config.userLanguage].authsuccess);
            this.setCssClass('loginSuccess');
            
            // post data
            this.loginBoxEl.dom.action = data.loginUrl || config.tine20Url;
            this.loginBoxEl.dom.submit();
        } else {
            // show fail message
            this.messageBoxEl.update(this.translations[config.userLanguage].authfailed);
            this.setCssClass('loginFaild');
            
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
        
        this.messageBoxEl.update(this.translations[config.userLanguage].authwait);
        this.setCssClass('onLogin');
        
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
        this.usernameEl   = new E(Ext.DomQuery.selectNode('input[name=username]', this.loginBoxEl.dom));
        this.passwordEl   = new E(Ext.DomQuery.selectNode('input[name=password]', this.loginBoxEl.dom));
        this.buttonEl     = new E(Ext.DomQuery.selectNode('div[class=tine20login-button]', this.loginBoxEl.dom));
        this.messageBoxEl = this.loginBoxEl.child('div[class=tine20login-message]');
        //this.progressEl   = this.loginBoxEl.child('div[class=tine20loginmessage]');
        
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
     * sets css class of outer form el according to 
     * login state
     * 
     * @param {string} state
     */
    setCssClass: function(state) {
        var allStates = [
            'onLogin',
            'loginFaild',
            'loginSuccess'
        ];
        Ext.each(allStates, function(s){
            var method = s === state ? 'addClass' : 'removeClass';
            this.loginBoxEl[method]('tine20login-' + s);
        }, this);
        
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