/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         fix problems with onFilterTrigger (selected tag does not disappear, trigger click does not search again)
 * TODO         code cleanup
 */

Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagFilter
 * @extends     Tine.widgets.grid.PickerFilter
 */
Tine.widgets.tags.TagFilter = Ext.extend(Tine.widgets.grid.PickerFilter, {
    
    field: 'tag',
    defaultOperator: 'equals',
    
    /**
     * @private
     */
    initComponent: function() {
        this.picker = Tine.widgets.tags.TagCombo;
        this.recordClass = Tine.Tinebase.Model.Tag;
        
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.label = _('Tag');
    }
    
    /**
     * get record picker
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     * 
     */
//    getPicker: function(filter, el) {
//        Tine.log.debug('Tine.widgets.tags.TagFilter::getPicker()');
//        
//        var result = new Tine.widgets.tags.TagCombo({
//            app: this.app,
//            filter: filter,
//            width: 200,
//            id: 'tw-ftb-frow-valuefield-' + filter.id,
//            value: filter.data.value ? filter.data.value : this.defaultValue,
//            renderTo: el
//        });
//        
//        return result;
//    }
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
//    valueRenderer: function(filter, el) {
//        // value
//        var value = new Tine.widgets.tags.TagCombo({
//            app: this.app,
//            filter: filter,
//            width: 200,
//            id: 'tw-ftb-frow-valuefield-' + filter.id,
//            value: filter.data.value ? filter.data.value : this.defaultValue,
//            renderTo: el
//        });
//        value.on('specialkey', function(field, e){
//             if(e.getKey() == e.ENTER){
//                 this.onFiltertrigger();
//             }
//        }, this);
//        // need to trigger filter on select because we can have the same names for (shared/personal) tags
//        //  and when filters are triggered, the first matching record in the TagCombo gets selected ... :(
//        value.on('select', this.onFiltertrigger, this);
//        
//        return value;
//    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.tag'] = Tine.widgets.tags.TagFilter;

