Ext.namespace('Egw.Felamimail');

Egw.Felamimail = function() {

	//var grid;

	// private function
	var showGrid = function(_layout) {
		var center = _layout.getRegion('center', false);
		
		// add a div, which will bneehe parent element for the grid
		var bodyTag = Ext.Element.get(document.body);
		var gridDivTag = bodyTag.createChild({tag: 'div',id: 'gridAddressbook',cls: 'x-layout-inactive-content'});
		
		// create the Data Store
		var ds = new Ext.data.JsonStore({
			url: 'index.php',
			baseParams: {method:'Felamimail.getData'},
			root: 'results',
			totalProperty: 'totalcount',
			id: 'message_id',
			fields: [
				{name: 'message_id'},
				{name: 'model'},
				{name: 'description'},
				{name: 'config_id'},
				{name: 'setting_id'},
				{name: 'software_id'}
			],
			// turn on remote sorting
			remoteSort: true
		});

		ds.setDefaultSort('message_id', 'desc');

		ds.load();

		var cm = new Ext.grid.ColumnModel([{
				resizable: true,
				id: 'userid',
				header: "ID",
				dataIndex: 'userid',
				width: 30
			},
			{
				resizable: true,
				id: 'lastname',
				header: "lastname",
				dataIndex: 'lastname'
			},
			{
				resizable: true,
				id: 'firstname',
				header: "firstname",
				dataIndex: 'firstname',
				hidden: true
			},
			{
				resizable: true,
				header: "street",
				dataIndex: 'street'
			},
			{
				resizable: true,
				id: 'city',
				header: "zip/city",
				dataIndex: 'city'
			},
			{
				resizable: true,
				header: "birthday",
				dataIndex: 'birthday'
			},
			{
				resizable: true,
				id: 'addressbook',
				header: "addressbook",
				dataIndex: 'addressbook'
		}]);
		
		cm.defaultSortable = true; // by default columns are sortable

		var grid = new Ext.grid.Grid(outerDivTag, {
			ds: ds,
			cm: cm,
			autoSizeColumns: false,
			selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
			enableColLock:false,
			loadMask: true,
			enableDragDrop:true,
			ddGroup: 'TreeDD',
			autoExpandColumn: 'n_given'
		});		
		

		grid.render();

		var gridHeader = grid.getView().getHeaderPanel(true);
		
		// add a paging toolbar to the grid's footer
		var pagingHeader = new Ext.PagingToolbar(gridHeader, ds, {
			pageSize: 50,
			displayInfo: true,
			displayMsg: 'Displaying contacts {0} - {1} of {2}',
			emptyMsg: "No contacts to display"
		});

		pagingHeader.insertButton(0, {
			id: 'addbtn',
			cls:'x-btn-icon',
			icon:'images/oxygen/16x16/actions/edit-add.png',
			tooltip: 'add new contact',
			onClick: _openDialog
		});

		pagingHeader.insertButton(1, {
			id: 'editbtn',
			cls:'x-btn-icon',
			icon:'images/oxygen/16x16/actions/edit.png',
			tooltip: 'edit current contact',
			disabled: true,
			onClick: _openDialog
		});

		pagingHeader.insertButton(2, {
			id: 'deletebtn',
			cls:'x-btn-icon',
			icon:'images/oxygen/16x16/actions/edit-delete.png',
			tooltip: 'delete selected contacts',
			disabled: true,
			onClick: _openDialog
		});

		pagingHeader.insertButton(3, new Ext.Toolbar.Separator());

		center.add(new Ext.GridPanel(grid));
		
		grid.on('rowclick', function(gridP, rowIndexP, eventP) {
			var rowCount = grid.getSelectionModel().getCount();
			
			var btns = pagingHeader.items.map;
			
			if(rowCount < 1) {
				btns.editbtn.disable();
				btns.deletebtn.disable();
			} else if(rowCount == 1) {
				btns.editbtn.enable();
				btns.deletebtn.enable();
			} else {
				btns.editbtn.disable();
				btns.deletebtn.enable();
			}
		});

		grid.on('rowdblclick', function(gridPar, rowIndexPar, ePar) {
			var record = gridPar.getDataSource().getAt(rowIndexPar);
			console.log('id: ' + record.data.contact_id);
			try {
				_openDialog(record.data.contact_id);
			} catch(e) {
			//	alert(e);
			}
		});
















		var grid = new Ext.grid.Grid(gridDivTag, {
				ds: ds,
				cm: cm,
				autoSizeColumns: false,
				selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
				enableColLock:false,
				//monitorWindowResize: true,
				loadMask: true,
				enableDragDrop:true,
				ddGroup: 'TreeDD',
				autoExpandColumn: 'lastname'
			});		
		
		// remove the first contentpanel from center region
		center.remove(0);

		grid.render();

		center.add(new Ext.GridPanel(grid));
    }
    
    
    
    
    var _getFolderPanel = function() 
    {
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method       = 'Felamimail.getSubTree';
            _loader.baseParams.accountId    = _node.attributes.accountId;
            _loader.baseParams.folderName   = _node.attributes.folderName;
            _loader.baseParams.location     = 'mainTree';
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Email',
            id: 'felamimail-tree',
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

        for(i=0; i<initialTree.Felamimail.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree.Felamimail[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            var currentToolbar = Egw.Egwbase.getActiveToolbar();

            if(currentToolbar != false && currentToolbar.id == 'toolbarFelamimail') {
                Ext.getCmp('gridFelamimail').getStore().load({params:{start:0, limit:50}});
            } else {
                Egw.Felamimail.Email.show();
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() == null) {
                _panel.expandPath('/root');
                //_panel.selectPath('/root/applications');
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
    }


    // public stuff
    return {
        getPanel: _getFolderPanel,
    }
	
}(); // end of application

Egw.Felamimail.Email = function() {

    /**
     * onclick handler for edit action
     */
    var _deleteHandler = function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected access log entries?', function(_button) {
            if(_button == 'yes') {
                var logIds = Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].id);
                }
                
                new Ext.data.Connection().request( {
                    url : 'index.php',
                    method : 'post',
                    scope : this,
                    params : {
                        method : 'Admin.deleteAccessLogEntries',
                        logIds : Ext.util.JSON.encode(logIds)
                    },
                    callback : function(_options, _success, _response) {
                        if(_success == true) {
                            var result = Ext.util.JSON.decode(_response.responseText);
                            if(result.success == true) {
                                Ext.getCmp('gridAdminAccessLog').getStore().reload();
                            }
                        }
                    }
                });
            }
        });
    }

    var _selectAllHandler = function(_button, _event) {
        Ext.getCmp('gridAdminAccessLog').getSelectionModel().selectAll();
    }
    
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
                {name: 'li'},
                {name: 'lo'},
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
    }

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
        
        Egw.Egwbase.setActiveToolbar(toolbar);

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

            Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}})
        });
    }
    
    var _renderResult = function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        switch (_value) {
            case '-3' :
                return 'invalid password';
                break;

            case '-2' :
                return 'ambiguous username';
                break;

            case '-1' :
                return 'user not found';
                break;

            case '0' :
                return 'failure';
                break;

            case '1' :
                return 'success';
                break;
        }
    }

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
            {resizable: true, header: 'Login Time', id: 'li', dataIndex: 'li', width: 120},
            {resizable: true, header: 'Logout Time', id: 'lo', dataIndex: 'lo', width: 120},
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
        
        Egw.Egwbase.setActiveContentPanel(gridPanel);

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
    }
        
    // public functions and variables
    return {
        show: function() {
            _showToolbar();
            _showGrid();            
        }
    }
    
}();
