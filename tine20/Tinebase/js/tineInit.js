/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.onReady(function() {
   
    Tine.Tinebase.tineInit.initAjax();
    Tine.Tinebase.tineInit.initRegistry();
    
    var waitForInits = function() {
        if (Tine.Tinebase.tineInit.initList.initRegistry) {
            Tine.Tinebase.tineInit.initViewport();
        } else {
            waitForInits.defer(100);
        }
    };
    waitForInits();
});

/**
 * static tine init functions
 */
Tine.Tinebase.tineInit = {
    /**
     * list of initialised items
     */
    initList: {
        initRegistry: false
    },
    
    initViewport: function(){
        // Tine Framework initialisation for each window
        Tine.Tinebase.tineInit.onLangFilesLoad();
        
        /** temporary login **/
        if (!Tine.Tinebase.registry.get('currentAccount')) {
            Tine.Login.showLoginDialog(Tine.Tinebase.registry.get('defaultUsername'), Tine.Tinebase.registry.get('defaultPassword'));
            return;
        }
        
        
        if (window.isMainWindow) {
            // mainscreen request
            Ext.ux.PopupWindowMgr.register({
                name: window.name,
                popup: window
            });
            Tine.Tinebase.MainScreen = new Tine.Tinebase.MainScreenClass();
            Tine.Tinebase.MainScreen.render();
            window.focus();
        } else {
            // @todo move PopupWindowMgr to generic WindowMgr
            // init WindowMgr like registry!
            var c = Ext.ux.PopupWindowMgr.get(window) || {};
            
            if (!c.itemsConstructor && window.exception) {
                switch (exception.code) {
                    
                    // autorisation required
                    case 401:
                        Tine.Login.showLoginDialog(Tine.Tinebase.registry.get('defaultUsername'), Tine.Tinebase.registry.get('defaultPassword'));
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
    
            window.document.title = c.title ? c.title : window.document.title;
    
            var items;
            if (c.itemsConstructor) {
                var parts = c.itemsConstructor.split('.');
                var ref = window;
                for (var i=0; i<parts.length; i++) {
                    ref = ref[parts[i]];
                }
                var items = new ref(c.itemsConstructorConfig);
            } else {
                items = c.items ? c.items : {};
            }
            
            /** temporary Tine.onRady for smooth transition to new window handling **/
            if (typeof(Tine.onReady) == 'function') {
                Tine.onReady();
            } else {
                c.viewport = new Ext.Viewport({
                    title: c.title,
                    layout: c.layout ? c.layout : 'border',
                    items: items
                });
            }
            window.focus();
        }
    },

    initAjax: function() {
        /**
         * send custom headers and json key on Ext.Ajax.requests
         */
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.url = options.url ? options.url : 'index.php';
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
            if (response.responseText.charAt(0) == '<') {
                var htmlText = response.responseText;
                response.responseText = Ext.util.JSON.encode({
                    msg: htmlText,
                    trace: []
                });
                
                connection.fireEvent('requestexception', connection, response, options);
                return false;
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
            }
            
            var data = Ext.util.JSON.decode(response.responseText);
            
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
                
                var win = new Tine.Tinebase.ExceptionDialog({
                    height: windowHeight,
                    exceptionInfo: data
                });
                win.show();
                break;
            }
            
        });
    },
    
    /**
     * init registry
     */
    initRegistry: function() {
        if (window.isMainWindow) {
            Ext.Ajax.request({
                params: {
                    method: 'Tinebase.getAllRegistryData'
                },
                success: function(response, request) {
                    var registryData = Ext.util.JSON.decode(response.responseText);
                    for (var app in registryData) {
                        if (registryData.hasOwnProperty(app)) {
                            var appData = registryData[app];
                            Tine[app].registry = new Ext.util.MixedCollection();
                            
                            for (var key in appData) {
                                if (appData.hasOwnProperty(key)) {
                                    Tine[app].registry.add(key, appData[key]);
                                }
                            }
                        }
                    }
                    Tine.Tinebase.tineInit.initList.initRegistry = true;
                }
            });
        } else {
            var mainWindow = Ext.ux.PopupWindowGroup.getMainWindow();
            Tine.Tinebase.registry = mainWindow.Tine.Tinebase.registry;
            
            if (Tine.Tinebase.registry.get('userApplications')) {
                var userApps = Tine.Tinebase.registry.get('userApplications');
                for(var i=0; i<userApps.length; i++) {
                    app = userApps[i];
                    
                    console.log(app.name);
                    if (app.name !== 'Tinebase') {
                        Tine[app.name].registry = mainWindow.Tine[app.name].registry;
                    }
                }
            }
            Tine.Tinebase.tineInit.initList.initRegistry = true;
        }
    },
    
    /**
     * Last stage of initialisation, to be done after Tine.onReady!
     */
    onLangFilesLoad: function() {
        Ext.ux.form.DateField.prototype.format = Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium');
    }
}


/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

Ext.namespace('Tine');
Tine.Build = '$Build: $';

/**
 * html encode all grid columns per defaut
 */
Ext.grid.ColumnModel.defaultRenderer = Ext.util.Format.htmlEncode;

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
 * initialise state provider
 */
Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
if (window.isMainWindow) {
    // fill store from registry / initial data
    // Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
} else {
    // take main windows store
    Ext.state.Manager.getProvider().setStateStore(Ext.ux.PopupWindowGroup.getMainWindow().Ext.state.Manager.getProvider().getStateStore());
}

/**
 * config locales
 */
//Locale.setlocale(Locale.LC_ALL, '');
Tine.Tinebase.tranlation = new Locale.Gettext();
Tine.Tinebase.tranlation.textdomain('Tinebase');
_ = function(msgid) {
    return Tine.Tinebase.tranlation.dgettext('Tinebase', msgid);
};



