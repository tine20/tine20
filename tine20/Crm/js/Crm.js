/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Crm');

/**
 * entry point, required by tinebase
 * creates and returnes app tree panel
 */
Tine.Crm.getPanel = function(){
	var tree = new Tine.widgets.container.TreePanel({
        id: 'crmTree',
        iconCls: 'crmThumbnailApplication',
        title: 'CRM',
        itemName: 'Leads',
        folderName: 'Leads',
        appName: 'crm',
        border: false
    });

    
    tree.on('click', function(node){
        Tine.Crm.Main.show(node);
    }, this);
        
    tree.on('beforeexpand', function(panel) {
        if(panel.getSelectionModel().getSelectedNode() === null) {
            panel.expandPath('/root/all');
            panel.selectPath('/root/all');
        }
        panel.fireEvent('click', panel.getSelectionModel().getSelectedNode());
    }, this);
    
    return tree;
};

Tine.Crm.Main = function(){

    var handler = {
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId=' + _rowIndex[0].id, 900, 700);
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
            
            popupWindow = new Tine.Tasks.EditPopup({
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
                //  var tree = Ext.getCmp('venues-tree');
                //  var curSelNode = tree.getSelectionModel().getSelectedNode();
                //  var RootNode   = tree.getRootNode();
                Tine.Tinebase.Common.openWindow('CrmLeadWindow', 'index.php?method=Crm.editLead&_leadId=0&_eventId=NULL', 900, 700);
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
    
    var _createDataStore = function()
    {
        var storeCrm = new Ext.data.JsonStore({
            baseParams: {
                method: 'Crm.getLeadsByOwner',
                owner: 'all'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Crm.Model.Lead,
            // turn on remote sorting
            remoteSort: true
        });
        
        storeCrm.setDefaultSort('description_ld', 'asc');

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
                    //state: Ext.getCmp('filterLeadstate').getValue(),
                    //probability: Ext.getCmp('filterProbability').getValue()                   
                }
            });
        });

            
       var filterComboLeadstate = new Ext.ux.ClearableComboBox({
            fieldLabel:'Leadstate', 
            id:'filterLeadstate',
           //id:'id',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            blankText: 'leadstate...',
            hiddenName:'leadstate_id',
            store: Tine.Crm.LeadState.getStore(),
            displayField:'leadstate',
            valueField:'leadstate_id',
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
               var _leadstate = record.data.leadstate_id;
           }
           
           combo.triggers[0].show();
           
           Ext.getCmp('gridCrm').getStore().load({
               params: {                
                start: 0,
                limit: 50,
          //      state: _state,
				leadstate: _leadstate,
				probability: Ext.getCmp('filterProbability').getValue()
                }
            });
       });
      
       var filterComboProbability = new Ext.ux.ClearableComboBox({
            fieldLabel:'probability', 
            id: 'filterProbability',
            name:'probability',
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
                    //state: Ext.getCmp('filterLeadstate').getValue(),
                    //probability: _probability
                }
            });         
       });      
        
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
                    sort: 'leadsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'leadsource_id',
                fields: [
                    {name: 'id'},
                    {name: 'leadsource'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            storeLeadsource.load();
            
            var columnModelLeadsource = new Ext.grid.ColumnModel([
                    { id:'leadsource_id', 
                      header: "id", 
                      dataIndex: 'leadsource_id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'leadsource', 
                      header: 'entries', 
                      dataIndex: 'leadsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'leadsource_id', type: 'int'},
               {name: 'leadsource', type: 'varchar'}
            ]);
            
            var handlerLeadsourceAdd = function(){
                var p = new entry({
                    leadsource_id: 'NULL',
                    leadsource: ''
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
                
                var leadsourceJson = Tine.Tinebase.Common.getJSONdata(leadsourceStore); 

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
                autoExpandColumn:'leadsource',
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
                    sort: 'leadtype',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'leadtype_id',
                fields: [
                    {name: 'id'},
                    {name: 'leadtype'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            storeLeadtype.load();
            
            var columnModelLeadtype = new Ext.grid.ColumnModel([
                    { id:'id', 
                      header: "id", 
                      dataIndex: 'id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'leadtype', 
                      header: 'leadtype', 
                      dataIndex: 'leadtype', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }                    
            ]);            
            
             var entry = Ext.data.Record.create([
               {name: 'leadtype_id', type: 'int'},
               {name: 'leadtype', type: 'varchar'}
            ]);
            
            var handlerLeadtypeAdd = function(){
                var p = new entry({
                    leadtype_id: 'NULL',
                    leadtype: ''
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
                
                var leadtypeJson = Tine.Tinebase.Common.getJSONdata(leadtypeStore); 

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
                autoExpandColumn:'leadtype',
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
                    sort: 'productsource',
                    dir: 'ASC'
                },
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                fields: [
                    {name: 'id'},
                    {name: 'productsource'},
                    {name: 'price'}
                ],
                // turn on remote sorting
                remoteSort: false
            });
            
            storeProductsource.load();
            
            var columnModelProductsource = new Ext.grid.ColumnModel([
                    { id:'id', 
                      header: "id", 
                      dataIndex: 'id', 
                      width: 25, 
                      hidden: true 
                    },
                    { id:'productsource', 
                      header: 'entries', 
                      dataIndex: 'productsource', 
                      width: 170, 
                      hideable: false, 
                      sortable: false, 
                      editor: new Ext.form.TextField({allowBlank: false}) 
                    }, 
                    {
                      id: 'price',  
                      header: "price",
                      dataIndex: 'price',
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
               {name: 'id', type: 'int'},
               {name: 'productsource', type: 'varchar'},
               {name: 'price', type: 'number'}
            ]);
            
            var handlerProductsourceAdd = function(){
                var p = new entry({
                    productsource_id: 'NULL',
                    productsource: '',
                    productsource_price: '0,00'
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
                
                var productsourceJson = Tine.Tinebase.Common.getJSONdata(productsourceStore); 

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
                autoExpandColumn:'productsource',
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
                {text: 'leadstate', handler: Tine.Crm.LeadState.EditStatesDialog},
                {text: 'leadsource', handler: editLeadsource},
                {text: 'leadtype', handler: editLeadtype},
                {text: 'product', handler: editProductsource}
            ]
        });     
        
        
        var handlerToggleDetails = function(toggle) {
        	//console.log(toggle.pressed);
            var gridView         = Ext.getCmp('gridCrm').getView();
            var gridColumnModell = Ext.getCmp('gridCrm').getColumnModel();
            
            if(toggle.pressed === true) {
                
                gridColumnModell.setRenderer(1, function(value, meta, record) {
                    return '<b>' + value + '</b><br /><br />' + record.data.description;
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
                    handler: handlerToggleDetails
                }),                    
                ' ',
                new Ext.Button({
                    text: 'Show closed',
                    enableToggle: true,
                    iconCls: 'showEndedLeadsAction',
                    id: 'crmShowClosedLeadsButton',
                    handler: function(toggle) {                        
                        var dataStore = Ext.getCmp('gridCrm').getStore();
                        dataStore.reload();
                    }                    
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
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
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
                '<b>Notes:</b> {description}</div></td>',
                '<td class="x-grid3-col x-grid3-cell"><b>Activities:</b> </td>')
        });
        
        var columnModel = new Ext.grid.ColumnModel([
            /*
			{resizable: true, header: 'projekt ID', id: 'id', dataIndex: 'id', width: 20, hidden: true},
            {resizable: true, header: 'lead name', id: 'description_ld', dataIndex: 'description_ld', width: 200},
            {resizable: true, header: 'Partner', id: 'lead_partner', dataIndex: 'lead_partner', width: 175, sortable: false, renderer: function(_leadPartner) {
                if(typeof(_leadPartner == 'array') && _leadPartner[0]) {
                    return '<b>' + _leadPartner[0].org_name + '</b><br />' + _leadPartner[0].n_fileas;
                }
            }},
            {resizable: true, header: 'Customer', id: 'lead_customer', dataIndex: 'lead_customer', width: 175, sortable: false, renderer: function(_leadCustomer) {
                if(typeof(_leadCustomer == 'array') && _leadCustomer[0]) {
                    return '<b>' + _leadCustomer[0].org_name + '</b><br />' + _leadCustomer[0].n_fileas;
                }
            }},*/
            {resizable: true, 
              header: 'state', 
              id: 'leadstate_id', 
              dataIndex: 'leadstate_id', 
              sortable: false,
              renderer: function(leadstate_id) {
                  var leadstates = Ext.getCmp('filterLeadstate').store;
                  var leadstate_name = leadstates.getById(leadstate_id);
                  return leadstate_name.data.leadstate;
              },
              width: 100},
            {resizable: true, header: 'probability', id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage},
            {resizable: true, header: 'turnover', id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
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
            autoExpandColumn: 'description_ld',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No Leads to display'
            })            
        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);


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
            Tine.Tinebase.Common.openWindow('leadWindow', 'index.php?method=Crm.editLead&_leadId='+record.data.id, 900, 700);            
        });
       
       return;
    };
    

   var _loadData = function(_node)
    {       
        var dataStore = Ext.getCmp('gridCrm').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.containerType) {
            case 'shared':
                dataStore.baseParams.method = 'Crm.getSharedLeads';
                break;
                  
            case 'otherUsers':
                dataStore.baseParams.method = 'Crm.getOtherPeopleLeads';
                break;

            case 'all':
                dataStore.baseParams.method = 'Crm.getAllLeads';
                break;

            case 'personal':
                dataStore.baseParams.method = 'Crm.getLeadsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner.accountId;
                break;

            case 'singleContainer':
                dataStore.baseParams.method        = 'Crm.getLeadsByFolder';
                dataStore.baseParams.folderId = _node.attributes.container.id;
                break;
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50
                //state: Ext.getCmp('filterLeadstate').getValue(),
                //probability: Ext.getCmp('filterProbability').getValue()                                                                               
            }
        });
    };    
    
        
    // public functions and variables
    return {
        show:   function(_node) {          
                    var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();
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
  

Ext.namespace('Tine.Crm.LeadEditDialog');
Tine.Crm.LeadEditDialog = function() {
    // private variables
    var dialog;
    var leadedit;

    var _getAdditionalData = function()
    {
        var additionalData = {};
    
        var store_products      = Ext.getCmp('grid_choosenProducts').getStore();       
        additionalData.products = Tine.Tinebase.Common.getJSONdata(store_products);

        // the start date (can not be empty)
        var startDate = Ext.getCmp('start').getValue();
        additionalData.start = startDate.format('c');

        // the end date
        var endDate = Ext.getCmp('end').getValue();
        if(typeof endDate == 'object') {
            additionalData.end = endDate.format('c');
        } else {
            additionalData.end = null;
        }

        // the estimated end
        var endScheduledDate = Ext.getCmp('end_scheduled').getValue();
        if(typeof endScheduledDate == 'object') {
            additionalData.end_scheduled = endScheduledDate.format('c');
        } else {
            additionalData.end_scheduled = null;
        }
        
        
        // collect data of assosicated contacts
        var linksContactsCustomer = new Array();
        var storeContactsCustomer = Tine.Crm.LeadEditDialog.Stores.getContactsCustomer();
        
        storeContactsCustomer.each(function(record) {
            linksContactsCustomer.push(record.id);          
        });
        
        additionalData.linkedCustomer = Ext.util.JSON.encode(linksContactsCustomer);
        

        var linksContactsPartner = new Array();
        var storeContactsPartner = Tine.Crm.LeadEditDialog.Stores.getContactsPartner();
        
        storeContactsPartner.each(function(record) {
            linksContactsPartner.push(record.id);          
        });
        
        additionalData.linkedPartner = Ext.util.JSON.encode(linksContactsPartner);


        var linksContactsInternal = new Array();
        var storeContactsInternal = Tine.Crm.LeadEditDialog.Stores.getContactsInternal();
        
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
        

        _leadData = new Tine.Crm.Model.Lead(_leadData);
        Tine.Crm.Model.Lead.FixDates(_leadData);
        
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
            id: 'description_ld',
            //fieldLabel:'Projektname', 
            emptyText: 'enter short name',
            name:'description_ld',
            allowBlank: false,
            selectOnFocus: true,
            anchor:'100%'
            //selectOnFocus:true            
        }); 
 
        var combo_leadstatus = new Ext.form.ComboBox({
                fieldLabel:'leadstate', 
                id:'leadstatus',
                name:'id',
                store: Tine.Crm.LeadEditDialog.Stores.getLeadStatus(),
                displayField:'value',
                valueField:'key',
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
                var combo_endDate = Ext.getCmp('end');
                combo_endDate.setValue(new Date());
            }
        });
        
        var combo_leadtyp = new Ext.form.ComboBox({
            fieldLabel:'leadtype', 
            id:'leadtype',
            name:'leadtype_id',
            store: Tine.Crm.LeadEditDialog.Stores.getLeadType(),
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
    

        var st_leadsource = new Ext.data.JsonStore({
            data: formData.comboData.leadsources,
            autoLoad: true,
            id: 'key',
            fields: [
                {name: 'key', mapping: 'id'},
                {name: 'value', mapping: 'leadsource'}

            ]
        });     

        var combo_leadsource = new Ext.form.ComboBox({
                fieldLabel:'leadsource', 
                id:'leadsource',
                name:'id',
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

        var st_activities = Tine.Crm.LeadEditDialog.Stores.getActivities(_leadData.data.tasks);
     
        var combo_probability =  new Ext.form.ComboBox({
            fieldLabel:'probability', 
            id: 'combo_probability',
            name:'probability',
            store: Tine.Crm.LeadEditDialog.Stores.getProbability(),
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
            id:'start',             
            format: 'd.m.Y',
            anchor:'95%'
        });

        
        var date_scheduledEnd = new Ext.form.DateField({
            fieldLabel:'estimated end', 
            id:'end_scheduled',
            format: 'd.m.Y',
            anchor:'95%'
        });
        
        var date_end = new Ext.form.DateField({
            xtype:'datefield',
            fieldLabel:'end', 
            id:'end',
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
            '<b>{summary}</b><br />',
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
            var _lead_id = _leadData.data.id;
       } else {
            var _lead_id = 'NULL';
       }
  
        var st_choosenProducts = new Ext.data.JsonStore({
            data: _leadData.data.products,
            autoLoad: true,
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'product_id'},
                {name: 'product_desc'},
                {name: 'product_price'}
            ]
        });


        st_choosenProducts.on('update', function(store, record, index) {
          //  if(record.data.id == 'NULL' && record.data.product_id) {
            if(record.data.product_id) {          
                var st_productsAvailable = Tine.Crm.LeadEditDialog.Stores.getProductsAvailable();
                var preset_price = st_productsAvailable.getById(record.data.product_id);
                record.data.product_price = preset_price.data.productsource_price;
            }
        });

        var st_productsAvailable = Tine.Crm.LeadEditDialog.Stores.getProductsAvailable(); 
        
        var cm_choosenProducts = new Ext.grid.ColumnModel([{ 
                header: "id",
                dataIndex: 'id',
                width: 1,
                hidden: true,
                sortable: false,
                fixed: true
                } , {
                header: "product",
                id: 'cm_product',
                dataIndex: 'product_id',
                width: 300,
                editor: new Ext.form.ComboBox({
                    name: 'product_combo',
                    id: 'product_combo',
                    hiddenName: 'productsource_id', //product_id',
                    store: st_productsAvailable, 
                    displayField:'value', 
                    valueField: 'productsource_id',
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
                dataIndex: 'product_desc',
                width: 300,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                } , {
                header: "price",
                dataIndex: 'product_price',
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
           {name: 'id', type: 'int'},
           {name: 'zumleadkey', type: 'int'},
           {name: 'product_id', type: 'int'},
           {name: 'product_desc', type: 'string'},
           {name: 'product_price', type: 'float'}
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
                        id: 'NULL',
                        zumleadkey: _lead_id,
                        product_id: '',                       
                        product_desc:'',
                        product_price: ''
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

        var storeContactsCustomer = Tine.Crm.LeadEditDialog.Stores.getContactsCustomer();
             
        var storeContactsPartner = Tine.Crm.LeadEditDialog.Stores.getContactsPartner();
             
        var storeContactsInternal = Tine.Crm.LeadEditDialog.Stores.getContactsInternal();     
     
        var store_contactSearch = Tine.Crm.LeadEditDialog.Stores.getContactsSearch();   
 

        var cm_contacts = new Ext.grid.ColumnModel([
                {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
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
                    //renderer: Tine.widgets.Percent.renderer,
                }, {
                    id: 'summary',
                    header: "summary",
                    width: 200,
                    sortable: true,
                    dataIndex: 'summary'
                }, {
                    id: 'due',
                    header: "Due Date",
                    width: 80,
                    sortable: true,
                    dataIndex: 'due',
                    renderer: Tine.Tinebase.Common.dateRenderer
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
                	popupWindow = new Tine.Tasks.EditPopup({
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

       if(_leadData.data.id !== null) {
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
            popupWindow = new Tine.Tasks.EditPopup({
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
        
        var folderTrigger = new Tine.widgets.container.selectionComboBox({
            fieldLabel: 'folder (person in charge)',
            name: 'container',
            itemName: 'Leads',
            appName: 'crm',
            anchor:'95%'
        });
     
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
                    name:'description',
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
                                name:'turnover',
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
                    anchor:'100%'
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
  
      var handlerApplyChanges = function(_button, _event) 
        {
            //var grid_products          = Ext.getCmp('grid_choosenProducts');

            var closeWindow = arguments[2] ? arguments[2] : false;
            var leadForm = Ext.getCmp('leadDialog').getForm();
            
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
                        //jsonKey: Tine.Tinebase.Registry.get('jsonKey')
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Crm) {
                            window.opener.Tine.Crm.Main.reload();
                        } 
                        if (closeWindow) {
                            window.setTimeout("window.close()", 400);
                        }
                        
                        // fill form with returned lead
                        _leadData = new Tine.Crm.Model.Lead(Ext.util.JSON.decode(_result.responseText));
                        Tine.Crm.Model.Lead.FixDates(_leadData);
                        leadForm.loadRecord(_leadData);
                        
                        //dlg.action_delete.enable();
                        _add_task.enable();
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
  
        var leadEdit = new Tine.widgets.dialog.EditRecord({
            id : 'leadDialog',
            tbarItems: [new Ext.Toolbar.Separator(), _add_task],
            handlerApplyChanges: handlerApplyChanges,
            handlerSaveAndClose: handlerSaveAndClose,
            handlerDelete: Tine.Crm.LeadEditDialog.Handler.handlerDelete,
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
                    Tine.Crm.LeadEditDialog.Elements.getTabPanelManageContacts(),                    
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
   
  //     Tine.Crm.LeadEditDialog.Handler.updateLeadRecord(_leadData);
  //     this.updateToolbarButtons();  
        Tine.Crm.LeadEditDialog.Stores.getContactsCustomer(_leadData.data.contactsCustomer);
        Tine.Crm.LeadEditDialog.Stores.getContactsPartner(_leadData.data.contactsPartner);
        Tine.Crm.LeadEditDialog.Stores.getContactsInternal(_leadData.data.contactsInternal);       
        Tine.Crm.LeadEditDialog.Stores.getActivities(_leadData.data.tasks);
        
        leadEdit.getForm().loadRecord(_leadData);
            
        Ext.getCmp('editViewport').on('afterlayout',function(container) {
             var _dimension = container.getSize();
             var _offset = 125;
             if(Ext.isIE7) {
                 var _offset = 142;
             }
             var _heightContacts = _dimension.height - Ext.getCmp('description_ld').getSize().height 
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
                Tine.Crm.LeadEditDialog.Stores.getContactsSearch().removeAll();
            } else {
                //Ext.getCmp('crm_editLead_SearchContactsGrid').getStore().load({
                Tine.Crm.LeadEditDialog.Stores.getContactsSearch().load({
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
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
        });
        
        Ext.getCmp('crm_gridPartner').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
            var record = _grid.getStore().getAt(_rowIndex);
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
        });
        
        Ext.getCmp('crm_gridAccount').on('rowdblclick', function(_grid, _rowIndex, _eventObject){
            var record = _grid.getStore().getAt(_rowIndex);
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.id, 850, 600);
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
                Tine.Crm.LeadEditDialog.Elements.actionRemoveContact.setDisabled(true);
            } else {
                // at least one row selected
                Tine.Crm.LeadEditDialog.Elements.actionRemoveContact.setDisabled(false);
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
                Tine.Crm.LeadEditDialog.Elements.actionAddContactToList.setDisabled(true);
            } else {
                // at least one row selected
                Tine.Crm.LeadEditDialog.Elements.actionAddContactToList.setDisabled(false);
            }
        }; 
        
        Ext.getCmp('crm_editLead_SearchContactsGrid').getSelectionModel().on('selectionchange', setAddContactButtonState);
        
    };

        // public functions and variables
    return {
        displayDialog: function(_leadData) {
            _displayDialog(_leadData);
        }
    }
}(); // end of application

Tine.Crm.LeadEditDialog.Handler = function() {
    return { 
        removeContact: function(_button, _event) 
        {
            //console.log('remove contact');           
            var currentContactsTab = Ext.getCmp('crm_editLeadL_istContactsTabPanel').getActiveTab();
            
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
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
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
                    var leadIds;// = Ext.util.JSON.encode([formData.values.id]);
                    
                    Ext.Ajax.request({
                        params: {
                            method: 'Crm.deleteLeads',
                            _leadIds: leadIds
                        },
                        text: 'Deleting lead...',
                        success: function(_result, _request){
                            window.opener.Tine.Crm.reload();
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

Tine.Crm.LeadEditDialog.Elements = function() {
    // public functions and variables
    return {
       actionRemoveContact: new Ext.Action({
            text: 'remove contact from list',
            disabled: true,
            handler: Tine.Crm.LeadEditDialog.Handler.removeContact,
            iconCls: 'actionDelete'
        }),
        
        actionAddContact: new Ext.Action({
            text: 'create new contact',
            handler: Tine.Crm.LeadEditDialog.Handler.addContact,
            iconCls: 'actionAdd'
        }),

        actionAddContactToList: new Ext.Action({
            text: 'add contact to list',
            disabled: true,
            handler: function(_button, _event) {
                Tine.Crm.LeadEditDialog.Handler.addContactToList(Ext.getCmp('crm_editLead_SearchContactsGrid'));
            },
            iconCls: 'actionAdd'
        }),        
        
        columnModelDisplayContacts: new Ext.grid.ColumnModel([
            {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
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
                    var email      = Ext.isEmpty(record.data.email) === false ? '<a href="mailto:'+record.data.email+'">'+record.data.email+'</a>' : '&nbsp;';
                    var contact_url        = Ext.isEmpty(record.data.contact_url) === false ? record.data.contact_url : '&nbsp;';                    

                var formated_return = '<table>' + 
                    '<tr><td>Email: </td><td>' + email + '</td></tr>' + 
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
                        Tine.Crm.LeadEditDialog.Elements.actionAddContactToList
                    ],
                    items: [
                        {
                            xtype:'grid',
                            id: 'crm_editLead_SearchContactsGrid',
                            title:'Search',
                            cm: this.columnModelSearchContacts,
                            store: Tine.Crm.LeadEditDialog.Stores.getContactsSearch(),
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
                        Tine.Crm.LeadEditDialog.Elements.actionAddContact,                
                        Tine.Crm.LeadEditDialog.Elements.actionRemoveContact
                    ],                
                    items: [
                        {
                            xtype:'grid',
                            id: 'crm_gridCostumer',
                            title:'Customer',
                            cm: this.columnModelDisplayContacts,
                            store: Tine.Crm.LeadEditDialog.Stores.getContactsCustomer(),
                            autoExpandColumn: 'n_fileas'
                        },{
                            xtype:'grid',
                            id: 'crm_gridPartner',
                            title:'Partner',
                            cm: this.columnModelDisplayContacts,
                            store: Tine.Crm.LeadEditDialog.Stores.getContactsPartner(),
                            autoExpandColumn: 'n_fileas'
                        }, {
                            xtype:'grid',
                            id: 'crm_gridAccount',
                            title:'Internal',
                            cm: this.columnModelDisplayContacts,
                            store: Tine.Crm.LeadEditDialog.Stores.getContactsInternal(),
                            autoExpandColumn: 'n_fileas'
                        }
                    ]
                }]         
            };
            return tabPanel;
        }
    };
}();

Tine.Crm.LeadEditDialog.Stores = function() {
    var _storeContactsInternal = null;
    
    var _storeContactsCustomer = null;
    
    var _storeContactsPartner = null;

    var _storeContactsSearch = null;
    
    // public functions and variables
    return {
        contactFields: [
            {name: 'link_id'},              
            {name: 'link_remark'},                        
            {name: 'id'},
            {name: 'owner'},
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
            {name: 'email'}
        ],
        
        getContactsCustomer: function (_contactsCustomer){  
                 
            if(_storeContactsCustomer === null) {
                _storeContactsCustomer = new Ext.data.JsonStore({
                    id: 'id',
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
                    id: 'id',
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
                    id: 'id',
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
                    id: 'id',
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
                    {name: 'key', mapping: 'id'},
                    {name: 'value', mapping: 'leadstate'},
                    {name: 'probability', mapping: 'probability'},
                    {name: 'endslead', mapping: 'endslead'}
                ]
            });
            
            return store;
        },

        getProductsAvailable: function (){        
            var store = new Ext.data.JsonStore({
                data: formData.comboData.productsource,
                autoLoad: true,
                id: 'id',
                fields: [
                    {name: 'id'},
                    {name: 'value', mapping: 'productsource'},
                    {name: 'price'}
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
                    {name: 'key', mapping: 'id'},
                    {name: 'value', mapping: 'leadtype'}
    
                ]
            });     
            
            return store;
        },
        
        getActivities: function (_tasks){     
            var store = new Ext.data.JsonStore({
                id: 'identifier',
                //fields: Tine.Tasks.Task
                fields: [
                    {name: 'id'},
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
                    {name: 'summary'},
                    {name: 'url'},
                    
                    // temporary extra props
                    {name: 'creator'},
                    {name: 'modifier'},
                  //  {name: 'status_realname'}
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
Tine.Crm.Model = {};

// lead
Tine.Crm.Model.Lead = Ext.data.Record.create([
    {name: 'id',            type: 'int'},
    {name: 'description_ld',          type: 'string'},
    {name: 'leadstate_id',  type: 'int'},
    {name: 'leadtype_id',   type: 'int'},
    {name: 'leadsource_id', type: 'int'},
    {name: 'container',     type: 'int'},
    {name: 'modifier',      type: 'int'},
    {name: 'start',         type: 'date', dateFormat: 'c'},
    {name: 'modified'},
    {name: 'description',   type: 'string'},
    {name: 'end',           type: 'date', dateFormat: 'c'},
    {name: 'turnover',      type: 'int'},
    {name: 'probability',   type: 'int'},
    {name: 'end_scheduled', type: 'date', dateFormat: 'c'},
    {name: 'lastread'},
    {name: 'lastreader'},
    {name: 'leadstate'},
    {name: 'leadtype'},
    {name: 'leadsource'},
  //  {name: 'leadpartner_linkId'},
  //  {name: 'leadpartner'},
  //  {name: 'leadpartner_detail'},                
  //  {name: 'leadlinkId'},
  //  {name: 'leadcustomer'},
  //  {name: 'leaddetail'}  
]);
// work arround nasty ext date bug
Tine.Crm.Model.Lead.FixDates = function(lead) {
    lead.data.start         = lead.data.start         ? Date.parseDate(lead.data.start, 'c')         : lead.data.start;
    lead.data.end           = lead.data.end           ? Date.parseDate(lead.data.end, 'c')           : lead.data.end;
    lead.data.end_scheduled = lead.data.end_scheduled ? Date.parseDate(lead.data.end_scheduled, 'c') : lead.data.end_scheduled;
}
        
