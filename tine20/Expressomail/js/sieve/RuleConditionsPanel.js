/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Expressomail.sieve');

/**
 * @namespace   Tine.Expressomail.sieve
 * @class       Tine.Expressomail.sieve.RuleConditionsPanel
 * @extends     Tine.widgets.grid.FilterToolbar
 * 
 * <p>Sieve Filter Conditions Panel</p>
 * <p>
 * mapping when getting filter values:
 *  field       -> test_header or 'size'
 *  operator    -> comperator
 *  value       -> key
 * </p>
 * <p>
 * </p>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleConditionsPanel
 */
Tine.Expressomail.sieve.RuleConditionsPanel = Ext.extend(Tine.widgets.grid.FilterToolbar, {
    
    defaultFilter: 'from',
    neverAllowSaving: true,
    showSearchButton: false,
    filterFieldWidth: 160,
    
    // unused fn
    onFiltertrigger: Ext.emptyFn,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Expressomail');
        this.rowPrefix = '';
        
        this.filterModels = Tine.Expressomail.sieve.RuleConditionsPanel.getFilterModel(this.app);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * gets filter data (use getValue() if we don't have a store/plugins)
     * 
     * @return {Array} of filter records
     */
    getAllFilterData: function() {
        return this.getValue();
    }
});

/**
 * get rule conditions for filter model and condition renderer
 * 
 * @param {} app
 * @return {Array}
 */
Tine.Expressomail.sieve.RuleConditionsPanel.getFilterModel = function(app) {

    return [
        {label: app.i18n._('From (Email)'),     field: 'from',     operators: ['contains', 'regex'], emptyText: 'test@example.org'},
        {label: app.i18n._('From (Email and Name)'), field: 'fromheader',     operators: ['contains', 'regex'], emptyText: 'name or email'},
        {label: app.i18n._('To (Email)'),       field: 'to',       operators: ['contains', 'regex'], emptyText: 'test@example.org'},
        {label: app.i18n._('Subject'),          field: 'subject',  operators: ['contains', 'regex'], emptyText: app.i18n._('Subject')},
        {label: app.i18n._('Size') + " (MB)",   field: 'size',     operators: ['greater', 'less'], valueType: 'number', defaultOperator: 'greater'},
        {label: app.i18n._('Header contains'),  field: 'header',   operators: ['freeform'], defaultOperator: 'freeform', 
            emptyTextOperator: app.i18n._('Header name'), emptyText: app.i18n._('Header value')},
        {label: app.i18n._('Header regex'),     field: 'headerregex',   operators: ['freeform'], defaultOperator: 'freeform',
            emptyTextOperator: app.i18n._('Header name'), emptyText: app.i18n._('Header value')}
    ];
};
