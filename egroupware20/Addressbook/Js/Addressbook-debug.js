Ext.namespace('Egw.Addressbook');

Egw.Addressbook = function() {

	/**
	 * the datastore for contacts and lists
	 */
    var ds_contacts;

    /**
     * state of the filterUser button
     */
    var displayContactsButtonState = true;
    
    /**
     * state of the filterLists button
     */
    var displayListsButtonState = false;

	/**
	 * the currently active node on the left tree
	 */
    var currentTreeNode;
    
    /**
     * the grid which displays the contacts/lists
     */
    var contactGrid;
    
    // private functions and variables
    var _setParameter = function(_dataSource)
    {
    	if(!currentTreeNode) {
    		currentTreeNode = Ext.getCmp('contacts-tree').getSelectionModel().getSelectedNode();
    	}
    	
        switch(currentTreeNode.attributes.datatype) {
            case 'list':
                _dataSource.baseParams.listId = currentTreeNode.attributes.listId;
                _dataSource.baseParams.method = 'Addressbook.getList';
               
                break;
                
            case 'contacts':
            case 'otherpeople':
            case 'sharedaddressbooks':
                _dataSource.baseParams.method = 'Addressbook.getContacts';
                 
                break;

            case 'overview':
                _dataSource.baseParams.method = 'Addressbook.getOverview';
                
                break;
        }
        
    	_dataSource.baseParams.datatype = currentTreeNode.attributes.datatype;
    	_dataSource.baseParams.owner = currentTreeNode.attributes.owner;    	
        _dataSource.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
    }
    
    /**
     * onclick handler for addBtn
     */
    var _addBtnHandler = function(_button, _event) {
        openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
    }
	
	/**
	 * onclick handler for addLstBtn
	 */
    var _addLstBtnHandler = function(_button, _event) {
        openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=', 450, 600);
    }	
	
    /**
     * onclick handler for deleteBtn
     */
    var _deleteBtnHandler = function(_button, _event) {
        var contactIds = Array();
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            contactIds.push(selectedRows[i].id);
        }
		
		contactIds = Ext.util.JSON.encode(contactIds);
		
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Addressbook.deleteContacts', 
				_contactIds: contactIds
			},
			text: 'Deleting contact...',
			success: function(_result, _request) {
  				ds_contacts.reload();
			},
			failure: function ( result, request) { 
				Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.'); 
			} 
		});
    }
	
    /**
     * onclick handler for editBtn
     */
    var _editBtnHandler = function(_button, _event) {
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        var contactId = selectedRows[0].id;
		
		if(selectedRows[0].data.contact_tid == 'l') {
    	    openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + contactId, 450, 600);
		}
		else {
	        openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + contactId, 850, 600);
		}
    }
    
    var searchFieldHandler = function(_textField, _value) {
		ds_contacts.reload();
    }
    
   	var action_addContact = new Ext.Action({
		text: 'add contact',
		handler: _addBtnHandler,
		iconCls: 'action_addContact'
	});

   	var action_addList = new Ext.Action({
		text: 'add list',
		handler: _addLstBtnHandler,
		iconCls: 'action_addList'
	});

   	var action_edit = new Ext.Action({
		text: 'edit',
		disabled: true,
		handler: _editBtnHandler,
		iconCls: 'action_edit'
	});
		
   	var action_delete = new Ext.Action({
		text: 'delete',
		disabled: true,
		handler: _deleteBtnHandler,
		iconCls: 'action_delete'
	});
	
	
	
    var _showContactToolbar = function()
    {
    	var northPanel = Ext.getCmp('north-panel');
    	var toolbarPanel = Ext.getCmp('applicationToolbar');
    	
    	northPanel.remove(toolbarPanel);

		var quickSearchField = new Ext.app.SearchField({
			id: 'quickSearchField',
			width:240,
			emptyText: 'enter searchfilter'
		}); 
		quickSearchField.on('change', searchFieldHandler);
		
        var contactToolbar = new Ext.Toolbar({
        	region: 'south',
          	id: 'applicationToolbar',
			split: false,
			height: 26,
			items: [
				action_addContact, 
				action_addList,
				action_edit,
				action_delete,
				'->', 'Search:', ' ',
/*    			new Ext.ux.SelectBox({
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

		northPanel.add(contactToolbar);
        northPanel.doLayout();
		
		return;
    }


    /**
     * creates the address grid
     *
     */
    var _getContactTree = function() 
    {
		var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method    = 'Addressbook.getSubTree';
            _loader.baseParams._node     = _node.id;
            _loader.baseParams._datatype = _node.attributes.datatype;
            _loader.baseParams._owner    = _node.attributes.owner;
            _loader.baseParams._location = 'mainTree';
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
        	title: 'Contacts',
            id: 'contacts-tree',
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

        for(i=0; i<initialTree.length; i++) {
        	treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
        	currentTreeNode = _node;
        	ds_contacts.reload();
        }, this);
        
        treePanel.on('expand', function(_panel) {
        	if(_panel.getSelectionModel().getSelectedNode() == null) {
        		_panel.expandPath('/root/alllists');
				_panel.selectPath('/root/addressbook');
        	}
			_showContactToolbar();
			_showContactGrid(_panel.getSelectionModel().getSelectedNode());
        }, this);

		return treePanel;
    }






    /**
     * creates the address grid
     *
     */
    var _showContactTree = function() 
    {
        //get container to which component will be added
        var westPanel = Ext.getCmp('west');
        if(westPanel.items) {
            for (var i=0; i<westPanel.items.length; i++){
                westPanel.remove(westPanel.items.get(i));
            }  
        }

		var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php'
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.method    = 'Addressbook.getSubTree';
            _loader.baseParams._node     = _node.id;
            _loader.baseParams._datatype = _node.attributes.datatype;
            _loader.baseParams._owner    = _node.attributes.owner;
            _loader.baseParams._location = 'mainTree';
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            id: 'contact-tree',
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

        for(i=0; i<initialTree.length; i++) {
        	treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
        	currentTreeNode = _node;
        	ds_contacts.reload();
        }, this);

        westPanel.add(treePanel);
        westPanel.show();
        westPanel.doLayout();

        treePanel.expandPath('/root/alllists');
        treePanel.selectPath('/root/addressbook');
        
        //westPanel.hide();

		return;
/*        
        // handle right mouse click
        tree.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            ctxTreeMenu.showAt(_event.getXY());
        });
*/

        
		var treeRoot = treePanel.getRootNode();
		var oldNodes = Array();
		treeRoot.eachChild(function (_node) {
			oldNodes.push(_node);
		}, this);

/*        var root2 = new Ext.tree.TreeNode({
            text: 'root2',
            draggable:false,
            allowDrop:false,
            id:'root2'
        });*/
        //treeRoot.appendChild(root2);
		
		//treePanel.render();
		//treeRoot.expand();
        treeRoot.appendChild(new Ext.tree.AsyncTreeNode(initialTree));
        treePanel.expandPath('/root/addressbook');
        treePanel.selectPath('/root/addressbook');
        
        treePanel.on('click', function(node) {
        	ds_contacts.reload();
        });
        
        //Ext.each(oldNodes, function(_node){_node.remove();}, this);
        
        //treePanel.expandPath('/root/root2');
    }
    
    /**
     * creates the address grid
     *
     */
    var _showContactGrid = function() 
    {
        //get container to which component will be added
        var container = Ext.getCmp('center-panel');
        if(container.items) {
            for (var i=0; i<container.items.length; i++){
                container.remove(container.items.get(i));
            }  
        }

		/**
		 * the datastore for contacts and lists
		 */
	    ds_contacts = new Ext.data.JsonStore({
	        url: 'index.php',
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
        
        ds_contacts.setDefaultSort('n_family', 'asc');

		// work around for bug 
		// loading data by json is not working if loadData is not done before
        ds_contacts.loadData({"results":[],"totalcount":"0","status":"success"});

		ds_contacts.on('beforeload', _setParameter);
		
        ds_contacts.load({params:{start:0, limit:50}});

        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 25,
            store: ds_contacts,
            displayInfo: true,
			displayMsg: 'Displaying contacts {0} - {1} of {2}',
			emptyMsg: "No contacts to display"
        }); 
        
        var cm_contacts = new Ext.grid.ColumnModel([
            { resizable: true, id: 'contact_tid', header: 'Type', dataIndex: 'contact_tid', width: 30, renderer: _renderContactTid },
            { resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family' },
            { resizable: true, id: 'n_given', header: 'Given name', dataIndex: 'n_given', width: 80 },
            { resizable: true, id: 'n_fn', header: 'Full name', dataIndex: 'n_fn', hidden: true },
            { resizable: true, id: 'n_fileas', header: 'Name + Firm', dataIndex: 'n_fileas', hidden: true },
            { resizable: true, id: 'contact_email', header: 'eMail', dataIndex: 'contact_email', width: 150, hidden: false },
            { resizable: true, id: 'contact_bday', header: 'Birthday', dataIndex: 'contact_bday', hidden: true },
            { resizable: true, id: 'org_name', header: 'Organisation', dataIndex: 'org_name', width: 150 },
            { resizable: true, id: 'org_unit', header: 'Unit', dataIndex: 'org_unit' , hidden: true },
            { resizable: true, id: 'contact_title', header: 'Title', dataIndex: 'contact_title', hidden: true },
            { resizable: true, id: 'contact_role', header: 'Role', dataIndex: 'contact_role', hidden: true },
            { resizable: true, id: 'contact_room', header: 'Room', dataIndex: 'contact_room', hidden: true },
            { resizable: true, id: 'adr_one_street', header: 'Street', dataIndex: 'adr_one_street', hidden: true },
            { resizable: true, id: 'adr_one_locality', header: 'Locality', dataIndex: 'adr_one_locality', width: 80, hidden: false },
            { resizable: true, id: 'adr_one_region', header: 'Region', dataIndex: 'adr_one_region', hidden: true },
            { resizable: true, id: 'adr_one_postalcode', header: 'Postalcode', dataIndex: 'adr_one_postalcode', hidden: true },
            { resizable: true, id: 'adr_one_countryname', header: 'Country', dataIndex: 'adr_one_countryname', hidden: true },
            { resizable: true, id: 'adr_two_street', header: 'Street (private)', dataIndex: 'adr_two_street', hidden: true },
            { resizable: true, id: 'adr_two_locality', header: 'Locality (private)', dataIndex: 'adr_two_locality', hidden: true },
            { resizable: true, id: 'adr_two_region', header: 'Region (private)', dataIndex: 'adr_two_region', hidden: true },
            { resizable: true, id: 'adr_two_postalcode', header: 'Postalcode (private)', dataIndex: 'adr_two_postalcode', hidden: true },
            { resizable: true, id: 'adr_two_countryname', header: 'Country (private)', dataIndex: 'adr_two_countryname', hidden: true },
            { resizable: true, id: 'tel_work', header: 'Phone', dataIndex: 'tel_work', hidden: false },
            { resizable: true, id: 'tel_cell', header: 'Cellphone', dataIndex: 'tel_cell', hidden: false },
            { resizable: true, id: 'tel_fax', header: 'Fax', dataIndex: 'tel_fax', hidden: true },
            { resizable: true, id: 'tel_car', header: 'Car phone', dataIndex: 'tel_car', hidden: true },
            { resizable: true, id: 'tel_pager', header: 'Pager', dataIndex: 'tel_pager', hidden: true },
            { resizable: true, id: 'tel_home', header: 'Phone (private)', dataIndex: 'tel_home', hidden: true },
            { resizable: true, id: 'tel_fax_home', header: 'Fax (private)', dataIndex: 'tel_fax_home', hidden: true },
            { resizable: true, id: 'tel_cell_private', header: 'Cellphone (private)', dataIndex: 'tel_cell_private', hidden: true },
            { resizable: true, id: 'contact_email_home', header: 'eMail (private)', dataIndex: 'contact_email_home', hidden: true },
            { resizable: true, id: 'contact_url', header: 'URL', dataIndex: 'contact_url', hidden: true },
            { resizable: true, id: 'contact_url_home', header: 'URL (private)', dataIndex: 'contact_url_home', hidden: true },
            { resizable: true, id: 'contact_note', header: 'Note', dataIndex: 'contact_note', hidden: true },
            { resizable: true, id: 'contact_tz', header: 'Timezone', dataIndex: 'contact_tz', hidden: true },
            { resizable: true, id: 'contact_geo', header: 'Geo', dataIndex: 'contact_geo', hidden: true }
        ]);
        
        cm_contacts.defaultSortable = true; // by default columns are sortable
        
        contactGrid = new Ext.grid.GridPanel({
            store: ds_contacts,
            cm: cm_contacts,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
            enableColLock:false,
            /*loadMask: true,*/
            autoExpandColumn: 'n_family',
            border: false
        });
		
        container.add(contactGrid);
        container.show();
        container.doLayout();

		contactGrid.on('rowclick', function(gridP, rowIndexP, eventP) {
			var rowCount = contactGrid.getSelectionModel().getCount();
            
			if(rowCount < 1) {
				action_edit.setDisabled(true);
				action_delete.setDisabled(true);
			} else if(rowCount == 1) {
				action_edit.setDisabled(false);
				action_delete.setDisabled(false);
			} else {
				action_edit.setDisabled(true);
				action_delete.setDisabled(false);
			}
		});
		
		contactGrid.on('rowcontextmenu', function(grid, rowIndex, eventObject) {
			eventObject.stopEvent();
			var record = grid.getStore().getAt(rowIndex);
			ctxMenuGrid.showAt(eventObject.getXY());
		});
		
		contactGrid.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
			var record = _gridPar.getStore().getAt(_rowIndexPar);
			//console.log('id: ' + record.data.contact_id);
			if(record.data.contact_tid == 'l') {
                try {
                    openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + record.data.contact_id, 450, 600);
                } catch(e) {
                //  alert(e);
                }
			} else {
				try {
					openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.data.contact_id, 850, 600);
				} catch(e) {
					// alert(e);
				}
			}
		});
        
        return;
        
        textF1 = new Ext.form.TextField({
            height: 22,
		    width: 200,
		    emptyText:'Suchparameter ...', 
		    allowBlank:false
        });

		textF1.on('specialkey', function(_this, _e) {        
            if(_e.getKey() == _e.ENTER || _e.getKey() == e.RETURN ){
                //contactDS.reload();
                //contactDS.removeAll();
                //contactDS.load({params:{
                //	start:0, 
                //    limit:50,
                //    query:_this.getValue()
                //}});         
            }
        });
	}
    
	var _renderContactTid = function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
        switch(_data) {
            case 'l':
                    return "<img src='images/oxygen/16x16/actions/users.png' width='12' height='12' alt='list'/>";
            default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
        }
    }
    
    var _displayContactsBtnHandler = function(_button, _event) {
    	displayContactsButtonState = _button.pressed;
        ds_contacts.reload();
    }
    var _displayListsBtnHandler = function(_button, _event) {
    	displayListsButtonState = _button.pressed;
        ds_contacts.reload();
    }
	
    /**
     * contextmenu for contact grid
     *
     */
    var ctxMenuGrid = new Ext.menu.Menu({
        id:'ctxMenuAddress', 
        items: [
	        action_edit,
	        action_delete,
	        '-',
			action_addContact, 
			action_addList
		]
    });
	
    var _exportBtnHandler = function(_button, _event) {
    }
    
    var openWindow = function(_windowName, _url, _width, _height) 
    {
        if (document.all) {
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else if (window.innerWidth) {
            w = window.innerWidth;
            h = window.innerHeight;
            x = window.screenX;
            y = window.screenY;
        }
        var leftPos = ((w - _width)/2)+y; 
        var topPos = ((h - _height)/2)+x;

        var popup = window.open(
            _url, 
            _windowName,
            'width=' + _width + ',height=' + _height + ',top=' + topPos + ',left=' + leftPos +
            ',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no'
        );
        
        return popup;
    }

    /**
     * opens up a new window to add/edit a contact
     *
     */
    var _openDialog = function(_id,_dtype) {
        var url;
        var w = 1024, h = 786;
        var popW = 850, popH = 600;
        
		if(_dtype == 'list') {
			popW = 450, popH = 600;		
		}
	
        if (document.all) {
            /* the following is only available after onLoad */
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else if (window.innerWidth) {
            w = window.innerWidth;
            h = window.innerHeight;
            x = window.screenX;
            y = window.screenY;
        }
        var leftPos = ((w-popW)/2)+y, topPos = ((h-popH)/2)+x;
        
        if(_dtype == 'list' && !_id) {
            url = 'index.php?method=Addressbook.editList';
        }
		else if(_dtype == 'list' && _id){
           url = 'index.php?method=Addressbook.editList&contactid=' + _id;
        }
		else if(_dtype != 'list' && _id){
           url = 'index.php?method=Addressbook.editContact&contactid=' + _id;
        }
		else {
            url = 'index.php?method=Addressbook.editContact';
        }
        //console.log(url);
        appId = 'addressbook';
        var popup = window.open(
            url, 
            'popupname',
            'width='+popW+',height='+popH+',top='+topPos+',left='+leftPos+',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no'
        );
        
        return;
    }
	
    /**
     * reload main window
     *
     */
    var _reloadMainWindow = function(closeCurrentWindow) {
        closeCurrentWindow = (closeCurrentWindow == null) ? false : closeCurrentWindow;
        
        window.opener.Egw.Addressbook.reload();
        if(closeCurrentWindow == true) {
            window.setTimeout("window.close()", 400);
        }
    }
	
    /**
     * displays the addressbook select dialog
     * shared between contact and list edit dialog
     */
    var _displayAddressbookSelectDialog = function(_onClickCallback){         
                
		if(!addressBookDialog) {
                   
            //################## listView #################

        var addressBookDialog = new Ext.Window({
					title: 'please select addressbook',
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
			
						
            var Tree = Ext.tree;
                
            treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
            treeLoader.on("beforeload", function(_loader, _node) {
                _loader.baseParams.method       = 'Addressbook.getSubTree';
                _loader.baseParams._node        = _node.id;
                _loader.baseParams._datatype    = _node.attributes.datatype;
                _loader.baseParams._owner       = _node.attributes.owner;
                _loader.baseParams._location    = 'selectFolder';
            }, this);
                            
            var tree = new Tree.TreePanel({
                animate:true,
				id: 'addressbookTree',
                loader: treeLoader,
                containerScroll: true,
                rootVisible:false
            });
            
            // set the root node
            var root = new Tree.TreeNode({
                text: 'root',
                draggable:false,
                allowDrop:false,
                id:'root'
            });
            tree.setRootNode(root);             
            
            // add the initial tree nodes    
            Ext.each(application, function(_treeNode) {
                root.appendChild(new Tree.AsyncTreeNode(_treeNode));                    
            });

          
          
            tree.on('click', function(_node) {
			     _node.select();
                if(tree.getSelectionModel().getSelectedNode()) {                
                    var cnode = tree.getSelectionModel().getSelectedNode().id;
                    var addressbook_id = tree.getNodeById(cnode).attributes.owner;  
                
                    if( (addressbook_id > 0) || (addressbook_id < 0) ) {
                   //     _onClickCallback(addressbook_id, addressbook_id);
						var addressbook_ = Ext.getCmp('addressbook_');
						addressbook_.setValue(addressbook_id);
                        //addressedit.setValues([{id:'contact_owner', value:addressbook_id}]);
                        addressBookDialog.hide();
                    } else {
                        Ext.MessageBox.alert('wrong selection','please select a valid addressbook');
                    }
                } else {
                    Ext.MessageBox.alert('no selection','please select an addressbook');
                }
            });

            
		addressBookDialog.add(tree);

		addressBookDialog.show();   			
			
	
		}
                
    }
    
	
    // public functions and variables
    return {
        // public functions
        show: function(_node) {
        	_showContactToolbar();
        	_showContactTree();
        	_showContactGrid(_node);
        },
        displayAddressbookSelectDialog: _displayAddressbookSelectDialog,
        
        getPanel: _getContactTree,
        
        reload: function() {
            ds_contacts.reload();
        }
    }
	
}(); // end of application


/**
 * all function to handle the contact edit dialog
 *
 */

Egw.Addressbook.ContactEditDialog = function() {

    var listGrid;
    
    var dialog;
    
    /**
     * the dialog to display the contact data
     */
    var addressedit;
    
    // private functions and variables
    
    var handler_applyChanges = function(_button, _event) 
    {
		
    	var contactForm = Ext.getCmp('contactDialog').getForm();
		contactForm.render();
    	
    	if(contactForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.contact_id = formData.values.contact_id;
			}
			    		
			contactForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving contact...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Addressbook.reload();
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
    	var contactForm = Ext.getCmp('contactDialog').getForm();
		contactForm.render();
    	
    	if(contactForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.contact_id = formData.values.contact_id;
			}
			    		
			contactForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving contact...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Addressbook.reload();
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

    var handler_deleteContact = function(_button, _event) 
    {
		var contactIds = Ext.util.JSON.encode([formData.values.contact_id]);
			
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Addressbook.deleteContacts', 
				_contactIds: contactIds
			},
			text: 'Deleting contact...',
			success: function(_result, _request) {
  				window.opener.Egw.Addressbook.reload();
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

   	var action_deleteContact = new Ext.Action({
		text: 'delete contact',
		handler: handler_deleteContact,
		iconCls: 'action_delete'
	});

    /**
     * display the contact edit dialog
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
        
        var contactToolbar = new Ext.Toolbar({
        	region: 'south',
          	id: 'applicationToolbar',
			split: false,
			height: 26,
			items: [
				action_saveAndClose,
				action_applyChanges,
				action_deleteContact
			]
		});

        var ds_country = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {method:'Egwbase.getCountryList'},
            root: 'results',
            id: 'shortName',
            fields: ['shortName', 'translatedName'],
            remoteSort: false
        });

		var addressbookTrigger = new Ext.form.TriggerField({
            fieldLabel:'Addressbook', 
            name:'contact_owner', 
			id: 'addressbook_',
            anchor:'95%',
            readOnly:true
        });

        addressbookTrigger.onTriggerClick = function() {
            Egw.Addressbook.displayAddressbookSelectDialog(_onAddressSelect);
        }
		
		var _setParameter = function(_dataSource)
		{
                _dataSource.baseParams.method = 'Addressbook.getContacts';
                _dataSource.baseParams.options = Ext.encode({
                    displayContacts: false,
                    displayLists:    true
                });
        }
        
		var states = [
			['AL', 'Alabama'],
			['AK', 'Alaska'],
			['AZ', 'Arizona'],
			['WV', 'West Virginia'],
			['WI', 'Wisconsin'],
			['WY', 'Wyoming']
		];

		var lists_store = new Ext.data.SimpleStore({
			fields: ['contact_id', 'contact_tid'],
			data : states
		});

		var lists_store2 = new Ext.data.SimpleStore({
			fields: ['contact_id', 'contact_tid']
		});
/*
		var lists_store =  new Ext.data.JsonStore({
           url: 'index.php',
           baseParams: {
               datatype: 'contacts',
               owner:    '0,9', 
               sort: 'n_family',
               dir: 'ASC',
               query:   ''
           },
           root: 'results',
           totalProperty: 'totalcount',
           id: 'contact_id',
           fields: [
               {name: 'contact_id'},
               {name: 'contact_tid'},
               {name: 'contact_owner'},
               {name: 'contact_private'},
               {name: 'n_family'},
               {name: 'n_given'},
               {name: 'org_name'},
               {name: 'contact_note'}
           ],
           // turn on remote sorting
           remoteSort: true
        });
		
		lists_store.setDefaultSort('n_family', 'asc');

		lists_store.on('beforeload', _setParameter);
		
		lists_store.load();
*/	
    	
		var view = new Ext.DataView({
			style:'overflow:auto',
		    multiSelect: true,
			id: 'list_source_',
			cls: 'x-list-small',			
			selectedClass: 'x-list-selected',
			itemSelector: 'div.x-view',
		    //  plugins: new Ext.DataView.DragSelector({dragSafe:true}),
		    store: lists_store,
		    tpl: new Ext.XTemplate(
		            '<tpl for=".">',
		            '<div class="x-view" id="{contact_id}">{contact_tid}</div>',
		            '</tpl>'
		    )
		});

		function Numsort (a, b) {
				return a - b;
		}
		
		
		var add_list = function(_dataview, _index, _htmlNode, _event){
			//console.log(_index);
				var _index = Ext.getCmp('list_source_');
				var _listitems = String(_index.getSelectedIndexes());
				var _selected_items = _index.getSelectionCount();
				
				if(_index.getSelectionCount() > '1')  {
					var _litems = _listitems.split(",");
				 } else if(_index.getSelectionCount() == '1')  {
					var _litems = new Array(_listitems);
				 }

					_litems.sort(Numsort);
					_litems.reverse();
				 
				for(i=0; i<_selected_items; i++)
				{	
					var record = lists_store.getAt(_litems[i]);
					lists_store2.add(record);	
				}
					lists_store2.sort('contact_tid', 'ASC');
					
										
				for(i=0; i<_selected_items; i++)
				{	
					var record = lists_store.getAt(_litems[i]);
					lists_store.remove(record);
				}
					lists_store.sort('contact_tid', 'ASC');					
		};
		
		var remove_list = function(_dataview, _index, _htmlNode, _event){
			//console.log(_index);
				var _index = Ext.getCmp('list_selected_');
				var _listitems = String(_index.getSelectedIndexes());
				var _selected_items = _index.getSelectionCount();
				
				if(_index.getSelectionCount() > '1')  {
					var _litems = _listitems.split(",");
				 } else if(_index.getSelectionCount() == '1')  {
					var _litems = new Array(_listitems);
				 }
					_litems.sort(Numsort);
					_litems.reverse();				 
				
				for(i=0; i<_selected_items; i++)
				{	
					var record = lists_store2.getAt(_litems[i]);
					lists_store.add(record);	
				}
					lists_store.sort('contact_tid', 'ASC');
					
				for(i=0; i<_selected_items; i++)
				{	
					var record = lists_store2.getAt(_litems[i]);
					lists_store2.remove(record);
				}
					lists_store2.sort('contact_tid', 'ASC');					
		};
		
		view.on('dblclick', add_list);	

		var view2 = new Ext.DataView({
			style:'overflow:auto',
		    multiSelect: true,
			id: 'list_selected_',
			cls: 'x-list-small',			
			selectedClass: 'x-list-selected',
			itemSelector: 'div.x-view',
		    //  plugins: new Ext.DataView.DragSelector({dragSafe:true}),
		    store: lists_store2,
		    tpl: new Ext.XTemplate(
		            '<tpl for=".">',
		            '<div class="x-view" id="{contact_id}">{contact_tid}</div>',
		            '</tpl>'
		    )
		});

		view2.on('dblclick', remove_list);	
			
		var list_source_box = new Ext.Panel({
			id:'list_source',
		    title:'available lists',
		    region:'center',
		    margins: '5 5 5 0',
		    layout:'fit',
		    items: view
		});

		var list_selected_box = new Ext.Panel({
		    id:'list_selected',
		    title:'chosen lists',
		    region:'center',
		    margins: '5 5 5 0',
		    layout:'fit',
		    items: view2
		});

		var addressedit = new Ext.FormPanel({
				url:'index.php',
				baseParams: {method :'Addressbook.saveContact'},
			    labelAlign: 'top',
				bodyStyle:'padding:5px',
				anchor:'100%',
				region: 'center',
	            id: 'contactDialog',
				tbar: contactToolbar, 
				deferredRender: false,
                items: [{
		            layout:'column',
		            border:false,
					deferredRender:false,
					anchor:'100%',
		            items:[{
		                columnWidth:.4,
		                layout: 'form',
		                border:false,
		                items: [{
		                    xtype:'textfield',
		                    fieldLabel:'First Name', 
							name:'n_given',
		                    anchor:'95%'
		                }, {
		                    xtype:'textfield',
		                    fieldLabel:'Middle Name', 
							name:'n_middle',
		                    anchor:'95%'
		                }, {
							xtype:'textfield',
		                    fieldLabel:'Last Name', 
							name:'n_family', 
							allowBlank:false,
		                    anchor:'95%'
						}]
		            },{
		                columnWidth:.2,
		                layout: 'form',
		                border:false,
		                items: [{
		                    xtype:'textfield',
							fieldLabel:'Prefix', 
							name:'n_prefix',
		                    anchor:'95%'
		                },{
		                    xtype:'textfield',
							fieldLabel:'Suffix', 
							name:'n_suffix',
		                    anchor:'95%'
		                }, addressbookTrigger ]
		            }, {
		                columnWidth:.4,
		                layout: 'form',
		                border:false,
		                items: [{
		                    xtype:'textarea',
							name: 'contact_note',
							fieldLabel: 'Notes',
							grow: false,
							preventScrollbars:false,
							anchor:'95% 85%'
		                }]
		            }]
		        },{
		            xtype:'tabpanel',
		            plain:true,
		            activeTab: 0,
					deferredRender:false,
		            anchor:'100% 70%',
		            defaults:{bodyStyle:'padding:10px'},
		            items:[{
		                title:'Business information',
		                layout:'column',
						deferredRender:false,
						border:false,
						items:[{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [{
								xtype:'textfield',
								fieldLabel:'Company', 
								name:'org_name',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Street', 
								name:'adr_one_street',  
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Street 2', 
								name:'adr_one_street2',  
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Postalcode', 
								name:'adr_one_postalcode',  
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'City', 
								name:'adr_one_locality',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Region', 
								name:'adr_one_region',
								anchor:'95%'
							},  
							new Ext.form.ComboBox({
								fieldLabel: 'Country',
								name: 'adr_one_countryname',
								hiddenName:'adr_one_countryname',
								store: ds_country,
								displayField:'translatedName',
								valueField:'shortName',
								typeAhead: true,
								mode: 'remote',
								triggerAction: 'all',
								emptyText:'Select a state...',
								selectOnFocus:true,
								anchor:'95%'
								})]
							},{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [{
								xtype:'textfield',
								fieldLabel:'Phone', 
								name:'tel_work',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Cellphone', 
								name:'tel_cell',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Fax', 
								name:'tel_fax',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Car phone', 
								name:'tel_car',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Pager', 
								name:'tel_pager',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Email', 
								name:'contact_email', 
								vtype:'email',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'URL', 
								name:'contact_url', 
								vtype:'url',
								anchor:'95%'
							}]
						},{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [{
								xtype:'textfield',
								fieldLabel:'Unit', 
								name:'org_unit',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Role', 
								name:'contact_role',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Title', 
								name:'contact_title',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Room', 
								name:'contact_room',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Name Assistent', 
								name:'contact_assistent',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Phone Assistent', 
								name:'tel_assistent',
								anchor:'95%'
							}]
						}]								
					},{
		                title:'Private information',
		                layout:'column',
						deferredRender:false,
						border:false,
						items:[{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [{
								xtype:'textfield',
								fieldLabel:'Street', name:'adr_two_street',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Street2', name:'adr_two_street2',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Postalcode', name:'adr_two_postalcode',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'City', name:'adr_two_locality',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Region', name:'adr_two_region',
								anchor:'95%'
							}, 
							 new Ext.form.ComboBox({
								fieldLabel: 'Country',
								name: 'adr_two_countryname',
								hiddenName:'adr_two_countryname',
								store: ds_country,
								displayField:'translatedName',
								valueField:'shortName',
								typeAhead: true,
								mode: 'remote',
								triggerAction: 'all',
								emptyText:'Select a state...',
								selectOnFocus:true,
								anchor:'95%'
							})]
							},{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [
								new Ext.form.DateField({
										fieldLabel:'Birthday', 
										name:'contact_bday', 
										format:formData.config.dateFormat, 
										altFormats:'Y-m-d',
										anchor: '95%'
							}), {
								xtype:'textfield',
								fieldLabel:'Phone', name:'tel_home',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Cellphone', name:'tel_cell_private',
								anchor:'95%'
							}, {
								xtype:'textfield',
								fieldLabel:'Fax', name:'tel_fax_home',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'Email', name:'contact_email_home', vtype:'email',
								anchor:'95%'
							},{
								xtype:'textfield',
								fieldLabel:'URL', name:'contact_url_home', vtype:'url',
								anchor:'95%'
							}]
						},{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [
								new Ext.form.FieldSet({
									id:'photo', 
									legend:'Photo'
							})]						
						}]
												
					},{
		                title:'Lists',
		                layout:'column',
						deferredRender:false,
						border:false,
						items: [{
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [ new Ext.Panel({
										layout: 'fit',
										id: 'source',
								        width:250,
								        height:350,
										items: [ list_source_box ]
							    })
							]}, {
							columnWidth:.333,
							layout: 'form',
							border:false,
							extraCls: 'list-butons',
							items: [ new Ext.Button({
								        text: 'add',
										iconCls: 'blist',
										minWidth: 80,
								        handler: add_list,
										icon: 'images/oxygen/16x16/actions/arrow-right-double.png'
								    }), new Ext.Button({
								        text: 'remove',
										iconCls: 'blist', 
										minWidth: 80,
								        handler: remove_list,
										icon: 'images/oxygen/16x16/actions/arrow-left-double.png'
								    })
							]}, {
							columnWidth:.333,
							layout: 'form',
							border:false,
							items: [ new Ext.Panel({
										layout: 'fit',
										id: 'destination',
								        width:250,
								        height:350,
										items: [ list_selected_box ]		
							    })
							]}
						]
		            },{
		                title:'Categories',
		                layout:'column',
						deferredRender:false,
						border:false,
		                items: [{
						}]
		            }]
		        }]
			});
	
		

		var viewport = new Ext.Viewport({
			layout: 'border',
			items: addressedit
		});        

		
/*		var photo = Ext.get('photo');

		var c = photo.createChild({
			tag:'center', 
			cn: {
				tag:'img',
				src: 'http://extjs.com/forum/image.php?u=2&dateline=1175747336',
				style:'margin-bottom:5px;'
			}
		});
    
		new Ext.Button(c, {
			text: 'Change Photo'
		});
		
*/                
        
        var ds_addressbooks = new Ext.data.SimpleStore({
            fields: ['id', 'addressbooks'],
            data: formData.config.addressbooks
        }); 

		return;

    }

    var _setContactDialogValues = function(__dialog, _formData) {
    	var _dialog = Ext.getCmp('contactDialog').getForm();
        for (var fieldName in _formData) {
        	//console.log('set ' + fieldName + ' to ' + _formData[fieldName]);
            var field = _dialog.findField(fieldName);
            if(field) {
                //console.log(fieldName + ' => ' + _formData[fieldName]);
                field.setValue(_formData[fieldName]);
            }
        }
        //console.log('done');
    }

    var _exportContact = function(_btn, _event) {
        Ext.MessageBox.alert('Export', 'Not yet implemented.');
    }
    
    var _onAddressSelect = function(_addressbooName, _addressbookId) {
        
		var addressbook_ = Ext.getCmp('addressbook_');
		addressbook_.setValue(_addressbookId);
		// addressedit.setValues([{id:'contact_owner', value:_addressbookId}]);
    }

    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                _setContactDialogValues(dialog, formData.values);
            }
        }       
    }
    
}(); // end of application


///////////////////////////////////////////////////////////////////////////////
//
// the dialog to manage lists
//
///////////////////////////////////////////////////////////////////////////////

Egw.Addressbook.ListEditDialog = function() {

  /**
     * the form to edit the list data
     */
    var listedit;

   
    // private functions and variables
	// handler for save, apply and delete functions
	
	var handler_applyChanges = function(_button, _event) 
    {	
    	var contactForm = Ext.getCmp('listDialog').getForm();
		contactForm.render();
    	
    	if(contactForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.contact_id = formData.values.contact_id;
			}
			    		
			contactForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving contact...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Addressbook.reload();
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
    	var contactForm = Ext.getCmp('listDialog').getForm();
		contactForm.render();
    	
    	if(contactForm.isValid()) {
			var additionalData = {};
			if(formData.values) {
				additionalData.contact_id = formData.values.contact_id;
			}
			    		
			contactForm.submit({
    			waitTitle:'Please wait!',
    			waitMsg:'saving contact...',
    			params:additionalData,
    			success:function(form, action, o) {
    				window.opener.Egw.Addressbook.reload();
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

    var handler_deleteContact = function(_button, _event) 
    {
		var contactIds = Ext.util.JSON.encode([formData.values.contact_id]);
			
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Addressbook.deleteLists', 
				_contactIds: contactIds
			},
			text: 'Deleting contact...',
			success: function(_result, _request) {
  				window.opener.Egw.Addressbook.reload();
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

   	var action_deleteContact = new Ext.Action({
		text: 'delete list',
		text: 'delete list',
		handler: handler_deleteContact,
		iconCls: 'action_delete'
	});	
	
    ////////////////////////////////////////////////////////////////////////////
    // distributionlist dialog
    ////////////////////////////////////////////////////////////////////////////
    var _displayDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';

        var disableButtons = true;
        if(formData.values) {
            disableButtons = false;
        }       
        
        var contactToolbar = new Ext.Toolbar({
        	region: 'south',
          	id: 'applicationToolbar',
			split: false,
			height: 26,
			items: [
				action_saveAndClose,
				action_applyChanges,
				action_deleteContact
			]
		});

        
        // list all available addressbooks - to asign list to
        var ds_addressbooks = new Ext.data.SimpleStore({
            fields: ['id', 'addressbooks'],
            data: formData.config.addressbooks
        });

		
		var addressbookTrigger = new Ext.form.TriggerField({
            fieldLabel:'Addressbook', 
			id: 'addressbook_',
            name:'list_owner', 
            anchor:'100%',
            readOnly:true
        });
        
        addressbookTrigger.onTriggerClick = function() {
            Egw.Addressbook.displayAddressbookSelectDialog(_onAddressSelect);
        }		
		
		searchDS = new Ext.data.JsonStore({
				url: 'index.php',
				baseParams: {
                method:   'Addressbook.getOverview', 
                options:  '{"displayContacts":true,"displayLists":false}' 
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'contact_id',
            fields: [
                {name: 'contact_id'},
                {name: 'n_family'},
                {name: 'n_given'},
                {name: 'contact_email'}
            ],
            // turn on remote sorting
            remoteSort: true
       //     success: function(response, options) {},
        //    failure: function(response, options) {}
        });
        
        //contactDS.on("beforeload", function() {
        //  console.log('before load');
        //});

        searchDS.setDefaultSort('n_family', 'asc');
        //searchDS.load({params:{start:0, limit:50}});
		
		            

    
        // search for contacts to add to current list
        var list_search = new Ext.form.ComboBox({
        	store: searchDS,
			fieldLabel: 'Search for new list members',
            displayField:'n_family',
            typeAhead: false,
            loadingText: 'Searching...',
			anchor:'100%',
            pageSize:10,
            hideTrigger:true,
			itemSelector: 'div.search-item',
            tpl: new Ext.XTemplate(
		            '<tpl for=".">',
		            '<div class="search-item">{n_family}, {n_given} {contact_email}</div>',
		            '</tpl>')
        });
    
	    list_search.on('select', function(combo,record,index){ // override default onSelect to do redirect
		    	var _record = new listMemberRecord({
                	contact_id: record.data.contact_id,
                    n_family: record.data.n_family,
                    contact_email: record.data.contact_email
                });
                
				ds_listMembers.add(_record);
                ds_listMembers.sort('n_family');
                
                list_search.reset();
                list_search.collapse();
            });
	
        list_search.on('specialkey', function(_this, _e){
			if(searchDS.getCount() == 0) {
				var regExp  = /^[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4}|museum)$/;
				
				var aussage = regExp.exec(list_search.getValue());
				
				if(aussage && (_e.getKey() == _e.ENTER || _e.getKey() == e.RETURN ) ) {
                    var contactEmail = list_search.getValue();
                    var position = contactEmail.indexOf('@');
                    if(position != -1) {
                        var familyName = Ext.util.Format.capitalize(contactEmail.substr(0, position));
                    } else {
                        var familyName = contactEmail;
                    }
					var record = new listMemberRecord({
						contact_id:       '-1',
						n_family:         familyName,
						contact_email:    contactEmail
					});
					ds_listMembers.add(record);
					ds_listMembers.sort('n_family');
					list_search.reset();
				}
			}
 		});

		var listedit = new Ext.FormPanel({
			url:'index.php',
			baseParams: {method :'Addressbook.saveList'},
			labelAlign: 'top',
			bodyStyle:'padding:5px',
			width: 450,
			region: 'center',
			deferredRender:false,
			id: 'listDialog',
			tbar: contactToolbar, 
			items: [{
				layout: 'form',
				title: 'list information',
				border:false,
				anchor:'100%',
				items:[ addressbookTrigger
				
					,{
						xtype:'textfield',
						fieldLabel:'List Name', 
						name:'list_name',
						anchor:'100%'
					}, {
						xtype:'textarea',
						fieldLabel:'List Description', 
						name:'list_description', 
						grow: false,
						anchor:'100%'
				}, list_search
				
				]
 			  }]
			});
	
		var listMemberRecord = Ext.data.Record.create([        
        	{name: 'contact_id', type: 'int'},
            {name: 'n_family', type: 'string'},                            
            {name: 'contact_email', type: 'string'}  
        ]);

        // data store for listmember grid
		if(formData.values.list_members) {
			var listmembers = formData.values.list_members;
		} else {
			var listmembers = [];
		}
		
		var ds_listMembers = new Ext.data.SimpleStore({
			id: 'list_Members',
			fields: ['contact_id', 'n_family', 'contact_email'],		
			data: listmembers
		});
		ds_listMembers.sort('n_family', 'asc');
		
        // columnmodel for listmember grid
        var cm_listMembers = new Ext.grid.ColumnModel([{
            resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family'
        },{
            resizable: true, id: 'contact_email', header: 'eMail address', dataIndex: 'contact_email'
        }]);
        cm_listMembers.defaultSortable = true; // by default columns are sortable
        
        var ctxListMenu = new Ext.menu.Menu({
            id:'ctxListMenu', 
            items: [{
                id:'delete',
                text:'delete entry',
                icon:'images/oxygen/16x16/actions/edit-delete.png',
                handler: _deleteLstItemHandler
            }]
        });

        var listGrid = new Ext.grid.GridPanel({
            store: ds_listMembers,
            cm: cm_listMembers,
			id: 'listGrid',
            selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
            autoSizeColumns: true,
            monitorWindowResize: false,
            trackMouseOver: true,
			autoWidth: true,
			height: 300,
            contextMenu: 'ctxListMenu',   
            autoExpandColumn: 'contact_email'
        }); 
    	
		
        listGrid.on('rowcontextmenu', function(grid, rowIndex, eventObject) {
            eventObject.stopEvent();
            // var record = grid.getDataSource().getAt(rowIndex);
			var record = grid.getStore().getAt(rowIndex);
            if(record.data.contact_tid == 'l') {
                ctxListMenu.showAt(eventObject.getXY());
            } else {
                ctxListMenu.showAt(eventObject.getXY());
            }
        });
        
		
		var viewport = new Ext.Viewport({
			layout: 'column',
			items: [listedit, listGrid]
		});   
		
		return; 
	  

    } 
    
    var _onAddressSelect = function(_addressbooName, _addressbookId) {
        listedit.setValues([{id:'list_owner', value:_addressbookId}]);
    }
    
    /**
     * onclick handler for deleteListItem
     *
     */
    var _deleteLstItemHandler = function(_button, _event) {
        var contactIDs = Array();		
		var l_grid = Ext.getCmp('listGrid');
		var l_members = Ext.getCmp('list_Members');
		
        
		var selectedRows = l_grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            l_members.remove(selectedRows[i]);
        }
    }
        
    
    ////////////////////////////////////////////////////////////////////////////
    // set the dialog field to their initial value
    ////////////////////////////////////////////////////////////////////////////
	
     var _setDialogValues = function(__dialog, _formData) {
		var _dialog = Ext.getCmp('listDialog').getForm();
        for (var fieldName in _formData) {
        	//console.log('set ' + fieldName + ' to ' + _formData[fieldName]);
            var field = _dialog.findField(fieldName);
            if(field) {
                //console.log(fieldName + ' => ' + _formData[fieldName]);
                field.setValue(_formData[fieldName]);
            }
        }
        //console.log('done');
    }
    
    var _encodeDataSourceEntries = function(_dataSource) {
        var jsonData = new Array();
        
        _dataSource.each(function(_record){
            jsonData.push(_record.data);
        }, this);
        
        return Ext.util.JSON.encode(jsonData);
    }
    
    // public functions and variables
     return {
        display: function() {
			var dialog = _displayDialog();
		//	var dialog = Ext.getCmp('listDialog');
		
            if(formData.values) {
                _setDialogValues(dialog, formData.values);
            }
        }
    }
}(); // end of Egw.Addressbook.ListEditDialog