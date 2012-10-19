/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * FreeDay grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeDayGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>FreeDay Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.FreeDayGridPanel
 */
Tine.HumanResources.FreeDayGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Tinebase.Model.Record} recordClass
     */
    recordClass: Tine.HumanResources.Model.FreeDay,
    recordProxy: Tine.HumanResources.freedayBackend,
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: null,
    editDialog: null,
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
        if(!this.initFilterToolbar()) {
//            this.disabled = true;
        } else {
            this.plugins = [];
            this.plugins.push(this.filterToolbar);
        }
        Tine.HumanResources.FreeDayGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        var plugins = [],
            filters = [];
        if(!this.editDialog) {
            plugins.push(new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()); 
        } else {
            if(this.editDialog.record && this.editDialog.record.data.id) {
                filters = [{field: 'employee_id', operator: 'equals', 'value': this.editDialog.record.data}];
            } else {
                return false;
            }
        }
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: this.recordClass.getFilterModel(),
            recordClass: this.recordClass,
            defaultFilter: 'query',
            filters: filters,
            plugins: plugins
        });
        
        return true;
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
//            {   id: 'tags', header: this.app.i18n._('Tags'), width: 40,  dataIndex: 'tags', sortable: false, renderer: Tine.Tinebase.common.tagsRenderer },
            { id: 'employee_id', header: this.app.i18n._('Employee'), dataIndex: 'employee_id', width: 200, sortable: true, hidden: (this.editDialog) ? true : false, renderer: this.renderEmployee, scope: this},
            { id: 'type', header: this.app.i18n._('Type'), dataIndex: 'type', width: 100, sortable: true, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'freetimeType')},
            { id: 'firstday_date', header: this.app.i18n._('Date Start'), dataIndex: 'firstday_date', width: 100, sortable: true, renderer: Tine.Tinebase.common.dateRenderer, hidden: true},
            { id: 'remark', header: this.app.i18n._('Remark'), dataIndex: 'remark', width: 200, sortable: true}
            ].concat(this.getModlogColumns());
    },
    
    /**
     * renders the employee
     * @param {Object} value
     * @param {Object} row
     * @param {Tine.Tinebase.data.Record} record
     * @return {String}
     */
    renderEmployee: function(value, row, record) {
        return record.get('employee_id') ? record.get('employee_id').n_fn : '';
    }
});
