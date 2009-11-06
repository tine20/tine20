/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * @class Tine.widgets.grid.FilterButton
 * @extends Ext.Button
 * <p>Toggle Button to be used as filter</p>
 * @constructor
 */
Tine.widgets.grid.FilterButton = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Tine.widgets.grid.FilterButton.superclass.constructor.call(this);
    Ext.applyIf(this, new Tine.widgets.grid.FilterPlugin());
};

Ext.extend(Tine.widgets.grid.FilterButton, Ext.Button, {
    /**
     * @cfg {String} field the filed to filter
     */
    field: null,
    /**
     * @cfg {String} operator operator of filter (defaults 'equals')
     */
    operator: 'equals',
    /**
     * @cfg {Bool} invert true if loging should be inverted (defaults false)
     */
    invert: false,
    
    /**
     * @private only toggle actions make sense as filters!
     */
    enableToggle: true,
    
    /**
     * @private
     */
    getValue: function() {
        return {field: this.field, operator: this.operator, value: this.invert ? !this.pressed : this.pressed};
    },
    
    /**
     * @private
     */
    setValue: function(filters) {
        for (var i=0; i<filters.length; i++) {
            if (filters[i].field == this.field) {
                this.toggle(this.invert ? !filters[i].value : !!filters[i].value);
                break;
            }
        }
    },
    
    /**
     * @private
     */
    handler: function() {
        this.onFilterChange();
    }
});