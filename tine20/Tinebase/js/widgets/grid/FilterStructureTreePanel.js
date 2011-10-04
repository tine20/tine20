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
    rootVisible: false,
    
    initComponent: function() {
        this.criteriaCount = 0;
        
        this.tbar = [{
            text: '+',
            scope: this,
            handler: this.onAddFilterPanel
        }];
        
        this.selModel = new Ext.ux.tree.CheckboxSelectionModel({
            defaultValue: true
        });
        
        this.root = {
            expanded: true,
            children: [this.createNode(this.filterPanel.activeFilterPanel)]
        };
        
        this.on('click', this.onClick, this);
        this.on('checkchange', this.onCheckChange, this);
        
        Tine.widgets.grid.FilterStructureTreePanel.superclass.initComponent.call(this);
    },
    
    onAddFilterPanel: function() {
        var filterPanel = this.filterPanel.addFilterPanel();
        this.filterPanel.setActiveFilterPanel(filterPanel);
        
        this.getRootNode().appendChild(this.createNode(filterPanel));
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
            id: filterPanel.id,
            filterPanel: filterPanel,
            leaf: true,
            text: String.format(_('Criteria {0}'), ++this.criteriaCount),
            iconCls: filterPanel.app ? filterPanel.app.getIconCls() : 'leaf'
        };
    }
});