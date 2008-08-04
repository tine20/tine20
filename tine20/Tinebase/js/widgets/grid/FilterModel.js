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
 * Model of filter
 * 
 * @constructor
 */
Tine.widgets.grid.FilterModel = function(config) {
    Ext.apply(this, config);
    Tine.widgets.grid.FilterModel.superclass.constructor.call(this);
    
    this.addEvents(
      /**
       * @event filtertrigger
       * is fired when user request to update list by filter
       * @param {Tine.widgets.grid.FilterToolbar}
       */
      'filtertrigger'
    );
    
};

Ext.extend(Tine.widgets.grid.FilterModel, Ext.Component, {
    /**
     * @cfg {String} label for the filter
     */
    label: '',
    
    /**
     * @cfg {String} name of th field to filter
     */
    field: '',
    
    /**
     * @cfg {string} type of value
     */
    valueType: 'string',
    
    /**
     * @cfg {string} default value
     */
    defaultValue: '',
    
    /**
     * @cfg {Array} valid operators
     */
    operators: null,
    
    /**
     * @cfg {String} name of the default operator
     */
    defaultOperator: 'contains',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.FilterModel.superclass.initComponent.call(this);
        this.isFilterModel = true;
        
        if (! this.operators) {
            this.operators = [];
        }
        
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
        var operatorStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: [
                {operator: 'contains', label: _('contains')},
                {operator: 'equals',   label: _('is equal to')},
                {operator: 'greater',  label: _('is greater than')},
                {operator: 'less',     label: _('is less than')},
                {operator: 'not',      label: _('is not')},
                //{operator: 'in',       label: _('is in')}
            ]
        });

        // filter operators
        if (this.operators.length == 0 && this.valueType == 'string') {
            this.operators.push('contains', 'equals', 'not');
        }
        if (this.operators.length > 0) {
            operatorStore.each(function(operator) {
                if (this.operators.indexOf(operator.get('operator')) < 0 ) {
                    operatorStore.remove(operator);
                }
            }, this);
        }
        
        if (operatorStore.getCount() > 1) {
            var operator = new Ext.form.ComboBox({
                filter: filter,
                width: 100,
                id: 'tw-ftb-frow-operatorcombo-' + filter.id,
                mode: 'local',
                lazyInit: false,
                emptyText: _('select a operator'),
                forceSelection: true,
                typeAhead: true,
                triggerAction: 'all',
                store: operatorStore,
                displayField: 'label',
                valueField: 'operator',
                value: filter.get('operator') ? filter.get('operator') : this.defaultOperator,
                renderTo: el,
            });
            operator.on('select', function(combo, newRecord, newKey) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
        } else {
            var operator = new Ext.form.Label({
                filter: filter,
                width: 100,
                style: {margin: '0px 10px'},
                getValue: function() { return operatorStore.getAt(0).get('operator'); },
                text : operatorStore.getAt(0).get('label'),
                //hideLabel: true,
                //readOnly: true,
                renderTo: el
            });
        }
        
        return operator;
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        //console.log('operator change');
    },
    
    valueRenderer: function(filter, el) {
        // value
        var value = new Ext.form.TextField({
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        
        return value;
    },
    
    /**
     * called on value change of a filter row
     * @private
     */
    onValueChange: function(filter, newValue) {
        filter.set('value', newValue);
        //console.log('value change');
    },
    
    /**
     * @private
     */
    onFiltertrigger: function() {
        this.fireEvent('filtertrigger', this);
    }
});