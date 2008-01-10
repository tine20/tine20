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
                        folderId: _treeNodeContextMenu.attributes.folderId
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
                    case 'otherUsersProjects':
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
                case 'otherUsersProjects':
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
                {name: 'pj_leadstate_id'},
                {name: 'pj_leadtype_id'},
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
                
                {name: 'pj_leadstate'},
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
        
        ds_crm.load({params:{
                            start:0,
                            limit:50,
                            dateFrom: Ext.getCmp('Crm_dateFrom').getRawValue(),
                            dateTo: Ext.getCmp('Crm_dateTo').getRawValue()
                             }});
        
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


    var _showCrmToolbar = function(){
    
        var st_probability = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','0 %'],
                        ['10','10 %'],
                        ['20','20 %'],
                        ['30','30 %'],
                        ['40','40 %'],
                        ['50','50 %'],
                        ['60','60 %'],
                        ['70','70 %'],
                        ['80','80 %'],
                        ['90','90 %'],
                        ['100','100 %']
                    ]
        });
    
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width: 200,
            emptyText: 'enter searchfilter'
        });
        quickSearchField.on('change', function(){
            Ext.getCmp('gridCrm').getStore().load({
                params: {
                    dateFrom: Ext.getCmp('Crm_dateFrom').getRawValue(),
                    dateTo: Ext.getCmp('Crm_dateTo').getRawValue(),                    
                    start: 0,
                    limit: 50
                }
            });
        });
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id: 'Crm_dateFrom',
            allowBlank: false,
            validateOnBlur: false,
            value: oneWeekAgo
        });
        dateFrom.on('change', function(){
            Ext.getCmp('gridCrm').getStore().load({
                params: {
                    dateFrom: Ext.getCmp('Crm_dateFrom').getRawValue(),
                    dateTo: Ext.getCmp('Crm_dateTo').getRawValue(),
                    start: 0,
                    limit: 50
                }
            });        
        })
    
        
        var dateTo = new Ext.form.DateField({
            id:             'Crm_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            value:          currentDate
        });
        dateTo.on('change', function(){
            Ext.getCmp('gridCrm').getStore().load({
                params: {
                    dateFrom: Ext.getCmp('Crm_dateFrom').getRawValue(),
                    dateTo: Ext.getCmp('Crm_dateTo').getRawValue(),
                    start: 0,
                    limit: 50
                }
            });        
        })        
        

       function editLeadstate() {
           var Dialog = new Ext.Window({
				title: 'Leadstates',
                id: 'Leadstate_window',
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
            
            var st_leadstate = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getLeadstates',
                    sort: 'pj_leadstate',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'pj_leadstate_id',
                fields: [
                    {name: 'pj_leadstate_id'},
                    {name: 'pj_leadstate'},
                    {name: 'pj_leadstate_probability'},
                    {name: 'pj_leadstate_endsproject', type: 'boolean'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadstate.load();
            
           var checkColumn = new Ext.grid.CheckColumn({
               header: "X Project?",
               dataIndex: 'pj_leadstate_endsproject',
               width: 50
            });
            
            var cm_leadstate = new Ext.grid.ColumnModel([
                	{ id:'pj_leadstate_id', 
                      header: "id", 
                      dataIndex: 'pj_leadstate_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'pj_leadstate', 
                      header: 'entries', 
                      dataIndex: 'pj_leadstate', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    },
                    { id:'pj_leadstate_probability', 
                      header: 'probability', 
                      dataIndex: 'pj_leadstate_probability', 
                      width: 50, 
                      hideable: false, 
                      sortable: false, 
                      renderer: Ext.util.Format.percentage,
                      editor: new Ext.form.ComboBox({
                        name: 'probability',
                        id: 'leadstate_probability',
                        hiddenName: 'pj_leadstate_probability',
                        store: st_probability, 
                        displayField:'value', 
                        valueField: 'key',
                        allowBlank: true, 
                        editable: false,
                        selectOnFocus:true,
                        forceSelection: true, 
                        triggerAction: "all", 
                        mode: 'local', 
                        lazyRender:true,
                        listClass: 'x-combo-list-small'
                        }) 
                    }, 
                    checkColumn                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'pj_leadstate_id', type: 'int'},
               {name: 'pj_leadstate', type: 'varchar'},
               {name: 'pj_leadstate_probability', type: 'int'},
               {name: 'pj_leadstate_endsproject', type: 'boolean'}
            ]);
            
            var handler_leadstate_add = function(){
                var p = new entry({
                    pj_leadstate_id: 'NULL',
                    pj_leadstate: '',
                    pj_leadstate_probability: '',
                    pj_leadstate_endsproject: false
                });
                leadstateGridPanel.stopEditing();
                st_leadstate.insert(0, p);
                leadstateGridPanel.startEditing(0, 0);
            }
                        
            var handler_leadstate_delete = function(){
               	var leadstateGrid  = Ext.getCmp('editLeadstateGrid');
        		var leadstateStore = leadstateGrid.getStore();
                
        		var selectedRows = leadstateGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadstateStore.remove(selectedRows[i]);
                }   
            }                        
                        
          
           var handler_leadstate_saveClose = function(){
                var leadstate_store = Ext.getCmp('editLeadstateGrid').getStore();
                
                var leadstate_json = Egw.Egwbase.Common.getJSONdata(leadstate_store); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadstates',
                                optionsData: leadstate_json
                            },
                            text: 'Saving leadstates...',
                            success: function(_result, _request){
                                    leadstate_store.reload();
                                    leadstate_store.rejectChanges();
                               },
                            failure: function(form, action) {
                    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
                    			}
                        });          
            }          
            
            var leadstateGridPanel = new Ext.grid.EditorGridPanel({
                store: st_leadstate,
                id: 'editLeadstateGrid',
                cm: cm_leadstate,
                autoExpandColumn:'pj_leadstate',
                plugins:checkColumn,
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'action_add',
                    handler : handler_leadstate_add
                    },{
                    text: 'delete item',
                    iconCls: 'action_delete',
                    handler : handler_leadstate_delete
                    },{
                    text: 'save',
                    iconCls: 'action_saveAndClose',
                    handler : handler_leadstate_saveClose 
                    }]  
                });


            Ext.grid.CheckColumn = function(config){
                Ext.apply(this, config);
                if(!this.id){
                    this.id = Ext.id();
                }
                this.renderer = this.renderer.createDelegate(this);
            };
            
            Ext.grid.CheckColumn.prototype ={
                init : function(grid){
                    this.grid = grid;
                    this.grid.on('render', function(){
                        var view = this.grid.getView();
                        view.mainBody.on('mousedown', this.onMouseDown, this);
                    }, this);
                },
            
                onMouseDown : function(e, t){
                    if(t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
                        e.stopEvent();
                        var index = this.grid.getView().findRowIndex(t);
                        var record = this.grid.store.getAt(index);
                        record.set(this.dataIndex, !record.data[this.dataIndex]);
                    }
                },
            
                renderer : function(v, p, record){
                    p.css += ' x-grid3-check-col-td'; 
                    return '<div class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'">&#160;</div>';
                }
            };                    
                        
          Dialog.add(leadstateGridPanel);
          Dialog.show();		  
        }

      function editLeadsource() {
           var Dialog = new Ext.Window({
				title: 'Leadsources',
                id: 'Leadsource_window',
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
            
            var st_leadsource = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getLeadsources',
                    sort: 'pj_leadsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'pj_leadsource_id',
                fields: [
                    {name: 'pj_leadsource_id'},
                    {name: 'pj_leadsource'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadsource.load();
            
            var cm_leadsource = new Ext.grid.ColumnModel([
                	{ id:'pj_leadsource_id', 
                      header: "id", 
                      dataIndex: 'pj_leadsource_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'pj_leadsource', 
                      header: 'entries', 
                      dataIndex: 'pj_leadsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'pj_leadsource_id', type: 'int'},
               {name: 'pj_leadsource', type: 'varchar'}
            ]);
            
            var handler_leadsource_add = function(){
                var p = new entry({
                    pj_leadsource_id: 'NULL',
                    pj_leadsource: ''
                });
                leadsourceGridPanel.stopEditing();
                st_leadsource.insert(0, p);
                leadsourceGridPanel.startEditing(0, 0);
            }
                        
            var handler_leadsource_delete = function(){
               	var leadsourceGrid  = Ext.getCmp('editLeadsourceGrid');
        		var leadsourceStore = leadsourceGrid.getStore();
                
        		var selectedRows = leadsourceGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadsourceStore.remove(selectedRows[i]);
                }   
            }                        
                        
          
           var handler_leadsource_saveClose = function(){
                var leadsource_store = Ext.getCmp('editLeadsourceGrid').getStore();
                
                var leadsource_json = Egw.Egwbase.Common.getJSONdata(leadsource_store); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadsources',
                                optionsData: leadsource_json
                            },
                            text: 'Saving leadsources...',
                            success: function(_result, _request){
                                    leadsource_store.reload();
                                    leadsource_store.rejectChanges();
                               },
                            failure: function(form, action) {
                    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
                    			}
                        });          
            }          
            
            var leadsourceGridPanel = new Ext.grid.EditorGridPanel({
                store: st_leadsource,
                id: 'editLeadsourceGrid',
                cm: cm_leadsource,
                autoExpandColumn:'pj_leadsource',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'action_add',
                    handler : handler_leadsource_add
                    },{
                    text: 'delete item',
                    iconCls: 'action_delete',
                    handler : handler_leadsource_delete
                    },{
                    text: 'save',
                    iconCls: 'action_saveAndClose',
                    handler : handler_leadsource_saveClose 
                    }]  
                });
                    
                        
          Dialog.add(leadsourceGridPanel);
          Dialog.show();		  
        }
  
     function editLeadtype() {
           var Dialog = new Ext.Window({
				title: 'Leadtypes',
                id: 'Leadtype_window',
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
            
            var st_leadtype = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getLeadtypes',
                    sort: 'pj_leadtype',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'pj_leadtype_id',
                fields: [
                    {name: 'pj_leadtype_id'},
                    {name: 'pj_leadtype'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadtype.load();
            
            var cm_leadtype = new Ext.grid.ColumnModel([
                	{ id:'pj_leadtype_id', 
                      header: "id", 
                      dataIndex: 'pj_leadtype_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'pj_leadtype', 
                      header: 'entries', 
                      dataIndex: 'pj_leadtype', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'pj_leadtype_id', type: 'int'},
               {name: 'pj_leadtype', type: 'varchar'}
            ]);
            
            var handler_leadtype_add = function(){
                var p = new entry({
                    pj_leadtype_id: 'NULL',
                    pj_leadtype: ''
                });
                leadtypeGridPanel.stopEditing();
                st_leadtype.insert(0, p);
                leadtypeGridPanel.startEditing(0, 0);
            }
                        
            var handler_leadtype_delete = function(){
               	var leadtypeGrid  = Ext.getCmp('editLeadtypeGrid');
        		var leadtypeStore = leadtypeGrid.getStore();
                
        		var selectedRows = leadtypeGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadtypeStore.remove(selectedRows[i]);
                }   
            }                        
                        
          
           var handler_leadtype_saveClose = function(){
                var leadtype_store = Ext.getCmp('editLeadtypeGrid').getStore();
                
                var leadtype_json = Egw.Egwbase.Common.getJSONdata(leadtype_store); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadtypes',
                                optionsData: leadtype_json
                            },
                            text: 'Saving leadtypes...',
                            success: function(_result, _request){
                                    leadtype_store.reload();
                                    leadtype_store.rejectChanges();
                               },
                            failure: function(form, action) {
                    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
                    			}
                        });          
            }          
            
            var leadtypeGridPanel = new Ext.grid.EditorGridPanel({
                store: st_leadtype,
                id: 'editLeadtypeGrid',
                cm: cm_leadtype,
                autoExpandColumn:'pj_leadtype',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'action_add',
                    handler : handler_leadtype_add
                    },{
                    text: 'delete item',
                    iconCls: 'action_delete',
                    handler : handler_leadtype_delete
                    },{
                    text: 'save',
                    iconCls: 'action_saveAndClose',
                    handler : handler_leadtype_saveClose 
                    }]  
                });
                    
                        
          Dialog.add(leadtypeGridPanel);
          Dialog.show();		  
        }
    
    function editProductsource() {
           var Dialog = new Ext.Window({
				title: 'Products',
                id: 'Product_window',
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
            
            var st_productsource = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getProductsource',
                    sort: 'pj_productsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'pj_productsource_id',
                fields: [
                    {name: 'pj_productsource_id'},
                    {name: 'pj_productsource'},
                    {name: 'pj_productsource_price'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_productsource.load();
            
            var cm_productsource = new Ext.grid.ColumnModel([
                	{ id:'pj_productsource_id', 
                      header: "id", 
                      dataIndex: 'pj_productsource_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'pj_productsource', 
                      header: 'entries', 
                      dataIndex: 'pj_productsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }, 
                    {
                      id: 'pj_productsource_price',  
                      header: "price",
                      dataIndex: 'pj_productsource_price',
                      width: 80,
                      align: 'right',
                      editor: new Ext.form.NumberField({
                          allowBlank: false,
                          allowNegative: false,
                          decimalSeparator: ','
                          }),
                      renderer: Ext.util.Format.euMoney                    
                    }
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'pj_productsource_id', type: 'int'},
               {name: 'pj_productsource', type: 'varchar'},
               {name: 'pj_productsource_price', type: 'number'}
            ]);
            
            var handler_productsource_add = function(){
                var p = new entry({
                    pj_productsource_id: 'NULL',
                    pj_productsource: '',
                    pj_productsource_price: '0,00'
                });
                productsourceGridPanel.stopEditing();
                st_productsource.insert(0, p);
                productsourceGridPanel.startEditing(0, 0);
            }
                        
            var handler_productsource_delete = function(){
               	var productsourceGrid  = Ext.getCmp('editProductsourceGrid');
        		var productsourceStore = productsourceGrid.getStore();
                
        		var selectedRows = productsourceGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    productsourceStore.remove(selectedRows[i]);
                }   
            }                        
                        
          
           var handler_productsource_saveClose = function(){
                var productsource_store = Ext.getCmp('editProductsourceGrid').getStore();
                
                var productsource_json = Egw.Egwbase.Common.getJSONdata(productsource_store); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveProductsource',
                                optionsData: productsource_json
                            },
                            text: 'Saving productsource...',
                            success: function(_result, _request){
                                    productsource_store.reload();
                                    productsource_store.rejectChanges();
                               },
                            failure: function(form, action) {
                    			//	Ext.MessageBox.alert("Error",action.result.errorMessage);
                    			}
                        });          
            }          
            
            var productsourceGridPanel = new Ext.grid.EditorGridPanel({
                store: st_productsource,
                id: 'editProductsourceGrid',
                cm: cm_productsource,
                autoExpandColumn:'pj_productsource',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'action_add',
                    handler : handler_productsource_add
                    },{
                    text: 'delete item',
                    iconCls: 'action_delete',
                    handler : handler_productsource_delete
                    },{
                    text: 'save',
                    iconCls: 'action_saveAndClose',
                    handler : handler_productsource_saveClose 
                    }]  
                });
                    
                        
          Dialog.add(productsourceGridPanel);
          Dialog.show();		  
        }     
        
        var settings_tb_menu = new Ext.menu.Menu({
            id: 'crmSettingsMenu',
            items: [
                {text: 'Leadstatus', handler: editLeadstate},
                {text: 'Leadsource', handler: editLeadsource},
                {text: 'Leadtype', handler: editLeadtype},
                {text: 'Product', handler: editProductsource}
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
            {resizable: true, header: 'Status', id: 'pj_leadstate', dataIndex: 'pj_leadstate', width: 150},
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
                limit:50,
                dateFrom: Ext.getCmp('Crm_dateFrom').getRawValue(),
                dateTo: Ext.getCmp('Crm_dateTo').getRawValue()
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
		iconCls: 'action_applyChanges',
        disabled: true
	});

   	var action_delete = new Ext.Action({
		text: 'delete project',
		handler: handler_pre_delete,
		iconCls: 'action_delete',
        disabled: true
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
                {name: 'value', mapping: 'pj_productsource'},
                {name: 'pj_productsource_price'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
 
 
        var st_leadstatus = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadstates',
                sort: 'pj_leadstate',
                dir: 'ASC'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'key',
            fields: [
                {name: 'key', mapping: 'pj_leadstate_id'},
                {name: 'value', mapping: 'pj_leadstate'}

            ],
            // turn on remote sorting
            remoteSort: true
        });
 
        var leadstatus = new Ext.form.ComboBox({
                fieldLabel:'Leadstatus', 
                id:'leadstatus',
                name:'leadstate',
                hiddenName:'pj_leadstate_id',
				store: st_leadstatus,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
//				mode: 'local',
				triggerAction: 'all',
				emptyText:'',
				selectOnFocus:true,
				editable: false,
				anchor:'95%'    
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
                hiddenName:'pj_leadtype_id',
				store: st_leadtyp,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
				triggerAction: 'all',
				emptyText:'',
				selectOnFocus:true,
				editable: false,
				anchor:'95%'    
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
				anchor:'95%'    
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
  
        var cm_choosenContacts = new Ext.grid.ColumnModel([
            	{id:'anschrift', header: "Anschrift", dataIndex: 'anschrift', width: 250, sortable: true },
                {id:'kontakt', header: "Kontakt", dataIndex: 'kontakt', width: 250, sortable: true }
        ]);
  
        var st_choosenContacts = new Ext.data.SimpleStore({
            fields: ['firstname','lastname', 'company'],
            data: [
                ['Lars', 'Kneschke', 'Metaways Infosystems GmbH'],
                ['Thomas', 'Wadewitz', 'Metaways Infosystems GmbH']
            ]
        });
  
        var st_contactSearch = new Ext.data.SimpleStore({
            fields: ['firstname','lastname', 'company', 'street', 'plz', 'town', 'phone', 'cellphone', 'email', 'contact_id', 'contact_type'],
            data: [
                ['Lars', 'Kneschke', 'Metaways Infosystems GmbH', 'Pickhuben 2-4', '20457', 'Hamburg', '0123 / 456 78 90', '0177 / 123 45 67', 'l.kneschke@metaways.de', '62', '1'],
                ['Thomas', 'Wadewitz', 'Metaways Infosystems GmbH', 'Pickhuben 2-4', '20457', 'Hamburg', '5678 / 910 12 34', '0160 / 789 01 23', 't.wadewitz@metaways.de', '66', '2'],                                                                                                                    
                ['Lars', 'Kneschke', '', 'Pickhuben 2-4', '20457', 'Hamburg', '0123 / 456 78 90', '0177 / 123 45 67', 'l.kneschke@metaways.de', '62', '3'],
                ['', 'Wadewitz', 'Metaways Infosystems GmbH', 'Pickhuben 2-4', '20457', 'Hamburg', '5678 / 910 12 34', '0160 / 789 01 23', 't.wadewitz@metaways.de', '66', '2']
                                
            ]
        });  
  
        
	    // Custom rendering Template for the View
        var resultTpl = new Ext.XTemplate( 
                    '<tpl for=".">',
                    '<div class="contact-item {contact_type:this.getType}">',
                    '{company:this.isNotEmpty}', 
                    '<a href="index.php?method=Addressbook.editContact&_contactId={contact_id}" target="_new"><b>{lastname}, {firstname}</b></a><br />',
                    '{street:this.isNotEmpty}',                     
                    '{plz} {town:this.isNotEmpty}',
                    
                    '<p><i>Phone:</i> {phone}<br />', 
                    '<i>Cellphone:</i> {cellphone}<br />', 
                    '<a href="mailto:{email}">{email}</a></p>', 
                    '</div></tpl>', {
                        isNotEmpty: function(textValue){
                            if ((textValue.length == 0) || (textValue == null)) {
                                return '';
                            }
                            else {
                                return textValue+'<br />';
                            }
                        }, 
                        getType: function(typeId){                          
                            switch (typeId) {
                                case "1": return ' contactType_lead';
                                          break;
                                     
                                case "2": return ' contactType_partner';
                                          break;
                                          
                                case "3": return ' contactType_internal';
                                          break;
                            }
                        }                                                
        });

        var grid_contact = new Ext.Panel({
	        height:300,
            id: 'grid_contact',
            cls: 'contacts_background',                            
	        autoScroll:true,
	
	        items: new Ext.DataView({
	            tpl: resultTpl,                
	            store: st_contactSearch,
                height: '95%',
	            itemSelector: 'div.contact-item'
	        }),	
	        tbar: [
	            new Ext.app.SearchField({
	                store: st_contactSearch
	            })
	        ],
            bbar: [
                new Ext.Action({
                    text: 'Lead',
                    //disabled: true,
                    //handler: handler_toggleLeads,
                    iconCls: 'contactType_lead_icon'
                }),
                new Ext.Action({
                    text: 'Partner',
                    //disabled: true,
                    //handler: handler_toggleLeads,
                    iconCls: 'contactType_partner_icon'
                }),            
                new Ext.Action({
                    text: 'Internal',                    
                    //disabled: true,
                    //handler: handler_toggleLeads,
                    iconCls: 'contactType_internal_icon'
                })                    
            
            ]
        });  
  
  
  
  		var folderTrigger = new Ext.form.TriggerField({
            fieldLabel:'Folder (Verantwortlicher)', 
			id: 'pj_owner_name',
            anchor:'95%',
            allowBlank: false,
            readOnly:true
        });

        folderTrigger.onTriggerClick = function() {
            Egw.Crm.displayFolderSelectDialog('pj_owner');
        };
        
        
        
        var tabPanelOverview = {
            title:'Overview',
            layout:'form',
            //deferredRender:false,
            border:false,
            items:[{  
                layout:'column',
                border:false,
                //deferredRender:false,
                items:[{
                    columnWidth: 1,
                    layout: 'form',
                    //title: 'Overview',
                    border:false,
                    frame: true,
                    height: 350,
                    hideLabel:true,
                    items: [{
                        xtype:'textfield',
                        hideLabel: true,
                        //fieldLabel:'Projektname', 
                        emptyText: 'enter short name',
                        name:'pj_name',
                        anchor:'100%'
                        //selectOnFocus:true
                    }, {
                        xtype:'textarea',
                        //fieldLabel:'Notizen', 
                        hideLabel: true,
                        name:'pj_description',
                        height: 110,
                        anchor:'100%',
                        emptyText: 'enter description'
                    }, {
					    layout:'column',
					    //anchor:'100%',
					    items: [{
                            columnWidth: .33,
                            //anchor:'100%',
                            items:[{
                                layout: 'form',
                                items: [
                                    leadstatus, 
                                    leadtyp,
                                    leadsource
                                ]
                            }]					        
					    },{
					        columnWidth: .33,
                            items:[{
                                layout: 'form',
                                border:false,
                                items: [
                                {
                                    xtype:'numberfield',
                                    fieldLabel:'erwarteter Umsatz', 
                                    name:'pj_turnover',
                                    anchor:'95%'
                                }, {
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
                                    renderer: Ext.util.Format.percentage,
                                    anchor:'95%'
                                },
                                    folderTrigger 
                                ]
                            }]              
					    },{
					        columnWidth: .33,
                            items:[{
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
                                }, {
                                    xtype:'datefield',
                                    fieldLabel:'voraussichtl. Ende', 
                                    name:'pj_end_scheduled',
                                    id:'expectedEndDate',
                                    //    format:formData.config.dateFormat, 
                                    format: 'd.m.Y',
                                    altFormat:'Y-m-d',
                                    anchor:'95%'
                                }, {
                                    xtype:'datefield',
                                    fieldLabel:'Ende', 
                                    name:'pj_end',
                                    id:'endDate',
                                    //       format:formData.config.dateFormat, 
                                    format: 'd.m.Y',
                                    altFormat:'Y-m-d',
                                    anchor:'95%'
                                }]
                            }]
					    },{
                            xtype: 'hidden',
                            name: 'pj_owner',
                            id: 'pj_owner'
                        }]
					}]
                }, {
                    //title: 'Contacts',
                    width: 300,
                    layout: 'form',
                    border: false,
                    frame: true,
                    height: 350,
                    items: [
                            // alert(st_contacts.getById(0));
                            //  console.log(record.data);
                
                     grid_contact
                    ]
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
        };        
                
        var tabPanelActivities = {
            title:'Aktivitäten',
            layout:'form',
            deferredRender:false,
            border:false,
            items:[{  
            }]
        };
        
        var tabPanelProducts = {
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
        };
  
		var projectedit = new Ext.FormPanel({
			baseParams: {method :'Crm.saveProject'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
            anchor:'100%',
			region: 'center',
            id: 'projectDialog',
			tbar: projectToolbar, 
			//deferredRender: false,
            items: [{
                xtype:'tabpanel',
	            plain:true,
	            activeTab: 0,
				//deferredRender:false,
                anchor:'100% 100%',
	            defaults:{bodyStyle:'padding:10px'},
	            items:[
                    tabPanelOverview, 
                    tabPanelActivities, 
                    tabPanelProducts
                ]
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
        
        if (formData.values.pj_id > 0) {
            action_applyChanges.enable();
            action_delete.enable();
        }

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

		if (formData.values.pj_leadstate_id) {
			
			var leadstatus = Ext.getCmp('leadstatus');
			var st_leadstatus = leadstatus.store;
			st_leadstatus.on('load', function(){
				leadstatus.setValue(formData.values.pj_leadstate_id);
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
	
		if (formData.values.pj_leadtype_id) {
			var leadtype = Ext.getCmp('leadtype');
			var st_leadtype = leadtype.store;
			st_leadtype.on('load', function(){
				leadtype.setValue(formData.values.pj_leadtype_id);
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