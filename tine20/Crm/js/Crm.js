Ext.namespace('Egw.Crm');

Egw.Crm = function() {


   var _treeNodeContextMenu = null;

    /**
     * the initial tree to be displayed in the left treePanel
     */
    var _initialTree = [{
        text: 'All Leads',
        cls: "treemain",
        nodeType: 'allLeads',
        id: 'allLeads',
        children: [{
            text: 'My Leads',
            cls: 'file',
            nodeType: 'userLeads',
            id: 'userLeads',
            leaf: null,
            owner: Egw.Egwbase.Registry.get('currentAccount').account_id
        }, {
            text: "Shared Leads",
            cls: "file",
            nodeType: "sharedLeads",
            children: null,
            leaf: null
        }, {
            text: "Other Users Leads",
            cls: "file",
            nodeType: "otherUsersLeads",
            children: null,
            leaf: null
        }]
    }];
    
    var _handler_addFolder = function(_button, _event) {
        Ext.MessageBox.prompt('New Folder', 'Please enter the name of the new folder:', function(_btn, _text) {
            if(_treeNodeContextMenu !== null && _btn == 'ok') {

                //console.log(_treeNodeContextMenu);
                var type = 'personal';
                if(_treeNodeContextMenu.attributes.nodeType == 'sharedLeads') {
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
                        //Ext.getCmp('Crm_Leads_Grid').getStore().reload();
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
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the lead.');
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
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the lead.');
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
                    case 'otherUsersLeads':
                        _loader.baseParams.method   = 'Crm.getOtherUsers';
                        break;
                        
                    case 'sharedLeads':
                        _loader.baseParams.method   = 'Crm.getSharedFolders';
                        break;
    
                    case 'userLeads':
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
                           
            tree.expandPath('/root/allLeads');
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
                case 'otherUsersLeads':
                    _loader.baseParams.method   = 'Crm.getOtherUsers';
                    break;
                    
                case 'sharedLeads':
                    _loader.baseParams.method   = 'Crm.getSharedFolders';
                    break;

                case 'userLeads':
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
                _panel.expandPath('/root/allLeads');
                _panel.selectPath('/root/allLeads');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            _treeNodeContextMenu = _node;

            switch(_node.attributes.nodeType) {
                case 'userLeads':
                case 'sharedLeads':
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
                method: 'Crm.getLeadsByOwner',
                owner: 'all'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'lead_id',
            fields: [
                {name: 'lead_id'},            
                {name: 'lead_name'},
                {name: 'lead_leadstate_id'},
                {name: 'lead_leadtype_id'},
                {name: 'lead_leadsource_id'},
                {name: 'lead_container'},
                {name: 'lead_modifier'},
                {name: 'lead_start'},
                {name: 'lead_modified'},
                {name: 'lead_description'},
                {name: 'lead_end'},
                {name: 'lead_turnover'},
                {name: 'lead_probability'},
                {name: 'lead_end_scheduled'},
                {name: 'lead_lastread'},
                {name: 'lead_lastreader'},
                
                {name: 'lead_leadstate'},
                {name: 'lead_leadtype'},
                {name: 'lead_leadsource'},
                
                {name: 'lead_partner_linkId'},
                {name: 'lead_partner', type: ''},
                {name: 'lead_partner_detail'},                
                {name: 'lead_lead_linkId'},
                {name: 'lead_customer'},
                {name: 'lead_lead_detail'}               

  
            ],
            // turn on remote sorting
            remoteSort: true
        });
     
   
        ds_crm.setDefaultSort('lead_name', 'asc');

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
                            dateFrom: '', //Ext.getCmp('Crm_dateFrom').getRawValue(),
                            dateTo: '', //Ext.getCmp('Crm_dateTo').getRawValue()
							leadstate: Ext.getCmp('filter_leadstate').getValue(),
							probability: Ext.getCmp('filter_probability').getValue()
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
            
                Egw.Egwbase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 900, 700);
             }
 		}); 
        
        var handler_edit = function() 
        {
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Egw.Egwbase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+_rowIndex[0].id, 900, 700);   

        }

    var handler_pre_delete = function(){
        Ext.MessageBox.show({
            title: 'Delete Lead?',
            msg: 'Are you sure you want to delete this lead?',
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
                
                var leadIds = Ext.util.JSON.encode(toDelete_Ids);

                Ext.Ajax.request({
                    params: {
                        method: 'Crm.deleteLeads',
                        _leadIds: leadIds
                    },
                    text: 'Deleting lead...',
                    success: function(_result, _request){
                        Ext.getCmp('gridCrm').getStore().reload();
                    },
                    failure: function(result, request){
                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the lead.');
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
        
        var st_probability_withNone = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        [null,'none'],
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
                    dateFrom: '', // Ext.getCmp('Crm_dateFrom').getRawValue(),
                    dateTo: '', //Ext.getCmp('Crm_dateTo').getRawValue(),                    
                    start: 0,
                    limit: 50,
					leadstate: Ext.getCmp('filter_leadstate').getValue(),
					probability: Ext.getCmp('filter_probability').getValue()					
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
                    limit: 50,
					leadstate: Ext.getCmp('filter_leadstate').getValue(),
					probability: Ext.getCmp('filter_probability').getValue()					
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
                    limit: 50,
					leadstate: Ext.getCmp('filter_leadstate').getValue(),
					probability: Ext.getCmp('filter_probability').getValue()					
                }
            });        
        })        
       
	  
	   var st_leadstate = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Crm.getLeadstates',
                    sort: 'lead_leadstate',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'lead_leadstate_id',
                fields: [
                    {name: 'lead_leadstate_id'},
                    {name: 'lead_leadstate'},
                    {name: 'lead_leadstate_probability'},
                    {name: 'lead_leadstate_endslead', type: 'boolean'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadstate.load();
			
	   var filter_combo_leadstate = new Ext.app.ClearableComboBox({
			fieldLabel:'Leadstate', 
			id:'filter_leadstate',
			name:'leadstate',
			hideLabel: true,
			width: 180,   
			blankText: 'leadstate...',
			hiddenName:'lead_leadstate_id',
			store: st_leadstate,
			displayField:'lead_leadstate',
			valueField:'lead_leadstate_id',
			typeAhead: true,
	    	mode: 'local',
			triggerAction: 'all',
			emptyText:'leadstate...',
			selectOnFocus:true,
			editable: false 
	   });          
	   filter_combo_leadstate.on('select', function(combo, record, index) {
           if (!record.data) {
               var _leadstate = '';       
           } else {
               var _leadstate = record.data.lead_leadstate_id;
           }
           
           combo.triggers[0].show();
           
           Ext.getCmp('gridCrm').getStore().load({
               params: {
                   dateFrom: '', // Ext.getCmp('Crm_dateFrom').getRawValue(),
                dateTo: '', //Ext.getCmp('Crm_dateTo').getRawValue(),                    
                start: 0,
                limit: 50,
                leadstate: _leadstate,
                probability: Ext.getCmp('filter_probability').getValue()
                }
            });
	   });
      
	   var filter_combo_probability = new Ext.app.ClearableComboBox({
			fieldLabel:'probability', 
			id: 'filter_probability',
			name:'lead_probability',
			hideLabel: true,			
			store: st_probability,
			blankText: 'probability...',			
			displayField:'value',
			valueField:'key',
			typeAhead: true,
			mode: 'local',
			triggerAction: 'all',
			emptyText:'probability...',
			selectOnFocus:true,
			editable: false,
			renderer: Ext.util.Format.percentage,
			width:90    
		});
	   filter_combo_probability.on('select', function(combo, record, index) {
           if (!record.data) {
               var _probability = '';       
           } else {
               var _probability = record.data.key;
           }           
           
           combo.triggers[0].show();           
           
	       Ext.getCmp('gridCrm').getStore().load({
                params: {
                    dateFrom: '', // Ext.getCmp('Crm_dateFrom').getRawValue(),
                    dateTo: '', //Ext.getCmp('Crm_dateTo').getRawValue(),                    
                    start: 0,
                    limit: 50,
					leadstate: Ext.getCmp('filter_leadstate').getValue(),
					probability: _probability
                }
            });	   		
	   });		
	    

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
                    sort: 'lead_leadstate',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'lead_leadstate_id',
                fields: [
                    {name: 'lead_leadstate_id'},
                    {name: 'lead_leadstate'},
                    {name: 'lead_leadstate_probability'},
                    {name: 'lead_leadstate_endslead', type: 'boolean'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadstate.load();
            
           var checkColumn = new Ext.grid.CheckColumn({
               header: "X Lead?",
               dataIndex: 'lead_leadstate_endslead',
               width: 50
            });
            
            var cm_leadstate = new Ext.grid.ColumnModel([
                	{ id:'lead_leadstate_id', 
                      header: "id", 
                      dataIndex: 'lead_leadstate_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'lead_leadstate', 
                      header: 'entries', 
                      dataIndex: 'lead_leadstate', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    },
                    { id:'lead_leadstate_probability', 
                      header: 'probability', 
                      dataIndex: 'lead_leadstate_probability', 
                      width: 50, 
                      hideable: false, 
                      sortable: false, 
                      renderer: Ext.util.Format.percentage,
                      editor: new Ext.form.ComboBox({
                        name: 'probability',
                        id: 'leadstate_probability',
                        hiddenName: 'lead_leadstate_probability',
                        store: st_probability_withNone, 
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
               {name: 'lead_leadstate_id', type: 'int'},
               {name: 'lead_leadstate', type: 'varchar'},
               {name: 'lead_leadstate_probability', type: 'int'},
               {name: 'lead_leadstate_endslead', type: 'boolean'}
            ]);
            
            var handler_leadstate_add = function(){
                var p = new entry({
                    lead_leadstate_id: null,
                    lead_leadstate: '',
                    lead_leadstate_probability: null,
                    lead_leadstate_endslead: false
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
                                    Ext.getCmp('filter_leadstate').store.reload();
                                    
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
                autoExpandColumn:'lead_leadstate',
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
                    sort: 'lead_leadsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'lead_leadsource_id',
                fields: [
                    {name: 'lead_leadsource_id'},
                    {name: 'lead_leadsource'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadsource.load();
            
            var cm_leadsource = new Ext.grid.ColumnModel([
                	{ id:'lead_leadsource_id', 
                      header: "id", 
                      dataIndex: 'lead_leadsource_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'lead_leadsource', 
                      header: 'entries', 
                      dataIndex: 'lead_leadsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'lead_leadsource_id', type: 'int'},
               {name: 'lead_leadsource', type: 'varchar'}
            ]);
            
            var handler_leadsource_add = function(){
                var p = new entry({
                    lead_leadsource_id: 'NULL',
                    lead_leadsource: ''
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
                autoExpandColumn:'lead_leadsource',
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
                    sort: 'lead_leadtype',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'lead_leadtype_id',
                fields: [
                    {name: 'lead_leadtype_id'},
                    {name: 'lead_leadtype'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_leadtype.load();
            
            var cm_leadtype = new Ext.grid.ColumnModel([
                	{ id:'lead_leadtype_id', 
                      header: "id", 
                      dataIndex: 'lead_leadtype_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'lead_leadtype', 
                      header: 'entries', 
                      dataIndex: 'lead_leadtype', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'lead_leadtype_id', type: 'int'},
               {name: 'lead_leadtype', type: 'varchar'}
            ]);
            
            var handler_leadtype_add = function(){
                var p = new entry({
                    lead_leadtype_id: 'NULL',
                    lead_leadtype: ''
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
                autoExpandColumn:'lead_leadtype',
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
                    sort: 'lead_productsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'lead_productsource_id',
                fields: [
                    {name: 'lead_productsource_id'},
                    {name: 'lead_productsource'},
                    {name: 'lead_productsource_price'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            st_productsource.load();
            
            var cm_productsource = new Ext.grid.ColumnModel([
                	{ id:'lead_productsource_id', 
                      header: "id", 
                      dataIndex: 'lead_productsource_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'lead_productsource', 
                      header: 'entries', 
                      dataIndex: 'lead_productsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }, 
                    {
                      id: 'lead_productsource_price',  
                      header: "price",
                      dataIndex: 'lead_productsource_price',
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
               {name: 'lead_productsource_id', type: 'int'},
               {name: 'lead_productsource', type: 'varchar'},
               {name: 'lead_productsource_price', type: 'number'}
            ]);
            
            var handler_productsource_add = function(){
                var p = new entry({
                    lead_productsource_id: 'NULL',
                    lead_productsource: '',
                    lead_productsource_price: '0,00'
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
                autoExpandColumn:'lead_productsource',
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
                {text: 'leadstate', handler: editLeadstate},
                {text: 'leadsource', handler: editLeadsource},
                {text: 'leadtype', handler: editLeadtype},
                {text: 'product', handler: editProductsource}
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
                ' ',
                {
                    text: 'Show closed leads',
                    enableToggle: true,
                    id: 'toggle_button',
                    handler: function(toggle) {
                        var dataStore = Ext.getCmp('gridCrm').getStore();

                        if(toggle.pressed) {
                            dataStore.filterBy(function(record) {
                                if(record.data.lead_end) {
                                    return true;
                                } else {
                                    return false;
                                }
                            });
                        }
                        
                        if(!toggle.pressed) {
                            dataStore.reload();
                        }
                    },                    
                    pressed: false
                    
                },
                'Search:  ', ' ',                
                filter_combo_leadstate,
				' ',
                filter_combo_probability,                
                new Ext.Toolbar.Separator(),
                '->',
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
            displayMsg: 'Displaying leads {0} - {1} of {2}',
            emptyMsg: "No leads to display"
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
                '<p><b>Notes:</b> {lead_description}<br>',
                '<p><b>Activities:</b> </p>'
            )
        });
        
        var columnModel = new Ext.grid.ColumnModel([
            expander,
            {resizable: true, header: 'projekt ID', id: 'lead_id', dataIndex: 'lead_id', width: 20, hidden: true},
            {resizable: true, header: 'lead name', id: 'lead_name', dataIndex: 'lead_name', width: 200},
            {resizable: true, header: 'Partner', id: 'lead_partner', dataIndex: 'lead_partner', width: 150, sortable: false, renderer: function(_leadPartner) {
                if(typeof(_leadPartner == 'array') && _leadPartner[0]) {
                    return '<b>' + _leadPartner[0].org_name + '</b><br />' + _leadPartner[0].n_fileas;
                }
            }},
            {resizable: true, header: 'Customer', id: 'lead_customer', dataIndex: 'lead_customer', width: 150, sortable: false, renderer: function(_leadCustomer) {
                if(typeof(_leadCustomer == 'array') && _leadCustomer[0]) {
                    return '<b>' + _leadCustomer[0].org_name + '</b><br />' + _leadCustomer[0].n_fileas;
                }
            }},
            {resizable: true, 
              header: 'state', 
              id: 'lead_leadstate_id', 
              dataIndex: 'lead_leadstate_id', 
              sortable: false,
              renderer: function(leadstate_id) {
                  var leadstates = Ext.getCmp('filter_leadstate').store;
                  var leadstate_name = leadstates.getById(leadstate_id);
                  return leadstate_name.data.lead_leadstate;
              },
              width: 150},
            {resizable: true, header: 'probability', id: 'lead_probability', dataIndex: 'lead_probability', width: 50, renderer: Ext.util.Format.percentage},
            {resizable: true, header: 'turnover', id: 'lead_turnover', dataIndex: 'lead_turnover', width: 100, renderer: Ext.util.Format.euMoney }
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
            Egw.Egwbase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+record.data.lead_id, 900, 700);            
        });
       
       return;
    }
    

   var _loadData = function(_node)
    {
        var dataStore = Ext.getCmp('gridCrm').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.nodeType) {
            case 'sharedLeads':
                dataStore.baseParams.method = 'Crm.getSharedLeads';
                break;
                  
            case 'otherUsersLeads':
                dataStore.baseParams.method = 'Crm.getOtherPeopleLeads';
                break;

            case 'allLeads':
                dataStore.baseParams.method = 'Crm.getAllLeads';
                break;


            case 'userLeads':
                dataStore.baseParams.method = 'Crm.getLeadsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner;
                break;

            case 'singleFolder':
                dataStore.baseParams.method        = 'Crm.getLeadsByFolder';
                dataStore.baseParams.folderId = _node.attributes.folderId;
                break;
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50,
                dateFrom: '',// Ext.getCmp('Crm_dateFrom').getRawValue(),
                dateTo: '', //Ext.getCmp('Crm_dateTo').getRawValue()
                leadstate: Ext.getCmp('filter_leadstate').getValue(),
                probability: Ext.getCmp('filter_probability').getValue()																                
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

Ext.namespace('Egw.Crm.LeadEditDialog');

Egw.Crm.LeadEditDialog.Handler = function() {
    // public functions and variables
    return {
	    removeContact: function(_button, _event) 
	    {
	    	var currentContactsTab = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab();
	    	
            var selectedRows = currentContactsTab.getSelectionModel().getSelections();
	    	var currentContactsStore = currentContactsTab.getStore();

	        for (var i = 0; i < selectedRows.length; ++i) {
	            currentContactsStore.remove(selectedRows[i]);
	        }
	
	        //Ext.getCmp('Addressbook_Grants_SaveButton').enable();
	        //Ext.getCmp('Addressbook_Grants_ApplyButton').enable();
	    },

	    addContact: function(_button, _event) 
	    {
            Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
	    },    
	    
	    addContactToList: function(_button, _event) 
	    {
	    	var selectedRows = Ext.getCmp('crm_editLead_SearchContactsGrid').getSelectionModel().getSelections();
            var currentContactsStore = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getStore();

	    	for (var i = 0; i < selectedRows.length; ++i) {
	            if(currentContactsStore.getById(selectedRows[i].id) === undefined) {
	                //console.log('record ' + record.id + 'not found');
	                currentContactsStore.addSorted(selectedRows[i], selectedRows[i].id);
	            }
            }
	    	
            var selectionModel = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getSelectionModel();
            
            selectionModel.selectRow(currentContactsStore.indexOfId(selectedRows[0].id));
        }      
    }
}();

Egw.Crm.LeadEditDialog.Elements = function() {
    // public functions and variables
    return {
    	actionRemoveContact: new Ext.Action({
	        text: 'remove contact from list',
	        disabled: true,
	        handler: Egw.Crm.LeadEditDialog.Handler.removeContact,
	        iconCls: 'action_delete'
	    }),
        
        actionAddContact: new Ext.Action({
            text: 'create new contact',
            handler: Egw.Crm.LeadEditDialog.Handler.addContact,
            iconCls: 'action_add'
        }),

        actionAddContactToList: new Ext.Action({
            text: 'add contact to list',
            disabled: true,
            handler: function(_button, _event) {
            	Egw.Crm.LeadEditDialog.Handler.addContactToList(Ext.getCmp('crm_editLead_SearchContactsGrid'));
            },
            iconCls: 'action_add'
        }),
	    
    	columnModelDisplayContacts: new Ext.grid.ColumnModel([
            {id:'contact_id', header: "contact_id", dataIndex: 'contact_id', width: 25, sortable: true, hidden: true },
            {id:'n_fileas', header: 'Name / Address', dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                function(val, meta, record) {
                    var n_fileas           = record.data.n_fileas != null ? record.data.n_fileas : '';
                    var org_name           = record.data.org_name != null ? record.data.org_name : ' ';
                    var adr_one_street     = record.data.adr_one_street != null ? record.data.adr_one_street : ' ';
                    var adr_one_postalcode = record.data.adr_one_postalcode != null ? record.data.adr_one_postalcode : ' ';
                    var adr_one_locality   = record.data.adr_one_locality != null ? record.data.adr_one_locality : ' ' ;                                       
                    
                    
                    var formated_return = '<b>' + n_fileas + '</b><br />' + org_name + '<br  />' + 
                        adr_one_street + '<br />' + 
                        adr_one_postalcode + ' ' + adr_one_locality    ;                    
                    
                    return formated_return;
                }
            },
            {id:'contact_one', header: "Phone", dataIndex: 'adr_one_locality', width: 170, sortable: false, renderer: function(val, meta, record) {
                    var tel_work           = record.data.tel_work != null ? record.data.tel_work : ' ';
                    var tel_fax            = record.data.tel_fax != null ? record.data.tel_fax : ' ';
                    var tel_cell           = record.data.tel_cell != null ? record.data.tel_cell : ' '  ;                                      

                var formated_return = '<table>' + 
                    '<tr><td>Phone: </td><td>' + tel_work + '</td></tr>' + 
                    '<tr><td>Fax: </td><td>' + tel_fax + '</td></tr>' + 
                    '<tr><td>Cellphone: </td><td>' + tel_cell + '</td></tr>' + 
                    '</table>';
                
                    return formated_return;
                }
            },
            {id:'tel_work', header: "Internet", dataIndex: 'tel_work', width: 200, sortable: false, renderer: function(val, meta, record) {
                    var contact_email      = record.data.contact_email != null ? '<a href="mailto:'+record.data.contact_email+'">'+record.data.contact_email+'</a>' : ' ';
                    var contact_url        = record.data.contact_url != null ? record.data.contact_url : ' ';                    

                var formated_return = '<table>' + 
                    '<tr><td>Email: </td><td>' + contact_email + '</td></tr>' + 
                    '<tr><td>WWW: </td><td>' + contact_url + '</td></tr>' + 
                    '</table>';
                
                    return formated_return;
                }
            }                                    
        ]),
        
        columnModelSearchContacts: new Ext.grid.ColumnModel([
            {id:'n_fileas', header: 'Name', dataIndex: 'n_fileas', sortable: true, renderer: 
                function(val, meta, record) {
                	var formated_return = null;
                	
                	if(record.data.n_fileas != null) {
                		formated_return = '<b>' + record.data.n_fileas + '</b><br />';
                	}

                    if(record.data.org_name != null) {
                    	if(formated_return === null) {
                    		formated_return = '<b>' + record.data.org_name + '</b><br />';
                    	} else {
                            formated_return += record.data.org_name + '<br />';
                    	}
                    }

                    if(record.data.adr_one_street != null) {
                        formated_return += record.data.adr_one_street + '<br />';
                    }
                    
                    if(record.data.adr_one_postalcode != null && record.data.adr_one_locality  != null) {
                        formated_return += record.data.adr_one_postalcode + ' ' + record.data.adr_one_locality + '<br />';
                    } else if (record.data.adr_one_locality  != null) {
                    	formated_return += record.data.adr_one_locality + '<br />';
                    }
                	                    
                    return formated_return;
                }
            }
        ]),
    	
    	getTabPanelManageContacts: function() {
    		// why does this not work????
    		var quickSearchField = new Ext.app.SearchField({
            //var quickSearchField = new Ext.form.TriggerField({
            //var quickSearchField = new Ext.form.TwinTriggerField({
	            id: 'crm_editLead_SearchContactsField',
	            width: 250,
	            //autoWidth: true,
	            emptyText: 'enter searchfilter'
            });
			quickSearchField.on('resize', function(){
				quickSearchField.wrap.setWidth(280);
			})
            

            var contactToolbar = new Ext.Toolbar({
                items: [
                    quickSearchField
                ]
            });

    		var tabPanel = {
	            title:'manage contacts',
	            layout:'border',
                //tbar: contactToolbar,
	            //layoutOnTabChange:true,  
                defaults: {
                    //anchor: '100% 100%',
                    border:false
                    //frame:false
                    //deferredRender:false,                
	            },         
	            items:[{
	            	region: 'west',
	            	xtype: 'tabpanel',
	            	width: 300,
	            	split: true,
	            	activeTab: 0,
	            	tbar: [
                        Egw.Crm.LeadEditDialog.Elements.actionAddContactToList
	            	],
	            	items: [
                        {
                            xtype:'grid',
                            id: 'crm_editLead_SearchContactsGrid',
                            title:'Search',
                            cm: this.columnModelSearchContacts,
                            store: Egw.Crm.LeadEditDialog.Stores.getContactsSearch(),
                            autoExpandColumn: 'n_fileas',
                            tbar: contactToolbar
                        }, {
			               title: 'Browse',
			               html: 'Browse',
			               disabled: true
			            }
                        //searchPanel
	               ]
	            }, {
	            	region: 'center',
                    xtype: 'tabpanel',
                    id: 'crm_editLead_ListContactsTabPanel',
                    title:'contacts panel',
                    activeTab: 0,
	                tbar: [
	                    Egw.Crm.LeadEditDialog.Elements.actionAddContact,                
	                    Egw.Crm.LeadEditDialog.Elements.actionRemoveContact
	                ],                
                    items: [
                        {
                            xtype:'grid',
                            id: 'crm_gridCostumer',
                            title:'Customer',
                            cm: this.columnModelDisplayContacts,
                            store: Egw.Crm.LeadEditDialog.Stores.getContactsCustomer(),
                            autoExpandColumn: 'n_fileas'
                        },{
                            xtype:'grid',
                            id: 'crm_gridPartner',
                            title:'Partner',
                            cm: this.columnModelDisplayContacts,
                            store: Egw.Crm.LeadEditDialog.Stores.getContactsPartner(),
                            autoExpandColumn: 'n_fileas'
                        }, {
                            xtype:'grid',
                            id: 'crm_gridAccount',
                            title:'Internal',
                            cm: this.columnModelDisplayContacts,
                            store: Egw.Crm.LeadEditDialog.Stores.getContactsInternal(),
                            autoExpandColumn: 'n_fileas'
                        }
                    ]
                }]         
            }
            return tabPanel;
        }
    }
}();


Egw.Crm.LeadEditDialog.Stores = function() {
    var _storeContactsInternal = null;
    
    var _storeContactsCustomer = null;
    
    var _storeContactsPartner = null;

    var _storeContactsSearch = null;
    
    // public functions and variables
    return {
    	contactFields: [
            {name: 'link_id'},              
            {name: 'link_remark'},                        
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
            {name: 'adr_one_countryname'},
            {name: 'tel_work'},
            {name: 'tel_cell'},
            {name: 'tel_fax'},
            {name: 'contact_email'}
        ],
        
        getContactsCustomer: function (){
        	if(_storeContactsCustomer === null) {
	        	_storeContactsCustomer = new Ext.data.JsonStore({
	                data: formData.values.contactsCustomer ? formData.values.contactsCustomer : {},
		            autoLoad: formData.values.contactsCustomer ? true : false,
		            id: 'contact_id',
		            fields: this.contactFields
		        });
        	}
        	
	        return _storeContactsCustomer;
        },
        
        getContactsPartner: function (){
        	if(_storeContactsPartner === null) {
		        _storeContactsPartner = new Ext.data.JsonStore({
		            data: formData.values.contactsPartner ? formData.values.contactsPartner : {},
		            autoLoad: formData.values.contactsPartner ? true : false,
		            id: 'contact_id',
		            fields: this.contactFields
		        });
        	}
        	
	        return _storeContactsPartner;
        },
        
	    getContactsInternal: function (){
	    	if(_storeContactsInternal === null) {
		        _storeContactsInternal = new Ext.data.JsonStore({
		            data: formData.values.contactsInternal ? formData.values.contactsInternal : {},
		            autoLoad: formData.values.contactsInternal ? true : false,
		            id: 'contact_id',
		            fields: this.contactFields
		        });     
	    	}
	        
	        return _storeContactsInternal;
	    },

        getContactsSearch: function (){
        	if(_storeContactsSearch === null) {
			    _storeContactsSearch = new Ext.data.JsonStore({
		            baseParams: {
		                //method: 'Addressbook.getAllContacts',
		                start: 0,
		                sort: 'n_fileas',
		                dir: 'asc',
		                limit: 0
		            },
		            root: 'results',
		            totalProperty: 'totalcount',
		            id: 'contact_id',
		            fields: this.contactFields,
		            // turn on remote sorting
		            remoteSort: true
		        });
		        
		        _storeContactsSearch.setDefaultSort('n_fileas', 'asc');
        	}
        	
	        return _storeContactsSearch;
        },
        
        getLeadStatus: function (){
	        var store = new Ext.data.JsonStore({
	            data: formData.comboData.leadstates,
	            autoLoad: true,         
	            id: 'key',
	            fields: [
	                {name: 'key', mapping: 'lead_leadstate_id'},
	                {name: 'value', mapping: 'lead_leadstate'},
	                {name: 'probability', mapping: 'lead_leadstate_probability'},
	                {name: 'endslead', mapping: 'lead_leadstate_endslead'}
	            ]
	        });
	        
	        return store;
        },

        getProductsAvailable: function (){        
	        var store = new Ext.data.JsonStore({
	            data: formData.comboData.productsource,
	            autoLoad: true,
	            id: 'lead_productsource_id',
	            fields: [
	                {name: 'lead_productsource_id'},
	                {name: 'value', mapping: 'lead_productsource'},
	                {name: 'lead_productsource_price'}
	            ]
	        });
	        
	        return store;
        },

        getLeadType: function (){
	        var store = new Ext.data.JsonStore({
	            data: formData.comboData.leadtypes,
	            autoLoad: true,
	            id: 'key',
	            fields: [
	                {name: 'key', mapping: 'lead_leadtype_id'},
	                {name: 'value', mapping: 'lead_leadtype'}
	
	            ]
	        });     
	        
	        return store;
        },
        
        getActivities: function (){
	        var store = new Ext.data.SimpleStore({
	                fields: ['id','status','status2','datum','titel','message','responsible'],
	                data: [
	                        ['0','3','4','05.12.2007 15:30','der titel','Die Karl-Theodor-Brücke, besser bekannt als Alte Brücke, ist eine Brücke über den Neckar in Heidelberg. Sie verbindet die Altstadt mit dem gegenüberliegenden Neckarufer am östlichen Ende des Stadtteils Neuenheim. Die Alte Brücke wurde 1788 unter Kurfürst Karl Theodor als insgesamt neunte Brücke an dieser Stelle errichtet.','Meier,Heiner'],
	                        ['1','2','1','12.11.2007 07:10','der titel2','Erbaut wurde sie nach einem Vorschlag des Bauinspektors Mathias Mayer aus Stein auf den vorhandenen Pfeilern der Vorgängerbauten. Im Zusammenspiel des Flusstals, der Altstadt und des Schlosses prägt die Alte Brücke seit jeher das klassische Heidelberg-Panorama.','Schultze,Heinz'],
	                        ['2','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
	                        ['3','3','4','05.12.2007 15:30','der titel','Die Wirkung der Alten Brücke liegt dabei vor allem in der Einbettung in die Landschaft. Heute gehört sie zu den bekanntesten Sehenswürdigkeiten Heidelbergs.','Meier,Heiner'],
	                        ['4','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
	                        ['5','3','4','05.12.2007 15:30','der titel','die lange message','Meier,Heiner'],
	                        ['6','2','1','12.11.2007 07:10','der titel2','die lange message2','Schultze,Heinz'],
	                        ['7','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
	                        ['8','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner'],
	                        ['9','4','2','14.12.2007 18:40','der titel3','die lange message3','Meier,Heiner']
	                    ]
	        });
	        
	        return store;
        },
        
        getProbability: function (){
	        var store = new Ext.data.SimpleStore({
	                fields: ['key','value'],
	                data: [
	                        ['0','0%'],
	                        ['10','10%'],
	                        ['20','20%'],
	                        ['30','30%'],
	                        ['40','40%'],
	                        ['50','50%'],
	                        ['60','60%'],
	                        ['70','70%'],
	                        ['80','80%'],
	                        ['90','90%'],
	                        ['100','100%']
	                    ]
	        });
	        
	        return store;
        }
    }
}();

Egw.Crm.LeadEditDialog.Main = function() {
    // private variables
    var dialog;
    var leadedit;
    
    var _getAdditionalData = function()
    {
        var additionalData = {};

        if(formData.values.lead_id) {
            additionalData.lead_id = formData.values.lead_id;
        }   
        
        var store_products         = Ext.getCmp('grid_choosenProducts').getStore();
        additionalData.products = Egw.Egwbase.Common.getJSONdata(store_products);

        // the start date (can not be empty
        var startDate = Ext.getCmp('_lead_start').getValue();
        additionalData.lead_start = startDate.format('c');

        // the end date
        var endDate = Ext.getCmp('_lead_end').getValue();
        if(typeof endDate == 'object') {
            additionalData.lead_end = endDate.format('c');
        } else {
            additionalData.lead_end = null;
        }

        // the estimated end
        var endScheduledDate = Ext.getCmp('_lead_end_scheduled').getValue();
        if(typeof endScheduledDate == 'object') {
            additionalData.lead_end_scheduled = endScheduledDate.format('c');
        } else {
            additionalData.lead_end_scheduled = null;
        }
        
        
        // collect data of assosicated contacts
        var linksContactsCustomer = new Array();
        var storeContactsCustomer = Egw.Crm.LeadEditDialog.Stores.getContactsCustomer();
        
        storeContactsCustomer.each(function(record) {
            linksContactsCustomer.push(record.id);          
        });
        
        additionalData.linkedCustomer = Ext.util.JSON.encode(linksContactsCustomer);
        

        var linksContactsPartner = new Array();
        var storeContactsPartner = Egw.Crm.LeadEditDialog.Stores.getContactsPartner();
        
        storeContactsPartner.each(function(record) {
            linksContactsPartner.push(record.id);          
        });
        
        additionalData.linkedPartner = Ext.util.JSON.encode(linksContactsPartner);


        var linksContactsInternal = new Array();
        var storeContactsInternal = Egw.Crm.LeadEditDialog.Stores.getContactsInternal();
        
        storeContactsInternal.each(function(record) {
            linksContactsInternal.push(record.id);          
        });
        
        additionalData.linkedAccount = Ext.util.JSON.encode(linksContactsInternal);
        
        return additionalData;
    }

    // private functions 
    var handler_applyChanges = function(_button, _event) 
    {
        //var grid_products          = Ext.getCmp('grid_choosenProducts');
       
    	var leadForm = Ext.getCmp('leadDialog').getForm();
    	
    	if(leadForm.isValid()) {            
			leadForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving lead...',
    			params:_getAdditionalData(),
    			success:function(form, action, o) {
                    //store_products.reload();
                    //store_products.rejectChanges();
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
    	var leadForm = Ext.getCmp('leadDialog').getForm();
    	
    	if(leadForm.isValid()) {
			leadForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving lead...',
    			params:_getAdditionalData(),
    			success:function(form, action, o) {
                    //store_products.reload();       
                    //store_products.rejectChanges();                            
    				window.opener.Egw.Crm.reload();
    				window.setTimeout("window.close()", 500);
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
            title: 'Delete Lead?',
            msg: 'Are you sure you want to delete this lead?',
            buttons: Ext.MessageBox.YESNO,
            fn: handler_delete,
//            animEl: 'mb4',
            icon: Ext.MessageBox.QUESTION
        });
    }


    var handler_delete = function(btn) 
    {
        if (btn == 'yes') {
            var leadIds = Ext.util.JSON.encode([formData.values.lead_id]);
            
            Ext.Ajax.request({
                params: {
                    method: 'Crm.deleteLeads',
                    _leadIds: leadIds
                },
                text: 'Deleting lead...',
                success: function(_result, _request){
                    window.opener.Egw.Crm.reload();
                    window.setTimeout("window.close()", 400);
                },
                failure: function(result, request){
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the lead.');
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
		text: 'delete lead',
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
        
        var leadToolbar = new Ext.Toolbar({
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
          
        var txtfld_leadName = new Ext.form.TextField({
            hideLabel: true,
            id: 'lead_name',
            //fieldLabel:'Projektname', 
            emptyText: 'enter short name',
            name:'lead_name',
            allowBlank: false,
            selectOnFocus: true,
            anchor:'100%'
            //selectOnFocus:true            
        }); 
 
 
        var combo_leadstatus = new Ext.form.ComboBox({
                fieldLabel:'leadstate', 
                id:'leadstatus',
                name:'leadstate',
                hiddenName:'lead_leadstate_id',
				store: Egw.Crm.LeadEditDialog.Stores.getLeadStatus(),
				displayField:'value',
                valueField:'key',
				//typeAhead: true,
				mode: 'local',
				triggerAction: 'all',
				editable: false,
				allowBlank: false,
                listWidth: '25%',
				forceSelection: true,
				anchor:'95%'    
        });
	
		combo_leadstatus.on('select', function(combo, record, index) {
            if (record.data.probability !== null) {
                var combo_probability = Ext.getCmp('combo_probability');
                combo_probability.setValue(record.data.probability);
            }

			if (record.data.endslead == '1') {
				var combo_endDate = Ext.getCmp('_lead_end');
				combo_endDate.setValue(new Date());
			}
		});
		
        var combo_leadtyp = new Ext.form.ComboBox({
            fieldLabel:'leadtype', 
            id:'leadtype',
            name:'lead_leadtyp',
            hiddenName:'lead_leadtype_id',
            store: Egw.Crm.LeadEditDialog.Stores.getLeadType(),
            mode: 'local',
            displayField:'value',
            valueField:'key',
            typeAhead: true,
            triggerAction: 'all',
            listWidth: '25%',                
            editable: false,
            allowBlank: false,
            forceSelection: true,
            anchor:'95%'    
        });
        combo_leadtyp.setValue('1');
    

        var st_leadsource = new Ext.data.JsonStore({
			data: formData.comboData.leadsources,
			autoLoad: true,
            id: 'key',
            fields: [
                {name: 'key', mapping: 'lead_leadsource_id'},
                {name: 'value', mapping: 'lead_leadsource'}

            ]
        }); 	

		var combo_leadsource = new Ext.form.ComboBox({
                fieldLabel:'leadsource', 
                id:'leadsource',
                name:'lead_leadsource',
                hiddenName:'lead_leadsource_id',
				store: st_leadsource,
				displayField:'value',
                valueField:'key',
				typeAhead: true,
                listWidth: '25%',                
				mode: 'local',
				triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
				anchor:'95%'    
        });
		combo_leadsource.setValue('1');
        
        var st_activities = Egw.Crm.LeadEditDialog.Stores.getActivities();
     
	 	var combo_probability =  new Ext.form.ComboBox({
			fieldLabel:'probability', 
			id: 'combo_probability',
			name:'lead_probability',
			store: Egw.Crm.LeadEditDialog.Stores.getProbability(),
			displayField:'value',
			valueField:'key',
			typeAhead: true,
			mode: 'local',
            listWidth: '25%',            
			triggerAction: 'all',
			emptyText:'',
			selectOnFocus:true,
			editable: false,
			renderer: Ext.util.Format.percentage,
			anchor:'95%'			
		});
		combo_probability.setValue('0');
	         
		var date_start = new Ext.form.DateField({
	        fieldLabel:'start', 
	        allowBlank:false,
	        id:'_lead_start',
	        //       format:formData.config.dateFormat, 
	        format: 'd.m.Y',
	        /*altFormat:'Y-m-d',
	        altFormat:'c',*/
	        anchor:'95%'
		});
		
		var date_scheduledEnd = new Ext.form.DateField({
            fieldLabel:'estimated end', 
            //name:'lead_end_scheduled',
            id:'_lead_end_scheduled',
            //    format:formData.config.dateFormat, 
            format: 'd.m.Y',
            //altFormat:'Y-m-d',
            anchor:'95%'
		});
		
		var date_end = new Ext.form.DateField({
            xtype:'datefield',
            fieldLabel:'end', 
            //name:'lead_end',
            id:'_lead_end',
            //       format:formData.config.dateFormat, 
            format: 'd.m.Y',
            altFormat:'Y-m-d',
            anchor:'95%'
		});
		

      var ActivitiesTpl = new Ext.XTemplate( 
            '<tpl for=".">',
            '<div class="activities-item-small">',
            // {status} {status2} 
            '<i>{datum} {responsible}</i><br />', 
            '<b>{titel}</b><br />',
            '{message}<br />',                     
            '</div></tpl>', {
                isNotEmpty: function(textValue){
                    if ((textValue === null) || (textValue.length == 0)) {
                        return '';
                    }
                    else {
                        return textValue+'<br />';
                    }
                }                                                
        });    
        
        var activities_limited = new Ext.Panel({
            title: 'last 10 activities',
            id: 'grid_activities_limited_panel',
            cls: 'contacts_background',                            
            layout:'fit',  
	        autoScroll: true,
            autoHeight: true,
	        items: new Ext.DataView({
	            tpl: ActivitiesTpl,       
                autoHeight:true,                         
                id: 'grid_activities_limited',
	            store: st_activities,
                overClass: 'x-view-over',
	            itemSelector: 'activities-item-small'
	        })
        });  
  
  
	   if (formData.values) {
			var _lead_id = formData.values.lead_id;
	   } else {
			var _lead_id = 'NULL';
	   }
  
        var st_choosenProducts = new Ext.data.JsonStore({
			data: formData.values.products,
			autoLoad: true,
            id: 'lead_id',
            fields: [
                {name: 'lead_id'},
                {name: 'lead_product_id'},
                {name: 'lead_product_desc'},
                {name: 'lead_product_price'}
            ]
        });

        var st_productsAvailable = Egw.Crm.LeadEditDialog.Stores.getProductsAvailable(); 
        
        var cm_choosenProducts = new Ext.grid.ColumnModel([{
                header: "Produkt",
                dataIndex: 'lead_product_id',
                width: 300,
                editor: new Ext.form.ComboBox({
                    name: 'product_combo',
                    id: 'product_combo',
                    hiddenName: 'lead_productsource_id', //lead_product_id',
                    store: st_productsAvailable, 
                    displayField:'value', 
                    valueField: 'lead_productsource_id',
                    allowBlank: false, 
                    editable: false,
                    selectOnFocus:true,
                    forceSelection: true, 
                    triggerAction: "all", 
                    mode: 'local', 
                    lazyRender:true,
                    listClass: 'x-combo-list-small'
                    }),
                renderer: function(data){
                    record = st_productsAvailable.getById(data);
                    if (record) {
                        return record.data.value;
                    }
                    else {
                        Ext.getCmp('leadDialog').doLayout();
                        return data;
                    }
                  }
                } , { 
                header: "Seriennummer",
                dataIndex: 'lead_product_desc',
                width: 300,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                } , {
                header: "Preis",
                dataIndex: 'lead_product_price',
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
       
       var product_combo = Ext.getCmp('product_combo');
       product_combo.on('change', function(field, value) {
          console.log(field);
    
       });
        
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
           {name: 'lead_id', type: 'int'},
           {name: 'lead_lead_id', type: 'int'},
           {name: 'lead_product_id', type: 'int'},
           {name: 'lead_product_desc', type: 'string'},
           {name: 'lead_product_price', type: 'float'}
        ]);
        
        var grid_choosenProducts = new Ext.grid.EditorGridPanel({
            store: st_choosenProducts,
            id: 'grid_choosenProducts',
            cm: cm_choosenProducts,
            mode: 'local',
            sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
            anchor: '100% 100%',
//            autoExpandColumn:'common',
            frame: false,
            clicksToEdit:2,
            tbar: [{
                text: 'add product',
                iconCls: 'action_add',
                handler : function(){
                    var p = new product({
                        lead_id: 'NULL',
						lead_lead_id: _lead_id,
                        lead_product_id: '',                       
                        lead_product_desc:'',
                        lead_product_price: ''
                    });
                    grid_choosenProducts.stopEditing();
                    st_choosenProducts.insert(0, p);
                    grid_choosenProducts.startEditing(0, 0);
                }
            } , {
                text: 'delete product',
                iconCls: 'action_delete',
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

        var storeContactsCustomer = Egw.Crm.LeadEditDialog.Stores.getContactsCustomer();
             
        var storeContactsPartner = Egw.Crm.LeadEditDialog.Stores.getContactsPartner();
             
        var storeContactsInternal = Egw.Crm.LeadEditDialog.Stores.getContactsInternal();     
     
        var store_contactSearch = Egw.Crm.LeadEditDialog.Stores.getContactsSearch();   
 

        var cm_contacts = new Ext.grid.ColumnModel([
            	{id:'contact_id', header: "contact_id", dataIndex: 'contact_id', width: 25, sortable: true, hidden: true },
        //    	{id:'link_remark', header: "link_remark", dataIndex: 'link_remark', width: 50, sortable: true },
                {id:'n_fileas', header: 'Name', dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                    function(val, meta, record) {
	                	var org_name = record.data.org_name != null ? record.data.org_name : ' '
	                	
	                    var formated_return = '<b>' + record.data.n_fileas + '</b><br />' + org_name;
                        
                        return formated_return;
                    }
                },
                {id:'contact_one', header: "Address", dataIndex: 'adr_one_locality', width: 170, sortable: false, renderer: function(val, meta, record) {
                    var formated_return =  
                        record.data.adr_one_street + '<br />' + 
                        record.data.adr_one_postalcode + ' ' + record.data.adr_one_locality;
                    
                        return formated_return;
                    }
                },
                {id:'tel_work', header: "Contactdata", dataIndex: 'tel_work', width: 200, sortable: false, renderer: function(val, meta, record) {
                    var formated_return = '<table>' + 
                        '<tr><td>Phone: </td><td>' + record.data.tel_work + '</td></tr>' + 
                        '<tr><td>Cellphone: </td><td>' + record.data.tel_cell + '</td></tr>' + 
                        '</table>';
                    
                        return formated_return;
                    }
                }                                    
        ]);
               
  		var folderTrigger = new Ext.form.TriggerField({
            fieldLabel:'folder (person in charge)', 
			id: 'lead_container_name',
            anchor:'95%',
            allowBlank: false,
            editable: false,
            readOnly:true
        });

        folderTrigger.onTriggerClick = function() {
            Egw.Crm.displayFolderSelectDialog('lead_container');
        };
        
        var ProductSummaryTpl = new Ext.XTemplate( 
            '<tpl for=".">',
            '<div class="productSummary-item-small">',
            // {productSummary}
            '1x InEOS, 2x InFinident, 1x PC', 
            '</div></tpl>');    
        
        var tabPanelOverview = {
            title:'overview',
            layout:'border',
            layoutOnTabChange:true,
            //deferredRender:false,
            //anchor:'100% 100%',
            defaults: {
                //bodyStyle:'padding:20px',
                //anchor: '100% 100%',
                border: true,
                frame: true
                //deferredRender:false,                
            },
            items: [{
		        title: 'Last 10 activities',
		        region: 'east',
                autoScroll: true,
		        width: 300,
              //  layout: 'fit',
		      //  split: true,
		        //margins: '0 5 5 5'
		        items: [
		          new Ext.DataView({
                    tpl: ActivitiesTpl,       
                    autoHeight:true,                    
                    id: 'grid_activities_limited',
                    store: st_activities,
                    overClass: 'x-view-over',
                    itemSelector: 'activities-item-small'
                  })
                ]
		    },{
		        region:'center',
		        layout: 'form',
                autoHeight: true,
                id: 'editCenterPanel',
		        //margins: '5 5 0 0'
                items: [
                    txtfld_leadName
                , {
                    xtype:'textarea',
                    //fieldLabel:'Notizen',
                    id: 'lead_notes',
                    hideLabel: true,
                    name:'lead_description',
                    height: 120,
                    anchor:'100%',
                    emptyText: 'enter description'
                }, {
                    layout:'column',
                    height: 150,
                    id: 'lead_combos',
                    anchor:'100%',                        
                    items: [{
                        columnWidth: .33,
                        //anchor:'100%',
                        items:[{
                            layout: 'form',
                            items: [
                                combo_leadstatus, 
                                combo_leadtyp,
                                combo_leadsource
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
                                fieldLabel:'expected turnover', 
                                name:'lead_turnover',
                                selectOnFocus: true,
                                anchor:'95%'
                            },  
                                combo_probability,
                                folderTrigger 
                            ]
                        }]              
                    },{
                        columnWidth: .33,
                        items:[{
                            layout: 'form',
                            border:false,
                            items: [
                                date_start,
                                date_scheduledEnd,
                                date_end   
                            ]
                        }]
                    },{
                        xtype: 'hidden',
                        name: 'lead_container',
                        id: 'lead_container'
                    }]
                }, {
                    xtype: 'tabpanel',
                    id: 'contactsPanel',
                    title:'contacts panel',
                    activeTab: 0,
                    height: 307,
                    items: [
	                    {
		                    xtype:'grid',
		                    //id: 'crm_gridCostumer',
		                    title:'Customer',
		                    cm: cm_contacts,
		                    store: storeContactsCustomer,
		                    autoExpandColumn: 'n_fileas'
		                },{
                            xtype:'grid',
                            //id: 'crm_gridPartner',
                            title:'Partner',
                            cm: cm_contacts,
                            store: storeContactsPartner,
                            autoExpandColumn: 'n_fileas'
                        }, {
                            xtype:'grid',
                            //id: 'crm_gridAccount',
                            title:'Internal',
                            cm: cm_contacts,
                            store: storeContactsInternal,
                            autoExpandColumn: 'n_fileas'
                        }
                    ]
                }]
            }]
        };        

        var tabPanelActivities = {
            title:'manage activities',
            layout:'form',
            disabled: true,
            layoutOnTabChange:true,            
            deferredRender:false,
            border:false,
            items:[{  
            }]
        };
        
        var tabPanelProducts = {
            title:'manage products',
            layout:'form',
            layoutOnTabChange:true,            
            deferredRender:false,
            anchor:'100% 100%',
            border:false,
            items:[//{  
                //xtype:'fieldset',
                //title:'selected products',
                //anchor:'100% 100%',
                //items: [
                    grid_choosenProducts
                //]
            //}
            ]
        };
  
		var leadedit = new Ext.FormPanel({
			baseParams: {method :'Crm.saveLead'},
		    labelAlign: 'top',
			bodyStyle:'padding:5px',
            anchor:'100%',
			region: 'center',
            id: 'leadDialog',
			tbar: leadToolbar, 
			deferredRender: false,
            layoutOnTabChange:true,            
            items: [{
                xtype:'tabpanel',
	            plain:true,
	            activeTab: 0,
				deferredRender:false,
                layoutOnTabChange:true,  
                anchor:'100% 100%',
	            //defaults:{bodyStyle:'padding:10px'},
	            items:[
                    tabPanelOverview, 
                    Egw.Crm.LeadEditDialog.Elements.getTabPanelManageContacts(),                    
                    tabPanelActivities, 
                    tabPanelProducts
                ]
            }]
        });
        
		var viewport = new Ext.Viewport({
			layout: 'border',
            id: 'editViewport',
			items: leadedit
		});

        Ext.getCmp('editViewport').on('afterlayout',function(container) {
             var _dimension = container.getSize();
             var _offset = 100;
             if(Ext.isIE7) {
                 var _offset = 117;
             }
             var _heightContacts = _dimension.height - Ext.getCmp('lead_name').getSize().height 
                                                     - Ext.getCmp('lead_notes').getSize().height
                                                     - Ext.getCmp('lead_combos').getSize().height
                                                     - _offset;

             Ext.getCmp('contactsPanel').setHeight(_heightContacts);
        }); 

		
		/*********************************
		 * 
		 * the UI logic
		 * 
		 *********************************/
		var searchContacts = function(_field, _newValue, _oldValue) {
            var currentContactsTabId = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getId();
            //console.log(currentContactsTabId);
            if(currentContactsTabId == 'crm_gridAccount') {
                var method = 'Addressbook.getAccounts';
            } else {
                var method = 'Addressbook.getAllContacts';
            }
            
            if(_newValue == '') {
                //Ext.getCmp('crm_editLead_SearchContactsGrid').getStore().removeAll();
                Egw.Crm.LeadEditDialog.Stores.getContactsSearch().removeAll();
            } else {
                //Ext.getCmp('crm_editLead_SearchContactsGrid').getStore().load({
                Egw.Crm.LeadEditDialog.Stores.getContactsSearch().load({
                    params: {
                        start: 0,
                        limit: 50,
                        filter: _newValue,
                        method: method
                    }
                });
            }
        };

        Ext.getCmp('crm_gridCostumer').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
        	var record = _grid.getStore().getAt(_rowIndex);
            Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
        });
        
        Ext.getCmp('crm_gridPartner').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
        	var record = _grid.getStore().getAt(_rowIndex);
            Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
        });
        
        Ext.getCmp('crm_gridAccount').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
        	var record = _grid.getStore().getAt(_rowIndex);
            Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
        });                
         
		Ext.getCmp('crm_editLead_SearchContactsField').on('change', searchContacts);
		    
        Ext.getCmp('crm_editLead_SearchContactsGrid').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
            var record = _grid.getStore().getAt(_rowIndex);
            var currentContactsStore = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getStore();
            
            if(currentContactsStore.getById(record.id) === undefined) {
                //console.log('record ' + record.id + 'not found');
                currentContactsStore.addSorted(record, record.id);
            }
            
            var selectionModel = Ext.getCmp('crm_editLead_ListContactsTabPanel').getActiveTab().getSelectionModel();
            
            selectionModel.selectRow(currentContactsStore.indexOfId(record.id));
        });

        var setRemoveContactButtonState = function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                Egw.Crm.LeadEditDialog.Elements.actionRemoveContact.setDisabled(true);
            } else {
                // at least one row selected
                Egw.Crm.LeadEditDialog.Elements.actionRemoveContact.setDisabled(false);
            }
        }; 
        
        var activateContactsSearch = function(_panel) {
        	setRemoveContactButtonState(_panel.getSelectionModel());
        	searchContacts(
        	   Ext.getCmp('crm_editLead_SearchContactsField'), 
                Ext.getCmp('crm_editLead_SearchContactsField').getValue(),
                Ext.getCmp('crm_editLead_SearchContactsField').getValue()
            );
        };
        
        Ext.getCmp('crm_gridCostumer').getSelectionModel().on('selectionchange', setRemoveContactButtonState);
        Ext.getCmp('crm_gridPartner').getSelectionModel().on('selectionchange', setRemoveContactButtonState);
        Ext.getCmp('crm_gridAccount').getSelectionModel().on('selectionchange', setRemoveContactButtonState);

        Ext.getCmp('crm_gridCostumer').on('activate', activateContactsSearch);
        Ext.getCmp('crm_gridPartner').on('activate', activateContactsSearch);
        Ext.getCmp('crm_gridAccount').on('activate', activateContactsSearch);
        

        var setAddContactButtonState = function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                Egw.Crm.LeadEditDialog.Elements.actionAddContactToList.setDisabled(true);
            } else {
                // at least one row selected
                Egw.Crm.LeadEditDialog.Elements.actionAddContactToList.setDisabled(false);
            }
        }; 
        
        Ext.getCmp('crm_editLead_SearchContactsGrid').getSelectionModel().on('selectionchange', setAddContactButtonState);
        
    }

    var setLeadDialogValues = function(_formData) {        
    	var form = Ext.getCmp('leadDialog').getForm();

        var myReader = new Ext.data.JsonStore({
        	root: 'rows',
        	fields:[
	            {name: 'lead_id', type: 'int'},
	            {name: 'lead_name', type: 'string'},
	            {name: 'lead_leadstate_id', type: 'int'},
	            {name: 'lead_leadtype_id', type: 'int'},
	            {name: 'lead_leadsource_id', type: 'int'},
	            {name: 'lead_container', type: 'int'},
	            {name: '_lead_start', mapping: 'lead_start', type: 'date', dateFormat: 'c'},
	            {name: 'lead_description', type: 'string'},
	            {name: '_lead_end', mapping: 'lead_end', type: 'date', dateFormat: 'c'},
	            {name: 'lead_turnover', type: 'int'},
	            {name: 'lead_probability', type: 'int'},
	            {name: '_lead_end_scheduled', mapping: 'lead_end_scheduled', type: 'date', dateFormat: 'c'}
            ] 
        });
        
        myReader.loadData({'rows': [_formData.values]});
        
        var leadRecord = myReader.getAt(0);
        
        if(typeof(leadRecord.data._lead_start) != 'object') {
        	leadRecord.data._lead_start = new Date();
        };
        
    	form.setValues(leadRecord.data);

        form.findField('lead_container_name').setValue(_formData.config.folderName);
        
        if (formData.values.lead_id > 0) {
            action_applyChanges.enable();
            action_delete.enable();
        }
    	
    	return;
    }

    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                setLeadDialogValues(formData);
            }
        }
        
    }
    
}(); // end of application
