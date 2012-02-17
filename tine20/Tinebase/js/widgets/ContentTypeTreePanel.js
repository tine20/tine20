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
    collapsible : true,

    root: null,
    
    titleCollapse: true,
    title: '',
    baseCls: 'ux-arrowcollapse',

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
     * init
     */  
    initComponent: function() {
        Tine.widgets.ContentTypeTreePanel.superclass.initComponent.call(this);
        
        var treeRoot = new Ext.tree.TreeNode({
            expanded: true,
            text : '',
            allowDrag : false,
            allowDrop : false,
            icon : false
        });
    
        this.setRootNode(treeRoot);
        var treeRoot = this.getRootNode();
        
        this.recordClass = Tine[this.app.appName].Model[this.contentType];
        
        Ext.each(this.contentTypes, function(ct) {
            var recordClass = Tine[this.app.appName].Model[ct.model];
            // check requiredRight if any
            if ( ct.requiredRight && (!Tine.Tinebase.common.hasRight(ct.requiredRight, this.app.appName, recordClass.getMeta('recordsName').toLowerCase()))) return true; 
            
            var child = new Ext.tree.TreeNode({
                id : 'treenode-' + recordClass.getMeta('modelName'),
                iconCls: this.app.appName + ct.model,
                text: recordClass.getRecordsName(),
                leaf : true,
                containerType: Tine.Tinebase.container.TYPE_SHARED,
                container: Ext.apply(Tine[this.app.name].registry.get('defaultContainer'), {name: this.app.i18n._hidden(recordClass.getMeta('containerName'))})                 
            });
            
            child.on('click', function() {
                        this.app.getMainScreen().activeContentType = ct.model;
                        this.app.getMainScreen().show();
                    }, this);            

            // append generic ctx-items (Tine.widgets.tree.ContextMenu)
                    
            if(ct.genericCtxActions) {
                this['contextMenu' + ct.model] = Tine.widgets.tree.ContextMenu.getMenu({
                    nodeName: this.app.i18n._hidden(recordClass.getMeta('recordsName')),
                    actions: ct.genericCtxActions,
                    scope: this,
                    backend: 'Tinebase_Container',
                    backendModel: 'Container'
                });
          
            child.on('contextmenu', function(node, event) {
                if(node.leaf) {
                    this.ctxNode = node;
                    this['contextMenu' + ct.model].showAt(event.getXY());
                }
            }, this);         
             }       
                    
            treeRoot.appendChild(child);
        }, this); 
    },
    
    /**
     * is called after render this panel, selects active node by contentType
     */
    afterRender: function() {
        Tine.widgets.ContentTypeTreePanel.superclass.afterRender.call(this);
        
        var activeNode = this.root.findChildBy(function(node) {
            if(node.id == 'treenode-' + this.contentType) return true;
            return false;
        }, this);
        
        if(activeNode) activeNode.select();
        
        this.on('click', function(event) {
            if(activeNode) activeNode.select();
        }, this);
        
    }
 
});
