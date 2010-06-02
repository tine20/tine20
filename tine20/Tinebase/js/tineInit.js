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
    Tine.Tinebase.tineInit.initRegistry();
    Tine.Tinebase.tineInit.initLibs();
    var waitForInits = function() {
        if (! Tine.Tinebase.tineInit.initList.initRegistry) {
            waitForInits.defer(100);
        } else {
            Tine.Tinebase.tineInit.initExtDirect();
            Tine.Tinebase.tineInit.initState();
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
Tine.weburl = 'http://www.tine20.org/';

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
     * @cfg {Boolean} stateful
     */
    stateful: true,

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
        
        // disable generic drops
        Ext.getBody().on('dragover', function(e) {
            e.stopPropagation();
            e.preventDefault();
            e.browserEvent.dataTransfer.dropEffect = 'none';
        }, this);
        
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
            html: '<div class="loading-indicator" width="16px" height="16px">&#160;</div><div id="tine-viewport-poweredby" class="tine-viewport-poweredby" style="position: absolute;">Powered by: <a target="_blank" href="' + Tine.weburl + '">' + Tine.title + '</a></div>',
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
        var mainCardPanel = Ext.getCmp('tine-viewport-maincardpanel');
        
        // check if user is already loged in        
        if (!Tine.Tinebase.registry.get('currentAccount')) {
            if (! Tine.loginPanel) {
                Tine.loginPanel = new Tine.Tinebase.LoginPanel({
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
                                Tine.Tinebase.tineInit.initState();
                                Tine.Tinebase.tineInit.renderWindow();
                            } else {
                                waitForRegistry.defer(100);
                            }
                        };
                        waitForRegistry();
                    }
                });
                mainCardPanel.add(Tine.loginPanel);
            }
            mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
            Tine.loginPanel.doLayout();
            
            return;
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
        var card = Tine.WindowFactory.getCenterPanel(c);
        mainCardPanel.add(card);
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
         * inspect all requests done via the ajax singleton
         * 
         * - send custom headers
         * - send json key 
         * - implicitly transform non jsonrpc requests
         * 
         * NOTE: implicitly transformed reqeusts get their callback fn's proxied 
         *       through generic response inspectors as defined below
         */
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.headers = options.headers || {};
            options.headers['X-Tine20-JsonKey'] = Tine.Tinebase.registry && Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('jsonKey') : '';
            
            // convert non Ext.Direct request to jsonrpc
            // - convert params
            // - convert error handling
            if (options.params && !options.isUpload) {
                var params = {};
                
                var def = Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('serviceMap').services[options.params.method] : false;
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
         * inspect completed responses => staus code == 200
         * 
         * - detect resoponse errors (e.g. html from xdebug) and convert to exceptional states
         * - implicitly transform requests from JSONRPC
         * 
         *  NOTE: All programatically catchable exceptions lead to successfull requests
         *        with the jsonprc protocol. For implicitly converted jsonprc requests we 
         *        transform error states here and route them to the error methods defined 
         *        in the request options
         *        
         *  NOTE: Illegal json data responses are mapped to error code 530
         *        Empty resonses (Ext.Decode can't deal with them) are maped to 540
         */
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
            
            // detect resoponse errors (e.g. html from xdebug) and convert into error response
            if (! options.isUpload && ! response.responseText.match(/^([{\[])|(<\?xml)+/)) {
                var exception = {
                    code: response.responseText !== "" ? 530 : 540,
                    message: response.responseText !== "" ? 'illegal json data in response' : 'empty response',
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    response: response.responseText
                };
                
                // encapsulate as jsonrpc response
                var requestOptions = Ext.decode(options.jsonData);
                response.responseText = Ext.encode({
                    jsonrpc: requestOptions.jsonrpc,
                    id: requestOptions.id,
                    error: {
                        code: -32000,
                        message: exception.message,
                        data: exception
                    }
                });
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
                        var responseData = Ext.decode(response.responseText)
                        var exception = responseData.data ? responseData.data : responseData;
                        exception.request = options.jsonData;
                        exception.response = response.responseText;
                        
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    }
                }
            }
        });
        
        /**
         * inspect request exceptions
         *  - convert to jsonrpc compatiple exceptional states
         *  - call generic exception handler if no handler is defined in request options
         *  
         * NOTE: Request exceptions are exceptional state from web-server:
         *       -> status codes != 200 : This kind of exceptions are not part of the jsonrpc protocol
         *       -> timeouts: status code 520
         */
        Ext.Ajax.on('requestexception', function(connection, response, options) {
            // map connection errors to errorcode 510 and timeouts to 520
            var errorCode = response.status > 0 ? response.status :
                            (response.status == 0 ? 510 : 520);
                            
            // convert into error response
            if (! options.isUpload) {
                var exception = {
                    code: errorCode,
                    message: 'request exception: ' + response.statusText,
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    response: response.responseText
                };
                
                // encapsulate as jsonrpc response
                var requestOptions = Ext.decode(options.jsonData);
                response.responseText = Ext.encode({
                    jsonrpc: requestOptions.jsonrpc,
                    id: requestOptions.id,
                    error: {
                        code: -32000,
                        message: exception.message,
                        data: exception
                    }
                });
            }
            
            if (options.isImplicitJsonRpc) {
                var jsonrpc = Ext.decode(response.responseText);
                
                response.responseText = Ext.encode(jsonrpc.error);
                    
                if(options.cbs.failure){
                    options.cbs.failure.call(options.scope, response, options);
                } else if(options.cbs.callback){
                    options.cbs.callback.call(options.scope, options, false, response);
                } else {
                    var responseData = Ext.decode(response.responseText)
                    var exception = responseData.data ? responseData.data : responseData;
                    
                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }
                
            } else if (! options.failure && ! options.callback) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        });
    },
    
    /**
     * init registry
     */
    initRegistry: function() {
        Ext.namespace('Tine.Tinebase.registry');
        if (window.isMainWindow) {
            Ext.Ajax.request({
                params: {
                    method: Tine.Tinebase.tineInit.getAllRegistryDataMethod
                },
                failure: function() {
                    // if registry could not be loaded, this is mostly due to missconfiguaration
                    // don't send error reports for that!
                    Tine.Tinebase.ExceptionHandler.handleRequestException({
                        code: 503
                    });
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
                    
                    Tine.Tinebase.tineInit.initList.initRegistry = true;
                }
            });
        } else {
            //var mainWindow = Ext.ux.PopupWindowGroup.getMainWindow();
            var mainWindow = Ext.ux.PopupWindowMgr.getMainWindow();
            
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
            case 'confirmLogout':
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
     * initialise state provider
     */
    initState: function() {
        // NOTE: IE is as always pain in the ass! cross window issues prohibit serialisation of state objects
        if (Tine.Tinebase.tineInit.stateful === true &&  !Ext.isIE) {
            if (window.isMainWindow) {
                Ext.state.Manager.setProvider(new Tine.Tinebase.StateProvider());
            } else {
                var mainWindow = Ext.ux.PopupWindowMgr.getMainWindow();
                Ext.state.Manager = mainWindow.Ext.state.Manager;
            }
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
     * init external libraries
     */
    initLibs: function() {
        if (OpenLayers) {
            // fix OpenLayers script location to find images/themes/...
            OpenLayers._getScriptLocation = function() {
                return 'library/OpenLayers/';
            }
        }
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
        Tine.Tinebase.translation = new Locale.Gettext();
        Tine.Tinebase.translation.textdomain('Tinebase');
        window._ = function(msgid) {
            return Tine.Tinebase.translation.dgettext('Tinebase', msgid);
        };
    },
    
    /**
     * Last stage of initialisation, to be done after Tine.onReady!
     */
    onLangFilesLoad: function() {
    //    Ext.ux.form.DateTimeField.prototype.format = Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium');
    }
};
