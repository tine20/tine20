/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Context grid panel
 */
Tine.Voipmanager.SnomPhoneGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomPhone,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
    
        this.recordProxy = Tine.Voipmanager.SnomPhoneBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
 
         
        Tine.Voipmanager.SnomPhoneGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Phone'),    field: 'query',    operators: ['contains']}
             ],
             defaultFilter: 'query',
             filters: []
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
            	header: this.app.i18n._('Id'), 
            	dataIndex: 'id', 
            	width: 30, 
            	hidden: true 
            },{ 
            	id: 'macaddress', 
            	header: this.app.i18n._('MAC address'), 
            	dataIndex: 'macaddress',
            	width: 50 
            },{ 
            	id: 'description', 
            	header: this.app.i18n._('description'), 
            	dataIndex: 'description' 
            },{
                id: 'location_id',
                header: this.app.i18n._('Location'),
                dataIndex: 'location_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.location;
                }
            },{
                id: 'template_id',
                header: this.app.i18n._('Template'),
                dataIndex: 'template_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.template;
                }                                
            },{ 
            	id: 'ipaddress', 
            	header: this.app.i18n._('IP Address'), 
            	dataIndex: 'ipaddress', 
            	width: 50 
            },{ 
            	id: 'current_software', 
            	header: this.app.i18n._('Software'), 
            	dataIndex: 'current_software', 
            	width: 50 
            },{ 
            	id: 'current_model', 
            	header: this.app.i18n._('current model'), 
            	dataIndex: 'current_model', 
            	width: 70, 
            	hidden: true 
            },{ 
            	id: 'redirect_event', 
            	header: this.app.i18n._('redirect event'), 
            	dataIndex: 'redirect_event', 
            	width: 70, 
            	hidden: true 
            },{ 
            	id: 'redirect_number', 
            	header: this.app.i18n._('redirect number'), 
            	dataIndex: 'redirect_number', 
            	width: 100, 
            	hidden: true 
            },{ 
            	id: 'redirect_time', 
            	header: this.app.i18n._('redirect time'), 
            	dataIndex: 'redirect_time', 
            	width: 25, 
            	hidden: true 
            },{ 
            	id: 'settings_loaded_at', 
            	header: this.app.i18n._('settings loaded at'), 
            	dataIndex: 'settings_loaded_at', 
            	width: 100, 
            	hidden: true,
                renderer: Tine.Tinebase.common.dateTimeRenderer 
            },{ 
            	id: 'last_modified_time', 
            	header: this.app.i18n._('last modified'), 
            	dataIndex: 'last_modified_time', 
            	width: 100, 
            	hidden: true,
                renderer: Tine.Tinebase.common.dateTimeRenderer 
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