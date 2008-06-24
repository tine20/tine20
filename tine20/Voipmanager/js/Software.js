/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Software');

Tine.Voipmanager.Software.Main = {
       
    actions: {
        addSoftware: null,
        editSoftware: null,
        deleteSoftware: null
    },
    
    handlers: {
        /**
         * onclick handler for addSoftware
         */
        addSoftware: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Voipmanager.editSoftware&softwareId=', 450, 300);
        },

        /**
         * onclick handler for editSoftware
         */
        editSoftware: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Software_Grid').getSelectionModel().getSelections();
            var softwareId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Voipmanager.editSoftware&softwareId=' + softwareId, 450, 300);
        },
        
        /**
         * onclick handler for deleteSoftware
         */
        deleteSoftware: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected software?', function(_button){
                if (_button == 'yes') {
                
                    var softwareIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Software_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        softwareIds.push(selectedRows[i].id);
                    }
                    
                    softwareIds = Ext.util.JSON.encode(softwareIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteSnomSoftware',
                            _softwareIds: softwareIds
                        },
                        text: 'Deleting software...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Software_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the software.');
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
    
        this.actions.addSoftware = new Ext.Action({
            text: this.translation._('add software'),
            handler: this.handlers.addSoftware,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editSoftware = new Ext.Action({
            text: this.translation._('edit software'),
            disabled: true,
            handler: this.handlers.editSoftware,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteSoftware = new Ext.Action({
            text: this.translation._('delete software'),
            disabled: true,
            handler: this.handlers.deleteSoftware,
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
    
    displaySoftwareToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Software_Grid').getStore().load({
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
                
        var softwareToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Software_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addSoftware, 
                this.actions.editSoftware,
                this.actions.deleteSoftware,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(softwareToolbar);
    },

    displaySoftwareGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.SnomSoftware,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
            _dataStore.baseParams.tagFilter = Ext.getCmp('TagCombo').getValue();
        }, this);   
        
        //Ext.StoreMgr.add('SoftwareStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying software {0} - {1} of {2}'),
            emptyMsg: this.translation._("No software to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 20, hidden: true },
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 250 }
        ]);                        
                
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteSoftware.setDisabled(true);
                this.actions.editSoftware.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteSoftware.setDisabled(false);
                this.actions.editSoftware.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteSoftware.setDisabled(false);
                this.actions.editSoftware.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Software_Grid',
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
                emptyText: 'No software to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuSoftware', 
                items: [
                    this.actions.editSoftware,
                    this.actions.deleteSoftware,
                    '-',
                    this.actions.addSoftware 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Voipmanager.editSoftware&softwareId=' + record.data.id, 450, 300);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Software_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteSoftware();
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
        var dataStore = Ext.getCmp('Voipmanager_Software_Grid').getStore();
        
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
                dataStore.baseParams.method = 'Voipmanager.searchSnomSoftware';
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Software_Toolbar') {
            this.initComponent();
            this.displaySoftwareToolbar();
            this.displaySoftwareGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Software_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Software_Grid').getStore().reload()", 200);
        }
    }
};



Tine.Voipmanager.Software.EditDialog =  {

        softwareRecord: null,
        
        updateSoftwareRecord: function(_softwareData)
        {
            this.softwareRecord = new Tine.Voipmanager.Model.SnomSoftware(_softwareData);
        },
        
        deleteSoftware: function(_button, _event)
        {
            var softwareIds = Ext.util.JSON.encode([this.softwareRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteSoftware', 
                    phoneIds: softwareIds
                },
                text: 'Deleting software...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Software.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the software.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editSoftwareForm').getForm();

            var _softwareImageData = new Array();
            var _siData;
            var softwareImageData = new Array();

/*            this._phoneModels.each(function(_rec) {               
                _softwareImageData = [_rec.data.id,Ext.getCmp('softwareimage' + _rec.data.id).getValue()];
                
                _siData = new Tine.Voipmanager.Model.SnomSoftware(_softwareImageData);
    console.log(_siData.data);                            
//                softwareImageData = softwareImageData + ',' + Ext.util.JSON.encode(_siData.data);
  //              console.log(softwareImageData);
            });*/


            if(form.isValid()) {
                form.updateRecord(this.softwareRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomSoftware', 
                        softwareData: Ext.util.JSON.encode(this.softwareRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Software) {
                            window.opener.Tine.Voipmanager.Software.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateSoftwareRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.softwareRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save software.'); 
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
        
        editSoftwareDialog: function(_phoneModels) 
        {
            var softwareVersion = new Array();
            
            _phoneModels.each(function(_rec) {
                softwareVersion.push(new Ext.form.TextField({
                    fieldLabel: _rec.data.model,
                    name: 'softwareimage_' + _rec.data.id,
                    id: 'softwareimage_' + _rec.data.id,
                    anchor:'100%',
                    maxLength: 128,                    
                    hideLabel: false
                }));      
            });
            
            var editSoftwareForm = [{
                layout:'form',
                //frame: true,
                border:false,
                width: 440,
                height: 280,
                items: [{
                    //labelSeparator: '',
                    xtype:'textarea',
                    name: 'description',
                    fieldLabel: 'Description',
                    grow: false,
                    preventScrollbars:false,
                    anchor:'100%',
                    height: 60
                }, {
                    layout: 'column',
                    border: false,
                    anchor: '100%',
                    height: 130,
                    items: [{
                        columnWidth: 1,
                        layout: 'form',
                        border: false,
                        anchor: '100%',
                        items: softwareVersion
                    }]
                }]
            }];
        
            return editSoftwareForm;
        
        },
        
        updateToolbarButtons: function()
        {
            if(this.softwareRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editSoftwareForm').action_delete.enable();
            }
        },
        
        display: function(_softwareData) 
        {           
            this._phoneModels = Tine.Voipmanager.Data.loadPhoneModelData();

            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editSoftwareForm',
                layout: 'fit',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteSoftware,
                items: this.editSoftwareDialog(this._phoneModels)
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            this.updateSoftwareRecord(_softwareData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.softwareRecord);
        }   
};
