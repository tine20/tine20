/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.TreePanel
 * @extends     Ext.tree.TreePanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @param       {Object} config Configuration options
 * @description
 * <p>Utility class for generating container trees as used in the apps tree panel</p>
 * <p>This widget handles all container related actions like add/rename/delte and manager permissions<p>
 *<p>Example usage:</p>
<pre><code>
var taskPanel =  new Tine.containerTreePanel({
    app: Tine.Tinebase.appMgr.get('Tasks'),
    recordClass: Tine.Tasks.Model.Task
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
        'containerpermissionchange',
        /**
         * @event containercolorset
         * Fires when a container color got changed
         * @param {container} the container whose color where changed
         */
        'containercolorset'
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
     * @cfg {Array} requiredGrants
     * grants which are required to select leaf node(s)
     */
    requiredGrants: null,
    
    /**
     * @cfg {Boolean} useContainerColor
     * use container colors
     */
    useContainerColor: false,
    /**
     * @cfg {Boolean} useContainerColor
     * use container properties
     */
    useProperties: true,
    
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
        if (! this.appName && this.recordClass) {
            this.appName = this.recordClass.getMeta('appName');
        }
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        if (this.allowMultiSelection) {
            this.selModel = new Ext.tree.MultiSelectionModel({});
        }
        
        var containerName = this.recordClass ? this.recordClass.getContainerName() : 'container';
        var containersName = this.recordClass ? this.recordClass.getContainersName() : 'containers';

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
                path: this.getRootPath(),
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
        };
        
        this.initContextMenu();
              
        this.getSelectionModel().on('beforeselect', this.onBeforeSelect, this);
        this.getSelectionModel().on('selectionchange', this.onSelectionChange, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforeNodeDrop, this);
        this.on('append', this.onAppendNode, this);
        
        Tine.widgets.container.TreePanel.superclass.initComponent.call(this);
        return;
    },
    
    /**
     * delivers the personal root node path
     * 
     * @returns personal root node path
     */
    getRootPath: function() {
        return Tine.Tinebase.container.getMyNodePath();
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
            this.filterPlugin = new Tine.widgets.tree.FilterPlugin({
                treePanel: this
            });
        }
        
        return this.filterPlugin;
    },
    
    /**
     * returns object of selected container/filter or null/default
     * 
     * @param {Array} [requiredGrants]
     * @param {Tine.Tinebase.Model.Container} [defaultContainer]
     * @param {Boolean} onlySingle use default if more than one container in selection
     * @return {Tine.Tinebase.Model.Container}
     */
    getSelectedContainer: function(requiredGrants, defaultContainer, onlySingle) {
        var container = defaultContainer,
            sm = this.getSelectionModel(),
            selection = typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        if (Ext.isArray(selection) && selection.length > 0 && (! onlySingle || selection.length === 1 || ! container)) {
            container = this.getContainerFromSelection(selection, requiredGrants) || container;
        } 
        // postpone this as we don't get the whole container record here
//        else if (this.filterMode == 'filterToolbar' && this.filterPlugin) {
//            container = this.getContainerFromFilter() || container;
//        }
        
        return container;
    },
    
    /**
     * get container from selection
     * 
     * @param {Array} selection
     * @param {Array} requiredGrants
     * @return {Tine.Tinebase.Model.Container}
     */
    getContainerFromSelection: function(selection, requiredGrants) {
        var result = null;

        Ext.each(selection, function(node) {
            if (node && Tine.Tinebase.container.pathIsContainer(node.attributes.container.path)) {
                if (! requiredGrants || this.hasGrant(node, requiredGrants)) {
                    result = node.attributes.container;
                    // take the first one
                    return false;
                }
            }
        }, this);
        
        return result;
    },

    /**
     * get container from filter toolbar
     * 
     * @param {Array} requiredGrants
     * @return {Tine.Tinebase.Model.Container}
     * 
     * TODO make this work -> atm we don't get the account grants here (why?)
     */
    getContainerFromFilter: function(requiredGrants) {
        var result = null;
        
        // check if single container is selected in filter toolbar 
        var ftb = this.filterPlugin.getGridPanel().filterToolbar,
            filterValue = null;

        ftb.filterStore.each(function(filter) {
            if (filter.get('field') == this.recordClass.getMeta('containerProperty')) {
                filterValue = filter.get('value');
                if (filter.get('operator') == 'equals') {
                    result = filterValue;
                } else if (filter.get('operator') == 'in' && filterValue.length == 1){
                    result = filterValue[0];
                }
                // take the first one
                return false;
            }
        }, this);
        
        return result;
    },
    
    /**
     * convert containerPath to treePath
     * 
     * @param {String}  containerPath
     * @return {String} treePath
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
     * @param {Array} grant
     * @return {}
     */
    hasGrant: function(node, grants) {
        var attr = node.attributes,
            condition = false;

        if(attr && attr.leaf) {
            condition = true;
            Ext.each(grants, function(grant) {
                condition = condition && attr.container.account_grants[grant];
            }, this);
        }

        return condition;
    },
    
    /**
     * @private
     * - select default path
     */
    afterRender: function() {
        Tine.widgets.container.TreePanel.superclass.afterRender.call(this);
        
        var defaultContainerPath = this.getDefaultContainerPath();
        
        if (defaultContainerPath && defaultContainerPath != '/') {
            var root = '/' + this.getRootNode().id;
            
            this.expand();
            // @TODO use getTreePath() when filemanager is fixed
            this.selectPath.defer(100, this, [root + defaultContainerPath]);
        }
        
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
        
        this.contextMenuSingleContainer = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.containerName,
            actions: ['delete', 'rename', 'grants'].concat(
                this.useProperties ? ['properties'] : []
            ).concat(
                this.useContainerColor ? ['changecolor'] : []
            ),
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
        
        this.contextMenuSingleContainerProperties = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.containerName,
            actions: ['properties'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
    },
    
    /**
     * called when node is appended to this tree
     */
    onAppendNode: function(tree, parent, appendedNode, idx) {
        if (appendedNode.leaf && this.hasGrant(appendedNode, this.requiredGrants)) {
            if (this.useContainerColor) {
                appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
                    this.colorNode = Ext.DomHelper.insertAfter(this.iconNode, {tag: 'span', html: '&nbsp;&#9673;&nbsp', style: {color: appendedNode.attributes.container.color || '#808080'}}, true);
                }, appendedNode.ui);
            }
        }
    },
    
    /**
     * expand automatically on node click
     * 
     * @param {} node
     * @param {} e
     */
    onClick: function(node, e) {
        var sm = this.getSelectionModel(),
            selectedNode = sm.getSelectedNode();
    
        // NOTE: in single select mode, a node click on a selected node does not trigger 
        //       a selection change. We need to do this by hand here
        if (! this.allowMultiSelection && node == selectedNode) {
            this.onSelectionChange(sm, node);
        }
        
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
            } else {
                this.contextMenuSingleContainerProperties.showAt(event.getXY());
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
            qtip: Tine.Tinebase.common.doubleEncode(attr.name),
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
            requiredGrants: this.requiredGrants,
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
                this.app.getMainScreen().getCenterPanel().loadGridData();
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
        /* 2012-01-30 cweiss: i have no idea what this was for.
         * if its needed please document here!
        var sm = this.getSelectionModel();
        
        sm.suspendEvents();
        sm.clearSelections();
        sm.resumeEvents();
        */
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
            
            // in case of filterPanel
            ftb = ftb.activeFilterPanel ? ftb.activeFilterPanel : ftb;

            // remove all ftb container and /toberemoved/ filters
            ftb.supressEvents = true;
            ftb.filterStore.each(function(filter) {
                var field = filter.get('field');
                // @todo find criteria what to remove
                if (field === 'container_id' || field === 'attender' || field === 'path') {
                    ftb.deleteFilter(filter);
                }
            }, this);
            ftb.supressEvents = false;
            
            // set ftb filters according to tree selection
            var containerFilter = this.getFilterPlugin().getFilter();
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
    },
    
    /**
     * get default container for new records
     * 
     * @param {String} default container registry key
     * @return {Tine.Tinebase.Model.Container}
     */
    getDefaultContainer: function(registryKey) {
        if (! registryKey) {
            registryKey = 'defaultContainer';
        }
        
        var container = Tine[this.appName].registry.get(registryKey);
        
        return this.getSelectedContainer('addGrant', container, true);
    }
});
