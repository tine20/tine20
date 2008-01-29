Ext.BLANK_IMAGE_URL = "extjs/resources/images/default/s.gif";

Ext.QuickTips.init();

Ext.namespace('Egw.Egwbase');

/**
 * generic storage class helps to manage global data
 */
Egw.Egwbase.Registry = new Ext.util.MixedCollection(true);

/**
 * Initialise eGroupWare 2.0 ExtJs framework
 */
Egw.Egwbase.initFramework = function() {
	
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
            options.params.jsonKey = Egw.Egwbase.Registry.get('jsonKey');
        }, this);
        
		
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
            var responseData = Ext.util.JSON.decode(response.responseText);
			if(responseData.status && responseData.status.code != 200) {
					//console.log(arguments);
					//connection.purgeListeners();
					//connection.fireEvent('requestexception', connection, response, options );
					//return false;
			}
        }, this);
        
        Ext.Ajax.on('requestexception', function(connection, response, options){
			//connection.purgeListeners();

            // if communication is lost, we can't create a nice ext window.
            if (response.status == 0) {
                alert('Conection lost, please check your network!');
            }
            
            var data = Ext.util.JSON.decode(response.responseText);
            Ext.Msg.show({
                title: response.statusText,
                msg: data.msg,
                icon: Ext.MessageBox.WARNING,
                buttons: Ext.MessageBox.OK
            });
            
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
 * eGroupWare 2.0 ExtJS client Mainscreen.
 */
Egw.Egwbase.MainScreen = function() {

    var _displayMainScreen = function() {

		var systemMenu = new Ext.menu.Menu({
			items: [{
				text: 'Change password',
                handler: _changePasswordHandler,
				disabled: false
			}, '-', {
				text: 'Logout',
	            handler: _logoutButtonHandler,
				icon: 'images/oxygen/16x16/actions/system-log-out.png'
			}]
		});

		var egwMenu = new Ext.Toolbar({
			id: 'egwMenu',
			height: 26,
            items:[{
                text: 'eGroupWare',
                menu: systemMenu
            }]

		});

        var egwFooter = new Ext.Toolbar({
            id: 'egwFooter',
            height: 26,
            items:[
                'Current timezone: ' +  Egw.Egwbase.Registry.get('timeZone'), 
                '->', 
                {
                    icon:    'images/oxygen/16x16/actions/system-log-out.png',
                    cls:     'x-btn-icon',
                    tooltip: {text:'Click this button to logout from eGroupWare'},
                    handler: _logoutButtonHandler
                }
            ]

        });

        var applicationToolbar = new Ext.Toolbar({
			id: 'applicationToolbar',
			height: 26
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
                        egwMenu
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
				    egwFooter
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
	            //layout: 'fit',
				layout:'accordion',
				defaults: {
					// applied to each contained panel
					// bodyStyle: 'padding:15px'
				},
				layoutConfig: {
					// layout-specific configs go here
					titleCollapse: true,
					animate: true,
					activeOnTop: false,
					hideCollapseTool: true
				},
				items: _getPanels()
			}]
		});
		
		/*var centerPanel = Ext.getCmp('north-panel-1');
        centerPanel.add(egwMenu);
        centerPanel.show();
        centerPanel.doLayout();*/

/*        egwMenu.add({
            text: 'eGroupWare',
            menu: systemMenu
        }, '->', {
            icon: 'images/oxygen/16x16/actions/system-log-out.png',
            cls: 'x-btn-icon',
            tooltip: {text:'Click this button to logout from eGroupWare'},
            handler: _logoutButtonHandler
        }); */
    };
    
    /**
     * returns array of panels to display in south region
     */
    var _getPanels = function() {
		
    	var panels = [];
        
        for(_application in Egw) {
        	try{
                panels.push(Egw[_application].getPanel());
                
        	} catch(e) {
        		//console.log(_application + ' failed');
        		//console.log(e);
        	}
        }
        
        return panels;
    };
    
    
    var _changePasswordHandler = function(_event) {
        
        var oldPw = new Ext.form.TextField({
            inputType: 'password',
            hideLabel: false,
            id: 'oldPw',
            fieldLabel:'old password', 
            name:'oldPw',
            allowBlank: false,
            anchor: '100%',            
            selectOnFocus: true
        });
        
        var newPw = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPw',
            fieldLabel:'new password', 
            name:'newPw',
            allowBlank: false,
            anchor: '100%',
            selectOnFocus: true      
        });
        
        var newPwSecondTime = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPwSecondTime',
            fieldLabel:'new password again', 
            name:'newPwSecondTime',
            allowBlank: false,
            anchor: '100%',           
            selectOnFocus: true                  
        });   

        var changePasswordToolbar = new Ext.Toolbar({
          	id: 'changePwToolbar',
			split: false,
			height: 26,
			items: [
                '->',
				{
                    text: 'change password',
                    iconCls: 'action_saveAndClose',
                    handler: function() {
                    if (changePasswordForm.getForm().isValid()) {
                        
                        if (changePasswordForm.getForm().getValues().newPw == changePasswordForm.getForm().getValues().newPwSecondTime) {
                            changePasswordForm.getForm().submit({
                                waitTitle: 'Please wait!',
                                waitMsg: 'changing password...',
                                params: {
                                    jsonKey: Egw.Egwbase.jsonKey
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
                    };
                  }                    
                }
			]
		});

        var changePasswordForm = new Ext.FormPanel({
			baseParams: {method :'Egwbase.changePassword'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
            tbar: changePasswordToolbar,
            anchor:'100%',
			region: 'center',
            id: 'changePwPanel',
			deferredRender: false,
	        items: [
                oldPw,
                newPw,
                newPwSecondTime
            ]
        });


        var pwDialog = new Ext.Window({
    		title: 'change password',
            id: 'changePassword_window',
			modal: true,
		    width: 250,
		    height: 300,
		    minWidth: 250,
		    minHeight: 300,
		    layout: 'fit',
		    plain:true,
		    bodyStyle:'padding:5px;',
		    buttonAlign:'center'
        });
            
        pwDialog.add(changePasswordForm);
        pwDialog.show();            
	};    
    
    /**
     * the logout button handler function
     */
    var _logoutButtonHandler = function(_event) {
		Ext.MessageBox.confirm('Confirm', 'Are you sure you want to logout?', function(btn, text) {
			if (btn == 'yes') {
				Ext.MessageBox.wait('Loging you out...', 'Please wait!');
				Ext.Ajax.request( {
					params : {
						method : 'Egwbase.logout'
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
Egw.Egwbase.Common = function(){
	
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
		var leftPos = ((w - _width) / 2) + y;
		var topPos = ((h - _height) / 2) + x;
		
		var popup = window.open(_url, _windowName, 'width=' + _width + ',height=' + _height + ',top=' + topPos + ',left=' + leftPos +
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
     * returns json coded data from given data source
	 *
	 * @param _dataSrc - Ext.data.JsonStore object
	 * @return json coded string
	 **/	
	var _getJSONDsRecs = function(_dataSrc) {
			
		if(Ext.isEmpty(_dataSrc)) {
			return false;
		}
			
		var data = _dataSrc.data, dataLen = data.getCount(), jsonData = new Array();		
		for(i=0; i < dataLen; i++) {
			var curRecData = data.itemAt(i).data;
			jsonData.push(curRecData);
		}	

		return Ext.util.JSON.encode(jsonData);
	}
       
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
			
		var data = _dataSrc.data, dataLen = data.getCount(), jsonData = new Array(), keysLen = _switchKeys.length;		
        
        if(keysLen < 1) {
            return false;
        }
        
		for(var i=0; i < dataLen; i++) {

                var curRecData = new Array();
                curRecData[0] = new Object();
                curRecData[0][_switchKeys[0]] = data.itemAt(i).data.key;
                curRecData[0][_switchKeys[1]] = data.itemAt(i).data.value;                

			jsonData.push(curRecData[0]);
		}	

		return Ext.util.JSON.encode(jsonData);
	}       
       
    
	return {
		dateTimeRenderer: _dateTimeRenderer,
		dateRenderer: _dateRenderer,
		usernameRenderer: _usernameRenderer,
		timeRenderer: _timeRenderer,
		openWindow:       _openWindow,
        getJSONdata:    _getJSONDsRecs,
        getJSONdataSKeys:    _getJSONDsRecsSwitchedKeys
	};
}();

Ext.namespace('Egw.Egwbase.Models');

/**
 * Model of the egw account
 */
Egw.Egwbase.Models.Account = Ext.data.Record.create([
    { name: 'account_id' },
	{ name: 'account_lid' },
	{ name: 'account_pwd' },
	{ name: 'account_lastlogin' },
	{ name: 'account_lastloginfrom' },
	{ name: 'account_lastpwd_change' },
	{ name: 'account_status' },
	{ name: 'account_expires' },
	{ name: 'account_type' },
	{ name: 'account_primary_group' },
	{ name: 'account_challenge' },
	{ name: 'account_response' }
]);

Ext.app.SearchField = Ext.extend(Ext.form.TwinTriggerField, {
    initComponent : function(){
        Ext.app.SearchField.superclass.initComponent.call(this);
        this.on('specialkey', function(f, e){
            if(e.getKey() == e.ENTER){
                this.onTrigger2Click();
                this.fireEvent('change', this, this.getRawValue(), this.startValue);
            }
        }, this);
    },

    validationEvent:false,
    validateOnBlur:false,
    trigger1Class:'x-form-clear-trigger',
    trigger2Class:'x-form-search-trigger',
    hideTrigger1:true,
    width:180,
    hasSearch : false,
    paramName : 'query',

    onTrigger1Click : function(){
        if(this.hasSearch){
            this.el.dom.value = '';
        	this.fireEvent('change', this, this.getRawValue(), this.startValue);
            this.triggers[0].hide();
            this.hasSearch = false;
        }
    },

    onTrigger2Click : function(){
        var v = this.getRawValue();
        if(v.length < 1){
            this.onTrigger1Click();
            return;
        }
        this.fireEvent('change', this, this.getRawValue(), this.startValue);
        this.hasSearch = true;
        this.triggers[0].show();
    }
});


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
