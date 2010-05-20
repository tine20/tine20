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
    
    onRender: function(tabpanel) {
        var dragZone = new Ext.dd.DragZone(tabpanel.header, {
            getDragData: function(e) {
                var sourceEl = e.getTarget('li[class^=x-tab]', 10);
            
                if (sourceEl) {
                    d = sourceEl.cloneNode(true);
                    d.id = Ext.id();
                    return {
                        ddel: d,
                        sourceEl: sourceEl,
                        repairXY: Ext.fly(sourceEl).getXY()
                    };
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
                return Ext.dd.DropZone.prototype.dropAllowed;
            },
    
            onNodeOut : function(target, dd, e, data){
                
            },
            
            onNodeDrop : this.onNodeDrop.createDelegate(this)
        });
    },
    
    
    onNodeDrop: function(target, dd, e, data){
        

        return true;  
    }
};
           

