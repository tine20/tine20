/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: tineInit.js 7831 2009-04-22 22:37:18Z c.weiss@metaways.de $
 *
 * TODO         allow to add user defined part to Tine.title
 * TODO         move locale/timezone registry values to preferences MixedCollection?
 */

Ext.onReady(function() {
    Tine.Tinebase.tineInit.initWindow();
    Tine.Tinebase.tineInit.initDebugConsole();
    Tine.Tinebase.tineInit.initBootSplash();
    Tine.Tinebase.tineInit.initLocale();
    Tine.Tinebase.tineInit.initAjax();
    Tine.Tinebase.tineInit.initErrorHandler();
    Tine.Tinebase.tineInit.initRegistry();
    var waitForInits = function() {
        if (! Tine.Tinebase.tineInit.initList.initRegistry) {
            waitForInits.defer(100);
        } else {
            Tine.Tinebase.tineInit.initExtDirect();
            Tine.Tinebase.tineInit.initWindowMgr();
            Tine.Tinebase.tineInit.onLangFilesLoad();
            Tine.Tinebase.tineInit.checkSelfUpdate();
            Tine.Tinebase.tineInit.renderWindow();
        }
    };
    waitForInits();
});

/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

/**
 * @class Tine
 * @singleton
 */
Ext.namespace('Tine', 'Tine.Tinebase', 'Tine.Calendar');


/**
 * version of Tine 2.0 javascript client version, gets set a build time <br>
 * <b>Supported Properties:</b>
 * <table>
 *   <tr><td><b>codeName</b></td><td> codename of release</td></tr>
 *   <tr><td><b>buildType</b></td><td> buildType? of release</td></tr>
 *   <tr><td><b>buildDate</b></td><td> buildDate of release</td></tr>
 *   <tr><td><b>packageString</b></td><td> packageString of release</td></tr>
 *   <tr><td><b>releaseTime</b></td><td> releaseTime of release</td></tr>
 *   <tr><td><b>title</b></td><td> title of release</td></tr>
 * </table>
 * @type {Object}
 */
Tine.clientVersion = {};
Tine.clientVersion.codeName         = '$HeadURL$';
Tine.clientVersion.buildType        = 'none';
Tine.clientVersion.buildDate        = 'none';
Tine.clientVersion.packageString    = 'none';
Tine.clientVersion.releaseTime      = 'none';

/**
 * title of app (gets set at build time)
 * 
 * @type String
 */
Tine.title = 'Tine 2.0';

Ext.namespace('Tine.Tinebase');

/**
 * @class Tine.Tinebase.tineInit
 * @namespace Tine.Tinebase
 * @sigleton
 * static tine init functions
 */
Tine.Tinebase.tineInit = {
    /**
     * @cfg {String} getAllRegistryDataMethod
     */
    getAllRegistryDataMethod: 'Tinebase.getAllRegistryData',
    /**
     * @cfg {String} requestUrl
     */
    requestUrl: 'index.php',
    
    /**
     * list of initialised items
     */
    initList: {
        initWindow:   false,
        initViewport: false,
        initRegistry: false
    },
    
    initWindow: function() {
        // disable the native 'select all'
        Ext.getBody().on('keydown', function(e) {
            if(e.ctrlKey && e.getKey() == e.A){
                e.preventDefault();
            } else if(!window.isMainWindow && e.ctrlKey && e.getKey() == e.T){
                e.preventDefault();
            }
        });

        //init window is done in Ext.ux.PopupWindowMgr. yet
        this.initList.initWindow = true;
    },
    
    initDebugConsole: function() {
        var map = new Ext.KeyMap(Ext.getDoc(), [{
            key: [122], // F11
            ctrl:true,
            fn: Tine.Tinebase.common.showDebugConsole
        }]);
    },
    
    
    /**
     * Each window has exactly one viewport containing a card layout in its lifetime
     * The default card is a splash screen.
     * 
     * defautl wait panel (picture only no string!)
     */
    initBootSplash: function() {
        centerSplash = function() {
            var vp = Ext.getBody().getSize();
            var p = Ext.get('tine-viewport-waitcycle');
            p.moveTo(vp.width/2 - this.splash.width/2, vp.height/2 - this.splash.height/2);
            
            var by = Ext.get('tine-viewport-poweredby');
            if (by) {
                var bySize = by.getSize();
                by.setTop(vp.height/2 - bySize.height);
                by.setLeft(vp.width/2 - bySize.width);
                by.setStyle({'z-index': 100000})
            }
        };
        
        this.splash = {
            id: 'tine-viewport-waitcycle',
            border: false,
            layout: 'fit',
            width: 16,
            height: 16,
            html: '<div class="loading-indicator" width="16px" height="16px">&#160;</div><div id="tine-viewport-poweredby" class="tine-viewport-poweredby" style="position: absolute;">Powered by: <a target="_blank" href="http://www.tine20.org">Tine 2.0</a></div>',
            listeners: {
                scope: this,
                render: centerSplash,
                resize: centerSplash
            }
        };
        
        Tine.Tinebase.viewport = new Ext.Viewport({
            layout: 'fit',
            border: false,
            items: {
                id: 'tine-viewport-maincardpanel',
                layout: 'card',
                border: false,
                activeItem: 0,
                items: this.splash
            },
            listeners: {
                scope: this,
                render: function(p) {
                    this.initList.initViewport = true;
                }
            }
        });
    },
    
    renderWindow: function(){
        // check if user is already loged in        
        if (!Tine.Tinebase.registry.get('currentAccount')) {
            Tine.Login.showLoginDialog({
                defaultUsername: Tine.Tinebase.registry.get('defaultUsername'),
                defaultPassword: Tine.Tinebase.registry.get('defaultPassword'),
                scope: this,
                onLogin: function(response) {
                    Tine.Tinebase.tineInit.initList.initRegistry = false;
                    Tine.Tinebase.tineInit.initRegistry();
                    var waitForRegistry = function() {
                        if (Tine.Tinebase.tineInit.initList.initRegistry) {
                            Ext.MessageBox.hide();
                            Tine.Tinebase.tineInit.initExtDirect();
                            Tine.Tinebase.tineInit.renderWindow();
                        } else {
                            waitForRegistry.defer(100);
                        }
                    };
                    waitForRegistry();
                }
            });
            return;
        }
        
        // temporary handling for server side exceptions of http (html) window requests
        if (window.exception) {
            switch (exception.code) {
                // autorisation required
                case 401:
                    Tine.Login.showLoginDialog(onLogin, Tine.Tinebase.registry.get('defaultUsername'), Tine.Tinebase.registry.get('defaultPassword'));
                    return;
                    break;
                
                // generic exception
                default:
                    // we need to wait to grab initialData from mainscreen
                    //var win = new Tine.Tinebase.ExceptionDialog({});
                    //win.show();
                    return;
                    break;
            }
        }
        // todo: find a better place for stuff to do after successfull login
        Tine.Tinebase.tineInit.initAppMgr();
        
        /** temporary Tine.onReady for smooth transition to new window handling **/
        if (typeof(Tine.onReady) == 'function') {
            Tine.Tinebase.viewport.destroy();
            Tine.onReady();
            return;
        }
        
        // fetch window config from WindowMgr
        var c = Ext.ux.PopupWindowMgr.get(window) || {};
        
        // set window title
        window.document.title = c.title ? c.title : window.document.title;
        
        // finaly render the window contentes in a new card  
        var mainCardPanel = Ext.getCmp('tine-viewport-maincardpanel');
        var card = Tine.WindowFactory.getContentPanel(c);
        mainCardPanel.layout.container.add(card);
        mainCardPanel.layout.setActiveItem(card.id);
        card.doLayout();
        
        //var ping = new Tine.Tinebase.sync.Ping({});
    },

    initAjax: function() {
        Ext.Ajax.url = Tine.Tinebase.tineInit.requestUrl;
        Ext.Ajax.method = 'POST';
        
        Ext.Ajax.defaultHeaders = {
            'X-Tine20-Request-Type' : 'JSON'
        };
        
        // to use as jsonprc id
        Ext.Ajax.requestId = 0;
        
        /**
         * send custom headers and json key on Ext.Ajax.requests
         * 
         * @legacy implicitly transform requests for JSONRPC
         */
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.headers = options.headers || {};
            options.headers['X-Tine20-JsonKey'] = Tine.Tinebase.registry && Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('jsonKey') : '';
            
            // convert non Ext.Direct request to jsonrpc
            // - convert params
            // - convert error handling
            if (options.params && !options.isUpload) {
                var params = {};
                
                var def = typeof Tine.Tinebase.registry.get == 'function' ? Tine.Tinebase.registry.get('serviceMap').services[options.params.method] : false;
                if (def) {
                    // sort parms according to def
                    for (var i=0, p; i<def.parameters.length; i++) {
                        p = def.parameters[i].name;
                        params[p] = options.params[p];
                    }
                } else {
                    for (param in options.params) {
                        if (options.params.hasOwnProperty(param) && param != 'method') {
                            params[param] = options.params[param];
                        }
                    }
                }
                
                options.jsonData = Ext.encode({
                    jsonrpc: '2.0',
                    method: options.params.method,
                    params: params,
                    id: ++Ext.Ajax.requestId
                });
                
                options.cbs = {};
                options.cbs.success  = options.success  || null;
                options.cbs.failure  = options.failure  || null;
                options.cbs.callback = options.callback || null;
                
                options.isImplicitJsonRpc = true;
                delete options.params;
                delete options.success;
                delete options.failure;
                delete options.callback;
            }
        });
        
        /**
         * detect resoponse errors (e.g. html from xdebug)
         * 
         * @legacy implicitly transform requests from JSONRPC
         */
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
            // detect resoponse errors (e.g. html from xdebug)
            if (! options.isUpload && ! response.responseText.match(/^([{\[])|(<\?xml)+/)) {
                var htmlText = response.responseText;
                response.responseText = Ext.util.JSON.encode({
                    msg: htmlText,
                    trace: []
                });
                
                connection.fireEvent('requestexception', connection, response, options);
            }
            
            // strip jsonrpc fragments for non Ext.Direct requests
            if (options.isImplicitJsonRpc){
                var jsonrpc = Ext.decode(response.responseText);
                if (jsonrpc.result) {
                    response.responseText = Ext.encode(jsonrpc.result);
                    
                    if(options.cbs.success){
                        options.cbs.success.call(options.scope, response, options);
                    }
                    if(options.cbs.callback){
                        options.cbs.callback.call(options.scope, options, true, response);
                    }
                } else {
                    
                    response.responseText = Ext.encode(jsonrpc.error);
                    
                    if(options.cbs.failure){
                        options.cbs.failure.call(options.scope, response, options);
                    } else if(options.cbs.callback){
                        options.cbs.callback.call(options.scope, options, false, response);
                    } else {
                        // generic error handling
                        connection.fireEvent('requestexception', connection, response, options);
                    }
                }
            }
        });
        
        /**
         * generic error handling
         * 
         * executed on requestexceptions and error states which are not handled by failure/callback functions
         */
        Ext.Ajax.on('requestexception', function(connection, response, options){
            
            // if communication is lost, we can't create a nice ext window.
            if (response.status === 0) {
                alert(_('Connection lost, please check your network!'));
                return false;
            }
            
            // decode JSONRPC response
            var rpcData = response ? Ext.util.JSON.decode(response.responseText) : null;
            
            // server did not respond anything
            if (! rpcData) {
                //alert(_('The server did not respond to your request. Please check your network or contact your administrator.'));
                Ext.MessageBox.show({
                    title: _('No response'), 
                    msg: _('We did not receive a response from the server. Your network could be down or a timeout occurred.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
                return true;
            }
            
            // error data
            var data = (rpcData.data) 
                ? rpcData.data 
                : {code: 0, message: (rpcData.msg) ? rpcData.msg : rpcData.message};
            
            switch(data.code) {
                // not authorised
                case 401:
                if (! options.params || options.params.method != 'Tinebase.logout') {
                    Ext.MessageBox.show({
                        title: _('Authorisation Required'), 
                        msg: _('Your session timed out. You need to login again.'),
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING,
                        fn: function() {
                            window.location.href = window.location.href;
                        }
                    });
                }
                break;
                
                // insufficient rights
                case 403:
                Ext.MessageBox.show({
                    title: _('Insufficient Rights'), 
                    msg: _('Sorry, you are not permitted to perform this action'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
                break;
                
                // not found
                case 404:
                Ext.MessageBox.show({
                    title: _('Not Found'), 
                    msg: _('Sorry, your request could not be completed because the required data could not be found. In most cases this means that someone already deleted the data. Please refresh your current view.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
                break;
                
                // concurrency conflict
                case 409:
                Ext.MessageBox.show({
                    title: _('Concurrent Updates'), 
                    msg: _('Someone else saved this record while you where editing the data. You need to reload and make your changes again.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING
                });
                break;
                
                // generic failure -> notify developers / only if no custom exception handler has been defined in options
                default:
                
                // NOTE: exceptionHandler is depricated use the failure function of the request or listen to the exception events
                //       of the Ext.Direct framework
                if (typeof options.exceptionHandler !== 'function' || 
                    false === options.exceptionHandler.call(options.scope, response, options)) {
                    var windowHeight = 400;
                    if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
                        windowHeight = Ext.getBody().getHeight(true) * 0.7;
                    }
                    
                    if (! Tine.Tinebase.exceptionDlg) {
                        Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                            height: windowHeight,
                            exceptionInfo: {
                                msg   : data.message,
                                trace : data.trace
                            },
                            listeners: {
                                close: function() {
                                    Tine.Tinebase.exceptionDlg = null;
                                }
                            }
                        });
                        Tine.Tinebase.exceptionDlg.show();
                    }
                }
                break;
            }
            
        });
    },
    
        
    /**
     * init a global error handler
     */
    initErrorHandler: function() {
        window.onerror = !window.onerror ? Tine.Tinebase.tineInit.globalErrorHandler : window.onerror.createSequence(Tine.Tinebase.tineInit.globalErrorHandler);
    },
    
    /**
     * @todo   make this working in safari
     * @return {string}
     */
    getNormalisedError: function() {
        var error = {
            name       : 'unknown error',
            message    : 'unknown',
            number     : 'unknown',
            description: 'unknown',
            url        : 'unknown',
            line       : 'unknown'
        };
        
        // NOTE: Arguments is not always a real Array
        var args = [];
        for (var i=0; i<arguments.length; i++) {
            args[i] = arguments[i];
        }
        
        //var lines = ["The following JS error has occured:"];
        if (args[0] instanceof Error) { // Error object thrown in try...catch
            error.name        = args[0].name;
            error.message     = args[0].message;
            error.number      = args[0].number & 0xFFFF; //Apply binary arithmetic for IE number, firefox returns message string in element array element 0
            error.description = args[0].description;
            
        } else if ((args.length == 3) && (typeof(args[2]) == "number")) { // Check the signature for a match with an unhandled exception
            error.name    = 'catchable exception'
            error.message = args[0];
            error.url     = args[1];
            error.line    = args[2];
        } else {
            error.message     = "An unknown JS error has occured.";
            error.description = 'The following information may be useful:' + "\n";
            for (var x = 0; x < args.length; x++) {
                error.description += (Ext.encode(args[x]) + "\n");
            }
        }
        return error;
    },
    
    globalErrorHandler: function() {
        var error = Tine.Tinebase.tineInit.getNormalisedError.apply(this, arguments);
        
        var traceHtml = '<table>';
        for (p in error) {
            if (error.hasOwnProperty(p)) {
                traceHtml += '<tr><td><b>' + p + '</b></td><td>' + error[p] + '</td></tr>'
            }
        }
        traceHtml += '</table>'
        
        // check for spechial cases we don't want to handle
        if (traceHtml.match(/versioncheck/)) {
            return true;
        }
        // we don't wanna know fancy FF3.5 crom bugs
        if (traceHtml.match(/chrome/)) {
            return true;
        }
        
        var data = {
            msg: 'js exception: ' + error.message,
            traceHTML: traceHtml
        };
        
        var windowHeight = 400;
        if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
            windowHeight = Ext.getBody().getHeight(true) * 0.7;
        }
        
        if (! Tine.Tinebase.exceptionDlg) {
            Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                height: windowHeight,
                exceptionInfo: data,
                listeners: {
                    close: function() {
                        Tine.Tinebase.exceptionDlg = null;
                    }
                }
            });
            Tine.Tinebase.exceptionDlg.show(Tine.Tinebase.exceptionDlg);
        }
        return true;
    },
    
    /**
     * init registry
     */
    initRegistry: function() {
        if (window.isMainWindow) {
            Ext.Ajax.request({
                params: {
                    method: Tine.Tinebase.tineInit.getAllRegistryDataMethod
                },
                success: function(response, request) {
                    var registryData = Ext.util.JSON.decode(response.responseText);
                    for (var app in registryData) {
                        if (registryData.hasOwnProperty(app)) {
                            var appData = registryData[app];
                            if (Tine[app]) {
                                Tine[app].registry = new Ext.util.MixedCollection();

                                for (var key in appData) {
                                    if (appData.hasOwnProperty(key)) {
                                        if (key == 'preferences') {
                                            var prefs = new Ext.util.MixedCollection();
                                            for (var pref in appData[key]) {
                                                if (appData[key].hasOwnProperty(pref)) {
                                                    prefs.add(pref, appData[key][pref]);
                                                }
                                            }
                                            prefs.on('replace', Tine.Tinebase.tineInit.onPreferenceChange);
                                            Tine[app].registry.add(key, prefs);
                                        } else {
                                            Tine[app].registry.add(key, appData[key]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // update window factory window type (required after login)
                    if (Tine.Tinebase.registry && Tine.Tinebase.registry.get('preferences')) {
                        var windowType = Tine.Tinebase.registry.get('preferences').get('windowtype');
                        
                        if (Tine.WindowFactory && Tine.WindowFactory.windowType != windowType) {
                            Tine.WindowFactory.windowType = windowType;
                        }
                    }
                    
                    // init state with data from reg
                    Tine.Tinebase.tineInit.initState();
                    
                    Tine.Tinebase.tineInit.initList.initRegistry = true;
                }
            });
        } else {
            var mainWindow = Ext.ux.PopupWindowGroup.getMainWindow();
            
            for (p in mainWindow.Tine) {
                if (mainWindow.Tine[p].hasOwnProperty('registry') && Tine.hasOwnProperty(p)) {
                    Tine[p].registry = mainWindow.Tine[p].registry;
                }
            }
            
            Tine.Tinebase.tineInit.initList.initRegistry = true;
        }
    },
    
    /**
     * executed when a value in Tinebase registry/preferences changed
     * @param {string} key
     * @param {value} oldValue
     * @param {value} newValue
     */
    onPreferenceChange: function(key, oldValue, newValue) {
        switch (key) {
            case 'windowtype':
                //console.log('hier');
                //break;
            case 'timezone':
            case 'locale':
                if (window.google && google.gears && google.gears.localServer) {
                    var pkgStore = google.gears.localServer.openStore('tine20-package-store');
                    if (pkgStore) {
                        google.gears.localServer.removeStore('tine20-package-store');
                    }
                }
                // reload mainscreen (only if timezone or locale have changed)
                window.location = window.location.href.replace(/#+.*/, '');
                break;
        }
    },
    
    /**
     * check if selfupdate is needed
     */
    checkSelfUpdate: function() {
        if (! Tine.Tinebase.registry.get('version')) {
            return false;
        }        
        
        var needSelfUpdate, serverVersion = Tine.Tinebase.registry.get('version'), clientVersion = Tine.clientVersion;
        if (clientVersion.codeName.match(/^\$HeadURL/)) {
            return;
        }
        
        var cp = new Ext.state.CookieProvider({});
        
        if (serverVersion.packageString != 'none') {
            needSelfUpdate = (serverVersion.packageString !== clientVersion.packageString);
        } else {
            needSelfUpdate = (serverVersion.codeName !== clientVersion.codeName);
        }
        
        if (needSelfUpdate) {
            if (window.google && google.gears && google.gears.localServer) {
                google.gears.localServer.removeManagedStore('tine20-store');
                google.gears.localServer.removeStore('tine20-package-store');
            }
            if (cp.get('clientreload', '0') == '0') {
                
                cp.set('clientreload', '1');
                window.location = window.location.href.replace(/#+.*/, '');
                return;
                
            } else {
                new Ext.LoadMask(Ext.getBody(), {
                    msg: _('Fatal Error: Client self-update failed, please contact your administrator and/or restart/reload your browser.'),
                    msgCls: ''
                }).show();
            }
        } else {
            cp.clear('clientreload');
            
            // if no selfupdate is needed we store langfile and index.php in manifest
            if (window.google && google.gears && google.gears.localServer) {
                if (serverVersion.buildType == 'RELEASE') {
                    var pkgStore = google.gears.localServer.createStore('tine20-package-store');
                    var resources = [
                        '',
                        'index.php',
                        'Tinebase/js/Locale/build/' + Tine.Tinebase.registry.get('locale').locale + '-all.js'
                    ];
                    
                    Ext.each(resources, function(resource) {
                        if (! pkgStore.isCaptured(resource)) {
                            pkgStore.capture(resources, function(){/*console.log(arguments)*/});
                        }
                    }, this);
                } else {
                    google.gears.localServer.removeStore('tine20-package-store');
                }
            }
        }
    },
    
    /**
     * initialise window and windowMgr (only popup atm.)
     */
    initWindowMgr: function() {
        /**
         * init the window handling
         */
        Ext.ux.PopupWindow.prototype.url = 'index.php';
        
        /**
         * initialise window types
         */
        var windowType = (Tine.Tinebase.registry.get('preferences') && Tine.Tinebase.registry.get('preferences').get('windowtype')) 
            ? Tine.Tinebase.registry.get('preferences').get('windowtype') 
            : 'Browser';
            
        Tine.WindowFactory = new Ext.ux.WindowFactory({
            windowType: windowType
        });
        
        /**
         * register MainWindow
         */
        if (window.isMainWindow) {
            Ext.ux.PopupWindowMgr.register({
                name: window.name,
                popup: window,
                contentPanelConstructor: 'Tine.Tinebase.MainScreen'
            });
        }
    },
    
    /**
     * add provider to Ext.Direct based on Tine servicemap
     */
    initExtDirect: function() {
        var sam = Tine.Tinebase.registry.get('serviceMap');
        
        Ext.Direct.addProvider(Ext.apply(sam, {
            'type'     : 'zfprovider',
            'namespace': 'Tine',
            'url'      : sam.target
        }));
    },
    
    /**
     * initialise state provider
     */
    initState: function() {
        Ext.state.Manager.setProvider(new Tine.Tinebase.StateProvider());
    },
    
    /**
     * initialise application manager
     */
    initAppMgr: function() {
        Tine.Tinebase.appMgr = new Tine.Tinebase.AppManager();
    },
    
    /**
     * config locales
     */
    initLocale: function() {
        //Locale.setlocale(Locale.LC_ALL, '');
        Tine.Tinebase.tranlation = new Locale.Gettext();
        Tine.Tinebase.tranlation.textdomain('Tinebase');
        window._ = function(msgid) {
            return Tine.Tinebase.tranlation.dgettext('Tinebase', msgid);
        };
    },
    
    /**
     * Last stage of initialisation, to be done after Tine.onReady!
     */
    onLangFilesLoad: function() {
    //    Ext.ux.form.DateTimeField.prototype.format = Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium');
    }
};
