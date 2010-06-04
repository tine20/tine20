/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         refactor this
 * TODO         translate strings (enable/disable/settings)
 */
 
Ext.ns('Tine.Admin', 'Tine.Admin.Applications');

/*********************************** MAIN DIALOG ********************************************/

Tine.Admin.Applications.Main = function() {

	// references to created toolbar and grid panel
	var applicationToolbar = null;
	var grid_applications = null;
	
    /**
     * onclick handler for edit action
     * 
     * TODO     make that more generic?
     */
    var _settingsHandler = function(_button, _event) {
        var selModel = Ext.getCmp('gridAdminApplications').getSelectionModel();
        if (selModel.getCount() > 0) {
            var selectedRows = selModel.getSelections();
            var appName = selectedRows[0].data.name;
            if (Tine[appName]) {
                _openSettingsWindow(appName);
            }
        } else {
            _button.setDisabled(true);
        }
    };
    
    var _openSettingsWindow = function(appName) {
        Tine[appName].AdminPanel.openWindow({
            record: (Tine[appName].Model.Settings) ? new Tine[appName].Model.Settings(appName) : null,
            title: String.format(_('{0} Settings'), translateAppTitle(appName)),
            listeners: {
                scope: this,
                'update': (Tine[appName].AdminPanel.onUpdate) ? Tine[appName].AdminPanel.onUpdate : Ext.emptyFn
            }
        });
    }

    var _enableDisableButtonHandler = function(state) {
        var applicationIds = new Array();
        var selectedRows = Ext.getCmp('gridAdminApplications').getSelectionModel().getSelections();
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
                        //Ext.getCmp('gridAdminApplications').getStore().reload();
                        // reload mainscreen because apps have to be loaded / unloaded
                        window.location = window.location.href.replace(/#+.*/, '');
                    }
                }
            }
        });
    };
    

    var _action_enable = new Ext.Action({
        text: 'enable application',
        disabled: true,
        handler: _enableDisableButtonHandler.createDelegate(this, ['enabled']),
        iconCls: 'action_enable'
    });

    var _action_disable = new Ext.Action({
        text: 'disable application',
        disabled: true,
        handler: _enableDisableButtonHandler.createDelegate(this, ['disabled']),
        iconCls: 'action_disable'
    });

	var _action_settings = new Ext.Action({
        text: 'settings',
        disabled: true,
        handler: _settingsHandler,
        iconCls: 'action_settings'
    });

	var _createApplicationaDataStore = function()
    {
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
            fields: [
                {name: 'id'},
                {name: 'name'},
                {name: 'status'},
                {name: 'order'},
                {name: 'app_tables'},
                {name: 'version'}
            ],
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
    };

	var _showApplicationsToolbar = function()
    {
    	// if toolbar was allready created set active toolbar and return
    	if (applicationToolbar)
    	{
    		Tine.Tinebase.MainScreen.setActiveToolbar(applicationToolbar, true);
    		return;
    	}
    	
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        _action_enable.setText(this.translation.gettext('enable application'));
        _action_disable.setText(this.translation.gettext('disable application'));
        //_action_settings.setText(this.translation.gettext('settings'));
    
        var ApplicationsAdminQuickSearchField = new Ext.ux.SearchField({
            id: 'ApplicationsAdminQuickSearchField',
            width:240,
            emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
        }); 
        ApplicationsAdminQuickSearchField.on('change', function() {
            Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
        });
        
        applicationToolbar = new Ext.Toolbar({
            id: 'toolbarAdminApplications',
            split: false,
            //height: 26,
            items: [{
				xtype: 'buttongroup',
				columns: 7,
				items: [
					Ext.apply(new Ext.Button(_action_enable), {
					scale: 'medium',
					rowspan: 2,
					iconAlign: 'top'
					}), {xtype: 'tbspacer', width: 10},
					Ext.apply(new Ext.Button(_action_disable), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					}), {xtype: 'tbspacer', width: 10},
					{xtype: 'tbseparator'}, {xtype: 'tbspacer', width: 10},
					Ext.apply(new Ext.Button(_action_settings), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					})
				]
			}, '->',
                this.translation.gettext('Search:'), ' ',
//				new Ext.ux.SelectBox({
//                	listClass:'x-combo-list-small',
//                  	width:90,
//                  	value:'Starts with',
//                  	id:'search-type',
//                  	store: new Ext.data.SimpleStore({
//                    	fields: ['text'],
//                    	expandData: true,
//                    	data : ['Starts with', 'Ends with', 'Any match']
//                  	}),
//                  	displayField: 'text'
//                }),
                ' ',
                ApplicationsAdminQuickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(applicationToolbar, true);
    };
    
    /**
     * translate and return app title
     * 
     * TODO try to generalize this fn as this gets used in Tags.js + RoleEditDialog.js as well 
     *      -> this could be moved to Tine.Admin.Application after Admin js refactoring
     */
    var translateAppTitle = function(appName) {
        var app = Tine.Tinebase.appMgr.get(appName);
        return (app) ? app.getTitle() : appName;
    };

    /**
     * render enabled field (translate)
     */
    var _renderEnabled = function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var translation = new Locale.Gettext();
        translation.textdomain('Admin');
        
        var gridValue;
        
    	switch(_value) {
            case 'disabled':
                gridValue = translation.gettext('disabled');
                break;
    		case 'enabled':
    		  gridValue = translation.gettext('enabled');
    		  break;
    		  
    		default:
    		  gridValue = String.format(translation.gettext('unknown status ({0})'), value);
    		  break;
    	}
        
        return gridValue;
	};

    /**
	 * creates the address grid
	 * 
	 */
    var _showApplicationsGrid = function() 
    {
    	// if grid panel was allready created set active content panel and return
    	if (grid_applications) {
    		Tine.Tinebase.MainScreen.setActiveContentPanel(grid_applications, true);
    		return;
    	}
    	
        var ctxMenuGrid = new Ext.menu.Menu({
            items: [
                _action_enable,
                _action_disable,
                _action_settings
            ]
        });

    	
        var ds_applications = _createApplicationaDataStore();
        
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
                { header: this.translation.gettext('Name'),    id: 'name', dataIndex: 'name', renderer: translateAppTitle},
                { header: this.translation.gettext('Status'),  id: 'status', dataIndex: 'status', width: 150, renderer: _renderEnabled},
                { header: this.translation.gettext('Version'), id: 'version', dataIndex: 'version', width: 70}
            ]
        });

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();
            var selected = _selectionModel.getSelections();

            if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'apps') ) {
                if (rowCount < 1) {
                    _action_enable.setDisabled(true);
                    _action_disable.setDisabled(true);
                    _action_settings.setDisabled(true);
                } else if (rowCount > 1) {
                    _action_enable.setDisabled(false);
                    _action_disable.setDisabled(false);
                    _action_settings.setDisabled(true);
                } else {
                    _action_enable.setDisabled(false);
                    _action_disable.setDisabled(false);
                    // check if app has admin panel and is enabled
                    if (Tine[selected[0].data.name].AdminPanel && selected[0].data.status == 'enabled') {
                        _action_settings.setDisabled(false);
                    } else {
                        _action_settings.setDisabled(true);
                    }
                }
                
                // don't allow to disable Admin, Tinebase or Addressbook as we can't deal with this yet
                for (var i=0; i<selected.length; i++) {
                    if (typeof selected[i].get == 'function' && selected[i].get('name').toString().match(/Tinebase|Admin|Addressbook/)) {
                        _action_enable.setDisabled(true);
                        _action_disable.setDisabled(true);
                        break;
                    }
                }
            }
        });
                
        grid_applications = new Ext.grid.GridPanel({
        	id: 'gridAdminApplications',
            store: ds_applications,
            cm: cm_applications,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'name',
            border: false,
            viewConfig: {            
                /**
                 * Return CSS class to apply to rows depending upon flags
                 * - checks Flagged, Deleted and Seen
                 * 
                 * @param {} record
                 * @param {} index
                 * @return {String}
                 */
                getRowClass: function(record, index) {
                    //console.log(record);
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
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(grid_applications, true);
        
        grid_applications.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);

                if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'apps') ) {
                    _action_enable.setDisabled(false);
                    _action_disable.setDisabled(false);
                }
                
                // don't allow to disable Admin, Tinebase or Addressbook as we can't deal with this yet
				if(_grid.getSelectionModel().getSelected().get('name').toString().match(/Tinebase|Admin|Addressbook/)) {
					_action_enable.setDisabled(true);
					_action_disable.setDisabled(true);
				}
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
        grid_applications.on('rowdblclick', function(grid, index, e) {
            var record = grid.getStore().getAt(index);
            if (Tine[record.data.name].AdminPanel && record.data.status == 'enabled') {
                _openSettingsWindow(record.data.name);
            }
        }, this);
          
        return;
    };   
    
    // public functions and variables
    return {
        show: function() 
        {
        	_showApplicationsToolbar();
            _showApplicationsGrid();
            
            this.loadData();
        },
        
	    loadData: function()
	    {
	        var dataStore = Ext.getCmp('gridAdminApplications').getStore();
	        dataStore.load({ params: { start:0, limit:50 } });
	    },
	    
	    reload: function() 
		{
		    if(Ext.ComponentMgr.all.containsKey('gridAdminApplications')) {
		        setTimeout ("Ext.getCmp('gridAdminApplications').getStore().reload()", 200);
		    }
		}
    };
    
}();
