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
  * @namespace   Tine.widgets.container
  * @class       Tine.containerTreePanel
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
     * @cfg {string} appName name of application
     */
    appName: '',
    /**
     * @cfg {String} requiredGrant
     * grant which is required to select leaf node(s)
     */
    requiredGrant: 'readGrant',
    /**
     * @cfg {string} containerName name of container (singular)
     */
	containerName: 'container',
    /**
     * @cfg {string} containerName name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {array} extraItems additional items to display under all
     */
    extraItems: null,
    
	// presets
	iconCls: 'x-new-application',
	rootVisible: false,
	border: false,
    autoScroll: true,
    //style: 'overflow-x: hidden; overflow-y: auto',
	
	// holds treenode which got a contextmenu
	ctxNode: null,
    
    // drag n drop
    // TODO make this configurable?
    enableDrop: true,
    ddGroup: 'containerDDGroup',
	
	// private
	initComponent: function(){
        var translation = new Locale.Gettext();
        translation.textdomain('Tinebase');
        
        if (! this.loader) {
            this.loader = new Tine.widgets.container.TreeLoader({
                appName: this.appName
            });
        }
		
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
            allowDrop:false,
	        children: [{
	            text: String.format(translation._('My {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_PERSONAL,
	            id: 'user',
	            leaf: null,
	            owner: Tine.Tinebase.registry.get('currentAccount'),
                allowDrop:false
	        }, {
	            text: String.format(translation._('Shared {0}'), this.containersName),
	            cls: 'file',
	            containerType: Tine.Tinebase.container.TYPE_SHARED,
                id: 'shared',
	            children: null,
	            leaf: null,
				owner: null,
                allowDrop:false
	        }, {
	            text: String.format(translation._('Other Users {0}'), this.containersName),
	            cls: 'file',
	            containerType: 'otherUsers',
                id: 'otherUsers',
	            children: null,
	            leaf: null,
				owner: null,
                allowDrop:false
	        }]
	    }];
        
        if(this.extraItems !== null) {
            Ext.each(this.extraItems, function(_item){
            	initialTree[0].children.push(_item);
            });
        }
	    
		this.initContextMenu();
		
        // permit selection of nodes with missing required grant
        this.getSelectionModel().on('beforeselect', function(sm, newSelection, oldSelection) {
            if (this.requiredGrant && newSelection.isLeaf()) {
                var accountGrants =  newSelection.attributes.container.account_grants || {};
                if (! accountGrants[this.requiredGrant]) {
                    Ext.Msg.alert(_('Permission Denied'), String.format(translation._("You don't have the required grant to select this {0}"), this.containerName));
                    return false;
                }
            }
        }, this);
        
        // expand automatically on node click
        this.on('click', function(node, e) {
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
        
        // define d&d drop function
        this.on('beforenodedrop', this.onBeforenodedrop, this);
		
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
            this.filterPlugin = new Tine.widgets.container.TreeFilterPlugin({
                scope: this
            });
            
            this.getSelectionModel().on('selectionchange', function(sm, node){
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
        this.getEl().first().first().applyStyles('overflow-x: hidden');
		this.expandPath('/root/all');
		this.selectPath('/root/all');
	},
    
	// private
	initContextMenu: function() {
        
        this.contextMenuUserFolder = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.containerName,
            actions: ['add'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
	    
	    this.contextMenuSingleContainer= Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.containerName,
	    	actions: ['delete', 'rename', 'grants'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
	    });
	},
    
    hasGrant: function(node, grant) {
        var attr = node.attributes;
        return (attr.containerType == "singleContainer" && attr.container.account_grants[grant]);
    },
    
    /**
     * record got dropped on container node
     * 
     * @param {Object} dropEvent
     * @private
     * 
     * TODO use Ext.Direct
     */
    onBeforenodedrop: function(dropEvent) {
        var targetContainerId = dropEvent.target.id;
        var recordIds = [];
        
        for (var i=0; i < dropEvent.data.selections.length; i++) {
            recordIds.push(dropEvent.data.selections[i].id);
        };
        
        // move messages to folder
        Ext.Ajax.request({
            params: {
                method: 'Tinebase_Container.moveRecordsToContainer',
                targetContainerId: targetContainerId,
                recordIds: recordIds,
                model: this.recordClass.getMeta('modelName'),
                applicationName: this.recordClass.getMeta('appName')
            },
            scope: this,
            success: function(_result, _request){
                // update grid
                this.filterPlugin.onFilterChange();
            }
        });
        
        // prevent repair actions
        dropEvent.dropStatus = true;
        return true;
    }
    
});


/**
 * filter plugin for container tree
 * 
 * @namespace Tine.widgets.container
 * @class     Tine.widgets.container.TreeFilterPlugin
 * @extends   Tine.widgets.grid.FilterPlugin
 */
Tine.widgets.container.TreeFilterPlugin = Ext.extend(Tine.widgets.grid.FilterPlugin, {
    
    /**
     * gets value of this container filter
     */
    getValue: function() {
        var sm = this.scope.getSelectionModel();
        var selection =  typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        var filters = [];
        Ext.each(selection, function(node) {
            filters.push(this.node2Filter(node));
        }, this);
        
        if (filters.length == 0) {
            return {field: 'container_id', operator: 'equals', value: ''};
        } else if (filters.length == 1) {
            return filters[0];
        } else  {
            return {condition: 'OR', filters: filters};
        }
    },
    
    node2Filter: function(node) {
        var filter = {field: 'container_id'};
        
        switch (node.attributes.containerType) {
            case 'singleContainer':
                filter.operator = 'equals';
                filter.value = node.attributes.container.id;
                break;
            case 'personal':
                filter.operator = 'personalNode';
                filter.value = node.attributes.owner.accountId;
                break;
            default:
                filter.operator = 'specialNode'
                filter.value = node.attributes.containerType;
                break;
        }
        
        return filter;
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
                                    this.scope.selectPath('/root/all/user/' + containerId);
                                } else {
                                    this.scope.selectPath('/root/all/otherUsers/' + containerId);
                                }
                                break;
                            case 'shared':
                                containerId = parts[1];
                                this.scope.selectPath('/root/all/shared/' + containerId);
                                break;
                            default:
                                console.error('no such container type');
                                break;
                                
                        }
                        break;
                    case 'specialNode':
                        switch (filters[i].value) {
                            case 'all':
                                this.scope.selectPath('/root/all');
                                break;
                            case 'shared':
                            case 'otherUsers':
                            case 'internal':
                                this.scope.selectPath('/root/all' + filters[i].value);
                                break;
                            default:
                                //throw new 
                                console.error('no such container_id spechial node');
                                break;
                        }
                        break;
                    case 'personalNode':
                        if (filters[i].value == Tine.Tinebase.registry.get('currentAccount').accountId) {
                            this.scope.selectPath('/root/all/user');
                        } else {
                            //scope.expandPath('/root/all/otherUsers');
                            this.scope.selectPath('/root/all/otherUsers/' + filters[i].value);
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

Tine.widgets.container.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {
    
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
                leaf: false,
                expanded: true
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
        
        attr.allowDrop = (attr.containerType && attr.containerType == 'singleContainer');
                
        attr.qtip = Ext.util.Format.htmlEncode(attr.text);
        attr.text = Ext.util.Format.htmlEncode(attr.text);
        
        this.inspectCreateNode(attr);
        
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
    
    inspectCreateNode: Ext.emptyFn,
    
    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback, scope){
        if(this.fireEvent("beforeload", this, node, callback) !== false){
            
            this.transId = Ext.Ajax.request({
                params: {
                    method: 'Tinebase_Container.getContainer',
                    application: this.appName,
                    containerType: node.attributes.containerType,
                    owner: node.attributes.owner ? node.attributes.owner.accountId : null
                },
                success: this.handleResponse,
                failure: this.handleFailure,
                scope: this,
                argument: {callback: callback, node: node, scope: scope}
            });
        } else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },
    
    /**
     * process response
     * 
     * @param {} response
     * @param {} node
     * @param {} callback
     */
    processResponse : function(response, node, callback){
        var data = Ext.util.JSON.decode(response.responseText);
        var o = data;
        
        try {
            node.beginUpdate();
            for(var i = 0, len = o.length; i < len; i++){
                var n = this.createNode(o[i]);
                if(n){
                    node.appendChild(n);
                }
            }
            node.endUpdate();
            if(typeof callback == "function"){
                callback(this, node);
            }
        }catch(e){
            this.handleFailure(response);
        }
    }

 });
