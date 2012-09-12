/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this (don't use Ext.getCmp, extend generic grid panel, ...)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Groups');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Groups.Main = {
        
    // references to crated toolbar and grid panel
    groupsToolbar: null,
    gridPanel: null,
        
    actions: {
        addGroup: null,
        editGroup: null,
        deleteGroup: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addGroup: function (button, event) {
            this.openEditWindow(null);
        },

        /**
         * onclick handler for editBtn
         */
        editGroup: function (button, event) {
            var selectedRows = Ext.getCmp('AdminGroupsGrid').getSelectionModel().getSelections();
            this.openEditWindow(selectedRows[0]);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteGroup: function (button, event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected groups?'), function (button) {
                if (button === 'yes') {
                
                    var groupIds = [],
                        selectedRows = Ext.getCmp('AdminGroupsGrid').getSelectionModel().getSelections();
                        
                    for (var i = 0; i < selectedRows.length; ++i) {
                        groupIds.push(selectedRows[i].id);
                    }
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteGroups',
                            groupIds: groupIds
                        },
                        scope: this,
                        text: this.translation.gettext('Deleting group(s)...'),
                        success: function (result, request) {
                            Ext.getCmp('AdminGroupsGrid').getStore().reload();
                        },
                        failure: function (result, request) {
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
                'update': function (record) {
                    this.reload();
                }
            }                
        });
    },
    
    initComponent: function () {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.actions.addGroup = new Ext.Action({
            text: this.translation.gettext('Add Group'),
            disabled: true,
            handler: this.handlers.addGroup,
            iconCls: 'action_addGroup',
            scope: this
        });
        
        this.actions.editGroup = new Ext.Action({
            text: this.translation.gettext('Edit Group'),
            disabled: true,
            handler: this.handlers.editGroup,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteGroup = new Ext.Action({
            text: this.translation.gettext('Delete Group'),
            disabled: true,
            handler: this.handlers.deleteGroup,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayGroupsToolbar: function () {
        
        // if toolbar was allready created set active toolbar and return
        if (this.groupsToolbar) {
            Tine.Tinebase.MainScreen.setActiveToolbar(this.groupsToolbar, true);
            return;
        }
        
        var GroupsAdminQuickSearchField = new Ext.ux.SearchField({
            id: 'GroupsAdminQuickSearchField',
            width: 240,
            emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
        });
        GroupsAdminQuickSearchField.on('change', function () {
            Ext.getCmp('AdminGroupsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        this.groupsToolbar = new Ext.Toolbar({
            id: 'AdminGroupsToolbar',
            split: false,
            //height: 26,
            items: [{
                xtype: 'buttongroup',
                columns: 5,
                items: [
                    Ext.apply(new Ext.Button(this.actions.addGroup), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }), {xtype: 'tbspacer', width: 10},
                    Ext.apply(new Ext.Button(this.actions.editGroup), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }), {xtype: 'tbspacer', width: 10},
                    Ext.apply(new Ext.Button(this.actions.deleteGroup), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                ]
            }, '->', 
                this.translation.gettext('Search:'), 
                ' ',
                GroupsAdminQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(this.groupsToolbar, true);
    },

    displayGroupsGrid: function () {
        
        // if grid panel was allready created set active content panel and return
        if (this.gridPanel)    {
            Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
            return;
        }
        
        if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')) {
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
            fields: Tine.Admin.Model.Group,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('id', 'asc');

        dataStore.on('beforeload', function (dataStore, options) {
            options = options || {};
            options.params = options.params || {};
            options.params.filter = Ext.getCmp('GroupsAdminQuickSearchField').getValue();
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
                { id: 'id', header: this.translation.gettext('ID'), dataIndex: 'id', width: 50, hidden: true },
                { id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 50 },
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description' }
            ]
        });
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect: true});

        rowSelectionModel.on('selectionchange', function (selectionModel) {
            var rowCount = selectionModel.getCount();

            if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')) {
                if (rowCount < 1) {
                    // no row selected
                    this.actions.deleteGroup.setDisabled(true);
                    this.actions.editGroup.setDisabled(true);
                } else if (rowCount > 1) {
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
        this.gridPanel = new Ext.grid.GridPanel({
            id: 'AdminGroupsGrid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock: false,
            autoExpandColumn: 'n_family',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit: true,
                ignoreAdd: true,
                emptyText: this.translation.gettext('No groups to display')
            }),
            enableHdMenu: false,
            plugins: [new Ext.ux.grid.GridViewMenuPlugin()]
        });
        
        this.gridPanel.on('rowcontextmenu', function (grid, rowIndex, eventObject) {
            eventObject.stopEvent();
            if (! grid.getSelectionModel().isSelected(rowIndex)) {
                grid.getSelectionModel().selectRow(rowIndex);
            }
            
            if (! this.contextMenu) {
                this.contextMenu = new Ext.menu.Menu({
                    id: 'ctxMenuGroups', 
                    items: [
                        this.actions.editGroup,
                        this.actions.deleteGroup,
                        '-',
                        this.actions.addGroup 
                    ]
                });
            }
            this.contextMenu.showAt(eventObject.getXY());
        }, this);
        
        this.gridPanel.on('rowdblclick', function (gridPar, rowIndexPar, ePar) {
            if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')) {
                var record = gridPar.getStore().getAt(rowIndexPar);
                this.openEditWindow(record);
            }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function () {
        var dataStore = Ext.getCmp('AdminGroupsGrid').getStore();
        dataStore.load({ params: { start: 0, limit: 50 } });
    },

    show: function () {
        if (this.groupsToolbar === null || this.gridPanel === null) {
            this.initComponent();
        }

        this.displayGroupsToolbar();
        this.displayGroupsGrid();

        this.loadData();
    },
    
    reload: function () {
        if (Ext.ComponentMgr.all.containsKey('AdminGroupsGrid')) {
            setTimeout("Ext.getCmp('AdminGroupsGrid').getStore().reload()", 200);
        }
    }
};
