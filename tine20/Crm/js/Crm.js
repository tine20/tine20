Ext.namespace('Egw.Crm');

Egw.Crm = function() {


   var _treeNodeContextMenu = null;

    /**
     * the initial tree to be displayed in the left treePanel
     */
    var _initialTree = [{
        text: 'All Leads',
        cls: "treemain",
        nodeType: 'allProjects',
        id: 'allProjects',
        children: [{
            text: 'My Leads',
            cls: 'file',
            nodeType: 'userProjects',
            id: 'userProjects',
            leaf: null,
            owner: Egw.Egwbase.Registry.get('currentAccount').account_id
        }, {
            text: "Shared Leads",
            cls: "file",
            nodeType: "sharedProjects",
            children: null,
            leaf: null
        }, {
            text: "Other Users Leads",
            cls: "file",
            nodeType: "otherUsersProjects",
            children: null,
            leaf: null
        }]
    }];
    
    var _handler_addFolder = function(_button, _event) {
        Ext.MessageBox.prompt('New Folder', 'Please enter the name of the new folder:', function(_btn, _text) {
            if(_treeNodeContextMenu !== null && _btn == 'ok') {

                //console.log(_treeNodeContextMenu);
                var type = 'personal';
                if(_treeNodeContextMenu.attributes.nodeType == 'sharedProjects') {
                	type = 'shared';
                }
                
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Crm.addFolder',
                        name: _text,
                        type: type,
                        owner: _treeNodeContextMenu.attributes.owner
                    },
                    text: 'Creating new folder...',
                    success: function(_result, _request){
                        //Ext.getCmp('Crm_Projects_Grid').getStore().reload();
                        //_treeNodeContextMenu.expand(false, false);
                        //console.log(_result);
                        if(_treeNodeContextMenu.isExpanded()) {
                        	var responseData = Ext.util.JSON.decode(_result.responseText);
	                        var newNode = new Ext.tree.TreeNode({
	                            leaf: true,
	                            cls: 'file',
	                            nodeType: 'singleFolder',
	                            folderId: responseData.folderId,
	                            text: _text
	                        });
                            _treeNodeContextMenu.appendChild(newNode);
                        } else {
                        	_treeNodeContextMenu.expand(false);
                        }
                    },
                    failure: function(result, request){
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the project.');
                    }
                });
            }
        });
    };

    var _handler_renameFolder = function(_button, _event) {
        var resulter = function(_btn, _text) {
            if(_treeNodeContextMenu !== null && _btn == 'ok') {

                //console.log(_treeNodeContextMenu);
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Crm.renameFolder',
                        folderId: _treeNodeContextMenu.attributes.folderId,
                        name: _text
                    },
                    text: 'Renamimg folder...',
                    success: function(_result, _request){
                        _treeNodeContextMenu.setText(_text);
                    },
                    failure: function(result, request){
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the project.');
                    }
                });
            }
        };
        
        Ext.MessageBox.show({
            title: 'Rename folder',
            msg: 'Please enter the new name of the folder:',
            buttons: Ext.MessageBox.OKCANCEL,
            value: _treeNodeContextMenu.text,
            fn: resulter,
            prompt: true,
            icon: Ext.MessageBox.QUESTION
        });
        
    };

    var _handler_deleteFolder = function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the folder ' + _treeNodeContextMenu.text + ' ?', function(_button){
            if (_button == 'yes') {
            
                //console.log(_treeNodeContextMenu);
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Crm.deleteFolder',
                        projectId: _treeNodeContextMenu.attributes.projectId
                    },
                    text: 'Deleting Folder...',
                    success: function(_result, _request){
                        if(_treeNodeContextMenu.isSelected()) {
                            Ext.getCmp('Crm_Tree').getSelectionModel().select(_treeNodeContextMenu.parentNode);
                            Ext.getCmp('Crm_Tree').fireEvent('click', _treeNodeContextMenu.parentNode);
                        }
                        _treeNodeContextMenu.remove();
                    },
                    failure: function(_result, _request){
                        Ext.MessageBox.alert('Failed', 'The folder could not be deleted.');
                    }
                });
            }
        });
    };    
     
   var _action_addFolder = new Ext.Action({
        text: 'add folder',
        handler: _handler_addFolder
    });

    var _action_deleteFolder = new Ext.Action({
        text: 'delete folder',
        iconCls: 'action_delete',
        handler: _handler_deleteFolder
    });

    var _action_renameFolder = new Ext.Action({
        text: 'rename folder',
        iconCls: 'action_rename',
        handler: _handler_renameFolder
    });

    var _action_permisionsFolder = new Ext.Action({
    	disabled: true,
        text: 'permissions',
        handler: _handler_deleteFolder
    });


    var _contextMenuUserFolder = new Ext.menu.Menu({
        items: [
            _action_addFolder
        ]
    });
    
    var _contextMenuSingleFolder= new Ext.menu.Menu({
        items: [
            _action_renameFolder,
            _action_deleteFolder,
            _action_permisionsFolder
        ]
    });


    var _displayFolderSelectDialog = function(_fieldName){         
               
            //################## listView #################

            var folderDialog = new Ext.Window({
                title: 'please select folder',
                modal: true,
                width: 375,
                height: 400,
                minWidth: 375,
                minHeight: 400,
                layout: 'fit',
                plain:true,
                bodyStyle:'padding:5px;',
                buttonAlign:'center'
            });         
            
            var treeLoader = new Ext.tree.TreeLoader({
                dataUrl:'index.php',
                baseParams: {
                    jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
                    location: 'mainTree'
                }
                
            });
             treeLoader.on("beforeload", function(_loader, _node) {
                switch(_node.attributes.nodeType) {
                    case 'otherProjects':
                        _loader.baseParams.method   = 'Crm.getOtherUsers';
                        break;
                        
                    case 'sharedProjects':
                        _loader.baseParams.method   = 'Crm.getSharedFolders';
                        break;
    
                    case 'userProjects':
                        _loader.baseParams.method   = 'Crm.getFoldersByOwner';
                        _loader.baseParams.owner    = _node.attributes.owner;
                        break;
                }
            }, this);
                            
            var tree = new Ext.tree.TreePanel({
                title: 'CRM',
                id: 'Crm_Tree',
                iconCls: 'Crm_thumbnail_application',
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
            tree.setRootNode(treeRoot);
            
            // add the initial tree nodes    
            for(var i=0; i< _initialTree.length; i++) {
                treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
            }
            
            tree.on('click', function(_node) {
                //console.log(_node);
                    if(_node.attributes.nodeType == 'singleFolder') {                
                        Ext.getCmp(_fieldName).setValue(_node.attributes.folderId);
                        Ext.getCmp(_fieldName + '_name').setValue(_node.text);
                        folderDialog.hide();
                    }
            }, this);

            folderDialog.add(tree);
    
            folderDialog.show();
                           
            tree.expandPath('/root/allProjects');
    };

 
    
     /**
     * creates the crm tree panel
     *
     */
    var _getTreePanel = function() 
    {
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
            	jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
            	location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            switch(_node.attributes.nodeType) {
                case 'otherProjects':
                    _loader.baseParams.method   = 'Crm.getOtherUsers';
                    break;
                    
                case 'sharedProjects':
                    _loader.baseParams.method   = 'Crm.getSharedFolders';
                    break;

                case 'userProjects':
                    _loader.baseParams.method   = 'Crm.getFoldersByOwner';
                    _loader.baseParams.owner    = _node.attributes.owner;
                    break;
            }
        }, this);

        var treePanel = new Ext.tree.TreePanel({
            title: 'CRM',
            id: 'Crm_Tree',
            iconCls: 'Crm_thumbnail_application',
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
// tree vs. treepanel
        for(var i=0; i< _initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            Egw.Crm.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root/allProjects');
                _panel.selectPath('/root/allProjects');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            _treeNodeContextMenu = _node;

            switch(_node.attributes.nodeType) {
                case 'userProjects':
                case 'sharedProjects':
                    _contextMenuUserFolder.showAt(_event.getXY());
                    break;

                case 'singleFolder':
                    _contextMenuSingleFolder.showAt(_event.getXY());
                    break;

                default:
                    break;
            }
        });
        return treePanel;
    };  
    
    
    
    var _createDataStore = function()
    {

         var ds_crm = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getProjectsByOwner',
                owner: 'all'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'pj_id',
            fields: [
                {name: 'pj_id'},            
                {name: 'pj_name'},
                {name: 'pj_distributionphase_id'},
                {name: 'pj_customertype_id'},
                {name: 'pj_leadsource_id'},
                {name: 'pj_owner'},
                {name: 'pj_modifier'},
                {name: 'pj_start'},
                {name: 'pj_modified'},
                {name: 'pj_description'},
                {name: 'pj_end'},
                {name: 'pj_turnover'},
                {name: 'pj_probability'},
                {name: 'pj_end_scheduled'},
                {name: 'pj_lastread'},
                {name: 'pj_lastreader'},
                
                {name: 'pj_projectstate'},
                {name: 'pj_leadtype'},
                {name: 'pj_leadsource'}//,
                /*
                {name: 'pj_partner'},
                {name: 'pj_lead'},
                {name: 'pj_partner_id'},
                {name: 'pj_lead_id'}  */
            ],
            // turn on remote sorting
            remoteSort: true
        });
     
   
        ds_crm.setDefaultSort('pj_name', 'asc');

        ds_crm.on('beforeload', function(_dataSource) {
        	_dataSource.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
            
        	/*
        	var from = Date.parseDate(
        	   Ext.getCmp('adminApplications_dateFrom').getRawValue(),
        	   'm/d/y'
        	);
            _dataSource.baseParams.from   = from.format("Y-m-d\\T00:00:00");

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               'm/d/y'
            );
            _dataSource.baseParams.to     = to.format("Y-m-d\\T23:59:59");  */
        });        
        
        ds_crm.load({params:{start:0, limit:50}});
        
        return ds_crm;
    }


	  	var action_add = new Ext.Action({
			text: 'add lead',
			iconCls: 'action_add',
			handler: function () {
           //     var tree = Ext.getCmp('venues-tree');
		//		var curSelNode = tree.getSelectionModel().getSelectedNode();
			//	var RootNode   = tree.getRootNode();
            
                Egw.Egwbase.Common.openWindow('CrmProjectWindow', 'index.php?method=Crm.editProject&_projectId=0&_eventId=NULL', 900, 700);
             }
 		}); 
        
        var handler_edit = function() 
        {
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Egw.Egwbase.Common.openWindow('projectWindow', 'index.php?method=Crm.editProject&_projectId='+_rowIndex[0].id, 900, 700);   

        }

    var handler_pre_delete = function(){
        Ext.MessageBox.show({
            title: 'Delete Project?',
            msg: 'Are you sure you want to delete this project?',
            buttons: Ext.MessageBox.YESNO,
            fn: handler_delete,
            icon: Ext.MessageBox.QUESTION
        });
    }

        var handler_delete = function(btn) 
        {
            if (btn == 'yes') {
                
                var _rowIndexIds = Ext.getCmp('gridCrm').getSelectionModel().getSelections();

                var toDelete_Ids = new Array();
                
                for (var i = 0; i < _rowIndexIds.length; ++i) {
                         toDelete_Ids.push(_rowIndexIds[i].id);
                } 
                
                var projectIds = Ext.util.JSON.encode(toDelete_Ids);

                Ext.Ajax.request({
                    params: {
                        method: 'Crm.deleteProjects',
                        _projectIds: projectIds
                    },
                    text: 'Deleting project...',
                    success: function(_result, _request){
                        Ext.getCmp('gridCrm').getStore().reload();
                    },
                    failure: function(result, request){
                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the project.');
                    }
                });
            } 
        }

	   	var action_edit = new Ext.Action({
			text: 'edit lead',
			disabled: true,
			handler: handler_edit,
			iconCls: 'action_edit'
		});
			
	   	var action_delete = new Ext.Action({
			text: 'delete lead',
			disabled: true,
			handler: handler_pre_delete,
			iconCls: 'action_delete'
		});	


    var _showCrmToolbar = function() {
    
        var quickSearchField = new Ext.app.SearchField({
            id:        'quickSearchField',
            width:     200,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('gridCrm').getStore().load({params:{start:0, limit:50}});
        });
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id:             'Crm_dateFrom',
            allowBlank:     false,
            validateOnBlur: false,
            value:          oneWeekAgo
        });
        var dateTo = new Ext.form.DateField({
            id:             'Crm_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            value:          currentDate
        });
        

       function editOptions(item) {
           var Dialog = new Ext.Window({
				title: item.table,
                id: 'options_window',
				modal: true,
			    width: 350,
			    height: 500,
			    minWidth: 300,
			    minHeight: 500,
			    layout: 'fit',
			    plain:true,
			    bodyStyle:'padding:5px;',
			    buttonAlign:'center'
            });	
            
            var st = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.get'+item.table,
                    sort: item.mapping,
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'key',
                fields: [
                    {name: 'key', mapping: item.mapping + '_id'},
                    {name: 'value', mapping: item.mapping}    
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st.load();
            

            
            var cm = new Ext.grid.ColumnModel([
                	{id:'id', header: "id", dataIndex: 'key', width: 25, hidden: true },
                    {id:'value', header: 'entries', dataIndex: 'value', width: 200, hideable: false, sortable: false, editor: new Ext.form.TextField({
                    allowBlank: false
                    }) }
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'key', mapping: item.mapping + '_id', type: 'int'},
               {name: 'value', mapping: item.mapping, type: 'int'}
            ]);
            
            var handler_options_add = function(){
                var p = new entry({
                    key: 'NULL',
					value: ''
                });
                gridPanel.stopEditing();
                st.insert(0, p);
                gridPanel.startEditing(0, 0);
            }
                        
            var handler_options_delete = function(){
               	var optionGrid  = Ext.getCmp('editOptionsGrid');
        		var optionStore = optionGrid.getStore();
                
        		var selectedRows = optionGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    optionStore.remove(selectedRows[i]);
                }   
            }                        
                        
          
           var handler_options_saveClose = function(){
                var store_options = Ext.getCmp('editOptionsGrid').getStore();
                var switchKeys = new Array(item.mapping + '_id', item.mapping);
                
                var options_json = Egw.Egwbase.Common.getJSONdataSKeys(store_options, switchKeys); 

                 Ext.Ajax.request({
                       //     url: 'index.php',
                            params: {
                                method: 'Crm.save' + item.table,
                                optionsData: options_json
                            },
                            text: 'Saving options...',
                            success: function(_result, _request){
                                    store_options.reload();
                                    store_options.rejectChanges();
                               },
                            failure: function(form, action) {
                    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
                    			}
                        });          
            }          
            
            var gridPanel = new Ext.grid.EditorGridPanel({
                store: st,
                id: 'editOptionsGrid',
                cm: cm,
                autoExpandColumn:'value',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'action_add',
                    handler : handler_options_add
                    },{
                    text: 'delete item',
                    iconCls: 'action_delete',
                    handler : handler_options_delete
                    },{
                    text: 'save',
                    iconCls: 'action_saveAndClose',
                    handler : handler_options_saveClose 
                    }]  
                });
                        
          Dialog.add(gridPanel);
          Dialog.show();		  
        }

        
        var settings_tb_menu = new Ext.menu.Menu({
            id: 'crmSettingsMenu',
            items: [
                {text: 'Leadstatus', handler: editOptions, table: 'Projectstates', mapping: 'pj_projectstate'},
                {text: 'Leadsource', handler: editOptions, table: 'Leadsources', mapping: 'pj_leadsource'},
                {text: 'Leadtype', handler: editOptions, table: 'Leadtypes', mapping: 'pj_leadtype'},
                {text: 'Product', handler: editOptions, table: 'Productsource', mapping: 'pj_productsource'}
            ]
        });     
        
        
        var toolbar = new Ext.Toolbar({
            id: 'Crm_toolbar',
            split: false,
            height: 26,
            items: [
                action_add,
                action_edit,
                action_delete,
                new Ext.Toolbar.Separator(),
                {
                    text:'Options',
                    iconCls: 'action_edit',  
                    menu: settings_tb_menu
                },
                '->',
                'Display from: ',
                ' ',
                dateFrom,
                'to: ',
                ' ',
                dateTo,                
                new Ext.Toolbar.Separator(),
                '->',
                'Search:', ' ',
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.MainScreen.setActiveToolbar(toolbar);
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
     * creates the grid
     * 
     */
    var _showGrid = function() 
    {
    	
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying projects {0} - {1} of {2}',
            emptyMsg: "No projects to display"
        }); 
        
      	var ctxMenuGrid = new Ext.menu.Menu({
        id:'ctxMenuGrid', 
        items: [
            action_edit,
            action_delete
        ]
    });

        var expander = new Ext.grid.RowExpander({
            tpl : new Ext.Template(
                '<p><b>Notes:</b> {pj_description}<br>',
                '<p><b>Activities:</b> </p>'
            )
        });
        
        var columnModel = new Ext.grid.ColumnModel([
            expander,
            {resizable: true, header: 'Projekt ID', id: 'pj_id', dataIndex: 'pj_id', width: 20, hidden: true},
            {resizable: true, header: 'Projektname', id: 'pj_name', dataIndex: 'pj_name', width: 200},
            {resizable: true, header: 'Partner', id: 'pj_partner', dataIndex: 'pj_partner', width: 150},
            {resizable: true, header: 'Lead', id: 'pj_lead', dataIndex: 'pj_lead', width: 150},
            {resizable: true, header: 'Status', id: 'pj_projectstate', dataIndex: 'pj_projectstate', width: 150},
            {resizable: true, header: 'Wahrscheinlichkeit', id: 'pj_probability', dataIndex: 'pj_probability', width: 50, renderer: Ext.util.Format.percentage},
            {resizable: true, header: 'Umsatz', id: 'pj_turnover', dataIndex: 'pj_turnover', width: 100, renderer: Ext.util.Format.euMoney }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                action_delete.setDisabled(true);
                action_edit.setDisabled(true);
            } 
            if (rowCount == 1) {
               action_edit.setDisabled(false);
               action_delete.setDisabled(false);               
            }    
            if(rowCount > 1) {                
               action_edit.setDisabled(true);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,   
            viewConfig: {
                        forceFit:true
                    },  
            plugins: expander,                    
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
   //         autoExpandColumn: 'Projektname',
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(gridPanel);


        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                action_delete.setDisabled(false);
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            Egw.Egwbase.Common.openWindow('projectWindow', 'index.php?method=Crm.editProject&_projectId='+record.data.pj_id, 900, 700);            
        });
       
       return;
    }
    

   var _loadData = function(_node)
    {
        var dataStore = Ext.getCmp('gridCrm').getStore();
        
     //   console.log(_node.attributes.nodeType);
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.nodeType) {
            case 'sharedProjects':
                dataStore.baseParams.method = 'Crm.getSharedProjects';
                break;

            case 'otherUsersProjects':
                dataStore.baseParams.method = 'Crm.getOtherPeopleProjects';
                break;

            case 'allProjects':
                dataStore.baseParams.method = 'Crm.getAllProjects';
                break;


            case 'userProjects':
                dataStore.baseParams.method = 'Crm.getProjectsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner;
                break;

            case 'singleFolder':
                dataStore.baseParams.method        = 'Crm.getProjectsByFolderId';
                dataStore.baseParams.folderId = _node.attributes.folderId;
                break;
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    };    
    
        
    // public functions and variables
    return {
        displayFolderSelectDialog: _displayFolderSelectDialog,
        show: function(_node) {          
             var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();
            if (currentToolbar === false || currentToolbar.id != 'Crm_toolbar') {
                _showCrmToolbar();
                _showGrid(_node);
            }
            _loadData(_node);
        },
        
        getPanel: _getTreePanel,

        reload: function() {
            Ext.getCmp('gridCrm').getStore().reload();
            Ext.getCmp('gridCrm').doLayout();
        }
    }
    
}(); // end of application



Egw.Crm.ProjectEditDialog = function() {

    // private variables
    var dialog;
    var projectedit;
    
    // private functions 
    var handler_applyChanges = function(_button, _event) 
    {
        var grid_products          = Ext.getCmp('grid_choosenProducts');
		var store_products         = Ext.getCmp('grid_choosenProducts').getStore();
        var modified_products_json = Egw.Egwbase.Common.getJSONdata(store_products);
       
    	var projectForm = Ext.getCmp('projectDialog').getForm();
		projectForm.render();
    	
    	if(projectForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.pj_id = formData.values.pj_id;
			}	
            additionalData.products = modified_products_json;
            
			projectForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
                    store_products.reload();
                    store_products.rejectChanges();
    				window.opener.Egw.Crm.reload();
    			},
    			failure:function(form, action) {
    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }
 

    var handler_saveAndClose = function(_button, _event) 
    {       
        var store_products = Ext.getCmp('grid_choosenProducts').getStore();
        var modified_products_json = Egw.Egwbase.Common.getJSONdata(store_products);
       
    	var projectForm = Ext.getCmp('projectDialog').getForm();
		projectForm.render();
    	
    	if(projectForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.pj_id = formData.values.pj_id;
			}	
            additionalData.products = modified_products_json;
                        
			projectForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
                    store_products.reload();       
                    store_products.rejectChanges();                            
    				window.opener.Egw.Crm.reload();
    				window.setTimeout("window.close()", 400);
    			},
    			failure:function(form, action) {
    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }

    var handler_pre_delete = function(){
        Ext.MessageBox.show({
            title: 'Delete Project?',
            msg: 'Are you sure you want to delete this project?',
            buttons: Ext.MessageBox.YESNO,
            fn: handler_delete,
//            animEl: 'mb4',
            icon: Ext.MessageBox.QUESTION
        });
    }


    var handler_delete = function(btn) 
    {
        if (btn == 'yes') {
            var projectIds = Ext.util.JSON.encode([formData.values.pj_id]);
            
            Ext.Ajax.request({
                params: {
                    method: 'Crm.deleteProjects',
                    _projectIds: projectIds
                },
                text: 'Deleting project...',
                success: function(_result, _request){
                    window.opener.Egw.Crm.reload();
                    window.setTimeout("window.close()", 400);
                },
                failure: function(result, request){
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the project.');
                }
            });
        } 
    }


   	var action_saveAndClose = new Ext.Action({
		text: 'save and close',
		handler: handler_saveAndClose,
		iconCls: 'action_saveAndClose'
	});

   	var action_applyChanges = new Ext.Action({
		text: 'apply changes',
		handler: handler_applyChanges,
		iconCls: 'action_applyChanges'
	});

   	var action_delete = new Ext.Action({
		text: 'delete project',
		handler: handler_pre_delete,
		iconCls: 'action_delete'
	});

    /**
     * display the event edit dialog
     *
     */
    var _displayDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';

        
        var disableButtons = true;
        if(formData.values) {
            disableButtons = false;
        }       
        
        var projectToolbar = new Ext.Toolbar({
        	region: 'south',
          	id: 'applicationToolbar',
			split: false,
			height: 26,
			items: [
				action_saveAndClose,
				action_applyChanges,
				action_delete
			]
		});

		var _setParameter = function(_dataSource)
		{
            _dataSource.baseParams.method = 'Crm.getEvents';
            _dataSource.baseParams.options = Ext.encode({
            });
        }
  
  
 
        var _editHandler = function(_button, _event) {
        
            editWindow.show();
            	
        }; 

        function formatDate(value){
            return value ? value.dateFormat('M d, Y') : '';
        };
    
        var _action_edit = new Ext.Action({
            text: 'editieren',
            //disabled: true,
            handler: _editHandler,
            iconCls: 'action_edit'
        });
 
 
         var st_productsAvailable = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getProductsource',
                sort: 'pj_productsource',
                dir: 'ASC'
            },
            root: 'results',
            autoLoad: true,
            totalProperty: 'totalcount',
            id: 'pj_productsource_id',
            fields: [
                {name: 'pj_productsource_id'},
                {name: 'value', mapping: 'pj_productsource'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
 
 
        var st_leadstatus = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getProjectstates',
                sort: 'pj_projectstate',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'key',
            fields: [
                {name: 'key', mapping: 'pj_projectstate_id'},
                {name: 'value', mapping: 'pj_projectstate'}

            ],
            // turn on remote sorting
            remoteSort: true
        });
 
        var leadstatus = new Ext.form.ComboBox({
                fieldLabel:'Leadstatus', 
                id:'leadstatus',
                name:'projectstate',
                hiddenName:'pj_distributionphase_id',
				store: st_leadstatus,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
//				mode: 'local',
				triggerAction: 'all',
				emptyText:'',
				selectOnFocus:true,
				editable: false,
				anchor:'96%'    
        });
	
	
        var st_leadtyp = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadtypes',
                sort: 'pj_leadtype',
                dir: 'ASC'                
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'key',
            fields: [
                {name: 'key', mapping: 'pj_leadtype_id'},
                {name: 'value', mapping: 'pj_leadtype'}

            ],
            // turn on remote sorting
            remoteSort: true
        }); 	
		
		var leadtyp = new Ext.form.ComboBox({
                fieldLabel:'Leadtypes', 
                id:'leadtype',
                name:'pj_leadtyp',
                hiddenName:'pj_customertype_id',
				store: st_leadtyp,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
				triggerAction: 'all',
				emptyText:'',
				selectOnFocus:true,
				editable: false,
				anchor:'96%'    
        });
    

        var st_leadsource = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadsources',
                sort: 'pj_leadsource',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'key',
            fields: [
                {name: 'key', mapping: 'pj_leadsource_id'},
                {name: 'value', mapping: 'pj_leadsource'}

            ],
            // turn on remote sorting
            remoteSort: true
        }); 	

		var leadsource = new Ext.form.ComboBox({
                fieldLabel:'Leadquelle', 
                id:'leadsource',
                name:'pj_leadsource',
                hiddenName:'pj_leadsource_id',
				store: st_leadsource,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
//				mode: 'local',
				triggerAction: 'all',
				emptyText:'',
				selectOnFocus:true,
				editable: false,
				anchor:'100%'    
        });
		
     
        var st_owner =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Meier, Heinz'],
                        ['1','Schultze, Hans'],
                        ['2','Vorfelder, Meier']
                    ]
        });
        
        var st_activities = new Ext.data.SimpleStore({
                fields: ['id','status','status2','datum','titel','message','responsible'],
                data: [
                        ['0','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['1','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['2','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['3','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['4','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['5','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
                        ['6','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
                        ['7','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['8','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
                        ['9','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner']
                    ]
        });
     
        var st_probability = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0%'],
                        ['1','10%'],
                        ['2','20%'],
                        ['3','30%'],
                        ['4','40%'],
                        ['5','50%'],
                        ['6','60%'],
                        ['7','70%'],
                        ['8','80%'],
                        ['9','90%'],
                        ['10','100%']
                    ]
        });
     
        var st_contacts = new Ext.data.JsonStore({
      //      baseParams: getParameterContactsDataStore(_node),
            root: 'results',
            totalProperty: 'totalcount',
            id: 'contact_id',
            fields: [
                {name: 'contact_id'},
                {name: 'contact_owner'},
                {name: 'n_family'},
                {name: 'n_given'},
                {name: 'n_middle'},
                {name: 'n_prefix'},
                {name: 'n_suffix'},
                {name: 'n_fn'},
                {name: 'n_fileas'},
                {name: 'org_name'},
                {name: 'org_unit'},
                {name: 'adr_one_street'},
                {name: 'adr_one_locality'},
                {name: 'adr_one_region'},
                {name: 'adr_one_postalcode'},
                {name: 'adr_one_countryname'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        st_contacts.setDefaultSort('n_family', 'asc');
        
        st_contacts.on('beforeload', function(_st_contacts) {
            _st_contacts.baseParams.datatype = 'allcontacts';
            _st_contacts.baseParams.method = 'Addressbook.getAllContacts';
            _st_contacts.baseParams.owner = 'allcontacts';
        });   
        
        
        var tpl_contacts = new Ext.Template(
            '<div >',
                '<h3>{n_family}</h3>',
            '</div>'
        );
     
        var cm_activities = new Ext.grid.ColumnModel([
            	{id:'status', header: "Status", dataIndex: 'status', width: 25, sortable: true },
                {id:'status2', header: "Status2", dataIndex: 'status2', width: 25, sortable: true },
                {id:'datum', header: "Datum", dataIndex: 'datum', width: 100, sortable: true },
                {id:'titel', header: "Titel", dataIndex: 'titel', width: 170, sortable: true },
                {id:'message', header: "Message", dataIndex: 'message', width: 300, sortable: true },
                {id:'responsible', header: "Verantwortlicher", dataIndex: 'responsible', width: 300, sortable: true }
        ]);
        
        var activities_limited = new Ext.grid.GridPanel({
                store: st_activities,
                id:'grid_activities_limited',
                cm: cm_activities,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 100%',
                enableColumnHide: false,
                enableColumnMove: false,
                enableHdMenu: false,
                stripeRows: true, 
                frame:false,
                iconCls:'icon-grid'
        });  
  
  
	   if (formData.values) {
			var _pj_id = formData.values.pj_id;
	   } else {
			var _pj_id = 'NULL';
	   }
  
        var st_choosenProducts = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getProductsById',
                _id: _pj_id
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'pj_id',
            fields: [
                {name: 'pj_id'},
                {name: 'pj_product_id'},
                {name: 'pj_product_desc'},
                {name: 'pj_product_price'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        st_choosenProducts.load();


        var cm_choosenProducts = new Ext.grid.ColumnModel([{
                header: "Produkt",
                dataIndex: 'pj_product_id',
                width: 300,
                editor: new Ext.form.ComboBox({
                    name: 'product_combo',
                    id: 'product_combo',
                    hiddenName: 'pj_productsource_id', //pj_product_id',
                    store: st_productsAvailable, 
                    displayField:'value', 
                    valueField: 'pj_productsource_id',
                    allowBlank: false, 
                    editable: false,
                    selectOnFocus:true,
                    forceSelection: true, 
                    triggerAction: "all", 
                  //  mode: 'local', 
                    lazyRender:true,
                    listClass: 'x-combo-list-small'
                    }),
                renderer: function(data){
                    record = st_productsAvailable.getById(data);
                    if (record) {
                        return record.data.value;
                    }
                    else {
                        Ext.getCmp('projectDialog').doLayout();
                        return data;
                    }
                  }
                } , { 
                header: "Seriennummer",
                dataIndex: 'pj_product_desc',
                width: 300,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                } , {
                header: "Preis",
                dataIndex: 'pj_product_price',
                width: 150,
                align: 'right',
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    decimalSeparator: ','
                    }),
                renderer: Ext.util.Format.euMoney
                }
        ]);
       
        
        var handler_remove_product = function(_button, _event)
        {
    		var productGrid = Ext.getCmp('grid_choosenProducts');
    		var productStore = productGrid.getStore();
            
    		var selectedRows = productGrid.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                productStore.remove(selectedRows[i]);
            }    	
        }; 
        
        var product = Ext.data.Record.create([
           {name: 'pj_id', type: 'int'},
           {name: 'pj_project_id', type: 'int'},
           {name: 'pj_product_id', type: 'int'},
           {name: 'pj_product_desc', type: 'string'},
           {name: 'pj_product_price', type: 'float'}
        ]);
        
        var grid_choosenProducts = new Ext.grid.EditorGridPanel({
            store: st_choosenProducts,
            id: 'grid_choosenProducts',
            cm: cm_choosenProducts,
            sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
            anchor: '100% 100%',
//            autoExpandColumn:'common',
            frame:false,
            clicksToEdit:2,
            tbar: [{
                text: 'Produkt hinzufügen',
                handler : function(){
                    var p = new product({
                        pj_id: 'NULL',
						pj_project_id: _pj_id,
                        pj_product_id: '',                       
                        pj_product_desc:'',
                        pj_product_price: ''
                    });
                    grid_choosenProducts.stopEditing();
                    st_choosenProducts.insert(0, p);
                    grid_choosenProducts.startEditing(0, 0);
                }
            } , {
                text: 'Produkt löschen',
                handler : handler_remove_product
            }]  
        });


        grid_choosenProducts.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            //var record = _grid.getStore().getAt(rowIndex);
            //_ctxMenuGrid.showAt(_eventObject.getXY());
        });
        /*
    var action_delete = new Ext.Action({
        text: 'delete contact',
        disabled: true,
        handler: _deleteBtnHandler,
        iconCls: 'action_delete'
    });        
        
    var _ctxMenuGrid = new Ext.menu.Menu({
        id:'ctxMenuProduct', 
        items: [
            action_delete
        ]
    });
      */  
  
        var cm_choosenContacts = new Ext.grid.ColumnModel([
            	{id:'anschrift', header: "Anschrift", dataIndex: 'anschrift', width: 250, sortable: true },
                {id:'kontakt', header: "Kontakt", dataIndex: 'kontakt', width: 250, sortable: true }
        ]);
  
        var st_choosenContacts = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0%'],
                        ['1','10%']
                    ]
        });
  
        var grid_contact = new Ext.grid.GridPanel({
                store: st_choosenContacts,
                id:'grid_choosenContacts',
                cm: cm_choosenContacts,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                anchor: '100% 87%',
                enableColumnHide: false,
                enableColumnMove: false,
                enableHdMenu: false,
                stripeRows: true, 
                frame:false,
                iconCls:'icon-grid'
        });  
  
  		var folderTrigger = new Ext.form.TriggerField({
            fieldLabel:'Folder (Verantwortlicher)', 
			id: 'pj_owner_name',
            anchor:'100%',
            allowBlank: false,
            readOnly:true
        });

        folderTrigger.onTriggerClick = function() {
            Egw.Crm.displayFolderSelectDialog('pj_owner');
        };
  
  
		var projectedit = new Ext.FormPanel({
			baseParams: {method :'Crm.saveProject'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
            anchor:'100%',
			region: 'center',
            id: 'projectDialog',
			tbar: projectToolbar, 
			deferredRender: false,
            items: [{
                xtype:'tabpanel',
	            plain:true,
	            activeTab: 0,
				deferredRender:false,
                anchor:'100% 100%',
	            defaults:{bodyStyle:'padding:10px'},
	            items:[{
	                title:'Übersicht',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                        layout:'column',
        	            border:false,
        				deferredRender:false,
                        anchor:'100%',
                        items:[{
                            columnWidth:.5,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'fieldset',
                                title:'Projekt',
                                height: 270,
                                anchor:'99%',
                                items: [{
                                    xtype:'textfield',
                                    fieldLabel:'Projektname', 
                                    name:'pj_name',
                                    anchor:'100%'
                                } , {
                                    layout:'column',
                    	            border:false,
                    				deferredRender:false,
                                    anchor:'100%',
                                    items:[{
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [
                                            leadstatus  
                                         , 
									 		leadtyp
										]
                                    } , {                               
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [
                                        	leadsource  
                                         , folderTrigger]
                                    },{
                                        xtype: 'hidden',
                        				name: 'pj_owner',
                        				id: 'pj_owner'
                        			}]
                                } , {
                                    xtype:'textarea',
                                    fieldLabel:'Notizen', 
                                    hideLabel: true,
                                    name:'pj_description',
                                    height: 90,
                                    anchor:'100%'
                                }]
                            } , {
                                xtype:'fieldset',
                                title:'Projektdaten',
                                height: 75,
                                anchor:'99%',
                                items: [{
                                    layout:'column',
                    	            border:false,
                    				deferredRender:false,
                                    anchor:'100%',
                                    items:[{
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'Start', 
                                            name:'pj_start',
                                            id:'startDate',
                                     //       format:formData.config.dateFormat, 
									 		format: 'd.m.Y',
                                            altFormat:'Y-m-d',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'Ende', 
                                            name:'pj_end',
                                            id:'endDate',
                                     //       format:formData.config.dateFormat, 
 									 		format: 'd.m.Y',
                                            altFormat:'Y-m-d',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'voraussichtl. Ende', 
                                            name:'pj_end_scheduled',
                                            id:'expectedEndDate',
                                      //    format:formData.config.dateFormat, 
  									 		format: 'd.m.Y',
                                            altFormat:'Y-m-d',
                                            anchor:'95%'
                                        }]
                                    }]
                                }]
                            }]
                        } , {
                            columnWidth:.5,
                            layout: 'form',
                            border:false,
                            items: [{
                                xtype:'fieldset',
                                title:'Kontakt',
                                height: 270,
                                anchor:'100%',
                                items: [{
                                    xtype:'combo',
                                    store: st_contacts,
                                    id: 'st_contacts',
                                    displayField:'contact_id',
                                    typeAhead: false,
                                    loadingText: 'Searching...',
                                    anchor: '100%',
                                    pageSize:50,
                                    queryParam: 'filter',
                                    hideTrigger:true,
                                    tpl: tpl_contacts,
                                    itemSelector: 'div.search-item',
                                    fieldLabel:'Kontakt auswählen', 
                                    hideLabel: true,
                                    name:'pj_contacts',
                                  /*  store: st_contacts,                            
                                    displayField:'n_fileas',
                                    valueField:'contact_id',
                                    typeAhead: false,
                                    loadingText: 'Searching...',
                                    mode: 'local',
                                    hideTrigger: true,                                    
                                    pageSize:10,
                                    tpl: tpl_contacts,
                                    itemSelector: 'div.search-item', */
                            		onSelect: function(record) {
                                       // alert(st_contacts.getById(0));
                            		//	console.log(record.data);
                                    }
                                } , grid_contact
                                ]
                            } , {
                                xtype:'fieldset',
                                title:'Umsatz',
                                height: 75,
                                anchor:'100%',
                                items: [{
                                    layout:'column',
                    	            border:false,
                    				deferredRender:false,
                                    anchor:'100%',
                                    items:[{
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'numberfield',
                                            fieldLabel:'erwarteter Umsatz', 
                                            name:'pj_turnover',
                                            anchor:'97%'
                                        }]
                                    } , {
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'combo',
                                            fieldLabel:'Wahrscheinlichkeit', 
                                            name:'pj_probability',
            								store: st_probability,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'100%'
                                        }]
                                    }]
                                }]
                            }]
                        }]
                    } , {
                        xtype:'fieldset',
                        title:'Produktübersicht',
                        height: 60,
                        anchor:'100%',
                        items: [{
                            xtype:'textfield',
                            fieldLabel:'Produkte', 
                            hideLabel: true,
                            name:'pj_productoverview',
                            id:'productoverview',
                            disabled: true,
                            value:'1 Inlab MC XL, 1 InEOS, 1 InFire, 1 InCoris',
                            anchor:'100%'
                        }]
                    } , {
                        xtype:'fieldset',
                        title:'letzte 10 Aktivitäten',
                        anchor:'100%',
                        height: 190,
                        items: [
                                activities_limited
                        ]
                    }]
                } , {
	                title:'Aktivitäten',
	                layout:'form',
					deferredRender:false,
					border:false,
					items:[{  
                    }]
                } , {
	                title:'Produkte',
	                layout:'form',
					deferredRender:false,
                    anchor:'100% 100%',
					border:false,
					items:[{  
                        xtype:'fieldset',
                        title:'gewählte Produkte',
                        anchor:'100% 100%',
                        items: [
                            grid_choosenProducts
                        ]
                    
                    }]
                }]
            }]
        });

		var viewport = new Ext.Viewport({
			layout: 'border',
			items: projectedit
		});        

    }

    var setProjectDialogValues = function(_formData) {        
    	var form = Ext.getCmp('projectDialog').getForm();
    	
    	form.setValues(_formData.values);
        
        form.findField('pj_owner_name').setValue(_formData.config.folderName);
        

        if (formData.values.pj_start > 0) {
			var startDate = new Date(eval(formData.values.pj_start * 1000));
		}
		
		if (formData.values.pj_end > 0) {
			var endDate = new Date(eval(formData.values.pj_end * 1000));
		}
		
		if (formData.values.pj_end_scheduled > 0) {
			var expectedEndDate = new Date(eval(formData.values.pj_end_scheduled * 1000));
		}

        form.findField('startDate').setValue(startDate);
        form.findField('endDate').setValue(endDate);
        form.findField('expectedEndDate').setValue(expectedEndDate);

		if (formData.values.pj_distributionphase_id) {
			
			var leadstatus = Ext.getCmp('leadstatus');
			var st_leadstatus = leadstatus.store;
			st_leadstatus.on('load', function(){
				leadstatus.setValue(formData.values.pj_distributionphase_id);
			}, this, {
				single: true
			});
			st_leadstatus.load();
		}

		if (formData.values.pj_leadsource_id) {
			var leadsource = Ext.getCmp('leadsource');
			var st_leadsource = leadsource.store;
			st_leadsource.on('load', function(){
				leadsource.setValue(formData.values.pj_leadsource_id);
			}, this, {
				single: true
			});
			st_leadsource.load();
		}
	
		if (formData.values.pj_customertype_id) {
			var leadtype = Ext.getCmp('leadtype');
			var st_leadtype = leadtype.store;
			st_leadtype.on('load', function(){
				leadtype.setValue(formData.values.pj_customertype_id);
			}, this, {
				single: true
			});
			st_leadtype.load();
		}  
    }

    var _exportContact = function(_btn, _event) {
        Ext.MessageBox.alert('Export', 'Not yet implemented.');
    }
    
    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                setProjectDialogValues(formData);
            }
         }
        
    }
    
}(); // end of application