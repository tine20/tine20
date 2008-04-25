/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Admin.Tags');
Tine.Admin.Tags.Main = {
    
    
    actions: {
        addTag: null,
        editTag: null,
        deleteTag: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addTag: function(_button, _event) {
            Tine.Tinebase.Common.openWindow('tagWindow', "index.php?method=Admin.editTag&tagId=",650, 600);
        },

        /**
         * onclick handler for editBtn
         */
        editTag: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
            var tagId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('tagWindow', 'index.php?method=Admin.editTag&tagId=' + tagId,650, 600);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteTag: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected tags?', function(_button){
                if (_button == 'yes') {
                
                    var tagIds = new Array();
                    var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        tagIds.push(selectedRows[i].id);
                    }
                    
                    tagIds = Ext.util.JSON.encode(tagIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteTags',
                            tagIds: tagIds
                        },
                        text: 'Deleting tag(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('AdminTagsGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the tag.');
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function()
    {
        this.actions.addTag = new Ext.Action({
            text: 'add tag',
            handler: this.handlers.addTag,
            iconCls: 'action_addTag',
            scope: this
        });
        
        this.actions.editTag = new Ext.Action({
            text: 'edit tag',
            disabled: true,
            handler: this.handlers.editTag,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTag = new Ext.Action({
            text: 'delete tag',
            disabled: true,
            handler: this.handlers.deleteTag,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayTagsToolbar: function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('AdminTagsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var tagsToolbar = new Ext.Toolbar({
            id: 'AdminTagsToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addTag, 
                this.actions.editTag,
                this.actions.deleteTag,
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(tagsToolbar);
    },

    displayTagsGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getTags'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Tag,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('name', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying tags {0} - {1} of {2}',
            emptyMsg: "No tags to display"
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'color', header: 'color', dataIndex: 'color', width: 25, renderer: function(color){return '<div style="width: 8px; height: 8px; background-color:' + color + '; border: 1px solid black;">&#160;</dev>';} },
            { resizable: true, id: 'name', header: 'Name', dataIndex: 'name', width: 200 },
            { resizable: true, id: 'description', header: 'Description', dataIndex: 'description', width: 500}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteTag.setDisabled(true);
                this.actions.editTag.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteTag.setDisabled(false);
                this.actions.editTag.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteTag.setDisabled(false);
                this.actions.editTag.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'AdminTagsGrid',
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
                emptyText: 'No tags to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuTags', 
                items: [
                    this.actions.editTag,
                    this.actions.deleteTag,
                    '-',
                    this.actions.addTag 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            try {
                Tine.Tinebase.Common.openWindow('tagWindow', 'index.php?method=Admin.editTag&tagId=' + record.data.id,650, 600);
            } catch(e) {
                // alert(e);
            }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function()
    {
        var dataStore = Ext.getCmp('AdminTagsGrid').getStore();
            
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function() 
    {
        this.initComponent();
        
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'AdminTagsToolbar') {
            this.displayTagsToolbar();
            this.displayTagsGrid();
        }
        this.loadData();
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('AdminTagsGrid')) {
            setTimeout ("Ext.getCmp('AdminTagsGrid').getStore().reload()", 200);
        }
    }
};

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Tags.EditDialog = {
    
    /**
     * var handlers
     */
     handlers: {
        removeAccount: function(_button, _event) 
        { 
            var tagGrid = Ext.getCmp('tagMembersGrid');
            var selectedRows = tagGrid.getSelectionModel().getSelections();
            
            var tagMembersStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                tagMembersStore.remove(selectedRows[i]);
            }
                
        },
        
        addAccount: function(account)
        {
            var tagGrid = Ext.getCmp('tagMembersGrid');
            
            var dataStore = tagGrid.getStore();
            var selectionModel = tagGrid.getSelectionModel();
            
            if (dataStore.getById(account.data.data.accountId) === undefined) {
                var record = new Tine.Tinebase.Model.User({
                    accountId: account.data.data.accountId,
                    accountDisplayName: account.data.data.accountDisplayName
                }, account.data.data.accountId);
                dataStore.addSorted(record);
            }
            selectionModel.selectRow(dataStore.indexOfId(account.data.data.accountId));            
        },

        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('tagDialog').getForm();
            
            if(form.isValid()) {
        
                // get tag members
                var tagGrid = Ext.getCmp('tagMembersGrid');

                Ext.MessageBox.wait('Please wait', 'Updating Memberships');
                
                var tagMembers = [];
                var dataStore = tagGrid.getStore();
                
                dataStore.each(function(_record){
                    tagMembers.push(_record.data.accountId);
                });
                
                // update form               
                form.updateRecord(Tine.Admin.Tags.EditDialog.tagRecord);

                /*********** save tag members & form ************/
                
                Ext.Ajax.request({
                    params: {
                        method: 'Admin.saveTag', 
                        tagData: Ext.util.JSON.encode(Tine.Admin.Tags.EditDialog.tagRecord.data),
                        tagMembers: Ext.util.JSON.encode(tagMembers)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Admin.Tags) {
                            window.opener.Tine.Admin.Tags.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            //this.updateTagRecord(Ext.util.JSON.decode(_result.responseText));
                            //form.loadRecord(this.tagRecord);
                            
                            // @todo   get tagMembers from result
                            /*
                            var tagMembers = Ext.util.JSON.decode(_result.responseText);
                            dataStore.loadData(tagMembers, false);
                            */
                            
                            Ext.MessageBox.hide();
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save tag.'); 
                    },
                    scope: this 
                });
                    
                
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
        },

        saveAndClose: function(_button, _event) 
        {
            this.handlers.applyChanges(_button, _event, true);
        },

        deleteTag: function(_button, _event) 
        {
            var tagIds = Ext.util.JSON.encode([Tine.Admin.Tags.EditDialog.tagRecord.data.id]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Admin.deleteTags', 
                    tagIds: tagIds
                },
                text: 'Deleting tag...',
                success: function(_result, _request) {
                    if(window.opener.Tine.Admin.Tags) {
                        window.opener.Tine.Admin.Tags.Main.reload();
                    }
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the tag.'); 
                } 
            });                           
        }
        
     },
     
    /**
     * var tagRecord
     */
    tagRecord: null,
    

    /**
     * function updateTagRecord
     */
    updateTagRecord: function(_tagData)
    {
        // if tagData is empty (=array), set to empty object because array won't work!
        if (_tagData.length === 0) {
            _tagData = {};
        }
        this.tagRecord = new Tine.Tinebase.Model.Tag(_tagData);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights)
    {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }

        if(_rights.deleteGrant === true) {
            Ext.getCmp('tagDialog').action_delete.enable();
        }*/
        Ext.getCmp('tagDialog').action_delete.enable();
    },
    
    /**
     * function display
     * 
     * @param   _tagData
     * @param   _tagMembers
     * 
     */
    display: function(_tagData, _tagMembers) 
    {

        /******* actions ********/

        this.actions = {
            addAccount: new Ext.Action({
                text: 'add account',
                disabled: true,
                scope: this,
                handler: this.handlers.addAccount,
                iconCls: 'action_addContact'
            }),
            removeAccount: new Ext.Action({
                text: 'remove account',
                disabled: true,
                scope: this,
                handler: this.handlers.removeAccount,
                iconCls: 'action_deleteContact'
            })
        };

        /******* account picker panel ********/
        
        var accountPicker =  new Tine.widgets.AccountpickerPanel ({            
            enableBbar: true,
            region: 'west',
            height: 200,
            //bbar: this.userSelectionBottomToolBar,
            selectAction: function() {              
                this.account = account;
                this.handlers.addAccount(account);
            }  
        });
                
        accountPicker.on('accountdblclick', function(account){
            this.account = account;
            this.handlers.addAccount(account);
        }, this);
        

        /******* load data store ********/

        this.dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: Tine.Tinebase.Model.User
        });

        Ext.StoreMgr.add('TagMembersStore', this.dataStore);
        
        this.dataStore.setDefaultSort('accountDisplayName', 'asc');        
        
        if (_tagMembers.length === 0) {
            this.dataStore.removeAll();
        } else {
            this.dataStore.loadData( _tagMembers );
        }

        /******* column model ********/

        var columnModel = new Ext.grid.ColumnModel([{ 
            resizable: true, id: 'accountDisplayName', header: 'Name', dataIndex: 'accountDisplayName', width: 30 
        }]);

        /******* row selection model ********/

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.removeAccount.setDisabled(true);
            } else {
                // only one row selected
                this.actions.removeAccount.setDisabled(false);
            }
        }, this);
       
        /******* bottom toolbar ********/

        var membersBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });

        /******* tag members grid ********/
        
        var tagMembersGridPanel = new Ext.grid.EditorGridPanel({
            id: 'tagMembersGrid',
            region: 'center',
            title: 'Tag Members',
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //autoExpandColumn: 'accountLoginName',
            autoExpandColumn: 'accountDisplayName',
            bbar: membersBottomToolbar,
            border: true
        }); 
        
        /******* THE edit dialog ********/
        
        var editTagDialog = {
            layout:'border',
            border:false,
            width: 600,
            height: 500,
            items:[{
                    region: 'north',
                    layout:'column',
                    border: false,
                    autoHeight: true,
                    items:[{
                        columnWidth: 1,
                        layout: 'form',
                        border: false,
                        items:[{
                            xtype:'textfield',
                            fieldLabel:'Tag Name', 
                            name:'name',
                            anchor:'100%',
                            allowBlank: false
                        }, {
                            xtype:'textarea',
                            name: 'description',
                            fieldLabel: 'Description',
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100%',
                            height: 60
                        }]        
                    }]
                },
                accountPicker, 
                tagMembersGridPanel
            ]
        };
        
        /******* build panel & viewport & form ********/
               
        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'tagDialog',
            title: 'Edit Tag ' + _tagData.name,
            layout: 'fit',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteTag,
            handlerExport: this.handlers.exportTag,
            items: editTagDialog
        });

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateTagRecord(_tagData);
        this.updateToolbarButtons(_tagData.grants);       

        dialog.getForm().loadRecord(this.tagRecord);
        
    } // end display function     
    
};
