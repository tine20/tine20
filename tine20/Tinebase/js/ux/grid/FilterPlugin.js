/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.grid');

/**
 * @class Ext.ux.grid.FilterPlugin
 * @extends Ext.util.Observable
 * <p>Base class for all grid filter plugins.</p>
 * @constructor
 */
Ext.ux.grid.FilterPlugin = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    this.addEvents(
        /**
         * @event change
         * Fired when the filter changed.
         * @param {Ext.ux.grid.FilterPlugin} this
         */
        'change'
    );
    
    Ext.ux.grid.FilterPlugin.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.grid.FilterPlugin, Ext.util.Observable, {
    
    /**
     * @property {Ext.data.Store} store
     */
    store: null,
    
    /**
     * main method which must return the filter object of this filter
     * 
     * @return {Object}
     */
    getValue: Ext.emptyFn,
    
    /**
     * plugin method of Ext.grid.GridPanel
     * 
     * @oaran {Ext.grid.GridPanel} grid
     */
    init: function(grid) {
        this.store = grid.store;
        this.doBind();
    },
    
    /**
     * binds this plugin to the grid store
     */
    doBind: function() {
        this.store.on('beforeload', this.onBeforeLoad, this);
    },
    
    /**
     * fires our change event
     */
    onFilterChange: function() {
        this.store.load({});
        this.fireEvent('change', this);
    },
    
    /**
     * called before store loads
     */
    onBeforeLoad: function(store, options) {
        options = options || {};
        options.params = options.params || {};
        var filter = options.params.filter ? options.params.filter : [];
        
        //console.log(options);
        var value = this.getValue();
        //console.log(value);
        if (value && Ext.isArray(filter)) {
            filter.push(value);
        }
    }
});