    /*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: MyPhones.js 3306 2008-07-10 14:18:42Z t.wadewitz@metaways.de $
 *
 */

Ext.namespace('Tine.Voipmanager.MyPhones');

Tine.Voipmanager.MyPhones.Main = {
       
    actions: {
        editMyPhone: null
    },
    
    handlers: {

        /**
         * onclick handler for editMyPhone
         */
        editMyPhone: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_MyPhones_Grid').getSelectionModel().getSelections();
            var myphoneId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('myPhonesWindow', 'index.php?method=Voipmanager.editMyPhone&phoneId=' + myphoneId, 700, 250);
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
           
        this.actions.editMyPhone = new Ext.Action({
            text: this.translation._('edit myphone'),
            disabled: true,
            handler: this.handlers.editMyPhone,
            iconCls: 'action_edit',
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
    
    displayMyPhonesToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_MyPhones_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50,
                        accountId: Tine.Tinebase.Registry.get('currentAccount').accountId 
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
     
        var myphoneToolbar = new Ext.Toolbar({
            id: 'Voipmanager_MyPhones_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.editMyPhone,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(myphoneToolbar);
    },

    displayMyPhonesGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Snom.Phone,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        Ext.StoreMgr.add('MyPhonesStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying myphones {0} - {1} of {2}'),
            emptyMsg: this.translation._("No myphones to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'macaddress', header: this.translation._('MAC address'), dataIndex: 'macaddress',width: 50 },
            { resizable: true, id: 'description', header: this.translation._('description'), dataIndex: 'description' },
            {
                resizable: true,
                id: 'location_id',
                header: this.translation._('Location'),
                dataIndex: 'location_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.location;
                }
            },
            {
                resizable: true,
                id: 'template_id',
                header: this.translation._('Template'),
                dataIndex: 'template_id',
                width: 70,
                renderer: function(_data,_obj, _rec) {
                    return _rec.data.template;
                }                                
            },            
            { resizable: true, id: 'ipaddress', header: this.translation._('IP Address'), dataIndex: 'ipaddress', width: 50 },            
            { resizable: true, id: 'current_software', header: this.translation._('Software'), dataIndex: 'current_software', width: 50 },
            { resizable: true, id: 'current_model', header: this.translation._('current model'), dataIndex: 'current_model', width: 70, hidden: true },
            { resizable: true, id: 'redirect_event', header: this.translation._('redirect event'), dataIndex: 'redirect_event', width: 70, hidden: true },
            { resizable: true, id: 'redirect_number', header: this.translation._('redirect number'), dataIndex: 'redirect_number', width: 100, hidden: true },
            { resizable: true, id: 'redirect_time', header: this.translation._('redirect time'), dataIndex: 'redirect_time', width: 25, hidden: true },
            { resizable: true, id: 'settings_loaded_at', header: this.translation._('settings loaded at'), dataIndex: 'settings_loaded_at', width: 100, hidden: true },            
            { resizable: true, id: 'last_modified_time', header: this.translation._('last modified'), dataIndex: 'last_modified_time', width: 100, hidden: true }            
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.editMyPhone.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.editMyPhone.setDisabled(true);
            } else {
                // only one row selected
                this.actions.editMyPhone.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_MyPhones_Grid',
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
                emptyText: 'No myphones to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuMyPhones', 
                items: [
                    this.actions.editMyPhone
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('myPhonesWindow', 'index.php?method=Voipmanager.editMyPhone&phoneId=' + record.data.id, 700, 250);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_MyPhones_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteMyPhone();
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
        var dataStore = Ext.getCmp('Voipmanager_MyPhones_Grid').getStore();

        dataStore.baseParams.method = 'Voipmanager.getMyPhones';

        dataStore.load({
            params:{
                start:0, 
                limit:50,
                accountId: Tine.Tinebase.Registry.get('currentAccount').accountId 
            }
        });
    },

    show: function(_node) 
    {
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_MyPhones_Toolbar') {
            this.initComponent();
            this.displayMyPhonesToolbar();
            this.displayMyPhonesGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_MyPhones_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_MyPhones_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.MyPhones.EditDialog =  {

        myphoneRecord: null,
        
        settingsRecord: null,
        
        updateMyPhoneRecord: function(_myphoneData)
        {                     
            if(_myphoneData.last_modified_time && _myphoneData.last_modified_time !== null) {
                _myphoneData.last_modified_time = Date.parseDate(_myphoneData.last_modified_time, 'c');
            }
            if(_myphoneData.settings_loaded_at && _myphoneData.settings_loaded_at !== null) {
                _myphoneData.settings_loaded_at = Date.parseDate(_myphoneData.settings_loaded_at, 'c');
            }
            if(_myphoneData.firmware_checked_at && _myphoneData.firmware_checked_at !== null) {
                _myphoneData.firmware_checked_at = Date.parseDate(_myphoneData.firmware_checked_at, 'c');
            }
            if(_myphoneData.redirect_event != 'time') {
                Ext.getCmp('redirect_time').disable();
            }
            
            this.myphoneRecord = new Tine.Voipmanager.Model.Snom.Phone(_myphoneData);

        },
        
        
        deleteMyPhone: function(_button, _event)
        {
            var myphoneIds = Ext.util.JSON.encode([this.myphoneRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteMyPhones', 
                    myphoneIds: myphoneIds
                },
                text: 'Deleting myPhone(s)...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.MyPhones.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete myPhone(s).'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editMyPhoneForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.myphoneRecord);
                
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveMyPhone', 
                        phoneData: Ext.util.JSON.encode(this.myphoneRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.MyPhones) {
                            window.opener.Tine.Voipmanager.MyPhones.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateMyPhoneRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.myphoneRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save myphone.'); 
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
                
               
                  
        editMyPhoneDialog: function(_myphoneData){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
        
   
            var _dialog = {
                title: 'MyPhone',
                layout: 'border',
                anchor: '100% 100%',
                layoutOnTabChange: true,
                defaults: {
                    border: true,
                    frame: false
                },
                items: [{
                    region: 'center',
                    autoScroll: true,
                    autoHeight: true,
                    items: [{
                        xtype: 'fieldset',
                        checkboxToggle: false,
                        id: 'redirectionFieldset',
                        title: translation._('redirection'),
                        autoHeight: true,
                        anchor: '100%',
                        defaults: {
                            anchor: '100%'
                        },
                        items: [{
                            layout: 'column',
                            border: false,
                            anchor: '100%',
                            items: [{ 
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'combo',
                                    fieldLabel: translation._('redirect_event'),
                                    name: 'redirect_event',
                                    id: 'redirect_event',                                                                       
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    anchor: '95%',
                                    triggerAction: 'all',
                                    editable: false,
                                    forceSelection: true,
                                    listeners: {
                                        select: function(_combo, _record, _index) {
                                            if (_record.data.id == 'time') {
                                                Ext.getCmp('redirect_time').enable();
                                            }
                                            
                                            if(_record.data.id != 'time') {                                                   
                                                Ext.getCmp('redirect_time').disable();
                                            }
                                        }
                                    },
                                    store: new Ext.data.SimpleStore({
                                        id: 'id',
                                        fields: ['id', 'name'],
                                        data: [
                                            ['all', translation._('all')],
                                            ['busy', translation._('busy')],
                                            ['none', translation._('none')],
                                            ['time', translation._('time')]
                                        ]
                                    })
                                }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'textfield',
                                    fieldLabel: translation._('redirect_number'),
                                    name: 'redirect_number',
                                    id: 'redirect_number',
                                    anchor: '95%'                                
                               }]
                            }, {
                                columnWidth: 0.33,
                                layout: 'form',
                                border: false,
                                anchor: '100%',
                                items: [{
                                    xtype: 'numberfield',
                                    fieldLabel: translation._('redirect_time'),
                                    name: 'redirect_time',
                                    id: 'redirect_time',
                                    anchor: '100%'                                                                     
                               }]
                            }]  
                        }]
                    }]
                }]
            };
            
            return _dialog;   
        },
     	
  
       editMyPhoneSettingsDialog: function(_writable){
        
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            Ext.QuickTips.init();                         
            
        
            var _dialog = {
                title: translation._('Settings'),
                layout: 'border',
                id: 'settingsBorderLayout',
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
									xtype: 'combo',
                                    fieldLabel: translation._('web_language'),
                                    name: 'web_language',
                                    id: 'web_language',
                                    disabled: _writable.web_language,
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('language'),
                                    name: 'language',
                                    id: 'language',
                                    disabled: _writable.language,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('display_method'),
                                    name: 'display_method',
                                    id: 'display_method',
                                    disabled: _writable.display_method,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('call_waiting'),
                                    name: 'call_waiting',
                                    id: 'call_waiting',
                                    disabled: _writable.call_waiting,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('mwi_notification'),
                                    name: 'mwi_notification',
                                    id: 'mwi_notification',
                                    disabled: _writable.mwi_notification,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('mwi_dialtone'),
                                    name: 'mwi_dialtone',
                                    id: 'mwi_dialtone',
                                    disabled: _writable.mwi_dialtone,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('headset_device'),
                                    name: 'headset_device',
                                    id: 'headset_device',
                                    disabled: _writable.headset_device,                                    
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
                                        xtype: 'combo',
                                        fieldLabel: translation._('message_led_other'),
                                        name: 'message_led_other',
                                        id: 'message_led_other',
                                        disabled: _writable.message_led_other,                                        
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('global_missed_counter'),
                                    name: 'global_missed_counter',
                                    id: 'global_missed_counter',
                                    disabled: _writable.global_missed_counter,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('scroll_outgoing'),
                                    name: 'scroll_outgoing',
                                    id: 'scroll_outgoing',                                  
                                    disabled: _writable.scroll_outgoing,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('show_local_line'),
                                    name: 'show_local_line',
                                    id: 'show_local_line',                                  
                                    disabled: _writable.show_local_line,                                    
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
                                    xtype: 'combo',
                                    fieldLabel: translation._('show_call_status'),
                                    name: 'show_call_status',
                                    id: 'show_call_status',
                                    disabled: _writable.show_call_status,                                    
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
            if(this.myphoneRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editMyPhoneForm').action_delete.enable();
            }
        },
        
        display: function(_myphoneData, _writable) 
        {
            this._myphoneData = _myphoneData;
            
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editMyPhoneForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteMyPhone,
                items: [{
                    defaults: {
                        frame: true
                    },
                	xtype: 'tabpanel',
                    border: false,
                    height: 100,
                    anchor: '100% 100%',
                    plain: true,
                    activeTab: 0,
                    id: 'editMyPhoneTabPanel',
                    layoutOnTabChange: true, 
                    deferredRender: false,                                   
                    items:[
                        this.editMyPhoneDialog(_myphoneData),
                        this.editMyPhoneSettingsDialog(_writable)
                    ]
                }]
            });
            
            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
               
            this.updateMyPhoneRecord(_myphoneData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.myphoneRecord);
        } 
};


