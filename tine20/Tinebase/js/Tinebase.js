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

Ext.namespace('Tine', 'Tine.Tinebase');

/**
 * @singleton
 * Instance of Tine.Tinebase.RegistryClass
 * 
 * NOTE: As long as the views include initial data, we can not overwrite the registry
 * in the main window with data from a popup!
 */
//if (window.name == Ext.ux.PopupWindowGroup.MainScreenName || window.name === '') {
    Tine.Tinebase.Registry = new Ext.util.MixedCollection();
//} else {
//    Tine.Tinebase.Registry = Ext.ux.PopupWindowGroup.getMainScreen().Tine.Tinebase.Registry;
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
    var locale = Tine.Tinebase.Registry.get('locale');
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
            options.params.jsonKey = Tine.Tinebase.Registry.get('jsonKey');
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
                        icon: Ext.MessageBox.WARNING
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
 * static common helpers
 */
Tine.Tinebase.Common = function(){
	
	/**
	 * Open browsers native popup
	 * @param {string} _windowName
	 * @param {string} _url
	 * @param {int} _width
	 * @param {int} _height
	 */
	var _openWindow = function(_windowName, _url, _width, _height){
		var w,h,x,y,leftPos,topPos,popup;
		
		if (document.all) {
			w = document.body.clientWidth;
			h = document.body.clientHeight;
			x = window.screenTop;
			y = window.screenLeft;
		}
		else 
			if (window.innerWidth) {
				w = window.innerWidth;
				h = window.innerHeight;
				x = window.screenX;
				y = window.screenY;
			}
		leftPos = ((w - _width) / 2) + y;
		topPos = ((h - _height) / 2) + x;
		
		popup = window.open(_url, _windowName, 'width=' + _width + ',height=' + _height + ',top=' + topPos + ',left=' + leftPos +
		',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no');
		
		return popup;
	};
	
	/**
     * Returns localised date and time string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised date and time
     */
	_dateTimeRenderer = function($_iso8601){
		return Ext.util.Format.date($_iso8601, Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium'));
	};
	
	/**
     * Returns localised date string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised date
     */
	_dateRenderer = function(date){
		return Ext.util.Format.date(date, Locale.getTranslationData('Date', 'medium'));
	};
	
	/**
	 * Returns localised time string
	 * 
	 * @param {mixed} date
	 * @see Ext.util.Format.date
	 * @return {string} localised time
	 */
	_timeRenderer = function(date){
		return Ext.util.Format.date(date, Locale.getTranslationData('Time', 'medium'));
	};

    /**
     * Returns the formated username
     * 
     * @param {object} account object 
     * @return {string} formated user display name
     */
    _usernameRenderer = function(_accountObject, _metadata, _record, _rowIndex, _colIndex, _store){
        return Ext.util.Format.htmlEncode(_accountObject.accountDisplayName);
    };
	
    /**
     * Returns a username or groupname with according icon in front
     */
    _accountRenderer = function(_accountObject, _metadata, _record, _rowIndex, _colIndex, _store){
        var type, iconCls, displayName;
        
        if(_accountObject.accountDisplayName){
            type = 'user';
            displayName = _accountObject.accountDisplayName;
        } else if (_accountObject.name){
            type = 'group';
            displayName = _accountObject.name;
        } else if (_record.data.name) {
            type = _record.data.type;
            displayName = _record.data.name;
        } else if (_record.data.account_name) {
            type = _record.data.account_type;
            displayName = _record.data.account_name;
        }
        iconCls = type == 'user' ? 'renderer renderer_accountUserIcon' : 'renderer renderer_accountGroupIcon';
        return '<div class="' + iconCls  + '">&#160;</div>' + Ext.util.Format.htmlEncode(displayName); 
    };
    
    /** 
     * returns json coded data from given data source
	 *
	 * @param _dataSrc - Ext.data.JsonStore object
	 * @return json coded string
	 **/	
	var _getJSONDsRecs = function(_dataSrc) {
			
		if(Ext.isEmpty(_dataSrc)) {
			return false;
		}
			
		var data = _dataSrc.data;
		var dataLen = data.getCount();
		var jsonData = [];
        var curRecData;
		for(i=0; i < dataLen; i++) {
			curRecData = data.itemAt(i).data;
			jsonData.push(curRecData);
		}	

		return Ext.util.JSON.encode(jsonData);
	};
       
    /** 
     * returns json coded data from given data source
     * switches array keys
	 *
	 * @param _dataSrc - Ext.data.JsonStore object
	 * @param _switchKeys - Array with old=>new key pairs
	 * @return json coded string
	 **/	
	var _getJSONDsRecsSwitchedKeys = function(_dataSrc, _switchKeys) {
			
		if(Ext.isEmpty(_dataSrc) || Ext.isEmpty(_switchKeys)) {
			return false;
		}
			
		var data = _dataSrc.data, dataLen = data.getCount();
		var jsonData = [];
		var keysLen = _switchKeys.length;		
        
        if(keysLen < 1) {
            return false;
        }
        
        var curRecData;
		for(var i=0; i < dataLen; i++) {
                curRecData = [];
                curRecData[0] = {};
                curRecData[0][_switchKeys[0]] = data.itemAt(i).data.key;
                curRecData[0][_switchKeys[1]] = data.itemAt(i).data.value;                

			jsonData.push(curRecData[0]);
		}	

		return Ext.util.JSON.encode(jsonData);
	}    ;   
       
    
	return {
		dateTimeRenderer: _dateTimeRenderer,
		dateRenderer: _dateRenderer,
		usernameRenderer: _usernameRenderer,
        accountRenderer:  _accountRenderer,
		timeRenderer: _timeRenderer,
		openWindow:       _openWindow,
        getJSONdata:    _getJSONDsRecs,
        getJSONdataSKeys:    _getJSONDsRecsSwitchedKeys
	};
}();

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
	
    if (!(Tine && Tine[_application] && Tine[_application].rights)) {
        console.error('Tine.' + _application + '.rights is not available, initialisation Error!');
        return false;
    } else {
    	userRights = Tine[_application].rights;
    }
    
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


