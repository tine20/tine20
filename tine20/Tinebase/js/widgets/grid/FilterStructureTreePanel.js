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
    
//    rootVisible: false,
    
    initComponent: function() {
        this.tbar = [{
            text: '+',
            scope: this,
            handler: this.onAddFilterPanel
        }];
        
        this.root = {
            path: '/',
            iconCls: this.filterPanel.activeFilterPanel.app ? this.filterPanel.activeFilterPanel.app.getIconCls() : '',
            expanded: true,
            text: _('or alternatively'),
            qtip: _('Show records that match to one of the following filters'),
            children: [this.createNode(this.filterPanel.activeFilterPanel)]
        };
        
        this.on('click', this.onClick, this);
        this.on('checkchange', this.onCheckChange, this);
        this.on('afterrender', this.onAfterRender, this);
        this.on('expandnode', this.filterPanel.manageHeight, this.filterPanel);
        this.on('collapsenode', this.filterPanel.manageHeight, this.filterPanel);
        this.filterPanel.on('filterpaneladded', this.onFilterPanelAdded, this);
        this.filterPanel.on('filterpanelremoved', this.onFilterPanelRemoved, this);
        this.filterPanel.on('filterpanelactivate', this.onFilterPanelActivate, this);
        this.filterPanel.activeFilterPanel.on('titlechange', this.onFilterPanelTitleChange, this);
        new Ext.tree.TreeEditor(this);
        
        Tine.widgets.grid.FilterStructureTreePanel.superclass.initComponent.call(this);
    },
    
    onAfterRender: function() {
        this.onFilterPanelActivate(this.filterPanel, this.filterPanel.activeFilterPanel);
        this.getRootNode().collapse();
    },
    
    /**
     * called when a filterPanel gets added
     * 
     * @param {Tine.widgets.grid.filterPanel} filterToolbar
     * @param {Tine.widgets.grid.filterToolbar} filterPanel
     */
    onFilterPanelAdded: function(filterToolbar, filterPanel) {
        this.getRootNode().appendChild(this.createNode(filterPanel));
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
     * called when a filterPanel gets activated
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