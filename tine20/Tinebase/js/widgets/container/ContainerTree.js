/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.TreePanel
 * @extends     Ext.tree.TreePanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * @param       {Object} config Configuration options
 * @description
 * <p>Utility class for generating container trees as used in the apps tree panel</p>
 * <p>This widget handles all container related actions like add/rename/delte and manager permissions<p>
 *<p>Example usage:</p>
<pre><code>
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
     * @cfg {Boolean} allowMultiSelection (defaults to true)
     */
    allowMultiSelection: true,
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
    
    useArrows: true,
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
        
        if (this.allowMultiSelection) {
            this.selModel = new Ext.tree.MultiSelectionModel({});
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
            cls: 'tinebase-tree-hide-collapsetool',
            expanded: true,
            children: [{
                path: Tine.Tinebase.container.getMyNodePath(),
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
            /**
             * @todo check acl!
             */
            onNodeOver : function(n, dd, e, data) {
                var node = n.node;
                
                // auto node expand check
                if(node.hasChildNodes() && !node.isExpanded()){
                    this.queueExpand(node);
                }
                return node.attributes.allowDrop ? 'tinebase-tree-drop-move' : false;
            },
            isValidDropPoint: function(n, dd, e, data){
                return n.node.attributes.allowDrop;
            },
            completeDrop: Ext.emptyFn
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
     * returns object of selected container or null/default
     * 
     * @param {String} [requiredGrant]
     * @param {Object} [defaultContainer]
     */
    getSelectedContainer: function(requiredGrant, defaultContainer) {
        var container = defaultContainer,
            sm = this.getSelectionModel(),
            selection = typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        if (Ext.isArray(selection)) {
            Ext.each(selection, function(node) {
                if (node && Tine.Tinebase.container.pathIsContainer(node.attributes.container.path)) {
                    if (! requiredGrant || this.hasGrant(node, requiredGrant)) {
                        container = node.attributes.container;
                        // take the first one
                        return false;
                    }
                }
            }, this);
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
        var matches = containerPath.match(/^\/personal\/{0,1}([0-9a-z_\-]*)\/{0,1}/i);
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
     * checkes if user has requested grant for given container represented by a tree node 
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {String} grant
     * @return {}
     */
    hasGrant: function(node, grant) {
        var attr = node.attributes;
        return (attr && attr.leaf && attr.container.account_grants[grant]);
    },
    
    /**
     * @private
     * - select default path
     */
    afterRender: function() {
        Tine.widgets.container.TreePanel.superclass.afterRender.call(this);
        // NOTE: selecting fires selectionChange... this breaks ftb if not rendered.
        //       As all searches return used filters, we don't need this anyway
        //this.selectContainerPath(this.getDefaultContainerPath());
        
        if (this.filterMode == 'filterToolbar' && this.filterPlugin) {
            this.filterPlugin.getGridPanel().filterToolbar.on('change', this.onFilterChange, this);
        }
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
            allowDrop: !!attr.account_grants && attr.account_grants.addGrant
        });
        
        // copy 'real' data to container space
        attr.container = Ext.copyTo({}, attr, Tine.Tinebase.Model.Container.getFieldNames());
    },
    
    /**
     * returns params for async request
     * 
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    onBeforeLoad: function(node) {
        var path = node.attributes.path;
        var type = Tine.Tinebase.container.path2type(path);
        var owner = Tine.Tinebase.container.pathIsPersonalNode(path);
        
        if (type === 'personal' && ! owner) {
            type = 'otherUsers';
        }
        
        var params = {
            method: 'Tinebase_Container.getContainer',
            application: this.app.appName,
            containerType: type,
            owner: owner
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
        
        // get selection filter from grid
        var sm = this.app.getMainScreen().getCenterPanel().getGrid().getSelectionModel();
        if (sm.getCount() === 0) {
            return false;
        }
        var filter = sm.getSelectionFilter();
        
        // move messages to folder
        Ext.Ajax.request({
            params: {
                method: 'Tinebase_Container.moveRecordsToContainer',
                targetContainerId: targetContainerId,
                filterData: filter,
                model: this.recordClass.getMeta('modelName'),
                applicationName: this.recordClass.getMeta('appName')
            },
            scope: this,
            success: function(result, request){
                // update grid
                this.filterPlugin.onFilterChange();
            }
        });
        
        // prevent repair actions
        dropEvent.dropStatus = true;
        return true;
    },
    
    /**
     * called on filtertrigger of filter toolbar
     * clears selection silently
     */
    onFilterChange: function() {
        var sm = this.getSelectionModel();
        
        sm.suspendEvents();
        sm.clearSelections();
        sm.resumeEvents();
    },
    
    /**
     * called when tree selection changes
     * 
     * @param {} sm
     * @param {} node
     */
    onSelectionChange: function(sm, nodes) {
        if (this.filterMode == 'gridFilter' && this.filterPlugin) {
            this.filterPlugin.onFilterChange();
        }
        if (this.filterMode == 'filterToolbar' && this.filterPlugin) {
            // get filterToolbar
            var ftb = this.filterPlugin.getGridPanel().filterToolbar;
            
            // remove all ftb container and /toberemoved/ filters
            ftb.supressEvents = true;
            ftb.filterStore.each(function(filter) {
                var field = filter.get('field');
                // @todo find criteria what to remove
                if (field === 'container_id' || field === 'attender') {
                    ftb.deleteFilter(filter);
                }
            }, this);
            ftb.supressEvents = false;
            
            // set ftb filters according to tree selection
            var containerFilter = this.getFilterPlugin().getContainerFilter();
            ftb.addFilter(new ftb.record(containerFilter));
        
            ftb.onFiltertrigger();
            
            // finally select the selected node, as filtertrigger clears all selections
            sm.suspendEvents();
            Ext.each(nodes, function(node) {
                sm.select(node, Ext.EventObject, true);
            }, this);
            sm.resumeEvents();
        }
    },
    
    /**
     * selects path by container Path
     * 
     * @param {String} containerPath
     * @param {String} [attr]
     * @param {Function} [callback]
     */
    selectContainerPath: function(containerPath, attr, callback) {
        return this.selectPath(this.getTreePath(containerPath), attr, callback);
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
    
    getContainerFilter: function() {
        var filter = {field: 'container_id'};
        var sm = this.treePanel.getSelectionModel();
        filter.operator = typeof sm.getSelectedNodes == 'function' ? 'in' : 'equals';
        var selection =  filter.operator === 'in' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        var values = [];
        Ext.each(selection, function(node) {
            values.push(node.attributes.container);
        }, this);
        
        filter.value = Ext.isEmpty(values) ? '' : filter.operator === 'in' ? values : values[0];
        return filter;
    },
    
    /**
     * gets value of this container filter
     */
    getValue: function() {
        // only return values if gridFilter mode
        if (this.treePanel.filterMode !== 'gridFilter') {
            return null;
        }
        
        return this.getContainerFilter();
    },
    
    /**
     * sets the selected container (node) of this tree
     * 
     * @param {Array} all filters
     */
    setValue: function(filters) {
        // only set filters if gridFilter mode
        if (this.treePanel.filterMode !== 'gridFilter') {
            return null;
        }
        
        var sm = this.treePanel.getSelectionModel();
        
        // clear all selections
        sm.clearSelections(true);
        
        Ext.each(filters, function(filter) {
            if (filter.field !== 'container_id') {
                return;
            }
            
            this.treePanel.getSelectionModel().suspendEvents();
            this.selectValue(filter.value);
        }, this);
    },
    
    selectValue: function(value) {
        var values = Ext.isArray(value) ? value : [value];
        Ext.each(values, function(value) {
            var treePath = this.treePanel.getTreePath(value.path);
            this.selectPath.call(this.treePanel, treePath, null, function() {
                // mark this expansion as done and check if all are done
                value.isExpanded = true;
                var allValuesExpanded = true;
                Ext.each(values, function(v) {
                    allValuesExpanded &= v.isExpanded;
                }, this);
                
                if (allValuesExpanded) {
                    this.treePanel.getSelectionModel().resumeEvents();
                }
            }.createDelegate(this), true)
        }, this);
    },
    
    /**
     * Selects the node in this tree at the specified path. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the selection is complete. The callback will be called with
     * (bSuccess, oSelNode) where bSuccess is if the selection was successful and oSelNode is the selected node.
     */
    selectPath : function(path, attr, callback, keep){
        attr = attr || 'id';
        var keys = path.split(this.pathSeparator),
            v = keys.pop();
        if(keys.length > 1){
            var f = function(success, node){
                if(success && node){
                    var n = node.findChild(attr, v);
                    if(n){
                        n.getOwnerTree().getSelectionModel().select(n, false, keep);
                        if(callback){
                            callback(true, n);
                        }
                    }else if(callback){
                        callback(false, n);
                    }
                }else{
                    if(callback){
                        callback(false, n);
                    }
                }
            };
            this.expandPath(keys.join(this.pathSeparator), attr, f);
        }else{
            this.root.select();
            if(callback){
                callback(true, this.root);
            }
        }
    }
});
