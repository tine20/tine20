/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.tree');

/**
 * @namespace   Ext.ux.tree
 * @class       FileTreeSelectionModel
 * @extends     Ext.util.Observable
 * 
 * Checkbox multi selection for a TreePanel.
 */
Ext.ux.tree.FileTreeSelectionModel = function(config){
   
    Ext.apply(this, config);
    Ext.ux.tree.FileTreeSelectionModel.superclass.constructor.call(this);

};

Ext.extend(Ext.ux.tree.FileTreeSelectionModel, Ext.tree.DefaultSelectionModel, {
    
    init : function(tree){
        this.tree = tree;
        tree.mon(tree.getTreeEl(), 'keydown', this.onKeyDown, this);

        tree.dragZone.onInitDrag = this.onInitDrag;
        tree.on('click', this.onNodeClick, this);
    },

    /**
     * tree node dragzone modified, dragged node doesn't get selected
     * 
     * @param e
     */
    onInitDrag: function(e) {
        
        Tine.log.debug('initDrag');
        var data = this.dragData;
        this.tree.eventModel.disable();
        this.proxy.update("");
        data.node.ui.appendDDGhost(this.proxy.ghost.dom);
        this.tree.fireEvent("startdrag", this.tree, data.node, e); 
    }

});
