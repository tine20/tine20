/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:      $
 *
 */

Ext.namespace('Tine.Voipmanager.Asterisk.Voicemail');

Tine.Voipmanager.Asterisk.Voicemail.Main = {
       
    actions: {
        addVoicemail: null,
        editVoicemail: null,
        deleteVoicemail: null
    },
    
    handlers: {
        /**
         * onclick handler for addVoicemail
         */
        addVoicemail: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('voicemailWindow', 'index.php?method=Voipmanager.editAsteriskVoicemail&voicemailId=', 450, 400);
        },

        /**
         * onclick handler for editVoicemail
         */
        editVoicemail: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Voicemail_Grid').getSelectionModel().getSelections();
            var voicemailId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('voicemailWindow', 'index.php?method=Voipmanager.editAsteriskVoicemail&voicemailId=' + voicemailId, 450, 400);
        },
        
        /**
         * onclick handler for deleteVoicemail
         */
        deleteVoicemail: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected voicemail?', function(_button){
                if (_button == 'yes') {
                
                    var voicemailIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Voicemail_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        voicemailIds.push(selectedRows[i].id);
                    }
                    
                    voicemailIds = Ext.util.JSON.encode(voicemailIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteAsteriskVoicemails',
                            _voicemailIds: voicemailIds
                        },
                        text: 'Deleting voicemail...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Voicemail_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the voicemail.');
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
    
        this.actions.addVoicemail = new Ext.Action({
            text: this.translation._('add voicemail'),
            handler: this.handlers.addVoicemail,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editVoicemail = new Ext.Action({
            text: this.translation._('edit voicemail'),
            disabled: true,
            handler: this.handlers.editVoicemail,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteVoicemail = new Ext.Action({
            text: this.translation._('delete voicemail'),
            disabled: true,
            handler: this.handlers.deleteVoicemail,
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
    
    displayVoicemailToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Voicemail_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };

        var contextFilter = new Tine.Voipmanager.widgets.ContextCombo();
        contextFilter.on('change', onFilterChange, this);        
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
        
        var voicemailToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Voicemail_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addVoicemail, 
                this.actions.editVoicemail,
                this.actions.deleteVoicemail,
                '->',
                this.translation._('Context: '), contextFilter,                
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(voicemailToolbar);
    },

    displayVoicemailGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Asterisk.Voicemail,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('fullname', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
            _dataStore.baseParams.context = Ext.getCmp('ContextCombo').getRawValue();            
        }, this);   
        
        //Ext.StoreMgr.add('VoicemailStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying voicemail {0} - {1} of {2}'),
            emptyMsg: this.translation._("No voicemail to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            { resizable: true, id: 'mailbox', header: this.translation._('mailbox'), dataIndex: 'mailbox', width: 50 },
            { resizable: true, id: 'context', header: this.translation._('context'), dataIndex: 'context', width: 70 },
            { resizable: true, id: 'fullname', header: this.translation._('fullname'), dataIndex: 'fullname', width: 180 },
            { resizable: true, id: 'email', header: this.translation._('email'), dataIndex: 'email', width: 120 },
            { resizable: true, id: 'pager', header: this.translation._('pager'), dataIndex: 'pager', width: 120 },
            { resizable: true, id: 'tz', header: this.translation._('tz'), dataIndex: 'tz', width: 10, hidden: true },
            { resizable: true, id: 'attach', header: this.translation._('attach'), dataIndex: 'attach', width: 10, hidden: true },
            { resizable: true, id: 'saycid', header: this.translation._('saycid'), dataIndex: 'saycid', width: 10, hidden: true },
            { resizable: true, id: 'dialout', header: this.translation._('dialout'), dataIndex: 'dialout', width: 10, hidden: true },
            { resizable: true, id: 'callback', header: this.translation._('callback'), dataIndex: 'callback', width: 10, hidden: true },
            { resizable: true, id: 'review', header: this.translation._('review'), dataIndex: 'review', width: 10, hidden: true },
            { resizable: true, id: 'operator', header: this.translation._('operator'), dataIndex: 'operator', width: 10, hidden: true },
            { resizable: true, id: 'envelope', header: this.translation._('envelope'), dataIndex: 'envelope', width: 10, hidden: true },
            { resizable: true, id: 'sayduration', header: this.translation._('sayduration'), dataIndex: 'sayduration', width: 10, hidden: true },
            { resizable: true, id: 'saydurationm', header: this.translation._('saydurationm'), dataIndex: 'saydurationm', width: 10, hidden: true },
            { resizable: true, id: 'sendvoicemail', header: this.translation._('sendvoicemail'), dataIndex: 'sendvoicemail', width: 10, hidden: true },
            { resizable: true, id: 'delete', header: this.translation._('delete'), dataIndex: 'delete', width: 10, hidden: true },
            { resizable: true, id: 'nextaftercmd', header: this.translation._('nextaftercmd'), dataIndex: 'nextaftercmd', width: 10, hidden: true },
            { resizable: true, id: 'forcename', header: this.translation._('forcename'), dataIndex: 'forcename', width: 10, hidden: true },
            { resizable: true, id: 'forcegreetings', header: this.translation._('forcegreetings'), dataIndex: 'forcegreetings', width: 10, hidden: true },
            { resizable: true, id: 'hidefromdir', header: this.translation._('hidefromdir'), dataIndex: 'hidefromdir', width: 10, hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteVoicemail.setDisabled(true);
                this.actions.editVoicemail.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteVoicemail.setDisabled(false);
                this.actions.editVoicemail.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteVoicemail.setDisabled(false);
                this.actions.editVoicemail.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Voicemail_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'fullname',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No voicemail to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var voicemailMenu = new Ext.menu.Menu({
                id:'ctxMenuVoicemail', 
                items: [
                    this.actions.editVoicemail,
                    this.actions.deleteVoicemail,
                    '-',
                    this.actions.addVoicemail 
                ]
            });
            voicemailMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('voicemailWindow', 'index.php?method=Voipmanager.editAsteriskVoicemail&voicemailId=' + record.data.id, 450, 400);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Voicemail_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteVoicemail();
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
        var dataStore = Ext.getCmp('Voipmanager_Voicemail_Grid').getStore();
   
        dataStore.baseParams.method = 'Voipmanager.getAsteriskVoicemails';
   
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Voicemail_Toolbar') {
            this.initComponent();
            this.displayVoicemailToolbar();
            this.displayVoicemailGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Voicemail_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Voicemail_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Asterisk.Voicemail.EditDialog =  {

        voicemailRecord: null,
        
        updateVoicemailRecord: function(_voicemailData)
        {            
            this.voicemailRecord = new Tine.Voipmanager.Model.Asterisk.Voicemail(_voicemailData);
  //          console.log(this.voicemailRecord);
        },
        
        deleteVoicemail: function(_button, _event)
        {
            var voicemailIds = Ext.util.JSON.encode([this.voicemailRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteAsteriskVoicemails', 
                    phoneIds: voicemailIds
                },
                text: 'Deleting voicemail...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Asterisk.Voicemail.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the voicemail.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editVoicemailForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.voicemailRecord);

                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveAsteriskVoicemail', 
                        voicemailData: Ext.util.JSON.encode(this.voicemailRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Asterisk.Voicemail) {
                            window.opener.Tine.Voipmanager.Asterisk.Voicemail.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateVoicemailRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.voicemailRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save voicemail.'); 
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
        
        editVoicemailMainDialog: function(){
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            var _dialog = {
                title: translation._('General'),
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
                        layout: 'form',
                        //frame: true,
                        border: false,
                        anchor: '100%',
                        items: [{
                            xtype: 'textfield',
                            fieldLabel: translation._('mailbox'),
                            name: 'mailbox',
                            maxLength: 11,
                            anchor: '100%',
                            allowBlank: false
                        }, {
                            xtype: 'combo',
                            fieldLabel: translation._('Context'),
                            name: 'context',
                            mode: 'local',
                            displayField: 'name',
                            valueField: 'name',
                            anchor: '100%',
                            triggerAction: 'all',
                            editable: false,
                            forceSelection: true,
                            store: new Ext.data.JsonStore({
                                storeId: 'Voipmanger_EditVoicemail_Context',
                                id: 'id',
                                fields: ['id', 'name']
                            })
                        }, {
                            xtype: 'textfield',
                            fieldLabel: translation._('Name'),
                            name: 'fullname',
                            maxLength: 150,
                            anchor: '100%',
                            allowBlank: false
                        }, {
                            xtype: 'numberfield',
                            fieldLabel: translation._('Password'),
                            name: 'password',
                            maxLength: 5,
                            anchor: '100%',
                            allowBlank: false
                        }, {
                            xtype: 'textfield',
                            vtype: 'email',
                            fieldLabel: translation._('email'),
                            name: 'email',
                            maxLength: 50,
                            anchor: '100%'                        
                        }, {
                            xtype: 'textfield',
                            fieldLabel: translation._('pager'),
                            name: 'pager',
                            maxLength: 50,
                            anchor: '100%'
                        }]
                    }]
                }]
            };
            
            
            return _dialog;    
        },
        
        
        editVoicemailAdditionalDialog: function(){
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            var _dialog = {
                title: translation._('additional'),
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
                        layout: 'column',
                        border: false,
                        anchor: '100%',
                        items: [{
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: translation._('tz'),
                                name: 'tz',
                                maxLength: 10,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: translation._('dialout'),
                                name: 'dialout',
                                maxLength: 10,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: translation._('callback'),
                                name: 'callback',
                                maxLength: 10,
                                anchor: '100%',
                                allowBlank: true
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
                                fieldLabel: translation._('sayduration'),
                                id: 'sayduration',
                                name: 'sayduration',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                 
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
                                xtype: 'numberfield',
                                fieldLabel: translation._('saydurationm'),
                                name: 'saydurationm',
                                maxLength: 4,
                                anchor: '98%',
                                allowBlank: true
                            }]
                        }, {
                            columnWidth: 0.33,
                            layout: 'form',
                            border: false,
                            anchor: '100%',
                            items: [{
                                xtype: 'combo',
                                fieldLabel: translation._('attach'),
                                name: 'attach',
                                id: 'attach',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                        
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('saycid'),
                                name: 'saycid',
                                id: 'saycid',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                                
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('review'),
                                name: 'review',
                                id: 'review',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                               
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
                                fieldLabel: translation._('operator'),
                                name: 'operator',
                                id: 'operator',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                            
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
                                fieldLabel: translation._('envelope'),
                                name: 'envelope',
                                id: 'envelope',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                        
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
                                fieldLabel: translation._('sendvoicemail'),
                                name: 'sendvoicemail',
                                id: 'sendvoicemail',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                               
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('delete'),
                                name: 'delete',
                                id: 'delete',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                             
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('nextaftercmd'),
                                name: 'nextaftercmd',
                                id: 'nextaftercmd',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                             
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('forcename'),
                                name: 'forcename',
                                id: 'forcename',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                              
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('forcegreetings'),
                                name: 'forcegreetings',
                                id: 'forcegreetings',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '100%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                             
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
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
                                fieldLabel: translation._('hidefromdir'),
                                name: 'hidefromdir',
                                id: 'hidefromdir',
                                mode: 'local',
                                displayField: 'value',
                                valueField: 'id',
                                anchor: '98%',
                                triggerAction: 'all',
                                editable: false,
                                forceSelection: true,
                                store: new Ext.data.SimpleStore({
                                    id: 'id',
                                    fields: ['id', 'value'],
                                    data: [                                                                        
                                        ['1', translation._('on')],
                                        ['0', translation._('off')]
                                    ]
                                })
                            }]
                        }]
                    }]
                }]
            };
            
            
            return _dialog;    
        },
        
        
        
        updateToolbarButtons: function()
        {
            if(this.voicemailRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editVoicemailForm').action_delete.enable();
            }
        },
        
        display: function(_voicemailData, _contexts) 
        {           
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editVoicemailForm',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteVoicemail,
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
                    id: 'editVoicemailTabPanel',
                    layoutOnTabChange: true,  
                    deferredRender: false,                            
                    items:[
                        this.editVoicemailMainDialog(),
                        this.editVoicemailAdditionalDialog()
                    ]
                }]
            });

            Ext.StoreMgr.lookup('Voipmanger_EditVoicemail_Context').loadData(_contexts);

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            this.updateVoicemailRecord(_voicemailData);
            this.updateToolbarButtons();     
            dialog.getForm().loadRecord(this.voicemailRecord);
               
        }   
};
