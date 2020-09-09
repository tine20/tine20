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
            var selectedRows = this.gridPanel.getSelectionModel().getSelections();
            this.openEditWindow(selectedRows[0]);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteGroup: function (button, event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected groups?'), function (button) {
                if (button === 'yes') {
                
                    var groupIds = [],
                        selectedRows = this.gridPanel.getSelectionModel().getSelections();
                        
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
                            this.gridPanel.getStore().reload();
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
            record: record,
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
        
        this.pageSize = Tine.Tinebase.registry.get('preferences').get('pageSize')
            ? parseInt(Tine.Tinebase.registry.get('preferences').get('pageSize'), 10)
            : 50;
        
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
        var app = Tine.Tinebase.appMgr.get('Admin');

        // if toolbar was allready created set active toolbar and return
        if (this.groupsToolbar) {
            app.getMainScreen().setActiveToolbar(this.groupsToolbar, true);
            return;
        }
        
        var GroupsAdminQuickSearchField = new Ext.ux.SearchField({
            id: 'GroupsAdminQuickSearchField',
            width: 240,
            emptyText: i18n._hidden('enter searchfilter')
        });
        GroupsAdminQuickSearchField.on('change', function () {
            this.gridPanel.getStore().load({
                params: {
                    start: 0,
                    limit: this.pageSize
                }
            });
        }, this);
        
        this.groupsToolbar = new Ext.Toolbar({
            canonicalName: ['Group', 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
            split: false,
            items: [{
                xtype: 'buttongroup',
                columns: 5,
                items: [
                    Ext.apply(new Ext.Button(this.actions.addGroup), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.actions.editGroup), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
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

        app.getMainScreen().setActiveToolbar(this.groupsToolbar, true);
    },

    displayGroupsGrid: function () {
        var app = Tine.Tinebase.appMgr.get('Admin');

        // if grid panel was allready created set active content panel and return
        if (this.gridPanel)    {
            app.getMainScreen().setActiveContentPanel(this.gridPanel, true);
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
            pageSize: this.pageSize,
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
                { id: 'email', header: this.translation.gettext('E-mail'), dataIndex: 'email', width: 50 },
                { id: 'account_only', header: this.translation.gettext('Account only'), dataIndex: 'account_only', renderer: Tine.Tinebase.common.booleanRenderer},
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description' },
                { id: 'creation_time',      header: i18n._('Creation Time'),         dataIndex: 'creation_time',         renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
                { id: 'created_by',         header: i18n._('Created By'),            dataIndex: 'created_by',            renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true },
                { id: 'last_modified_time', header: i18n._('Last Modified Time'),    dataIndex: 'last_modified_time',    renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
                { id: 'last_modified_by',   header: i18n._('Last Modified By'),      dataIndex: 'last_modified_by',      renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true }
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
            canonicalName: ['Group', 'Grid'].join(Tine.Tinebase.CanonicalPath.separator),
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
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   'Tinebase-MainContextMenu'
                    }],
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
        app.getMainScreen().setActiveContentPanel(this.gridPanel, true);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function () {
        var dataStore = this.gridPanel.getStore();
        dataStore.load({ params: { start: 0, limit: this.pageSize } });
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
        if (this.gridPanel) {
            this.gridPanel.getStore().reload.defer(200, this.gridPanel.getStore());
        }
    }
};
