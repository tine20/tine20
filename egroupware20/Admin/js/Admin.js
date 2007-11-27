Ext.namespace('Egw.Admin');

Egw.Admin = function() {

    var _initialTree = [{
        text: 'Accounts',
        cls: 'treemain',
        allowDrag: false,
        allowDrop: true,
        id: 'accounts',
        icon: false,
        children: [],
        leaf: null,
        expanded: true,
        dataPanelType: 'accounts'
    },{
        "text":"Applications",
		"cls":"treemain",
		"allowDrag":false,
		"allowDrop":true,
		"id":"applications",
		"icon":false,
      /*"application":"Admin", */
		"children":[],
		"leaf":null,
		"expanded":true,
		"dataPanelType":"applications"
	},{
		"text":"Access Log",
		"cls":"treemain",
		"allowDrag":false,
		"allowDrop":true,
		"id":"accesslog",
		"icon":false,
	  /*"application":"Admin",*/
		"children":[],
		"leaf":null,
		"expanded":true,
		"dataPanelType":"accesslog"
	}];

	/**
     * creates the address grid
     *
     */
    var _getAdminTree = function() 
    {
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method   = 'Admin.getSubTree';
            _loader.baseParams.node     = _node.id;
            _loader.baseParams.location = 'mainTree';
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Admin',
            id: 'admin-tree',
            iconCls: 'AdminTreePanel',
            loader: treeLoader,
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

        for(var i=0; i<_initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
        	var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();

        	switch(_node.attributes.dataPanelType) {
                case 'accesslog':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminAccessLog') {
                        Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Egw.Admin.AccessLog.show();
                    }
                    
                    break;
                    
                case 'accounts':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminAccountsToolbar') {
                        Ext.getCmp('AdminAccountsGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Egw.Admin.Accounts.show();
                    }
                    
                    break;
                    
                case 'applications':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminApplications') {
                    	Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Egw.Admin.Applications.show();
                    }
                    
                    break;
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/applications');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

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
    };
    
    // public functions and variables
    return {
        getPanel: _getAdminTree
    };
    
}();

Egw.Admin.AccessLog = function() {

    /**
     * onclick handler for edit action
     */
    var _deleteHandler = function(_button, _event) {
    	Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected access log entries?', function(_button) {
    		if(_button == 'yes') {
    			var logIds = new Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].id);
                }
                
                Ext.data.Connection().request( {
                    url : 'index.php',
                    method : 'post',
                    scope : this,
                    params : {
                        method : 'Admin.deleteAccessLogEntries',
                        logIds : Ext.util.JSON.encode(logIds)
                    },
                    callback : function(_options, _success, _response) {
                        if(_success === true) {
                        	var result = Ext.util.JSON.decode(_response.responseText);
                        	if(result.success === true) {
                                Ext.getCmp('gridAdminAccessLog').getStore().reload();
                        	}
                        }
                    }
                });
    		}
    	});
    };

    var _selectAllHandler = function(_button, _event) {
    	Ext.getCmp('gridAdminAccessLog').getSelectionModel().selectAll();
    };

    var _action_delete = new Ext.Action({
        text: 'delete entry',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_delete'
    });

    var _action_selectAll = new Ext.Action({
        text: 'select all',
        handler: _selectAllHandler
    });

    var _contextMenuGridAdminAccessLog = new Ext.menu.Menu({
        items: [
            _action_delete,
            '-',
            _action_selectAll 
        ]
    });

    var _createDataStore = function()
    {
        /**
         * the datastore for accesslog entries
         */
        var ds_accessLog = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Admin.getAccessLogEntries'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'log_id',
            fields: [
                {name: 'sessionid'},
                {name: 'loginid'},
                {name: 'ip'},
                {name: 'li', type: 'date', dateFormat: 'c'},
                {name: 'lo', type: 'date', dateFormat: 'c'},
                {name: 'log_id'},
                {name: 'account_id'},
                {name: 'result'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        ds_accessLog.setDefaultSort('li', 'desc');

        ds_accessLog.on('beforeload', function(_dataSource) {
        	_dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        	
        	var from = Date.parseDate(
        	   Ext.getCmp('adminApplications_dateFrom').getRawValue(),
               'm/d/y'
        	);
            _dataSource.baseParams.from   = from.format("Y-m-d\\T00:00:00");

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               'm/d/y'
            );
            _dataSource.baseParams.to     = to.format("Y-m-d\\T23:59:59");
        });        
        
        ds_accessLog.load({params:{start:0, limit:50}});
        
        return ds_accessLog;
    };

    var _showToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id:        'quickSearchField',
            width:     200,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
        });
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id:             'adminApplications_dateFrom',
            allowBlank:     false,
            validateOnBlur: false,
            value:          oneWeekAgo
        });
        var dateTo = new Ext.form.DateField({
            id:             'adminApplications_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            value:          currentDate
        });
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarAdminAccessLog',
            split: false,
            height: 26,
            items: [
                _action_delete,'->',
                'Display from: ',
                ' ',
                dateFrom,
                new Ext.Toolbar.Spacer(),
                'to: ',
                ' ',
                dateTo,
                new Ext.Toolbar.Spacer(),
                '-',
                'Search:', ' ',
/*                new Ext.ux.SelectBox({
                  listClass:'x-combo-list-small',
                  width:90,
                  value:'Starts with',
                  id:'search-type',
                  store: new Ext.data.SimpleStore({
                    fields: ['text'],
                    expandData: true,
                    data : ['Starts with', 'Ends with', 'Any match']
                  }),
                  displayField: 'text'
                }), */
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.MainScreen.setActiveToolbar(toolbar);

        dateFrom.on('valid', function(_dateField) {
            var from = Date.parseDate(
               Ext.getCmp('adminApplications_dateFrom').getRawValue(),
               'm/d/y'
            );

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               'm/d/y'
            );
            
            if(from.getTime() > to.getTime()) {
            	Ext.getCmp('adminApplications_dateTo').setRawValue(Ext.getCmp('adminApplications_dateFrom').getRawValue());
            }

            Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
        });
        
        dateTo.on('valid', function(_dateField) {
            var from = Date.parseDate(
               Ext.getCmp('adminApplications_dateFrom').getRawValue(),
               'm/d/y'
            );

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               'm/d/y'
            );
            
            if(from.getTime() > to.getTime()) {
                Ext.getCmp('adminApplications_dateFrom').setRawValue(Ext.getCmp('adminApplications_dateTo').getRawValue());
            }

            Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
        });
    };
    
    var _renderResult = function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch (_value) {
            case '-3' :
                gridValue = 'invalid password';
                break;

            case '-2' :
                gridValue = 'ambiguous username';
                break;

            case '-1' :
                gridValue = 'user not found';
                break;

            case '0' :
                gridValue = 'failure';
                break;

            case '1' :
                gridValue = 'success';
                break;
        }
        
        return gridValue;
    };

    /**
     * creates the address grid
     * 
     */
    var _showGrid = function() 
    {
    	_action_delete.setDisabled(true);
    	
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying access log entries {0} - {1} of {2}',
            emptyMsg: "No access log entries to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: true, header: 'Session ID', id: 'sessionid', dataIndex: 'sessionid', width: 200, hidden: true},
            {resizable: true, header: 'Login Name', id: 'loginid', dataIndex: 'loginid'},
            {resizable: true, header: 'IP Address', id: 'ip', dataIndex: 'ip', width: 150},
            {resizable: true, header: 'Login Time', id: 'li', dataIndex: 'li', width: 120, renderer: Egw.Egwbase.Common.dateTimeRenderer},
            {resizable: true, header: 'Logout Time', id: 'lo', dataIndex: 'lo', width: 120, renderer: Egw.Egwbase.Common.dateTimeRenderer},
            {resizable: true, header: 'Account ID', id: 'account_id', dataIndex: 'account_id', width: 70, hidden: true},
            {resizable: true, header: 'Result', id: 'result', dataIndex: 'result', width: 110, renderer: _renderResult}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_delete.setDisabled(true);
            } else {
                _action_delete.setDisabled(false);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridAdminAccessLog',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'loginid',
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                _action_delete.setDisabled(false);
            }
            _contextMenuGridAdminAccessLog.showAt(_eventObject.getXY());
        });
        
/*        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
        });*/
    };
        
    // public functions and variables
    return {
        show: function() {
            _showToolbar();
            _showGrid();            
        }
    };
    
}();


Egw.Admin.Applications = function() {

    /**
     * onclick handler for edit action
     */
    var _editButtonHandler = function(_button, _event) {
        var selectedRows = Ext.getCmp('gridAdminApplications').getSelectionModel().getSelections();
        var applicationId = selectedRows[0].id;
        
        Egw.Egwbase.Common.openWindow('applicationWindow', 'index.php?method=Admin.getApplication&appId=' + applicationId, 800, 450);
    };
    
    var _enableDisableButtonHandler = function(_button, _event) {
    	//console.log(_button);
    	
    	var state = 0;
    	if(_button.id == 'Admin_Accesslog_Action_Enable') {
    		state = 1;
    	}
    	
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
                applicationIds : Ext.util.JSON.encode(applicationIds),
                state: state
            },
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        Ext.getCmp('gridAdminApplications').getStore().reload();
                    }
                }
            }
        });
    };
    

    var _action_enable = new Ext.Action({
        text: 'enable application',
        disabled: true,
        handler: _enableDisableButtonHandler,
        iconCls: 'action_enable',
        id: 'Admin_Accesslog_Action_Enable'
    });

    var _action_disable = new Ext.Action({
        text: 'disable application',
        disabled: true,
        handler: _enableDisableButtonHandler,
        iconCls: 'action_disable',
        id: 'Admin_Accesslog_Action_Disable'
    });

	var _action_settings = new Ext.Action({
        text: 'settings',
        disabled: true,
        handler: _editButtonHandler,
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
            id: 'app_id',
            fields: [
                {name: 'app_id'},
                {name: 'app_name'},
                {name: 'app_enabled'},
                {name: 'app_order'},
                {name: 'app_tables'},
                {name: 'app_version'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        ds_applications.setDefaultSort('app_name', 'asc');

        ds_applications.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        ds_applications.load({params:{start:0, limit:50}});
        
        return ds_applications;
    };

	var _showApplicationsToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
        });
        
        var applicationToolbar = new Ext.Toolbar({
            id: 'toolbarAdminApplications',
            split: false,
            height: 26,
            items: [
                _action_enable,
                _action_disable,
                '-',
                _action_settings,
                '->',
                'Search:', ' ',
/*                new Ext.ux.SelectBox({
                  listClass:'x-combo-list-small',
                  width:90,
                  value:'Starts with',
                  id:'search-type',
                  store: new Ext.data.SimpleStore({
                    fields: ['text'],
                    expandData: true,
                    data : ['Starts with', 'Ends with', 'Any match']
                  }),
                  displayField: 'text'
                }), */
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.MainScreen.setActiveToolbar(applicationToolbar);
    };
    
    var _renderEnabled = function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
    	switch(_value) {
            case '0':
              gridValue = 'disabled';
              break;
              
    		case '1':
    		  gridValue = 'enabled';
    		  break;
    		  
            case '2':
              gridValue = 'enabled (but hidden)';
              break;
              
            case '3':
              gridValue = 'enabled (new window)';
              break;
              
    		default:
    		  gridValue = 'unknown status (' + _value + ')';
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
        var ds_applications = _createApplicationaDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: ds_applications,
            displayInfo: true,
            displayMsg: 'Displaying application {0} - {1} of {2}',
            emptyMsg: "No applications to display"
        }); 
        
        var cm_applications = new Ext.grid.ColumnModel([
            {resizable: true, header: 'order', id: 'app_order', dataIndex: 'app_order', width: 50},
            {resizable: true, header: 'name', id: 'app_name', dataIndex: 'app_name'},
            {resizable: true, header: 'status', id: 'app_enabled', dataIndex: 'app_enabled', width: 150, renderer: _renderEnabled},
            {resizable: true, header: 'version', id: 'app_version', dataIndex: 'app_version', width: 70}
        ]);
        
        cm_applications.defaultSortable = true; // by default columns are sortable

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_enable.setDisabled(true);
                _action_disable.setDisabled(true);
                //_action_settings.setDisabled(true);
            } else if (rowCount > 1){
                _action_enable.setDisabled(false);
                _action_disable.setDisabled(false);
                //_action_settings.setDisabled(true);
            } else {
                _action_enable.setDisabled(false);
                _action_disable.setDisabled(false);
                //_action_settings.setDisabled(false);            	
            }
        });
                
        var grid_applications = new Ext.grid.GridPanel({
        	id: 'gridAdminApplications',
            store: ds_applications,
            cm: cm_applications,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'app_name',
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(grid_applications);

        grid_applications.on('rowclick', function(gridP, rowIndexP, eventP) {
            var rowCount = gridP.getSelectionModel().getCount();
            
/*            if(rowCount < 1) {
                action_edit.setDisabled(true);
                action_delete.setDisabled(true);
            } else if(rowCount == 1) {
                action_edit.setDisabled(false);
                action_delete.setDisabled(false);
            } else {
                action_edit.setDisabled(true);
                action_delete.setDisabled(false);
            } */
        });
        
        grid_applications.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);

/*                action_edit.setDisabled(false);
                action_delete.setDisabled(false);*/
            }
            //var record = _grid.getStore().getAt(rowIndex);
/*            ctxMenuListGrid.showAt(_eventObject.getXY()); */
        });
        
        grid_applications.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            try {
                Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + record.data.list_id, 800, 450);
            } catch(e) {
            //  alert(e);
            }
        });
        
        return;
    };   
    
    // public functions and variables
    return {
        show: function() {
        	_showApplicationsToolbar();
            _showApplicationsGrid();        	
        }
    };
    
}();

Egw.Admin.Accounts = function() {

    /**
     * onclick handler for edit action
     */
    var _editButtonHandler = function(_button, _event) {
        var selectedRows = Ext.getCmp('gridAdminApplications').getSelectionModel().getSelections();
        var applicationId = selectedRows[0].id;
        
        Egw.Egwbase.Common.openWindow('applicationWindow', 'index.php?method=Admin.getApplication&appId=' + applicationId, 800, 450);
    };
    
    var _enableDisableButtonHandler = function(_button, _event) {
        //console.log(_button);
        
        var state = 0;
        if(_button.id == 'Admin_Accesslog_Action_Enable') {
            state = 1;
        }
        
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
                applicationIds : Ext.util.JSON.encode(applicationIds),
                state: state
            },
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        Ext.getCmp('gridAdminApplications').getStore().reload();
                    }
                }
            }
        });
    };
    

    var _action_enable = new Ext.Action({
        text: 'enable application',
        disabled: true,
        handler: _enableDisableButtonHandler,
        iconCls: 'action_enable',
        id: 'Admin_Accesslog_Action_Enable'
    });

    var _action_disable = new Ext.Action({
        text: 'disable application',
        disabled: true,
        handler: _enableDisableButtonHandler,
        iconCls: 'action_disable',
        id: 'Admin_Accesslog_Action_Disable'
    });

    var _action_settings = new Ext.Action({
        text: 'settings',
        disabled: true,
        handler: _editButtonHandler,
        iconCls: 'action_settings'
    });
        
    var _createDataStore = function()
    {
        /**
         * the datastore for lists
         */
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getAccounts'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'account_id',
            fields: [
                {name: 'account_id'},
                {name: 'account_lid'},
                {name: 'account_familyname'},
                {name: 'account_givenname'},
                {name: 'account_emailaddress'},
                {name: 'account_lastlogin '},
                {name: 'account_lastloginfrom'},
                {name: 'account_lastpwd_change'},
                {name: 'account_status'},
                {name: 'account_expires '}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('account_lid', 'asc');

        dataStore.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        dataStore.load({params:{start:0, limit:50}});
        
        return dataStore;
    };

    var _showApplicationsToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('AdminAccountsGrid').getStore().load({params:{start:0, limit:50}});
        });
        
        var applicationToolbar = new Ext.Toolbar({
            id: 'AdminAccountsToolbar',
            split: false,
            height: 26,
            items: [
                _action_enable,
                _action_disable,
                '-',
                _action_settings,
                '->',
                'Search:', ' ',
/*                new Ext.ux.SelectBox({
                  listClass:'x-combo-list-small',
                  width:90,
                  value:'Starts with',
                  id:'search-type',
                  store: new Ext.data.SimpleStore({
                    fields: ['text'],
                    expandData: true,
                    data : ['Starts with', 'Ends with', 'Any match']
                  }),
                  displayField: 'text'
                }), */
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.MainScreen.setActiveToolbar(applicationToolbar);
    };
    
    var _renderEnabled = function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch(_value) {
            case '0':
              gridValue = 'disabled';
              break;
              
            case '1':
              gridValue = 'enabled';
              break;
              
            case '2':
              gridValue = 'enabled (but hidden)';
              break;
              
            case '3':
              gridValue = 'enabled (new window)';
              break;
              
            default:
              gridValue = 'unknown status (' + _value + ')';
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
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying accounts {0} - {1} of {2}',
            emptyMsg: "No accounts to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: true, header: 'ID', id: 'account_id', dataIndex: 'account_id', width: 50},
            {resizable: true, header: 'Login name', id: 'account_lid', dataIndex: 'account_lid'},
            {resizable: true, header: 'First name', id: 'account_familyname', dataIndex: 'account_familyname', width: 150, renderer: _renderEnabled},
            {resizable: true, header: 'Given Name', id: 'account_givenname', dataIndex: 'account_givenname'},
            {resizable: true, header: 'Email', id: 'account_emailaddress', dataIndex: 'account_emailaddress'},
            {resizable: true, header: 'Last login at', id: 'account_lastlogin', dataIndex: 'account_lastlogin'},
            {resizable: true, header: 'Last login from', id: 'account_lastloginfrom', dataIndex: 'account_lastloginfrom'},
            {resizable: true, header: 'Password changed', id: 'account_lastpwd_change', dataIndex: 'account_lastpwd_change'},
            {resizable: true, header: 'Status', id: 'account_status', dataIndex: 'account_status'},
            {resizable: true, header: 'Expires', id: 'account_expires', dataIndex: 'account_expires'}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable

        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_enable.setDisabled(true);
                _action_disable.setDisabled(true);
                //_action_settings.setDisabled(true);
            } else if (rowCount > 1){
                _action_enable.setDisabled(false);
                _action_disable.setDisabled(false);
                //_action_settings.setDisabled(true);
            } else {
                _action_enable.setDisabled(false);
                _action_disable.setDisabled(false);
                //_action_settings.setDisabled(false);              
            }
        });
                
        var grid_applications = new Ext.grid.GridPanel({
            id: 'AdminAccountsGrid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'account_familyname',
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(grid_applications);

        grid_applications.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);

/*                action_edit.setDisabled(false);
                action_delete.setDisabled(false);*/
            }
            //var record = _grid.getStore().getAt(rowIndex);
/*            ctxMenuListGrid.showAt(_eventObject.getXY()); */
        });
        
        grid_applications.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            try {
                Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + record.data.list_id, 800, 450);
            } catch(e) {
            //  alert(e);
            }
        });
        
        return;
    };   
    
    // public functions and variables
    return {
        show: function() {
            _showApplicationsToolbar();
            _showApplicationsGrid();            
        }
    };
    
}();
