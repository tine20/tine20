/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * Employee grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Employee Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeGridPanel
 */
Tine.HumanResources.EmployeeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.HumanResources.Model.Employee} recordClass
     */
    recordClass: Tine.HumanResources.Model.Employee,
    
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: false,
    
    /**
     * optional additional filterToolbar configs
     * @cfg {Object} ftbConfig
     */
    ftbConfig: null,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'number', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'n_fn'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.HumanResources.employeeBackend
        
        this.gridConfig.columns = this.getColumns();
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar(this.ftbConfig);
        
        this.initFilterToolbar();
        this.plugins.push(this.filterToolbar);
        
        Tine.HumanResources.EmployeeGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.HumanResources.Model.Employee.getFilterModel(),
            defaultFilter: 'query',
            recordClass: this.recordClass,
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumns: function() {
        return [
            {   id: 'tags', header: this.app.i18n._('Tags'), width: 40,  dataIndex: 'tags', sortable: false, renderer: Tine.Tinebase.common.tagsRenderer },                
            {
                id: 'number',
                header: this.app.i18n._("Number"),
                width: 100,
                sortable: true,
                dataIndex: 'number',
                hidden: true
            }, {
                id: 'n_fn',
                header: this.app.i18n._("Full Name"),
                width: 150,
                sortable: true,
                dataIndex: 'n_fn'
            }, {
                id: 'salutation',
                header: this.app.i18n._("Salutation"),
                width: 150,
                sortable: true,
                dataIndex: 'salutation',
                hidden: true
            }, {
                id: 'title',
                header: this.app.i18n._("Title"),
                width: 150,
                sortable: true,
                dataIndex: 'title',
                hidden: true
            }, {
                id: 'n_given',
                header: this.app.i18n._("First Name"),
                width: 150,
                sortable: true,
                dataIndex: 'n_given',
                hidden: true
            }, {
                id: 'n_family',
                header: this.app.i18n._("Last Name"),
                width: 150,
                sortable: true,
                dataIndex: 'n_family',
                hidden: true
            }, {
                id: 'employment_begin',
                header: this.app.i18n._("Employment begin"),
                width: 150,
                sortable: true,
                dataIndex: 'employment_begin',
                renderer: Tine.Tinebase.common.dateRenderer
            }, {
                id: 'employment_end',
                header: this.app.i18n._("Employment end"),
                width: 150,
                sortable: true,
                dataIndex: 'employment_end',
                renderer: Tine.Tinebase.common.dateRenderer
            }, {
                id: 'supervisor_id',
                header: this.app.i18n._("Supervisor"),
                width: 150,
                sortable: true,
                dataIndex: 'supervisor_id',
                renderer: function(value) {
                    return Ext.util.Format.htmlEncode(value ? value.n_fn : '');
                }
            }, {
                id: 'health_insurance',
                header: this.app.i18n._("Health Insurance"),
                width: 150,
                sortable: true,
                dataIndex: 'health_insurance'
            }, {
                id: 'division_id',
                header: this.app.i18n._("Division"),
                width: 150,
                sortable: true,
                dataIndex: 'division_id',
                renderer: function(data) {
                    if(data && data.title) {
                        return Ext.util.Format.htmlEncode(data.title)
                    }
                    return '';
                }
            }
            
            ].concat(this.getModlogColumns());
    }
});
