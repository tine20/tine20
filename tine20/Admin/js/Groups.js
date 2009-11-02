/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         split this into two files (grid + edit dlg)
 * TODO         refactor this (don't use Ext.getCmp, etc.)
 */

Ext.namespace('Tine.Admin.Groups');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Groups.Main = {
    
    
    actions: {
        addGroup: null,
        editGroup: null,
        deleteGroup: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addGroup: function(_button, _event) {
            //Tine.Admin.Groups.EditDialog.openWindow({});
            this.openEditWindow(null);
        },

        /**
         * onclick handler for editBtn
         */
        editGroup: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminGroupsGrid').getSelectionModel().getSelections();
            //Tine.Admin.Groups.EditDialog.openWindow({group: selectedRows[0]});
            this.openEditWindow(selectedRows[0]);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteGroup: function(_button, _event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected groups?'), function(_button){
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
                        text: this.translation.gettext('Deleting group(s)...'),
                        success: function(_result, _request){
                            Ext.getCmp('AdminGroupsGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the group.'));
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
        var popupWindow = Tine.Admin.Groups.EditDialog.openWindow({
            group: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.reload();
                }
            }                
        });
    },
    
    initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.actions.addGroup = new Ext.Action({
            text: this.translation.gettext('add group'),
            disabled: true,
            handler: this.handlers.addGroup,
            iconCls: 'action_addGroup',
            scope: this
        });
        
        this.actions.editGroup = new Ext.Action({
            text: this.translation.gettext('edit group'),
            disabled: true,
            handler: this.handlers.editGroup,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteGroup = new Ext.Action({
            text: this.translation.gettext('delete group'),
            disabled: true,
            handler: this.handlers.deleteGroup,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayGroupsToolbar: function() {
        var GroupsAdminQuickSearchField = new Ext.ux.SearchField({
            id: 'GroupsAdminQuickSearchField',
            width:240,
            emptyText: this.translation.gettext('enter searchfilter')
        }); 
        GroupsAdminQuickSearchField.on('change', function(){
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
                this.translation.gettext('Search:'), 
                ' ',
                GroupsAdminQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(groupsToolbar);
    },

    displayGroupsGrid: function() {
        if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts') ) {
            this.actions.addGroup.setDisabled(false);
        }

    	// the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getGroups'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Group,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('id', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('GroupsAdminQuickSearchField').getValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying groups {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No groups to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { id: 'id', header: this.translation.gettext('ID'), dataIndex: 'id', width: 10, hidden: true },
                { id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 50 },
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description' }
            ]
        });
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts') ) {
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
                emptyText: this.translation.gettext('No groups to display')
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
        	if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts') ) {
                var record = _gridPar.getStore().getAt(_rowIndexPar);
                //Tine.Admin.Groups.EditDialog.openWindow({group: record});
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
        var dataStore = Ext.getCmp('AdminGroupsGrid').getStore();
            
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

        if(currentToolbar === false || currentToolbar.id != 'AdminGroupsToolbar') {
            this.displayGroupsToolbar();
            this.displayGroupsGrid();
        }
        this.loadData();
    },
    
    reload: function() {
        if(Ext.ComponentMgr.all.containsKey('AdminGroupsGrid')) {
            setTimeout ("Ext.getCmp('AdminGroupsGrid').getStore().reload()", 200);
        }
    }
};





/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Groups.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * var group
     */
    group: null,

    windowNamePrefix: 'groupEditWindow_',
    
    id : 'groupDialog',
    layout: 'fit',
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();
        
        if(form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Memberships'));
            
            // get group members
            var groupMembers = [];
            this.membersStore.each(function(record){
                groupMembers.push(record.id);
            });
            
            // update record with form data               
            form.updateRecord(this.group);

            /*********** save group members & form ************/
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveGroup', 
                    groupData: Ext.util.JSON.encode(this.group.data),
                    groupMembers: Ext.util.JSON.encode(groupMembers)
                },
                success: function(response) {
                    /*
                    if(window.opener.Tine.Admin.Groups) {
                        window.opener.Tine.Admin.Groups.Main.reload();
                    }
                    */
                    this.fireEvent('update', Ext.util.JSON.encode(this.group.data));
                    if(_closeWindow === true) {
                        //window.close();
                        this.window.close()
                    } else {
                        this.onRecordLoad(response);
                    }
                    Ext.MessageBox.hide();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save group.')); 
                },
                scope: this 
            });
                
            
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    handlerDelete: function(_button, _event) {
        var groupIds = Ext.util.JSON.encode([Tine.Admin.Groups.EditDialog.group.data.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteGroups', 
                groupIds: groupIds
            },
            text: this.translation.gettext('Deleting group...'),
            success: function(_result, _request) {
                if(window.opener.Tine.Admin.Groups) {
                    window.opener.Tine.Admin.Groups.Main.reload();
                }
                window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the group.')); 
            } 
        });                           
    },

    /**
     * function updateRecord
     */
    updateRecord: function(_groupData) {
    	// if groupData is empty (=array), set to empty object because array won't work!
        if (_groupData.length === 0) {
        	_groupData = {};
        }
        this.group = new Tine.Tinebase.Model.Group(_groupData, _groupData.id ? _groupData.id : 0);
        
        // tweak, as group members are not in standard form cycle yet
        this.membersStore.loadData(this.group.get('groupMembers'));
    },

    /**
     * function updateToolbarButtons
     * 
     */
    updateToolbarButtons: function(_rights) {        
    },
    
    /**
     * function getFormContents
     * 
     */
    getFormContents: function() {
        this.membersStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Account
        });

        var accountPickerGridPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Group Members'),
            store: this.membersStore,
            region: 'center',
            anchor: '100% 100%'
        });
        
        var editGroupDialog = {
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
	                        fieldLabel: this.translation.gettext('Group Name'), 
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
                accountPickerGridPanel
            ]
        };
        
        return editGroupDialog;
    },
    
    initComponent: function() {
        this.group = this.group ? this.group : new Tine.Tinebase.Model.Group({}, 0);
        
        //this.title = title: 'Edit Group ' + ,
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getGroup',
                groupId: this.group.id
            }
        });
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.items = this.getFormContents();
        Tine.Admin.Groups.EditDialog.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);

        if (! this.group.id) {
            window.document.title = this.translation.gettext('Add new group');
        } else {
            window.document.title = String.format(this.translation.gettext('Edit Group "{0}"'), this.group.get('name'));
        }

        this.getForm().loadRecord(this.group);
        this.updateToolbarButtons();
        //Ext.MessageBox.hide();
    }
});


/**
 * Groups Edit Popup
 */
Tine.Admin.Groups.EditDialog.openWindow = function (config) {
    config.group = config.group ? config.group : new Tine.Tinebase.Model.Group({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.Admin.Groups.EditDialog.prototype.windowNamePrefix + config.group.id,
        layout: Tine.Admin.Groups.EditDialog.prototype.windowLayout,
        contentPanelConstructor: 'Tine.Admin.Groups.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};