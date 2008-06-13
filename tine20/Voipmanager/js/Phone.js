/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Phones');

Tine.Voipmanager.Phones.Main = {
       
    actions: {
        addPhone: null,
        editPhone: null,
        deletePhone: null
    },
    
    handlers: {
        /**
         * onclick handler for addPhone
         */
        addPhone: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=', 500, 350);
        },

        /**
         * onclick handler for editPhone
         */
        editPhone: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
            var phoneId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=' + phoneId, 500, 350);
        },
        
        /**
         * onclick handler for deletePhone
         */
        deletePhone: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected phones?', function(_button){
                if (_button == 'yes') {
                
                    var phoneIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        phoneIds.push(selectedRows[i].id);
                    }
                    
                    phoneIds = Ext.util.JSON.encode(phoneIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deletePhones',
                            _phoneIds: phoneIds
                        },
                        text: 'Deleting phone(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.');
                        }
                    });
                }
            });
        }    
    },
    
    renderer: {
        contactTid: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            //switch(_data) {
            //    default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
            //}
        }       
    },

    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Voipmanager');
    
        this.actions.addPhone = new Ext.Action({
            text: this.translation._('add phone'),
            handler: this.handlers.addPhone,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editPhone = new Ext.Action({
            text: this.translation._('edit phone'),
            disabled: true,
            handler: this.handlers.editPhone,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deletePhone = new Ext.Action({
            text: this.translation._('delete phone'),
            disabled: true,
            handler: this.handlers.deletePhone,
            iconCls: 'action_delete',
            scope: this
        });
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        /*menu.add(
            {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
        );*/

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
    
    displayPhonesToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Phones_Grid').getStore().load({
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
     
        var phoneToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Phones_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addPhone, 
                this.actions.editPhone,
                this.actions.deletePhone,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(phoneToolbar);
    },

    displayPhonesGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Phone,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        Ext.StoreMgr.add('PhonesStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying phones {0} - {1} of {2}'),
            emptyMsg: this.translation._("No phones to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'macaddress', header: this.translation._('MAC address'), dataIndex: 'macaddress',width: 60 },
            { resizable: true, id: 'location', header: this.translation._('Location'), dataIndex: 'location',width: 40 },
            { resizable: true, id: 'template', header: this.translation._('Template'), dataIndex: 'template',width: 40 },            
            { resizable: true, id: 'ipaddress', header: this.translation._('phone IP address'), dataIndex: 'ipaddress', width: 110 },
            { resizable: true, id: 'last_modified_time', header: this.translation._('last modified'), dataIndex: 'last_modified_time', width: 100, hidden: true },
            {
                resizable: true,
                id: 'description',
                header: this.translation._('description'),
                dataIndex: 'description',
                width: 250
            }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deletePhone.setDisabled(true);
                this.actions.editPhone.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Phones_Grid',
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
                emptyText: 'No phones to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuPhones', 
                items: [
                    this.actions.editPhone,
                    this.actions.deletePhone,
                    '-',
                    this.actions.addPhone 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Voipmanager.editPhone&phoneId=' + record.data.id, 500, 350);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Phones_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deletePhone();
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
        var dataStore = Ext.getCmp('Voipmanager_Phones_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.dataPanelType) {
            case 'phones':
                dataStore.baseParams.method = 'Voipmanager.getPhones';
                break;
                
            case 'location':
                dataStore.baseParams.method = 'Voipmanager.getLocation';
                break;                
                
            case 'templates':
                dataStore.baseParams.method = 'Voipmanager.getTemplates';
                break;                 
                
            case 'lines':
                dataStore.baseParams.method = 'Voipmanager.getLines';
                break;                
                
            case 'settings':
                dataStore.baseParams.method = 'Voipmanager.getSettings';
                break;                
                
            case 'software':
                dataStore.baseParams.method = 'Voipmanager.getSoftware';
                break;                                                                
        }
        
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Phones_Toolbar') {
            this.initComponent();
            this.displayPhonesToolbar();
            this.displayPhonesGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Phones_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload()", 200);
        }
    }
};

Tine.Voipmanager.Phones.EditDialog =  {

        phoneRecord: null,
        
        updatePhoneRecord: function(_phoneData)
        {
                       
            if(_phoneData.last_modified_time && _phoneData.last_modified_time !== null) {
                _phoneData.last_modified_time = Date.parseDate(_phoneData.last_modified_time, 'c');
            }
            this.phoneRecord = new Tine.Voipmanager.Model.Phone(_phoneData);
        },
        
        
        deletePhone: function(_button, _event)
        {
            var phoneIds = Ext.util.JSON.encode([this.phoneRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deletePhones', 
                    phoneIds: phoneIds
                },
                text: 'Deleting phone...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Phones.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editPhoneForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.phoneRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.savePhone', 
                        phoneData: Ext.util.JSON.encode(this.phoneRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Phones) {
                            window.opener.Tine.Voipmanager.Phones.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updatePhoneRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.phoneRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save phone.'); 
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
                  
                  
        editPhoneDialog:  [{
            layout:'fit',
            //frame: true,
            border:false,
            anchor: '100% 100%',
            items: [{
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
                        fieldLabel: 'MAC Address',
                        name: 'macaddress',
                        maxLength: 12,
                        anchor:'98%',
                        allowBlank: false
                    } , {
                        xtype: 'combo',
                        fieldLabel: 'Template',
                        name: 'template_id',
                        id: 'template_id',
                        mode: 'remote',
                        displayField:'name',
                        valueField:'id',
                        anchor:'98%',                    
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: Tine.Voipmanager.Data.loadTemplateData(),
                        listeners: {
                            storeLoaded: function(){
                                this.setValue(this.value);
                            }
                        }                        
                    }, {
                        xtype: 'combo',
                        fieldLabel: 'Location',
                        name: 'location_id',
                        id: 'location_id',
                        mode: 'remote',
                        displayField:'name',
                        valueField:'id',
                        anchor:'98%',                    
                        triggerAction: 'all',
                        editable: false,
                        forceSelection: true,
                        store: Tine.Voipmanager.Data.loadLocationData(),
                        listeners: {
                            storeLoaded: function(){
                                this.setValue(this.value);
                            }
                        }                        
                    }]
                }, {
                    columnWidth: .5,
                    layout: 'form',
                    border: false,
                    anchor: '100%',
                    autoHeight: true,
                    items:[{
                        xtype:'textarea',
                        name: 'description',
                        fieldLabel: 'Description',
                        grow: false,
                        preventScrollbars:false,
                        anchor:'100%',
                        height: 105
                    }]
                }]
            },{
                layout:'form',
                border:false,
                anchor: '100%',
                items: [{                
                    xtype:'fieldset',
                    checkboxToggle:false,
                    id: 'infos',
                    title: 'Infos',
                    autoHeight:true,
                    anchor: '100%',
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
                                xtype: 'textfield',
                                fieldLabel: 'Current IP Address',
                                name: 'ip_address',
                                maxLength: 20,
                                anchor:'98%',
                                readOnly: true                        
                            },{
                                xtype: 'textfield',
                                fieldLabel: 'Current Software Version',
                                name: 'software_version',
                                maxLength: 20,
                                anchor:'98%',
                                readOnly: true                        
                            },{
                                xtype: 'textfield',
                                fieldLabel: 'Current Phone Model',
                                name: 'phone_model',
                                maxLength: 20,
                                anchor:'98%',
                                readOnly: true   
                            }]
                        },{         
                            columnWidth: .5,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items:[{                                    
                                xtype: 'textfield',
                                fieldLabel: 'Settings Loaded at',
                                name: 'settings_loadingtime',
                                maxLength: 20,
                                anchor:'100%',
                                readOnly: true                        
                            },{
                                xtype: 'textfield',
                                fieldLabel: 'Firmware last checked at',
                                name: 'firmware_update_time',
                                maxLength: 20,
                                anchor:'100%',
                                readOnly: true                        
                            }]
                        }]
                    }]
                }]
            }]
        }],
        

        
        updateToolbarButtons: function()
        {
            if(this.phoneRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editPhoneForm').action_delete.enable();
            }
        },
        
        display: function(_phoneData) 
        {
            if (!arguments[0]) {
                var _phoneData = {};
            }

            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editPhoneForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deletePhone,
                items: [{
                    layout:'fit',
                    border: false,
                    autoHeight: true,
                    anchor: '100% 100%',
                    items: this.editPhoneDialog
                }]
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
                    
            this.updatePhoneRecord(_phoneData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.phoneRecord);
            
            Ext.getCmp('template_id').store.load({
                params: {
                    query: ''
                },
                callback: function(){
                    Ext.getCmp('template_id').fireEvent('storeLoaded');
                }
            });

            Ext.getCmp('location_id').store.load({
                params: {
                    query: ''
                },
                callback: function(){
                    Ext.getCmp('location_id').fireEvent('storeLoaded');
                }
            });           
        } 
};


