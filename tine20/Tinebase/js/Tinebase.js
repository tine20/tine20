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

/**
 * Main entry point of each Tine 2.0 window
 * 
 */
Ext.onReady(function(){
    // Tine Framework initialisation for each window
    Tine.Tinebase.initFramework();
    /** temporary login **/
    if (!Tine.Tinebase.registry.get('currentAccount')) {
        Tine.Login.showLoginDialog(Tine.Tinebase.registry.get('defaultUsername'), Tine.Tinebase.registry.get('defaultPassword'));
        return;
    }
    
    
    if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
        // mainscreen request
        window.name = Ext.ux.PopupWindowGroup.MainScreenName;
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
});




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
if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
    // fill store from registry / initial data
    // Ext.state.Manager.setProvider(new Ext.ux.state.JsonProvider());
} else {
    // take main windows store
    Ext.state.Manager.getProvider().setStateStore(Ext.ux.PopupWindowGroup.getMainScreen().Ext.state.Manager.getProvider().getStateStore());
}





/**
 * @singleton
 * Instance of Tine.Tinebase.registryClass
 * 
 * NOTE: As long as the views include initial data, we can not overwrite the registry
 * in the main window with data from a popup!
 */
//if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
    Tine.Tinebase.registry = new Ext.util.MixedCollection();
//} else {
//    Tine.Tinebase.registry = Ext.ux.PopupWindowGroup.getMainScreen().Tine.Tinebase.registry;
//}

/**
 * config locales
 */
//Locale.setlocale(Locale.LC_ALL, '');
Tine.Tinebase.tranlation = new Locale.Gettext();
Tine.Tinebase.tranlation.textdomain('Tinebase');
_ = function(msgid) {
    return Tine.Tinebase.tranlation.dgettext('Tinebase', msgid);
};

/**
 * Initialise Tine 2.0 ExtJs framework
 */
Tine.Tinebase.initFramework = function() {
	
    /*
    var locale = Tine.Tinebase.registry.get('locale');
    var headEl = Ext.get(document.getElementsByTagName("head")[0]);
    
    var file = 'Tinebase/js/Locale/build/' + locale.locale + '-all.js';
    var script = Ext.DomHelper.insertFirst(headEl, {tag: 'script', src: file, type: 'text/javascript'}, true)
    */
    
	/**
     * Ajax reuest proxy
     * 
     * Any ajax request (direct Ext.Ajax, Grid and Tree) is proxied here to
     * set some defaults and check the response status. 
     * 
     * We don't use the HTTP status codes directly as no seperation between real 
     * HTTP problems and application problems would be possible with this schema. 
     * However on PHP side we allways maintain a status object within the response
     * where the same HTTP codes are used.
     */
    var initAjax = function(){
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.url = options.url ? options.url : 'index.php';
            options.params.jsonKey = Tine.Tinebase.registry.get('jsonKey');
            options.params.requestType = options.params.requestType || 'JSON';
            
            options.headers = options.headers ? options.headers : {};
            options.headers['X-Tine20-Request-Type'] = options.headers['X-Tine20-Request-Type'] || 'JSON';
        }, this);
        
		
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
            var responseData = Ext.util.JSON.decode(response.responseText);
			if(responseData.status && responseData.status.code != 200) {
					//console.log(arguments);
					//connection.purgeListeners();
					//connection.fireEvent('requestexception', connection, response, options );
					//return false;
			}
        }, this);
        
        /**
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
            
        }, this);
    };

 
    var initFormats = function() {
        Ext.util.Format = Ext.apply(Ext.util.Format, {
                euMoney: function(v){
                    v = (Math.round((v-0)*100))/100;
                    v = (v == Math.floor(v)) ? v + ".00" : ((v*10 == Math.floor(v*10)) ? v + "0" : v);
                    v = String(v);
                    var ps = v.split('.');
                    var whole = ps[0];
                    var sub = ps[1] ? '.'+ ps[1] : '.00';
                    var r = /(\d+)(\d{3})/;
                    while (r.test(whole)) {
                        whole = whole.replace(r, '$1' + '.' + '$2');
                    }
                    v = whole + sub;
                    if(v.charAt(0) == '-'){
                        return v.substr(1) + ' -€';
                    }  
                    return v + " €";
                },
                percentage: function(v){
                    if(v === null) {
                        return 'none';
                    }
                    if(!isNaN(v)) {
                        return v + " %";                        
                    } 
               },
               pad: function(v,l,s){
                    if (!s) {
                        s = '&nbsp;';
                    }
                    var plen = l-v.length;
                    for (var i=0;i<plen;i++) {
                        v += s;
                    }
                    return v;
               }
        });
        
        Ext.ux.form.DateField.prototype.format = Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium');
    };
	
    initAjax();
    initFormats();
};

/**
 * check if user has right to view/manage this application/resource
 * 
 * @param   string      right (view, admin, manage)
 * @param   string      application
 * @param   string      resource (for example roles, accounts, ...)
 * @returns boolean 
 */
Tine.Tinebase.hasRight = function(_right, _application, _resource)
{
	var userRights = [];
	
    if (!(Tine && Tine[_application] && Tine[_application].registry && Tine[_application].registry.get('rights'))) {
        console.error('Tine.' + _application + '.rights is not available, initialisation Error!');
        return false;
    }
	userRights = Tine[_application].registry.get('rights');
    
    //console.log(userRights);
    var result = false;
    
    for (var i=0; i < userRights.length; i++) {
        if (userRights[i] == 'admin') {
            result = true;
            break;
        }
        
        if (_right == 'view' && (userRights[i] == 'view_' + _resource || userRights[i] == 'manage_' + _resource) ) {
            result = true;
            break;
        }
        
        if (_right == 'manage' && userRights[i] == 'manage_' + _resource) {
            result = true;
            break;
        }
        
        if (_right == userRights[i]) {
        	result = true;
        	break;
        }
    }

    return result;
};


