/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager.Templates');

Tine.Voipmanager.Templates.Main = {
       
    actions: {
        addTemplate: null,
        editTemplate: null,
        deleteTemplate: null
    },
    
    handlers: {
        /**
         * onclick handler for addTemplate
         */
        addTemplate: function(_button, _event) 
        {
            Tine.Tinebase.Common.openWindow('templateWindow', 'index.php?method=Voipmanager.editTemplate&templateId=', 450, 350);
        },

        /**
         * onclick handler for editTemplate
         */
        editTemplate: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Voipmanager_Template_Grid').getSelectionModel().getSelections();
            var templateId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('templateWindow', 'index.php?method=Voipmanager.editTemplate&templateId=' + templateId, 450, 350);
        },
        
        /**
         * onclick handler for deleteTemplate
         */
        deleteTemplate: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected template?', function(_button){
                if (_button == 'yes') {
                
                    var templateIds = [];
                    var selectedRows = Ext.getCmp('Voipmanager_Template_Grid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        templateIds.push(selectedRows[i].id);
                    }
                    
                    templateIds = Ext.util.JSON.encode(templateIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Voipmanager.deleteSnomTemplates',
                            _templateIds: templateIds
                        },
                        text: 'Deleting template...',
                        success: function(_result, _request){
                            Ext.getCmp('Voipmanager_Template_Grid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the template.');
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
    
        this.actions.addTemplate = new Ext.Action({
            text: this.translation._('add template'),
            handler: this.handlers.addTemplate,
            iconCls: 'action_add',
            scope: this
        });
        
        this.actions.editTemplate = new Ext.Action({
            text: this.translation._('edit template'),
            disabled: true,
            handler: this.handlers.editTemplate,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTemplate = new Ext.Action({
            text: this.translation._('delete template'),
            disabled: true,
            handler: this.handlers.deleteTemplate,
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
    
    displayTemplateToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Voipmanager_Template_Grid').getStore().load({
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
        
        var templateToolbar = new Ext.Toolbar({
            id: 'Voipmanager_Template_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addTemplate, 
                this.actions.editTemplate,
                this.actions.deleteTemplate,
                '->',
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(templateToolbar);
    },

    displayTemplateGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Voipmanager.Model.Template,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);   
        
        //Ext.StoreMgr.add('TemplateStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying template {0} - {1} of {2}'),
            emptyMsg: this.translation._("No template to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            { resizable: true, id: 'name', header: this.translation._('name'), dataIndex: 'name', width: 100 },
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 350 },
            { resizable: true, id: 'model', header: this.translation._('Model'), dataIndex: 'model', width: 10, hidden: true },
            { resizable: true, id: 'keylayout_id', header: this.translation._('Keylayout Id'), dataIndex: 'keylayout_id', width: 10, hidden: true },
            { resizable: true, id: 'setting_id', header: this.translation._('Settings Id'), dataIndex: 'setting_id', width: 10, hidden: true },
            { resizable: true, id: 'software_id', header: this.translation._('Software Id'), dataIndex: 'software_id', width: 10, hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteTemplate.setDisabled(true);
                this.actions.editTemplate.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteTemplate.setDisabled(false);
                this.actions.editTemplate.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteTemplate.setDisabled(false);
                this.actions.editTemplate.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Voipmanager_Template_Grid',
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
                emptyText: 'No template to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuTemplate', 
                items: [
                    this.actions.editTemplate,
                    this.actions.deleteTemplate,
                    '-',
                    this.actions.addTemplate 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('templateWindow', 'index.php?method=Voipmanager.editTemplate&templateId=' + record.data.id, 450, 350);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Voipmanager_Template_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteTemplate();
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
        var dataStore = Ext.getCmp('Voipmanager_Template_Grid').getStore();
   
        dataStore.baseParams.method = 'Voipmanager.getSnomTemplates';
   
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

        if(currentToolbar === false || currentToolbar.id != 'Voipmanager_Template_Toolbar') {
            this.initComponent();
            this.displayTemplateToolbar();
            this.displayTemplateGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Voipmanager_Template_Grid')) {
            setTimeout ("Ext.getCmp('Voipmanager_Template_Grid').getStore().reload()", 200);
        }
    }
};


Tine.Voipmanager.Templates.EditDialog =  {

        templateRecord: null,
        
        updateTemplateRecord: function(_templateData)
        {
            this.templateRecord = new Tine.Voipmanager.Model.Template(_templateData);
        },
        
        deleteTemplate: function(_button, _event)
        {
            var templateIds = Ext.util.JSON.encode([this.templateRecord.get('id')]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Voipmanager.deleteSnomTemplates', 
                    phoneIds: templateIds
                },
                text: 'Deleting template...',
                success: function(_result, _request) {
                    window.opener.Tine.Voipmanager.Templates.Main.reload();
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the template.'); 
                } 
            });         
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('voipmanager_editTemplateForm').getForm();

            if(form.isValid()) {
                form.updateRecord(this.templateRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Voipmanager.saveSnomTemplate', 
                        templateData: Ext.util.JSON.encode(this.templateRecord.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Voipmanager.Templates) {
                            window.opener.Tine.Voipmanager.Templates.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateTemplateRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            this.updateToolbarButtons();
                            form.loadRecord(this.templateRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save template.'); 
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
        
        editTemplateDialog: [{
            layout:'form',
            //frame: true,
            border:false,
            width: 440,
            height: 280,
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
                height: 40
            } ,
                new Ext.form.ComboBox({
                    fieldLabel: 'Software Version',
                    name: 'software_id',
                    id: 'software_id',
                    mode: 'local',
                    displayField:'description',
                    valueField:'id',
                    anchor:'100%',                    
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                    	storeId: 'Voipmanger_EditTemplate_Software',
                        id: 'id',
                        fields: ['id','model','description']
                    })                    
                }),
                new Ext.form.ComboBox({
                    fieldLabel: 'Keylayout',
                    name: 'keylayout_id',
                    id: 'keylayout_id',
                    mode: 'local',
                    displayField:'description',
                    valueField:'id',
                    anchor:'100%',                    
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                    	storeId: 'Voipmanger_EditTemplate_Keylayout',
                        id: 'id',
                        fields: ['id','model','description']
                    })                    
                }),
                new Ext.form.ComboBox({
                    fieldLabel: 'Settings',
                    name: 'setting_id',
                    id: 'setting_id',
                    mode: 'local',
                    displayField:'description',
                    valueField:'id',
                    anchor:'100%',                    
                    triggerAction: 'all',
                    editable: false,
                    forceSelection: true,
                    store: new Ext.data.JsonStore({
                    	storeId: 'Voipmanger_EditTemplate_Settings',
                        id: 'id',
                        fields: ['id','model','description']
                    })                    
                })                                  
            ]
        }],
        
        updateToolbarButtons: function()
        {
            if(this.templateRecord.get('id') > 0) {
                Ext.getCmp('voipmanager_editTemplateForm').action_delete.enable();
            }
        },
        
        display: function(_templateData, _software, _keylayout, _settings) 
        {           
            if (!arguments[0]) {
                var _templateData = {model:'snom320'};                
            }
            
            Ext.StoreMgr.lookup('Voipmanger_EditTemplate_Software').loadData(_software);
            Ext.StoreMgr.lookup('Voipmanger_EditTemplate_Keylayout').loadData(_keylayout);            
            Ext.StoreMgr.lookup('Voipmanger_EditTemplate_Settings').loadData(_settings);            

            // Ext.FormPanel
            var dialog = new Tine.widgets.dialog.EditRecord({
                id : 'voipmanager_editTemplateForm',
                layout: 'fit',
                //title: 'the title',
                labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteTemplate,
                items: this.editTemplateDialog
            });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
            
            this.updateTemplateRecord(_templateData);
            this.updateToolbarButtons();     
            dialog.getForm().loadRecord(this.templateRecord);
               
        }   
};
