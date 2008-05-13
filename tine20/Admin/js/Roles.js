
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
     * check if right is set for application and get the record id
     * @private
     */
    getRightId: function(applicationId, right) {
        
        var id = false;
        var result = 0;
        this.rightsDataStore.each(function(item){
            if ( item.data.application_id == applicationId && item.data.right == right ) {
            	result = item.id;
            	//console.log ("hit");
            	return;
            }
        });
        
        return result;
    },  
    
    /**
     * var handlers
     */
     handlers: {
        removeAccount: function(_button, _event) 
        { 
            var roleGrid = Ext.getCmp('roleMembersGrid');
            var selectedRows = roleGrid.getSelectionModel().getSelections();
            
            var roleMembersStore = this.membersDataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                roleMembersStore.remove(selectedRows[i]);
            }
                
        },
        
        addAccount: function(account)
        {
        	var roleGrid = Ext.getCmp('roleMembersGrid');
            
            var dataStore = roleGrid.getStore();
            var selectionModel = roleGrid.getSelectionModel();
            
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
                
                //var dataStore = roleGrid.getStore();
                
                var roleMembers = [];
                var membersStore = Ext.StoreMgr.lookup('RoleMembersStore');
                membersStore.each(function(_record){
                	roleMembers.push(_record.data);
                });

                // get role rights                
                var roleRights = [];
                var rightsStore = Ext.StoreMgr.get('RoleRightsStore');
                
                rightsStore.each(function(_record){
                    roleRights.push(_record.data);
                });

                // update form               
                form.updateRecord(Tine.Admin.Roles.EditDialog.roleRecord);

                /*********** save role members & form ************/
                
                Ext.Ajax.request({
                    params: {
                        method: 'Admin.saveRole', 
                        roleData: Ext.util.JSON.encode(Tine.Admin.Roles.EditDialog.roleRecord.data),
                        roleMembers: Ext.util.JSON.encode(roleMembers),
                        roleRights: Ext.util.JSON.encode(roleRights),
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
     * var rights storage
     */
    rightsDataStore: null,

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
     * creates the rights tree
     *
     */
    getRightsTree: function(_allRights) 
    {
    
        var treePanel = new Ext.tree.TreePanel({
            id: 'rightsTree',
            iconCls: 'AdminTreePanel',
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
                        
        // add nodes to tree        
        for(var i=0; i<_allRights.length; i++) {

        	var node = new Ext.tree.TreeNode(_allRights[i]);
        	node.attributes.application_id = _allRights[i].application_id;
        	node.expanded = true;
        	treeRoot.appendChild(node);
        	
        	// append children        	
        	if ( _allRights[i].children ) {
        	
                for(var j=0; j < _allRights[i].children.length; j++) {
                
                	var childData = _allRights[i].children[j];
                	childData.leaf = true;
                    childData.icon = "s.gif";                    
                	
                	// check if right is set
                	var rightIsSet = ( this.getRightId(_allRights[i].application_id,childData.right) > 0 );
                	childData.checked = rightIsSet;
                    
                    var child = new Ext.tree.TreeNode(childData);
                    child.attributes.right = childData.right;
                	
                	// add onchange handler
                	child.on('checkchange', function(_node, _checked) {
                	
                	    // get parents application id
                	    var applicationId = _node.parentNode.attributes.application_id;
                	
                	    // put it in the storage or remove it                        
                	    if ( _checked ) {
                   	        this.rightsDataStore.add (
                   	            new Ext.data.Record({
                                    //right: _node.text,
                   	            	right: _node.attributes.right,
                                    application_id: applicationId
                                })
                            );
                        } else {
                            var rightId = this.getRightId(applicationId,_node.attributes.right);
                            this.rightsDataStore.remove ( this.rightsDataStore.getById(rightId) );                                                                                         
                        }   
                        
                        //console.log ( this.rightsDataStore );
                        
                	},this);
                	
                	node.appendChild(child);                	
                }		
        	}
        	
        }     
        
        return treePanel;
    },
    
    /**
     * function display
     * 
     * @param   _roleData
     * @param   _roleMembers
     * 
     */
    display: function(_roleData, _roleMembers, _roleRights, _allRights) 
    {
    	
    	//console.log ( _roleMembers );
        //console.log ( _roleRights );

        /******* load role members data store ********/

        this.membersDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            //id: 'id',
            //fields: Tine.Tinebase.Model.Account
            fields: [ 'account_name', 'account_id', 'account_type' ]
        });

        Ext.StoreMgr.add('RoleMembersStore', this.membersDataStore);        
        // this.membersDataStore.setDefaultSort('account_name', 'asc');        
        
        if (_roleMembers.length === 0) {
            this.membersDataStore.removeAll();
        } else {
            this.membersDataStore.loadData( _roleMembers );
        }
        
        /******* account picker + members grid panel ********/
 
        var membersPanel = new Tine.widgets.account.ConfigGrid({
            //height: 300,
            accountPickerType: 'both',
            accountPickerTypeDefault: 'group', 
            accountListTitle: 'Role members',
            configStore: this.membersDataStore,
            hasAccountPrefix: true,
            configColumns: []
        });        

        /******* load role rights data store ********/

        this.rightsDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Roles.Right
        });

        Ext.StoreMgr.add('RoleRightsStore', this.rightsDataStore);
        
        //this.rightsDataStore.setDefaultSort('right', 'asc');        
        
        if (_roleRights.length === 0) {
            this.rightsDataStore.removeAll();
        } else {
            this.rightsDataStore.loadData( _roleRights );
        }
        
        /******* rights tree ********/
        
        var rightsTreePanel = this.getRightsTree(_allRights);
 
        /******* tab panels ********/
    	
        var tabPanelMembers = {
            title:'Members',
            //layout:'column',
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            border:false,
            items:[
                membersPanel
            ]
        };
        
        var tabPanelRights = {
            title:'Rights',
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            autoScroll: true,
            anchor:'100% 100%',
            border:false,
            items:[
                rightsTreePanel
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
            //title: 'Edit Role ' + _roleData.name,
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

        dialog.getForm().loadRecord(this.roleRecord);
        
    } // end display function     
    
};

/**
 * Model of a right
 */
Tine.Admin.Roles.Right = Ext.data.Record.create([
    {name: 'application_id'},
    {name: 'right'},
]);


