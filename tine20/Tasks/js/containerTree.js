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
	            owner: Egw.Egwbase.Registry.get('currentAccount').account_id
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

	   this.setRootNode(treeRoot);
       
	   for(var i=0; i<initialTree.length; i++) {
           treeRoot.appendChild( new Ext.tree.AsyncTreeNode(initialTree[i]) );
       }
	},
		
 });
 
Egw.containerTreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	//private
 	createNode: function(attr){
		
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
        } else if (attr.n_fileas) {
            attr = {
                nodeType: 'Personal',
                text: attr.n_fileas,
                cls: 'folder',
                leaf: false,
                owner: attr.account_id
            }
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
    },
 });
