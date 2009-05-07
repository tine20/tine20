/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.container');

 /**
  * @class       Tine.containerTreePanel
  * @package     Tine
  * @subpackage  Widgets
  * @extends     Ext.tree.TreePanel
  * @param       {Object} config Configuration options
  * @description
  * <p>Utility class for generating container trees as used in the 
  * apps tree panel</p>
  * <p>This widget handles all container related actions like add/rename/delte 
  * and manager permissions<p>
  * <p>Example usage:</p>
  * <pre><code>
  var taskPanel =  new Tine.containerTreePanel({
        iconCls: 'TasksTreePanel',
        title: 'Tasks',
        appName: 'Tasks',
        containerName: 'to do list',
        containersName: 'to do lists',
        border: false
    });
  </code></pre>
  */
Tine.widgets.container.TreePanel = function(config) {
    Ext.apply(this, config);
    
    if (this.app) {
        this.appName = this.app.appName;
        
        if (this.recordClass) {
            this.containerName = this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1);
            this.containersName = this.app.i18n._hidden(this.recordClass.getMeta('containersName'));
        }
    }
    Tine.widgets.container.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.container.TreePanel, Ext.tree.TreePanel, {
 	/**
     * @cfg {string} appName
     * name of application
     */
    appName: '',
	/**
     * @cfg {string} itemName
     * name of containers items
     */
    //itemName: 'item',
    /**
     * @cfg {string} containerName
     * name of container (singular)
     */
	containerName: 'container',
    /**
     * @cfg {string} containerName
     * name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {array} extraItems
     * additional items to display under all
     */
    extraItems: null,
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
	// presets
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
    autoScroll: true,
	
	// holds treenode which got a contextmenu
	ctxNode: null,
	
	// private
	initComponent: function(){
        var translation = new Locale.Gettext();
        translation.textdomain('Tinebase');
		
		Tine.widgets.container.TreePanel.superclass.initComponent.call(this);
		this.addEvents(
            /**
             * @event containeradded
             * Fires when a container was added
             * @param {container} the new container
             */
            'containeradd',
            /**
             * @event containerdelete
             * Fires when a container got deleted
             * @param {container} the deleted container
             */
            'containerdelete',
            /**
             * @event containerrename
             * Fires when a container got renamed
             * @param {container} the renamed container
             */
            'containerrename',
			/**
             * @event containerpermissionchange
             * Fires when a container got renamed
             * @param {container} the container whose permissions where changed
             */
            'containerpermissionchange'
		);
			
		var treeRoot = new Ext.tree.TreeNode({
	        text: 'root',
	        draggable:false,
	        allowDrop:false,
	        id:'root'
	    });
	    
	    var initialTree = [{
	        text: String.format(translation._('All {0}'), this.containersName),
	        cls: "treemain",
	        containerType: 'all',
	        id: 'all',
	        children: [{
	            text: String.format(translation._('My {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_PERSONAL,
	            id: 'user',
	            leaf: null,
	            owner: Tine.Tinebase.registry.get('currentAccount')
	        }, {
	            text: String.format(translation._('Shared {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_SHARED,
                id: 'shared',
	            children: null,
	            leaf: null,
				owner: null
	        }, {
	            text: String.format(translation._('Other Users {0}'), this.containersName),
	            cls: 'file',
	            containerType: 'otherUsers',
                id: 'otherUsers',
	            children: null,
	            leaf: null,
				owner: null
	        }]
	    }];
	    
        if(this.extraItems !== null) {
            Ext.each(this.extraItems, function(_item){
            	initialTree[0].children.push(_item);
            });
        }
	    
	    this.loader = new Tine.widgets.container.TreeLoader({
	        dataUrl:'index.php',
            displayLength: this.displayLength,
	        baseParams: {
	            jsonKey: Tine.Tinebase.registry.get('jsonKey'),
                requestType : 'JSON',
				method: 'Tinebase_Container.getContainer',
				application: this.appName,
				containerType: Tine.Tinebase.container.TYPE_PERSONAL
	        }
	    });
		
		this.loader.on("beforeload", function(loader, node) {
			loader.baseParams.containerType = node.attributes.containerType;
			loader.baseParams.owner = node.attributes.owner ? node.attributes.owner.accountId : null;
	    }, this);
        
		this.initContextMenu();
		
        this.on('click', function(node){
            // note: if node is clicked, it is not selected!
            node.getOwnerTree().selectPath(node.getPath());
            node.expand();
        }, this);
        
	    this.on('contextmenu', function(node, event){
			this.ctxNode = node;
			var container = node.attributes.container;
			var owner     = node.attributes.owner;
			switch (node.attributes.containerType) {
				case 'singleContainer':
					if (container.account_grants.adminGrant) {
						//console.log('GRANT_ADMIN for this container');
						this.contextMenuSingleContainer.showAt(event.getXY());
					}
					break;
				case Tine.Tinebase.container.TYPE_PERSONAL:
				    if (owner.accountId == Tine.Tinebase.registry.get('currentAccount').accountId) {
						//console.log('owner clicked his own folder');
						this.contextMenuUserFolder.showAt(event.getXY());
					}
					break;
				case Tine.Tinebase.container.TYPE_SHARED:
				    if(Tine.Tinebase.common.hasRight('admin', this.appName) || Tine.Tinebase.common.hasRight('manage_shared_folders', this.appName)) {
				        this.contextMenuUserFolder.showAt(event.getXY());
				    }
					break;
			}
		}, this);
		
		this.setRootNode(treeRoot);
	   
	    for(var i=0; i<initialTree.length; i++) {
           treeRoot.appendChild( new Ext.tree.AsyncTreeNode(initialTree[i]) );
        }
	},
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                
                /**
                 * gets value of this container filter
                 */
                getValue: function() {
                    var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
                    ];
                },
                
                /**
                 * sets the selected container (node) of this tree
                 * 
                 * @param {Array} all filters
                 */
                setValue: function(filters) {
                    for (var i=0; i<filters.length; i++) {
                        if (filters[i].field == 'container_id') {
                            switch (filters[i].operator) {
                                case 'equals':
                                    var parts = filters[i].value.path.replace(/^\//, '').split('/');
                                    var userId, containerId;
                                    switch (parts[0]) {
                                        case 'personal':
                                            userId = parts[1];
                                            containerId = parts[2];
                                            
                                            if (userId == Tine.Tinebase.registry.get('currentAccount').accountId) {
                                                scope.selectPath('/root/all/user/' + containerId);
                                            } else {
                                                scope.selectPath('/root/all/otherUsers/' + containerId);
                                            }
                                            break;
                                        case 'shared':
                                            containerId = parts[1];
                                            scope.selectPath('/root/all/shared/' + containerId);
                                            break;
                                        default:
                                            console.error('no such container type');
                                            break;
                                            
                                    }
                                    break;
                                case 'specialNode':
                                    switch (filters[i].value) {
                                        case 'all':
                                            scope.selectPath('/root/all');
                                            break;
                                        case 'shared':
                                        case 'otherUsers':
                                        case 'internal':
                                            scope.selectPath('/root/all' + filters[i].value);
                                            break;
                                        default:
                                            //throw new 
                                            console.error('no such container_id spechial node');
                                            break;
                                    }
                                    break;
                                case 'personalNode':
                                    if (filters[i].value == Tine.Tinebase.registry.get('currentAccount').accountId) {
                                        scope.selectPath('/root/all/user');
                                    } else {
                                        //scope.expandPath('/root/all/otherUsers');
                                        scope.selectPath('/root/all/otherUsers/' + filters[i].value);
                                    }
                                    break;
                                default:
                                    console.error('no such container_id filter operator');
                                    break;
                            }
                        }
                    }
                    //console.log(filters);
                }
            });
            
            this.on('click', function(node){
                this.filterPlugin.onFilterChange();
            }, this);
        }
        
        return this.filterPlugin;
    },
    
    /**
     * returns object of selected container or null
     */
    getSelectedContainer: function() {
        var container = null;
        
        var node = this.getSelectionModel().getSelectedNode();
        var containerType = node.attributes && node.attributes.containerType;
        if (containerType == 'singleContainer' && node.attributes.container) {
            container = node.attributes.container;
        }
        
        return container;
    },
    
	// private
	afterRender: function() {
		Tine.widgets.container.TreePanel.superclass.afterRender.call(this);
		//console.log(this);
		this.expandPath('/root/all');
		this.selectPath('/root/all');
	},
    
	// private
	initContextMenu: function() {
        var il8n = new Locale.Gettext();
        il8n.textdomain('Tinebase');
        
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu({
            il8n: il8n,
            nodeName: this.containerName,
            actions: ['add'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
	    
	    this.contextMenuSingleContainer= Tine.widgets.tree.ContextMenu.getMenu({
            il8n: il8n,
            nodeName: this.containerName,
	    	actions: ['delete', 'rename', 'grants'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
	    });
	}
});

/**
 * Helper class for {Tine.widgets.container.TreePanel}
 * 
 * @extends {Ext.tree.TreeLoader}
 * @param {Object} attr
 */
Tine.widgets.container.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    
	/**
     * @private
     */
 	createNode: function(attr) {
        
		// map attributes from Tinebase_Container to attrs from library/ExtJS
		if (attr.name) {
            if (!attr.account_grants.account_id){
                // temporary workaround, for a Zend_Json::encode problem
                attr.account_grants = Ext.util.JSON.decode(attr.account_grants);
            }
            attr = {
                containerType: 'singleContainer',
                container: attr,
                text: attr.name,
                id: attr.id,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                containerType: Tine.Tinebase.container.TYPE_PERSONAL,
                text: attr.accountDisplayName,
                id: attr.accountId,
                cls: 'folder',
                leaf: false,
                owner: attr
            };
        }
                
        attr.qtip = Ext.util.Format.htmlEncode(attr.text);
        attr.text = Ext.util.Format.htmlEncode(Ext.util.Format.ellipsis(attr.text, this.displayLength));
        
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
