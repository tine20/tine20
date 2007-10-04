/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.Container
 * @extends Ext.BoxComponent
 * Base class for any {@link Ext.BoxComponent} that can contain other components.  Containers handle the basic
 * behavior of containing items, namely adding, inserting and removing them.  The specific layout logic required
 * to visually render contained items is delegated to any one of the different {@link #layout} classes available.
 * This class is intended to be extended and should generally not need to be created directly via the new keyword.
 */
Ext.Container = Ext.extend(Ext.BoxComponent, {
    /** @cfg {Boolean} monitorResize
     * True to automatically monitor window resize events to handle anything that is sensitive to the current size
     * of the viewport.  This value is typically managed by the chosen {@link #layout} and should not need to be set manually.
     */
    /**
     * @cfg {String} layout
     * The layout type to be used in this container.  If not specified, a default {@link Ext.layout.ContainerLayout}
     * will be created and used.  Valid values are: accordion, anchor, border, card, column, fit, form and table.
     * Specific config values for the chosen layout type can be specified using {@link #layoutConfig}.
     */
    /**
     * @cfg {Object} layoutConfig
     * This is a config object containing properties specific to the chosen layout (to be used in conjunction with
     * the {@link #layout} config value).  For complete details regarding the valid config options for each layout
     * type, see the layout class corresponding to the type specified:
     * {@link Ext.layout.Accordion}, {@link Ext.layout.AnchorLayout}, {@link Ext.layout.BorderLayout},
     * {@link Ext.layout.CardLayout}, {@link Ext.layout.ColumnLayout}, {@link Ext.layout.FitLayout},
     * {@link Ext.layout.FormLayout} and {@link Ext.layout.TableLayout}.
     */
    /**
     * @cfg {String/Number} activeItem
     * A string component id or the numeric index of the component that should be initially activated within the
     * container's layout on render.  For example, activeItem: 'item-1' or activeItem: 0 (index 0 = the first
     * item in the container's collection).  activeItem only applies to layout styles that can display
     * items one at a time (like {@link Ext.layout.Accordion}, {@link Ext.layout.CardLayout} and
     * {@link Ext.layout.FitLayout}).  Related to {@link Ext.layout.ContainerLayout#activeItem}.
     */
    /**
     * @cfg {Mixed} items
     * A single item, or an array of items to be added to this container.  Each item can be any type of object
     * based on {@link Ext.Component}, or a valid config object for such an item.  If a single item is being
     * passed, it should be passed directly as an object reference (e.g., items: {...}).  Multiple items should be
     * passed as an array of objects (e.g., items: [{...}, {...}]).
     */
    /**
     * @cfg {Object} defaults
     * A config object that will be applied to all components added to this container either via the {@link #items}
     * config or via the {@link #add} or {@link #insert} methods.  The defaults config can contain any number of
     * name/value property pairs to be added to each item, and should be valid for the types of items
     * being added to the container.  For example, to automatically apply padding to the body of each of a set of
     * contained {@link Ext.Panel} items, you could pass: defaults: {bodyStyle:'padding:15px'}.
     */

    /** @cfg {Boolean} autoDestroy
     * If true the container will automatically destroy any contained component that is removed from it, else
     * destruction must be handled manually (defaults to true).
     */
    autoDestroy: true,
    /** @cfg {Boolean} hideBorders
     * True to hide the borders of each contained component, false to defer to the component's existing
     * border settings (defaults to false).
     */
    /** @cfg {String} defaultType
     * The default type of container represented by this object as registered in {@link Ext.ComponentMgr}
     * (defaults to 'panel').
     */
    defaultType: 'panel',

    // private
    initComponent : function(){
        Ext.Container.superclass.initComponent.call(this);

        this.addEvents({
            /**
             * @event beforeadd
             * Fires before any {@link Ext.Component} is added or inserted into the container.
             * A handler can return false to cancel the add.
             * @param {Ext.Container} this
             * @param {Ext.Component} component The component being added
             * @param {Number} index The index at which the component will be added to the container's items collection
             */
            'beforeadd':true,
            /**
             * @event beforeremove
             * Fires before any {@link Ext.Component} is removed from the container.  A handler can return
             * false to cancel the remove.
             * @param {Ext.Container} this
             * @param {Ext.Component} component The component being removed
             */
            'beforeremove':true,
            /**
             * @event add
             * Fires after any {@link Ext.Component} is added or inserted into the container.
             * @param {Ext.Container} this
             * @param {Ext.Component} component The component that was added
             * @param {Number} index The index at which the component was added to the container's items collection
             */
            'add':true,
            /**
             * @event remove
             * Fires after any {@link Ext.Component} is removed from the container.
             * @param {Ext.Container} this
             * @param {Ext.Component} component The component that was removed
             */
            'remove':true
        });

        var items = this.items;
        if(items){
            delete this.items;
            if(items instanceof Array){
                this.add.apply(this, items);
            }else{
                this.add(items);
            }
        }
    },

    // private
    initItems : function(){
        if(!this.items){
            this.items = new Ext.util.MixedCollection(false, this.getComponentId);
            this.getLayout(); // initialize the layout
        }
    },

    // private
    setLayout : function(layout){
        if(this.layout && this.layout != layout){
            this.layout.setContainer(null);
        }
        this.initItems();
        this.layout = layout;
        layout.setContainer(this);
    },

    // private
    render : function(){
        Ext.Container.superclass.render.apply(this, arguments);
        if(this.layout){
            if(typeof this.layout == 'string'){
                this.layout = new Ext.Container.LAYOUTS[this.layout.toLowerCase()](this.layoutConfig);
            }
            this.setLayout(this.layout);

            if(this.activeItem !== undefined){
                var item = this.activeItem;
                delete this.activeItem;
                this.layout.setActiveItem(item);
                return;
            }
        }
        this.doLayout();
        if(this.monitorResize === true){
            Ext.EventManager.onWindowResize(this.doLayout, this);
        }
    },

    // protected - should only be called by layouts
    getLayoutTarget : function(){
        return this.el;
    },

    // private - used as the key lookup function for the items collection
    getComponentId : function(comp){
        return comp.itemId || comp.id;
    },

    /**
     * Adds a component to this container.  Fires the beforeadd event before adding, then fires the add event
     * after the component has been added.
     * @param {Ext.Component} component The component to add
     * @return {Ext.Component} component The component that was added (with the container's default config values applied)
     */
    add : function(comp){
        if(!this.items){
            this.initItems();
        }
        var a = arguments, len = a.length;
        if(len > 1){
            for(var i = 0; i < len; i++) {
                this.add(a[i]);
            }
            return;
        }
        var c = this.lookupComponent(this.applyDefaults(comp));
        var pos = this.items.length;
        if(this.fireEvent('beforeadd', this, c, pos) !== false && this.onBeforeAdd(c) !== false){
            this.items.add(c);
            c.ownerCt = this;
            this.fireEvent('add', this, c, pos);
        }
        return c;
    },

    /**
     * Inserts a component into this container at a specified index.  Fires the beforeadd event before inserting,
     * then fires the add event after the component has been inserted.
     * @param {Number} index The index at which the component will be inserted into the container's items collection
     * @param {Ext.Component} component The component to add
     * @return {Ext.Component} component The component that was inserted (with the container's default config values applied)
     */
    insert : function(index, comp){
        if(!this.items){
            this.initItems();
        }
        var a = arguments, len = a.length;
        if(len > 2){
            for(var i = len-1; i >= 1; --i) {
                this.insert(index, a[i]);
            }
            return;
        }
        var c = this.lookupComponent(this.applyDefaults(comp));
        if(this.fireEvent('beforeadd', this, c, index) !== false && this.onBeforeAdd(c) !== false){
            this.items.insert(index, c);
            c.ownerCt = this;
            this.fireEvent('add', this, c, index);
        }
        return c;
    },

    // private
    applyDefaults : function(c){
        if(this.defaults){
            if(typeof c == 'string'){
                c = Ext.ComponentMgr.get(c);
                Ext.apply(c, this.defaults);
            }else if(!c.events){
                Ext.applyIf(c, this.defaults);
            }else{
                Ext.apply(c, this.defaults);
            }
        }
        return c;
    },

    // private
    onBeforeAdd : function(item){
        if(item.ownerCt){
            item.ownerCt.remove(item, false);
        }
    },

    /**
     * Removes a component from this container.  Fires the beforeremove event before removing, then fires
     * the remove event after the component has been removed.
     * @param {Component/String} component The component reference or id to remove
     * @param {Boolean} autoDestroy True to automatically invoke the component's {@link Ext.Component#destroy} function
     */
    remove : function(comp, autoDestroy){
        var c = this.getComponent(comp);
        if(c && this.fireEvent('beforeremove', this, c) !== false){
            this.items.remove(c);
            if(autoDestroy === true || (autoDestroy !== false && this.autoDestroy)){
                c.destroy();
            }
            if(this.layout && this.layout.activeItem == c){
                delete this.layout.activeItem;
            }
            this.fireEvent('remove', this, c);
        }
        return c;
    },

    // private
    getComponent : function(comp){
        if(typeof comp == 'object'){
            return comp;
        }
        return this.items.get(comp);
    },

    // private
    lookupComponent : function(comp){
        if(typeof comp == 'string'){
            return Ext.ComponentMgr.get(comp);
        }else if(!comp.events){
            return this.createComponent(comp);
        }
        if(this.hideBorders === true){
            comp.border = (comp.border === true);
        }
        return comp;
    },

    // private
    createComponent : function(config){
        return Ext.ComponentMgr.create(config, this.defaultType);
    },

    /**
     * Force this container's layout to be recalculated.  Generally this is handled automatically by the container,
     * and should be used with care as it can be intensive with complex layouts since all nested containers'
     * doLayout methods are also called recursively.
     */
    doLayout : function(){
        if(this.rendered && this.layout){
            this.layout.layout();
        }
        if(this.items){
            var cs = this.items.items;
            for(var i = 0, len = cs.length; i < len; i++) {
                var c  = cs[i];
                if(c.doLayout){
                    c.doLayout();
                }
            }
        }
    },

    /**
     * Returns the layout currently in use by the container.  If the container does not currently have a layout
     * set, a default {@link Ext.layout.ContainerLayout} will be created and set as the container's layout.
     * @return {ContainerLayout} layout The container's layout
     */
    getLayout : function(){
        if(!this.layout){
            var layout = new Ext.layout.ContainerLayout(this.layoutConfig);
            this.setLayout(layout);
        }
        return this.layout;
    },

    // private
    onDestroy : function(){
        if(this.items){
            var cs = this.items.items;
            for(var i = 0, len = cs.length; i < len; i++) {
                Ext.destroy(cs[i]);
            }
        }
        if(this.monitorResize){
            Ext.EventManager.removeResizeListener(this.doLayout, this);
        }
        Ext.Container.superclass.onDestroy.call(this);
    },

    // private - container bubbling
    bubble : function(fn, scope, args){
        var p = this;
        while(p){
            if(fn.call(scope || p, args || p) === false){
                break;
            }
            p = p.ownerCt;
        }
    },

    // private - item cascading
    cascade : function(fn, scope, args){
        if(fn.call(scope || this, args || this) !== false){
            if(this.items){
                var cs = this.items.items;
                for(var i = 0, len = cs.length; i < len; i++){
                    if(cs[i].cascade){
                        cs[i].cascade(fn, scope, args);
                    }
                }
            }
        }
    }
});

Ext.Container.LAYOUTS = {};