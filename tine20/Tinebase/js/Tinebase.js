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
 
Ext.BLANK_IMAGE_URL = "ExtJS/resources/images/default/s.gif";

Ext.QuickTips.init();

/**
 * create console pseudo object when firebug is disabled/not installed
 */
if (! ("console" in window) || !("firebug" in console)) {
    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group"
                 , "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
    window.console = {};
    for (var i = 0; i <names.length; ++i) window.console[names[i]] = function() {};
}

/**
 * config locales
 */
Locale.setlocale(Locale.LC_ALL, '');
Locale.Gettext.textdomain('Tinebase');
// shorthands
_ = Locale.Gettext._;
n_ = Locale.Gettext.n_;

Ext.namespace('Tine.Tinebase');

/**
 * generic storage class helps to manage global data
 */
Tine.Tinebase.Registry = new Ext.util.MixedCollection(true);

/**
 * Initialise Tine 2.0 ExtJs framework
 */
Tine.Tinebase.initFramework = function() {
	
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
            options.url = 'index.php';
            options.params.jsonKey = Tine.Tinebase.Registry.get('jsonKey');
        }, this);
        
		
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
            // detect resoponse errors (e.g. html from xdebug)
            if (response.responseText.charAt(0) == '<') {
                var windowHeight = 600;
                if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
                    windowHeight = Ext.getBody().getHeight(true) * 0.7;
                }
                var win = new Ext.Window({
                    width: 600,
                    height: windowHeight,
                    autoScroll: true,
                    title: 'There where Errors',
                    html: response.responseText,
                    buttons: [ new Ext.Action({
                        text: 'ok',
                        handler: function(){ win.close(); }
                    })],
                     buttonAlign: 'center'
                });
                
                win.show();
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
			//connection.purgeListeners();

            // if communication is lost, we can't create a nice ext window.
            if (response.status === 0) {
                alert('Connection lost, please check your network!');
            }
            
            var data = Ext.util.JSON.decode(response.responseText);
            var trace = '';
            for (var i=0,j=data.trace.length; i<j; i++) {
                trace += (data.trace[i].file ? data.trace[i].file : '[internal function]') +
                         (data.trace[i].line ? '(' + data.trace[i].line + ')' : '') + ': ' +
                         (data.trace[i]['class'] ? '<b>' + data.trace[i]['class'] + data.trace[i]['type'] + '</b>' : '') +
                         '<b>' + data.trace[i]['function'] + '</b>' +
                        '(' + (data.trace[i]['args'][0] ? data.trace[i]['args'][0] : '') + ')<br/>';
            }

            var windowHeight = 600;
            if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
                windowHeight = Ext.getBody().getHeight(true) * 0.7;
            }
            var win = new Ext.Window({
                width: 800,
                height: windowHeight,
                autoScroll: true,
                title: data.msg,
                html: trace,
                buttons: [ new Ext.Action({
                    text: 'ok',
                    handler: function(){ win.close(); }
                })],
                 buttonAlign: 'center'
            });
            
            win.show();
            
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
               }
        });
    

    };
	
    initAjax();
    initFormats();
};

/**
 * Tine 2.0 ExtJS client Mainscreen.
 */
Tine.Tinebase.MainScreen = function() {

    var _displayMainScreen = function() {

		var tineMenu = new Ext.Toolbar({
			id: 'tineMenu',
			height: 26,
            items:[{
                text: 'Tine 2.0',
                menu: {
                	id: 'Tinebase_System_Menu',  	
		            items: [{
		                text: 'Change password',
		                handler: _changePasswordHandler
		                //disabled: true
		            }, '-', {
		                text: 'Logout',
		                handler: _logoutButtonHandler,
		                iconCls: 'action_logOut'
		            }]                
		        }
            }, {
            	text: 'Admin',
            	id: 'Tinebase_System_AdminButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_AdminMenu'
                }     
            }, {
                text: 'Preferences',
                id: 'Tinebase_System_PreferencesButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_PreferencesMenu'
                }
            }, '->', {
            	text: _('Logout'),
                iconCls: 'action_logOut',
                //cls:     'x-btn-icon',
                tooltip: {text: _('Logout from Tine 2.0')},
                handler: _logoutButtonHandler
            }]

		});

        var tineFooter = new Ext.Toolbar({
            id: 'tineFooter',
            height: 26,
            items:[
                'Account name: ' + Tine.Tinebase.Registry.get('currentAccount').accountDisplayName + ' ',
                'Timezone: ' +  Tine.Tinebase.Registry.get('timeZone')
            ]

        });

        var applicationToolbar = new Ext.Toolbar({
			id: 'applicationToolbar',
			height: 26
		});
        // defualt app
        applicationToolbar.on('render', function(){
            var appPanel = Ext.getCmp('Addressbook_Tree');
            if (appPanel) {
                appPanel.expand();
            }
        });

        var applicationArcordion = new Ext.Panel({
            layout:'accordion',
            border: false,
            layoutConfig: {
                // layout-specific configs go here
                titleCollapse: true,
                animate: true,
                activeOnTop: true,
                hideCollapseTool: true
            },
            items: _getPanels()
        });
        
		var viewport = new Ext.Viewport({
			layout: 'border',
			items: [{
				region: 'north',
				id:     'north-panel',
				split:  false,
				height: 52,
                border: false,
				layout:'border',
				items: [{
                    //title:  'North Panel 1',
                    region: 'north',
                    height: 26,
                    border: false,
                    id:     'north-panel-1',
                    items: [
                        tineMenu
                    ]
                },{
                    region: 'center',
                    height: 26,
                    border: false,
                    id:     'north-panel-2',
                    items: [
                        applicationToolbar
                    ]
                }]
			}, {
				region: 'south',
				id: 'south',
                border: false,
				split: false,
				height: 26,
				initialSize: 26,
				items:[
				    tineFooter
				]
/*			}, {
 				region: 'east',
				id: 'east',
				title: 'east',
				split: true,
				width: 100,
				minSize: 100,
				maxSize: 500,
				autoScroll:true,
				collapsible:true,
				titlebar: true,
				animate: true, */
			}, {
				region: 'center',
				id: 'center-panel',
				animate: true,
				useShim:true,
                border: false,
				layout: 'fit'
			}, {
				region: 'west',
	            id: 'west',
	            split: true,
	            width: 200,
	            minSize: 100,
	            maxSize: 300,
                border: false,
	            collapsible:true,
	            containerScroll: true,
	            collapseMode: 'mini',
	            layout: 'fit',
				//layout:'accordion',
				defaults: {
					// applied to each contained panel
					// bodyStyle: 'padding:15px'
				},
                /*
				layoutConfig: {
					// layout-specific configs go here
					titleCollapse: true,
					animate: true,
					activeOnTop: true,
					hideCollapseTool: true
				},
                */
                items: applicationArcordion
				//items: _getPanels()
			}]
		});
    };
    
    /**
     * returns array of panels to display in south region
     */
    var _getPanels = function() {
		
    	var panels = [];
        
        for(var _application in Tine) {
        	try{
                for (var i=0, j=Tine[_application]['rights'].length; i<j; i++) {
                    if (Tine[_application]['rights'][i] == 'run') {
                        panels.push(Tine[_application].getPanel());
                        break;
                    }
                }
        	} catch(e) {
        		//console.log(_application + ' failed');
        		//console.log(e);
        	}
        }
        
        return panels;
    };
    
    
    var _changePasswordHandler = function(_event) {
        
        var oldPassword = new Ext.form.TextField({
            inputType: 'password',
            hideLabel: false,
            id: 'oldPassword',
            fieldLabel:'old password', 
            name:'oldPassword',
            allowBlank: false,
            anchor: '100%',            
            selectOnFocus: true
        });
        
        var newPassword = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPassword',
            fieldLabel:'new password', 
            name:'newPassword',
            allowBlank: false,
            anchor: '100%',
            selectOnFocus: true      
        });
        
        var newPasswordSecondTime = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPasswordSecondTime',
            fieldLabel:'new password again', 
            name:'newPasswordSecondTime',
            allowBlank: false,
            anchor: '100%',           
            selectOnFocus: true                  
        });   

        // @todo remove that? is it still in use??
        /*
        var changePasswordToolbar = new Ext.Toolbar({
          	id: 'changePasswordToolbar',
			split: false,
			height: 26,
			items: [
                '->',
				{
                    text: 'Change password',
                    iconCls: 'action_saveAndClose',
                    handler: function() {
                    if (changePasswordForm.getForm().isValid()) {
                        
                    	var oldPassword = changePasswordForm.getForm().getValues().oldPw;
                    	var newPassword = changePasswordForm.getForm().getValues().newPw;
                    	
                        if (changePasswordForm.getForm().getValues().newPw == changePasswordForm.getForm().getValues().newPwSecondTime) {
                        	console.log ( newPassword );
                            //changePasswordForm.getForm().submit({
                        	Ext.Ajax.request({
                        		url: 'index.php',
                                //waitTitle: 'Please wait!',
                                //waitMsg: 'changing password...',
                                params: {
                                    //jsonKey: Tine.Tinebase.jsonKey
                                    method: 'Tinebase.changePassword',
                                    oldPassword: oldPassword,
                                    newPassword: newPassword
                                },
                                success: function(form, action, o){
                                    Ext.getCmp('changePassword_window').hide(); 
                                    Ext.MessageBox.show({
                                        title: 'Success',
                                        msg: 'Your password has been changed.',
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.SUCCESS
                                        //fn: function() {} 
                                    });
                                },
                                failure: function(form, action){
                                    Ext.MessageBox.show({
                                        title: 'Failure',
                                        msg: 'Your old password is incorrect.',
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.ERROR  
                                        //fn: function() {} 
                                    });
                                }
                            });
                        } else {
                            Ext.MessageBox.show({
                                title: 'Failure',
                                msg: 'The new passwords mismatch, please correct them.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR
                                //fn: function() {}
                            });    
                        }
                    }
                  }                    
                }
			]
		});
		*/

        var changePasswordForm = new Ext.FormPanel({
			baseParams: {method :'Tinebase.changePassword'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
      //      tbar: changePasswordToolbar,
            anchor:'100%',
			region: 'center',
            id: 'changePasswordPanel',
			deferredRender: false,
	        items: [
                oldPassword,
                newPassword,
                newPasswordSecondTime
            ]
        });

        _savePassword = function() {
            if (changePasswordForm.getForm().isValid()) {

                var oldPassword = changePasswordForm.getForm().getValues().oldPassword;
                var newPassword = changePasswordForm.getForm().getValues().newPassword;
                var newPasswordRepeat = changePasswordForm.getForm().getValues().newPasswordSecondTime;
            	
                if (newPassword == newPasswordRepeat) {
                	Ext.Ajax.request({
                        url: 'index.php',
                        waitTitle: 'Please wait!',
                        waitMsg: 'changing password...',
                        params: {
                            method: 'Tinebase.changePassword',
                            oldPassword: oldPassword,
                            newPassword: newPassword
                        },
                        success: function(form, action, o){
                            Ext.getCmp('changePassword_window').hide(); 
                            Ext.MessageBox.show({
                                title: 'Success',
                                msg: 'Your password has been changed.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.SUCCESS  /*,
                                fn: function() {} */
                            });
                        },
                        failure: function(form, action){
                            Ext.MessageBox.show({
                                title: 'Failure',
                                msg: 'Your old password is incorrect.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR  /*,
                                fn: function() {} */
                            });
                        }
                    });
                } else {
                    Ext.MessageBox.show({
                        title: 'Failure',
                        msg: 'The new passwords mismatch, please correct them.',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR  /*,
                        fn: function() {} */
                    });    
                }
            }
          };

        var passwordDialog = new Ext.Window({
    		title: 'Change password for ' + Tine.Tinebase.Registry.get('currentAccount').accountDisplayName,
            id: 'changePassword_window',
			modal: true,
		    width: 350,
		    height: 230,
		    minWidth: 350,
		    minHeight: 230,
		    layout: 'fit',
		    plain: true,
            buttons: [
                {
                    text: 'Ok',
             //       iconCls: 'action_saveAndClose',
                    handler: _savePassword                    
                } ,
                {
                    text: 'Cancel',
//                    iconCls: 'action_saveAndClose',
                    handler: function() {
                        Ext.getCmp('changePassword_window').hide();
                    }
                }
            ],
		    bodyStyle: 'padding:5px;',
		    buttonAlign: 'center'
        });
            
        passwordDialog.add(changePasswordForm);
        passwordDialog.show();            
	};    
    
    /**
     * the logout button handler function
     */
    var _logoutButtonHandler = function(_event) {
		Ext.MessageBox.confirm('Confirm', 'Are you sure you want to logout?', function(btn, text) {
			if (btn == 'yes') {
				Ext.MessageBox.wait('Logging you out...', 'Please wait!');
				Ext.Ajax.request( {
					params : {
						method : 'Tinebase.logout'
					},
					callback : function(options, bSuccess, response) {
						// remove the event handler
						// the reload() trigers the unload event
						window.location.reload();
					}
				});
			}
        });
	};
    
    var _setActiveContentPanel = function(_panel)
    {
        // get container to which component will be added
        var centerPanel = Ext.getCmp('center-panel');
        if(centerPanel.items) {
            for (var i=0; i<centerPanel.items.length; i++){
                centerPanel.remove(centerPanel.items.get(i));
            }  
        }

        centerPanel.add(_panel);
        centerPanel.doLayout();
    };
    
    var _getActiveToolbar = function()
    {
    	var northPanel = Ext.getCmp('north-panel-2');
    	
    	if(northPanel.items) {
    		return northPanel.items.get(0);
    	} else {
    		return false;
    	}
    };
    
    var _setActiveToolbar = function(_toolbar)
    {
        var northPanel = Ext.getCmp('north-panel-2');
        if(northPanel.items) {
            for (var i=0; i<northPanel.items.length; i++){
                northPanel.remove(northPanel.items.get(i));
            }  
        }
        //var toolbarPanel = Ext.getCmp('applicationToolbar');
        
        northPanel.add(_toolbar);
        northPanel.doLayout();
    };    
    
    // public functions and variables
    return {
        display:               _displayMainScreen,
        getActiveToolbar:      _getActiveToolbar,
        setActiveToolbar:      _setActiveToolbar,
        setActiveContentPanel: _setActiveContentPanel
    };
}();

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
		return Ext.util.Format.date($_iso8601, 'd.m.Y H:i:s');
	};
	
	/**
     * Returns localised date string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {string} localised date
     */
	_dateRenderer = function(date){
		return Ext.util.Format.date(date, 'd.m.Y');
	};
	
	/**
	 * Returns localised time string
	 * 
	 * @param {mixed} date
	 * @see Ext.util.Format.date
	 * @return {string} localised time
	 */
	_timeRenderer = function(date){
		return Ext.util.Format.date(date, 'H:i:s');
	};

    /**
     * Returns the formated username
     * 
     * @param {object} account object 
     * @return {string} formated user display name
     */
    _usernameRenderer = function(_accountObject, _metadata, _record, _rowIndex, _colIndex, _store){
        return _accountObject.accountDisplayName;
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
            type = 'group'
            displayName = _accountObject.name;
        } else if (_record.data.name) {
            type = _record.data.type
            displayName = _record.data.name;
        } else if (_record.data.account_name) {
            type = _record.data.account_type;
            displayName = _record.data.account_name;
        }
        iconCls = type == 'user' ? 'renderer renderer_accountUserIcon' : 'renderer renderer_accountGroupIcon';
        return '<div class="' + iconCls  + '">&#160;</div>' + displayName; 
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
		for(i=0; i < dataLen; i++) {
			var curRecData = data.itemAt(i).data;
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
        
		for(var i=0; i < dataLen; i++) {

                var curRecData = [];
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



Ext.grid.RowExpander = function(config){
    Ext.apply(this, config);

    this.addEvents({
        beforeexpand : true,
        expand: true,
        beforecollapse: true,
        collapse: true
    });

    Ext.grid.RowExpander.superclass.constructor.call(this);

    if(this.tpl){
        if(typeof this.tpl == 'string'){
            this.tpl = new Ext.Template(this.tpl);
        }
        this.tpl.compile();
    }

    this.state = {};
    this.bodyContent = {};
};

    Ext.extend(Ext.grid.RowExpander, Ext.util.Observable, {
        header: "",
        width: 20,
        sortable: false,
        fixed:true,
        dataIndex: '',
        id: 'expander',
        lazyRender : true,
        enableCaching: false,
    
        getRowClass : function(record, rowIndex, p, ds){
            p.cols = p.cols-1;
            var content = this.bodyContent[record.id];
            if(!content && !this.lazyRender){
                content = this.getBodyContent(record, rowIndex);
            }
            if(content){
                p.body = content;
            }
            return this.state[record.id] ? 'x-grid3-row-expanded' : 'x-grid3-row-collapsed';
        },
    
        init : function(grid){
            this.grid = grid;
    
            var view = grid.getView();
            view.getRowClass = this.getRowClass.createDelegate(this);
    
            view.enableRowBody = true;
    
            grid.on('render', function(){
                view.mainBody.on('mousedown', this.onMouseDown, this);
            }, this);
        },
    
        getBodyContent : function(record, index){
            if(!this.enableCaching){
                return this.tpl.apply(record.data);
            }
            var content = this.bodyContent[record.id];
            if(!content){
                content = this.tpl.apply(record.data);
                this.bodyContent[record.id] = content;
            }
            return content;
        },
    
        onMouseDown : function(e, t){
            if(t.className == 'x-grid3-row-expander'){
                e.stopEvent();
                var row = e.getTarget('.x-grid3-row');
                this.toggleRow(row);
            }
        },
    
        renderer : function(v, p, record){
            p.cellAttr = 'rowspan="2"';
            return '<div class="x-grid3-row-expander">&#160;</div>';
        },
    
        beforeExpand : function(record, body, rowIndex){
            if(this.fireEvent('beforexpand', this, record, body, rowIndex) !== false){
                if(this.tpl && this.lazyRender){
                    body.innerHTML = this.getBodyContent(record, rowIndex);
                }
                return true;
            }else{
                return false;
            }
        },
    
        toggleRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            this[Ext.fly(row).hasClass('x-grid3-row-collapsed') ? 'expandRow' : 'collapseRow'](row);
        },
    
        expandRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            var record = this.grid.store.getAt(row.rowIndex);
            var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
            if(this.beforeExpand(record, body, row.rowIndex)){
                this.state[record.id] = true;
                Ext.fly(row).replaceClass('x-grid3-row-collapsed', 'x-grid3-row-expanded');
                this.fireEvent('expand', this, record, body, row.rowIndex);
            }
        },
    
        collapseRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            var record = this.grid.store.getAt(row.rowIndex);
            var body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
            if(this.fireEvent('beforcollapse', this, record, body, row.rowIndex) !== false){
                this.state[record.id] = false;
                Ext.fly(row).replaceClass('x-grid3-row-expanded', 'x-grid3-row-collapsed');
                this.fireEvent('collapse', this, record, body, row.rowIndex);
            }
        }
});

/**
 * check if user has right to view/manage this application/resource
 * 
 * @param   string      right (view, admin, manage)
 * @param   string      resource (for example roles, accounts, ...)
 * @returns boolean 
 */
Tine.Tinebase.hasRight = function(_right, _resource)
{
    //console.log ( Tine.Admin.rights );
    var result = false;
    
    for ( var i=0; i < Tine.Admin.rights.length; i++ ) {
        if ( Tine.Admin.rights[i] == 'admin' ) {
            result = true;
            break;
        }
        
        if ( _right == 'view' && (Tine.Admin.rights[i] == 'view_' + _resource || Tine.Admin.rights[i] == 'manage_' + _resource ) ) {
            result = true;
            break;
        }
        
        if ( _right == 'manage' && Tine.Admin.rights[i] == 'manage_' + _resource ) {
            result = true;
            break;
        }
    }

    /*
    if ( result == true ) {    
        console.log ("has right: ");
    } else {
    	console.log ("has not right: ");
    }
    console.log (_right);
    */
    
    return result;
};


