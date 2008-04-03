
/*********************************** TINE ADMIN GROUPS  ********************************************/
/*********************************** TINE ADMIN GROUPS  ********************************************/

Ext.namespace('Tine.Admin.Groups');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Groups.Main = {
    
    
    actions: {
        addGroup: null,
        editGroup: null,
        deleteGroup: null,
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addGroup: function(_button, _event) {
            Tine.Tinebase.Common.openWindow('groupWindow', "index.php?method=Admin.editGroup&groupId=",600, 400);
        },

        /**
         * onclick handler for editBtn
         */
        editGroup: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminGroupsGrid').getSelectionModel().getSelections();
            var groupId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('groupWindow', 'index.php?method=Admin.editGroup&groupId=' + groupId,600, 400);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteGroup: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected groups?', function(_button){
                if (_button == 'yes') {
                
                    var groupIds = new Array();
                    var selectedRows = Ext.getCmp('AdminGroupsGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        groupIds.push(selectedRows[i].id);
                    }
                    
                    groupIds = Ext.util.JSON.encode(groupIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteGroups',
                            groupIds: groupIds
                        },
                        text: 'Deleting group(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('AdminGroupsGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the group.');
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function()
    {
        this.actions.addGroup = new Ext.Action({
            text: 'add group',
            handler: this.handlers.addGroup,
            iconCls: 'action_addGroup',
            scope: this
        });
        
        this.actions.editGroup = new Ext.Action({
            text: 'edit group',
            disabled: true,
            handler: this.handlers.editGroup,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteGroup = new Ext.Action({
            text: 'delete group',
            disabled: true,
            handler: this.handlers.deleteGroup,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayGroupsToolbar: function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('AdminGroupsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var groupsToolbar = new Ext.Toolbar({
            id: 'AdminGroupsToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addGroup, 
                this.actions.editGroup,
                this.actions.deleteGroup,
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(groupsToolbar);
    },

    displayGroupsGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getGroups'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Admin.Model.Group,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('id', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying groups {0} - {1} of {2}',
            emptyMsg: "No groups to display"
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: 'ID', dataIndex: 'id', width: 10 },
            { resizable: true, id: 'name', header: 'Name', dataIndex: 'name', width: 50 },
            { resizable: true, id: 'description', header: 'Description', dataIndex: 'description' },
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteGroup.setDisabled(true);
                this.actions.editGroup.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteGroup.setDisabled(false);
                this.actions.editGroup.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteGroup.setDisabled(false);
                this.actions.editGroup.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'AdminGroupsGrid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'n_family',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No groups to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuGroups', 
                items: [
                    this.actions.editGroup,
                    this.actions.deleteGroup,
                    '-',
                    this.actions.addGroup 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('groupWindow', 'index.php?method=Admin.editGroup&groupId=' + record.data.id,600, 400);
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
        var dataStore = Ext.getCmp('AdminGroupsGrid').getStore();
            
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

        if(currentToolbar === false || currentToolbar.id != 'AdminGroupsToolbar') {
            this.displayGroupsToolbar();
            this.displayGroupsGrid();
        }
        this.loadData();
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('AdminGroupsGrid')) {
            setTimeout ("Ext.getCmp('AdminGroupsGrid').getStore().reload()", 200);
        }
    }
}

/*********************************** EDIT DIALOG / ACCOUNTPICKER ********************************/

/* not used */
Tine.Admin.Groups.AccountpickerDialog = Ext.extend(Tine.widgets.AccountpickerPanel, {
    
    /**
     * @cfg {Tine.Admin.Model.Group}
     * group to manage grants for
     */
    group: null,

    /**
     * @property {Object}
     * Models 
     */
    /*models: {
       groupMember : Tine.Admin.Model.groupMember
    },*/

    id: 'GroupsAccountDialog',

    // private
    handlers: {
        removeAccount: function(_button, _event) {
            
            var selectedRows = this.GrantsGridPanel.getSelectionModel().getSelections();
            /*
            var grantsStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                grantsStore.remove(selectedRows[i]);
            }
    
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
            */
        },
        addAccount: function(account){
            // we somehow lost scope...
            /*
            var cgd = Ext.getCmp('ContainerGrantsDialog');
            var dataStore = cgd.dataStore;
            var grantsSelectionModel = cgd.GrantsGridPanel.getSelectionModel();
            
            if (dataStore.getById(account.data.accountId) === undefined) {
                var record = new cgd.models.containerGrant({
                    accountId: account.data.accountId,
                    accountName: account.data,
                    readGrant: true,
                    addGrant: false,
                    editGrant: false,
                    deleteGrant: false,
                    adminGrant: false
                }, account.data.accountId);
                dataStore.addSorted(record);
            }
            grantsSelectionModel.selectRow(dataStore.indexOfId(account.data.accountId));
            
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
            */
        },

        accountsActionApply: function(button, event, closeWindow) {
            // we somehow lost scope...
            /*
            var cgd = Ext.getCmp('ContainerGrantsDialog');
            if (cgd.grantContainer) {
                var container = cgd.grantContainer;
                Ext.MessageBox.wait('Please wait', 'Updateing Grants');
                
                var grants = [];
                var grantsStore = cgd.dataStore;
                
                grantsStore.each(function(_record){
                    grants.push(_record.data);
                });
                
                Ext.Ajax.request({
                    params: {
                        method: 'Tinebase_Container.setContainerGrants',
                        containerId: container.id,
                        grants: Ext.util.JSON.encode(grants)
                    },
                    success: function(_result, _request){
                        var grants = Ext.util.JSON.decode(_result.responseText);
                        grantsStore.loadData(grants, false);
                        
                        Ext.MessageBox.hide();
                        if (closeWindow){
                            cgd.close();
                        }
                    }
                });
                
                Ext.getCmp('AccountsActionSaveButton').disable();
                Ext.getCmp('AccountsActionApplyButton').disable();
            }
            */
        },
        accountsActionSave: function(button, event) {
        	/*
            var cgd = Ext.getCmp('ContainerGrantsDialog');
            cgd.handlers.accountsActionApply(button, event, true);
            */
        }
    },

    //private
    initComponent: function(){
        this.title = 'Manage accounts for this group';

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
        
        /*        
        this.dataStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getGroupMembers',
                groupId: this.group.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: this.models.groupMember
            
        });
        
        Ext.StoreMgr.add('GroupMembersStore', this.dataStore);
        
        this.dataStore.setDefaultSort('accountName', 'asc');
        
        this.dataStore.load();
        
        this.dataStore.on('update', function(_store){
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
        }, this);
        
        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: true, 
                id: 'accountName', 
                header: 'Name', 
                dataIndex: 'accountName', 
                //renderer: Tine.Tinebase.Common.usernameRenderer,
                width: 100
            }]
        );

        columnModel.defaultSortable = true; // by default columns are sortable
                
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        var permissionsBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });
        
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
        
        this.GroupsAccountGridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            title: 'Group Accounts',
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //plugins: columns, 
            autoExpandColumn: 'accountName',
            bbar: permissionsBottomToolbar,
            border: false
        });
        */
        
        this.items = [
           //this.GroupsAccountGridPanel
           new Ext.Panel({
            region: 'center',
            title: 'test',
            html: 'hallo'
           })
        ];

        Tine.Admin.Groups.AccountpickerDialog.superclass.initComponent.call(this);
    },

    // private
    /*
    onRender: function(ct, position){
        Tine.widgets.container.grantDialog.superclass.onRender.call(this, ct, position);
        
        this.getUserSelection().on('accountdblclick', function(account){
            this.handlers.addAccount(account);   
        }, this);
    } 
    */
})

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Groups.EditDialog = {
	
    /**
     * var handlers
     */
     handlers: {
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('groupDialog').getForm();
            
            if(form.isValid()) {
                form.updateRecord(Tine.Admin.Groups.EditDialog.groupRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Admin.saveGroup', 
                        groupData: Ext.util.JSON.encode(Tine.Admin.Groups.EditDialog.groupRecord.data)
                    },
                    success: function(_result, _request) {
                     	if(window.opener.Tine.Admin.Groups) {
                            window.opener.Tine.Admin.Groups.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            //this.updateGroupRecord(Ext.util.JSON.decode(_result.responseText));
                            //this.updateToolbarButtons(formData.config.addressbookRights);
                            //form.loadRecord(this.groupRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save group.'); 
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

        deleteGroup: function(_button, _event) 
        {
            var groupIds = Ext.util.JSON.encode([Tine.Admin.Groups.EditDialog.groupRecord.data.id]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Admin.deleteGroups', 
                    groupIds: groupIds
                },
                text: 'Deleting group...',
                success: function(_result, _request) {
                    if(window.opener.Tine.Admin.Groups) {
                        window.opener.Tine.Admin.Groups.Main.reload();
                    }
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the group.'); 
                } 
            });                           
        },
        
     },
     
    /**
     * var groupRecord
     */
    groupRecord: null,

    /**
     * function updateGroupRecord
     */
    updateGroupRecord: function(_groupData)
    {
    	// if groupData is empty (=array), set to empty object because array won't work!
        if (_groupData.length == 0) {
        	_groupData = {};
        }
        this.groupRecord = new Tine.Admin.Model.Group(_groupData);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights)
    {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('groupDialog').action_saveAndClose.enable();
            Ext.getCmp('groupDialog').action_applyChanges.enable();
        }

        if(_rights.deleteGrant === true) {
            Ext.getCmp('groupDialog').action_delete.enable();
        }*/
        Ext.getCmp('groupDialog').action_delete.enable();
    },
    
    /**
     * function display
     */
    display: function(_groupData) 
    {

        /******* actions ********/
/*
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
*/
        /******* account picker panel ********/
        
        var accountPicker =  new Tine.widgets.AccountpickerPanel ({            
            enableBbar: true,
            region: 'west',
            //region: 'south',
            height: 200,
            //bbar: this.userSelectionBottomToolBar,
            selectAction: function() {
            }  
        });

        /******* data store ********/
        
        /*
        this.dataStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Tinebase_Container.getContainerGrants',
                containerId: this.grantContainer.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: Tine.Admin.Model.groupMember
        });
        
        Ext.StoreMgr.add('ContainerGrantsStore', this.dataStore);
        
        this.dataStore.setDefaultSort('accountName', 'asc');
        
        this.dataStore.load();
        
        this.dataStore.on('update', function(_store){
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
        }, this);
        */
        
        // testing
        this.dataStore = new Tine.Admin.Model.groupMember ({
                accountId: 1,
                groupId: 2
        });
        
        /******* column model ********/

        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: true, 
                id: 'accountName', 
                header: 'Name', 
                dataIndex: 'accountName', 
                //renderer: Tine.Tinebase.Common.usernameRenderer,
                width: 70
            }]
        );

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
/*
        var membersBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });
*/
        /******* group members grid ********/
        /*
        var groupMembersGridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            title: 'groupMembers',
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //plugins: columns, // [readColumn, addColumn, editColumn, deleteColumn],
            autoExpandColumn: 'accountName',
            //bbar: membersBottomToolbar,
            border: false
        }); 
        */
        
        // testing
        var groupMembersGridPanel = new Ext.Panel({
            region: 'center',
            title: 'test',
            html: 'hallo'
        });

        /******* THE edit dialog ********/
        
        var editGroupDialog = ({
            layout:'border',
            border:true,
            width: 600,
            height: 400,
            items:[{
            	region: 'north',
            	//region: 'center',
                layout:'column',
                //anchor:'100%',
                //autoHeight: true,
                height: 200,
                items:[{
                    columnWidth: 1,
                    layout: 'form',
                    border: false,
                    items:[{
                        xtype:'textfield',
                        fieldLabel:'Group Name', 
                        name:'name',
                        anchor:'95%',
                        allowBlank: false,
                    }, {
                        xtype:'textarea',
                        name: 'description',
                        fieldLabel: 'Description',
                        grow: false,
                        preventScrollbars:false,
                        anchor:'95%',
                        height: 120
                    }]        
                }]
            },
            accountPicker, 
            groupMembersGridPanel,

            ]
        });
               
        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'groupDialog',
            tbarItems: [],
            title: 'edit group',
            layout: 'border',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteGroup,
            handlerExport: this.handlers.exportGroup,
            items: editGroupDialog
        });

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateGroupRecord(_groupData);
        this.updateToolbarButtons(_groupData.grants);       

        dialog.getForm().loadRecord(this.groupRecord);
        
    } // end display function
    
}

/*********************************** GROUP MODEL ********************************************/

Ext.namespace('Tine.Admin.Model');
Tine.Admin.Model.Group = Ext.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
]);

Tine.Admin.Model.groupMember = Ext.data.Record.create([
    {name: 'accountId'},
    {name: 'groupId'},
]);
