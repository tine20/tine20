/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.ComponentMgr
 * <p>Provides a common registry of all components (specifically subclasses of {@link Ext.Component}) on a page so
 * that they can be easily accessed by component id (see {@link Ext.getCmp}).</p>
 * <p>Every component class also gets registered in ComponentMgr by its 'xtype' property, which is its Ext-specific
 * type name (e.g., Ext.form.TextField's xtype is 'textfield'). This allows you to check the xtype of specific
 * object instances (see {@link Ext.Component#getXType} and {@link Ext.Component#isXType}). For a list of all
 * available xtypes, see {@link Ext.Component}.</p>
 * @singleton
 */
Ext.ComponentMgr = function(){
    var all = new Ext.util.MixedCollection();
    var types = {};

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
        },

        /**
         * The MixedCollection used internally for the component cache. An example usage may be subscribing to
         * events on the MixedCollection to monitor addition or removal.  Read-only.
         * @type {MixedCollection}
         */
        all : all,

        // private
        registerType : function(xtype, cls){
            types[xtype] = cls;
            cls.xtype = xtype;
        },

        // private
        create : function(config, defaultType){
            return new types[config.xtype || defaultType](config);
        }
    };
}();

// this will be called a lot internally,
// shorthand to keep the bytes down
Ext.reg = Ext.ComponentMgr.registerType;