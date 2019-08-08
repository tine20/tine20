/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.ContentTypeTreePanel
 * @extends     Ext.tree.TreePanel
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @param       {Object} config Configuration options
 * @description
 * <p>Utility class for generating content type trees as used in the apps westpanel</p>
 *<p>Example usage:</p>
<pre><code>
var modulePanel =  new Tine.widgets.ContentTypeTreePanel({
    app: Tine.Tinebase.appMgr.get('Timetracker'),
    contentTypes: [{modelName: 'Timesheet', requiredRight: null}, {modelName: 'Timeaccount', requiredRight: 'manage'}],
    contentType: 'Timeaccount'
});
</code></pre>
 */
Tine.widgets.ContentTypeTreePanel = function(config) {
    Ext.apply(this, config);
        
    Tine.widgets.ContentTypeTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.ContentTypeTreePanel, Ext.tree.TreePanel, {
    rootVisible : false,
    border : false,

    root: null,
    
    title: 'Modules', // i18n._('Modules')

    collapsible: true,
    baseCls: 'ux-arrowcollapse',
    animCollapse: true,
    titleCollapse:true,
    draggable : true,
    autoScroll: false,
    autoHeight: true,
    canonicalName: 'ModulPicker',
    
    collapsed: false,
    renderHidden: true,
    
    recordClass: null,
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Array} contentTypes
     */
    contentTypes: null,
    
    /**
     * @cfg {String} contentType 
     */
    contentType: null,
    
    /**
     * Enable state
     * 
     * @type {Boolean}
     */
    stateful: true,
    
    /**
     * if a previous state has been applied, this is set to true.
     * if restoring the last state fails, this is set to false
     * 
     * @type Boolean
     */
    stateApplied: null,
    
    /**
     * define state events
     * 
     * @type {Array}
     */
    stateEvents: ['collapse', 'expand', 'collapsenode', 'expandnode'],
    
    /**
     * @private {Ext.tree.treeNode} the latest clicked node
     */
    lastClickedNode: null,
    
    /**
     * init
     */  
    initComponent: function() {
        this.stateId = this.app.name + (this.contentType ? this.contentType : '') + '-moduletree';
        Tine.widgets.ContentTypeTreePanel.superclass.initComponent.call(this);
        
        this.setTitle(i18n._(this.title));
        
        var treeRoot = new Ext.tree.TreeNode({
            expanded: true,
            text : '',
            allowDrag : false,
            allowDrop : false,
            icon : false
        });
        var groupNodes = {};
        this.setRootNode(treeRoot);
        var treeRoot = this.getRootNode();
        
        this.recordClass = Tine[this.app.appName].Model[this.contentType];
        
        this.on('click', this.saveClickedNodeState, this);

        Ext.each (this.contentTypes, function(ct) {
            var modelName = ct.hasOwnProperty('meta') 
                ? ct.meta.modelName 
                : (
                    ct.hasOwnProperty('model')
                        ? ct.model
                        : ct.modelName
                ); 
            
            var modelApp = ct.app ? ct.app : (ct.appName ? Tine.Tinebase.appMgr.get(ct.appName) : this.app);
            
            var recordClass = Tine[modelApp.appName].Model[modelName];

            if (! recordClass && !ct.text) {
                // module is disabled (feature switch) or otherwise non-functional
                return;
            }

            var contentType = ct.contentType ? ct.contentType : recordClass.getMeta('modelName');
            
            var group = recordClass ? recordClass.getMeta('group') : false;
            
            if (group) {
                if(! groupNodes[group]) {
                    groupNodes[group] = new Ext.tree.TreeNode({
                        id : 'modulenode-' + group,
                        iconCls: modelApp.appName + modelName,
                        text: modelApp.i18n._hidden(group),
                        leaf : false,
                        expanded: false
                    });
                    treeRoot.appendChild(groupNodes[group]);
                }
                var parentNode = groupNodes[group];
            } else {
                var parentNode = treeRoot;
            }
            
            // check requiredRight if any
            // add check for model name also
            if (
                ct.requiredRight && recordClass &&
                ! Tine.Tinebase.common.hasRight(ct.requiredRight, this.app.appName, recordClass.getMeta('recordsName').toLowerCase()) &&
                ! Tine.Tinebase.common.hasRight(ct.requiredRight, this.app.appName, recordClass.getMeta('modelName').toLowerCase()) &&
                ! Tine.Tinebase.common.hasRight(ct.requiredRight, this.app.appName, recordClass.getMeta('modelName').toLowerCase() + 's')
            ) return true;

            var c = {
                id : 'treenode-' + contentType,
                contentType: contentType,
                iconCls: ct.iconCls ? ct.iconCls : modelApp.appName + modelName,
                text: ct.text ? ct.text : recordClass.getModuleName(),
                leaf : true
            };
            
            if (ct.genericCtxActions && recordClass) {
                c.container = modelApp.getRegistry().get('default' + recordClass.getMeta('modelName') + 'Container');
            }
            
            var child = new Ext.tree.TreeNode(c);
            
            child.on('click', function() {
                this.app.getMainScreen().setActiveContentType(contentType);
            }, this);

            // append generic ctx-items (Tine.widgets.tree.ContextMenu)
            if (ct.genericCtxActions) {
                this['contextMenu' + modelName] = Tine.widgets.tree.ContextMenu.getMenu({
                    nodeName: modelApp.i18n.ngettext(recordClass.getMeta('recordName'), recordClass.getMeta('recordsName'), 12),
                    actions: ct.genericCtxActions,
                    scope: this,
                    backend: 'Tinebase_Container',
                    backendModel: 'Container'
                });
          
                child.on('contextmenu', function(node, event) {
                    event.stopEvent();
                    if(node.leaf) {
                        this.ctxNode = node;
                        this['contextMenu' + modelName].showAt(event.getXY());
                    }
                }, this);
            }
            
            parentNode.appendChild(child);
        }, this);
    },
    
    /**
     * saves the last clicked node as state
     * 
     * @param {Ext.tree.treeNode} node
     * @param {Ext.EventObjectImpl} event
     */
    saveClickedNodeState: function(node, event) {
        this.lastClickedNode = node;
        this.saveState();
    },
    
    /**
     * @see Ext.Component
     */
    getState: function() {
        var root = this.getRootNode();
        
        var state = {
            expanded: [],
            selected: this.lastClickedNode ? this.lastClickedNode.id : null
        };
        Ext.each(root.childNodes, function(node) {
            state.expanded.push(!! node.expanded);
        }, this);
        
        return state;
    },
    
    /**
     * applies state to cmp
     * 
     * @param {Object} state
     */
    applyState: function(state) {
        var root = this.getRootNode();
        Ext.each(state.expanded, function(isExpanded, index) {
            // check if node exists, as user might have lost permissions for modules
            if (root.childNodes[index]) {
                root.childNodes[index].expanded = isExpanded;
            }
        }, this);

        (function() {
            var node = this.getNodeById(state.selected);
            if (node) {
                node.select();
                this.stateApplied = true;
                var contentType = node.id.split('-')[1];
                this.app.getMainScreen().setActiveContentType(contentType ? contentType : '');
            } else {
                this.stateApplied = false;
            }
            
        }).defer(10, this);
    },
    
    /**
     * is called after render, calls the superclass and this.afterRenderSelectNode
     */
    afterRender: function () {
        Tine.widgets.ContentTypeTreePanel.superclass.afterRender.call(this);
        
        this.afterRenderSelectNode();
    },
    
    /**
     * is called after render and will be deferred, if state restoring is still running
     */
    afterRenderSelectNode: function() {
        // wait if we don't know already if the state could be applied
        if (this.stateful === true && this.stateApplied === null) {
            this.afterRenderSelectNode.defer(20, this)
            return;
        }
        
        // don't do anything, if a state has been applied
        if (this.stateful === true && this.stateApplied === true) {
            return;
        }
        
        // find treenode to select
        var treeNode = this.getRootNode().findChild('id', 'treenode-' + this.contentType);
        
        // if no treenode was found, try to find the module node
        if (! treeNode) {
            // get group by current contentType
            for (var index = 0; index < this.contentTypes.length; index++) {
                if (this.contentTypes[index].modelName == this.contentType) {
                    var group = this.contentTypes[index].group;
                }
            }
            
            // if a group was found, try to expand the node
            if (group) {
                var moduleNode = this.getRootNode().findChild('id', 'modulenode-' + group);
                if (moduleNode) {
                    moduleNode.expand();
                }
                // try to find node a bit later after expanding the parent, so we can be sure the node is rendered already
                (function() {
                    var treeNode = moduleNode.findChild('id', 'treenode-' + this.contentType);
                    if (treeNode) {
                        this.getSelectionModel().select(treeNode);
                    }
                }).defer(50, this);
            }
        } else {
            //  select the node if it has been found
            if (treeNode) {
                this.getSelectionModel().select(treeNode);
            }
        }
    },
    
    /**
     * initializes the state and is called before afterRender
     * 
     * @see Ext.Component
     */
    initState: function() {
        Tine.widgets.ContentTypeTreePanel.superclass.initState.call(this);
        
        (function() {
            if (this.stateApplied === null) {
                this.stateApplied = false;
            }
        }).defer(50, this);
    }
});
