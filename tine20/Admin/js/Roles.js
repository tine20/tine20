/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        refactor this (don't use Ext.getCmp, etc.)
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
            this.openEditWindow(null);
        },

        /**
         * onclick handler for editBtn
         */
        editRole: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminRolesGrid').getSelectionModel().getSelections();
            this.openEditWindow(selectedRows[0]);
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
                            Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the role.'));
                        }
                    });
                }
            }, this);
        }    
    },
    
    /**
     * open edit window
     * 
     * @param {} record
     */
    openEditWindow: function (record) {
        var popupWindow = Tine.Admin.Roles.EditDialog.openWindow({
            role: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.reload();
                }
            }                
        });
    },
    
    /**
     * init roles grid
     */
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
            emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
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
        if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'roles') ) {
            this.actions.addRole.setDisabled(false);
        }    	
    	
        // the datastore
        var dataStore = new Ext.data.DirectStore({
            api: {
                read: Tine.Admin.getRoles
            },
            
            reader: new Ext.data.JsonReader({
                root: 'results',
                idProperty: 'id',
                totalProperty: 'totalcount'
            }, Tine.Tinebase.Model.Role),
            
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
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { id: 'id', header: this.translation.gettext('ID'), dataIndex: 'id', hidden: true, width: 10 },
                { id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 50 },
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description' }
            ]
        });
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'roles') ) {
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
        	if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'roles') ) {
                var record = _gridPar.getStore().getAt(_rowIndexPar);
                this.openEditWindow(record);
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


/**
 * Model of a right
 */
Tine.Admin.Roles.Right = Ext.data.Record.create([
    {name: 'application_id'},
    {name: 'right'}
]);


