/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Setting.js 3100 2008-06-26 11:55:07Z twadewitz $
 *
 */

Ext.namespace('Tine.Voipmanager.Snom.Settings');

Tine.Voipmanager.Snom.Settings.Main = {
       
    actions: {
        addSetting: null,
        editSetting: null,
        deleteSetting: null
    },
    
    handlers: {
        /**
         * onclick handler for addSetting
         */
        addSetting: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('settingsWindow', 'index.php?method=Voipmanager.editSnomSetting&settingId=', 740, 330);
        },

        /**
         * onclick handler for editSetting
         */
        editSetting: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Settings_Grid').getSelectionModel().getSelections();
            var settingId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('settingsWindow', 'index.php?method=Voipmanager.editSnomSetting&settingId=' + settingId, 740, 330);
        },
        
        /**
         * onclick handler for deleteSetting
         */
        deleteSetting: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected settings?', function(_button){
                if (_button == 'yes') {
                
                    var settingIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Settings_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        settingIds.push(selectedRows[i].id);
                    }
                    
                    settingIds = Ext.util.JSON.encode(settingIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteSnomSettings',
                            _settingIds: settingIds
                        },
                        text: 'Deleting setting(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Settings_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the setting.');
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
    
        this.actions.addSetting = new Ext.Action({
            text: this.translation._('add setting'),
            handler: this.handlers.addSetting,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editSetting = new Ext.Action({
            text: this.translation._('edit setting'),
            disabled: true,
            handler: this.handlers.editSetting,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteSetting = new Ext.Action({
            text: this.translation._('delete setting'),
            disabled: true,
            handler: this.handlers.deleteSetting,
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
    
    displaySettingsToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Settings_Grid').getStore().load({
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
     
        var settingToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Settings_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addSetting, 
                this.actions.editSetting,
                this.actions.deleteSetting,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(settingToolbar);
    },

    displaySettingsGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Snom.Setting,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('id', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        Ext.StoreMgr.add('SettingsStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying settings {0} - {1} of {2}'),
            emptyMsg: this.translation._("No settings to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'name', header: this.translation._('name'), dataIndex: 'name', width: 150 },
            { resizable: true, id: 'description', header: this.translation._('description'), dataIndex: 'description', width: 200 },
            { resizable: true, id: 'web_language', header: this.translation._('web_language'), dataIndex: 'web_language', width: 10, hidden: true },
            { resizable: true, id: 'language', header: this.translation._('language'), dataIndex: 'language', width: 10, hidden: true },
            { resizable: true, id: 'display_method', header: this.translation._('display_method'), dataIndex: 'display_method', width: 10, hidden: true },
            { resizable: true, id: 'mwi_notification', header: this.translation._('mwi_notification'), dataIndex: 'mwi_notification', width: 10, hidden: true },
            { resizable: true, id: 'mwi_dialtone', header: this.translation._('mwi_dialtone'), dataIndex: 'mwi_dialtone', width: 10, hidden: true },
            { resizable: true, id: 'headset_device', header: this.translation._('headset_device'), dataIndex: 'headset_device', width: 10, hidden: true },
            { resizable: true, id: 'message_led_other', header: this.translation._('message_led_other'), dataIndex: 'message_led_other', width: 10, hidden: true },
            { resizable: true, id: 'global_missed_counter', header: this.translation._('global_missed_counter'), dataIndex: 'global_missed_counter', width: 10, hidden: true },
            { resizable: true, id: 'scroll_outgoing', header: this.translation._('scroll_outgoing'), dataIndex: 'scroll_outgoing', width: 10, hidden: true },
            { resizable: true, id: 'show_local_line', header: this.translation._('show_local_line'), dataIndex: 'show_local_line', width: 10, hidden: true },
            { resizable: true, id: 'show_call_status', header: this.translation._('show_call_status'), dataIndex: 'show_call_status', width: 10, hidden: true },
            { resizable: true, id: 'call_waiting', header: this.translation._('call_waiting'), dataIndex: 'call_waiting', width: 25, hidden: true }
           
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteSetting.setDisabled(true);
                this.actions.editSetting.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteSetting.setDisabled(false);
                this.actions.editSetting.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteSetting.setDisabled(false);
                this.actions.editSetting.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Settings_Grid',
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
                emptyText: 'No settings to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuSettings', 
                items: [
                    this.actions.editSetting,
                    this.actions.deleteSetting,
                    '-',
                    this.actions.addSetting 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('settingsWindow', 'index.php?method=Voipmanager.editSnomSetting&settingId=' + record.data.id, 740, 330);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Settings_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteSetting();
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
        var dataStore = Ext.getCmp('Voipmanager_Settings_Grid').getStore();

        dataStore.baseParams.method = 'Voipmanager.getSnomSettings';

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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Settings_Toolbar') {
            this.initComponent();
            this.displaySettingsToolbar();
            this.displaySettingsGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Settings_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Settings_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Snom.Settings.EditDialog =  {

        settingRecord: null,
        
        _templateData: null,
        
        
        updateWritableFields: function(_settingData)
        {
             _writableFields = [["web_language_writable"],["language_writable"],["display_method_writable"],["call_waiting_writable"],["mwi_notification_writable"],["mwi_dialtone_writable"],["headset_device_writable"],["message_led_other_writable"],["global_missed_counter_writable"],["scroll_outgoing_writable"],["show_local_line_writable"],["show_call_status_writable"]];               
            
            Ext.each(_writableFields, function(_item, _index, _array) {
                if (Ext.getCmp(_item)) {
                    _settingData.data[_item] = Ext.getCmp(_item).getValue();
                }

                this.settingRecord = new Tine.Voipmanager.Model.Snom.Setting(_settingData);
            });
            
        },
        
        
        updateSettingRecord: function(_settingData)
        {                     
            this.settingRecord = new Tine.Voipmanager.Model.Snom.Setting(_settingData);
        },
        
        
        deleteSetting: function(_button, _event)
        {
            var settingIds = Ext.util.JSON.encode([this.settingRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteSnomSettings', 
                    settingIds: settingIds
                },
                text: 'Deleting setting...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Snom.Settings.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the setting.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editSettingForm').getForm();

            
            

            if(form.isValid()) {
                
                this.updateWritableFields(this.settingRecord);
                
                form.updateRecord(this.settingRecord);
               
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomSetting', 
                        settingData: Ext.util.JSON.encode(this.settingRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Snom.Settings) {
                            window.opener.Tine.Voipmanager.Snom.Settings.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateSettingRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.settingRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save setting.'); 
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
                
                  
        editSettingMainDialog: function(_settingData){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            Ext.QuickTips.init();                         
            
        
            var _dialog = {
                title: translation._('SettingsMain'),
                layout: 'border',
                anchor: '100% 100%',
                layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items: [{
                    layout: 'hfit',
                    containsScrollbar: false,
                    //margins: '0 18 0 5',
                    autoScroll: false,
                    id: 'editSettingMainDialog',
                    region: 'center',
                    items: [{
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        height: 50,
                        items: [{
                            columnWidth: 0.35,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: translation._('name'),
                                name: 'name',
                                maxLength: 150,
                                anchor: '98%',
                                allowBlank: false
                            }]
                        }, {
                            columnWidth: 0.65,
                            layout: 'form',
                            border: false,
                            anchor: '98%',
                            autoHeight: true,
                            items: [{
                                xtype: 'textfield',
                                name: 'description',
                                        tooltip: 'bla',                                
                                fieldLabel: translation._('Description'),
                                maxLength: 255,
                                anchor: '100%'
                            }]
                        }]
                    }, {
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: [{
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [
									{
									xtype: 'lockCombo',
                                    fieldLabel: translation._('web_language'),
                                    name: 'web_language',
                                    id: 'web_language',
									hiddenFieldId: 'web_language_writable',
									hiddenFieldData: _settingData.web_language_writable,
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                        
                                            ['English', Locale.getTranslationData('Language', 'en')],
                                            ['Deutsch', Locale.getTranslationData('Language', 'de')],
                                            ['Espanol', Locale.getTranslationData('Language', 'es')],
                                            ['Francais', Locale.getTranslationData('Language', 'fr')],
                                            ['Italiano', Locale.getTranslationData('Language', 'it')],
                                            ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                                            ['Portugues', Locale.getTranslationData('Language', 'pt')],
                                            ['Suomi', Locale.getTranslationData('Language', 'fi')],
                                            ['Svenska', Locale.getTranslationData('Language', 'sv')],
                                            ['Dansk', Locale.getTranslationData('Language', 'da')],
                                            ['Norsk', Locale.getTranslationData('Language', 'no')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('language'),
                                    name: 'language',
                                    id: 'language',
									hiddenFieldId: 'language_writable',
									hiddenFieldData: _settingData.language_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                                                                               
                                            ['English', Locale.getTranslationData('Language', 'en')],
                                            ['English(UK)', Locale.getTranslationData('Language', 'en_GB')],
                                            ['Deutsch', Locale.getTranslationData('Language', 'de')],
                                            ['Espanol', Locale.getTranslationData('Language', 'es')],
                                            ['Francais', Locale.getTranslationData('Language', 'fr')],
                                            ['Italiano', Locale.getTranslationData('Language', 'it')],
                                            ['Cestina', Locale.getTranslationData('Language', 'cs')],
                                            ['Nederlands', Locale.getTranslationData('Language', 'nl')],
                                            ['Polski', Locale.getTranslationData('Language', 'pl')],
                                            ['Portugues', Locale.getTranslationData('Language', 'pt')],
                                            ['Slovencina', Locale.getTranslationData('Language', 'sl')],
                                            ['Suomi', Locale.getTranslationData('Language', 'fi')],
                                            ['Svenska', Locale.getTranslationData('Language', 'sv')],
                                            ['Dansk', Locale.getTranslationData('Language', 'da')],
                                            ['Norsk', Locale.getTranslationData('Language', 'no')],                                            
                                            ['Japanese', Locale.getTranslationData('Language', 'ja')],
                                            ['Chinese', Locale.getTranslationData('Language', 'zh')]
                                        ]
                                    })
                                }]
                            },{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('display_method'),
                                    name: 'display_method',
                                    id: 'display_method',
									hiddenFieldId: 'display_method_writable',
									hiddenFieldData: _settingData.display_method_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                                                                
                                            ['full_contact', translation._('whole url')],
                                            ['display_name', translation._('name')],
                                            ['display_number', translation._('number')],
                                            ['display_name_number', translation._('name + number')],
                                            ['display_number_name', translation._('number + name')]
                                        ]
                                    })
                                }]
                            }]
                        },{          
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('call_waiting'),
                                    name: 'call_waiting',
                                    id: 'call_waiting',
									hiddenFieldId: 'call_waiting_writable',
									hiddenFieldData: _settingData.call_waiting_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                        
                                            ['on', translation._('on')],
                                            ['visual', translation._('visual')],
                                            ['ringer', translation._('ringer')],
                                            ['off', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('mwi_notification'),
                                    name: 'mwi_notification',
                                    id: 'mwi_notification',
									hiddenFieldId: 'mwi_notification_writable',
									hiddenFieldData: _settingData.mwi_notification_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                        
                                            ['silent', translation._('silent')],
                                            ['beep', translation._('beep')],
                                            ['reminder', translation._('reminder')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('mwi_dialtone'),
                                    name: 'mwi_dialtone',
                                    id: 'mwi_dialtone',
									hiddenFieldId: 'mwi_dialtone_writable',
									hiddenFieldData: _settingData.mwi_dialtone_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                        
                                            ['normal', translation._('normal')],
                                            ['stutter', translation._('stutter')]
                                        ]
                                    })
                                }]
                            }]
                        }, {          
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('headset_device'),
                                    name: 'headset_device',
                                    id: 'headset_device',
									hiddenFieldId: 'headset_device_writable',
									hiddenFieldData: _settingData.headset_device_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],                                        
                                            ['none', translation._('none')],
                                            ['headset_rj', translation._('headset_rj')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                        xtype: 'lockCombo',
                                        fieldLabel: translation._('message_led_other'),
                                        name: 'message_led_other',
                                        id: 'message_led_other',
	    								hiddenFieldId: 'message_led_other_writable',
    									hiddenFieldData: _settingData.message_led_other_writable,                                        
                                        mode: 'local',
                                        displayField: 'name',
                                        valueField: 'id',
                                        anchor: '95%',
                                        triggerAction: 'all',
                                        editable: false,
                                        forceSelection: true,
                                        store: new Ext.data.SimpleStore({
                                            id: 'id',
                                            fields: ['id', 'name'],
                                            data: [
                                                [ null,  translation._('- factory default -')],
                                                ['1', translation._('on')],
                                                ['0', translation._('off')]
                                            ]
                                        })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('global_missed_counter'),
                                    name: 'global_missed_counter',
                                    id: 'global_missed_counter',
									hiddenFieldId: 'global_missed_counter_writable',
									hiddenFieldData: _settingData.global_missed_counter_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')], 
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }]
                        }, {          
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('scroll_outgoing'),
                                    name: 'scroll_outgoing',
                                    id: 'scroll_outgoing',
									hiddenFieldId: 'scroll_outgoing_writable',
									hiddenFieldData: _settingData.scroll_outgoing_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('show_local_line'),
                                    name: 'show_local_line',
                                    id: 'show_local_line',
									hiddenFieldId: 'show_local_line_writable',
									hiddenFieldData: _settingData.show_local_line_writable,                                    
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'lockCombo',
                                    fieldLabel: translation._('show_call_status'),
                                    name: 'show_call_status',
                                    id: 'show_call_status',
									hiddenFieldId: 'show_call_status_writable',
									hiddenFieldData: _settingData.show_call_status_writable,                                        
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '100%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            [ null,  translation._('- factory default -')],
                                            ['1', translation._('on')],
                                            ['0', translation._('off')]
                                        ]
                                    })
                                }]
                            }]
                        }]
                    }]   // form 
                }]   // center
            };
            
            return _dialog;   
        },
        
       
        updateToolbarButtons: function()
        {
            if(this.settingRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editSettingForm').action_delete.enable();
            }
        },
        
        display: function(_settingData, _snomLines, _lines, _templates, _locations) 
        {
            
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editSettingForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteSetting,
                items: [{
                    defaults: {
                        frame: true
                    },
                    xtype: 'tabpanel',
                    border: false,
                    height: 100,
                    //autoHeight: true,
                    anchor: '100% 100%',
                    plain:true,
                    activeTab: 0,
                    id: 'editSettingTabPanel',
                    layoutOnTabChange:true,
                    deferredRender: false,                         
                    items:[
                        this.editSettingMainDialog(_settingData)
                    ]
                }]
            });

            
            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                items: dialog
            });
               
            this.updateSettingRecord(_settingData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.settingRecord);
            
        } 
};


