/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Model of filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.MonthFilter
 * @extends     Tine.widgets.grid.FilterModel
 */

Tine.widgets.grid.MonthFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    valueType: 'date',
    defaultValue: 'monthLast',
    dateFilterSupportsPeriod: false,

    appName: null,
    label: null,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.MonthFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get(this.appName);
        this.label = this.label ? this.app.i18n._hidden(this.label) : i18n._("Month");
        
        this.operators = ['within', 'before', 'after', 'equals'];
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        filter.set('value', '');
        
        // for date filters we need to rerender the value section
        switch (newOperator) {
            case 'within':
                filter.numberfield.hide();
                filter.datePicker.hide();
                filter.textfield.hide();
                filter.withinCombo.show();
                filter.formFields.value = filter.withinCombo;
                break;
            case 'inweek':
                filter.withinCombo.hide();
                filter.datePicker.hide();
                filter.textfield.hide();
                filter.numberfield.show();
                filter.formFields.value = filter.numberfield;
                break;
            case 'equals':
                filter.withinCombo.hide();
                filter.datePicker.hide();
                filter.numberfield.hide();
                filter.textfield.show();
                filter.formFields.value = filter.textfield;
                break;
            default:
                filter.withinCombo.hide();
                filter.numberfield.hide();
                filter.textfield.hide();
                filter.datePicker.show();
                filter.formFields.value = filter.datePicker;
        }

        var width = filter.formFields.value.el.up('.tw-ftb-frow-value').getWidth() -10;
        if (filter.formFields.value.wrap) {
            filter.formFields.value.wrap.setWidth(width);
        }
        filter.formFields.value.setWidth(width);
    },
    
    /**
     * render a date value
     * 
     * we place a picker and a combo in the dom element and hide the one we don't need yet
     */
    dateValueRenderer: function(filter, el) {
        var operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator;
        
        if (! filter.textfield) {
            filter.textfield = new Ext.ux.form.ClearableTextField({
                filter: filter,
                hidden: true,
                renderTo: el,
                value: filter.data.value ? filter.data.value : '',
                emptyText: this.emptyText,
                listeners: {
                    scope: this,
                    change: function() { this.onFiltertrigger() },
                    specialkey: function(field, e){
                        if(e.getKey() == e.ENTER){
                            this.onFiltertrigger();
                        }
                    }
                }
            });
        }
        
        if (operator != 'equals') {
            return Tine.widgets.grid.MonthFilter.superclass.dateValueRenderer.call(this, filter, el);
        }
        
        return filter.textfield;
    },
    /**
     * returns past operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDatePastOps: function() {
        return [
            ['monthThis',       i18n._('this month')],
            ['monthLast',       i18n._('last month')],
            ['quarterThis',     i18n._('this quarter')],
            ['quarterLast',     i18n._('last quarter')],
            ['yearThis',        i18n._('this year')],
            ['yearLast',        i18n._('last year')]
        ];
    },
    
    /**
     * returns future operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDateFutureOps: function() {
        return [
        ];
    }
});