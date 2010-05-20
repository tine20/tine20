/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frank Wiechmann <f.wiechmann@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */



Ext.ns('Ext.ux');


/**
 * @class SortPlugin
 * @param {Object} config Configurations options
 */
Ext.ux.TabPanelSortPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.TabPanelSortPlugin.prototype = {
    /**
     * tabpanel
     * @type object
     */
    tabpanel: null,
    
    
    /**
     * init
     * @param {} cmp
     */
    init: function(cmp){
        this.tabpanel = cmp;
        
        this.handler = null;
        this.scope = null;

        this.tabpanel.on('render', this.onRender, this);
    },
    
    
    /**
     * onRender defines dragZone and dropZone
     * 
     * @param {} tabpanel
     */
    onRender: function(tabpanel) {
        var dragZone = new Ext.dd.DragZone(tabpanel.header, {
            getDragData: function(e) {
                var target = tabpanel.findTargets(e);

                if (target.el) {
                    d = target.el.cloneNode(true);
                    d.id = Ext.id();
                    return Ext.apply(target, {
                        ddel: d,
                        repairXY: Ext.fly(target.el).getXY()
                    });
                };
            },
            
            getRepairXY: function() {
                return this.dragData.repairXY;
            }
        });
        
        var dropZone = new Ext.dd.DropZone(tabpanel.header, {
            getTargetFromEvent: function(e) {
                return e.getTarget('ul[class^=x-tab]', 10);
            },
    
            onNodeOver : function(target, dd, e, data){
            	//@TODO: calculate position
            	data.position = 2;
            	//@TODO: separation line
                return Ext.dd.DropZone.prototype.dropAllowed;
            },
    
            onNodeOut : function(target, dd, e, data){
                
            },
            
            onNodeDrop : this.onNodeDrop.createDelegate(this)
        });
    },
    
    
    /**
     * onNodeDrop determines that a DragSource has been dropped onto the drop node
     * 
     * @param {} target
     * @param {} dd
     * @param {} e
     * @param {} data
     * @return {Boolean}
     */
    onNodeDrop: function(target, dd, e, data){
    	this.tabpanel.insert(data.position, data.item);

        return true;  
    }
};