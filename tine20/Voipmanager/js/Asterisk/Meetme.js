/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     
 *
 */

Ext.namespace('Tine.Voipmanager.Asterisk.Meetme');

Tine.Voipmanager.Asterisk.Meetme.Main = {
       
    actions: {
        addMeetme: null,
        editMeetme: null,
        deleteMeetme: null
    },
    
    handlers: {
        /**
         * onclick handler for addMeetme
         */
        addMeetme: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('meetmeWindow', 'index.php?method=Voipmanager.editAsteriskMeetme&meetmeId=', 450, 250);
        },

        /**
         * onclick handler for editMeetme
         */
        editMeetme: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Meetme_Grid').getSelectionModel().getSelections();
            var meetmeId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('meetmeWindow', 'index.php?method=Voipmanager.editAsteriskMeetme&meetmeId=' + meetmeId, 450, 250);
        },
        
        /**
         * onclick handler for deleteMeetme
         */
        deleteMeetme: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected meetme?', function(_button){
                if (_button == 'yes') {
                
                    var meetmeIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Meetme_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        meetmeIds.push(selectedRows[i].id);
                    }
                    
                    meetmeIds = Ext.util.JSON.encode(meetmeIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteAsteriskMeetmes',
                            _meetmeIds: meetmeIds
                        },
                        text: 'Deleting meetme...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Meetme_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the meetme.');
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
    
        this.actions.addMeetme = new Ext.Action({
            text: this.translation._('add meetme'),
            handler: this.handlers.addMeetme,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editMeetme = new Ext.Action({
            text: this.translation._('edit meetme'),
            disabled: true,
            handler: this.handlers.editMeetme,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteMeetme = new Ext.Action({
            text: this.translation._('delete meetme'),
            disabled: true,
            handler: this.handlers.deleteMeetme,
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
    
    displayMeetmeToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Meetme_Grid').getStore().load({
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
        
        var meetmeToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Meetme_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addMeetme, 
                this.actions.editMeetme,
                this.actions.deleteMeetme,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(meetmeToolbar);
    },

    displayMeetmeGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Asterisk.Meetme,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('confno', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        //Ext.StoreMgr.add('MeetmeStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying meetme {0} - {1} of {2}'),
            emptyMsg: this.translation._("No meetme to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            { resizable: true, id: 'confno', header: this.translation._('confno'), dataIndex: 'confno', width: 80 },
            { resizable: true, id: 'pin', header: this.translation._('pin'), dataIndex: 'pin', width: 80 },
            { resizable: true, id: 'adminpin', header: this.translation._('adminpin'), dataIndex: 'adminpin', width: 80 }						
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteMeetme.setDisabled(true);
                this.actions.editMeetme.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteMeetme.setDisabled(false);
                this.actions.editMeetme.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteMeetme.setDisabled(false);
                this.actions.editMeetme.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Meetme_Grid',
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
                emptyText: 'No meetme to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var meetmeMenu = new Ext.menu.Menu({
                id:'ctxMenuMeetme', 
                items: [
                    this.actions.editMeetme,
                    this.actions.deleteMeetme,
                    '-',
                    this.actions.addMeetme 
                ]
            });
            meetmeMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('meetmeWindow', 'index.php?method=Voipmanager.editAsteriskMeetme&meetmeId=' + record.data.id, 450, 250);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Meetme_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteMeetme();
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
        var dataStore = Ext.getCmp('Voipmanager_Meetme_Grid').getStore();
   
        dataStore.baseParams.method = 'Voipmanager.getAsteriskMeetmes';
   
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Meetme_Toolbar') {
            this.initComponent();
            this.displayMeetmeToolbar();
            this.displayMeetmeGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Meetme_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Meetme_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Asterisk.Meetme.EditDialog =  {

        meetmeRecord: null,
        
        updateMeetmeRecord: function(_meetmeData)
        {            
            this.meetmeRecord = new Tine.Voipmanager.Model.Asterisk.Meetme(_meetmeData);
        },
        
        deleteMeetme: function(_button, _event)
        {
            var meetmeIds = Ext.util.JSON.encode([this.meetmeRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteAsteriskMeetmes', 
                    phoneIds: meetmeIds
                },
                text: 'Deleting meetme...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Asterisk.Meetme.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the meetme.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editMeetmeForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.meetmeRecord);

                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveAsteriskMeetme', 
                        meetmeData: Ext.util.JSON.encode(this.meetmeRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Asterisk.Meetme) {
                            window.opener.Tine.Voipmanager.Asterisk.Meetme.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateMeetmeRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.meetmeRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save meetme.'); 
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
        
        editMeetmeDialog: function(){
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            var _dialog = [{
                layout: 'form',
                //frame: true,
                border: false,
                width: 440,
                height: 280,
                items: [{
                    xtype: 'numberfield',
                    fieldLabel: translation._('confno'),
                    name: 'confno',
					id: 'confno',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }, {
                    xtype: 'numberfield',
                    fieldLabel: translation._('pin'),
                    name: 'pin',
					id: 'pin',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                },{
                    xtype: 'numberfield',
                    fieldLabel: translation._('adminpin'),
                    name: 'adminpin',
					id: 'adminpin',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }]
            }];
            
            
            return _dialog;    
        },
        
        updateToolbarButtons: function()
        {
            if(this.meetmeRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editMeetmeForm').action_delete.enable();
            }
        },
        
        display: function(_meetmeData, _software, _keylayout, _settings) 
        {           
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editMeetmeForm',
                layout: 'fit',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteMeetme,
                items: this.editMeetmeDialog()
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            this.updateMeetmeRecord(_meetmeData);
            this.updateToolbarButtons();     
            dialog.getForm().loadRecord(this.meetmeRecord);
               
        }   
};
