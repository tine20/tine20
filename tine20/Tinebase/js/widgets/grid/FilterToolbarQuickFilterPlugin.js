/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterToolbarQuickFilterPlugin
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Tine.widgets.grid.FilterToolbarQuickFilterPlugin = function(config) {
    //this.quickFilter = new Ext.ux.SearchField({});
};

Tine.widgets.grid.FilterToolbarQuickFilterPlugin.prototype = {
    /**
     * @cfg {String} quickFilterField
     * 
     * name of quickfilter filed in filter definitions
     */
    quickFilterField: 'query',
    
    /**
     * filter toolbar we are plugin of
     * 
     * @type {Tine.widgets.grid.FilterToolbar} ftb
     */
    ftb: null,
    
    /**
     * external quick filter field
     * 
     * @type {Ext.ux.searchField} quickFilter
     */
    quickFilter: null,
    
    /**
     * filter row of filter toolbar where a quickfilter is set
     * 
     * @type {Ext.data.record}
     */
    quickFilterRow: null,
    
    /**
     * bind value field of this.quickFilterRow to this.quickFilter
     */
    bind: function() {
        this.quickFilter.on('keyup', this.syncField, this);
        this.quickFilterRow.formFields.value.on('keyup', this.syncField, this);
        this.quickFilter.on('change', this.syncField, this);
        this.quickFilterRow.formFields.value.on('change', this.syncField, this);
    },
    
    getQuickFilterField: function() {
        this.quickFilter = new Ext.ux.SearchField({
            enableKeyEvents: true
        });
        
        this.quickFilter.onTrigger1Click = this.quickFilter.onTrigger1Click.createSequence(this.onQuickFilterClear, this);
        this.quickFilter.onTrigger2Click = this.quickFilter.onTrigger2Click.createSequence(this.onQuickFilterTrigger, this);
        return this.quickFilter;
    },
    
    init: function(ftb) {
        this.ftb = ftb;
        this.ftb.renderFilterRow = this.ftb.renderFilterRow.createSequence(this.onAddFilter, this);
        this.ftb.onFieldChange   = this.ftb.onFieldChange.createSequence(this.onFilterChange, this);
        this.ftb.deleteFilter    = this.ftb.deleteFilter.createInterceptor(this.onBeforeDeleteFilter, this);
        
        this.ftb.onFilterRowsChange = this.ftb.onFilterRowsChange.createInterceptor(this.onFilterRowsChange, this);
        this.ftb.getQuickFilterField = this.getQuickFilterField.createDelegate(this);
        
    },
    
    
    onAddFilter: function(filter) {
        if (filter.get('field') == this.quickFilterField && ! this.quickFilterRow) {
            this.quickFilterRow = filter;
            this.bind();
            
            // preset quickFilter with filterrow value
            this.syncField(filter.formFields.value);
            
            //console.log('quickfilter add')
            //console.log(filter);
        }
        
    },
    
    /**
     * called when a filter field of the filtertoolbar changes
     */
    onFilterChange: function(filter, newField) {
        if (filter == this.quickFilterRow) {
            this.onBeforeDeleteFilter(filter);
        }
        
        if (newField == this.quickFilterField) {
            this.onAddFilter(filter);
        }
    },
    
    /**
     * called when the filterrows of the filtertoolbar changes
     * 
     * we detect the hidestatus of the filtertoolbar
     */
    onFilterRowsChange: function() {
        this.ftb.searchButtonWrap.removeClass('x-btn-over');
        
        if (this.ftb.filterStore.getCount() <= 1 
            && this.ftb.filterStore.getAt(0).get('field') == this.quickFilterField
            && !this.ftb.filterStore.getAt(0).formFields.value.getValue()) {
                
            this.ftb.el.dom.style.display = 'none';
        } else {
            this.ftb.el.dom.style.display = '';
        }
    },
    
    onBeforeDeleteFilter: function(filter) {
        if (filter == this.quickFilterRow) {
            this.quickFilter.setValue('');
            this.unbind();
            delete this.quickFilterRow;
            
            // look for an other quickfilterrow
            this.ftb.filterStore.each(function(f) {
                if (f != filter && f.get('field') == this.quickFilterField ) {
                    this.onAddFilter(f);
                    return false;
                }
            }, this);
            
            //console.log('quickfilter remove')
            //console.log(filter);
        }
    },
    
    onQuickFilterClear: function() {
        this.ftb.deleteAllFilters.call(this.ftb);
    },
    
    onQuickFilterTrigger: function() {
        this.ftb.onFiltertrigger.call(this.ftb);
        this.ftb.onFilterRowsChange.call(this.ftb);
    },
    
    /**
     * syncs field contents of this.quickFilterRow and this.quickFilter
     * 
     * @param {Ext.EventObject} e
     * @param {Ext.form.Field} field
     */
    syncField: function(field) {
        if (field == this.quickFilter) {
            this.quickFilterRow.formFields.value.setValue(this.quickFilter.getValue());
        } else {
            this.quickFilter.setValue(this.quickFilterRow.formFields.value.getValue());
        }
    },
    
    /**
     * unbind value field of this.quickFilterRow from this.quickFilter
     */
    unbind: function() {
        this.quickFilter.un('keyup', this.syncField, this);
        this.quickFilterRow.formFields.value.un('keyup', this.syncField, this);
        this.quickFilter.un('change', this.syncField, this);
        this.quickFilterRow.formFields.value.un('change', this.syncField, this);
    }
};