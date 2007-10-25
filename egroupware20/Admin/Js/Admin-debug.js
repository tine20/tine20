Ext.namespace('Egw.Admin');

Egw.Admin = function() {

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

        for(i=0; i<initialTree.Admin.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree.Admin[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
/*            action_edit.setDisabled(true);
            action_delete.setDisabled(true); */

        	var currentToolbar = Egw.Egwbase.getActiveToolbar();
            switch(_node.attributes.dataPanelType) {
                case 'applications':
                    if(currentToolbar != false && currentToolbar.id == 'toolbarAdminApplications') {
                    	Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Egw.Admin.Applications.show();
                    }
                    
                    break;
                    
/*                case 'lists':
                    if(currentDataPanelType != _node.attributes.dataPanelType) {
                        createListsDataStore(_node);
                        showListsGrid();
                        currentDataPanelType = _node.attributes.dataPanelType;
                    } else {
                        ds_contacts.baseParams = getParameterListsDataStore(_node);
                        ds_contacts.load({params:{start:0, limit:50}});
                    }
                    
                    break; */
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() == null) {
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
    }
    
    // public functions and variables
    return {
        getPanel: _getAdminTree,
    }
    
}();

Egw.Admin.Applications = function() {

    /**
     * onclick handler for edit action
     */
    var _editButtonHandler = function(_button, _event) {
        var selectedRows = Ext.getCmp('grid_applications').getSelectionModel().getSelections();
        var applicationId = selectedRows[0].id;
        
        Egw.Egwbase.openWindow('applicationWindow', 'index.php?method=Admin.getApplication&appId=' + applicationId, 800, 450);
    }

	var _action_edit = new Ext.Action({
        text: 'edit',
        disabled: true,
        handler: _editButtonHandler,
        iconCls: 'action_edit'
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

        //ds_contacts.on('beforeload', _setParameter);        
        
        ds_applications.load({params:{start:0, limit:50}});
        
        return ds_applications;
    }

	var _showApplicationsToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        //quickSearchField.on('change', searchFieldHandler);
        
        var applicationToolbar = new Ext.Toolbar({
            /*region: 'south', */
            id: 'toolbarAdminApplications',
            split: false,
            height: 26,
            items: [
                _action_edit,
                '->', 'Search:', ' ',
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
        
        Egw.Egwbase.setActiveToolbar(applicationToolbar);
    }
    
    var _renderEnabled = function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
    	switch(_value) {
            case '0':
              return 'disabled';
              break;
              
    		case '1':
    		  return 'enabled';
    		  break;
    		  
            case '2':
              return 'enabled but hidden';
              break;
              
            case '3':
              return 'enabled (new window)';
              break;
              
    		default:
    		  return 'unknown status (' + _value + ')';
    		  break;
    	}
	}

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
        
        var grid_applications = new Ext.grid.GridPanel({
        	id: 'gridAdminApplications',
            store: ds_applications,
            cm: cm_applications,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
            enableColLock:false,
            /*loadMask: true,*/
            autoExpandColumn: 'app_name',
            border: false
        });
        
        Egw.Egwbase.setActiveContentPanel(grid_applications);

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
                openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + record.data.list_id, 800, 450);
            } catch(e) {
            //  alert(e);
            }
        });
        
        return;
    }    
    
    // public functions and variables
    return {
        show: function() {
        	_showApplicationsToolbar();
            _showApplicationsGrid();        	
        }
    }
    
}();
