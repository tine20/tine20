/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets.grid');

/**
 * a row selection model capable to return filters
 * @constructor
 * @class Tine.Tinebase.widgets.grid.FilterSelectionModel
 * @extends Ext.grid.RowSelectionModel
 */
Tine.Tinebase.widgets.grid.FilterSelectionModel = Ext.extend(Ext.grid.RowSelectionModel, {
    /**
     * @cfg {Ext.data.Store}
     */
    store: null,
    
    /**
     * @property {Bool}
     */
    isFilterSelect: false,
    
    /**
     * Returns filterData representing current selection
     * 
     * @return {Array} filterData
     */
    getSelectionFilter: function() {
        if(! this.isFilterSelect) {
            return this.getFilterOfRowSelection();
        } else {
            /* cruide hack lets save it as comment, maybe we need it some time ;-)
            var opts = {}
            for (var i=0; i<this.store.events.beforeload.listeners.length; i++) {
                if (this.store.events.beforeload.listeners[i].scope.xtype == 'filterplugin') {
                    this.store.events.beforeload.listeners[i].fireFn.call(this.store.events.beforeload.listeners[i].scope, this.store, opts);
                }
            }
            //console.log(opts.params.filter);
            return opts.params.filter;
            */
            
            return this.store.lastFilter;
        }
    },
    
    /**
     * selects all rows 
     * @param {Bool} onlyPage select only rows from current page
     * @return {Void}
     */
    selectAll: function(onlyPage) {
        Tine.Tinebase.widgets.grid.FilterSelectionModel.superclass.selectAll.call(this);
        
        if (! onlyPage) {
            this.isFilterSelect = true;
        }
    },
    
    deselectAll: function() {
        
    },
    
    toggleSelection: function() {
        
    },
    
    /**
     * Returns number of selected rows
     * 
     * @return {Number}
     */
    getCount: function() {
        if(! this.isFilterSelect) {
            return Tine.Tinebase.widgets.grid.FilterSelectionModel.superclass.getCount.call(this);
        } else {
            return this.store.getCount();
        }
    },
   
    /**
     * converts a 'normal' row selection into a filter
     * 
     * @private
     * @return {Array} filterData
     */
    getFilterOfRowSelection: function() {
        
    }
});