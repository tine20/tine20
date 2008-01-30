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
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
                method: 'Admin.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
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
                        Egw.Admin.AccessLog.Main.show();
                    }
                    
                    break;
                    
                case 'accounts':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminAccountsToolbar') {
                        Ext.getCmp('AdminAccountsGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Egw.Admin.Accounts.Main.show();
                    }
                    
                    break;
                    
                case 'applications':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminApplications') {
                    	Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Egw.Admin.Applications.Main.show();
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

Ext.namespace('Egw.Admin.AccessLog');
Egw.Admin.AccessLog.Main = function() {

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
                
                Ext.Ajax.request( {
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
                {name: 'accountName'},
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
            {resizable: true, header: 'Name', id: 'accountName', dataIndex: 'accountName', width: 170, renderer: Egw.Egwbase.Common.usernameRenderer},
            {resizable: true, header: 'IP Address', id: 'ip', dataIndex: 'ip', width: 150},
            {resizable: true, header: 'Login Time', id: 'li', dataIndex: 'li', width: 130, renderer: Egw.Egwbase.Common.dateTimeRenderer},
            {resizable: true, header: 'Logout Time', id: 'lo', dataIndex: 'lo', width: 130, renderer: Egw.Egwbase.Common.dateTimeRenderer},
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

Ext.namespace('Egw.Admin.Applications');
Egw.Admin.Applications.Main = function() {

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

Ext.namespace('Egw.Admin.Accounts');
Egw.Admin.Accounts.Main = function() {

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
            id: 'accountId',
            fields: Egw.Admin.Accounts.Account,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('accountLoginName', 'asc');

        dataStore.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        dataStore.load({params:{start:0, limit:50}});
        
        return dataStore;
    };

    
    var _renderStatus = function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch(_value) {
            case 'A':
              gridValue = "<img src='images/oxygen/16x16/actions/dialog-apply.png' width='12' height='12'/>";
              break;
              
            case 'D':
              gridValue = "<img src='images/oxygen/16x16/actions/dialog-cancel.png' width='12' height='12'/>";
              break;
              
            default:
              gridValue = _value;
              break;
        }
        
        return gridValue;
    };

    /**
     * creates the address grid
     * 
     */
    
    // public functions and variables
    return {
        show: function() 
        {
        	this.initComponent();
            this.showToolbar();
            this.showMainGrid();            
        },
        
        openAccountEditWindow: function(_accountId) 
        {
        	var accountId = (_accountId ? _accountId : '');
        	Egw.Egwbase.Common.openWindow('accountEditWindow', 'index.php?method=Admin.editAccountDialog&accountId=' + accountId, 800, 450);
        },

	    addButtonHandler: function(_button, _event) {
	        Egw.Admin.Accounts.Main.openAccountEditWindow();
	    },

	    editButtonHandler: function(_button, _event) {
	        var selectedRows = Ext.getCmp('AdminAccountsGrid').getSelectionModel().getSelections();
	        var accountId = selectedRows[0].id;
	        
	        Egw.Admin.Accounts.Main.openAccountEditWindow(accountId);
	    },
    
	    enableDisableButtonHandler: function(_button, _event) {
	        //console.log(_button);
	        
	        var status = 'disabled';
	        if(_button.id == 'Admin_Accounts_Action_Enable') {
	            status = 'enabled';
	        }
	        
	        var accountIds = new Array();
	        var selectedRows = Ext.getCmp('AdminAccountsGrid').getSelectionModel().getSelections();
	        for (var i = 0; i < selectedRows.length; ++i) {
	            accountIds.push(selectedRows[i].id);
	        }
	        
	        Ext.Ajax.request({
	            url : 'index.php',
	            method : 'post',
	            params : {
	                method : 'Admin.setAccountState',
	                accountIds : Ext.util.JSON.encode(accountIds),
	                status: status
	            },
	            callback : function(_options, _success, _response) {
	                if(_success === true) {
	                    var result = Ext.util.JSON.decode(_response.responseText);
	                    if(result.success === true) {
	                        Ext.getCmp('AdminAccountsGrid').getStore().reload();
	                    }
	                }
	            }
	        });
	    },
    
	    resetPasswordHandler: function(_button, _event) {
	        Ext.MessageBox.prompt('Set new password', 'Please enter the new password:', function(_button, _text) {
	            if(_button == 'ok') {
	                var accountId = Ext.getCmp('AdminAccountsGrid').getSelectionModel().getSelected().id;
	                
	                Ext.Ajax.request( {
	                    params : {
	                        method    : 'Admin.resetPassword',
	                        accountId : accountId,
	                        password  : _text
	                    },
	                    callback : function(_options, _success, _response) {
	                        if(_success === true) {
	                            var result = Ext.util.JSON.decode(_response.responseText);
	                            if(result.success === true) {
	                                Ext.getCmp('AdminAccountsGrid').getStore().reload();
	                            }
	                        }
	                    }
	                });
	            }
	        });
	    },
	    
	    deleteButtonHandler: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected account(s)?', function(_confirmButton){
	            if (_confirmButton == 'yes') {
	            
	                var accountIds = new Array();
	                var selectedRows = Ext.getCmp('AdminAccountsGrid').getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    accountIds.push(selectedRows[i].id);
	                }
	                
	                Ext.Ajax.request({
	                    url: 'index.php',
	                    params: {
	                        method: 'Admin.deleteAccounts',
	                        accountIds: Ext.util.JSON.encode(accountIds)
	                    },
	                    text: 'Deleting account(s)...',
	                    success: function(_result, _request){
	                        Ext.getCmp('AdminAccountsGrid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the account(s).');
	                    }
	                });
	            }
	        });
        },

	    actionEnable: null,
	    actionDisable: null,
	    actionResetPassword: null,
	    actionAddAccount: null,
	    actionEditAccount: null,
	    actionDeleteAccount: null,
	    
	    showToolbar: function()
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
	                this.actionAddAccount,
	                this.actionEditAccount,
	                this.actionDeleteAccount,
	                '-',
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
	    },
	    
	    showMainGrid: function() 
	    {
            var ctxMenuGrid = new Ext.menu.Menu({
                /*id:'AdminAccountContextMenu',*/ 
                items: [
                    this.actionEditAccount,
	                this.actionEnable,
	                this.actionDisable,
	                this.actionResetPassword,
                    this.actionDeleteAccount,
	                '-',
	                this.actionAddAccount 
	            ]
	        });
        
	        var dataStore = _createDataStore();
	        
	        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
	            pageSize: 50,
	            store: dataStore,
	            displayInfo: true,
	            displayMsg: 'Displaying accounts {0} - {1} of {2}',
	            emptyMsg: "No accounts to display"
	        }); 
	        
	        var columnModel = new Ext.grid.ColumnModel([
	            {resizable: true, header: 'ID', id: 'accountId', dataIndex: 'accountId', hidden: true, width: 50},
	            {resizable: true, header: 'Status', id: 'accountStatus', dataIndex: 'accountStatus', width: 50, renderer: _renderStatus},
	            {resizable: true, header: 'Displayname', id: 'accountDisplayName', dataIndex: 'accountDisplayName'},
	            {resizable: true, header: 'Loginname', id: 'accountLoginName', dataIndex: 'accountLoginName'},
	            {resizable: true, header: 'Last name', id: 'accountLastName', dataIndex: 'accountLastName', hidden: true},
	            {resizable: true, header: 'First name', id: 'accountFirstName', dataIndex: 'accountFirstName', hidden: true},
	            {resizable: true, header: 'Email', id: 'accountEmailAddress', dataIndex: 'accountEmailAddress', width: 200},
	            {resizable: true, header: 'Last login at', id: 'accountLastLogin', dataIndex: 'accountLastLogin', width: 130, renderer: Egw.Egwbase.Common.dateTimeRenderer},
	            {resizable: true, header: 'Last login from', id: 'accountLastLoginfrom', dataIndex: 'accountLastLoginfrom'},
	            {resizable: true, header: 'Password changed', id: 'accountLastPasswordChange', dataIndex: 'accountLastPasswordChange', width: 130, renderer: Egw.Egwbase.Common.dateTimeRenderer},
	            {resizable: true, header: 'Expires', id: 'accountExpires', dataIndex: 'accountExpires', width: 130, renderer: Egw.Egwbase.Common.dateTimeRenderer}
	        ]);
	        
	        columnModel.defaultSortable = true; // by default columns are sortable
	
	        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
	        
	        rowSelectionModel.on('selectionchange', function(_selectionModel) {
	            var rowCount = _selectionModel.getCount();
	
	            if(rowCount < 1) {
	            	this.actionEditAccount.setDisabled(true);
	            	this.actionDeleteAccount.setDisabled(true);
	                this.actionEnable.setDisabled(true);
	                this.actionDisable.setDisabled(true);
	                this.actionResetPassword.setDisabled(true);
	                //_action_settings.setDisabled(true);
	            } else if (rowCount > 1){
                    this.actionEditAccount.setDisabled(true);
                    this.actionDeleteAccount.setDisabled(false);
	                this.actionEnable.setDisabled(false);
	                this.actionDisable.setDisabled(false);
	                this.actionResetPassword.setDisabled(true);
	                //_action_settings.setDisabled(true);
	            } else {
                    this.actionEditAccount.setDisabled(false);
                    this.actionDeleteAccount.setDisabled(false);
	                this.actionEnable.setDisabled(false);
	                this.actionDisable.setDisabled(false);
	                this.actionResetPassword.setDisabled(false);
	                //_action_settings.setDisabled(false);              
	            }
	        }, this);
	                
	        var grid_applications = new Ext.grid.GridPanel({
	            id: 'AdminAccountsGrid',
	            store: dataStore,
	            cm: columnModel,
	            tbar: pagingToolbar,     
	            autoSizeColumns: false,
	            selModel: rowSelectionModel,
	            enableColLock:false,
	            loadMask: true,
	            autoExpandColumn: 'accountDisplayName',
	            border: false
	        });
	        
	        Egw.Egwbase.MainScreen.setActiveContentPanel(grid_applications);
	
	        grid_applications.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
	            _eventObject.stopEvent();
	            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
	                _grid.getSelectionModel().selectRow(_rowIndex);
	
	                this.actionEnable.setDisabled(false);
	                this.actionDisable.setDisabled(false);
	            }
	            //var record = _grid.getStore().getAt(rowIndex);
	            ctxMenuGrid.showAt(_eventObject.getXY());
	        }, this);
	        
	        grid_applications.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
	            var record = _gridPar.getStore().getAt(_rowIndexPar);
	            try {
	                Egw.Admin.Accounts.Main.openAccountEditWindow(record.id);
	            } catch(e) {
	                //alert(e);
	            }
	        });
	        
	        grid_applications.on('keydown', function(e){
                 if(e.getKey() == e.DELETE && Ext.getCmp('AdminAccountsGrid').getSelectionModel().getCount() > 0){
                     this.deleteButtonHandler();
                 }
            }, this);
	        
	    },
	    
	    initComponent: function()
	    {
	        this.actionAddAccount = new Ext.Action({
	            text: 'add account',
	            //disabled: true,
	            handler: this.addButtonHandler,
	            iconCls: 'action_addContact',
	            scope: this
	        });
	        
            this.actionEditAccount = new Ext.Action({
                text: 'edit account',
                disabled: true,
                handler: this.editButtonHandler,
                iconCls: 'action_edit',
                scope: this
            });

            this.actionDeleteAccount = new Ext.Action({
                text: 'delete account',
                disabled: true,
                handler: this.deleteButtonHandler,
                iconCls: 'action_delete',
                scope: this
            });            
            
	        this.actionEnable = new Ext.Action({
	            text: 'enable account',
	            disabled: true,
	            handler: this.enableDisableButtonHandler,
	            iconCls: 'action_enable',
	            id: 'Admin_Accounts_Action_Enable',
	            scope: this
	        });
	    
	        this.actionDisable = new Ext.Action({
	            text: 'disable account',
	            disabled: true,
	            handler: this.enableDisableButtonHandler,
	            iconCls: 'action_disable',
	            id: 'Admin_Accounts_Action_Disable',
	            scope: this
	        });
	    
	        this.actionResetPassword = new Ext.Action({
	            text: 'reset password',
	            disabled: true,
	            handler: this.resetPasswordHandler,
	            /*iconCls: 'action_disable',*/
	            id: 'Admin_Accounts_Action_resetPassword',
	            scope: this
	        });
	    },
	    
	    reload: function() {
            if(Ext.ComponentMgr.all.containsKey('AdminAccountsGrid')) {
                setTimeout ("Ext.getCmp('AdminAccountsGrid').getStore().reload()", 200);
            }
        }
	       
	    
    };
    
}();

Egw.Admin.Accounts.EditDialog = function() {
    // public functions and variables
    return {
    	accountRecord: null,
    	
    	updateAccountRecord: function(_accountData)
    	{
            if(_accountData.accountExpires && _accountData.accountExpires !== null) {
                _accountData.accountExpires = Date.parseDate(_accountData.accountExpires, 'c');
            }
            if(_accountData.accountLastLogin && _accountData.accountLastLogin !== null) {
                _accountData.accountLastLogin = Date.parseDate(_accountData.accountLastLogin, 'c');
            }
            if(_accountData.accountLastPasswordChange && _accountData.accountLastPasswordChange !== null) {
                _accountData.accountLastPasswordChange = Date.parseDate(_accountData.accountLastPasswordChange, 'c');
            }
            if(!_accountData.accountPassword) {
            	_accountData.accountPassword = null;
            }

            this.accountRecord = new Egw.Admin.Accounts.Account(_accountData);
    	},
    	
    	deleteAccount: function(_button, _event)
    	{
	        var accountIds = Ext.util.JSON.encode([this.accountRecord.get('accountId')]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Admin.deleteAccounts', 
	                accountIds: accountIds
	            },
	            text: 'Deleting account...',
	            success: function(_result, _request) {
	                window.opener.Egw.Admin.Accounts.Main.reload();
	                window.close();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the account.'); 
	            } 
	        });    		
    	},
    	
        applyChanges: function(_button, _event, _closeWindow) 
        {
        	var form = Ext.getCmp('admin_editAccountForm').getForm();

        	if(form.isValid()) {
        		form.updateRecord(this.accountRecord);
        		if(this.accountRecord.data.accountFirstName) {
            		this.accountRecord.data.accountFullName = this.accountRecord.data.accountFirstName + ' ' + this.accountRecord.data.accountLastName;
                    this.accountRecord.data.accountDisplayName = this.accountRecord.data.accountLastName + ', ' + this.accountRecord.data.accountFirstName;
        		} else {
                    this.accountRecord.data.accountFullName = this.accountRecord.data.accountLastName;
                    this.accountRecord.data.accountDisplayName = this.accountRecord.data.accountLastName;
        		}
	    
	            Ext.Ajax.request({
	                params: {
	                    method: 'Admin.saveAccount', 
	                    accountData: Ext.util.JSON.encode(this.accountRecord.data),
	                    password: form.findField('accountPassword').getValue(),
	                    password2: form.findField('accountPassword2').getValue()                        
	                },
	                success: function(_result, _request) {
	                	window.opener.Egw.Admin.Accounts.Main.reload();
                        if(_closeWindow === true) {
                            window.close();
                        } else {
		                	this.updateAccountRecord(Ext.util.JSON.decode(_result.responseText));
		                	this.updateToolbarButtons();
		                	form.loadRecord(this.accountRecord);
                        }
	                },
	                failure: function ( result, request) { 
	                    Ext.MessageBox.alert('Failed', 'Could not save account.'); 
	                },
	                scope: this 
	            });
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
    	},

        saveChanges: function(_button, _event) 
        {
        	this.applyChanges(_button, _event, true);
        },
        
        editAccountDialog: [{
            layout:'column',
            //frame: true,
            border:false,
            autoHeight: true,
            items:[{
                //frame: true,
                columnWidth:.6,
                border:false,
                layout: 'form',
                defaults: {
                    anchor: '95%'
                },
                items: [{
                        xtype: 'textfield',
                        fieldLabel: 'First Name',
                        name: 'accountFirstName'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: 'Last Name',
                        name: 'accountLastName',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: 'Login Name',
                        name: 'accountLoginName',
                        allowBlank: false
                    }, {
                        xtype: 'textfield',
                        fieldLabel: 'Password',
                        name: 'accountPassword',
                        inputType: 'password',
                        emptyText: 'no password set'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: 'Password again',
                        name: 'accountPassword2',
                        inputType: 'password',
                        emptyText: 'no password set'
                    }, {
                        xtype: 'combo',
                        fieldLabel: 'Primary group',
                        name: 'accountPrimaryGroup',
                        mode: 'local',
                        displayField:'groupName',
                        valueField:'key',
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: new Ext.data.SimpleStore(
                            {
                                fields: ['key','groupName'],
                                data: [
                                    ['1','Admins'],
                                    ['2','Default'],
                                    ['3','Guest']
                                ]
                            }
                        )
                    }, {
                        xtype: 'textfield',
                        vtype: 'email',
                        fieldLabel: 'Emailaddress',
                        name: 'accountEmailAddress'
                    }
                ]
            },{
                columnWidth:.4,
                border:false,
                layout: 'form',
                defaults: {
                    anchor: '95%'
                },
                items: [
                    {
                        xtype: 'combo',
                        fieldLabel: 'Status',
                        name: 'accountStatus',
                        mode: 'local',
                        displayField:'status',
                        valueField:'key',
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: new Ext.data.SimpleStore(
                            {
                                fields: ['key','status'],
                                data: [
                                    ['A','enabled'],
                                    ['D','disabled']
                                ]
                            }
                        )
                    }, 
                    new Ext.ux.ClearableDateField({ 
                        fieldLabel: 'Expires',
                        name: 'accountExpires',
                        format: "d.m.Y",
                        emptyText: 'never'
                    }), {
                        xtype: 'datefield',
                        fieldLabel: 'Last login at',
                        name: 'accountLastLogin',
                        format: "d.m.Y H:i:s",
                        emptyText: 'never logged in',
                        hideTrigger: true,
                        readOnly: true
                    }, {
                        xtype: 'textfield',
                        fieldLabel: 'Last login from',
                        name: 'accountLastLoginfrom',
                        emptyText: 'never logged in',
                        readOnly: true
                    }, {
                        xtype: 'datefield',
                        fieldLabel: 'Password set',
                        name: 'accountLastPasswordChange',
                        format: "d.m.Y H:i:s",
                        emptyText: 'never',
                        hideTrigger: true,
                        readOnly: true
                    }
                ]
            }]
        }],
        
        updateToolbarButtons: function()
        {
            if(this.accountRecord.get('accountId') > 0) {
                Ext.getCmp('admin_editAccountForm').action_delete.enable();
            }
        },
        
        display: function(_accountData) 
        {
            // Ext.FormPanel
		    var dialog = new Egw.widgets.dialog.EditRecord({
		        id : 'admin_editAccountForm',
		        //title: 'the title',
		        labelWidth: 120,
                labelAlign: 'side',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteAccount,
		        items: this.editAccountDialog
		    });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
	        
	        //if (!arguments[0]) var task = {};
            this.updateAccountRecord(_accountData);
            this.updateToolbarButtons();
	        dialog.getForm().loadRecord(this.accountRecord);
        }
    };
}();

Egw.Admin.Accounts.Account = Ext.data.Record.create([
    // egw record fields
    { name: 'accountId' },
    { name: 'accountFirstName' },
    { name: 'accountLastName' },
    { name: 'accountLoginName' },
    { name: 'accountPassword' },
    { name: 'accountDisplayName' },
    { name: 'accountFullName' },
    { name: 'accountStatus' },
    { name: 'accountPrimaryGroup' },
    { name: 'accountExpires', type: 'date', dateFormat: 'c' },
    { name: 'accountLastLogin', type: 'date', dateFormat: 'c' },
    { name: 'accountLastPasswordChange', type: 'date', dateFormat: 'c' },
    { name: 'accountLastLoginfrom' },
    { name: 'accountEmailAddress' }
]);
