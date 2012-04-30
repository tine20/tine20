/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
    evalGrants: true,
    
    /**
     * optional additional filterToolbar configs
     * @cfg {Object} ftbConfig
     */
    ftbConfig: null,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'id'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.HumanResources.recordBackend;
        
        this.gridConfig.cm = this.getColumnModel();
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar(this.ftbConfig);
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.HumanResources.EmployeeGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
            {   id: 'tags', header: this.app.i18n._('Tags'), width: 40,  dataIndex: 'tags', sortable: false, renderer: Tine.Tinebase.common.tagsRenderer },                
            {
                id: 'number',
                header: this.app.i18n._("Number"),
                width: 100,
                sortable: true,
                dataIndex: 'number',
                hidden: true
            }, {
                id: 'title',
                header: this.app.i18n._("Title"),
                width: 350,
                sortable: true,
                dataIndex: 'title'
            }, {
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 150,
                sortable: true,
                dataIndex: 'status'
//                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('HumanResources', 'projectStatus')
            }].concat(this.getModlogColumns())
        });
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
