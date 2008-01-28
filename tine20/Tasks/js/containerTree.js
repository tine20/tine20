/*
 * egroupware 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

 /**
  * @class Egw.containerTreePanel
  * @package     Egw
  * @subpackage  Widgets
  * <p> Utility class for generating container trees as used in the 
  * apps tree panel</p>
  * <p>Example usage:</p>
  * <pre><code>
  var taskPanel =  new Egw.containerTreePanel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        itemName: 'Tasks',
        appName: 'Tasks',
        border: false
    });
  </code></pre>
  */
 Egw.containerTreePanel = Ext.extend(Ext.tree.TreePanel, {
 	/**
     * @cfg {string} appName
     * name of application
     */
    appName: '',
	/**
     * @cfg {string} itemName
     * name of containers items
     */
    itemName: 'item',
    
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
	
	//private
	initComponent: function(){
		Egw.containerTreePanel.superclass.initComponent.call(this);
		
		var treeRoot = new Ext.tree.TreeNode({
	        text: 'root',
	        draggable:false,
	        allowDrop:false,
	        id:'root'
	    });
	    
	    var initialTree = [{
	        text: 'All ' + this.itemName,
	        cls: "treemain",
	        nodeType: 'all',
	        id: 'all',
	        children: [{
	            text: 'My ' + this.itemName,
	            cls: 'file',
	            nodeType: 'Personal',
	            id: 'user',
	            leaf: null,
	            owner: Egw.Egwbase.Registry.get('currentAccount').accountId
	        }, {
	            text: 'Shared ' + this.itemName,
	            cls: 'file',
	            nodeType: 'Shared',
	            children: null,
	            leaf: null,
				owner: null
	        }, {
	            text: 'Other Users ' + this.itemName,
	            cls: 'file',
	            nodeType: 'OtherUsers',
	            children: null,
	            leaf: null,
				owner: null
	        }]
	    }];
	    
	    this.loader = new Egw.containerTreeLoader({
	        dataUrl:'index.php',
	        baseParams: {
	            jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
				method: 'Egwbase.getContainer',
				application: this.appName,
				nodeType: 'Personal'
				 
	        }
	    });
		
		this.loader.on("beforeload", function(loader, node) {
			loader.baseParams.nodeType = node.attributes.nodeType;
			loader.baseParams.owner    = node.attributes.owner;
	    }, this);

        this.on("activate", function(){
			this.expandPath('/root/all');
		}, this);
	    
		this.setRootNode(treeRoot);
	   
	    for(var i=0; i<initialTree.length; i++) {
           treeRoot.appendChild( new Ext.tree.AsyncTreeNode(initialTree[i]) );
        }
		
		
	},
	afterRender: function() {
		Egw.containerTreePanel.superclass.afterRender.call(this);
		//console.log(this);
		this.expandPath('/root/all');
	}
		
 });

Ext.namespace('Egw.widgets', 'Egw.widgets.container');
Egw.widgets.container.selectionComboBox = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfg {array}
     * default container
     */
    defaultContainer: false,
	
    allowBlank: false,
    readOnly:true,
	container_id: null,
	
	// private
	initComponent: function(){
		Egw.widgets.container.selectionComboBox.superclass.initComponent.call(this);
        if (this.defaultContainer) {
			this.container_id = this.defaultContainer.container_id;
			this.value = this.defaultContainer.container_name;
		}
		this.onTriggerClick = function(e) {
            var w = new Egw.widgets.container.selectionDialog({
				TriggerField: this
			});
        };
	},
	//private
	getValue: function(){
		return this.container_id;
	}
});

Egw.widgets.container.selectionDialog = Ext.extend(Ext.Component, {
	title: 'please select a container',

	// private
    initComponent: function(){
		Egw.widgets.container.selectionDialog.superclass.initComponent.call(this);
		console.log(this.hallo);
		var w = new Ext.Window({
			title: this.title,
			modal: true,
			width: 375,
			height: 400,
			minWidth: 375,
			minHeight: 400,
			layout: 'fit',
			plain: true,
			bodyStyle: 'padding:5px;',
			buttonAlign: 'center'
		});
		
		var tree = new Egw.containerTreePanel({
			itemName: this.TriggerField.itemName,
			appName: this.TriggerField.appName,
			defaultContainer: this.TriggerField.defaultContainer
		});
		
		tree.on('click', function(_node) {
            if(_node.attributes.nodeType == 'singleContainer') {
				
				this.TriggerField.container = _node.attributes.container;
				this.TriggerField.setValue(_node.attributes.text);
                w.hide();
            }
        }, this);
			
		w.add(tree);
		w.show();
	}
});


			
Egw.containerTreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	//private
 	createNode: function(attr)
 	{
		// map attributes from Egwbase_Container to attrs from ExtJS
		if (attr.container_name) {
            //console.log(this.baseParams);
            attr = {
                nodeType: 'singleContainer',
                container: attr.container_id,
                text: attr.container_name,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                nodeType: 'Personal',
                text: attr.accountDisplayName,
                cls: 'folder',
                leaf: false,
                owner: attr.accountId
            };
        }
		

		// apply baseAttrs, nice idea Corey!
        if(this.baseAttrs){
            Ext.applyIf(attr, this.baseAttrs);
        }
        if(this.applyLoader !== false){
            attr.loader = this;
        }
        if(typeof attr.uiProvider == 'string'){
           attr.uiProvider = this.uiProviders[attr.uiProvider] || eval(attr.uiProvider);
        }
        return(attr.leaf ?
                        new Ext.tree.TreeNode(attr) :
                        new Ext.tree.AsyncTreeNode(attr));
    }
 });
