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
    app: Tine.Tinebase.appMgr.get('Tasks'),
    recordClass: Tine.Tasks.Task
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
        
    Tine.widgets.container.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.container.TreePanel, Ext.tree.TreePanel, {
 	/**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    /**
     * @cfg {String} defaultContainerPath
     */
    defaultContainerPath: null,
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
    /**
     * @cfg {Tine.data.Record} recordClass
     */
    recordClass: null,
    /**
     * @cfg {String} requiredGrant
     * grant which is required to select leaf node(s)
     */
    requiredGrant: 'readGrant',
    
	//iconCls: 'x-new-application',
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
	initComponent: function() {
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        var containerName = this.recordClass ? this.recordClass.getMeta('containerName') : 'container';
        var containersName = this.recordClass ? this.recordClass.getMeta('containersName') : 'containers';
        
        //ngettext('container', 'containers', n);
        this.containerName = this.containerName || this.app.i18n.n_hidden(containerName, containersName, 1);
        this.containersName = this.containersName || this.app.i18n._hidden(containersName);
        
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
        
        // init drop zone
        this.dropConfig = {
            ddGroup: this.ddGroup || 'TreeDD',
            appendOnly: this.ddAppendOnly === true,
            onNodeOver : function(n, dd, e, data) {
                var node = n.node;
                
                // auto node expand check
                if(node.hasChildNodes() && !node.isExpanded()){
                    this.queueExpand(node);
                }
                return node.attributes.allowDrop ? "x-tree-drop-ok-append" : false;
            },
            isValidDropPoint: function(n, dd, e, data){
                return n.node.attributes.allowDrop;
            }
        }
        
		this.initContextMenu();
		
        this.getSelectionModel().on('beforeselect', this.onBeforeSelect, this);
        this.getSelectionModel().on('selectionchange', this.onSelectionChange, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforeNodeDrop, this);
		
        Tine.widgets.container.TreePanel.superclass.initComponent.call(this);
        return;
	},
    
    /**
     * template fn for subclasses to set default path
     * 
     * @return {String}
     */
    getDefaultContainerPath: function() {
        return this.defaultContainerPath || '/';
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
    
    /**
     * convert containerPath to treePath
     * 
     * @param {String} containerPath
     * @return {String}
     */
    getTreePath: function(containerPath) {
        var treePath = '/' + this.getRootNode().id + (containerPath !== '/' ? containerPath : '');

        // replace personal with otherUsers if personal && ! personal/myaccountid
        var matches = treePath.match(/personal\/{0,1}(.*)/)
        if (matches) {
            if (matches[1] != Tine.Tinebase.registry.get('currentAccount').accountId) {
                treePath = treePath.replace('personal', 'otherUsers');
            } else {
                treePath = treePath.replace('personal/'  + Tine.Tinebase.registry.get('currentAccount').accountId, 'personal');
            }
        }
        
        return treePath;
    },
    
	/**
     * @private
     * 
     * - kill x-scrollers
     * - select default path
	 */
	afterRender: function() {
		Tine.widgets.container.TreePanel.superclass.afterRender.call(this);
        this.getEl().first().first().applyStyles('overflow-x: hidden');
		this.selectContainerPath(this.getDefaultContainerPath());
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
        var container = node.attributes.container,
            path = container.path,
            owner;
        
        if (! Ext.isString(path)) {
            return;
        }
        
        if (Tine.Tinebase.container.pathIsContainer(path)) {
            if (container.account_grants && container.account_grants.adminGrant) {
                this.contextMenuSingleContainer.showAt(event.getXY());
            }
        } else if (path.match(/^\/shared$/) && (Tine.Tinebase.common.hasRight('admin', this.app.appName) || Tine.Tinebase.common.hasRight('manage_shared_folders', this.app.appName))){
            this.contextMenuUserFolder.showAt(event.getXY());
        } else if (Tine.Tinebase.registry.get('currentAccount').accountId == Tine.Tinebase.container.pathIsPersonalNode(path)){
            this.contextMenuUserFolder.showAt(event.getXY());
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
            allowDrop: !!attr.account_grants && attr.account_grants.addGrant,
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
            application: this.app.appName,
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
                var message = '<b>' +String.format(_("You are not allowed to select the {0} '{1}':"), this.containerName, newSelection.attributes.text) + '</b><br />' +
                              String.format(_("{0} grant is required for desired action"), this.requiredGrant);
                Ext.Msg.alert(_('Insufficient Grants'), message);
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
    onBeforeNodeDrop: function(dropEvent) {
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
        if (this.filterMode == 'filterToolbar' && this.filterPlugin) {
            var sm = this.getSelectionModel();
            var selection =  typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
            
            // multi select not implemented in ftb yet!
            var node = selection[0];
            
            // get filterToolbar
            var ftb = this.filterPlugin.grid.filterToolbar;
            
            //var supressEvents = ftb.supressEvents;
            ftb.supressEvents = true;
            
            // remove all ftb container filters
            ftb.filterStore.each(function(filter) {
                if (filter.get('field') === 'container_id') {
                    ftb.deleteFilter(filter);
                }
            }, this);
            
            // set ftb filters according to tree selection
            ftb.supressEvents = false;
            ftb.addFilter(new ftb.record({field: 'container_id', operator: 'equals', value: node.attributes.container}));
            if (! sm.filterPluginSetValue) {
                ftb.onFiltertrigger();
            }
            
            sm.filterPluginSetValue = false;
        }
    },
    
    /**
     * selects path by container Path
     * 
     * @param {String} containerPath
     */
    selectContainerPath: function(containerPath) {
        return this.selectPath(this.getTreePath(containerPath));
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
            
            this.treePanel.getSelectionModel().filterPluginSetValue = true;
            this.treePanel.selectContainerPath(filter.value.path);
        }, this);
    }
});
