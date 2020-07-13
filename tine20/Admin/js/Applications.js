/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this
 * TODO         translate strings (enable/disable/settings)
 */
 
Ext.ns('Tine.Admin', 'Tine.Admin.Applications');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Applications.Main = {

    init: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');

        this._action_enable = new Ext.Action({
            text: this.translation.gettext('Enable Application'),
            disabled: true,
            handler: this._enableDisableButtonHandler.createDelegate(this, ['Enabled']),
            scope: this,
            iconCls: 'action_enable'
        });

        this._action_disable = new Ext.Action({
            text: this.translation.gettext('Disable Application'),
            disabled: true,
            handler: this._enableDisableButtonHandler.createDelegate(this, ['Disabled']),
            scope: this,
            iconCls: 'action_disable'
        });

        this._action_settings = new Ext.Action({
            text: this.translation.gettext('Settings'),
            disabled: true,
            handler: this._settingsHandler,
            scope: this,
            iconCls: 'action_settings'
        });
    },

    /**
     * onclick handler for edit action
     * 
     * TODO     make that more generic?
     */
    _settingsHandler: function(_button, _event) {
        var selModel = this.gridPanel.getSelectionModel();
        if (selModel.getCount() > 0) {
            var selectedRows = selModel.getSelections();
            var appName = selectedRows[0].data.name;
            if (Tine[appName]) {
                this._openSettingsWindow(appName);
            }
        } else {
            _button.setDisabled(true);
        }
    },
    
    _openSettingsWindow: function(appName) {
        Tine[appName].AdminPanel.openWindow({
            record: (Tine[appName].Model.Settings) ? new Tine[appName].Model.Settings(appName) : null,
            title: String.format(this.translation.gettext('{0} Settings'), this.translateAppTitle(appName)),
            listeners: {
                scope: this,
                'update': (Tine[appName].AdminPanel.onUpdate) ? Tine[appName].AdminPanel.onUpdate : Ext.emptyFn
            }
        });
    },

    _enableDisableButtonHandler: function(state) {
        var applicationIds = new Array();
        var selectedRows = this.gridPanel.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            applicationIds.push(selectedRows[i].id);
        }
        
        Ext.Ajax.request({
            url : 'index.php',
            method : 'post',
            params : {
                method : 'Admin.setApplicationState',
                applicationIds : applicationIds,
                state: state
            },
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        // reload mainscreen because apps have to be loaded / unloaded
                        Tine.Tinebase.common.reload();
                    }
                }
            }
        });
    },
    
    _createApplicationaDataStore: function() {
        /**
         * the datastore for lists
         */
        var ds_applications = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Admin.getApplications'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Admin.Model.Application,
            // turn on remote sorting
            remoteSort: true
        });
        
        ds_applications.setDefaultSort('name', 'asc');

        ds_applications.on('beforeload', function(_dataSource, _options) {
            _options = _options || {};
            _options.params = _options.params || {};
            _options.params.filter = Ext.getCmp('ApplicationsAdminQuickSearchField').getValue();
        });
        
        //ds_applications.load({params:{start:0, limit:50}});
        
        return ds_applications;
    },

    _showApplicationsToolbar: function() {
        // if toolbar was allready created set active toolbar and return
        if (this.actionToolbar)
        {
            Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
            return;
        }
        
        this._action_enable.setText(this.translation.gettext('Enable Application'));
        this._action_disable.setText(this.translation.gettext('Disable Application'));
        this._action_settings.setText(this.translation.gettext('Settings'));
    
        var ApplicationsAdminQuickSearchField = new Ext.ux.SearchField({
            id: 'ApplicationsAdminQuickSearchField',
            width:240,
            emptyText: i18n._hidden('enter searchfilter')
        });
        ApplicationsAdminQuickSearchField.on('change', function() {
            this.gridPanel.getStore().load({params:{start:0, limit:50}});
        }, this);
        
        this.actionToolbar = new Ext.Toolbar({
            canonicalName: ['Application', 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
            split: false,
            //height: 26,
            items: [{
                xtype: 'buttongroup',
                columns: 7,
                items: [
                    Ext.apply(new Ext.Button(this._action_enable), {
                    scale: 'medium',
                    rowspan: 2,
                    iconAlign: 'top'
                    }), {xtype: 'tbspacer', width: 10},
                    Ext.apply(new Ext.Button(this._action_disable), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }), {xtype: 'tbspacer', width: 10},
                    {xtype: 'tbseparator'}, {xtype: 'tbspacer', width: 10},
                    Ext.apply(new Ext.Button(this._action_settings), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                ]
            }, '->',
                this.translation.gettext('Search:'), ' ',
//                new Ext.ux.SelectBox({
//                    listClass:'x-combo-list-small',
//                      width:90,
//                      value:'Starts with',
//                      id:'search-type',
//                      store: new Ext.data.SimpleStore({
//                        fields: ['text'],
//                        expandData: true,
//                        data : ['Starts with', 'Ends with', 'Any match']
//                      }),
//                      displayField: 'text'
//                }),
                ' ',
                ApplicationsAdminQuickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    },
    
    /**
     * translate and return app title
     * 
     * TODO try to generalize this fn as this gets used in Tags.js + RoleEditDialog.js as well 
     *      -> this could be moved to Tine.Admin.Application after Admin js refactoring
     */
    translateAppTitle: function(appName) {
        var app = Tine.Tinebase.appMgr.get(appName);
        return (app) ? app.getTitle() : appName;
    },

    /**
     * render enabled field (translate)
     */
    _renderEnabled: function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch(_value) {
            case 'disabled':
                gridValue = this.translation.gettext('Disabled');
                break;
            case 'enabled':
              gridValue = this.translation.gettext('Enabled');
              break;
              
            default:
              gridValue = String.format(this.translation.gettext('Unknown status ({0})'), value);
              break;
        }
        
        return gridValue;
    },

    /**
     * creates the address grid
     * 
     */
    _showApplicationsGrid: function()
    {
        // if grid panel was allready created set active content panel and return
        if (this.gridPanel) {
            Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
            return;
        }
        
        var ctxMenuGrid = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: [
                this._action_enable,
                this._action_disable,
                this._action_settings
            ]
        });

        
        var ds_applications = this._createApplicationaDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: ds_applications,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying application {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No applications to display")
        });
        
        var cm_applications = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { header: this.translation.gettext('Order'),   id: 'order', dataIndex: 'order', width: 50},
                { header: this.translation.gettext('Name'),    id: 'name', dataIndex: 'name', renderer: this.translateAppTitle.createDelegate(this)},
                { header: this.translation.gettext('Status'),  id: 'status', dataIndex: 'status', width: 150, renderer: this._renderEnabled.createDelegate(this)},
                { header: this.translation.gettext('Version'), id: 'version', dataIndex: 'version', width: 70}
            ]
        });

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();
            var selected = _selectionModel.getSelections();

            if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'apps') ) {
                if (rowCount < 1) {
                    this._action_enable.setDisabled(true);
                    this._action_disable.setDisabled(true);
                    this._action_settings.setDisabled(true);
                } else if (rowCount > 1) {
                    this._action_enable.setDisabled(false);
                    this._action_disable.setDisabled(false);
                    this._action_settings.setDisabled(true);
                } else {
                    this._action_enable.setDisabled(false);
                    this._action_disable.setDisabled(false);
                    // check if app has admin panel and is enabled
                    if (Tine[selected[0].data.name] && Tine[selected[0].data.name].AdminPanel && selected[0].data.status == 'enabled') {
                        this._action_settings.setDisabled(false);
                    } else {
                        this._action_settings.setDisabled(true);
                    }
                }
                
                // don't allow to disable Admin, Tinebase or Addressbook as we can't deal with this yet
                for (var i=0; i<selected.length; i++) {
                    if (typeof selected[i].get == 'function' && selected[i].get('name').toString().match(/Tinebase|Admin|Addressbook/)) {
                        this._action_disable.setDisabled(true);
                        break;
                    }
                }
            }
        }, this);
                
        this.gridPanel = new Ext.grid.GridPanel({
            canonicalName: ['Application', 'Grid'].join(Tine.Tinebase.CanonicalPath.separator),
            store: ds_applications,
            cm: cm_applications,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            autoExpandColumn: 'name',
            border: false,
            viewConfig: {
                /**
                 * Return CSS class to apply to rows depending upon flags
                 * - checks Flagged, Deleted and Seen
                 * 
                 * @param {Object} record
                 * @param {Integer} index
                 * @return {String}
                 */
                getRowClass: function(record, index) {
                    var className = '';
                    switch(record.get('status')) {
                        case 'disabled':
                            className = 'grid_row_disabled';
                            break;
                        case 'enabled':
                            className = 'grid_row_enabled';
                            break;
                    }
                    return className;
                }
            }
        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
        
        this.gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);

                if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'apps') ) {
                    this._action_enable.setDisabled(false);
                    this._action_disable.setDisabled(false);
                }
                
                // don't allow to disable Admin, Tinebase or Addressbook as we can't deal with this yet
                if(_grid.getSelectionModel().getSelected().get('name').toString().match(/Tinebase|Admin|Addressbook/)) {
                    this._action_enable.setDisabled(true);
                    this._action_disable.setDisabled(true);
                }
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
        this.gridPanel.on('rowdblclick', function(grid, index, e) {
            var record = grid.getStore().getAt(index);
            if (record.data.status == 'enabled' && Tine[record.data.name] && Tine[record.data.name].AdminPanel) {
                this._openSettingsWindow(record.data.name);
            }
        }, this);
    },

    show: function()
    {
        this.init();
        this._showApplicationsToolbar();
        this._showApplicationsGrid();

        this.loadData();
    },

    loadData: function()
    {
        var dataStore = this.gridPanel.getStore();
        dataStore.load({ params: { start:0, limit:50 } });
    },

    reload: function () {
        if (this.gridPanel) {
            this.gridPanel.getStore().reload.defer(200, this.gridPanel.getStore());
        }
    }
};
