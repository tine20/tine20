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
 
        // add context menu actions
        var action_resetHttpClientInfo = new Ext.Action({
           text: this.app.i18n._('reset phones HTTP authentication'), 
           handler: this.resetHttpClientInfo,
           iconCls: 'action_resetHttpClientInfo',
           scope: this
        });
        
        var action_openPhonesWebGui = new Ext.Action({
           text: this.app.i18n._('Open phones web gui'), 
           handler: this.openPhonesWebGui,
           iconCls: 'action_openPhonesWebGui',
           scope: this
        });
        
        this.contextMenuItems = [action_resetHttpClientInfo, action_openPhonesWebGui];
        
        Tine.Voipmanager.SnomPhoneGridPanel.superclass.initComponent.call(this);
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
            	header: this.app.i18n._('Id'), 
            	dataIndex: 'id', 
            	width: 30,
            	sortable: true,
            	hidden: true 
            },{ 
            	id: 'macaddress', 
            	header: this.app.i18n._('MAC address'), 
            	dataIndex: 'macaddress',
            	width: 50,
            	sortable: true
            },{ 
            	id: 'description', 
            	header: this.app.i18n._('description'), 
            	dataIndex: 'description',
            	sortable: true
            },{
                id: 'location_id',
                header: this.app.i18n._('Location'),
                dataIndex: 'location_id',
                width: 70,
                sortable: true,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.location;
                }
            },{
                id: 'template_id',
                header: this.app.i18n._('Template'),
                dataIndex: 'template_id',
                width: 70,
                sortable: true,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.template;
                }                                
            },{ 
            	id: 'ipaddress', 
            	header: this.app.i18n._('IP Address'), 
            	dataIndex: 'ipaddress', 
            	width: 50,
                sortable: true
            },{ 
            	id: 'current_software', 
            	header: this.app.i18n._('Software'), 
            	dataIndex: 'current_software', 
            	width: 50,
            	sortable: true
            },{ 
            	id: 'current_model', 
            	header: this.app.i18n._('current model'), 
            	dataIndex: 'current_model', 
            	width: 70,
            	sortable: true,
            	hidden: true 
            },{ 
            	id: 'redirect_event', 
            	header: this.app.i18n._('redirect event'), 
            	dataIndex: 'redirect_event', 
            	width: 70,
            	sortable: true,
            	hidden: true 
            },{ 
            	id: 'redirect_number', 
            	header: this.app.i18n._('redirect number'), 
            	dataIndex: 'redirect_number', 
            	width: 100,
            	sortable: true,
            	hidden: true 
            },{ 
            	id: 'redirect_time', 
            	header: this.app.i18n._('redirect time'), 
            	dataIndex: 'redirect_time', 
            	width: 25,
            	sortable: true,
            	hidden: true 
            },{ 
            	id: 'settings_loaded_at', 
            	header: this.app.i18n._('settings loaded at'), 
            	dataIndex: 'settings_loaded_at', 
            	width: 100, 
                sortable: true,
            	hidden: true,
                renderer: Tine.Tinebase.common.dateTimeRenderer 
            },{ 
            	id: 'last_modified_time', 
            	header: this.app.i18n._('last modified'), 
            	dataIndex: 'last_modified_time', 
            	width: 100, 
                sortable: true,
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
    },
    
    /**
     * onclick handler for resetHttpClientInfo
     */
    resetHttpClientInfo: function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to send HTTP Client Info again?', function(_button){
            if (_button == 'yes') {
            
                var phoneIds = [];
                
                var selectedRows = this.selectionModel.getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    phoneIds.push(selectedRows[i].id);
                }
                
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Voipmanager.resetHttpClientInfo',
                        _phoneIds: phoneIds
                    },
                    text: 'sending HTTP Client Info to phone(s)...',
                    success: function(_result, _request){
                        // not really needed to reload store
                        //Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload();
                    },
                    failure: function(result, request){
                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to send HTTP Client Info to the phone(s).');
                    }
                });
            }
        }, this);
    },
    
    /**
     * onclick handler for openPhonesWebGui
     */
    openPhonesWebGui: function(_button, _event) {
        var phoneIp;
                
        var selectedRows = this.selectionModel.getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            phoneIp = selectedRows[i].get('ipaddress');
            if (phoneIp && phoneIp.length >= 7) {
                window.open('http://' + phoneIp, '_blank',  'width=1024,height=768,scrollbars=1');
            }
        }
    }
});