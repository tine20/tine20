/*
 * Ext JS Library 1.0.1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://www.extjs.com/license
 */

var EGWNameSpace = EGWNameSpace || {};
 
Ext.onReady(function(){
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
	//toolbardivDivTag	= contentDivTag.createChild({tag: 'div',id: 'toolbardiv',cls: 'x-layout-inactive-content'});
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
	
	//============================================
	//============== end Context Menu =============
	//============================================
	

	//============================================
	//=============== tree definition ===============
	//============================================

	var Tree = Ext.tree;

	treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
	//treeLoader.baseParams.func = 'getTree';
	treeLoader.on("beforeload", function(loader, node) {
		loader.baseParams.method   = node.attributes.application + '.getTree';
		loader.baseParams.node     = node.id;
        loader.baseParams.datatype = node.attributes.datatype;
        loader.baseParams.owner    = node.attributes.owner;
	}, this);
	            
	var tree = new Tree.TreePanel('nav', {
		animate:true,
		loader: treeLoader,
		enableDD:true,
		//lines: false,
		ddGroup: 'TreeDD',
		enableDrop: true,			
		containerScroll: true,
		rootVisible:false
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
		if(typeof(EGWNameSpace[applicationName]) == 'object') {
			//console.log('application ' + applicationName + ' exists');
			layout.beginUpdate();
			EGWNameSpace[applicationName].show(layout, node);
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
