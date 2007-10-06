Ext.BLANK_IMAGE_URL = "extjs/resources/images/default/s.gif";

Ext.QuickTips.init();

Ext.namespace('Egw.Egwbase');

Egw.Egwbase = function() {
    var _displayMainScreen = function() {
    
		var systemMenu = new Ext.menu.Menu({
			items: [{
				text: 'Preferences'
			}, {
				text: 'Change password'
			}, '-', {
				text: 'Logout',
				icon: 'images/oxygen/16x16/actions/system-log-out.png',
			}]
		});

		var allAppsMenu = new Ext.menu.Menu({
			items: [{
				text: 'Home',
				icon: 'images/oxygen/16x16/actions/favorites.png'
			}, {
				text: 'Addressbook',
				icon: 'images/oxygen/16x16/apps/kaddressbook.png'
			}]
		});

		var appMenu = new Ext.menu.Menu({
			items: [{
				text: 'Manager folder'
			}, {
				text: 'Empty trash'
			}]
		});

		var egwMenu = new Ext.Toolbar({
			id: 'egwMenu',
			height: 26
		});
		
        var tb2 = new Ext.Toolbar({
			id: 'applicationToolbar',
			height: 26,
		});

        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method    = _node.attributes.application + '.getSubTree';
            _loader.baseParams._node     = _node.id;
            _loader.baseParams._datatype = _node.attributes.datatype;
            _loader.baseParams._owner    = _node.attributes.owner;
            _loader.baseParams._location = 'mainTree';
        }, this);

    
        var tree = new Ext.tree.TreePanel({
            region: 'west',
            id: 'west',
            split: true,
            width: 200,
            minSize: 100,
            maxSize: 500,
            autoScroll:true,
            collapsible:true,
            titlebar: true,
            animate: true,
            animate:true,
            enableDD:true,
            containerScroll: true,
            collapseMode: 'mini',
            loader: treeLoader,
            rootVisible:false
        });
        
        
        // handle right mouse click
        tree.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            ctxTreeMenu.showAt(_event.getXY());
        });
        
        tree.on('click', function(node) {
            //apply panels depending on application type
            var applicationName = getAppByNode(node);
            //console.log('checking for application ' + applicationName);
            //console.log(typeof(EGWNameSpace[applicationName]));
            if(typeof(Egw[applicationName]) == 'object') {
                //console.log('application ' + applicationName + ' exists');
                //layout.beginUpdate();
                Egw[applicationName].show(node);
                //layout.endUpdate();
            }
        });
        
        // set the root node
        var root = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        tree.setRootNode(root);

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
			},
				tree
			]
		});

        //for(var i = 0; i < initialTree.length; i++) {
            //console.log(initialTree[i]);
            root.appendChild(new Ext.tree.AsyncTreeNode(initialTree));
            //root.appendChild(new Ext.tree.AsyncTreeNode(initialTree[i]));
        //}

        root.expand('root/addressbook');

        egwMenu.add({
            text: 'eGroupWare',
            menu: systemMenu
        }, {
            text: 'Applications',
            menu: allAppsMenu
        }, {
            text: 'Addressbook',
            iconCls: 'bmenu',
            icon: 'images/oxygen/16x16/apps/kaddressbook.png',
            menu: appMenu
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

























Ext.onReady(function(){
return;
	Ext.BLANK_IMAGE_URL = "extjs/resources/images/default/s.gif";
	Ext.QuickTips.init();
	
	//============================================
	//============== create HTML Code =============
	//============================================
	
	bodyTag			= Ext.Element.get(document.body); //get Instance of BodyTagExtJsElement
	containerDivTag		= bodyTag.createChild({tag: 'div'});	

	headerDivTag		= containerDivTag.createChild({tag: 'div',id: 'header',cls: 'x-layout-inactive-content',style: 'padding: 0px 0px 0px 0px'});

	navDivTag		= containerDivTag.createChild({tag: 'div',id: 'nav',cls: 'x-layout-inactive-content'});
		
	contentDivTag		= containerDivTag.createChild({tag: 'div',id: 'content',cls: 'x-layout-inactive-content'});
	toolbardivDivTag	= contentDivTag.createChild({tag: 'div',id: 'toolbardiv',cls: 'x-layout-inactive-content'});
	//gridDivTag		= contentDivTag.createChild({tag: 'div',id: 'grid',cls: 'x-layout-inactive-content'});
				
	footerDivTag		= containerDivTag.createChild({tag: 'div',id: 'footer',cls: 'x-layout-inactive-content',style: 'padding: 0px 0px 0px 0px'});

	searchdivDivTag		= bodyTag.createChild({tag: 'div',id: 'searchdiv',cls: 'x-layout-inactive-content',style: 'padding: 0px 0px 0px 0px',text:'test'});
	searchinputInputTag 	= searchdivDivTag.createChild({type: 'text' ,tag: 'input',id: 'searchinput', name:'searchinput',cls: 'x-layout-inactive-content'});
	
	//addressbookArea		= bodyTag.createChild({tag: 'div'});
	//calendarArea		= bodyTag.createChild({tag: 'div'});
	
	
	//============================================
	//============ end create HTML Code ============
	//============================================
	
	
	
	//============================================
	//==================== grid ===================
	//============================================
	
	//render grid content fields in a certain way
	function renderLastName(value, p, record){
		return String.format('<b>{0}</b> {1}', value, record.data['firstname']);
	}
	
	function renderLastNamePlain(value){
		return String.format('<b><i>{0}</i></b>', value);
	}
	
	function renderCity(value, p, record){
		return String.format('{1} / {0}', value, record.data['zip']);
	}
	
	function renderCityPlain(value){
		return String.format('<b><i>{0}</i></b>', value);
	}

	//var el = Ext.get("popup");
	//el.setStyle('background-color:#C3DAF9');
	//============================================
	//================== header ===================
	//============================================

	var headerTb = new Ext.Toolbar('header');
	

	
	//============================================
	//=============== end header ===================
	//============================================

	 
	//======================================
	//==========  toolbar definition ============
	//======================================
	var tblk = new Ext.Toolbar('header');

/*	tblk.add(
	{
		id: 'del',
		cls:'x-btn-icon delete',
		icon:'images/oxygen/16x16/actions/edit-delete.png',
		disabled: true,
		tooltip: 'delete entry',
		onClick: function() {
			m = grid.getSelections();
			for(i=0;i<m.length;i++) {
				record = ds.getById(m[i].get("userid"));
				ds.remove(record); // removes record only in grid, needs to be done also on server side
			}			
		}
	},'-',{
		id: 'details',
		pressed: false,
		enableToggle:true,
		//text: 'details',
		icon: 'images/oxygen/16x16/actions/fileview-detailed.png',
		cls: 'x-btn-icon details',
		toggleHandler: toggleDetails,
		tooltip: 'view details'
	},'-');*/

	//----------------------------------------------------------------
	//--------------------------- Combobox -----------------------
	//----------------------------------------------------------------
	
/*	var store = new Ext.data.SimpleStore({
		fields: ['id', 'state'],
		data : [
	        	['all', 'Alle Stati'],
	        	['important', 'wichtig'],
	        	['unread', 'ungelesen'],
	        	['replied', 'beantwortet'],
	        	['red', 'gelesen'],
	        	['deleted', 'gel&ouml;scht']			
		]
	});
	
	var combo = new Ext.form.ComboBox({
		store: store,
		displayField:'state',
		typeAhead: true,
		mode: 'local',
		triggerAction: 'all',
		emptyText:'select a state...',
		selectOnFocus:true,
		width:135
	});	
	
	tblk.addField(combo);	*/
	tblk.addFill();

	//======================================
	//==========  toolbar definition ============
	//==========  search field ============
	//======================================

	//template - search result of combo box appearing below the input field
	var resultTpl = new Ext.Template(
		'<div class="search-item">',
		'<h3>{lastname}</h3>',
		'</div>'
	);
	
	//combobox to enter search pattern / selecting contact address
/*	var searchCombo = new Ext.form.ComboBox({
		store: ds,
		displayField:'searchresults',
		typeAhead: true,
		loadingText: 'Searching...',
		width: 220,
		pageSize:10,
		hideTrigger:true,
		tpl: resultTpl,
		emptyText: 'Search pattern...',
		onSelect: function(record) { 
			ds.reload(
				{	params:{start:0, limit:50, test:1}	}
			);
	
			this.collapse();
			grid.getView().refresh();
		}
	});
	
	// apply it to the exsting input element
	searchCombo.applyTo('searchinput');
	tblk.addElement('searchdiv');*/

	tblk.add('-');

	tblk.add(new Ext.Toolbar.Button({
		//text: 'Logout',
		handler: onLogoutButtonClick,
		tooltip: {text:'This buttons logs you out form eGroupWare.', title:'Logout'},
		//cls: 'x-btn-text-icon blist',
		icon: 'images/oxygen/16x16/actions/system-log-out.png',
		cls: 'x-btn-icon details'
        })
	);		
	
	function onLogoutButtonClick(e) {
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
	//----------------------------------------------------------------
	//----------------------- end Combobox ---------------------
	//----------------------------------------------------------------
	
	//combobox
	var btns = tblk.items.map;

	//======================================
	//==========  layout definition ============
	//======================================
	var combo;
	
	var layout = new Ext.BorderLayout(document.body, {
		north: {
			split:false,
			initialSize: 26
		},
		south: {
			split:false,
			initialSize: 20
		},
		west: {
			split:true,
			initialSize: 225,
			minSize: 100,
			maxSize: 500,
			autoScroll:true,
			collapsible:true,
			titlebar: true,
			animate: true
		},
		center: {
			split:true,
			collapsible:true,
			//titlebar: true,
			animate: true,						
			useShim:true
		}		
	});
	
	layout.beginUpdate();
	layout.add('north', new Ext.ContentPanel(headerDivTag, {fitToFrame:true}));
	layout.add('south', new Ext.ContentPanel(footerDivTag, {fitToFrame:true}));
	layout.add('west', new Ext.ContentPanel(navDivTag, {fitToFrame:true}));

	//var curGridPanel = new Ext.GridPanel(grid);

	//layout.add('center', curGridPanel);
	layout.endUpdate();

	//============================================
	//============ end layout definition =============
	//============================================
	
	//============================================
	//=============== Context Menu ================
	//============================================

	var ctxMenu = new Ext.menu.Menu({
		id:'copyCtx', items: [{
			id:'expand',
			text:'unread'
		},{
                	id:'collapse',
                	text:'important'
		}]
	});
	
	var ctxMenuTreeFellow = new Ext.menu.Menu({
		id:'copyCtx', items: [{
			id:'expand',
			text:'unread fellow'
		},{
                	id:'collapse',
                	text:'important fellow'
		}]
	});
	
	var ctxMenuTreeTeam = new Ext.menu.Menu({
		id:'copyCtx', items: [{
			id:'expand',
			text:'unread team'
		},{
                	id:'collapse',
                	text:'important team'
		}]
	});
	
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
	//============== open dialog ===================
	//============================================	
    var _openDialog = function(_id,_dtype) {
        var url;
        var w = 1024, h = 786;
        var popW = 950, popH = 600;
        
		if(_dtype == 'list') {
			popW = 450, popH = 600;		
		}
	
        if (document.all) {
            /* the following is only available after onLoad */
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else if (window.innerWidth) {
            w = window.innerWidth;
            h = window.innerHeight;
            x = window.screenX;
            y = window.screenY;
        }
        var leftPos = ((w-popW)/2)+y, topPos = ((h-popH)/2)+x;
        
        if(_dtype == 'list' && !_id) {
            url = 'index.php?method=Addressbook.editList';
        }
		else if(_dtype == 'list' && _id){
           url = 'index.php?method=Addressbook.editList&contactid=' + _id;
        }
		else if(_dtype != 'list' && _id){
           url = 'index.php?method=Addressbook.editContact&contactid=' + _id;
        }
		else {
            url = 'index.php?method=Addressbook.editContact';
        }
        //console.log(url);
        appId = 'addressbook';
        var popup = window.open(
            url, 
            'popupname',
            'width='+popW+',height='+popH+',top='+topPos+',left='+leftPos+',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no'
        );
        
        return;
    }
	//============================================
	//============== end open dialog ===============
	//============================================	
	
	

	//============================================
	//=============== tree definition ===============
	//============================================

	var Tree = Ext.tree;

	treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
	//treeLoader.baseParams.func = 'getSubTree';
	treeLoader.on("beforeload", function(loader, node) {
		loader.baseParams.method   = node.attributes.application + '.getSubTree';
		loader.baseParams._node     = node.id;
        loader.baseParams._datatype = node.attributes.datatype;
        loader.baseParams._owner    = node.attributes.owner;
		loader.baseParams._location = 'mainTree';
	}, this);
	            
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
	
	//handle drag and drop
	tree.on('beforenodedrop', function(e) {
            //console.log(e);
            //console.log(e.data);
            
            e.cancel = true;
            
            return;
			sourceAppId = getAppByNode(e.dropNode);
			targetAppId = getAppByNode(e.target);
	
			//drag drop within the tree
			if(e.tree.id == 'nav') {				
				eval('EGWNameSpace.'+sourceAppId+'.handleDragDrop(e)');
			}
			//drag drop grid to tree
			else 
			{
			
				var s = e.data.selections, r = [];
			    for(var i = 0; i < s.length; i++) {
			        var draggedItem = s[i].data.lastname;				
					//alert(this.getRootNode().getDepth());
					//alert(this.getRootNode().findChild('id', 'personal_lk'));
					//alert(e.tree.getRootNode().findChild('id','4'));
					
					//FIND CHILD is searching only in the current level !!, submenu levels are not concerned
					
					//if(!this.getRootNode().findChild('id', 'personal_lk')){ // <-- filter duplicates , still wrong
			            r.push(new Ext.tree.TreeNode({ // build array of TreeNodes to add
			                allowDrop:false,
			                text: 'Test #' + draggedItem,
			                id: draggedItem,
							cls: 'file', 
							contextMenuClass: 'ctxMenuTreeFellow',						
							leaf: true
			            }));
			        //}
			    }
			    e.dropNode = r;  // return the new nodes to the Tree DD
			    e.cancel = r.length < 1; // cancel if all nodes were duplicates*/			
			}
	});		
	

	tree.on('click', function(node) {
		//apply panels depending on application type
		var applicationName = getAppByNode(node);
		//console.log('checking for application ' + applicationName);
		//console.log(typeof(EGWNameSpace[applicationName]));
		if(typeof(Egw[applicationName]) == 'object') {
			//console.log('application ' + applicationName + ' exists');
			layout.beginUpdate();
			Egw[applicationName].show(layout, node);
			layout.endUpdate();
		}
 	});
 	
	// set the root node
	var root = new Tree.TreeNode({
		text: 'root',
		draggable:false,
		allowDrop:false,
		id:'root'
	});
	tree.setRootNode(root);

	var overview = new Tree.AsyncTreeNode({
		text:'Today', 
		cls:'treemain', 
		allowDrag:true,
		allowDrop:true,		
		id:'overview',
		icon:'images/oxygen/16x16/actions/kdeprint-printer-infos.png',
		leaf:true
	});
	root.appendChild(overview);

	for(var i = 0; i < applications.length; i++) {
		root.appendChild(new Tree.AsyncTreeNode(applications[i]));
	}

	// render the tree
	tree.render();
	root.expand(); 
	overview.select();
	
	//============================================
	//============== end tree definition =============
	//============================================

	
	
	//============================================
	//================= footer ====================
	//============================================
	
	var headerTb = new Ext.Toolbar('footer');
	headerTb.add('5 unread emails * next meeting in 15 minutes * 2 phonecalls for today');
	
	
	
	//============================================
	//=============== end footer ===================
	//============================================
	

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
												
	
	
	
	
	//============================================
	//================ end grid ====================
	//============================================

	
});


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
