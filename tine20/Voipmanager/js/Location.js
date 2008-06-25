/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Location');

Tine.Voipmanager.Location.Main = {
       
    actions: {
        addLocation: null,
        editLocation: null,
        deleteLocation: null
    },
    
    handlers: {
        /**
         * onclick handler for addLocation
         */
        addLocation: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('locationWindow', 'index.php?method=Voipmanager.editLocation&LocationId=', 550, 600);
        },

        /**
         * onclick handler for editLocation
         */
        editLocation: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Location_Grid').getSelectionModel().getSelections();
            var locationId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('locationWindow', 'index.php?method=Voipmanager.editLocation&locationId=' + locationId, 550, 600);
        },
        
        /**
         * onclick handler for deleteLocation
         */
        deleteLocation: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected location?', function(_button){
                if (_button == 'yes') {
                
                    var locationIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Location_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        locationIds.push(selectedRows[i].id);
                    }
                    
                    locationIds = Ext.util.JSON.encode(locationIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteSnomLocations',
                            _locationIds: locationIds
                        },
                        text: 'Deleting location...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Location_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the location.');
                        }
                    });
                }
            });
        }    
    },


    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Voipmanager');
    
        this.actions.addLocation = new Ext.Action({
            text: this.translation._('add location'),
            handler: this.handlers.addLocation,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editLocation = new Ext.Action({
            text: this.translation._('edit location'),
            disabled: true,
            handler: this.handlers.editLocation,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteLocation = new Ext.Action({
            text: this.translation._('delete location'),
            disabled: true,
            handler: this.handlers.deleteLocation,
            iconCls: 'action_delete',
            scope: this
        });
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Voipmanager.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('VoipmanagerTreePanel');
        preferencesButton.setDisabled(true);
    },
    
    displayLocationToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Location_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
        
        var locationToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Location_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addLocation, 
                this.actions.editLocation,
                this.actions.deleteLocation,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(locationToolbar);
    },

    displayLocationGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Location,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        //Ext.StoreMgr.add('LocationStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying location {0} - {1} of {2}'),
            emptyMsg: this.translation._("No location to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'firmware_interval', header: this.translation._('FW Interval'), dataIndex: 'firmware_interval', width: 10, hidden: true },
            { resizable: true, id: 'firmware_status', header: this.translation._('FW Status'), dataIndex: 'firmware_status', width: 100, hidden: true  },
            { resizable: true, id: 'update_policy', header: this.translation._('Update Policy'), dataIndex: 'update_policy', width: 30, hidden: true },
            { resizable: true, id: 'setting_server', header: this.translation._('Server Setting'), dataIndex: 'setting_server', width: 100, hidden: true  },
            { resizable: true, id: 'admin_mode', header: this.translation._('Admin Mode'), dataIndex: 'admin_mode', width: 10, hidden: true },
            { resizable: true, id: 'ntp_server', header: this.translation._('NTP Server'), dataIndex: 'ntp_server', width: 50, hidden: true  },
            { resizable: true, id: 'webserver_type', header: this.translation._('Webserver Type'), dataIndex: 'webserver_type', width: 30, hidden: true },
            { resizable: true, id: 'https_port', header: this.translation._('HTTPS Port'), dataIndex: 'https_port', width: 10, hidden: true  },
            { resizable: true, id: 'http_user', header: this.translation._('HTTP User'), dataIndex: 'http_user', width: 15, hidden: true },
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            {
                resizable: true,
                id: 'name',
                header: this.translation._('Name'),
                dataIndex: 'name',
                width: 80
            }, 
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 350 },
            { resizable: true, id: 'filter_registrar', header: this.translation._('Filter Registrar'), dataIndex: 'filter_registrar', width: 10, hidden: true },
            { resizable: true, id: 'callpickup_dialoginfo', header: this.translation._('CP Dialoginfo'), dataIndex: 'callpickup_dialoginfo', width: 10, hidden: true },
            { resizable: true, id: 'pickup_indication', header: this.translation._('Pickup Indic.'), dataIndex: 'pickup_indication', width: 10, hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteLocation.setDisabled(true);
                this.actions.editLocation.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteLocation.setDisabled(false);
                this.actions.editLocation.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteLocation.setDisabled(false);
                this.actions.editLocation.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Location_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No location to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuLocation', 
                items: [
                    this.actions.editLocation,
                    this.actions.deleteLocation,
                    '-',
                    this.actions.addLocation 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('locationWindow', 'index.php?method=Voipmanager.editLocation&locationId=' + record.data.id, 550, 600);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Location_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteLocation();
             }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function(_node)
    {
        var dataStore = Ext.getCmp('Voipmanager_Location_Grid').getStore();
        
        dataStore.baseParams.method = 'Voipmanager.getSnomLocations';
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function(_node) 
    {
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Location_Toolbar') {
            this.initComponent();
            this.displayLocationToolbar();
            this.displayLocationGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Location_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Location_Grid').getStore().reload()", 200);
        }
    }
};



Tine.Voipmanager.Location.EditDialog =  {

        locationRecord: null,
        
        updateLocationRecord: function(_locationData)
        {            
            if(_locationData.admin_mode == 'true') {
                Ext.getCmp('admin_mode_switch').expand();
            }  
            if(_locationData.admin_mode == 'false') {
                Ext.getCmp('admin_mode_switch').collapse();
            }

            if(_locationData.webserver_type == 'off') {
                Ext.getCmp('enable_webserver_switch').collapse();
            }  

            if (_locationData.webserver_type == 'http') {
                Ext.getCmp('https_port').disable();
            }         

            if (_locationData.webserver_type == 'https') {
                Ext.getCmp('http_port').disable();
            }         
            
            this.locationRecord = new Tine.Voipmanager.Model.Location(_locationData);
        },
        
        deleteLocation: function(_button, _event)
        {
            var locationIds = Ext.util.JSON.encode([this.locationRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteSnomLocations', 
                    locationIds: locationIds
                },
                text: 'Deleting location...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Location.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the location.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editLocationForm').getForm();
console.log(this.locationRecord);
            if(form.isValid()) {
                form.updateRecord(this.locationRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomLocation', 
                        locationData: Ext.util.JSON.encode(this.locationRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Location) {
                            window.opener.Tine.Voipmanager.Location.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateLocationRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.locationRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save location.'); 
                    },
                    scope: this 
                });
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
        },

        saveChanges: function(_button, _event) 
        {
            this.applyChanges(_button, _event, true);
        },
        
        editLocationDialog: [{
            layout:'fit',
            border: false,
            autoHeight: true,
            anchor: '100% 100%',
            items:[{            
                layout:'form',
                //frame: true,
                border:false,
                anchor: '100%',
                items: [{
                        xtype: 'textfield',
                        fieldLabel: 'Name',
                        name: 'name',
                        maxLength: 80,
                        anchor:'100%',
                        allowBlank: false
                    } , {
                        xtype:'textarea',
                        name: 'description',
                        fieldLabel: 'Description',
                        grow: false,
                        preventScrollbars:false,
                        anchor:'100%',
                        height: 30
                    } , {
                        xtype: 'textfield',
                        vtype: 'url',
                        fieldLabel: 'Settings URL',
                        name: 'setting_server',
                        maxLength: 255,
                        anchor:'100%',
                        allowBlank: false
                    } , {
                        xtype: 'textfield',
                        vtype: 'url',
                        fieldLabel: 'Firmware URL',
                        name: 'firmware_status',
                        maxLength: 255,
                        anchor:'100%',
                        allowBlank: false
                    } , {
                        xtype: 'textfield',
                        vtype: 'url',
                        fieldLabel: 'Base Download URL',
                        name: 'base_download_url',
                        maxLength: 255,
                        anchor:'100%',
                        allowBlank: false
                    } , {
                        layout:'column',
                        border:false,
                        anchor: '100%',
                        items: [{
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{
                                xtype: 'combo',
                                fieldLabel: 'Update Policy',
                                name: 'update_policy',
                                mode: 'local',
                                displayField:'policy',
                                valueField:'key',
                                anchor:'98%',                    
                                triggerAction: 'all',
                                allowBlank: false,
                                editable: false,
                                store: new Ext.data.SimpleStore({
                                    fields: ['key','policy'],
                                    data: [
	                                    ['auto_update', 'auto update'], 
	                                    ['ask_for_update', 'ask for update'],  
	                                    ['never_update_firm', 'never update firm'],  
	                                    ['never_update_boot', 'never update boot'],  
	                                    ['settings_only', 'settings only'],  
	                                    ['never_update', 'never update']
                                    ]
                                })
                            }]
                        } , {
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{
                                xtype: 'numberfield',
                                fieldLabel: 'Firmware Interval',
                                name: 'firmware_interval',
                                maxLength: 11,
                                anchor:'100%',
                                allowBlank: false
                           }]
                       }]
                    } , {
                        xtype:'fieldset',
                        checkboxToggle:false,
                        checkboxName: 'ntpSetting',
                        id: 'ntp_setting',
                        title: 'NTP Server',
                        autoHeight:true,
                        anchor: '100%',
                        defaults: {anchor:'100%'},
                        items :[{
                            layout:'column',
                            border:false,
                            anchor: '100%',
                            items: [{
                                columnWidth: .7,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items:[{
                                    xtype: 'textfield',
                                    fieldLabel: 'NTP Server Address',
                                    name: 'ntp_server',
                                    maxLength: 255,
                                    anchor:'98%',
                                    allowBlank: false
                                }]
                            }, {
                                columnWidth: .3,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items:[{
                                    xtype: 'numberfield',
                                    fieldLabel: 'NTP Refresh',
                                    name: 'ntp_refresh',
                                    maxLength: 20,
                                    anchor:'100%'
                                }]
                            }]
                        }, new Ext.form.ComboBox({
                            fieldLabel: 'Timezone',
                            id: 'timezone',
                            name: 'timezone',
                            mode: 'local',
                            displayField:'timezone',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: Tine.Voipmanager.Data.loadTimezoneData()
                        })]
                    }, {
                        xtype:'fieldset',
                        checkboxToggle:true,
                        checkboxName: 'admin_mode',
                        id: 'admin_mode_switch',
                        listeners: {
                            expand: function() {
                                Ext.getCmp('admin_mode').setValue(true);
                            },
                            collapse: function() {
                                Ext.getCmp('admin_mode').setValue(false);
                            }
                        },
                        title: 'Enable admin mode',
                        autoHeight:true,
                        anchor:'100%',
                        defaults: {anchor:'100%'},
                        items :[{
                            xtype: 'hidden',
                            name: 'admin_mode',
                            id: 'admin_mode'
                        },{
                            xtype: 'numberfield',
                            fieldLabel: 'Admin Mode Password',
                            name: 'admin_mode_password',
                            /*inputType: 'password',*/
                            maxLength: 20,
                            anchor:'100%'
                       }]
                    }, {
                        xtype:'fieldset',
                        checkboxToggle:true,
                        checkboxName: 'enableWebserver',                        
                        title: 'Enable webserver',
                        autoHeight:true,
                        id: 'enable_webserver_switch',
                        listeners: {
                            collapse: function() {
                                Ext.getCmp('webserver_type').setValue('off');
                            },
                            expand: function() {
                                if(Ext.getCmp('webserver_type').getValue() == 'off') {
                                    Ext.getCmp('webserver_type').setValue('http_https');
                                }
                            }
                        },                        
                        defaults: {anchor:'100%'},
                        items :[{
                        layout:'column',
                        border:false,
                        anchor: '100%',
                        items: [{
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{
                                xtype: 'combo',
                                fieldLabel: 'Webserver Type',
                                name: 'webserver_type',
                                id: 'webserver_type',
                                mode: 'local',
                                displayField:'wwwtype',
                                valueField:'key',
                                listeners: {
                                    select: function(_field, _newValue, _oldValue) {   
                                        if (_newValue.data.key == 'https') {
                                            Ext.getCmp('http_port').disable();
                                            Ext.getCmp('https_port').enable();                                            
                                        }
                                        if (_newValue.data.key == 'http') {
                                            Ext.getCmp('http_port').enable();
                                            Ext.getCmp('https_port').disable();                                            
                                        }  
                                        if (_newValue.data.key == 'http_https') {
                                            Ext.getCmp('http_port').enable();
                                            Ext.getCmp('https_port').enable();                                            
                                        }                                          
                                    }
                                },
                                anchor:'98%',                    
                                triggerAction: 'all',
                                allowBlank: false,
                                editable: false,
                                store: new Ext.data.SimpleStore({
                                    fields: ['key','wwwtype'],
                                    data: [
                                            ['https', 'https'],
                                            ['http', 'http'],
                                            ['http_https', 'http https']
                                    ]
                                })
                            }]
                        } , {
	                        columnWidth: .5,
	                        layout: 'form',
	                        border: false,
	                        anchor: '100%',
	                        items:[{
                                layout:'column',
                                border:false,
                                anchor: '100%',
                                items: [{
                                    columnWidth: .5,
                                    layout: 'form',
                                    border: false,
                                    anchor: '100%',
                                    items:[{                                    
                                        xtype: 'textfield',
                                        fieldLabel: 'HTTP Port',
                                        name: 'http_port',
                                        id: 'http_port',
                                        maxLength: 6,
                                        anchor:'98%',
                                        allowBlank: true
                                    }]
                                } , {
                                    columnWidth: .5,
                                    layout: 'form',
                                    border: false,
                                    anchor: '100%',
                                    items:[{                                    
                                        xtype: 'textfield',
                                        fieldLabel: 'HTTPS Port',
                                        name: 'https_port',
                                        id: 'https_port',
                                        maxLength: 6,
                                        anchor:'100%',
                                        allowBlank: true
                                    }]
                                }]
                            }]
                        }]
                    },{
                        layout:'column',
                        border:false,
                        anchor: '100%',
                        items: [{
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{
                                xtype: 'textfield',
                                fieldLabel: 'HTTP User',
                                name: 'http_user',
                                maxLength: 20,
                                anchor:'98%'
                            }]
                        },{
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{
                                xtype: 'textfield',
                                fieldLabel: 'HTTP Password',
                                name: 'http_pass',
                                inputType: 'textfield',
                                maxLength: 20,
                                anchor:'100%'
                            }]
                        }]
                    }]
                }]
            }]
        }],
        
        updateToolbarButtons: function()
        {
            if(this.locationRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editLocationForm').action_delete.enable();
            }
        },
        
        display: function(_locationData) 
        {           
            if (!arguments[0]) {
                var _locationData = {};
            }        
        
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editLocationForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteLocation,
                items: this.editLocationDialog
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            //if (!arguments[0]) var task = {};
            this.updateLocationRecord(_locationData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.locationRecord);
        }
   
};