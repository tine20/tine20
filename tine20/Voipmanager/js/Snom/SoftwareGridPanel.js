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
 * Software grid panel
 */
Tine.Voipmanager.SnomSoftwareGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomSoftware,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.SnomSoftwareBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.SnomSoftwareGridPanel.superclass.initComponent.call(this);
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
                width: 20, 
                sortable: true,
                hidden: true 
               },{
                   id: 'name', 
                  header: this.app.i18n._('name'), 
                  dataIndex: 'name', 
                  width: 150,
                sortable: true
              },{
                  id: 'description', 
                  header: this.app.i18n._('Description'), 
                  dataIndex: 'description', 
                  width: 250,
                sortable: true
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
