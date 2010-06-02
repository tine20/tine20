/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.grid');

/**
 * a row selection model capable to return filters
 * @constructor
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterSelectionModel
 * @extends     Ext.grid.RowSelectionModel
 */
Tine.widgets.grid.FilterSelectionModel = Ext.extend(Ext.grid.RowSelectionModel, {
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
            var filterData = this.getAllFilterData();
            return filterData;
        }
    },
    
    /**
     * gets filter data of all filter plugins
     * 
     * NOTE: As we can't find all filter plugins directly we need a litte hack 
     *       to get their data
     *       
     *       We register ourselve as latest beforeload.
     *       In the options.filter we have the filters then.
     */
    getAllFilterData: function() {
        this.store.on('beforeload', this.storeOnBeforeload, this);
        this.store.load();
        this.store.un('beforeload', this.storeOnBeforeload, this);
        
        return this.allFilterData;
    },
    
    storeOnBeforeload: function(store, options) {
        this.allFilterData = options.params.filter;
        this.store.fireEvent('exception');
        return false;
    },
    
    /**
     * selects all rows 
     * @param {Bool} onlyPage select only rows from current page
     * @return {Void}
     */
    selectAll: function(onlyPage) {
        this.isFilterSelect = !onlyPage;
        
        Tine.widgets.grid.FilterSelectionModel.superclass.selectAll.call(this);
    },
    
    /**
     * @private
     */
    onRefresh : function(){
        this.clearSelections(true);
        Tine.widgets.grid.FilterSelectionModel.superclass.onRefresh.call(this);
    },
    
    /**
     * @private
     */
    onRemove : function(v, index, r){
        this.clearSelections(true);
        Tine.widgets.grid.FilterSelectionModel.superclass.onRemove.call(this, v, index, r);
    },
    
    /**
     * Deselects a row.
     * @param {Number} row The index of the row to deselect
     */
    deselectRow : function(index, preventViewNotify) {
        this.isFilterSelect = false;
        Tine.widgets.grid.FilterSelectionModel.superclass.deselectRow.call(this, index, preventViewNotify);
    },
    
    /**
     * Clears all selections.
     */
    clearSelections: function(silent) {
        this.suspendEvents();
        this.isFilterSelect = false;
        
        Tine.widgets.grid.FilterSelectionModel.superclass.clearSelections.call(this);
        
        this.resumeEvents();
        if (! silent) {
            this.fireEvent('selectionchange', this);
        }
    },
    
    /**
     * toggle selection
     */
    toggleSelection: function() {
        if (this.isFilterSelect) {
            this.clearSelections();
        } else {
            this.suspendEvents();
            
            var index;
            this.store.each(function(record) {
                index = this.store.indexOf(record);
                if (this.isSelected(index)) {
                    this.deselectRow(index);
                } else {
                    this.selectRow(index, true);
                }
            }, this);
            
            this.resumeEvents();
            this.fireEvent('selectionchange', this);
        }
    },
    
    /**
     * Returns number of selected rows
     * 
     * @return {Number}
     */
    getCount: function() {
        if(! this.isFilterSelect) {
            return Tine.widgets.grid.FilterSelectionModel.superclass.getCount.call(this);
        } else {
            return this.store.getTotalCount();
        }
    },
   
    /**
     * converts a 'normal' row selection into a filter
     * 
     * @private
     * @return {Array} filterData
     */
    getFilterOfRowSelection: function() {
        //var idProperty = this.store.fields.getMeta('idProperty');
        var idProperty = this.store.reader.meta.id;
        var ids = [];
        this.each(function(record) {
            ids.push(record.id);
        });
        return [{field: idProperty, operator: 'in', value: ids}];
    }
});