
/*********************************** TINE ADMIN ROLES  ********************************************/
/*********************************** TINE ADMIN ROLES  ********************************************/

Ext.namespace('Tine.Admin.Roles');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Roles.Main = {
    
    
    actions: {
        addRole: null,
        editRole: null,
        deleteRole: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addRole: function(_button, _event) {
            Tine.Tinebase.Common.openWindow('roleWindow', "index.php?method=Admin.editRole&roleId=",650, 600);
        },

        /**
         * onclick handler for editBtn
         */
        editRole: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminRolesGrid').getSelectionModel().getSelections();
            var roleId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('roleWindow', 'index.php?method=Admin.editRole&roleId=' + roleId,650, 600);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteRole: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected roles?', function(_button){
                if (_button == 'yes') {
                
                    var roleIds = new Array();
                    var selectedRows = Ext.getCmp('AdminRolesGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        roleIds.push(selectedRows[i].id);
                    }
                    
                    roleIds = Ext.util.JSON.encode(roleIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteRoles',
                            roleIds: roleIds
                        },
                        text: 'Deleting role(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('AdminRolesGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the role.');
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function()
    {
        this.actions.addRole = new Ext.Action({
            text: 'add role',
            handler: this.handlers.addRole,
            iconCls: 'action_permissions',
            scope: this
        });
        
        this.actions.editRole = new Ext.Action({
            text: 'edit role',
            disabled: true,
            handler: this.handlers.editRole,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteRole = new Ext.Action({
            text: 'delete role',
            disabled: true,
            handler: this.handlers.deleteRole,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayRolesToolbar: function()
    {
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('AdminRolesGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var rolesToolbar = new Ext.Toolbar({
            id: 'AdminRolesToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addRole, 
                this.actions.editRole,
                this.actions.deleteRole,
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(rolesToolbar);
    },

    displayRolesGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getRoles'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Role,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('id', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying roles {0} - {1} of {2}',
            emptyMsg: "No roles to display"
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: 'ID', dataIndex: 'id', width: 10 },
            { resizable: true, id: 'name', header: 'Name', dataIndex: 'name', width: 50 },
            { resizable: true, id: 'description', header: 'Description', dataIndex: 'description' }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteRole.setDisabled(true);
                this.actions.editRole.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteRole.setDisabled(false);
                this.actions.editRole.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteRole.setDisabled(false);
                this.actions.editRole.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'AdminRolesGrid',
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
                emptyText: 'No roles to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuRoles', 
                items: [
                    this.actions.editRole,
                    this.actions.deleteRole,
                    '-',
                    this.actions.addRole 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            try {
                Tine.Tinebase.Common.openWindow('roleWindow', 'index.php?method=Admin.editRole&roleId=' + record.data.id,650, 600);
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
        var dataStore = Ext.getCmp('AdminRolesGrid').getStore();
            
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

        if(currentToolbar === false || currentToolbar.id != 'AdminRolesToolbar') {
            this.displayRolesToolbar();
            this.displayRolesGrid();
        }
        this.loadData();
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('AdminRolesGrid')) {
            setTimeout ("Ext.getCmp('AdminRolesGrid').getStore().reload()", 200);
        }
    }
};

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Roles.EditDialog = {
	
    /**
     * returns index of record in the store
     * @private
     */
    getRecordIndex: function(account, dataStore) {
        
        var id = false;
        dataStore.each(function(item){
            //console.log (item);
            if ((item.data.type == 'user' || item.data.type == 'account') &&
                    account.data.type == 'user' &&
                    item.data.id == account.data.id) {
                id = item.id;
            } else if (item.data.account_type == 'group' &&
                    account.data.type == 'group' &&
                    item.data.id == account.data.id) {
                id = item.id;
            }
        });
        
        return id ? dataStore.indexOfId(id) : false;
    },  

    /**
     * var handlers
     */
     handlers: {
        removeAccount: function(_button, _event) 
        { 
            var roleGrid = Ext.getCmp('roleMembersGrid');
            var selectedRows = roleGrid.getSelectionModel().getSelections();
            
            var roleMembersStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                roleMembersStore.remove(selectedRows[i]);
            }
                
        },
        
        addAccount: function(account)
        {
        	var roleGrid = Ext.getCmp('roleMembersGrid');
            
            var dataStore = roleGrid.getStore();
            var selectionModel = roleGrid.getSelectionModel();
            
            //console.log ( account );
            
            // check if exists
            var recordIndex = Tine.Admin.Roles.EditDialog.getRecordIndex(account, dataStore);
            
            if (recordIndex === false) {
                var record = new Ext.data.Record({
                    id: account.data.id,
                    type: account.data.type,
                    name: account.data.name
                }, account.data.id);
                dataStore.addSorted(record);
            }
            selectionModel.selectRow(dataStore.indexOfId(account.data.id));            
        },
        
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('roleDialog').getForm();
            
            if(form.isValid()) {

            	// get role members
                var roleGrid = Ext.getCmp('roleMembersGrid');

                Ext.MessageBox.wait('Please wait', 'Updating Memberships');
                
                var roleMembers = [];
                var dataStore = roleGrid.getStore();
                
                dataStore.each(function(_record){
                    //roleMembers.push(_record.data.accountId);
                	roleMembers.push(_record.data);
                });

                // update form               
                form.updateRecord(Tine.Admin.Roles.EditDialog.roleRecord);

                /*********** save role members & form ************/
                
                Ext.Ajax.request({
                    params: {
                        method: 'Admin.saveRole', 
                        roleData: Ext.util.JSON.encode(Tine.Admin.Roles.EditDialog.roleRecord.data),
                        roleMembers: Ext.util.JSON.encode(roleMembers)
                    },
                    success: function(_result, _request) {
                     	if(window.opener.Tine.Admin.Roles) {
                            window.opener.Tine.Admin.Roles.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                        	var response = Ext.util.JSON.decode(_result.responseText);
                            //console.log(response);
                            this.updateRoleRecord(response.updatedData);
                            form.loadRecord(this.roleRecord);
                            
                        	// @todo   get roleMembers from result
                        	/*
                        	var roleMembers = Ext.util.JSON.decode(_result.responseText);
                            dataStore.loadData(roleMembers, false);
                            */
                        	
                        	Ext.MessageBox.hide();
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save role.'); 
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

        deleteRole: function(_button, _event) 
        {
            var roleIds = Ext.util.JSON.encode([Tine.Admin.Roles.EditDialog.roleRecord.data.id]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Admin.deleteRoles', 
                    roleIds: roleIds
                },
                text: 'Deleting role...',
                success: function(_result, _request) {
                    if(window.opener.Tine.Admin.Roles) {
                        window.opener.Tine.Admin.Roles.Main.reload();
                    }
                    window.close();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the role.'); 
                } 
            });                           
        }
        
     },
     
    /**
     * var roleRecord
     */
    roleRecord: null,
    

    /**
     * function updateRoleRecord
     */
    updateRoleRecord: function(_roleData)
    {
    	// if roleData is empty (=array), set to empty object because array won't work!
        if (_roleData.length === 0) {
        	_roleData = {};
        }
        this.roleRecord = new Tine.Tinebase.Model.Role(_roleData);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights)
    {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('roleDialog').action_saveAndClose.enable();
            Ext.getCmp('roleDialog').action_applyChanges.enable();
        }

        if(_rights.deleteGrant === true) {
            Ext.getCmp('roleDialog').action_delete.enable();
        }
        Ext.getCmp('roleDialog').action_delete.enable();
        */
    },

    /**
     * creates the rights tree
     *
     */
    getRightsTree: function() 
    {
    	/*
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                method: 'Admin.getRoleRights',
            	roleId: roleRecord.data.id
            }
        });
    	
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
        }, this);
        */
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Role Application Rights',
            id: 'rights',
            iconCls: 'AdminTreePanel',
            //loader: treeLoader,
            rootVisible: false,
            border: false
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        treePanel.setRootNode(treeRoot);

        // get tree data from json / Admin.getRoleRights
        var _rights = [{
            text: 'Addressbook',
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'addressbook',
            icon: false,
            children: [{
                text: 'Admin',
                checked: true,
                id: 'admin',
                icon: false,            
            },{
                text: 'Run',
                checked: false,
                id: 'run',
                icon: false,            
            }],
            leaf: null,
            expanded: false,
            //dataPanelType: 'accounts'
        },{
            text: 'CRM',
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'groupss',
            icon: false,
            children: [],
            leaf: null,
            expanded: true,
            //dataPanelType: 'groups' 
        }];
        
        for(var i=0; i<_rights.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_rights[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            //var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();
            /*
            switch(_node.attributes.dataPanelType) {
                case 'accesslog':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminAccessLog') {
                        Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.AccessLog.Main.show();
                    }
                    
                    break;
                    
                    
            } */
        }, this);

        /*
        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/applications');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
        */
        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
            /* switch(_node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(_event.getXY());
                    break;
            } */
        });

        return treePanel;
    },
    
    /**
     * function display
     * 
     * @param   _roleData
     * @param   _roleMembers
     * 
     */
    display: function(_roleData, _roleMembers) 
    {
    	
    	//console.log ( _roleMembers );

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

        var accountPicker =  new Tine.widgets.account.PickerPanel ({            
            enableBbar: true,
            height: 300,
            selectType: 'both',
            selectTypeDefault: 'group', 
            //bbar: this.userSelectionBottomToolBar,
            /*selectAction: function() {            	
                this.account = account;
                this.handlers.addAccount(account);
            } */ 
        });
                
        accountPicker.on('accountdblclick', function(account){
            this.account = account;
            this.handlers.addAccount(account);
        }, this);


        /******* load role members data store ********/

        this.dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            //id: 'id',
            fields: Tine.Tinebase.Model.Account
        });

        Ext.StoreMgr.add('RoleMembersStore', this.dataStore);
        
        this.dataStore.setDefaultSort('name', 'asc');        
        
        if (_roleMembers.length === 0) {
        	this.dataStore.removeAll();
        } else {
            this.dataStore.loadData( _roleMembers );
        }

        /******* column model ********/

        var columnModel = new Ext.grid.ColumnModel([{ 
        	resizable: true, id: 'name', header: 'Name', dataIndex: 'name', width: 30 
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

        /******* role members grid ********/
 
    	var roleMembersGridPanel = new Ext.grid.EditorGridPanel({
        	id: 'roleMembersGrid',
        	title: 'Role Members',
        	height: 300,
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            //autoExpandColumn: 'accountLoginName',
            autoExpandColumn: 'name',
            bbar: membersBottomToolbar,
            border: true
        }); 
        
        /******* rights tree ********/
        
        var rightsTreePanel = this.getRightsTree();
 
        /******* tab panels ********/
    	
        var tabPanelMembers = {
            title:'Members',
            layout:'column',
            layoutOnTabChange:true,            
            deferredRender:false,
            border:false,
            items:[
                accountPicker, 
                roleMembersGridPanel
            ]
        };
        
        var tabPanelRights = {
            title:'Rights',
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            anchor:'100% 100%',
            border:false,
            items:[
                rightsTreePanel
                /*
                new Ext.Panel ({
                   title: 'rights tree panel'
                })
                */
            ]
        };
    	
        /******* THE edit dialog ********/
        
        var editRoleDialog = {
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
	                        fieldLabel:'Role Name', 
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
                new Ext.TabPanel({
                    plain:true,
                    region: 'center',
                    activeTab: 0,
                    id: 'editMainTabPanel',
                    layoutOnTabChange:true,  
                    items:[
                        tabPanelMembers, 
                        tabPanelRights
                    ]
                })
            ]
        };
        
        /******* build panel & viewport & form ********/
               
        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'roleDialog',
            title: 'Edit Role ' + _roleData.name,
            layout: 'fit',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteRole,
            handlerExport: this.handlers.exportRole,
            items: editRoleDialog
        });

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateRoleRecord(_roleData);
        //this.updateToolbarButtons(_roleData.grants);       

        dialog.getForm().loadRecord(this.roleRecord);
        
    } // end display function     
    
};
