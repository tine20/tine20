/*
 * Ext JS Library 1.1 RC 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://www.extjs.com/license
 */

/**
 * @class Ext.ComponentMgr
 * Provides a common registry of all components on a page so that they can be easily accessed by component id.
 * @singleton
 */
Ext.ComponentMgr = function(){
    var all = new Ext.util.MixedCollection();

    return {
        /**
         * Registers a component.
         * @param {Ext.Component} c The component
         */
        register : function(c){
            all.add(c);
        },

        /**
         * Unregisters a component.
         * @param {Ext.Component} c The component
         */
        unregister : function(c){
            all.remove(c);
        },

        /**
         * Returns a component by id
         * @param {String} id The component id
         */
        get : function(id){
            return all.get(id);
        },

        /**
         * Registers a function that will be called when a specified component is added to ComponentMgr
         * @param {String} id The component id
         * @param {Funtction} fn The callback function
         * @param {Object} scope The scope of the callback
         */
        onAvailable : function(id, fn, scope){
            all.on("add", function(index, o){
                if(o.id == id){
                    fn.call(scope || o, o);
                    all.un("add", fn, scope);
                }
            });
        }
    };
}();

/**
 * @class Ext.Component
 * @extends Ext.util.Observable
 * Base class for all Ext form controls that provides a common set of events and functionality shared by all components.
 * @constructor
 * @param {Ext.Element/String/Object} config The configuration options.  If an element is passed, it is set as the internal
 * element and its id used as the component id.  If a string is passed, it is assumed to be the id of an existing element
 * and is used as the component id.  Otherwise, it is assumed to be a standard config object and is applied to the component.
 */
Ext.Component = function(config){
    config = config || {};
    if(config.tagName || config.dom || typeof config == "string"){ // element object
        config = {el: config, id: config.id || config};
    }
    this.initialConfig = config;

    Ext.apply(this, config);
    this.addEvents({
        /**
         * @event disable
         * Fires after the component is disabled.
	     * @param {Ext.Component} this
	     */
        disable : true,
        /**
         * @event enable
         * Fires after the component is enabled.
	     * @param {Ext.Component} this
	     */
        enable : true,
        /**
         * @event beforeshow
         * Fires before the component is shown.  Return false to stop the show.
	     * @param {Ext.Component} this
	     */
        beforeshow : true,
        /**
         * @event show
         * Fires after the component is shown.
	     * @param {Ext.Component} this
	     */
        show : true,
        /**
         * @event beforehide
         * Fires before the component is hidden. Return false to stop the hide.
	     * @param {Ext.Component} this
	     */
        beforehide : true,
        /**
         * @event hide
         * Fires after the component is hidden.
	     * @param {Ext.Component} this
	     */
        hide : true,
        /**
         * @event beforerender
         * Fires before the component is rendered. Return false to stop the render.
	     * @param {Ext.Component} this
	     */
        beforerender : true,
        /**
         * @event render
         * Fires after the component is rendered.
	     * @param {Ext.Component} this
	     */
        render : true,
        /**
         * @event beforedestroy
         * Fires before the component is destroyed. Return false to stop the destroy.
	     * @param {Ext.Component} this
	     */
        beforedestroy : true,
        /**
         * @event destroy
         * Fires after the component is destroyed.
	     * @param {Ext.Component} this
	     */
        destroy : true
    });
    if(!this.id){
        this.id = "ext-comp-" + (++Ext.Component.AUTO_ID);
    }
    Ext.ComponentMgr.register(this);
    Ext.Component.superclass.constructor.call(this);
    this.initComponent();
    if(this.renderTo){ // not supported by all components yet. use at your own risk!
        this.render(this.renderTo);
        delete this.renderTo;
    }
};

// private
Ext.Component.AUTO_ID = 1000;

Ext.extend(Ext.Component, Ext.util.Observable, {
    /**
     * true if this component is hidden. Read-only.
     */
    hidden : false,
    /**
     * true if this component is disabled. Read-only.
     */
    disabled : false,
    /**
     * CSS class added to the component when it is disabled.
     */
    disabledClass : "x-item-disabled",
    /**
     * true if this component has been rendered. Read-only.
     */
    rendered : false,

    allowDomMove: true,

    // private
    ctype : "Ext.Component",

    // private
    actionMode : "el",

    // private
    getActionEl : function(){
        return this[this.actionMode];
    },

    /** @cfg {String} hideMode
     * How this component should hidden. Supported values are
     * "visibility" (css visibility), "offsets" (negative offset position) and
     * "display" (css display) - defaults to "display".
     */
    hideMode: 'display',

    initComponent : Ext.emptyFn,
    /**
     * If this is a lazy rendering component, render it to its container element.
     * @param {String/HTMLElement/Element} container (optional) The element this component should be rendered into. If it is being applied to existing markup, this should be left off.
     */
    render : function(container, position){
        if(!this.rendered && this.fireEvent("beforerender", this) !== false){
            if(!container && this.el){
                this.el = Ext.get(this.el);
                container = this.el.dom.parentNode;
                this.allowDomMove = false;
            }
            this.container = Ext.get(container);
            this.rendered = true;
            if(position !== undefined){
                if(typeof position == 'number'){
                    position = this.container.dom.childNodes[position];
                }else{
                    position = Ext.getDom(position);
                }
            }
            this.onRender(this.container, position || null);
            if(this.cls){
                this.el.addClass(this.cls);
                delete this.cls;
            }
            if(this.style){
                this.el.applyStyles(this.style);
                delete this.style;
            }
            this.fireEvent("render", this);
            this.afterRender(this.container);
            if(this.hidden){
                this.hide();
            }
            if(this.disabled){
                this.disable();
            }
        }
        return this;
    },

    // private
    // default function is not really useful
    onRender : function(ct, position){
        if(this.el){
            this.el = Ext.get(this.el);
            if(this.allowDomMove !== false){
                ct.dom.insertBefore(this.el.dom, position);
            }
        }
    },

    // private
    getAutoCreate : function(){
        var cfg = typeof this.autoCreate == "object" ?
                      this.autoCreate : Ext.apply({}, this.defaultAutoCreate);
        if(this.id && !cfg.id){
            cfg.id = this.id;
        }
        return cfg;
    },

    // private
    afterRender : Ext.emptyFn,

    // private
    destroy : function(){
        if(this.fireEvent("beforedestroy", this) !== false){
            this.purgeListeners();
            this.beforeDestroy();
            if(this.rendered){
                this.el.removeAllListeners();
                this.el.remove();
                if(this.actionMode == "container"){
                    this.container.remove();
                }
            }
            this.onDestroy();
            Ext.ComponentMgr.unregister(this);
            this.fireEvent("destroy", this);
        }
    },

	// private
    beforeDestroy : function(){

    },

	// private
	onDestroy : function(){

    },

    /**
     * Returns the underlying {@link Ext.Element}.
     * @return {Ext.Element} The element
     */
    getEl : function(){
        return this.el;
    },

    /**
     * Returns the id of this component.
     * @return {String}
     */
    getId : function(){
        return this.id;
    },

    /**
     * Try to focus this component.
     * @param {Boolean} selectText True to also select the text in this component (if applicable)
     * @return {Ext.Component} this
     */
    focus : function(selectText){
        if(this.rendered){
            this.el.focus();
            if(selectText === true){
                this.el.dom.select();
            }
        }
        return this;
    },

    // private
    blur : function(){
        if(this.rendered){
            this.el.blur();
        }
        return this;
    },

    /**
     * Disable this component.
     * @return {Ext.Component} this
     */
    disable : function(){
        if(this.rendered){
            this.onDisable();
        }
        this.disabled = true;
        this.fireEvent("disable", this);
        return this;
    },

	// private
    onDisable : function(){
        this.getActionEl().addClass(this.disabledClass);
        this.el.dom.disabled = true;
    },

    /**
     * Enable this component.
     * @return {Ext.Component} this
     */
    enable : function(){
        if(this.rendered){
            this.onEnable();
        }
        this.disabled = false;
        this.fireEvent("enable", this);
        return this;
    },

	// private
    onEnable : function(){
        this.getActionEl().removeClass(this.disabledClass);
        this.el.dom.disabled = false;
    },

    /**
     * Convenience function for setting disabled/enabled by boolean.
     * @param {Boolean} disabled
     */
    setDisabled : function(disabled){
        this[disabled ? "disable" : "enable"]();
    },

    /**
     * Show this component.
     * @return {Ext.Component} this
     */
    show: function(){
        if(this.fireEvent("beforeshow", this) !== false){
            this.hidden = false;
            if(this.rendered){
                this.onShow();
            }
            this.fireEvent("show", this);
        }
        return this;
    },

    // private
    onShow : function(){
        var ae = this.getActionEl();
        if(this.hideMode == 'visibility'){
            ae.dom.style.visibility = "visible";
        }else if(this.hideMode == 'offsets'){
            ae.removeClass('x-hidden');
        }else{
            ae.dom.style.display = "";
        }
    },

    /**
     * Hide this component.
     * @return {Ext.Component} this
     */
    hide: function(){
        if(this.fireEvent("beforehide", this) !== false){
            this.hidden = true;
            if(this.rendered){
                this.onHide();
            }
            this.fireEvent("hide", this);
        }
        return this;
    },

    // private
    onHide : function(){
        var ae = this.getActionEl();
        if(this.hideMode == 'visibility'){
            ae.dom.style.visibility = "hidden";
        }else if(this.hideMode == 'offsets'){
            ae.addClass('x-hidden');
        }else{
            ae.dom.style.display = "none";
        }
    },

    /**
     * Convenience function to hide or show this component by boolean.
     * @param {Boolean} visible True to show, false to hide
     * @return {Ext.Component} this
     */
    setVisible: function(visible){
        if(visible) {
            this.show();
        }else{
            this.hide();
        }
        return this;
    },

    /**
     * Returns true if this component is visible.
     */
    isVisible : function(){
        return this.getActionEl().isVisible();
    },

    cloneConfig : function(overrides){
        overrides = overrides || {};
        var id = overrides.id || Ext.id();
        var cfg = Ext.applyIf(overrides, this.initialConfig);
        cfg.id = id; // prevent dup id
        return new this.__extcls(cfg);
    }
});