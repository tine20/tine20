/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Roles
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
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
            Tine.Admin.Roles.EditDialog.openWindow({role: null});
        },

        /**
         * onclick handler for editBtn
         */
        editRole: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminRolesGrid').getSelectionModel().getSelections();
            
            Tine.Admin.Roles.EditDialog.openWindow({role: selectedRows[0]});
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteRole: function(_button, _event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected roles?'), function(_button){
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
                        text: this.translation.gettext('Deleting role(s)...'),
                        success: function(_result, _request){
                            Ext.getCmp('AdminRolesGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the role.'));
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.actions.addRole = new Ext.Action({
            text: this.translation.gettext('add role'),
            disabled: true,
            handler: this.handlers.addRole,
            iconCls: 'action_permissions',
            scope: this
        });
        
        this.actions.editRole = new Ext.Action({
            text: this.translation.gettext('edit role'),
            disabled: true,
            handler: this.handlers.editRole,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteRole = new Ext.Action({
            text: this.translation.gettext('delete role'),
            disabled: true,
            handler: this.handlers.deleteRole,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayRolesToolbar: function() {
        var RolesQuickSearchField = new Ext.ux.SearchField({
            id: 'RolesQuickSearchField',
            width:240,
            emptyText: this.translation.gettext('enter searchfilter')
        }); 
        RolesQuickSearchField.on('change', function(){
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
                this.translation.gettext('Search:'), 
                ' ',
                RolesQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(rolesToolbar);
    },

    displayRolesGrid: function() {
        if ( Tine.Tinebase.hasRight('manage', 'roles') ) {
            this.actions.addRole.setDisabled(false);
        }    	
    	
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
            _dataStore.baseParams.query = Ext.getCmp('RolesQuickSearchField').getValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying roles {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No roles to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation.gettext('ID'), dataIndex: 'id', width: 10 },
            { resizable: true, id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 50 },
            { resizable: true, id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description' }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if ( Tine.Tinebase.hasRight('manage', 'roles') ) {
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
                emptyText: this.translation.gettext('No roles to display')
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
        	if ( Tine.Tinebase.hasRight('manage', 'roles') ) {
                var record = _gridPar.getStore().getAt(_rowIndexPar);
                Tine.Admin.Roles.EditDialog.openWindow({role: record});
        	}
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function() {
        var dataStore = Ext.getCmp('AdminRolesGrid').getStore();
            
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function() {
        this.initComponent();
        
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'AdminRolesToolbar') {
            this.displayRolesToolbar();
            this.displayRolesGrid();
        }
        this.loadData();
    },
    
    reload: function() {
        if(Ext.ComponentMgr.all.containsKey('AdminRolesGrid')) {
            setTimeout ("Ext.getCmp('AdminRolesGrid').getStore().reload()", 200);
        }
    }
};

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Roles.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {

    /**
     * @cfg {Tine.Tinebase.Model.Role}
     */
    role: null,
    
    /**
     * @property {Object} holds allRights (possible rights)
     */
    allRights: null,
    
    /**
     * @property {Ext.tree.treePanel}
     */
    rightsTreePanel: null,
    
    windowNamePrefix: 'rolesEditWindow_',
    
    layout: 'fit',
    id : 'roleDialog',
    labelWidth: 120,
    labelAlign: 'top',
	
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
        removeAccount: function(_button, _event) { 
            var roleGrid = Ext.getCmp('roleMembersGrid');
            var selectedRows = roleGrid.getSelectionModel().getSelections();
            
            var roleMembersStore = this.membersDataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                roleMembersStore.remove(selectedRows[i]);
            }
                
        },
        
        addAccount: function(account) {
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
        
     },
     
     handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();
        
        if(form.isValid()) {
            // get role members
            var roleGrid = Ext.getCmp('roleMembersGrid');

            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
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
            form.updateRecord(this.role);

            /*********** save role members & form ************/
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveRole', 
                    roleData: Ext.util.JSON.encode(this.role.data),
                    roleMembers: Ext.util.JSON.encode(roleMembers),
                    roleRights: Ext.util.JSON.encode(roleRights)
                },
                success: function(response) {
                    if(window.opener.Tine.Admin.Roles) {
                        window.opener.Tine.Admin.Roles.Main.reload();
                    }
                    if(_closeWindow === true) {
                        window.close();
                    } else {
                        this.onRecordLoad(response);
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save role.')); 
                },
                scope: this 
            });
                
            
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    handlerDelete: function(_button, _event) {
        var roleIds = Ext.util.JSON.encode([this.role.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteRoles', 
                roleIds: roleIds
            },
            text: this.translation.gettext('Deleting role...'),
            success: function(_result, _request) {
                if(window.opener.Tine.Admin.Roles) {
                    window.opener.Tine.Admin.Roles.Main.reload();
                }
                window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the role.')); 
            } 
        });                           
    },
    
    
    /**
     * var rights storage
     */
    rightsDataStore: null,

    updateRecord: function(_roleData) {
    	// if roleData is empty (=array), set to empty object because array won't work!
        if (_roleData.length === 0) {
        	_roleData = {};
        }
        this.role = new Tine.Tinebase.Model.Role(_roleData, _roleData.id ? _roleData.id : 0);
        
        this.membersDataStore.loadData(this.role.get('roleMembers'));
        this.rightsDataStore.loadData(this.role.get('roleRights'));
        this.allRights = this.role.get('allRights');
        this.createRightsTreeNodes();
    },

    /**
     * creates the rights tree
     *
     */
    initRightsTree: function() {
        
        this.rightsTreePanel = new Ext.tree.TreePanel({
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

        this.rightsTreePanel.setRootNode(treeRoot);
    },
    
    createRightsTreeNodes: function () {
        var _allRights = this.allRights;
        var treeRoot = this.rightsTreePanel.getRootNode();
        
        var toRemove = [];
        treeRoot.eachChild(function(node){
            toRemove.push(node);
        });
        
        // add nodes to tree        
        for(var i=0; i<_allRights.length; i++) {
            // don't duplicate tree nodes on 'apply changes'
            toRemove[i] ? toRemove[i].remove() : null;
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
        
        return this.rightsTreePanel;
    },
    
    /**
     * returns form
     * 
     */
    getFormContents: function() {
        
        /******* account picker + members grid panel ********/
 
        var membersPanel = new Tine.widgets.account.ConfigGrid({
            //height: 300,
            accountPickerType: 'both',
            accountPickerTypeDefault: 'group', 
            accountListTitle: this.translation.gettext('Role members'),
            configStore: this.membersDataStore,
            hasAccountPrefix: true,
            configColumns: []
        });        

        /******* tab panels ********/
    	this.initRightsTree();
        
        var tabPanelMembers = {
            title:this.translation.gettext('Members'),
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
            title:this.translation.gettext('Rights'),
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            autoScroll: true,
            anchor:'100% 100%',
            border:false,
            items:[
                this.rightsTreePanel
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
	                        fieldLabel: this.translation.gettext('Role Name'), 
	                        name:'name',
	                        anchor:'100%',
	                        allowBlank: false
	                    }, {
	                        xtype:'textarea',
	                        name: 'description',
	                        fieldLabel: this.translation.gettext('Description'),
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
        
        return editRoleDialog;
    },
    
    initComponent: function() {
        this.role = this.role ? this.role : new Tine.Tinebase.Model.Role({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        //this.title = title: 'Edit Role ' + ,
        
        Ext.MessageBox.wait(this.translation._('Loading Role...'), this.translation._('Please Wait'));
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getRole',
                roleId: this.role.id
            }
        });
        
        // init role members store
        this.membersDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            //id: 'id',
            //fields: Tine.Tinebase.Model.Account
            fields: [ 'account_name', 'account_id', 'account_type' ]
        });
        Ext.StoreMgr.add('RoleMembersStore', this.membersDataStore);        
        // this.membersDataStore.setDefaultSort('account_name', 'asc');
        
        // init rights store
        this.rightsDataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Roles.Right
        });
        Ext.StoreMgr.add('RoleRightsStore', this.rightsDataStore);
        //this.rightsDataStore.setDefaultSort('right', 'asc');
        
        this.items = this.getFormContents();
        Tine.Admin.Groups.EditDialog.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.role.id) {
            window.document.title = this.translation.gettext('Add New Role');
        } else {
            window.document.title = sprintf(this.translation.gettext('Edit Role "%s"'), this.role.get('name'));
        }
        
        this.getForm().loadRecord(this.role);
        Ext.MessageBox.hide();
    }
    
});

/**
 * Roles Edit Popup
 */
Tine.Admin.Roles.EditDialog.openWindow = function (config) {
    config.role = config.role ? config.role : new Tine.Tinebase.Model.Role({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 600,
        name: Tine.Admin.Roles.EditDialog.prototype.windowNamePrefix + config.role.id,
        layout: Tine.Admin.Roles.EditDialog.prototype.windowLayout,
        itemsConstructor: 'Tine.Admin.Roles.EditDialog',
        itemsConstructorConfig: config
    });
    return window;
};

/**
 * Model of a right
 */
Tine.Admin.Roles.Right = Ext.data.Record.create([
    {name: 'application_id'},
    {name: 'right'}
]);


