/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frank Wiechmann <f.wiechmann@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
     * current position
     * @type Number
     */
    pos: null,
    
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
            getDragData: this.getDragData.createDelegate(this),
            getRepairXY: function() {
                return this.dragData.repairXY;
            }
        });
        
        var dropZone = new Ext.dd.DropZone(tabpanel.header, {
            onNodeOver : this.onNodeOver.createDelegate(this),
            onNodeDrop : this.onNodeDrop.createDelegate(this),
            getTargetFromEvent: function(e) {
                return e.getTarget('ul[class^=x-tab]', 10);
            }
        });
    },
    
    getDragData: function(e) {
        var target = this.tabpanel.findTargets(e);

        if (target.el) {
            this.pos = this.tabpanel.items.indexOf(target.item);
            
            var d = target.el.cloneNode(true);
            d.id = Ext.id();
            
            return Ext.apply(target, {
                pos: this.pos,
                ddel: d,
                repairXY: Ext.fly(target.el).getXY()
            });
        };
    },
    
    onNodeOver : function(target, dd, e, data){
        var target = this.tabpanel.findTargets(e);
        if (target.el) {
            this.pos = this.tabpanel.items.indexOf(target.item);
            var box = Ext.fly(target.el).getBox(),
                side = (e.getXY()[0] > box.x + box.width/2) ? 'r' : 'l';
            
            if (this.pos > data.pos && side === 'l') {
                this.pos = --this.pos;
            } else if (this.pos < data.pos && side === 'r') {
                this.pos = ++this.pos;
            }
        }
        
        //@TODO: separation line
        return Ext.dd.DropZone.prototype.dropAllowed;
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
        
        if (this.pos == (this.tabpanel.items.length)-1) {
            Ext.fly(data.el).insertAfter(this.tabpanel.items.itemAt(this.pos).tabEl);
        } else {
            if (this.tabpanel.items.indexOf(data.item) < this.pos) {
                Ext.fly(data.el).insertBefore(this.tabpanel.items.itemAt(this.pos+1).tabEl);
            } else {
                Ext.fly(data.el).insertBefore(this.tabpanel.items.itemAt(this.pos).tabEl);
            }
        }
        
        this.tabpanel.items.remove(data.item);
        this.tabpanel.items.insert(this.pos, data.item);
        return true;  
    }
};