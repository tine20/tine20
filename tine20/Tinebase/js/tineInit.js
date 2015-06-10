/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         allow to add user defined part to Tine.title
 */

/*global Ext, Tine, google, OpenLayers, Locale, */

/** ------------------------- Ext.ux Initialisation ------------------------ **/

Ext.ux.Printer.BaseRenderer.prototype.stylesheetPath = 'Tinebase/js/ux/Printer/print.css';


/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

/**
 * @class Tine
 * @singleton
 */
Ext.namespace('Tine', 'Tine.Tinebase', 'Tine.Calendar');

/**
 * version of Tine 2.0 javascript client version, gets set a build / release time <br>
 * <b>Supported Properties:</b>
 * <table>
 *   <tr><td><b>buildType</b></td><td> type of build</td></tr>
 *   <tr><td><b>buildDate</b></td><td> date of build</td></tr>
 *   <tr><td><b>buildRevision</b></td><td> revision of build</td></tr>
 *   <tr><td><b>codeName</b></td><td> codename of release</td></tr>
 *   <tr><td><b>packageString</b></td><td> packageString of release</td></tr>
 *   <tr><td><b>releaseTime</b></td><td> releaseTime of release</td></tr>
 * </table>
 * @type {Object}
 */
Tine.clientVersion = {};
Tine.clientVersion.buildType        = 'none';
Tine.clientVersion.buildDate        = 'none';
Tine.clientVersion.buildRevision    = 'none';
Tine.clientVersion.codeName         = 'none';
Tine.clientVersion.packageString    = 'none';
Tine.clientVersion.releaseTime      = 'none';

/**
 * title of app (gets set at build time)
 * 
 * @type String
 */
Tine.title = 'Tine 2.0<small>org</small>';
Tine.weburl = 'http://www.tine20.org';
Tine.helpUrl = 'http://wiki.tine20.org';

/**
 * quiet logging in release mode
 */
Ext.LOGLEVEL = Tine.clientVersion.buildType === 'RELEASE' ? 0 : 7;
Tine.log = Ext.ux.log;

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
     * prefix for localStorage keys
     * @type String
     */
    lsPrefix: Tine.Tinebase.common.getUrl('path') + 'Tine',
    
    onPreferenceChangeRegistered: false,
    
    initWindow: function () {
        Ext.getBody().on('keydown', function (e) {
            if (e.ctrlKey && e.getKey() === e.A && ! (e.getTarget('form') || e.getTarget('input') || e.getTarget('textarea'))) {
                // disable the native 'select all'
                e.preventDefault();
            } else if (e.getKey() === e.BACKSPACE && ! (e.getTarget('form') || e.getTarget('input') || e.getTarget('textarea'))) {
                // disable the native 'history back'
                e.preventDefault();
            } else if (!window.isMainWindow && e.ctrlKey && e.getKey() === e.T) {
                // disable the native 'new tab' if in popup window
                e.preventDefault();
            } else if (window.isMainWindow && e.ctrlKey && e.getKey() === e.L) {
                // reload on ctrl-l
                Tine.Tinebase.common.reload({
                    clearCache: true
                });
            }
        });
        
        // disable generic drops
        Ext.getBody().on('dragover', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.browserEvent.dataTransfer.dropEffect = 'none';
        }, this);
    },
    
    initDebugConsole: function () {
        var map = new Ext.KeyMap(Ext.getDoc(), [{
            key: [122], // F11
            ctrl: true,
            fn: Tine.Tinebase.common.showDebugConsole
        }]);
    },
    
    
    /**
     * Each window has exactly one viewport containing a card layout in its lifetime
     * The default card is a splash screen.
     * 
     * defautl wait panel (picture only no string!)
     */
    initBootSplash: function () {
        
        this.splash = {
            xtype: 'container',
            id: 'tine-viewport-waitcycle',
            border: false,
            layout: 'fit',
            width: 16,
            height: 16,
            // the content elements come from the initial html so they are displayed fastly
            contentEl: Ext.select('div[class^=tine-viewport-]')
        };
        
        Tine.Tinebase.viewport = new Ext.Viewport({
            layout: 'fit',
            border: false,
            items: {
                xtype: 'container',
                id: 'tine-viewport-maincardpanel',
                ref: 'tineViewportMaincardpanel',
                isWindowMainCardPanel: true,
                layout: 'card',
                border: false,
                activeItem: 0,
                items: this.splash
            }
        });
    },

    initLoginPanel: function() {
        if (window.isMainWindow && ! Tine.loginPanel) {
            var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
            Tine.loginPanel = new Tine.Tinebase.LoginPanel({
                defaultUsername: Tine.Tinebase.registry.get('defaultUsername'),
                defaultPassword: Tine.Tinebase.registry.get('defaultPassword')
            });
            mainCardPanel.add(Tine.loginPanel);
        }
        
        // event firing is unpredictable in IE9/10/11, you'll never know when it comes ...
        if (! Ext.isNewIE) {
            Tine.Tinebase.registry.on('replace', function(key, oldValue, newValue) {
                if (oldValue && !newValue) {
                    Tine.log.info('tineInit::initLoginPanel - handle logout in other window');
                    if (window.isMainWindow) {
                        Tine.Tinebase.common.reload();
                    } else {
                        Ext.ux.PopupWindow.close(window);
                    }
                }
            }, this, 'currentAccount');
        }
    },

    showLoginBox: function(cb, scope) {
        var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel,
            activeItem = mainCardPanel.layout.activeItem;

        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        Tine.loginPanel.doLayout();
        Tine.loginPanel.onLogin = function(response) {
            mainCardPanel.layout.setActiveItem(activeItem);
            cb.call(scope||window, response);
        };

//        //listen for other windows login?
//        Tine.Tinebase.registry.on('replace', function() {
//            mainCardPanel.layout.setActiveItem(activeItem);
//        }, this, 'currentAccount');

    },

    renderWindow: function () {
        Tine.log.info('renderWindow::start');
        
        var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;

        // check if user is already logged in
        if (! Tine.Tinebase.registry.get('currentAccount')) {
            Tine.Tinebase.tineInit.showLoginBox(function(response){
                Tine.log.info('tineInit::renderWindow -fetch users registry');
                Tine.Tinebase.tineInit.initRegistry(true, function() {
                    Tine.log.info('tineInit::renderWindow - registry fetched, render main window');
                    Ext.MessageBox.hide();
                    Tine.Tinebase.tineInit.initWindowMgr();
                    Tine.Tinebase.tineInit.renderWindow();
                });
            });
            
            return;
        }

        // experimental deep link
        if (window.location.hash) {
            var hash = window.location.hash.replace(/^#+/, ''),
                config;

            if (hash.match(/\//)) {
                var args = hash.split('/'),
                    app = Tine.Tinebase.appMgr.get(args.shift()),
                    method = args.shift();

                config = app.dispatchRoute(method, args);

            } else {
                //http://tine.example.com/#{"name":"TimesheetEditWindow_0","contentPanelConstructor":"Tine.Timetracker.TimesheetEditDialog","recordId":0}
                config = Ext.decode(hash);
            }

            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, Tine.Tinebase.common.getUrl());
            }

            // what about plugins?
            Ext.applyIf(config, {
                name: window.name,
                popup: window
            });

            window.name = config.name;

            // disable mainWindows per hash for the moment. MainWindow concept needs to be rethought
            // in the context of window manager rewrite
            window.isMainWindow = false;

            Ext.ux.PopupWindowMgr.register(new Ext.ux.PopupWindow(config));
        }

        /**
         * register MainWindow
         */
        else if (window.isMainWindow) {
            Ext.ux.PopupWindowMgr.register({
                name: window.name,
                popup: window,
                contentPanelConstructor: 'Tine.Tinebase.MainScreenPanel'
            });
        }


        Tine.log.info('renderWindow::before get window');
        
        // fetch window config from WindowMgr
        var c = Ext.ux.PopupWindowMgr.get(window) || {};
        
        // set window title
        window.document.title = Ext.util.Format.stripTags(c.title ? c.title : window.document.title);
        
        Tine.log.info('renderWindow::getCenterPanel');
        
        // finally render the window contents in a new card
        var card = Tine.WindowFactory.getCenterPanel(c);
        mainCardPanel.add(card);
        mainCardPanel.layout.setActiveItem(card.id);
        card.doLayout();
        
        window.initializationComplete = true;
    },

    initAjax: function () {
        Ext.Ajax.url = Tine.Tinebase.tineInit.requestUrl;
        Ext.Ajax.method = 'POST';
        
        Ext.Ajax.defaultHeaders = {
            'X-Tine20-Request-Type' : 'JSON'
        };
        
        Ext.Ajax.transactions = {};
        
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
        Ext.Ajax.on('beforerequest', function (connection, options) {
            options.headers = options.headers || {};
            options.headers['X-Tine20-JsonKey'] = Tine.Tinebase.registry && Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('jsonKey') : '';
            options.headers['X-Tine20-TransactionId'] = Tine.Tinebase.data.Record.generateUID();
            
            options.url = Ext.urlAppend((options.url ? options.url : Tine.Tinebase.tineInit.requestUrl),  'transactionid=' + options.headers['X-Tine20-TransactionId']);
            
            // convert non Ext.Direct request to jsonrpc
            // - convert params
            // - convert error handling
            if (options.params && !options.isUpload) {
                var params = {};
                
                var def = Tine.Tinebase.registry.get('serviceMap') ? Tine.Tinebase.registry.get('serviceMap').services[options.params.method] : false;
                if (def) {
                    // sort parms according to def
                    for (var i = 0, p; i < def.parameters.length; i += 1) {
                        p = def.parameters[i].name;
                        params[p] = options.params[p];
                    }
                } else {
                    for (var param in options.params) {
                        if (options.params.hasOwnProperty(param) && param !== 'method') {
                            params[param] = options.params[param];
                        }
                    }
                }
                
                options.jsonData = Ext.encode({
                    jsonrpc: '2.0',
                    method: options.params.method,
                    params: params,
                    id: ++Ext.Direct.TID
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
            
            Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']] = {
                date: new Date(),
                json: options.jsonData
            };
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
         *        Memory exhausted to 550
         */
        Ext.Ajax.on('requestcomplete', function (connection, response, options) {
            delete Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']];
            
            // detect resoponse errors (e.g. html from xdebug) and convert into error response
            if (! options.isUpload && ! response.responseText.match(/^([{\[])|(<\?xml)+/)) {
                var exception = {
                    code: response.responseText !== "" ? 530 : 540,
                    message: response.responseText !== "" ? 'illegal json data in response' : 'empty response',
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    response: response.responseText
                };
                
                // Fatal error: Allowed memory size of n bytes exhausted (tried to allocate m bytes) 
                if (response.responseText.match(/^Fatal error: Allowed memory size of /m)) {
                    Ext.apply(exception, {
                        code: 550,
                        message: response.responseText
                    });
                }
                
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
            if (options.isImplicitJsonRpc) {
                var jsonrpc = Ext.decode(response.responseText);
                if (jsonrpc.result) {
                    response.responseText = Ext.encode(jsonrpc.result);
                    
                    if (options.cbs.success) {
                        options.cbs.success.call(options.scope, response, options);
                    }
                    if (options.cbs.callback) {
                        options.cbs.callback.call(options.scope, options, true, response);
                    }
                } else {
                    
                    response.responseText = Ext.encode(jsonrpc.error);
                    
                    if (options.cbs.failure) {
                        options.cbs.failure.call(options.scope, response, options);
                    } else if (options.cbs.callback) {
                        options.cbs.callback.call(options.scope, options, false, response);
                    } else {
                        var responseData = Ext.decode(response.responseText);
                            
                        exception = responseData.data ? responseData.data : responseData;
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
        Ext.Ajax.on('requestexception', function (connection, response, options) {
            delete Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']];
            // map connection errors to errorcode 510 and timeouts to 520
            var errorCode = response.status > 0 ? response.status :
                            (response.status === 0 ? 510 : 520);
                            
            // convert into error response
            if (! options.isUpload) {
                var exception = {
                    code: errorCode,
                    message: 'request exception: ' + response.statusText,
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    requestHeaders: options.headers,
                    openTransactions: Ext.Ajax.transactions,
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
            
            // NOTE: Tine.data.RecordProxy is implicitRPC atm.
            if (options.isImplicitJsonRpc) {
                var jsonrpc = Ext.decode(response.responseText);
                
                response.responseText = Ext.encode(jsonrpc.error);
                    
                if (options.cbs.failure) {
                    options.cbs.failure.call(options.scope, response, options);
                } else if (options.cbs.callback) {
                    options.cbs.callback.call(options.scope, options, false, response);
                } else {
                    var responseData = Ext.decode(response.responseText);
                    
                    exception = responseData.data ? responseData.data : responseData;
                    
                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }
                
            } else if (! options.failure && ! options.callback) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        });
    },

    /**
     * init registry
     *
     * @param {Boolean} forceReload
     * @param {Function} cb
     * @param {Object} scope
     */
    initRegistry: function (forceReload, cb, scope) {
        Tine.Tinebase.registry = store.namespace(Tine.Tinebase.tineInit.lsPrefix + '.' + 'Tinebase.registry');

        var version = Tine.Tinebase.registry.get('version'),
            userApplications = Tine.Tinebase.registry.get('userApplications') || [];

        var reloadNeeded =
               !version
            || !userApplications
            || userApplications.length < 2;

        if (forceReload || reloadNeeded) {
            Ext.Ajax.request({
                timeout: 120000, // 2 minutes
                params: {
                    method: Tine.Tinebase.tineInit.getAllRegistryDataMethod
                },
                failure: function () {
                    // if registry could not be loaded, this is mostly due to missconfiguaration
                    // don't send error reports for that!
                    Tine.Tinebase.ExceptionHandler.handleRequestException({
                        code: 503
                    });
                },
                success: function (response, request) {
                    var registryData = Ext.util.JSON.decode(response.responseText);
                    for (var app in registryData) {
                        if (registryData.hasOwnProperty(app)) {
                            var appData = registryData[app];
                            if (Tine[app]) {
                                Tine[app].registry = store.namespace(Tine.Tinebase.tineInit.lsPrefix + '.' + app + '.registry');

                                for (var key in appData) {
                                    if (appData.hasOwnProperty(key)) {
                                        if (key === 'preferences') {
                                            Tine[app].preferences = store.namespace(Tine.Tinebase.tineInit.lsPrefix + '.' + app + '.preferences');
                                            for (var pref in appData[key]) {
                                                if (appData[key].hasOwnProperty(pref)) {
                                                    Tine[app].preferences.set(pref, appData[key][pref]);
                                                }
                                            }

                                        } else {
                                            Tine[app].registry.set(key, appData[key]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    Tine.Tinebase.tineInit.onRegistryLoad();

                    cb.call(scope);
                }
            });
        } else {
            for (var app,i=0;i<userApplications.length;i++) {
                app = userApplications[i].name;
                if (Tine[app]) {
                  Tine[app].registry = store.namespace(Tine.Tinebase.tineInit.lsPrefix + '.' + app + '.registry');
                  Tine[app].preferences = store.namespace(Tine.Tinebase.tineInit.lsPrefix + '.' + app + '.preferences');
                }
            }

            Tine.Tinebase.tineInit.onRegistryLoad();
            cb.call(scope);
        }


    },

    /**
     * apply registry data
     */
    onRegistryLoad: function() {
        if (! Tine.Tinebase.tineInit.onPreferenceChangeRegistered 
            && Tine.Tinebase.registry.get('preferences')
            && Tine.Tinebase.registry.get('currentAccount')
            && ! Ext.isNewIE
        ) {
            Tine.log.info('tineInit::onRegistryLoad - register onPreferenceChange handler');
            Tine.Tinebase.preferences.on('replace', Tine.Tinebase.tineInit.onPreferenceChange);
            Tine.Tinebase.tineInit.onPreferenceChangeRegistered = true;
        }

        Tine.helpUrl = Tine.Tinebase.registry.get('helpUrl') || Tine.helpUrl;

        Ext.override(Ext.ux.file.Upload, {
            maxFileUploadSize: Tine.Tinebase.registry.get('maxFileUploadSize'),
            maxPostSize: Tine.Tinebase.registry.get('maxPostSize')
        });

        Tine.Tinebase.tineInit.initExtDirect();

        Tine.Tinebase.tineInit.initState();

        if (Tine.Tinebase.registry.get('currentAccount')) {
            Tine.Tinebase.tineInit.initAppMgr();
        }

        Tine.Tinebase.tineInit.initUploadMgr();

        Tine.Tinebase.tineInit.initLoginPanel();
    },

    /**
     * remove all registry data
     */
    clearRegistry: function() {
        Tine.log.info('tineInit::clearRegistry');
        store.namespace(Tine.Tinebase.tineInit.lsPrefix).clearAll();
    },

    /**
     * executed when a value in Tinebase registry/preferences changed
     *
     * @param {string} key
     * @param {value} oldValue
     * @param {value} newValue
     */
    onPreferenceChange: function (key, oldValue, newValue) {
        if (Tine.Tinebase.tineInit.isReloading) {
            return;
        }
        
        switch (key) {
            case 'windowtype':
            case 'confirmLogout':
            case 'timezone':
            case 'locale':
                Tine.log.info('tineInit::onPreferenceChange - reload mainscreen');
                Tine.Tinebase.common.reload({
                    clearCache: key == 'locale'
                });

                break;
        }
    },
    
    /**
     * initialise window and windowMgr (only popup atm.)
     */
    initWindowMgr: function () {
        /**
         * initialise window types
         */
        var windowType = 'Browser';
        Ext.ux.PopupWindow.prototype.url = 'index.php';

        if (Tine.Tinebase.registry && Tine.Tinebase.registry.get('preferences')) {
            // update window factory window type (required after login)
            windowType = Tine.Tinebase.registry.get('preferences').get('windowtype');
        }

        Tine.WindowFactory = new Ext.ux.WindowFactory({
            windowType: windowType
        });

        // init ApplicationStarter on Ext window once
        if (windowType == 'Ext') {
            Tine.Tinebase.ApplicationStarter.init();
        }
    },
    /**
     * initialise state provider
     */
    initState: function () {
        if (Tine.Tinebase.tineInit.stateful === true) {
            if (window.isMainWindow || Ext.isIE) {
                // NOTE: IE is as always pain in the ass! cross window issues prohibit serialisation of state objects
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
    initExtDirect: function () {
        var sam = Tine.Tinebase.registry.get('serviceMap');
        
        Ext.Direct.addProvider(Ext.apply(sam, {
            'type'     : 'jsonrpcprovider',
            'namespace': 'Tine',
            'url'      : sam.target
        }));
    },
    
    /**
     * init external libraries
     */
    initLibs: function () {
        if (OpenLayers) {
            // fix OpenLayers script location to find images/themes/...
            OpenLayers._getScriptLocation = function () {
                return 'library/OpenLayers/';
            };
        }
    },
    
    /**
     * initialise application manager
     */
    initAppMgr: function () {
        if (! Ext.isIE9 && ! Ext.isIE && ! window.isMainWindow) {
            // return app from main window for non-IE browsers
            Tine.Tinebase.appMgr = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr;
        } else {
            Tine.Tinebase.appMgr = new Tine.Tinebase.AppManager();
        }
    },
    
    /**
     * initialise upload manager
     */
    initUploadMgr: function () {
        Tine.Tinebase.uploadManager = new Ext.ux.file.UploadManager();
    },
    
    /**
     * config locales
     */
    initLocale: function () {
        //Locale.setlocale(Locale.LC_ALL, '');
        Tine.Tinebase.translation = new Locale.Gettext();
        Tine.Tinebase.translation.textdomain('Tinebase');
        window._ = function (msgid) {
            return Tine.Tinebase.translation.dgettext('Tinebase', msgid);
        };
        Tine.Tinebase.prototypeTranslation();
    }
};

Ext.onReady(function () {
    Tine.Tinebase.tineInit.initWindow();
    Tine.Tinebase.tineInit.initDebugConsole();
    Tine.Tinebase.tineInit.initBootSplash();
    Tine.Tinebase.tineInit.initLocale();
    Tine.Tinebase.tineInit.initAjax();
    Tine.Tinebase.tineInit.initLibs();

    // FIXME: we now always load the registry because otherwise loginFromPost would not work
    Tine.Tinebase.tineInit.initRegistry(window.isMainWindow, function() {
        Tine.Tinebase.tineInit.initWindowMgr();
        Tine.Tinebase.tineInit.renderWindow();
    });
});
