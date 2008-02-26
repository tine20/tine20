Ext.namespace('Egw.Crm');


Egw.Crm.getPanel = function(){
    return Egw.Crm.Tree.getPanel();
};

Egw.Crm.Tree = function() {

    var _treeNodeContextMenu = null;

    var _handlerAddFolder = function(_button, _event) {
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

    var _handlerRenameFolder = function(_button, _event) {
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

    var _handlerDeleteFolder = function(_button, _event) {
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
     
   var _actionAddFolder = new Ext.Action({
        text: 'add folder',
        handler: _handlerAddFolder
    });

    var _actionDeleteFolder = new Ext.Action({
        text: 'delete folder',
        iconCls: 'actionDelete',
        handler: _handlerDeleteFolder
    });

    var _actionRenameFolder = new Ext.Action({
        text: 'rename folder',
        iconCls: 'actionRename',
        handler: _handlerRenameFolder
    });

    var _actionPermisionsFolder = new Ext.Action({
        disabled: true,
        text: 'permissions',
        handler: _handlerDeleteFolder
    });


    var _contextMenuUserFolder = new Ext.menu.Menu({
        items: [
            _actionAddFolder
        ]
    });
    
    var _contextMenuSingleFolder= new Ext.menu.Menu({
        items: [
            _actionRenameFolder,
            _actionDeleteFolder,
            _actionPermisionsFolder
        ]
    });


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
            owner: Egw.Egwbase.Registry.get('currentAccount').accountId
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
            id: 'crmTree',
            iconCls: 'crmThumbnailApplication',
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
            Egw.Crm.Main.show(_node);
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
        
      return {
         getPanel: _getTreePanel,
         initialTree: _initialTree
     }
}(); // end of application   
    

Ext.namespace('Egw.Crm.Main');
Egw.Crm.Main = function(){

    var handler = {
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Egw.Egwbase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId=' + _rowIndex[0].id, 900, 700);
        },
        
        handlerDelete: function(){
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete this lead?', function(_button) {
                if(_button == 'yes') {            
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
            });
        },
        
        handlerAddTask: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            
            popupWindow = new Egw.Tasks.EditPopup({
                relatedApp: 'crm',
                relatedId: _rowIndex[0].id
            });
        }    
    };
    
    var actions = {
        actionAdd: new Ext.Action({
            text: 'add lead',
            iconCls: 'actionAdd',
            handler: function(){
                //     var tree = Ext.getCmp('venues-tree');
                //      var curSelNode = tree.getSelectionModel().getSelectedNode();
                //  var RootNode   = tree.getRootNode();
                
                Egw.Egwbase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 900, 700);
            }
        }),
        
        actionEdit: new Ext.Action({
            text: 'edit lead',
            disabled: true,
            handler: handler.handlerEdit,
            iconCls: 'actionEdit'
        }),
        
        actionDelete: new Ext.Action({
            text: 'delete lead',
            disabled: true,
            handler: handler.handlerDelete,
            iconCls: 'actionDelete'
        }),
        
        actionAddTask: new Ext.Action({
            text: 'add task',
            handler: handler.handlerAddTask,
            iconCls: 'actionAddTask',
            disabled: true,
            scope: this
        })
    };


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
                title: 'Crm',
                id: 'crmTree',
                iconCls: 'crmThumbnailApplication',
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
            for(var i=0; i< Egw.Crm.Tree.initialTree.length; i++) {
                treeRoot.appendChild(new Ext.tree.AsyncTreeNode(Egw.Crm.Tree.initialTree[i]));
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

    
    var _createDataStore = function()
    {

         var storeCrm = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadsByOwner',
                owner: 'all'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'lead_id',
            fields: Egw.Crm.Model.Lead,
            // turn on remote sorting
            remoteSort: true
        });
     
   
        storeCrm.setDefaultSort('lead_name', 'asc');

        storeCrm.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter           = Ext.getCmp('quickSearchField').getRawValue();
            _dataSource.baseParams.leadstate        = Ext.getCmp('filterLeadstate').getValue();
            _dataSource.baseParams.probability      = Ext.getCmp('filterProbability').getValue();
            _dataSource.baseParams.getClosedLeads   = Ext.getCmp('crmShowClosedLeadsButton').pressed;
        });        
        
        storeCrm.load({params:{start:0, limit:50}});
        
        return storeCrm;
    };


    var _showCrmToolbar = function(){
    
        var storeProbability = new Ext.data.SimpleStore({
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
        
        var storeProbabilityWithNone = new Ext.data.SimpleStore({
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
    
       var storeLeadstate = new Ext.data.JsonStore({
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
            
            storeLeadstate.load();    
    
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width: 200,
            emptyText: 'enter searchfilter'
        });
        quickSearchField.on('change', function(){
            Ext.getCmp('gridCrm').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                    //leadstate: Ext.getCmp('filterLeadstate').getValue(),
                    //probability: Ext.getCmp('filterProbability').getValue()                   
                }
            });
        });

            
       var filterComboLeadstate = new Ext.ux.ClearableComboBox({
            fieldLabel:'Leadstate', 
            id:'filterLeadstate',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            blankText: 'leadstate...',
            hiddenName:'lead_leadstate_id',
            store: storeLeadstate,
            displayField:'lead_leadstate',
            valueField:'lead_leadstate_id',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            emptyText:'leadstate...',
            selectOnFocus:true,
            editable: false 
       });          
       filterComboLeadstate.on('select', function(combo, record, index) {
           if (!record.data) {
               var _leadstate = '';       
           } else {
               var _leadstate = record.data.lead_leadstate_id;
           }
           
           combo.triggers[0].show();
           
           Ext.getCmp('gridCrm').getStore().load({
               params: {                
                start: 0,
                limit: 50,
                leadstate: _leadstate,
                probability: Ext.getCmp('filterProbability').getValue()
                }
            });
       });
      
       var filterComboProbability = new Ext.ux.ClearableComboBox({
            fieldLabel:'probability', 
            id: 'filterProbability',
            name:'lead_probability',
            hideLabel: true,            
            store: storeProbability,
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
       filterComboProbability.on('select', function(combo, record, index) {
           if (!record.data) {
               var _probability = '';       
           } else {
               var _probability = record.data.key;
           }           
           
           combo.triggers[0].show();           
           
           Ext.getCmp('gridCrm').getStore().load({
                params: {                    
                    start: 0,
                    limit: 50
                    //leadstate: Ext.getCmp('filterLeadstate').getValue(),
                    //probability: _probability
                }
            });         
       });      
        

       function editLeadstate() {
           var Dialog = new Ext.Window({
                title: 'Leadstates',
                id: 'leadstateWindow',
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
            
            var storeLeadstate = new Ext.data.JsonStore({
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
            
            storeLeadstate.load();
            
           var checkColumn = new Ext.ux.grid.CheckColumn({
               header: "X Lead?",
               dataIndex: 'lead_leadstate_endslead',
               width: 50
            });
            
            var columnModelLeadstate = new Ext.grid.ColumnModel([
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
                        store: storeProbabilityWithNone, 
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
            
            var handlerLeadstateAdd = function(){
                var p = new entry({
                    lead_leadstate_id: null,
                    lead_leadstate: '',
                    lead_leadstate_probability: null,
                    lead_leadstate_endslead: false
                });
                leadstateGridPanel.stopEditing();
                storeLeadstate.insert(0, p);
                leadstateGridPanel.startEditing(0, 0);
                leadstateGridPanel.fireEvent('celldblclick',this, 0, 1);
            };
                        
            var handlerLeadstateDelete = function(){
                var leadstateGrid  = Ext.getCmp('editLeadstateGrid');
                var leadstateStore = leadstateGrid.getStore();
                
                var selectedRows = leadstateGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadstateStore.remove(selectedRows[i]);
                }   
            };                        
                        
          
           var handlerLeadstateSaveClose = function(){
                var leadstateStore = Ext.getCmp('editLeadstateGrid').getStore();
                var leadstateJson = Egw.Egwbase.Common.getJSONdata(leadstateStore); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadstates',
                                optionsData: leadstateJson
                            },
                            text: 'Saving leadstates...',
                            success: function(_result, _request){
                                    leadstateStore.reload();
                                    leadstateStore.rejectChanges();
                                    Ext.getCmp('filterLeadstate').store.reload();
                                    
                               },
                            failure: function(form, action) {
                                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                                }
                        });          
            };          
            
            var leadstateGridPanel = new Ext.grid.EditorGridPanel({
                store: storeLeadstate,
                id: 'editLeadstateGrid',
                cm: columnModelLeadstate,
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
                    iconCls: 'actionAdd',
                    handler : handlerLeadstateAdd
                    },{
                    text: 'delete item',
                    iconCls: 'actionDelete',
                    handler : handlerLeadstateDelete
                    },{
                    text: 'save',
                    iconCls: 'actionSaveAndClose',
                    handler : handlerLeadstateSaveClose 
                    }]  
                });

          Dialog.add(leadstateGridPanel);
          Dialog.show();          
        }

      function editLeadsource() {
           var Dialog = new Ext.Window({
                title: 'Leadsources',
                id: 'leadsourceWindow',
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
            
            var storeLeadsource = new Ext.data.JsonStore({
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
            
            storeLeadsource.load();
            
            var columnModelLeadsource = new Ext.grid.ColumnModel([
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
            
            var handlerLeadsourceAdd = function(){
                var p = new entry({
                    lead_leadsource_id: 'NULL',
                    lead_leadsource: ''
                });
                leadsourceGridPanel.stopEditing();
                storeLeadsource.insert(0, p);
                leadsourceGridPanel.startEditing(0, 0);
                leadsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
            };
                        
            var handlerLeadsourceDelete = function(){
                var leadsourceGrid  = Ext.getCmp('editLeadsourceGrid');
                var leadsourceStore = leadsourceGrid.getStore();
                
                var selectedRows = leadsourceGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadsourceStore.remove(selectedRows[i]);
                }   
            };                        
                        
          
           var handlerLeadsourceSaveClose = function(){
                var leadsourceStore = Ext.getCmp('editLeadsourceGrid').getStore();
                
                var leadsourceJson = Egw.Egwbase.Common.getJSONdata(leadsourceStore); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadsources',
                                optionsData: leadsourceJson
                            },
                            text: 'Saving leadsources...',
                            success: function(_result, _request){
                                    leadsourceStore.reload();
                                    leadsourceStore.rejectChanges();
                               },
                            failure: function(form, action) {
                                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                                }
                        });          
            };          
            
            var leadsourceGridPanel = new Ext.grid.EditorGridPanel({
                store: storeLeadsource,
                id: 'editLeadsourceGrid',
                cm: columnModelLeadsource,
                autoExpandColumn:'lead_leadsource',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'actionAdd',
                    handler : handlerLeadsourceAdd
                    },{
                    text: 'delete item',
                    iconCls: 'actionDelete',
                    handler : handlerLeadsourceDelete
                    },{
                    text: 'save',
                    iconCls: 'actionSaveAndClose',
                    handler : handlerLeadsourceSaveClose 
                    }]  
                });
                    
                        
          Dialog.add(leadsourceGridPanel);
          Dialog.show();          
        }
  
     function editLeadtype() {
           var Dialog = new Ext.Window({
                title: 'Leadtypes',
                id: 'leadtypeWindow',
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
            
            var storeLeadtype = new Ext.data.JsonStore({
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
            
            storeLeadtype.load();
            
            var columnModelLeadtype = new Ext.grid.ColumnModel([
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
            
            var handlerLeadtypeAdd = function(){
                var p = new entry({
                    lead_leadtype_id: 'NULL',
                    lead_leadtype: ''
                });
                leadtypeGridPanel.stopEditing();
                storeLeadtype.insert(0, p);
                leadtypeGridPanel.startEditing(0, 0);
                leadtypeGridPanel.fireEvent('celldblclick',this, 0, 1);                
            };
                        
            var handlerLeadtypeDelete = function(){
                var leadtypeGrid  = Ext.getCmp('editLeadtypeGrid');
                var leadtypeStore = leadtypeGrid.getStore();
                
                var selectedRows = leadtypeGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    leadtypeStore.remove(selectedRows[i]);
                }   
            };                        
                        
          
           var handlerLeadtypeSaveClose = function(){
                var leadtypeStore = Ext.getCmp('editLeadtypeGrid').getStore();
                
                var leadtypeJson = Egw.Egwbase.Common.getJSONdata(leadtypeStore); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveLeadtypes',
                                optionsData: leadtypeJson
                            },
                            text: 'Saving leadtypes...',
                            success: function(_result, _request){
                                    leadtypeStore.reload();
                                    leadtypeStore.rejectChanges();
                               },
                            failure: function(form, action) {
                                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                                }
                        });          
            };          
            
            var leadtypeGridPanel = new Ext.grid.EditorGridPanel({
                store: storeLeadtype,
                id: 'editLeadtypeGrid',
                cm: columnModelLeadtype,
                autoExpandColumn:'lead_leadtype',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'actionAdd',
                    handler : handlerLeadtypeAdd
                    },{
                    text: 'delete item',
                    iconCls: 'actionDelete',
                    handler : handlerLeadtypeDelete
                    },{
                    text: 'save',
                    iconCls: 'actionSaveAndClose',
                    handler : handlerLeadtypeSaveClose 
                    }]  
                });
                    
                        
          Dialog.add(leadtypeGridPanel);
          Dialog.show();          
        }
    
    function editProductsource() {
           var Dialog = new Ext.Window({
                title: 'Products',
                id: 'productWindow',
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
            
            var storeProductsource = new Ext.data.JsonStore({
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
            
            storeProductsource.load();
            
            var columnModelProductsource = new Ext.grid.ColumnModel([
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
            
            var handlerProductsourceAdd = function(){
                var p = new entry({
                    lead_productsource_id: 'NULL',
                    lead_productsource: '',
                    lead_productsource_price: '0,00'
                });
                productsourceGridPanel.stopEditing();
                storeProductsource.insert(0, p);
                productsourceGridPanel.startEditing(0, 0);
                productsourceGridPanel.fireEvent('celldblclick',this, 0, 1);                
            };
                        
            var handlerProductsourceDelete = function(){
                var productsourceGrid  = Ext.getCmp('editProductsourceGrid');
                var productsourceStore = productsourceGrid.getStore();
                
                var selectedRows = productsourceGrid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    productsourceStore.remove(selectedRows[i]);
                }   
            };                        
                        
          
           var handlerProductsourceSaveClose = function(){
                var productsourceStore = Ext.getCmp('editProductsourceGrid').getStore();
                
                var productsourceJson = Egw.Egwbase.Common.getJSONdata(productsourceStore); 

                 Ext.Ajax.request({
                            params: {
                                method: 'Crm.saveProductsource',
                                optionsData: productsourceJson
                            },
                            text: 'Saving productsource...',
                            success: function(_result, _request){
                                    productsourceStore.reload();
                                    productsourceStore.rejectChanges();
                               },
                            failure: function(form, action) {
                                //  Ext.MessageBox.alert("Error",action.result.errorMessage);
                                }
                        });          
            };          
            
            var productsourceGridPanel = new Ext.grid.EditorGridPanel({
                store: storeProductsource,
                id: 'editProductsourceGrid',
                cm: columnModelProductsource,
                autoExpandColumn:'lead_productsource',
                frame:false,
                viewConfig: {
                    forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
                clicksToEdit:2,
                tbar: [{
                    text: 'new item',
                    iconCls: 'actionAdd',
                    handler : handlerProductsourceAdd
                    },{
                    text: 'delete item',
                    iconCls: 'actionDelete',
                    handler : handlerProductsourceDelete
                    },{
                    text: 'save',
                    iconCls: 'actionSaveAndClose',
                    handler : handlerProductsourceSaveClose 
                    }]  
                });
             
          Dialog.add(productsourceGridPanel);
          Dialog.show();          
        }     
        
        var settingsToolbarMenu = new Ext.menu.Menu({
            id: 'crmSettingsMenu',
            items: [
                {text: 'leadstate', handler: editLeadstate},
                {text: 'leadsource', handler: editLeadsource},
                {text: 'leadtype', handler: editLeadtype},
                {text: 'product', handler: editProductsource}
            ]
        });     
        
        
        var handlerToggleDetails = function(toggle) {
        	console.log(toggle.pressed);
            var gridView         = Ext.getCmp('gridCrm').getView();
            var gridColumnModell = Ext.getCmp('gridCrm').getColumnModel();
            
            if(toggle.pressed === true) {
                
                gridColumnModell.setRenderer(1, function(value, meta, record) {
                    return '<b>' + value + '</b><br /><br />' + record.data.lead_description;
                } );                
                
                gridColumnModell.setRenderer(2, function(_leadPartner) {                   
                    if(typeof(_leadPartner == 'array')) {
                        var _partner = '';
                        for(i=0; i < _leadPartner.length; i++){
                            var org_name           = Ext.isEmpty(_leadPartner[i].org_name) === false ? _leadPartner[i].org_name : '&nbsp;';
                            var n_fileas           = Ext.isEmpty(_leadPartner[i].n_fileas) === false ? _leadPartner[i].n_fileas : '&nbsp;';
                            var adr_one_street     = Ext.isEmpty(_leadPartner[i].adr_one_street) === false ? _leadPartner[i].adr_one_street : '&nbsp;';
                            var adr_one_postalcode = Ext.isEmpty(_leadPartner[i].adr_one_postalcode) === false ? _leadPartner[i].adr_one_postalcode : '&nbsp;';
                            var adr_one_locality   = Ext.isEmpty(_leadPartner[i].adr_one_locality) === false ? _leadPartner[i].adr_one_locality : '&nbsp;';
                            var tel_work           = Ext.isEmpty(_leadPartner[i].tel_work) === false ? _leadPartner[i].tel_work : '&nbsp;';
                            var tel_cell           = Ext.isEmpty(_leadPartner[i].tel_cell) === false ? _leadPartner[i].tel_cell : '&nbsp;';
                            
                            if(i > 0) {
                                _style = 'borderTop';
                            } else {
                                _style = '';
                            }
                            
                            _partner =  _partner + '<table width="100%" height="100%" class="' + _style + '">'
                                                 + '<tr><td colspan="2">' + org_name + '</td></tr>'
                                                 + '<tr><td colspan="2"><b>' + n_fileas + '</b></td></tr>'
                                                 + '<tr><td colspan="2">' + adr_one_street + '</td></tr>'
                                                 + '<tr><td colspan="2">' + adr_one_postalcode + ' ' + adr_one_locality + '</td></tr>'
                                                 + '<tr><td width="50%">phone: </td><td width="50%">' + tel_work + '</td></tr>'
                                                 + '<tr><td width="50%">cellphone: </td><td width="50%">' + tel_cell + '</td></tr>'
                                                 + '</table> <br />';
                        }
                        return _partner;
                    }
                });
                
                gridColumnModell.setRenderer(3, function(_leadCustomer) {
                    if(typeof(_leadCustomer == 'array')) {
                        var _customer = '';
                        for(i=0; i < _leadCustomer.length; i++){
                            var org_name           = Ext.isEmpty(_leadCustomer[i].org_name) === false ? _leadCustomer[i].org_name : '&nbsp;';
                            var n_fileas           = Ext.isEmpty(_leadCustomer[i].n_fileas) === false ? _leadCustomer[i].n_fileas : '&nbsp;';
                            var adr_one_street     = Ext.isEmpty(_leadCustomer[i].adr_one_street) === false ? _leadCustomer[i].adr_one_street : '&nbsp;';
                            var adr_one_postalcode = Ext.isEmpty(_leadCustomer[i].adr_one_postalcode) === false ? _leadCustomer[i].adr_one_postalcode : '&nbsp;';                            
                            var adr_one_locality   = Ext.isEmpty(_leadCustomer[i].adr_one_locality) === false ? _leadCustomer[i].adr_one_locality : '&nbsp;';                            
                            var tel_work           = Ext.isEmpty(_leadCustomer[i].tel_work) === false ? _leadCustomer[i].tel_work : '&nbsp;';
                            var tel_cell           = Ext.isEmpty(_leadCustomer[i].tel_cell) === false  ? _leadCustomer[i].tel_cell : '&nbsp;';
                            
                            if(i > 0) {
                                _style = 'borderTop';
                            } else {
                                _style = '';
                            }
                                                        
                            _customer =  _customer + '<table width="100%" height="100%" class="' + _style + '">'
                                                 + '<tr><td colspan="2">' + org_name + '</td></tr>'
                                                 + '<tr><td colspan="2"><b>' + n_fileas + '</b></td></tr>'
                                                 + '<tr><td colspan="2">' + adr_one_street + '</td></tr>'
                                                 + '<tr><td colspan="2">' + adr_one_postalcode + ' ' + adr_one_locality + '</td></tr>'
                                                 + '<tr><td width="50%">phone: </td><td width="50%">' + tel_work + '</td></tr>'
                                                 + '<tr><td width="50%">cellphone: </td><td width="50%">' + tel_cell + '</td></tr>'
                                                 + '</table> <br />';
                        }
                        return _customer;
                    }
                });
                  
               gridView.refresh();              
            } 
            
            if(toggle.pressed === false) {
                
                gridColumnModell.setRenderer(1, function(value, meta, record) {
                    return value;
                } );                
                
                gridColumnModell.setRenderer(2, function(_leadPartner) {
                if(typeof(_leadPartner == 'array') && _leadPartner[0]) {
                        return '<b>' + _leadPartner[0].org_name + '</b><br />' + _leadPartner[0].n_fileas;
                    }
                } );
                
                gridColumnModell.setRenderer(3, function(_leadCustomer) {
                if(typeof(_leadCustomer == 'array') && _leadCustomer[0]) {
                        return '<b>' + _leadCustomer[0].org_name + '</b><br />' + _leadCustomer[0].n_fileas;
                    }
                });
                  
               gridView.refresh();                                
            }
        };
        
        var toolbar = new Ext.Toolbar({
            id: 'crmToolbar',
            split: false,
            height: 26,
            items: [
                actions.actionAdd,
                actions.actionEdit,
                actions.actionDelete,
                actions.actionAddTask,
                new Ext.Toolbar.Separator(),
                {
                    text:'Options',
                    iconCls: 'actionEdit', 
                    menu: settingsToolbarMenu
                },
                '->',
                new Ext.Button({
                    text: 'Show Details',
                    enableToggle: true,
                    id: 'crmShowDetailsButton',
                    iconCls: 'showDetailsAction',
                    handler: handlerToggleDetails,
                }),                    
                ' ',
                new Ext.Button({
                    text: 'Show closed leads',
                    enableToggle: true,
                    iconCls: 'showEndedLeadsAction',
                    id: 'crmShowClosedLeadsButton',
                    handler: function(toggle) {                        
                        var dataStore = Ext.getCmp('gridCrm').getStore();
                        dataStore.reload();
                    },                    
                }),
                'Search:  ', ' ',                
                filterComboLeadstate,
                ' ',
                filterComboProbability,                
                new Ext.Toolbar.Separator(),
                '->',
                ' ',
                quickSearchField
            ]
        });
        
        Egw.Egwbase.MainScreen.setActiveToolbar(toolbar);
    };
    
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
    };

    
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
            actions.actionEdit,
            actions.actionDelete,
            actions.actionAddTask
        ]
    });

        var expander = new Ext.grid.RowExpander({
            enableCaching: false,
            tpl : new Ext.Template(
                '<b>Notes:</b> {lead_description}</div></td>',
                '<td class="x-grid3-col x-grid3-cell"><b>Activities:</b> </td>')
        });
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: true, header: 'projekt ID', id: 'lead_id', dataIndex: 'lead_id', width: 20, hidden: true},
            {resizable: true, header: 'lead name', id: 'lead_name', dataIndex: 'lead_name', width: 200},
            {resizable: true, header: 'Partner', id: 'lead_partner', dataIndex: 'lead_partner', width: 175, sortable: false, renderer: function(_leadPartner) {
                if(typeof(_leadPartner == 'array') && _leadPartner[0]) {
                    return '<b>' + _leadPartner[0].org_name + '</b><br />' + _leadPartner[0].n_fileas;
                }
            }},
            {resizable: true, header: 'Customer', id: 'lead_customer', dataIndex: 'lead_customer', width: 175, sortable: false, renderer: function(_leadCustomer) {
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
                  var leadstates = Ext.getCmp('filterLeadstate').store;
                  var leadstate_name = leadstates.getById(leadstate_id);
                  return leadstate_name.data.lead_leadstate;
              },
              width: 100},
            {resizable: true, header: 'probability', id: 'lead_probability', dataIndex: 'lead_probability', width: 50, renderer: Ext.util.Format.percentage},
            {resizable: true, header: 'turnover', id: 'lead_turnover', dataIndex: 'lead_turnover', width: 100, renderer: Ext.util.Format.euMoney }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                actions.actionDelete.setDisabled(true);
                actions.actionEdit.setDisabled(true);
                actions.actionAddTask.setDisabled(true);
            } 
            if (rowCount == 1) {
               actions.actionEdit.setDisabled(false);
               actions.actionDelete.setDisabled(false);               
               actions.actionAddTask.setDisabled(false);
            }    
            if(rowCount > 1) {                
               actions.actionEdit.setDisabled(true);
               actions.actionAddTask.setDisabled(true);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar, 
            stripeRows: true,  
            viewConfig: {
                forceFit:true
            },  
            plugins: expander,                    
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'lead_name',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No Leads to display'
            })            
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(gridPanel);


        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                actions.action_delete.setDisabled(false);
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            Egw.Egwbase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+record.data.lead_id, 900, 700);            
        });
       
       return;
    };
    

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
                limit:50
                //leadstate: Ext.getCmp('filterLeadstate').getValue(),
                //probability: Ext.getCmp('filterProbability').getValue()                                                                               
            }
        });
    };    
    
        
    // public functions and variables
    return {
        displayFolderSelectDialog: _displayFolderSelectDialog,
        show:   function(_node) {          
                    var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();
                    if (currentToolbar === false || currentToolbar.id != 'Crm_toolbar') {
                        _showCrmToolbar();
                        _showGrid(_node);
                    }
                    _loadData(_node);
        },    
        reload: function() {
                    if(Ext.ComponentMgr.all.containsKey('gridCrm')) {
                        setTimeout ("Ext.getCmp('gridCrm').getStore().reload()", 200);
                    }
        }
    };
}(); // end of application
  

Ext.namespace('Egw.Crm.LeadEditDialog');
Egw.Crm.LeadEditDialog = function() {
    // private variables
    var dialog;
    var leadedit;

    var _getAdditionalData = function()
    {
        var additionalData = {};
    
        var store_products      = Ext.getCmp('grid_choosenProducts').getStore();       
        additionalData.products = Egw.Egwbase.Common.getJSONdata(store_products);

        // the start date (can not be empty)
        var startDate = Ext.getCmp('lead_start').getValue();
        additionalData.lead_start = startDate.format('c');

        // the end date
        var endDate = Ext.getCmp('lead_end').getValue();
        if(typeof endDate == 'object') {
            additionalData.lead_end = endDate.format('c');
        } else {
            additionalData.lead_end = null;
        }

        // the estimated end
        var endScheduledDate = Ext.getCmp('lead_end_scheduled').getValue();
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
        
        var linksTasks = new Array();
        var taskGrid = Ext.getCmp('gridActivities');
        var storeTasks = taskGrid.getStore();
        
        storeTasks.each(function(record) {
            linksTasks.push(record.id);          
        });
        
        additionalData.linkedTasks = Ext.util.JSON.encode(linksTasks);        
        
        return additionalData;
    };

    
    /**
     * display the event edit dialog
     *
     */
    var _displayDialog = function(_leadData) {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';
        
        // work arround nasty ext date bug
        _leadData.lead_start         = _leadData.lead_start         ? Date.parseDate(_leadData.lead_start, 'c')         : _leadData.lead_start;
        _leadData.lead_end           = _leadData.lead_end           ? Date.parseDate(_leadData.lead_end, 'c')           : _leadData.lead_end;
        _leadData.lead_end_scheduled = _leadData.lead_end_scheduled ? Date.parseDate(_leadData.lead_end_scheduled, 'c') : _leadData.lead_end_scheduled;
        
        _leadData = new Egw.Crm.Model.Lead(_leadData);
        
        var disableButtons = true;

        var _setParameter = function(_dataSource) {
            _dataSource.baseParams.method = 'Crm.getEvents';
            _dataSource.baseParams.options = Ext.encode({
            });
        };
 
        var _editHandler = function(_button, _event) {
            editWindow.show();
        }; 

        var _action_edit = new Ext.Action({
            text: 'editieren',
            //disabled: true,
            handler: _editHandler,
            iconCls: 'actionEdit'
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

        var st_activities = Egw.Crm.LeadEditDialog.Stores.getActivities(_leadData.data.tasks);
     
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
            id:'lead_start',             
            format: 'd.m.Y',
            anchor:'95%'
        });

        
        var date_scheduledEnd = new Ext.form.DateField({
            fieldLabel:'estimated end', 
            //name:'lead_end_scheduled',
            id:'lead_end_scheduled',
            //    format:formData.config.dateFormat, 
            format: 'd.m.Y',
            anchor:'95%'
        });
        
        var date_end = new Ext.form.DateField({
            xtype:'datefield',
            fieldLabel:'end', 
            //name:'lead_end',
            id:'lead_end',
            //       format:formData.config.dateFormat, 
            format: 'd.m.Y',
            anchor:'95%'
        });

    activitiesGetStatusIcon = function(statusName) {   
        return '<div class="TasksMainGridStatus-' + statusName + '" ext:qtip="' + statusName + '"></div>';
    };
        

      var ActivitiesTpl = new Ext.XTemplate( 
        '<tpl for=".">',
            '<div class="activities-item-small">',
            '<div class="TasksMainGridStatus-{status_realname}" ext:qtip="{status_realname}"></div> {due}<br/>',
            '<i>{creator}</i><br />', 
            '<b>{summaray}</b><br />',
            '{description}<br />',                     
            '</div></tpl>', {
                setContactField: function(textValue){
                    alert(textValue);
                    if ((textValue === null) || (textValue.length == 0)) {
                        return '';
                    }
                    else {
                        return textValue+'<br />';
                    }
                }                                                
        });    
    /*    
        var activities_limited = new Ext.Panel({
            title: 'last activities',
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
  */
  
       if (_leadData.data) {                    
            var _lead_id = _leadData.data.lead_id;
       } else {
            var _lead_id = 'NULL';
       }
  
        var st_choosenProducts = new Ext.data.JsonStore({
            data: _leadData.data.products,
            autoLoad: true,
            id: 'lead_id',
            fields: [
                {name: 'lead_id'},
                {name: 'lead_product_id'},
                {name: 'lead_product_desc'},
                {name: 'lead_product_price'}
            ]
        });


        st_choosenProducts.on('update', function(store, record, index) {
          //  if(record.data.lead_id == 'NULL' && record.data.lead_product_id) {
            if(record.data.lead_product_id) {          
                var st_productsAvailable = Egw.Crm.LeadEditDialog.Stores.getProductsAvailable();
                var preset_price = st_productsAvailable.getById(record.data.lead_product_id);
                record.data.lead_product_price = preset_price.data.lead_productsource_price;
            }
        });

        var st_productsAvailable = Egw.Crm.LeadEditDialog.Stores.getProductsAvailable(); 
        
        var cm_choosenProducts = new Ext.grid.ColumnModel([{ 
                header: "lead_id",
                dataIndex: 'lead_id',
                width: 1,
                hidden: true,
                sortable: false,
                fixed: true
                } , {
                header: "product",
                id: 'cm_product',
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
                    typeAhead: true,
                    editable: true,
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
                header: "serialnumber",
                dataIndex: 'lead_product_desc',
                width: 300,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                } , {
                header: "price",
                dataIndex: 'lead_product_price',
                width: 150,
                align: 'right',
/*                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    decimalSeparator: ','
                    }),   */
                renderer: Ext.util.Format.euMoney
                }
        ]);
       
       var product_combo = Ext.getCmp('product_combo');
       product_combo.on('change', function(field, value) {
    
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

        var handler_remove_task = function(_button, _event)
        {
            var taskGrid = Ext.getCmp('gridActivities');
            var taskStore = taskGrid.getStore();
            
            var selectedRows = taskGrid.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                taskStore.remove(selectedRows[i]);
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
            autoExpandColumn:'cm_product',
            frame: false,
            clicksToEdit:2,
            tbar: [{
                text: 'add product',
                iconCls: 'actionAdd',
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
                    grid_choosenProducts.fireEvent('celldblclick',this, 0, 1);
                }
            } , {
                text: 'delete product',
                iconCls: 'actionDelete',
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
        //      {id:'link_remark', header: "link_remark", dataIndex: 'link_remark', width: 50, sortable: true },
                {id:'n_fileas', header: 'Name', dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                    function(val, meta, record) {
                        var org_name = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : '&nbsp;';
                        
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

         
        var cm_activities = new Ext.grid.ColumnModel([
                {   id:'identifier', 
                    header: "identifier", 
                    dataIndex: 'identifier', 
                    width: 5, 
                    sortable: true, 
                    hidden: true 
                }, {
                    id: 'status',
                    header: "Status",
                    width: 40,
                    sortable: true,
                    dataIndex: 'status_realname',
                    renderer: activitiesGetStatusIcon
                }, {
                    id: 'percent',
                    header: "Percent",
                    width: 50,
                    sortable: true,
                    dataIndex: 'percent'//,
    //              renderer: Egw.widgets.Percent.renderer,
                }, {
                    id: 'summaray',
                    header: "Summaray",
                    width: 200,
                    sortable: true,
                    dataIndex: 'summaray'
                }, {
                    id: 'due',
                    header: "Due Date",
                    width: 80,
                    sortable: true,
                    dataIndex: 'due',
                    renderer: Egw.Egwbase.Common.dateRenderer
                }, {
                    id: 'creator',
                    header: "Creator",
                    width: 130,
                    sortable: true,
                    dataIndex: 'creator'
                }, {
                    id: 'description',
                    header: "Description",
                    width: 240,
                    sortable: false,
                    dataIndex: 'description'
                }                                  
        ]);               
      
        var  _add_task = new Ext.Action({
                text: 'add task',
                handler: function(){
                	popupWindow = new Egw.Tasks.EditPopup({
                        relatedApp: 'crm',
                        relatedId: _leadData.data.lead_id
                	});
                	
                	popupWindow.on('update', function(task) {
                		var index = st_activities.getCount();
                		st_activities.insert(index, [task]);
                	}, this);
                },
                iconCls: 'actionAddTask',
                disabled: true
        });         

       if(_leadData.data.lead_id !== null) {
           _add_task.enable();
       }
      
        var gridActivities = new Ext.grid.GridPanel({
            id: 'gridActivities',
            store: st_activities,
            cm: cm_activities,
            tbar: [_add_task , {
                text: 'delete task',
                iconCls: 'actionDelete',
                handler : handler_remove_task 
            }],
            stripeRows: true,  
            viewConfig: {
                        forceFit:true
                    },  
            autoSizeColumns: true,
            sm: new Ext.grid.RowSelectionModel({multiSelect:true}),
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false
        });               

        gridActivities.on('rowdblclick', function(_grid, _rowIndex, _object) {
            var record = _grid.getStore().getAt(_rowIndex); 
            popupWindow = new Egw.Tasks.EditPopup({
                identifier: record.data.identifier
            });
            
            popupWindow.on('update', function(task) {
                // this is major bullshit!
            	// it only works one time. The problem begind begins with the different record 
            	// definitions here and in tasks...
                var record = st_activities.getById(task.data.identifier);
                var index = st_activities.indexOf(record);
                st_activities.remove(record);
                st_activities.insert(index, [task]);
                st_activities.commitChanges();
                
            }, this);
        });
               
        var folderTrigger = new Ext.form.TriggerField({
            fieldLabel:'folder (person in charge)', 
            id: 'lead_container_name',
            anchor:'95%',
            allowBlank: false,
            editable: false,
            readOnly:true
        });

        folderTrigger.on('render', function(comp) {
            if(formData.config.folderName) {
                comp.setValue(formData.config.folderName);
            }
        });

        folderTrigger.onTriggerClick = function() {
            Egw.Crm.Main.displayFolderSelectDialog('lead_container');
        };

        
   /*     
       if(formData.values.lead_id !== null) {
           _add_task.enable();
       }
 */       
        var tabPanelOverview = {
            title:'overview',
            layout:'border',
            layoutOnTabChange:true,
            defaults: {
                border: true,
                frame: true            
            },
            items: [{
                title: 'last activities',
                region: 'east',
                autoScroll: true,
                width: 300,
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
                items: [
                    txtfld_leadName, 
                {
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
                    height: 140,
                    id: 'lead_combos',
                    anchor:'100%',                        
                    items: [{
                        columnWidth: .33,
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
                    xtype: 'textfield',
                    hideLabel: false,
                    fieldLabel: 'products',
                    id: 'productSummary',
                    name:'productSummary',
                    allowBlank: false,
                    cls: 'productSummary',
                    disabled: true,
                    selectOnFocus: true,
                    anchor:'100%'//,
//                    value: formData.values.productSummary
                }, {
                    xtype: 'tabpanel',
                    id: 'contactsPanel',
                    title:'contacts panel',
                    activeTab: 0,
                    height: 273,
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
            disabled: false,
            layoutOnTabChange:true,            
            deferredRender:false,
            border:false,
            items:[
                gridActivities                  
            ]
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
  
// >>>  
  
      var handlerApplyChanges = function(_button, _event) 
        {
            //var grid_products          = Ext.getCmp('grid_choosenProducts');

            var closeWindow = arguments[2] ? arguments[2] : false;
            var leadForm = Ext.getCmp('leadDialog').getForm();
            leadForm.render();
            
            if(leadForm.isValid()) {  
                Ext.MessageBox.wait('please wait', 'saving lead...');                
           
                leadForm.updateRecord(_leadData);
                
                var additionalData = _getAdditionalData();
                
                Ext.Ajax.request({
                    params: {
                        method: 'Crm.saveLead', 
                        lead: Ext.util.JSON.encode(_leadData.data),
                        linkedCustomer: additionalData.linkedCustomer,
                        linkedPartner:  additionalData.linkedPartner,
                        linkedAccount:  additionalData.linkedAccount,
                        linkedTasks:    additionalData.linkedTasks,
                        products:       additionalData.products
                        //jsonKey: Egw.Egwbase.Registry.get('jsonKey')
                    },
                    success: function(_result, _request) {
                        if(window.opener.Egw.Crm) {
                            window.opener.Egw.Crm.Main.reload();
                        } 
                        if (closeWindow) {
                            window.setTimeout("window.close()", 400);
                        }
                        //dlg.action_delete.enable();
                        // override task with returned data
                        //task = new Egw.Tasks.Task(Ext.util.JSON.decode(_result.responseText));
                        // update form with this new data
                        //form.loadRecord(task);                    
                        Ext.MessageBox.hide();
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save lead.'); 
                    } 
                });
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
        };
 

        var handlerSaveAndClose = function(_button, _event) 
        {     
            handlerApplyChanges(_button, _event, true);
        };  
  
// <<<  
        var leadEdit = new Egw.widgets.dialog.EditRecord({
            id : 'leadDialog',
            tbarItems: [new Ext.Toolbar.Separator(), _add_task],
            handlerApplyChanges: handlerApplyChanges,
            handlerSaveAndClose: handlerSaveAndClose,
            handlerDelete: Egw.Crm.LeadEditDialog.Handler.handlerDelete,
            labelAlign: 'top',
            layout: 'fit',
                bodyStyle:'padding:5px',
                anchor:'100%',
                region: 'center',  
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
            items: leadEdit
        });
   
  //     Egw.Crm.LeadEditDialog.Handler.updateLeadRecord(_leadData);
  //     this.updateToolbarButtons();  
        Egw.Crm.LeadEditDialog.Stores.getContactsCustomer(_leadData.data.contactsCustomer);
        Egw.Crm.LeadEditDialog.Stores.getContactsPartner(_leadData.data.contactsPartner);
        Egw.Crm.LeadEditDialog.Stores.getContactsInternal(_leadData.data.contactsInternal);       
        Egw.Crm.LeadEditDialog.Stores.getActivities(_leadData.data.tasks);
        
      
        leadEdit.getForm().loadRecord(_leadData);
            
        Ext.getCmp('editViewport').on('afterlayout',function(container) {
             var _dimension = container.getSize();
             var _offset = 125;
             if(Ext.isIE7) {
                 var _offset = 142;
             }
             var _heightContacts = _dimension.height - Ext.getCmp('lead_name').getSize().height 
                                                     - Ext.getCmp('lead_notes').getSize().height
                                                     - Ext.getCmp('lead_combos').getSize().height
                                                     - Ext.getCmp('productSummary').getSize().height
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
            
            if(currentContactsStore.getById(record.id) == undefined) {
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
        
    };
/*
    var _setLeadDialogValues = function(_formData) {        
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
        }
        
        form.setValues(leadRecord.data);

        form.findField('lead_container_name').setValue(_formData.config.folderName);
        
        if (formData.values.lead_id > 0) {
            action_applyChanges.enable();
            action_delete.enable();
        }
        
        return;
    };
*/

        // public functions and variables
    return {
        displayDialog: function(_leadData) {
            _displayDialog(_leadData);
        }
    }
}(); // end of application

Egw.Crm.LeadEditDialog.Handler = function() {
    return { 
        removeContact: function(_button, _event) 
        {
console.log('remove contact');           
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
        },  
              
        handlerDelete: function() 
        {
            Ext.MessageBox.confirm('Confirm', 'Are you sure you want to delete this lead?', function(_button) {
                if(_button == 'yes') {      
                    var leadIds;// = Ext.util.JSON.encode([formData.values.lead_id]);
                    
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
            });
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
            iconCls: 'actionDelete'
        }),
        
        actionAddContact: new Ext.Action({
            text: 'create new contact',
            handler: Egw.Crm.LeadEditDialog.Handler.addContact,
            iconCls: 'actionAdd'
        }),

        actionAddContactToList: new Ext.Action({
            text: 'add contact to list',
            disabled: true,
            handler: function(_button, _event) {
                Egw.Crm.LeadEditDialog.Handler.addContactToList(Ext.getCmp('crm_editLead_SearchContactsGrid'));
            },
            iconCls: 'actionAdd'
        }),        
        
        columnModelDisplayContacts: new Ext.grid.ColumnModel([
            {id:'contact_id', header: "contact_id", dataIndex: 'contact_id', width: 25, sortable: true, hidden: true },
            {id:'n_fileas', header: 'Name / Address', dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                function(val, meta, record) {
                    var n_fileas           = Ext.isEmpty(record.data.n_fileas) === false ? record.data.n_fileas : '&nbsp;';
                    var org_name           = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : '&nbsp;';
                    var adr_one_street     = Ext.isEmpty(record.data.adr_one_street) === false ? record.data.adr_one_street : '&nbsp;';
                    var adr_one_postalcode = Ext.isEmpty(record.data.adr_one_postalcode) === false ? record.data.adr_one_postalcode : '&nbsp;';
                    var adr_one_locality   = Ext.isEmpty(record.data.adr_one_locality) === false ? record.data.adr_one_locality : '&nbsp;';                                       
                    
                    
                    var formated_return = '<b>' + n_fileas + '</b><br />' + org_name + '<br  />' + 
                        adr_one_street + '<br />' + 
                        adr_one_postalcode + ' ' + adr_one_locality    ;                    
                    
                    return formated_return;
                }
            },
            {id:'contact_one', header: "Phone", dataIndex: 'adr_one_locality', width: 170, sortable: false, renderer: function(val, meta, record) {
                    var tel_work           = Ext.isEmpty(record.data.tel_work) === false ? record.data.tel_work : '&nbsp;';
                    var tel_fax            = Ext.isEmpty(record.data.tel_fax) === false ? record.data.tel_fax : '&nbsp;';
                    var tel_cell           = Ext.isEmpty(record.data.tel_cell) === false ? record.data.tel_cell : '&nbsp;'  ;                                      

                var formated_return = '<table>' + 
                    '<tr><td>Phone: </td><td>' + tel_work + '</td></tr>' + 
                    '<tr><td>Fax: </td><td>' + tel_fax + '</td></tr>' + 
                    '<tr><td>Cellphone: </td><td>' + tel_cell + '</td></tr>' + 
                    '</table>';
                
                    return formated_return;
                }
            },
            {id:'tel_work', header: "Internet", dataIndex: 'tel_work', width: 200, sortable: false, renderer: function(val, meta, record) {
                    var contact_email      = Ext.isEmpty(record.data.contact_email) === false ? '<a href="mailto:'+record.data.contact_email+'">'+record.data.contact_email+'</a>' : '&nbsp;';
                    var contact_url        = Ext.isEmpty(record.data.contact_url) === false ? record.data.contact_url : '&nbsp;';                    

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
                    
                    if(Ext.isEmpty(record.data.n_fileas) === false) {
                        formated_return = '<b>' + record.data.n_fileas + '</b><br />';
                    }

                    if(Ext.isEmpty(record.data.org_name) === false) {
                        if(formated_return === null) {
                            formated_return = '<b>' + record.data.org_name + '</b><br />';
                        } else {
                            formated_return += record.data.org_name + '<br />';
                        }
                    }

                    if(Ext.isEmpty(record.data.adr_one_street) === false) {
                        formated_return += record.data.adr_one_street + '<br />';
                    }
                    
                    if( (Ext.isEmpty(record.data.adr_one_postalcode) === false)  && (Ext.isEmpty(record.data.adr_one_locality) === false) ) {
                        formated_return += record.data.adr_one_postalcode + ' ' + record.data.adr_one_locality + '<br />';
                    } else if (Ext.isEmpty(record.data.adr_one_locality) === false) {
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
            });
            

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
            };
            return tabPanel;
        }
    };
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
        
        getContactsCustomer: function (_contactsCustomer){  
                 
            if(_storeContactsCustomer === null) {
                _storeContactsCustomer = new Ext.data.JsonStore({
                    id: 'contact_id',
                    fields: this.contactFields
                });
            }                
                
            if(_contactsCustomer) {
                _storeContactsCustomer.loadData(_contactsCustomer);    
            }
            
            return _storeContactsCustomer;            
        },
        
        getContactsPartner: function (_contactsPartner){
  
            if(_storeContactsPartner === null) {
                _storeContactsPartner = new Ext.data.JsonStore({
                    id: 'contact_id',
                    fields: this.contactFields
                });
            }
             
            if(_contactsPartner) {                
                _storeContactsPartner.loadData(_contactsPartner);
            }
                         
            return _storeContactsPartner;
        },
        
        getContactsInternal: function (_contactsInternal){

            if(_storeContactsInternal === null) {
                _storeContactsInternal = new Ext.data.JsonStore({
                    id: 'contact_id',
                    fields: this.contactFields
                });
            }    
                
            if(_contactsInternal) {
                _storeContactsInternal.loadData(_contactsInternal);
            }                 
            
            return _storeContactsInternal;
        },

        getContactsSearch: function (){
            if(_storeContactsSearch === null) {
                _storeContactsSearch = new Ext.data.JsonStore({
                    baseParams: {
                        method: 'Addressbook.getAllContacts',
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
        
        getActivities: function (_tasks){     
            var store = new Ext.data.JsonStore({
                id: 'identifier',
                fields: [
                    {name: 'identifier'},
                    {name: 'container'},
                    {name: 'created_by'},
                    {name: 'creation_time', type: 'date', dateFormat: 'c'},
                    {name: 'last_modified_by'},
                    {name: 'last_modified_time', type: 'date', dateFormat: 'c'},
                    {name: 'is_deleted'},
                    {name: 'deleted_time', type: 'date', dateFormat: 'c'},
                    {name: 'deleted_by'},
                    {name: 'percent'},
                    {name: 'completed', type: 'date', dateFormat: 'c'},
                    {name: 'due', type: 'date', dateFormat: 'c'},
                    {name: 'class'},
                    {name: 'description'},
                    {name: 'geo'},
                    {name: 'location'},
                    {name: 'organizer'},
                    {name: 'priority'},
                    {name: 'status'},
                    {name: 'summaray'},
                    {name: 'url'},
                    
                    {name: 'creator'},
                    {name: 'modifier'},
                    {name: 'status_realname'}
                ]
            });

            if(_tasks) {                
                store.loadData(_tasks);
            }
        
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
    };
}();

// models
Egw.Crm.Model = {};

// lead
Egw.Crm.Model.Lead = Ext.data.Record.create([
    {name: 'lead_id',            type: 'int'},
    {name: 'lead_name',          type: 'string'},
    {name: 'lead_leadstate_id',  type: 'int'},
    {name: 'lead_leadtype_id',   type: 'int'},
    {name: 'lead_leadsource_id', type: 'int'},
    {name: 'lead_container',     type: 'int'},
    {name: 'lead_modifier',      type: 'int'},
    {name: 'lead_start',         type: 'date', dateFormat: 'c'},
    {name: 'lead_modified'},
    {name: 'lead_description',   type: 'string'},
    {name: 'lead_end',           type: 'date', dateFormat: 'c'},
    {name: 'lead_turnover',      type: 'int'},
    {name: 'lead_probability',   type: 'int'},
    {name: 'lead_end_scheduled', type: 'date', dateFormat: 'c'},
    {name: 'lead_lastread'},
    {name: 'lead_lastreader'},
    {name: 'lead_leadstate'},
    {name: 'lead_leadtype'},
    {name: 'lead_leadsource'},
    {name: 'lead_partner_linkId'},
    {name: 'lead_partner'},
    {name: 'lead_partner_detail'},                
    {name: 'lead_lead_linkId'},
    {name: 'lead_customer'},
    {name: 'lead_lead_detail'}  
]);
