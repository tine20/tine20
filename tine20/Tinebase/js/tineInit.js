/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.onReady(function() {
    Tine.Tinebase.tineInit.initWindow();
    Tine.Tinebase.tineInit.initBootSplash();
    Tine.Tinebase.tineInit.initLocale();
    Tine.Tinebase.tineInit.initAjax();
    Tine.Tinebase.tineInit.initErrorHandler();
    Tine.Tinebase.tineInit.initRegistry();
    Tine.Tinebase.tineInit.initWindowMgr();
    Tine.Tinebase.tineInit.initState();
    
    var waitForInits = function() {
        if (! Tine.Tinebase.tineInit.initList.initRegistry) {
            waitForInits.defer(100);
        } else {
            Tine.Tinebase.tineInit.onLangFilesLoad();
            Tine.Tinebase.tineInit.renderWindow();
        }
    };
    waitForInits();
});

/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

Ext.namespace('Tine');
Tine.Build = '$Build: $';

/**
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
        // disable browsers native context menu globaly
        Ext.getBody().on('contextmenu', Ext.emptyFn, this, {preventDefault: true});
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
        };
        
        this.splash = {
            id: 'tine-viewport-waitcycle',
            border: false,
            layout: 'fit',
            width: 16,
            height: 16,
            html: '<div class="loading-indicator" width="16px" height="16px">&#160;</div>',
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
        /**
         * send custom headers and json key on Ext.Ajax.requests
         */
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.url = options.url ? options.url : Tine.Tinebase.tineInit.requestUrl;
            options.params.jsonKey = Tine.Tinebase.registry && Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('jsonKey') : '';
            options.params.requestType = options.params.requestType || 'JSON';
            
            options.headers = options.headers ? options.headers : {};
            options.headers['X-Tine20-Request-Type'] = options.headers['X-Tine20-Request-Type'] || 'JSON';
        });
        
        /**
         * Fetch HTML in JSON responses, which indicate response errors.
         */
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
            // detect resoponse errors (e.g. html from xdebug)
            //if (response.responseText.charAt(0) == '<') {
            if (! response.responseText.match(/^[{\[]+/)) {
                var htmlText = response.responseText;
                response.responseText = Ext.util.JSON.encode({
                    msg: htmlText,
                    trace: []
                });
                
                connection.fireEvent('requestexception', connection, response, options);
            }
        });
        
        /**
         * Fetch exceptions
         * 
         * Exceptions which come to the client signal a software failure.
         * So we display the message and trace here for the devs.
         * @todo In production mode there should be a 'report bug' wizzard here
         */
        Ext.Ajax.on('requestexception', function(connection, response, options){
            // if communication is lost, we can't create a nice ext window.
            if (response.status === 0) {
                alert(_('Connection lost, please check your network!'));
                return false;
            }
            
            var data = response ? Ext.util.JSON.decode(response.responseText) : null;
            
            // server did not responde anything
            if (! data) {
                alert(_('The server did not respond to your request. Please check your network or contact your administrator.'));
                return false;
            }
            
            switch(data.code) {
                // not autorised
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
                
                // concurrency conflict
                case 409:
                Ext.MessageBox.show({
                    title: _('Concurrent Updates'), 
                    msg: _('Someone else saved this record while you where editing the data. You need to reload and make your changes again.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING
                });
                break;
                
                // generic failure -> notify developers
                default:
                var trace = '';
                for (var i=0,j=data.trace.length; i<j; i++) {
                    trace += (data.trace[i].file ? data.trace[i].file : '[internal function]') +
                             (data.trace[i].line ? '(' + data.trace[i].line + ')' : '') + ': ' +
                             (data.trace[i]['class'] ? '<b>' + data.trace[i]['class'] + data.trace[i].type + '</b>' : '') +
                             '<b>' + data.trace[i]['function'] + '</b>' +
                            '(' + (data.trace[i].args[0] ? data.trace[i].args[0] : '') + ')<br/>';
                }
                data.traceHTML = trace;
                
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
                    Tine.Tinebase.exceptionDlg.show();
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
    getFormattedMessage: function(args) {
        var lines = ["The following error has occured:"];
        if (args[0] instanceof Error) { // Error object thrown in try...catch
            var err = args[0];
            lines[lines.length] = "Message: (" + err.name + ") " + err.message;
            lines[lines.length] = "Error number: " + (err.number & 0xFFFF); //Apply binary arithmetic for IE number, firefox returns message string in element array element 0
            lines[lines.length] = "Description: " + err.description;
        } else if ((args.length == 3) && (typeof(args[2]) == "number")) { // Check the signature for a match with an unhandled exception
            lines[lines.length] = "Message: " + args[0];
            lines[lines.length] = "URL: " + args[1];
            lines[lines.length] = "Line Number: " + args[2];
        } else {
            lines = ["An unknown error has occured."]; // purposely rebuild lines
            lines[lines.length] = "The following information may be useful:";
            for (var x = 0; x < args.length; x++) {
                lines[lines.length] = Ext.encode(args[x]);
            }
        }
        return lines.join("\n");
    },
    
    globalErrorHandler: function() {
        
        // NOTE: Arguments is not a real Array
        var args = [];
        for (var i=0; i<arguments.length; i++) {
            args[i] = arguments[i];
            
            
        }
        
        var errormsg = Tine.Tinebase.tineInit.getFormattedMessage(args);
        
        // check for spechial cases we don't want to handle
        if (errormsg.match(/versioncheck/)) {
            return true;
        }
        
        var data = {
            msg: 'js exception: ' + errormsg,
            traceHTML: errormsg.replace(/\n/g, "<br />").replace(/\t/g, " &nbsp; &nbsp;")
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
                                        Tine[app].registry.add(key, appData[key]);
                                    }
                                }
                            }
                        }
                    }
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
            /*
            Tine.Tinebase.registry = mainWindow.Tine.Tinebase.registry;
            
            if (Tine.Tinebase.registry.get('userApplications')) {
                var userApps = Tine.Tinebase.registry.get('userApplications');
                var app;
                for(var i=0; i<userApps.length; i++) {
                    app = userApps[i];
                    
                    if (app.name !== 'Tinebase') {
                    	if (Tine[app.name]) {
                    	   Tine[app.name].registry = mainWindow.Tine[app.name].registry;	
                    	} 
                    }
                }
            }
            */
            Tine.Tinebase.tineInit.initList.initRegistry = true;
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
        Tine.WindowFactory = new Ext.ux.WindowFactory({
            windowType: 'Browser'
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
        Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
        if (window.isMainWindow) {
            // fill store from registry / initial data
            // Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
        } else {
            // take main windows store
            Ext.state.Manager.getProvider().setStateStore(Ext.ux.PopupWindowGroup.getMainWindow().Ext.state.Manager.getProvider().getStateStore());
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
        Ext.ux.form.DateField.prototype.format = Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium');
    }
};
