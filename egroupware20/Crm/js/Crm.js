Ext.namespace('Egw.Crm');

Egw.Crm = function() {

    var _initialTree = [{
        "text":"Projects",
        "cls":"treemain",
        "allowDrag":false,
        "allowDrop":true,
        "id":"crm",
        "icon":"images\/oxygen\/16x16\/apps\/package-multimedia.png",
        "application":"Crm",
        "datatype":"projects",
        /*"children":[],*/
        "leaf":null,
        "contextMenuClass":"ctxMenuProject",
        "owner":"all",
    //    "jsonMethod":"Crm.getProjectsByOwner",
        "dataPanelType":"projects"
    }];
    
    var _getCrmTree = function() 
    {
        function dummyHandler(item){
            Ext.example.msg('FUTURE FEATURE', '...soon you will be able to ', item.text);
        }
    
        var settings_tb_menu = new Ext.menu.Menu({
            id: 'crmSettingsMenu',
            items: [
                '-', {
                    text: 'Settings',
                    menu: {       
                        items: [
                            {text: 'Edit Leadstati', handler: dummyHandler},
                            {text: 'Edit Leadsources', handler: dummyHandler},
                            {text: 'Edit Leadtypes', handler: dummyHandler},
                            {text: 'Edit Products', handler: dummyHandler}
                        ]
                    }
                } , {
                    text: 'Admin',
                    menu: {       
                        items: [
                            {text: 'Global Categories', handler: dummyHandler}
                        ]
                    }
                }
            ]
        });
        
      
        var settings_tb = new Ext.Toolbar({
            id: 'settingsCrm',
            split: false,
            height: 26,
            items: [
                settings_tb_menu
            ]
        });    
       
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
                method: 'Crm.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
            _loader.baseParams.datatype = _node.attributes.datatype;
            _loader.baseParams.owner    = _node.attributes.owner;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Crm',
            id: 'crm-tree',
            tbar: settings_tb_menu,
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

        for(i=0; i<_initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
        }
   
        treePanel.on('click', function(_node, _event) {
               this.show();
/*               
        	var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();

                
        	switch(_node.attributes.dataPanelType) {
                case 'lead':
                    Egw.Crm.show();
                    if(currentToolbar != false && currentToolbar.id == 'toolbarCrmLead') {
                        Ext.getCmp('gridCrmLead').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Egw.Crm.Lead.show();
                    }
                    
                    break;
                    
                case 'partner':
                    Egw.Crm.show();
                    if(currentToolbar != false && currentToolbar.id == 'toolbarCrmPartner') {
                    	Ext.getCmp('gridCrmPartner').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Egw.Crm.Partner.show();
                    }
                        
                    break;
                    
                case 'projects':
                    Egw.Crm.show();
                        
                    break;    
                    
            }  */
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() == null) {
                _panel.expandPath('/root/projekte');
                _panel.selectPath('/root/projekte');
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
    
    
    
    var _createDataStore = function()
    {

         var ds_crm = new Ext.data.JsonStore({
            url: 'index.php',
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
        
	  	var action_add = new Ext.Action({
			text: 'add',
			handler: function () {
           //     var tree = Ext.getCmp('venues-tree');
		//		var curSelNode = tree.getSelectionModel().getSelectedNode();
			//	var RootNode   = tree.getRootNode();
            
                Egw.Egwbase.Common.openWindow('CrmProjectWindow', 'index.php?method=Crm.editProject&_projectId=0&_eventId=NULL', 900, 700);
             },
			iconCls: 'action_add'
		});        
        
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarCrm',
            split: false,
            height: 26,
            items: [
                action_add,
                //_action_delete,'->',
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
    //	_action_delete.setDisabled(true);
    	
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying projects {0} - {1} of {2}',
            emptyMsg: "No projects to display"
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
        /*
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_delete.setDisabled(true);
            } else {
                _action_delete.setDisabled(false);
            }
        });
      */  
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: dataStore,
            cm: columnModel,
//            tbar: pagingToolbar,   
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
                _action_delete.setDisabled(false);
            }
            _contextMenuGridAdminAccessLog.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            Egw.Egwbase.Common.openWindow('projectWindow', 'index.php?method=Crm.editProject&_projectId='+record.data.pj_id, 850, 600);            
        });
       
       return;
    }
        
    // public functions and variables
    return {
        show: function(_node) {          
            _showCrmToolbar();
            _showGrid();    
          //  _getCrmTree();
          
        },
        
        getPanel: _getCrmTree
    }
    
}(); // end of application



Egw.Crm.ProjectEditDialog = function() {

    var dialog;
    
    /**
        * the dialog to display the contact data
        */
    var projectedit;
    
    // private functions and variables
    
    var handler_applyChanges = function(_button, _event) 
    {
		
    	var eventForm = Ext.getCmp('eventDialog').getForm();
		eventForm.render();
    	
    	if(eventForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.event_id = formData.values.event_id;
			}
			    		
			eventForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Eventscheduler.reload();
    			},
    			failure:function(form, action) {
    				//Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }

    var handler_saveAndClose = function(_button, _event) 
    {
        var store_products = Ext.getCmp('grid_choosenProducts').getStore();
        var modified_products = store_products.getModifiedRecords();
        
       
        var _getJSONDsRecs = function(_dataSrc) {
            
        if(Ext.isEmpty(_dataSrc)) {
                return false;
            }
                
            var data = _dataSrc.data, dataLen = data.getCount(), jsonData = new Array();            
            for(i=0; i < dataLen; i++) {
                var curRecData = data.itemAt(i).data;
                jsonData.push(curRecData);
            }   

            return Ext.util.JSON.encode(jsonData);
        }

        var modified_products_json = _getJSONDsRecs(store_products);
       
    	var projectForm = Ext.getCmp('projectDialog').getForm();
		projectForm.render();
    	
    	if(projectForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.event_id = formData.values.project_id;
			}
			    		
            additionalData.products = modified_products_json;
                        
			projectForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving event...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Eventscheduler.reload();
    				window.setTimeout("window.close()", 400);
    			},
    			failure:function(form, action) {
    				//Ext.MessageBox.alert("Error",action.result.errorMessage);
    			}
    		});
    	} else {
    		Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
    	}
    }

    var handler_delete = function(_button, _event) 
    {
		var eventIds = Ext.util.JSON.encode([formData.values.event_id]);
			
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Eventscheduler.deleteEvents', 
				_eventIds: eventIds
			},
			text: 'Deleting event...',
			success: function(_result, _request) {
  				window.opener.Egw.Eventscheduler.reload();
   				window.setTimeout("window.close()", 400);
			},
			failure: function ( result, request) { 
				Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.'); 
			} 
		});
        			    		
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
		text: 'delete event',
		handler: handler_delete,
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

        var _action_edit = new Ext.Action({
            text: 'editieren',
            //disabled: true,
            handler: _editHandler,
            iconCls: 'action_edit'
        });
 

        function formatDate(value){
            return value ? value.dateFormat('M d, Y') : '';
        };
    
      
        var st_leadstatus = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Crm.getProjectstate'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'pj_projectstate_id',
            fields: [
                {name: 'pj_projectstate_id'},
                {name: 'pj_projectstate'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        st_leadstatus.load();
        
                
        var st_leadtyp =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Mitbewerber'],
                        ['1','Kunde'],
                        ['2','Partner'],
                        ['3','Presse'],
                        ['4','Prospekt'],
                        ['5','Wiederverkäufer'],
                        ['6','Lieferant'],
                        ['7','anderer Kundentyp']
                    ]
        });
        
        var st_leadsource =  new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','Werbung'],
                        ['1','Tip von Wiederverkäufer'],
                        ['2','InLabBus'],
                        ['3','Messe'],
                        ['4','andere Leadquelle'],
                        ['5','Kaltaquise'],
                        ['6','Empfehlung'],
                        ['7','Arbeitskreise'],
                        ['8','Telefonkontakt'],
                        ['9','Kongress']
                    ]
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
            url: 'index.php',
            //baseParams: getParameterContactsDataStore(_node),
            root: 'results',
            totalProperty: 'totalcount',
            id: 'contact_id',
            fields: [
                {name: 'contact_id'},
                {name: 'contact_tid'},
                {name: 'contact_owner'},
                {name: 'contact_private'},
                {name: 'cat_id'},
                {name: 'n_family'},
                {name: 'n_given'},
                {name: 'n_middle'},
                {name: 'n_prefix'},
                {name: 'n_suffix'},
                {name: 'n_fn'},
                {name: 'n_fileas'},
                {name: 'contact_bday'},
                {name: 'org_name'},
                {name: 'org_unit'},
                {name: 'contact_title'},
                {name: 'contact_role'},
                {name: 'contact_assistent'},
                {name: 'contact_room'},
                {name: 'adr_one_street'},
                {name: 'adr_one_street2'},
                {name: 'adr_one_locality'},
                {name: 'adr_one_region'},
                {name: 'adr_one_postalcode'},
                {name: 'adr_one_countryname'},
                {name: 'contact_label'},
                {name: 'adr_two_street'},
                {name: 'adr_two_street2'},
                {name: 'adr_two_locality'},
                {name: 'adr_two_region'},
                {name: 'adr_two_postalcode'},
                {name: 'adr_two_countryname'},
                {name: 'tel_work'},
                {name: 'tel_cell'},
                {name: 'tel_fax'},
                {name: 'tel_assistent'},
                {name: 'tel_car'},
                {name: 'tel_pager'},
                {name: 'tel_home'},
                {name: 'tel_fax_home'},
                {name: 'tel_cell_private'},
                {name: 'tel_other'},
                {name: 'tel_prefer'},
                {name: 'contact_email'},
                {name: 'contact_email_home'},
                {name: 'contact_url'},
                {name: 'contact_url_home'},
                {name: 'contact_freebusy_uri'},
                {name: 'contact_calendar_uri'},
                {name: 'contact_note'},
                {name: 'contact_tz'},
                {name: 'contact_geo'},
                {name: 'contact_pubkey'},
                {name: 'contact_created'},
                {name: 'contact_creator'},
                {name: 'contact_modified'},
                {name: 'contact_modifier'},
                {name: 'contact_jpegphoto'},
                {name: 'account_id'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        st_contacts.setDefaultSort('n_family', 'asc');
        
        st_contacts.on('beforeload', function(_st_contacts) {
            _st_contacts.baseParams.datatype = 'allcontacts';
            _st_contacts.baseParams.method = 'Crm.getContactsByOwner';
            _st_contacts.baseParams.owner = 'allcontacts';
        });   
        
        
        var tpl_contacts = new Ext.Template(
            '<div class="search-item">',
                '<h3>{contact_id} , {n_fileas}</h3>',
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
  
        var st_choosenProducts = new Ext.data.SimpleStore({
                fields: ['id','product_id','product_price','product_serial'],
                data: [
                        ['1','2','1500','254234231da2'],
                        ['2','4','2100','fqw2323g2f3'],
                        ['3','8','2800','sf323fq23f']
                    ]
        });
 
  
        var st_productsAvailable = new Ext.data.SimpleStore({
                fields: ['key','value'],
                data: [
                        ['0','CEREC AE'],
                        ['1','CEREC MC XL'],
                        ['2','CEREC 3 SE'],
                        ['3','CEREC PPU'],
                        ['4','Inlab MC XL'],
                        ['5','Inlab'],
                        ['6','InEOS'],
                        ['7','InFire'],
                        ['8','InCoris'],
                        ['9','InFinident'],
                        ['10','PC']
                    ],
                id: 'key'
        });  
  
        function renderProductCombo(value) {
            if(value) return st_productsAvailable.getAt(value).get('value');
        }
  
        var cm_choosenProducts = new Ext.grid.ColumnModel([{
                header: "Produkt",
                dataIndex: 'product_id',
                width: 300,
                editor: new Ext.form.ComboBox({
                    name: 'product_combo',
                    hiddenName: 'product_id',
                    store: st_productsAvailable, 
                    displayField:'value', 
                    valueField: 'key',
                    allowBlank: false, 
                    editable: false,
                    forceSelection: true, 
                    triggerAction: "all", 
                    mode: 'local', 
                    lazyRender:true,
                    listClass: 'x-combo-list-small'
                    }),
                renderer: renderProductCombo
                } , { 
                header: "Seriennummer",
                dataIndex: 'product_serial',
                width: 300,
                editor: new Ext.form.TextField({
                    allowBlank: false
                    })
                } , {
                header: "Preis",
                dataIndex: 'product_price',
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
        
        
        
        var product = Ext.data.Record.create([
           {name: 'id', type: 'int'},
           {name: 'product_id', type: 'int'},
           {name: 'product_serial', type: 'string'},
           {name: 'product_price', type: 'float'}
        ]);
        
        var grid_choosenProducts = new Ext.grid.EditorGridPanel({
            store: st_choosenProducts,
            id: 'grid_choosenProducts',
            cm: cm_choosenProducts,
            anchor: '100% 100%',
//            autoExpandColumn:'common',
            frame:false,
            clicksToEdit:1,
            tbar: [{
                text: 'Produkt hinzufügen',
                handler : function(){
                    var p = new product({
                        id: '-1',
                        product_id: '',                       
                        product_serial:'',
                        product_price: 0
                    });
                    grid_choosenProducts.stopEditing();
                    st_choosenProducts.insert(0, p);
                    grid_choosenProducts.startEditing(0, 0);
                }
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
  
		var projectedit = new Ext.FormPanel({
			url:'index.php',
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
                                        items: [{
                                            xtype:'combo',
                                            fieldLabel:'Leadstatus', 
                                            name:'pj_leadstatus',
                                            hiddenName:'pj_distributionphase_id',
            								store: st_leadstatus,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'96%'
                                        } , {
                                            xtype:'combo',
                                            fieldLabel:'Leadtyp', 
                                            name:'pj_leadtyp',
                                            hiddenName:'pj_customertype_id',
            								store: st_leadtyp,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'96%'
                                        }]
                                    } , {                               
                                        columnWidth:.5,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'combo',
                                            fieldLabel:'Leadquelle', 
                                            name:'pj_leadsource',
                                            hiddenName:'pj_leadsource_id',
            								store: st_leadsource,
            								displayField:'value',
            								valueField:'key',
            								typeAhead: true,
            								mode: 'local',
            								triggerAction: 'all',
            								emptyText:'',
            								selectOnFocus:true,
            								editable: false,
            								anchor:'100%'
                                        } , {
                                            xtype:'combo',
                                            fieldLabel:'Verantwortlicher', 
                                            name:'pj_owner',
            								store: st_owner,
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
                                } , {
                                    xtype:'textarea',
                                    fieldLabel:'Notizen', 
                                    hideLabel: true,
                                    name:'pj_notes',
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
                                            name:'startDate',
                                            id:'startDate',
                                            format: 'd.m.Y',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'Ende', 
                                            name:'endDate',
                                            id:'endDate',
                                            format: 'd.m.Y',
                                            anchor:'95%'
                                        }]
                                    } , {
                                        columnWidth:.33,
                                        layout: 'form',
                                        border:false,
                                        items: [{
                                            xtype:'datefield',
                                            fieldLabel:'voraussichtl. Ende', 
                                            name:'exprectedEndDate',
                                            id:'expectedEndDate',
                                            format: 'd.m.Y',
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
                                        alert(st_contacts.getById(0));
                            			//alert(record);
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

    var setContactDialogValues = function(_formData) {
    	var form = Ext.getCmp('projectDialog').getForm();
    	
    	form.setValues(_formData);
    	
  //  	form.findField('contact_owner_name').setRawValue(formData.config.addressbookName);
    	
    	if(formData.config.oneCountryName) {
    		//console.log('set adr_one_countryname to ' + formData.config.oneCountryName);
	    	form.findField('adr_one_countryname').setRawValue(formData.config.oneCountryName);
    	}

    	if(formData.config.twoCountryName) {
    		//console.log('set adr_two_countryname to ' + formData.config.twoCountryName);
	    	form.findField('adr_two_countryname').setRawValue(formData.config.twoCountryName);
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
                setContactDialogValues(formData.values);
            }
        }
        
    }
    
}(); // end of application