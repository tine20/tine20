/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Software grid panel
 */
Tine.Voipmanager.SnomSoftwareGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomSoftware,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
    
        this.recordProxy = Tine.Voipmanager.SnomSoftwareBackend;
                
        this.gridConfig.columns = this.getColumns();
        //this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
      //  this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        //this.plugins.push(this.filterToolbar);
 
         
        Tine.Voipmanager.SnomSoftwareGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        
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
            	hidden: true 
           	},{ 
           		id: 'name', 
          		header: this.app.i18n._('name'), 
          		dataIndex: 'name', 
          		width: 150 
          	},{ 
          		id: 'description', 
          		header: this.app.i18n._('Description'), 
          		dataIndex: 'description', 
          		width: 250 
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