/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

Tine.widgets.grid.FilterStructureTreePanel = Ext.extend(Ext.tree.TreePanel, {
    
    /**
     * @cfg {Tine.widgets.grid.FilterPanel} filterPanel
     */
    filterPanel: null,
    
    cls: 'tw-ftb-filterstructure-treepanel',
    autoScroll: true,
    border: false,
    useArrows: true,
    collapsible: true,
    collapsed: true,
    baseCls: 'ux-arrowcollapse',
    animCollapse: true,
    titleCollapse:true,
    rootVisible: false,
    
//    draggable : true,
                        
//    rootVisible: false,
    
    initComponent: function() {
        this.title = [
            '<span ext:qtip="',
            Ext.util.Format.htmlEncode(_('Show records that match to one of the following filters')),
            '">',
            Ext.util.Format.htmlEncode(_('or alternatively')),
            '</span>'
        ].join('');
        
//        this.tbar = [{
//            text: '+',
//            scope: this,
//            handler: this.onAddFilterPanel
//        }];
        
        this.root = {
            path: '/',
//            iconCls: this.filterPanel.activeFilterPanel.app ? this.filterPanel.activeFilterPanel.app.getIconCls() : '',
            expanded: true,
//            text: _('or alternatively'),
//            qtip: _('Show records that match to one of the following filters'),
            children: [
                this.createNode(this.filterPanel.activeFilterPanel),
                {id: 'addFilterPanel', text: _('Add alternative filter'), iconCls: 'action_add', leaf: true}
            ]
        };
        
        this.contextMenu = new Ext.menu.Menu({
            items: [{
                text: _('Remove Filter'),
                iconCls: 'action_remove',
                scope: this,
                handler: this.onCtxRemove
            }]
        });
        
        this.on('click', this.onClick, this);
        this.on('beforeclick', this.onBeforeClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('checkchange', this.onCheckChange, this);
        this.on('afterrender', this.onAfterRender, this);
        this.on('insert', this.onNodeInsert, this);
        this.on('textchange', this.onNodeTextChange, this);
        this.on('expandnode', this.filterPanel.manageHeight, this.filterPanel);
        this.on('collapsenode', this.filterPanel.manageHeight, this.filterPanel);
        this.on('collapse', this.filterPanel.manageHeight, this.filterPanel);
        this.on('expand', this.filterPanel.manageHeight, this.filterPanel);
        this.filterPanel.on('filterpaneladded', this.onFilterPanelAdded, this);
        this.filterPanel.on('filterpanelremoved', this.onFilterPanelRemoved, this);
        this.filterPanel.on('filterpanelactivate', this.onFilterPanelActivate, this);
        this.filterPanel.activeFilterPanel.on('titlechange', this.onFilterPanelTitleChange, this);
        
        this.editor = new Ext.tree.TreeEditor(this);
        
        Tine.widgets.grid.FilterStructureTreePanel.superclass.initComponent.call(this);
    },
    
    onContextMenu: function(node, e) {
        if (this.getRootNode().childNodes.length > 2 && node.id != 'addFilterPanel') {
            this.contextMenu.contextNode = node;
            this.contextMenu.showAt(e.getXY());
        }
    },
    
    onCtxRemove: function() {
        if (this.contextMenu.contextNode) {
            this.filterPanel.removeFilterPanel(this.contextMenu.contextNode.id);
        }
    },
    
    onAfterRender: function() {
        this.onFilterPanelActivate(this.filterPanel, this.filterPanel.activeFilterPanel);
//        this.getRootNode().collapse();
    },
    
    /**
     * called when the text of a noded changed
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {String} text
     * @param {String} oldText
     */
    onNodeTextChange: function(node, text, oldText) {
        if (node.attributes && node.attributes.filterPanel) {
            node.attributes.filterPanel.setTitle(text);
        }
    },
    
    /**
     * called when a node got inserted to this tree
     * 
     * @param {Ext.tree.TreePanel} tree
     * @param {Ext.tree.TreeNode} parent
     * @param {Ext.tree.TreeNode} node
     */
    onNodeInsert: function(tree, parent, node) {
        
        var clickEl = Ext.EventObject.getTarget('.x-tree-node-el', 10),
            isUserActionInsert = clickEl && Ext.fly(clickEl, '_treeEvents').getAttribute('tree-node-id', 'ext') == 'addFilterPanel' ? true : false,
            nodeCount = this.getRootNode().childNodes.length;
        
        // expand this panel if more than 2 childnodes (initial + add)
        if (nodeCount > 2 && this.collapsed) {
            this.expand();
        }
            
        // start editor for new node (if initialted by user)
        if (isUserActionInsert) {
            this.editor.triggerEdit.defer(100, this.editor, [node]);
        }
    },
    
    /**
     * called when a filterPanel gets added
     * 
     * @param {Tine.widgets.grid.filterPanel} filterToolbar
     * @param {Tine.widgets.grid.filterToolbar} filterPanel
     */
    onFilterPanelAdded: function(filterToolbar, filterPanel) {
//        this.getRootNode().appendChild(this.createNode(filterPanel));
//        var newNode = this.createNode(filterPanel);
//        newNode
        this.getRootNode().insertBefore(this.createNode(filterPanel), this.getNodeById('addFilterPanel'));
        filterPanel.on('titlechange', this.onFilterPanelTitleChange, this);
    },
    
    /**
     * called when a filterPanel gets removed
     * 
     * @param {Tine.widgets.grid.filterPanel} filterToolbar
     * @param {Tine.widgets.grid.filterToolbar} filterPanel
     */
    onFilterPanelRemoved: function(filterToolbar, filterPanel) {
        var node = this.getNodeById(filterPanel.id);
        delete node.filterPanel;
        if (node) {
            node.remove();
        }
    },
    
    /**
     * called when a filterPanel gets activated
     * 
     * @param {Tine.widgets.grid.filterPanel} filterToolbar
     * @param {Tine.widgets.grid.filterToolbar} filterPanel
     */
    onFilterPanelActivate: function(filterToolbar, filterPanel) {
        var node = this.getNodeById(filterPanel.id);
        if (node) {
            this.suspendEvents();
            node.select();
            this.resumeEvents();
        }
    },
    
    /**
     * called when a filterPanel title changed
     * 
     * @param {Tine.widgets.grid.filterToolbar} filterPanel
     * @param {String} text
     */
    onFilterPanelTitleChange: function(filterPanel, text) {
        var node = this.getNodeById(filterPanel.id);
        if (node) {
            this.suspendEvents();
            node.setText(text);
            this.resumeEvents();
        }
    },
    
    onBeforeClick: function(node) {
        if (node.id == 'addFilterPanel') {
            this.onAddFilterPanel();
            return false;
        }
    },
    
    onAddFilterPanel: function() {
        var filterPanel = this.filterPanel.addFilterPanel();
        this.filterPanel.setActiveFilterPanel(filterPanel);
    },
    
    onCheckChange: function(node, checked) {
        node.attributes.filterPanel.isActive = checked;
    },
    
    onClick: function(node) {
        if (node.attributes && node.attributes.filterPanel) {
            this.filterPanel.setActiveFilterPanel(node.attributes.filterPanel);
        }
    },
    
    createNode: function(filterPanel) {
        // mark filterPanel active
        filterPanel.isActive = true;
        
        return {
            checked: true,
            editable: true,
            id: filterPanel.id,
            filterPanel: filterPanel,
            leaf: true,
            text: filterPanel.title ? filterPanel.title : filterPanel.id,
            iconCls: filterPanel.app ? filterPanel.app.getIconCls() : 'leaf'
        };
    }
});