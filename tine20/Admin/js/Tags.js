/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         split into two files (grid & edit dlg)
 * TODO         fix autoheight in edit dlg (use border layout?)
 */
 
/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Tags');

Tine.Admin.Tags.Main = {
    
    //  references to created toolbar and grid panel
    tagsToolbar: null,
    gridPanel: null,
    
    actions: {
        addTag: null,
        editTag: null,
        deleteTag: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addTag: function (button, event) {
            Tine.Admin.Tags.EditDialog.openWindow({
                tag: null,
                listeners: {
                    scope: this,
                    'update': function (record) {
                        this.reload();
                    }
                }
            });
        },

        /**
         * onclick handler for editBtn
         */
        editTag: function (button, event) {
            var selectedRows = this.gridPanel.getSelectionModel().getSelections();
            Tine.Admin.Tags.EditDialog.openWindow({
                tag: selectedRows[0],
                listeners: {
                    scope: this,
                    'update': function (record) {
                        this.reload();
                    }
                }
            });
        },
        
        /**
         * onclick handler for deleteBtn
         */
        deleteTag: function (button, event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected tags?'), function (button) {
                if (button === 'yes') {
                
                    var tagIds = [],
                        selectedRows = this.gridPanel.getSelectionModel().getSelections();
                        
                    for (var i = 0; i < selectedRows.length; ++i) {
                        tagIds.push(selectedRows[i].id);
                    }
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteTags',
                            tagIds: tagIds
                        },
                        text: this.translation.gettext('Deleting tag(s)...'),
                        success: function (result, request) {
                            this.gridPanel.getStore().reload();
                        },
                        scope: this
                    });
                }
            }, this);
        }    
    },
    
    initComponent: function () {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.pageSize = Tine.Tinebase.registry.get('preferences').get('pageSize')
            ? parseInt(Tine.Tinebase.registry.get('preferences').get('pageSize'), 10)
            : 50;
        
        this.actions.addTag = new Ext.Action({
            text: this.translation.gettext('Add Tag'),
            handler: this.handlers.addTag,
            iconCls: 'action_tag',
            scope: this,
            disabled: !(Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags'))
        });
        
        this.actions.editTag = new Ext.Action({
            text: this.translation.gettext('Edit Tag'),
            disabled: true,
            handler: this.handlers.editTag,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTag = new Ext.Action({
            text: this.translation.gettext('Delete Tag'),
            disabled: true,
            handler: this.handlers.deleteTag,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayTagsToolbar: function () {
        // if toolbar was allready created set active toolbar and return
        if (this.tagsToolbar) {
            Tine.Tinebase.MainScreen.setActiveToolbar(this.tagsToolbar, true);
            return;
        }
        
        var TagsQuickSearchField = new Ext.ux.SearchField({
            id: 'TagsQuickSearchField',
            width: 240,
            emptyText: i18n._hidden('enter searchfilter')
        });
        
        TagsQuickSearchField.on('change', function () {
            this.gridPanel.getStore().load({
                params: {
                    start: 0,
                    limit: this.pageSize
                }
            });
        }, this);
        
        this.tagsToolbar = new Ext.Toolbar({
            canonicalName: ['Tag', 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
            split: false,
            //height: 26,
            items: [{
                xtype: 'buttongroup',
                columns: 5,
                items: [
                    Ext.apply(new Ext.Button(this.actions.addTag), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.actions.editTag), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.actions.deleteTag), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                ]
            }, '->', 
                this.translation.gettext('Search:'), 
                ' ',
                TagsQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(this.tagsToolbar, true);
    },

    displayTagsGrid: function () {
        // if grid panel was allready created set active content panel and return
        if (this.gridPanel) {
            Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
            return;
        }
        
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

        dataStore.on('beforeload', function (dataStore, options) {
            options = options || {};
            options.params = options.params || {};
            options.params.query = Ext.getCmp('TagsQuickSearchField').getValue();
        }, this);
                
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: this.pageSize,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying tags {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No tags to display")
        });
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { id: 'id', header: this.translation.gettext('ID'), dataIndex: 'id', hidden: true, width: 40 },
                { id: 'color', header: this.translation.gettext('Color'), dataIndex: 'color', width: 25, renderer: function (color,meta,record) {
                    return '<div style="margin-top:1px;width: 8px; height: 8px; background-color:' + color + '; border-radius:5px;border: 1px solid black;" title="' + record.get('name') + ' (' +  i18n._('Usage:&#160;') + record.get('occurrence') + ')">&#160;</div>';
                }},
                { id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 200 },
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description', width: 500}
            ]
        });
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect: true});

        rowSelectionModel.on('selectionchange', function (selectionModel) {
            var rowCount = selectionModel.getCount();

            if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags')) {
                if (rowCount < 1) {
                    // no row selected
                    this.actions.deleteTag.setDisabled(true);
                    this.actions.editTag.setDisabled(true);
                } else if (rowCount > 1) {
                    // more than one row selected
                    this.actions.deleteTag.setDisabled(false);
                    this.actions.editTag.setDisabled(true);
                } else {
                    // only one row selected
                    this.actions.deleteTag.setDisabled(false);
                    this.actions.editTag.setDisabled(false);
                }
            }
        }, this);
        
        this.gridPanel = new Ext.grid.GridPanel({
            canonicalName: ['Tag', 'Grid'].join(Tine.Tinebase.CanonicalPath.separator),
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock: false,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit: true,
                ignoreAdd: true,
                emptyText: this.translation.gettext('No tags to display')
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
                    id: 'ctxMenuTags',
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   'Tinebase-MainContextMenu'
                    }],
                    items: [
                        this.actions.editTag,
                        this.actions.deleteTag,
                        '-',
                        this.actions.addTag 
                    ]
                });
            }
            this.contextMenu.showAt(eventObject.getXY());
        }, this);
        
        this.gridPanel.on('rowdblclick', function (gridPar, rowIndexPar, ePar) {
            var record = gridPar.getStore().getAt(rowIndexPar);
            Tine.Admin.Tags.EditDialog.openWindow({
                tag: record,
                listeners: {
                    scope: this,
                    'update': function (record) {
                        this.reload();
                    }
                }
            });
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function () {
        var dataStore = this.gridPanel.getStore();
        dataStore.load({ params: { start: 0, limit: this.pageSize } });
    },

    show: function () {
        if (this.tagsToolbar === null || this.gridPanel) {
            this.initComponent();
        }

        this.displayTagsToolbar();
        this.displayTagsGrid();

        this.loadData();
    },

    reload: function () {
        if (this.gridPanel) {
            this.gridPanel.getStore().reload.defer(200, this.gridPanel.getStore());
        }
    }
};
