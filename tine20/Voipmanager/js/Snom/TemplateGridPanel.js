/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Context grid panel
 */
Tine.Voipmanager.SnomTemplateGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomTemplate,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
    
        this.recordProxy = Tine.Voipmanager.SnomTemplateBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
 
         
        Tine.Voipmanager.SnomTemplateGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: _('Quick search'),    field: 'query',    operators: ['contains']}
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{
                id: 'id', 
                header: this.app.i18n._('id'), 
                dataIndex: 'id', 
                width: 10, 
                sortable: true,
                hidden: true 
               },{
                   id: 'name', 
                   header: this.app.i18n._('name'), 
                   dataIndex: 'name', 
                   width: 100,
                sortable: true
               },{
                   id: 'description', 
                   header: this.app.i18n._('Description'), 
                   dataIndex: 'description', 
                   width: 350,
                sortable: true
               },{
                   id: 'keylayout_id', 
                   header: this.app.i18n._('Keylayout Id'), 
                   dataIndex: 'keylayout_id', 
                   width: 10, 
                sortable: true,
                   hidden: true 
               },{
                   id: 'setting_id', 
                   header: this.app.i18n._('Settings Id'), 
                   dataIndex: 'setting_id', 
                   width: 10, 
                sortable: true,
                   hidden: true 
               },{
                   id: 'software_id', 
                   header: this.app.i18n._('Software Id'), 
                   dataIndex: 'software_id', 
                   width: 10, 
                sortable: true,
                   hidden: true 
               }];
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
       
        return [

        ];
    } 
});
