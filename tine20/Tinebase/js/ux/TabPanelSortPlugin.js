/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Ext.ux');


/**
 * @class       SortPlugin
 * @namespace   Ext.ux
 * 
 * @constructor
 * @param {Object} config Configurations options
 */
Ext.ux.TabPanelSortPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.TabPanelSortPlugin.prototype = {
    
    /**
     * @cfg {Object} dragZoneConfig
     */
    dragZoneConfig: null,
    
    /**
     * @cfg {Object} dropZoneConfig
     */
    dropZoneConfig: null,
    
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
     * init this plugin
     * 
     * @param {Ext.Component} cmp
     */
    init: function(cmp){
        this.tabpanel = cmp;
        
        this.handler = null;
        this.scope = null;

        this.tabpanel.on('render', this.onRender, this);
    },
    
    
    /**
     * onRender define dragZone and dropZone
     * 
     * @param {Ext.TabPanel} tabpanel
     */
    onRender: function(tabpanel) {
        var dragZone = new Ext.dd.DragZone(tabpanel.header, Ext.apply({
            getDragData: this.getDragData.createDelegate(this),
            getRepairXY: this.getRepairXY.createDelegate(this)
        }, this.dragZoneConfig || {}));
        
        var dropZone = new Ext.dd.DropZone(tabpanel.header, Ext.apply({
            onNodeOver : this.onNodeOver.createDelegate(this),
            onNodeDrop : this.onNodeDrop.createDelegate(this),
            getTargetFromEvent: this.getTargetFromEvent.createDelegate(this)
        }, this.dropZoneConfig || {}));
    },
    
    /**
     * Called when a mousedown occurs in this container. Looks in {@link Ext.dd.Registry}
     * for a valid target to drag based on the mouse down. Override this method
     * to provide your own lookup logic (e.g. finding a child by class name). Make sure your returned
     * object has a "ddel" attribute (with an HTML Element) for other functions to work.
     * @param {EventObject} e The mouse down event
     * @return {Object} The dragData
     */
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
    
    /**
     * Called while the DropZone determines that a {@link Ext.dd.DragSource} is over a drop node
     * that has either been registered or detected by a configured implementation of {@link #getTargetFromEvent}.
     * The default implementation returns this.dropNotAllowed, so it should be
     * overridden to provide the proper feedback.
     * 
     * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
     * {@link #getTargetFromEvent} for this node)
     * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
     * @param {Event} e The event
     * @param {Object} data An object containing arbitrary data supplied by the drag source
     * @return {String} status The CSS class that communicates the drop status back to the source so that the
     * underlying {@link Ext.dd.StatusProxy} can be updated
     */
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
     * Called when the DropZone determines that a {@link Ext.dd.DragSource} has been dropped onto
     * the drop node.  The default implementation returns false, so it should be overridden to provide the
     * appropriate processing of the drop event and return true so that the drag source's repair action does not run.
     * @param {Object} nodeData The custom data associated with the drop node (this is the same value returned from
     * {@link #getTargetFromEvent} for this node)
     * @param {Ext.dd.DragSource} source The drag source that was dragged over this drop zone
     * @param {Event} e The event
     * @param {Object} data An object containing arbitrary data supplied by the drag source
     * @return {Boolean} True if the drop was valid, else false
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
    },
    
    /**
     * Called before a repair of an invalid drop to get the XY to animate to. By default returns
     * the XY of this.dragData.ddel
     * @param {EventObject} e The mouse up event
     * @return {Array} The xy location (e.g. [100, 200])
     */
    getRepairXY: function() {
        return this.dragData.repairXY;
    },
    
    /**
     * Returns a custom data object associated with the DOM node that is the target of the event.  By default
     * this looks up the event target in the {@link Ext.dd.Registry}, although you can override this method to
     * provide your own custom lookup.
     * @param {Event} e The event
     * @return {Object} data The custom data
     */
    getTargetFromEvent: function(e) {
        return e.getTarget('ul[class^=x-tab]', 10);
    }
};