/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         add year to 'inweek' filter?
 */
Ext.ns('Tine.widgets.grid');

/**
 * Model of filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterModel
 * @extends     Ext.util.Observable
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
    
    this.initComponent();
};

Ext.extend(Tine.widgets.grid.FilterModel, Ext.util.Observable, {
    /**
     * @cfg {String} label 
     * label for the filter
     */
    label: '',
    
    /**
     * @cfg {String} field
     * name of th field to filter
     */
    field: '',
    
    /**
     * @cfg {String} valueType
     * type of value
     */
    valueType: 'string',
    
    /**
     * @cfg {String} defaultValue
     * default value
     */
    defaultValue: null,
    
    /**
     * @cfg {Array} operators
     * valid operators
     */
    operators: null,
    
    /**
     * @cfg {String} defaultOperator
     * name of the default operator
     */
    defaultOperator: null,
    
    /**
     * @cfg {Array} customOperators
     * define custom operators
     */
    customOperators: null,
    
    /**
     * @cfg {Ext.data.Store|Array} 
     * used by combo valueType
     */
    store: null,
    
    /**
     * @cfg {String} displayField
     * used by combo valueType
     */
    displayField: null,
    
    /**
     * @cfg {String} valueField
     * used by combo valueType
     */
    valueField: null,
    filterValueWidth: 200,
    
    /**
     * holds the future operators of date filters. Auto set by getDateFutureOps
     * 
     * @type {Array}
     */
    dateFutureOps: null,
    
    /**
     * holds the future operators of date filters. Auto set by getDatePastOps
     * 
     * @type {Array}
     */
    datePastOps: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.isFilterModel = true;
        
        if (! this.operators) {
            this.operators = [];
        }
        
        
        if (this.defaultOperator === null) {
            switch (this.valueType) {
                
                case 'date':
                    this.defaultOperator = 'within';
                    break;
                case 'account':
                case 'group':
                case 'user':
                case 'bool':
                case 'number':
                case 'percentage':
                case 'combo':
                case 'country':
                    this.defaultOperator = 'equals';
                    break;
                case 'string':
                default:
                    this.defaultOperator = 'contains';
                    break;
            }
        }
        
        if (this.defaultValue === null) {
            switch (this.valueType) {
                case 'customfield':
                case 'string':
                    this.defaultValue = '';
                    break;
                case 'bool':
                    this.defaultValue = '1';
                    break;
                case 'percentage':
                    this.defaultValue = '0';
                    break;
                case 'date':
                case 'account':
                case 'group':
                case 'user':
                case 'number':
                case 'country':
                default:
                    break;
            }
        }
        
        this.datePastOps = this.getDatePastOps();
        this.dateFutureOps = this.getDateFutureOps();
    },
    
    /**
     * returns past operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDatePastOps: function() {
        return [
            ['dayThis',         _('today')], 
            ['dayLast',         _('yesterday')], 
            ['weekThis',        _('this week')], 
            ['weekLast',        _('last week')],
            ['weekBeforeLast',  _('the week before last')],
            ['monthThis',       _('this month')],
            ['monthLast',       _('last month')],
            ['monthThreeLast',  _('last three months')],
            ['monthSixLast',    _('last six months')],
            ['anytime',         _('anytime')],
            ['quarterThis',     _('this quarter')],
            ['quarterLast',     _('last quarter')],
            ['yearThis',        _('this year')],
            ['yearLast',        _('last year')]
        ];
    },
    
    /**
     * returns future operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDateFutureOps: function() {
        return [
            ['dayNext',         _('tomorrow')], 
            ['weekNext',        _('next week')], 
            ['monthNext',       _('next month')],
            ['quarterNext',     _('next quarter')],
            ['yearNext',        _('next year')]
        ];
    },
    
    onDestroy: Ext.emptyFn,
    
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
                {operator: 'contains',      label: _('contains')},
                {operator: 'regex',         label: _('reg. exp.')},
                {operator: 'equals',        label: _('is equal to')},
                {operator: 'equalsspecial', label: _('is equal to without (-, )')},
                {operator: 'greater',       label: _('is greater than')},
                {operator: 'less',          label: _('is less than')},
                {operator: 'not',           label: _('is not')},
                {operator: 'in',            label: _('one of')},
                {operator: 'notin',         label: _('none of')},
                {operator: 'before',        label: _('is before')},
                {operator: 'after',         label: _('is after')},
                {operator: 'within',        label: _('is within')},
                {operator: 'inweek',        label: _('is in week no.')},
                {operator: 'startswith',    label: _('starts with')},
                {operator: 'endswith',      label: _('ends with')},
                {operator: 'definedBy',     label: _('defined by')}
            ].concat(this.getCustomOperators() || []),
            remoteSort: false,
            sortInfo: {
                field: 'label',
                direction: 'ASC'
            }
        });

        // filter operators
        if (this.operators.length == 0) {
            switch (this.valueType) {
                case 'string':
                    this.operators.push('contains', 'equals', 'startswith', 'endswith', 'not', 'in');
                    break;
                case 'customfield':
                    this.operators.push('contains', 'equals', 'startswith', 'endswith', 'not');
                    break;
                case 'date':
                    this.operators.push('equals', 'before', 'after', 'within', 'inweek');
                    break;
                case 'number':
                case 'percentage':
                    this.operators.push('equals', 'greater', 'less');
                    break;
                default:
                    this.operators.push(this.defaultOperator);
                    break;
            }
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
                width: 80,
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
                tpl: '<tpl for="."><div class="x-combo-list-item tw-ftb-operator-{operator}">{label}</div></tpl>',
                renderTo: el
            });
            operator.on('select', function(combo, newRecord, newKey) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
            
            operator.on('blur', function(combo) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
            
        } else if (this.operators[0] == 'freeform') {
            var operator = new Ext.form.TextField({
                filter: filter,
                width: 100,
                emptyText: this.emptyTextOperator || '',
                value: filter.get('operator') ? filter.get('operator') : '',
                renderTo: el
            });
        } else {
            var operator = new Ext.form.Label({
                filter: filter,
                width: 100,
                style: {margin: '0px 10px'},
                getValue: function() { return operatorStore.getAt(0).get('operator'); },
                text : operatorStore.getAt(0).get('label'),
                renderTo: el,
                setValue: Ext.emptyFn
            });
        }
        
        return operator;
    },
    
    /**
     * get custom operators
     * 
     * @return {Array}
     */
    getCustomOperators: function() {
        return this.customOperators || [];
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        filter.set('value', '');
        
        // for date filters we need to rerender the value section
        if (this.valueType == 'date') {
            switch (newOperator) {
                case 'within':
                    filter.numberfield.hide();
                    filter.datePicker.hide();
                    filter.withinCombo.show();
                    filter.formFields.value = filter.withinCombo;
                    break;
                case 'inweek':
                    filter.withinCombo.hide();
                    filter.datePicker.hide();
                    filter.numberfield.show();
                    filter.formFields.value = filter.numberfield;
                    break;
                default:
                    filter.withinCombo.hide();
                    filter.numberfield.hide();
                    filter.datePicker.show();
                    filter.formFields.value = filter.datePicker;
            }
        }
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value,
            fieldWidth = this.filterValueWidth,
            commonOptions = {
                filter: filter,
                width: fieldWidth,
                id: 'tw-ftb-frow-valuefield-' + filter.id,
                renderTo: el,
                value: filter.data.value ? filter.data.value : this.defaultValue
            };
        
        switch (this.valueType) {
            case 'date':
                value = this.dateValueRenderer(filter, el);
                break;
            case 'percentage':
                value = new Ext.ux.PercentCombo(Ext.apply(commonOptions, {
                    listeners: {
                        'specialkey': function(field, e) {
                             if(e.getKey() == e.ENTER){
                                 this.onFiltertrigger();
                             }
                        },
                        'select': this.onFiltertrigger,
                        scope: this
                    }
                }));
                break;
            case 'user':
                value = new Tine.Addressbook.SearchCombo(Ext.apply(commonOptions, {
                    listWidth: 350,
                    emptyText: _('Search Account ...'),
                    userOnly: true,
                    name: 'organizer',
                    nameField: 'n_fileas',
                    useAccountRecord: true,
                    listeners: {
                        'specialkey': function(field, e) {
                             if(e.getKey() == e.ENTER){
                                 this.onFiltertrigger();
                             }
                        },
                        'select': this.onFiltertrigger,
                        scope: this
                    }
                }));
                break;
            case 'bool':
                value = new Ext.form.ComboBox(Ext.apply(commonOptions, {
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    store: [
                        [0, Locale.getTranslationData('Question', 'no').replace(/:.*/, '')], 
                        [1, Locale.getTranslationData('Question', 'yes').replace(/:.*/, '')]
                    ],
                    listeners: {
                        'specialkey': function(field, e) {
                             if(e.getKey() == e.ENTER){
                                 this.onFiltertrigger();
                             }
                        },
                        'select': this.onFiltertrigger,
                        scope: this
                    }
                }));
                break;
            case 'combo':
                var comboConfig = Ext.apply(commonOptions, {
                    mode: 'local',
                    forceSelection: true,
                    triggerAction: 'all',
                    store: this.store,
                    listeners: {
                        'specialkey': function(field, e) {
                             if(e.getKey() == e.ENTER){
                                 this.onFiltertrigger();
                             }
                        },
                        'select': this.onFiltertrigger,
                        scope: this
                    }
                });
                if (this.displayField !== null && this.valueField !== null) {
                    comboConfig.displayField = this.displayField;
                    comboConfig.valueField = this.valueField;
                }
                value = new Ext.form.ComboBox(comboConfig);
                break;
            case 'country':
                value = new Tine.widgets.CountryCombo(Ext.apply(commonOptions, {
                }));
                break;
            case 'customfield':
            case 'string':
            case 'number':
            default:
                value = new Ext.ux.form.ClearableTextField(Ext.apply(commonOptions, {
                    emptyText: this.emptyText,
                    listeners: {
                        scope: this,
                        specialkey: function(field, e){
                            if(e.getKey() == e.ENTER){
                                this.onFiltertrigger();
                            }
                        }
                    }
                }));
                break;
        }
        
        return value;
    },
    
    /**
     * called on value change of a filter row
     * @private
     */
    onValueChange: function(filter, newValue) {
        filter.set('value', newValue);
    },
    
    /**
     * render a date value
     * 
     * we place a picker and a combo in the dom element and hide the one we don't need yet
     */
    dateValueRenderer: function(filter, el) {
        var operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator;
        
        var valueType = 'datePicker';
        switch (operator) {
            case 'within':
                valueType = 'withinCombo';
                break;
            case 'inweek':
                valueType = 'numberfield';
                break;
        }
        
        var comboOps = this.pastOnly ? this.datePastOps : this.dateFutureOps.concat(this.datePastOps);
        var comboValue = 'weekThis';
        if (filter.data.value && filter.data.value.toString().match(/^[a-zA-Z]+$/)) {
            comboValue = filter.data.value.toString();
        } else if (this.defaultValue && this.defaultValue.toString().match(/^[a-zA-Z]+$/)) {
            comboValue = this.defaultValue.toString();
        }
        
        filter.withinCombo = new Ext.form.ComboBox({
            hidden: valueType != 'withinCombo',
            filter: filter,
            width: this.filterValueWidth,
            value: comboValue,
            renderTo: el,
            mode: 'local',
            lazyInit: false,
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: comboOps,
            editable: false,
            listeners: {
                'specialkey': function(field, e) {
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                },
                'select': this.onFiltertrigger,
                scope: this
            }
        });

        var pickerValue = '';
        if (Ext.isDate(filter.data.value)) {
            pickerValue = filter.data.value;
        } else if (Ext.isDate(Date.parseDate(filter.data.value, Date.patterns.ISO8601Long))) {
            pickerValue = Date.parseDate(filter.data.value, Date.patterns.ISO8601Long);
        } else if (Ext.isDate(this.defaultValue)) {
            pickerValue = this.defaultValue;
        }
        
        filter.datePicker = new Ext.form.DateField({
            hidden: valueType != 'datePicker',
            filter: filter,
            width: this.filterValueWidth,
            value: pickerValue,
            renderTo: el,
            listeners: {
                'specialkey': function(field, e) {
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                },
                'select': this.onFiltertrigger,
                scope: this
            }
        });
        
        filter.numberfield = new Ext.form.NumberField({
            hidden: valueType != 'numberfield',
            filter: filter,
            width: this.filterValueWidth,
            value: pickerValue,
            renderTo: el,
            minValue: 1,
            maxValue: 52,
            maxLength: 2,   
            allowDecimals: false,
            allowNegative: false,
            listeners: {
                scope: this,
                specialkey: function(field, e){
                    if(e.getKey() == e.ENTER){
                        this.onFiltertrigger();
                    }
                }
            }
        });
        
        // upps, how to get a var i only know the name of???
        return filter[valueType];
    },
    
    /**
     * @private
     */
    onFiltertrigger: function() {
        // auto search on filter change only if set in user preferences
        if (parseInt(Tine.Tinebase.registry.get('preferences').get('filterChangeAutoSearch'), 10) === 1) {
            this.fireEvent('filtertrigger', this);
        }
    }
});

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterRegistry
 * @singleton
 */
Tine.widgets.grid.FilterRegistry = function() {
    var filters = {};
    
    return {
        register: function(appName, modelName, filter) {
            var key = appName + '.' + modelName;
            if (! filters[key]) {
                filters[key] = [];
            }
            
            filters[key].push(filter);
        },
        
        get: function(appName, modelName) {
            if (Ext.isFunction(appName.getMeta)) {
                modelName = appName.getMeta('modelName');
                appName = appName.getMeta('appName');
            }
            
            var key = appName + '.' + modelName;
            
            return filters[key] || [];
        }
    };
}();
