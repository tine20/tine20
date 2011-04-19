/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.container');


/**
 * Container grid panel
 * 
 * @namespace   Tine.Admin.container
 * @class       Tine.Admin.container.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.container.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    // TODO change this icon
    newRecordIcon: 'action_addContact',
    recordClass: Tine.Tinebase.Model.Container,
    recordProxy: Tine.Admin.containerBackend,
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'name'
    },
    
    initComponent: function() {
        this.gridConfig.cm = this.getColumnModel();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Admin.container.GridPanel.superclass.initComponent.call(this);
    },
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns columns
     * @private
     * @return Array
     * 
     * TODO add more
     */
    getColumns: function(){
        return [
            { header: this.app.i18n._('ID'), id: 'id', dataIndex: 'id', width: 50},
            { header: this.app.i18n._('Container Name'), id: 'name', dataIndex: 'name', hidden: false, width: 200}
        ];
    },
    
    /**
     * initialises filter toolbar
     * 
     * TODO add more
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Container'),    field: 'query',       operators: ['contains']}
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    }    
});
