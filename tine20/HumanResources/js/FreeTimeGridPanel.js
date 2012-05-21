/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * FreeTime grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>FreeTime Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.FreeTimeGridPanel
 */
Tine.HumanResources.FreeTimeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Tinebase.Model.Record} recordClass
     */
    recordClass: null,
    recordProxy: null,
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: null,
    fromEditDialog: null,
    /**
     * optional additional filterToolbar configs
     * @cfg {Object} ftbConfig
     */
    ftbConfig: null,
    recordProxy: null,
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: null,
    gridConfig: null,
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.defaultSortInfo = {field: 'number', direction: 'DESC'};
        this.gridConfig = { autoExpandColumn: 'n_fn' };
        this.gridConfig.columns = this.getColumns();
        if(! this.fromEditDialog) {
            this.initFilterToolbar();
            this.plugins.push(this.filterToolbar);
        }
        
        Tine.HumanResources.FreeTimeGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: this.recordClass.getFilterModel(),
            defaultFilter: 'query',
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
     * 
     * 
     * 'id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'employee_id' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'duration'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'remark'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'date'
     */
    getColumns: function(){
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
                width: 350,
                sortable: true,
                dataIndex: 'n_fn'
            }, {
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 150,
                sortable: true,
                dataIndex: 'status'
            }].concat(this.getModlogColumns());
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    }
});
