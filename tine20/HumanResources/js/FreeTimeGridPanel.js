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
     * 
     * @cfg {Tine.Tinebase.Model.Record} recordClass
     */
    recordClass: Tine.HumanResources.Model.FreeTime,
    recordProxy: Tine.HumanResources.freetimeBackend,
    
    /**
     * eval grants
     * 
     * @cfg {Boolean} evalGrants
     */
    evalGrants: null,
    
    /**
     * the calling editDialog
     * 
     * @type Tine.HumanResources.EmployeeEditDialog
     */
    editDialog: null,
    
    /**
     * optional additional filterToolbar configs
     * 
     * @cfg {Object} ftbConfig
     */
    ftbConfig: null,
    
    recordProxy: null,
    
    /**
     * grid specific
     * 
     * @private
     */
    defaultSortInfo: null,
    
    gridConfig: null,
    
    /**
     * cache for statusRenderers
     * 
     * @type {Array}
     */
    statusRenderers: null,
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.defaultSortInfo = {field: 'number', direction: 'DESC'};
        this.statusRenderers = [];
        this.gridConfig = { autoExpandColumn: 'n_fn' };
        this.gridConfig.columns = this.getColumns();
        if(!this.initFilterToolbar()) {
            this.getActionToolbar = Ext.emptyFn;
            
        } else {
            this.plugins = [];
            this.plugins.push(this.filterToolbar);
        }
        
        if(this.editDialog) {
            this.bbar = [];
        }
        
        Tine.HumanResources.FreeTimeGridPanel.superclass.initComponent.call(this);
        if(this.editDialog) {
            this.fillBottomToolbar();
        }
        
    },
    
    /**
     * will be called in Edit Dialog Mode
     */
    fillBottomToolbar: function() {
        var tbar = this.getBottomToolbar();
        tbar.addButton(new Ext.Button(this.action_editInNewWindow));
        tbar.addButton(new Ext.Button(this.action_addInNewWindow));
        tbar.addButton(new Ext.Button(this.action_deleteRecord));
    },
    
    /**
     * overwrites and calls superclass
     * 
     * @param {Object} button
     * @param {Tine.Tinebase.data.Record} record
     * @param {Array} addRelations
     */
    onEditInNewWindow: function(button, record, addRelations) {
        if(this.editDialog) {
            button.fixedFields = [{key: 'employee_id', value: this.editDialog.record.data}];
        }
        Tine.HumanResources.FreeTimeGridPanel.superclass.onEditInNewWindow.call(this, button, record, addRelations);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        var plugins = [],
            filters = [],
            hidden = false;
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
            defaultFilter: 'query',
            recordClass: this.recordClass,
            filters: filters,
            plugins: plugins,
            hidden: hidden
        });
        
        return true;
    },
    
    /**
     * returns ColumnModel
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumns: function(){
        return [
            { id: 'employee_id', header: this.app.i18n._('Employee'), dataIndex: 'employee_id', width: 200, sortable: true, hidden: (this.editDialog) ? true : false, renderer: this.renderEmployee, scope: this},
            { id: 'type', header: this.app.i18n._('Type'), dataIndex: 'type', width: 100, sortable: true, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'freetimeType')},
            { id: 'status', header: this.app.i18n._('Status'), dataIndex: 'status', width: 100, sortable: true, renderer: this.renderStatus.createDelegate(this), scope: this },
            { id: 'firstday_date', header: this.app.i18n._('Date Start'), dataIndex: 'firstday_date', width: 100, sortable: true, renderer: Tine.Tinebase.common.dateRenderer, hidden: true},
            { id: 'remark', header: this.app.i18n._('Remark'), dataIndex: 'remark', width: 200, sortable: true}
            ].concat(this.getModlogColumns());
    },
    
    /**
     * renders the employee
     * 
     * @param {Object} value
     * @param {Object} row
     * @param {Tine.Tinebase.data.Record} record
     * @return {String}
     */
    renderEmployee: function(value, row, record) {
        return record.get('employee_id') ? record.get('employee_id').n_fn : '';
    },
    
    /**
     * renders the status
     * 
     * @param {Object} value
     * @param {Object} row
     * @param {Tine.Tinebase.data.Record} record
     * @return {String}
     */
    renderStatus:function(value, row, record) {
        var prefix = record.get('type').split('_')[0],
            configName = prefix.toLowerCase() + 'Status';
        
        if (! this.statusRenderers[configName]) {
            this.statusRenderers[configName] = Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', configName);
        }
        
        return this.statusRenderers[configName](value, row, record);
    }
});
