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
	
    var initAjax = function(){
        Ext.Ajax.on('beforerequest', function(connection, options){
            options.url = 'index.php';
            //console.log('beforerequest');
            //console.log(options);
        }, this);
        
        Ext.Ajax.on('requestcomplete', function(connection, response, options){
        
            //console.log(response.responseText.substr(2,response.responseText.length));
            //console.log('requestcomplete');
            //console.log(response.responseText);
        
        }, this);
        
        Ext.Ajax.on('requestexception', function(connection, response, options){
            // if communication is lost, we can't create a nice ext window.
            if (response.status == 0) {
                return alert('Conection lost, please check your network!')
            }
            
            var data = Ext.util.JSON.decode(response.responseText);
            //eval('var data = '+response.responseText);
            
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
                }
        });
    }
	
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
				disabled: true
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
    }
    
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
        };
        
        return panels;
    }
    
    /**
     * the logout button handler function
     */
    var _logoutButtonHandler = function(_event) {
		Ext.MessageBox.confirm('Confirm', 'Are you sure you want to logout?', function(btn, text) {
			if (btn == 'yes') {
				Ext.MessageBox.wait('Loging you out...', 'Please wait!');
				new Ext.data.Connection().request( {
					url : 'index.php',
					method : 'post',
					scope : this,
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
	}
    
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
    }
    
    var _getActiveToolbar = function()
    {
    	var northPanel = Ext.getCmp('north-panel-2');
    	
    	if(northPanel.items) {
    		return northPanel.items.get(0);
    	} else {
    		return false;
    	}
    }
    
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
    }

    
    
    // public functions and variables
    return {
        display:               _displayMainScreen,
        getActiveToolbar:      _getActiveToolbar,
        setActiveToolbar:      _setActiveToolbar,
        setActiveContentPanel: _setActiveContentPanel
    }
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
	}
	
	_dateTimeRenderer = function($_iso8601){
		return Ext.util.Format.date($_iso8601, 'd.m.Y H:i:s');
	}
	
	return {
		dateTimeRenderer: _dateTimeRenderer,
		openWindow:       _openWindow,
	}
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
