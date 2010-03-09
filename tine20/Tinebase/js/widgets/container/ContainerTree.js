/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

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
    /**
     * @cfg {String} filterMode one of:
     *   - gridFilter: hooks into the grids.store
     *   - filterToolbar: hooks into the filterToolbar (container filterModel required)
     */
    filterMode: 'gridFilter',
    
	iconCls: 'x-new-application',
	border: false,
    autoScroll: true,
	enableDrop: true,
    ddGroup: 'containerDDGroup',
    
    /**
     * @fixme not needed => all events hand their events over!!!
     * 
     * @property ctxNode holds treenode which got a contextmenu
     * @type Ext.tree.TreeNode
     */
	ctxNode: null,
    
	/**
     * init this treePanel
	 */
	initComponent: function(){
        this.loader = this.loader || new Tine.widgets.tree.Loader({
            getParams: this.onBeforeLoad.createDelegate(this),
            inspectCreateNode: this.onBeforeCreateNode.createDelegate(this)
        });
        
		this.root = {
            path: '/',
            expanded: true,
            children: [{
                path: '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId,
                id: 'personal'
            }, {
                path: '/shared',
                id: 'shared'
            }, {
                path: '/personal',
                id: 'otherUsers'
            }].concat(this.getExtraItems())
        };
        
        
		this.initContextMenu();
		
        this.getSelectionModel().on('beforeselect', this.onBeforeSelect, this);
        this.getSelectionModel().on('selectionchange', this.onSelectionChange, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
		
        Tine.widgets.container.TreePanel.superclass.initComponent.call(this);
        return;
	},
    
    /**
     * template fn for subclasses to append extra items
     * 
     * @return {Array}
     */
    getExtraItems: function() {
        return this.extraItems || [];
    },
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.container.TreeFilterPlugin({
                treePanel: this
            });
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
		this.expandPath('/');
		this.selectPath('/');
	},
    
	/**
     * @private
	 */
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
    
    /**
     * expand automatically on node click
     * 
     * @param {} node
     * @param {} e
     */
    onClick: function(node, e) {
        node.expand();
    },
    
    /**
     * show context menu
     * 
     * @param {} node
     * @param {} event
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        var container = node.attributes.container;
        
        if (node.leaf) {
            if (container.account_grants.adminGrant) {
                this.contextMenuSingleContainer.showAt(event.getXY());
            }
        } else {
            var pathParts = container.path.split('/');
            var type = Tine.Tinebase.container.path2type(pathParts);
            // @fixme when all other ctxmenu users work on paths
            this.ctxNode.attributes.containerType = type;
            
            if (type == Tine.Tinebase.container.TYPE_PERSONAL && pathParts[2] == Tine.Tinebase.registry.get('currentAccount').accountId) {
                this.contextMenuUserFolder.showAt(event.getXY());
            } else if(Tine.Tinebase.common.hasRight('admin', this.appName) || Tine.Tinebase.common.hasRight('manage_shared_folders', this.appName)) {
                this.contextMenuUserFolder.showAt(event.getXY());
            }
        }
    },

    /**
     * adopt attr
     * 
     * @param {Object} attr
     */
    onBeforeCreateNode: function(attr) {
        if (attr.accountDisplayName) {
            attr.name = attr.accountDisplayName;
            attr.path = '/personal/' + attr.accountId;
            attr.id = attr.accountId;
        }
        
        if (! attr.name && attr.path) {
            attr.name = Tine.Tinebase.container.path2name(attr.path, this.containerName, this.containersName);
        }
        
        Ext.applyIf(attr, {
            text: Ext.util.Format.htmlEncode(attr.name),
            qtip: Ext.util.Format.htmlEncode(attr.name),
            leaf: !!attr.account_grants,
            allowDrop: !!attr.account_grants,
            container: attr
        });
    },
    
    /**
     * returns params for async request
     * 
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    onBeforeLoad: function(node) {
        var pathParts = node.attributes.path.split('/');
        
        var params = {
            method: 'Tinebase_Container.getContainer',
            application: this.appName,
            containerType: Tine.Tinebase.container.path2type(pathParts),
            owner: pathParts[2]
        };
        
        return params;
    },
    
    /**
     * permit selection of nodes with missing required grant
     * 
     * @param {} sm
     * @param {} newSelection
     * @param {} oldSelection
     * @return {Boolean}
     */
    onBeforeSelect: function(sm, newSelection, oldSelection) {
        if (this.requiredGrant && newSelection.isLeaf()) {
            var accountGrants =  newSelection.attributes.container.account_grants || {};
            if (! accountGrants[this.requiredGrant]) {
                Ext.Msg.alert(_('Permission Denied'), String.format(_("You don't have the required grant to select this {0}"), this.containerName));
                return false;
            }
        }
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
    },
    
    /**
     * called when tree selection changes
     * 
     * @param {} sm
     * @param {} node
     */
    onSelectionChange: function(sm, node) {
        if (this.filterMode == 'gridFilter' && this.filterPlugin) {
            this.filterPlugin.onFilterChange();
        }
        if (this.filterMode == 'filterToolbar') {
            
        }
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
     * @cfg {ContainerTree} treePanel (required)
     */
    treePanel: null,
    
    /**
     * gets value of this container filter
     */
    getValue: function() {
        // only return values if gridFilter mode
        if (this.treePanel.filterMode !== 'gridFilter') {
            return null;
        }
        
        var sm = this.treePanel.getSelectionModel();
        var selection =  typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        var filters = [];
        Ext.each(selection, function(node) {
            // NOTE: operator gets adopted when path is parsed
            filters.push({field: 'container_id', operator: 'equals', value: node.attributes.container.path});
            //filters.push(node.attributes.container.path);
        }, this);
        
        if (filters.length == 0) {
            return {field: 'container_id', operator: 'equals', value: ''};
        } else if (filters.length == 1) {
            return filters[0];
        } else  {
            return {condition: 'OR', filters: filters};
        }
    },
    
    /**
     * sets the selected container (node) of this tree
     * 
     * @param {Array} all filters
     */
    setValue: function(filters) {
        Ext.each(filters, function(filter) {
            if (filter.field !== 'container_id') {
                return;
            }
            
            var pathParts = filter.value.path.split('/');
            if (pathParts.length > 1) {
                pathParts[1] = Tine.Tinebase.container.path2type(pathParts);
            }
            this.treePanel.selectPath(pathParts.join('/'));
        }, this);
    }
});

Tine.widgets.container.TreeLoader = Ext.extend(Tine.widgets.tree.Loader, {});
