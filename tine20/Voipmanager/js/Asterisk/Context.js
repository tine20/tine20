/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:      $
 *
 */

Ext.namespace('Tine.Voipmanager.Asterisk.Context');

Tine.Voipmanager.Asterisk.Context.Main = {
       
    actions: {
        addContext: null,
        editContext: null,
        deleteContext: null
    },
    
    handlers: {
        /**
         * onclick handler for addContext
         */
        addContext: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('contextWindow', 'index.php?method=Voipmanager.editAsteriskContext&contextId=', 450, 250);
        },

        /**
         * onclick handler for editContext
         */
        editContext: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Context_Grid').getSelectionModel().getSelections();
            var contextId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('contextWindow', 'index.php?method=Voipmanager.editAsteriskContext&contextId=' + contextId, 450, 250);
        },
        
        /**
         * onclick handler for deleteContext
         */
        deleteContext: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected context?', function(_button){
                if (_button == 'yes') {
                
                    var contextIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Context_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        contextIds.push(selectedRows[i].id);
                    }
                    
                    contextIds = Ext.util.JSON.encode(contextIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteAsteriskContexts',
                            _contextIds: contextIds
                        },
                        text: 'Deleting context...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Context_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the context.');
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
    
        this.actions.addContext = new Ext.Action({
            text: this.translation._('add context'),
            handler: this.handlers.addContext,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editContext = new Ext.Action({
            text: this.translation._('edit context'),
            disabled: true,
            handler: this.handlers.editContext,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteContext = new Ext.Action({
            text: this.translation._('delete context'),
            disabled: true,
            handler: this.handlers.deleteContext,
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
    
    displayContextToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Context_Grid').getStore().load({
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
        
        var contextToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Context_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addContext, 
                this.actions.editContext,
                this.actions.deleteContext,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(contextToolbar);
    },

    displayContextGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Asterisk.Context,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        //Ext.StoreMgr.add('ContextStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying context {0} - {1} of {2}'),
            emptyMsg: this.translation._("No context to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            { resizable: true, id: 'name', header: this.translation._('name'), dataIndex: 'name', width: 100 },
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 350 }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteContext.setDisabled(true);
                this.actions.editContext.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteContext.setDisabled(false);
                this.actions.editContext.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteContext.setDisabled(false);
                this.actions.editContext.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Context_Grid',
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
                emptyText: 'No context to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuContext', 
                items: [
                    this.actions.editContext,
                    this.actions.deleteContext,
                    '-',
                    this.actions.addContext 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('contextWindow', 'index.php?method=Voipmanager.editAsteriskContext&contextId=' + record.data.id, 450, 250);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Context_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteContext();
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
        var dataStore = Ext.getCmp('Voipmanager_Context_Grid').getStore();
   
        dataStore.baseParams.method = 'Voipmanager.getAsteriskContexts';
   
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Context_Toolbar') {
            this.initComponent();
            this.displayContextToolbar();
            this.displayContextGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Context_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Context_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Asterisk.Context.EditDialog =  {

        contextRecord: null,
        
        updateContextRecord: function(_contextData)
        {            
            this.contextRecord = new Tine.Voipmanager.Model.Asterisk.Context(_contextData);
        },
        
        deleteContext: function(_button, _event)
        {
            var contextIds = Ext.util.JSON.encode([this.contextRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteAsteriskContexts', 
                    phoneIds: contextIds
                },
                text: 'Deleting context...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Asterisk.Context.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the context.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editContextForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.contextRecord);

                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveAsteriskContext', 
                        contextData: Ext.util.JSON.encode(this.contextRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Asterisk.Context) {
                            window.opener.Tine.Voipmanager.Asterisk.Context.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateContextRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.contextRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save context.'); 
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
        
        editContextDialog: function(){
            var translation = new Locale.Gettext();
            translation.textdomain('Voipmanager');
            
            var _dialog = [{
                layout: 'form',
                //frame: true,
                border: false,
                width: 440,
                height: 280,
                items: [{
                    xtype: 'textfield',
                    fieldLabel: translation._('Name'),
                    name: 'name',
                    maxLength: 80,
                    anchor: '100%',
                    allowBlank: false
                }, {
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: translation._('Description'),
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100%',
                    height: 40
                }]
            }];
            
            
            return _dialog;    
        },
        
        updateToolbarButtons: function()
        {
            if(this.contextRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editContextForm').action_delete.enable();
            }
        },
        
        display: function(_contextData, _software, _keylayout, _settings) 
        {           
            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editContextForm',
                layout: 'fit',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteContext,
                frame: true,
                items: this.editContextDialog()
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            this.updateContextRecord(_contextData);
            this.updateToolbarButtons();     
            dialog.getForm().loadRecord(this.contextRecord);
               
        }   
};
