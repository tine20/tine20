/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    contentTypes: [{model: 'Timesheet', requiredRight: null}, {model: 'Timeaccount', requiredRight: 'manage'}],
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
    
    title: 'Modules',

    collapsible: true,
    baseCls: 'ux-arrowcollapse',
    animCollapse: true,
    titleCollapse:true,
    draggable : true,
    autoScroll: false,
    autoHeight: true,
    
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
        
        Ext.each(this.contentTypes, function(ct) {
            var modelName = ct.meta ? ct.meta.modelName : ct.model; 
            var recordClass = Tine[this.app.appName].Model[modelName];
            var group = recordClass.getMeta('group');
            
            if (group) {
                if(! groupNodes[group]) {
                    groupNodes[group] = new Ext.tree.TreeNode({
                        id : 'modulenode-' + recordClass.getMeta('modelName'),
                        iconCls: this.app.appName + modelName,
                        text: this.app.i18n._hidden(group),
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
            if (ct.requiredRight && (!Tine.Tinebase.common.hasRight(ct.requiredRight, this.app.appName, recordClass.getMeta('recordsName').toLowerCase()))) return true;
            var child = new Ext.tree.TreeNode({
                id : 'treenode-' + recordClass.getMeta('modelName'),
                iconCls: this.app.appName + modelName,
                text: recordClass.getRecordsName(),
                leaf : true
            });
            
            child.on('click', function() {
                this.app.getMainScreen().activeContentType = modelName;
                this.app.getMainScreen().show();
            }, this);

            // append generic ctx-items (Tine.widgets.tree.ContextMenu)
            if (ct.genericCtxActions) {
                this['contextMenu' + modelName] = Tine.widgets.tree.ContextMenu.getMenu({
                    nodeName: this.app.i18n._hidden(recordClass.getMeta('recordsName')),
                    actions: ct.genericCtxActions,
                    scope: this,
                    backend: 'Tinebase_Container',
                    backendModel: 'Container'
                });
          
                child.on('contextmenu', function(node, event) {
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
            root.childNodes[index].expanded = isExpanded;
        }, this);

        (function() {
            var node = this.getNodeById(state.selected);
            if (node) {
                node.select();
                var contentType = node.id.split('-')[1];
                this.app.getMainScreen().activeContentType = contentType ? contentType : '';
                this.app.getMainScreen().show();
            }
        }).defer(100, this);
    }
});
