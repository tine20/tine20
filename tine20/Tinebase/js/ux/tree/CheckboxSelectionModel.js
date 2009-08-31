Ext.ns('Ext.ux', 'Ext.ux.tree');

/**
 * @class Ext.ux.tree.CheckboxSelectionModel
 * @extends Ext.util.Observable
 * Ceckbox multi selection for a TreePanel.
 */
Ext.ux.tree.CheckboxSelectionModel = function(config){
   this.addEvents(
       /**
        * @event beforeselect
        * Fires before the selected node changes, return false to cancel the change
        * @param {DefaultSelectionModel} this
        * @param {TreeNode} node the node to select
        */
       "beforeselect",
       /**
        * @event selectionchange
        * Fires when the selected nodes change
        * @param {MultiSelectionModel} this
        * @param {Array} nodes Array of the selected nodes
        */
       "selectionchange"
   );
    Ext.apply(this, config);
    Ext.ux.tree.CheckboxSelectionModel.superclass.constructor.call(this);
};

Ext.ux.tree.CheckboxSelectionModel = Ext.extend(Ext.ux.tree.CheckboxSelectionModel, Ext.util.Observable, {
    /**
     * @cfg {Bool} activateLeafNodesOnly
     * true to only activate leaf nodes
     */
    activateLeafNodesOnly : false,
    
    /**
     * @type Ext.tree.TreeNode
     * currently active node
     */
    activeNode: null,
    
    /**
     * activate given node
     * 
     * @param {Ext.tree.TreeNode} node
     * @return {Boolean}
     */
    activate: function(node) {
        if (this.activateLeafNodesOnly && ! node.isLeaf()) {
            return false;
        }
        
        if (this.activeNode) {
            this.activeNode.ui.onSelectedChange(false);
        }
        
        this.activeNode = node;
        node.ui.onSelectedChange(true);
    },
        
    /**
     * Clear all selections
     */
    clearSelections : function(suppressEvent) {
        this.suspendEvents(false);
        
        var sn = this.tree.getChecked();
        if(sn.length > 0){
            for(var i = 0, len = sn.length; i < len; i++){
                sn[i].ui.toggleCheck(false);
            }
        }
        
        this.resumeEvents();
    },
    
    /**
     * get active node
     * @return {Ext.tree.TreeNode}
     */
    getActiveNode: function() {
        return this.activeNode;
    },
    
    /**
     * Returns an array of the selected nodes
     * @return {Array}
     */
    getSelectedNodes: function() {
        return this.tree.getChecked();
    },
    
    init : function(tree){
        this.tree = tree;
        tree.getTreeEl().on("keydown", this.onKeyDown, this);
        tree.on("click", this.onNodeClick, this);
        tree.on("beforeappend", this.onBeforeAppend, this);
        tree.on("checkchange", this.onCheckChange, this);
        
    },
    
    /**
     * Returns true if the node is selected
     * @param {Ext.tree.TreeNode} node The node to check
     * @return {Boolean}
     */
    isSelected : function(node){
        return node.isChecked();  
    },
    
    onBeforeAppend: function(tree, parent, node) {
        node.attributes.checked = false;
    },
    
    onCheckChange: function(node, checked) {
        if (checked) {
            this.activate(node);
        } else {
            
        }
        this.fireEvent("selectionchange", this, this.tree.getChecked());
    },
    
    onNodeClick : function(node){
        this.select(node);
        this.activate(node);
    },
    
    /**
     * Select a node.
     * @param {Ext.tree.TreeNode} node The node to select
     * @return {Text.tree.TreeNode} The selected node
     */
    select : function(node, e, keepExisting){
        if (! node.ui.isChecked() && this.fireEvent('beforeselect', this, node) !== false) {
            node.ui.toggleCheck(true);
        }
        return node;
    },
    
    /**
     * Deselect a node.
     * @param {Ext.tree.TreeNode} node The node to unselect
     */
    unselect : function(node){
        node.ui.toggleCheck(false);
    },
    
    onKeyDown : Ext.tree.DefaultSelectionModel.prototype.onKeyDown,

    selectNext : Ext.tree.DefaultSelectionModel.prototype.selectNext,

    selectPrevious : Ext.tree.DefaultSelectionModel.prototype.selectPrevious
});