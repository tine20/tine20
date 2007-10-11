Ext.BLANK_IMAGE_URL = "extjs/resources/images/default/s.gif";

Ext.QuickTips.init();

Ext.namespace('Egw.Egwbase');

Egw.Egwbase = function() {
    var _displayMainScreen = function() {

		var systemMenu = new Ext.menu.Menu({
			items: [{
				text: 'Home',
				icon: 'images/oxygen/16x16/actions/favorites.png'
			}, {
				text: 'Preferences'
			}, {
				text: 'Change password'
			}, '-', {
				text: 'Logout',
	            handler: _logoutButtonHandler,
				icon: 'images/oxygen/16x16/actions/system-log-out.png'
			}]
		});

/*		var appMenu = new Ext.menu.Menu({
			items: [{
				text: 'Manager folder'
			}, {
				text: 'Empty trash'
			}]
		}); */

		var egwMenu = new Ext.Toolbar({
			id: 'egwMenu',
			height: 26
		});
		
        var tb2 = new Ext.Toolbar({
			id: 'applicationToolbar',
			height: 26
		});

		var viewport = new Ext.Viewport({
			layout: 'border',
			items: [{
				region: 'north',
				id: 'north-panel',
				split: false,
				height: 52,
				items: [
					egwMenu,
					tb2
				]
			}, {
				region: 'south',
				id: 'south',
				split: false,
				height: 20,
				initialSize: 20
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
				layout: 'fit'
			}, {
				region: 'west',
	            id: 'west',
	            split: true,
	            width: 200,
	            minSize: 100,
	            maxSize: 300,
	            collapsible:true,
	            containerScroll: true,
	            collapseMode: 'mini',
	            layout: 'fit',
				layout:'accordion',
				defaults: {
					// applied to each contained panel
					// bodyStyle: 'padding:15px'
				},
				layoutConfig: {
					// layout-specific configs go here
					titleCollapse: true,
					animate: false,
					activeOnTop: false,
					hideCollapseTool: true
				},
			    items: [{
			        title: 'Home',
			        id: 'home-panel',
					border: false
			    },
			    Egw.Addressbook.getPanel(),
			    {
			        title: 'Asterisk',
			        id: 'asterisk-panel',
					border: false
			    }]
			}]
		});

        egwMenu.add({
            text: 'eGroupWare',
            menu: systemMenu
        }, '->', {
            icon: 'images/oxygen/16x16/actions/system-log-out.png',
            cls: 'x-btn-icon',
            tooltip: {text:'Click this button to logout from eGroupWare'},
            handler: _logoutButtonHandler
        });
    }
    
    /**
     * the logout button handler function
     */
    var _logoutButtonHandler = function(_event) {
                Ext.MessageBox.confirm('Confirm', 'Are you sure you want to logout?', function(btn, text){
                        //alert(btn);
                        if (btn == 'yes'){
                                Ext.MessageBox.wait('Loging you out...', 'Please wait!');
                                new Ext.data.Connection().request({
                                        url: 'index.php',
                                        method: 'post',
                                        scope: this,
                                        params: {method:'Egwbase.logout', logout:true},
                                        callback: function(options, bSuccess, response) {
                                                window.location.reload();
                                        }
                                });
                        }
                });

    }
    
    // public functions and variables
    return {
        display: _displayMainScreen
    }
}();

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























/*

Ext.onReady(function(){
return;
   	var ctxTreeMenu = new Ext.menu.Menu({
		id:'addCtx', 
		items: [{
			id:'add contact',
			text:'add new contact',
			icon:'images/oxygen/16x16/actions/add-user.png',
			handler: function () {
			
				var curSelNode = tree.getSelectionModel().getSelectedNode();
				var RootNode   = tree.getRootNode();
			//	alert(curSelNode+', root: '+RootNode);
				_openDialog();
			}	
		},'',{
            id:'add list',
            text:'add new list',
            icon:'images/oxygen/16x16/actions/add-users.png',
            handler: function () {
				_openDialog('','list');
			}
        }]
	});	
	//============================================
	//============== end Context Menu =============
	//============================================
	
	
	

	//============================================
	//=============== tree definition ===============
	//============================================

	var Tree = Ext.tree;

	treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
	//treeLoader.baseParams.func = 'getSubTree';
	var tree = new Tree.TreePanel('nav', {
		animate:true,
		loader: treeLoader,
		enableDD:true,
		//lines: false,
		ddGroup: 'TreeDD',
		enableDrop: true,			
		containerScroll: true,
		rootVisible:false,
		contextmenu: ctxTreeMenu
	});

	
	// handle right mouse click
	tree.on('contextmenu', function(_node, _event) {
		 		_event.stopEvent();
		 		ctxTreeMenu.showAt(_event.getXY());
		 	});	
	

	

	//----------------------------------------------------------------
	//---------------------------- toolbar --------------------------
	//----------------------------------------------------------------
	
	//------------------------ toolbar functions ------------------------
	
	function toggleDetails(btn, pressed) {
	        cm.getColumnById('lastname').renderer = pressed ? renderLastName : renderLastNamePlain;
        	cm.getColumnById('city').renderer = pressed ? renderCity : renderCityPlain;
        	grid.getView().refresh();
        }
	
	//----------------------------------------------------------------
	//------------------------ end toolbar ------------------------
	//----------------------------------------------------------------
												
});
*/

//returns application name (names of first level nodes)
function getAppByNode(node) {

	//root
	if(node.getDepth() == 0) {
		return false;
	}

	//other depths
	curNode = node;
	while(curNode.getDepth() > 1) {
		curNode = curNode.parentNode;
	}

	return curNode.attributes.application;
}
