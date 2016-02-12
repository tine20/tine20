/**
 * @class GetIt.GridPrinter
 * @author Ed Spencer (edward@domine.co.uk)
 * Class providing a common way of printing Ext.Components. Ext.ux.Printer.print delegates the printing to a specialised
 * renderer class (each of which subclasses Ext.ux.Printer.BaseRenderer), based on the xtype of the component.
 * Each renderer is registered with an xtype, and is used if the component to print has that xtype.
 * 
 * See the files in the renderers directory to customise or to provide your own renderers.
 * 
 * Usage example:
 * 
 * var grid = new Ext.grid.GridPanel({
 *   colModel: //some column model,
 *   store   : //some store
 * });
 * 
 * Ext.ux.Printer.print(grid);
 * 
 */
Ext.ux.Printer = function() {
  
  return {
    /**
     * @property renderers
     * @type Object
     * An object in the form {xtype: RendererClass} which is manages the renderers registered by xtype
     */
    renderers: {},
    
    /**
     * Registers a renderer function to handle components of a given xtype
     * @param {String} xtype The component xtype the renderer will handle
     * @param {Function} renderer The renderer to invoke for components of this xtype
     */
    registerRenderer: function(xtype, renderer) {
      this.renderers[xtype] = new (renderer)();
    },
    
    /**
     * Returns the registered renderer for a given xtype
     * @param {String} xtype The component xtype to find a renderer for
     * @return {Object/undefined} The renderer instance for this xtype, or null if not found
     */
    getRenderer: function(xtype) {
      return this.renderers[xtype];
    },

    findRenderer: function(component) {
        var xtypes = component.getXTypes().split('/'),
            renderer = undefined;

        //iterate backwards over the xtypes of this component, dispatching to the most specific renderer
        for (var i = xtypes.length - 1; i >= 0; i--){
            var xtype    = xtypes[i],
                renderer = this.getRenderer(xtype);

            if (renderer != undefined) {
                break;
            }
        }

        return renderer;
    },

    /**
     * Prints the passed grid. Reflects on the grid's column model to build a table, and fills it using the store
     * @param {Ext.Component} component The component to print
     */
    print: function(component) {
        var renderer = this.findRenderer(component);

        if (renderer != undefined) {
          renderer.print(component);
        }
    }
  };
}();

/**
 * Override how getXTypes works so that it doesn't require that every single class has
 * an xtype registered for it.
 */
Ext.override(Ext.Component, {
  getXTypes : function(){
      var tc = this.constructor;
      if(!tc.xtypes){
          var c = [], sc = this;
          while(sc){ //was: while(sc && sc.constructor.xtype) {
            var xtype = sc.constructor.xtype;
            if (xtype != undefined) c.unshift(xtype);
            
            sc = sc.constructor.superclass;
          }
          tc.xtypeChain = c;
          tc.xtypes = c.join('/');
      }
      return tc.xtypes;
  }
});
