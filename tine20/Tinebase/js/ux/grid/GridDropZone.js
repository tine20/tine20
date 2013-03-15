/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux', 'Ext.ux.grid');

/**
 * Grid Drop Zone
 * 
 * @param {Ext.grid.GridPanel} grid
 * @param {Object} config
 */
Ext.ux.grid.GridDropZone = function(grid, config){
    this.view = grid.getView();
    this.grid = grid;
    
    Ext.ux.grid.GridDropZone.superclass.constructor.call(this, this.view.mainBody.dom, config);
};

Ext.extend(Ext.ux.grid.GridDropZone, Ext.dd.DropZone, {
    getTargetFromEvent: function(e) {
        return e.getTarget(this.view.rowSelector);
    },

    onNodeEnter : function(target, dd, e, data) { 
        //Ext.fly(target).addClass('x-grid3-row-selected');
    },

    onNodeOut : function(target, dd, e, data) {
        this.removeDropIndicators(target);
        //Ext.fly(target).removeClass('x-grid3-row-selected');
    },

    onNodeOver : function(target, dd, e, data){
        var pt = this.getDropPoint(e, target, dd),
            returnCls = this.dropNotAllowed,
            cls;
        
        if(this.isValidDropPoint(target, pt, dd, e, data)){
           if(pt == "above"){
               returnCls = this.view.findRowIndex(target) === 0 ? "x-tree-drop-ok-above" : "x-tree-drop-ok-between";
               cls = "x-grid3-drag-insert-above";
           }else if(pt == "below"){
               returnCls = this.view.findRowIndex(target) === this.grid.getStore().getCount() -1 ? "x-tree-drop-ok-below" : "x-tree-drop-ok-between";
               cls = "x-grid3-drag-insert-below";
           }else{
               returnCls = "x-tree-drop-ok-append";
               cls = "x-grid3-drag-append";
           }
           if(this.lastInsertClass != cls){
               Ext.fly(target).replaceClass(this.lastInsertClass, cls);
               this.lastInsertClass = cls;
           }
       }
       return returnCls;
    },
    
    removeDropIndicators : function(target){
        if(target){
            Ext.fly(target).removeClass([
                "x-grid3-drag-insert-above",
                "x-grid3-drag-insert-below",
                "x-grid3-drag-append"
            ]);
            this.lastInsertClass = "_noclass";
        }
    },
    
    getDropPoint : function(e, target, dd) {
        var dragEl = dd.ddel,
            t = Ext.lib.Dom.getY(target),
            b = t + target.offsetHeight,
            y = Ext.lib.Event.getPageY(e),
            q = (b - t) / 2;

        return y > (t + q) ? "below" : "above";
    },
    
    isValidDropPoint: function(target, pt, dd, e, data) {
        return true;
    }
});