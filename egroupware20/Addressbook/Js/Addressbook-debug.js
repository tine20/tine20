Ext.namespace('Egw.Addressbook');

Egw.Addressbook = function() {

    var contactDS;
    
    var contactGrid;
	
	var listGrid;
    
    var dialog;
    
    var filterContactsButton, filterListsButton;

    // private functions and variables
   

    /**
     * creates the address grid
     *
     */
    var _showAddressGrid = function(_layout, _node) {

       var center = _layout.getRegion('center', false);

       // remove the first contentpanel from center region
       center.remove(0);
        
       // add a div, which will beneth parent element for the grid
       var contentTag 		= Ext.Element.get('content');
	   var toolbarDivTag	= contentTag.createChild({tag: 'div', id: 'toolbargriddiv'});
	   var outerDivTag 		= contentTag.createChild({tag: 'div', id: 'outergriddiv'});
	   
	   
       switch(_node.attributes.datatype) {
           case 'list':
               var options = {
                   listId: _node.attributes.listId
               };
               var method = 'Addressbook.getContacts';
               
               break;
           
           case 'contacts':
               var options = {
                   displayContacts: true,
                   displayLists:    true
               };
               var method = 'Addressbook.getContacts';
                
               break;
                
            case 'otherpeople':
                var options = {
                        displayContacts: true,
                        displayLists:    true,
                };
               var method = 'Addressbook.getContacts';
                
                break;

           case 'sharedaddressbooks':
               var options = {
                       displayContacts: true,
                       displayLists:    true,
               };
               var method = 'Addressbook.getContacts';
               
               break;   

           case 'overview':
               var options = {
                       displayContacts: true,
                       displayLists:    true,
               };
               var method = 'Addressbook.getOverview';
               
               break;   
       }

       // create the Data Store
       contactDS = new Ext.data.JsonStore({
           url: 'index.php',
           baseParams: {
               method:   method, 
               datatype: _node.attributes.datatype,
               owner:    _node.attributes.owner, 
               nodeid:   _node.attributes.id, 
               options:  Ext.encode(options),
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
        
       //contactDS.on("beforeload", function() {
       //  console.log('before load');
       //});

       contactDS.setDefaultSort('n_family', 'asc');

       contactDS.load({params:{start:0, limit:50}});
       
       contactDS.on('beforeload', function(_loader, _node) {
           _loader.baseParams.options = Ext.encode({
              displayContacts:    filterContactsButton.pressed,
              displayLists:       filterListsButton.pressed
           });
           _loader.baseParams.query = textF1.getValue();
       });

       var cm = new Ext.grid.ColumnModel([
		{ resizable: true, id: 'contact_tid', dataIndex: 'contact_tid', width: 30, renderer: _renderContactTid },
		{ resizable: true, id: 'contact_private', header: 'private?', dataIndex: 'contact_private', hidden: true },
		{ resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family' },
		{ resizable: true, id: 'n_given', header: 'Given name', dataIndex: 'n_given' },
		{ resizable: true, id: 'n_middle', header: 'Middle name', dataIndex: 'n_middle', hidden: true },
		{ resizable: true, id: 'n_prefix', header: 'Prefix', dataIndex: 'n_prefix', hidden: true },
		{ resizable: true, id: 'n_suffix', header: 'Suffix', dataIndex: 'n_suffix', hidden: true },
		{ resizable: true, id: 'n_fn', header: 'Full name', dataIndex: 'n_fn', hidden: true },
		{ resizable: true, id: 'n_fileas', header: 'Name + Firm', dataIndex: 'n_fileas', hidden: true },
		{ resizable: true, id: 'contact_bday', header: 'Birthday', dataIndex: 'contact_bday', hidden: true },
		{ resizable: true, id: 'org_name', header: 'Organisation', dataIndex: 'org_name' },
		{ resizable: true, id: 'org_unit', header: 'Unit', dataIndex: 'org_unit' },
		{ resizable: true, id: 'contact_title', header: 'Title', dataIndex: 'contact_title' },
		{ resizable: true, id: 'contact_role', header: 'Role', dataIndex: 'contact_role' },
		{ resizable: true, id: 'contact_assistent', header: 'Assistent', dataIndex: 'contact_assistent', hidden: true },
		{ resizable: true, id: 'contact_room', header: 'Room', dataIndex: 'contact_room', hidden: true },
		{ resizable: true, id: 'adr_one_street', header: 'Street', dataIndex: 'adr_one_street', hidden: true },
		{ resizable: true, id: 'adr_one_street2', header: 'Street 2', dataIndex: 'adr_one_street2', hidden: true },
		{ resizable: true, id: 'adr_one_locality', header: 'Locality', dataIndex: 'adr_one_locality', hidden: true },
		{ resizable: true, id: 'adr_one_region', header: 'Region', dataIndex: 'adr_one_region', hidden: true },
		{ resizable: true, id: 'adr_one_postalcode', header: 'Postalcode', dataIndex: 'adr_one_postalcode', hidden: true },
		{ resizable: true, id: 'adr_one_countryname', header: 'Country', dataIndex: 'adr_one_countryname', hidden: true },
		{ resizable: true, id: 'contact_label', header: 'Label', dataIndex: 'contact_label', hidden: true },
		{ resizable: true, id: 'adr_two_street', header: 'Street (private)', dataIndex: 'adr_two_street', hidden: true },
		{ resizable: true, id: 'adr_two_street2', header: 'Street 2 (private)', dataIndex: 'adr_two_street2', hidden: true },
		{ resizable: true, id: 'adr_two_locality', header: 'Locality (private)', dataIndex: 'adr_two_locality', hidden: true },
		{ resizable: true, id: 'adr_two_region', header: 'Region (private)', dataIndex: 'adr_two_region', hidden: true },
		{ resizable: true, id: 'adr_two_postalcode', header: 'Postalcode (private)', dataIndex: 'adr_two_postalcode', hidden: true },
		{ resizable: true, id: 'adr_two_countryname', header: 'Country (private)', dataIndex: 'adr_two_countryname', hidden: true },
		{ resizable: true, id: 'tel_work', header: 'Phone', dataIndex: 'tel_work', hidden: true },
		{ resizable: true, id: 'tel_cell', header: 'Cellphone', dataIndex: 'tel_cell', hidden: true },
		{ resizable: true, id: 'tel_fax', header: 'Fax', dataIndex: 'tel_fax', hidden: true },
		{ resizable: true, id: 'tel_assistent', header: 'Assistent phone', dataIndex: 'tel_assistent', hidden: true },
		{ resizable: true, id: 'tel_car', header: 'Car phone', dataIndex: 'tel_car', hidden: true },
		{ resizable: true, id: 'tel_pager', header: 'Pager', dataIndex: 'tel_pager', hidden: true },
		{ resizable: true, id: 'tel_home', header: 'Phone (private)', dataIndex: 'tel_home', hidden: true },
		{ resizable: true, id: 'tel_fax_home', header: 'Fax (private)', dataIndex: 'tel_fax_home', hidden: true },
		{ resizable: true, id: 'tel_cell_private', header: 'Cellphone (private)', dataIndex: 'tel_cell_private', hidden: true },
		{ resizable: true, id: 'tel_other', header: 'Phone other', dataIndex: 'tel_other', hidden: true },
		{ resizable: true, id: 'tel_prefer', header: 'Phone prefer', dataIndex: 'tel_prefer', hidden: true },
		{ resizable: true, id: 'contact_email', header: 'eMail', dataIndex: 'contact_email', hidden: true },
		{ resizable: true, id: 'contact_email_home', header: 'eMail (private)', dataIndex: 'contact_email_home', hidden: true },
		{ resizable: true, id: 'contact_url', header: 'URL', dataIndex: 'contact_url', hidden: true },
		{ resizable: true, id: 'contact_url_home', header: 'URL (private)', dataIndex: 'contact_url_home', hidden: true },
		{ resizable: true, id: 'contact_freebusy_uri', header: 'Freebusy URI', dataIndex: 'contact_freebusy_uri', hidden: true },
		{ resizable: true, id: 'contact_calendar_uri', header: 'Calendar URI', dataIndex: 'contact_calendar_uri', hidden: true },
		{ resizable: true, id: 'contact_note', header: 'Note', dataIndex: 'contact_note', hidden: true },
		{ resizable: true, id: 'contact_tz', header: 'Timezone', dataIndex: 'contact_tz', hidden: true },
		{ resizable: true, id: 'contact_geo', header: 'Geo', dataIndex: 'contact_geo', hidden: true },
		{ resizable: true, id: 'contact_pubkey', header: 'Public Key', dataIndex: 'contact_pubkey', hidden: true },
		{ resizable: true, id: 'contact_created', header: 'Creation Date', dataIndex: 'contact_created', hidden: true },
		{ resizable: true, id: 'contact_creator', header: 'Creator', dataIndex: 'contact_creator', hidden: true },
		{ resizable: true, id: 'contact_modified', header: 'Modification Date', dataIndex: 'contact_modified', hidden: true },
		{ resizable: true, id: 'contact_modifier', header: 'Modifier', dataIndex: 'contact_modifier', hidden: true },
		{ resizable: true, id: 'contact_jpegphoto', header: 'Photo', dataIndex: 'contact_jpegphoto', hidden: true },
		{ resizable: true, id: 'addressbook', header: 'addressbook', dataIndex: 'addressbook', hidden: true }
		]);
        
       cm.defaultSortable = true; // by default columns are sortable

       contactGrid = new Ext.grid.Grid(outerDivTag, {
           ds: contactDS,
           cm: cm,
           autoSizeColumns: false,
           selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
           enableColLock:false,
           loadMask: true,
           enableDragDrop:true,
           ddGroup: 'TreeDD',
           autoExpandColumn: 'n_given'
       });

       contactGrid.render();
       
       contactGrid.on('rowclick', function(gridP, rowIndexP, eventP) {
           var rowCount = contactGrid.getSelectionModel().getCount();
            
           var btns = generalToolbar.items.map;
            
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

       contactGrid.on('rowdblclick', function(gridPar, rowIndexPar, ePar) {
           var record = gridPar.getDataSource().getAt(rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            if(record.data.contact_tid == 'l')
            {
                try {
                    _openDialog(record.data.contact_id,'list');
                } catch(e) {
                //  alert(e);
                }
            }
            else
           {
                try {
                    _openDialog(record.data.contact_id);
                } catch(e) {
                    // alert(e);
                }
            }
       });
        
       contactGrid.on('rowcontextmenu', function(grid, rowIndex, eventObject) {
           eventObject.stopEvent();
            var record = grid.getDataSource().getAt(rowIndex);
            if(record.data.contact_tid == 'l') {
                ctxMenuList.showAt(eventObject.getXY());
            }
            else {
                ctxMenuAddress.showAt(eventObject.getXY());
            }
       });
       
		
              

      var gridHeader = contactGrid.getView().getHeaderPanel(true);
        
	   
       //adding one more toolbar to grid
    	var generalToolbar = new Ext.Toolbar(toolbarDivTag);
	   
	        
		

	   var pagingHeader = Ext.DomHelper.append(generalToolbar.el,{tag:'div',id:Ext.id()},true);
       // add a paging toolbar to the grid's footer
       var pagingToolbar = new Ext.PagingToolbar(pagingHeader, contactDS, {
           pageSize: 50,
            cls:'x-btn-icon-22',
           displayInfo: true,
           displayMsg: 'Displaying contacts {0} - {1} of {2}',
           emptyMsg: "No contacts to display"
       });

	   
	   
       generalToolbar.addButton({
           id: 'addbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/add-user.png',
           tooltip: 'add new contact',
           handler: _addBtnHandler
       });
        
       generalToolbar.addButton({
           id: 'addlstbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/add-users.png',
           tooltip: 'add new list',
           handler: _addLstBtnHandler
       });     

       generalToolbar.addButton({
           id: 'editbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/edit.png',
           tooltip: 'edit current item',
           disabled: true,
           handler: _editBtnHandler
       });

       generalToolbar.addButton({
           id: 'deletebtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/edit-delete.png',
           tooltip: 'delete selected items',
           disabled: true,
           handler: _deleteBtnHandler
       });

       generalToolbar.insertButton(4, new Ext.Toolbar.Separator());
    
       filterContactsButton = generalToolbar.addButton({
           id: 'filtercontactsbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/user.png',
           tooltip: 'display contacts',
           enableToggle: true,
           pressed: true,
           handler: _filterUserBtnHandler
       });
       
       filterListsButton = generalToolbar.addButton({
           id: 'filterlistsbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/users.png',
           tooltip: 'display lists',
           enableToggle: true,
           pressed: true,
           handler: _filterListsBtnHandler
       });
       
       generalToolbar.addButton({
           id: 'exportbtn',
           cls:'x-btn-icon-22',
           icon:'images/oxygen/22x22/actions/file-export.png',
           tooltip: 'export selected contacts',
           disabled: false,
           onClick: _exportBtnHandler
       }); 
       
        var textF1 = new Ext.form.TextField({
            height: 22,
		    width: 200,
		    emptyText:'Suchparameter ...', 
		    allowBlank:false
        });

		textF1.on('specialkey', function(_this, _e) {        
            if(_e.getKey() == _e.ENTER || _e.getKey() == e.RETURN ){
                contactDS.reload();
                //contactDS.removeAll();
                //contactDS.load({params:{
                //	start:0, 
                //    limit:50,
                //    query:_this.getValue()
                //}});         
            }
        });
		
        generalToolbar.add(new Ext.Toolbar.Fill());

        generalToolbar.addField(textF1);
	   
        generalToolbar.add({
            id: 'clearsearchbtn',
			cls:'x-btn-icon-22',
			icon:'images/oxygen/22x22/actions/clear-left.png',
			tooltip: 'Lösche bestehende Sucheingabe',
			disabled: false,
			onClick: 	function () {                                          
                textF1.setValue(''); 
                contactDS.reload();
            }
        });
							
        generalToolbar.add({
            id: 'searchbtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/mail-find.png',
            tooltip: 'Suche Adresse/Adressliste',
            disabled: false,
            onClick: function () {
                contactDS.reload();
            }
        }); 
	   
	   center.add(new Ext.GridPanel(contactGrid,{toolbar: generalToolbar}));
        
   }
    
    var _renderContactTid = function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
        switch(_data) {
            case 'l':
                    return "<img src='images/oxygen/16x16/actions/users.png' width='12' height='12' alt='list'/>";
            default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
        }
    }
    var _filterUserBtnHandler = function(_button, _event) {
        contactDS.reload();
    }
    var _filterListsBtnHandler = function(_button, _event) {
        contactDS.reload();
    }
	
	
    /**
     * onclick handler for deleteBtn
     *
     */
    var _deleteBtnHandler = function(_button, _event) {
        var contactIDs = Array();
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            contactIDs.push(selectedRows[i].id);
        }
        _deleteContact(contactIDs, function() {Egw.Addressbook.reload();});
        contactDS.reload();
    }


	
    /**
     * onclick handler for editBtn
     *
     */
    var _editBtnHandler = function(_button, _event) {
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        var contactID = selectedRows[0].id;
		
		if(selectedRows[0].data.contact_tid == 'l') {
			_openDialog(contactID,'list');
		}
		else {
            _openDialog(contactID);
		}
    }
    
    /**
     * onclick handler for addBtn
     *
     */
    var _addBtnHandler = function(_button, _event) {
        _openDialog();
    }
	
  /**
     * onclick handler for addLstBtn
     *
     */
    var _addLstBtnHandler = function(_button, _event) {
        _openDialog('','list');
    }	
    
    /**
     * onclick handler for deleteLstBtn
     *
     */
    var _deleteLstBtnHandler = function(_button, _event) {
        var contactIDs = Array();
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            contactIDs.push(selectedRows[i].id);
        }
        _deleteContact(contactIDs, function() {Egw.Addressbook.reload();});
        contactDS.reload();
    }

    /**
     * onclick handler for editLstBtn
     *
     */
    var _editLstBtnHandler = function(_button, _event) {
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        var contactID = selectedRows[0].id;
        
        _openDialog(contactID, 'list');
    }
	
	
	
    /**
     * contextmenu for contact grid
     *
     */
    var ctxMenuAddress = new Ext.menu.Menu({
        id:'ctxMenuAddress', 
        items: [{
            id:'edit',
            text:'edit contact',
            icon:'images/oxygen/16x16/actions/edit.png',
            handler: _editBtnHandler
        },{
            id:'delete',
            text:'delete contact',
            icon:'images/oxygen/16x16/actions/edit-delete.png',
            handler: _deleteBtnHandler
        },'-',{
            id:'new contact',
            text:'new contact',
            icon:'images/oxygen/16x16/actions/add-user.png',
            handler: _addBtnHandler
        },{
            id:'new list',
            text:'new list',
            icon:'images/oxygen/16x16/actions/add-users.png',
            handler: _addLstBtnHandler
        }]
    });
	
   var ctxMenuList = new Ext.menu.Menu({
        id:'ctxMenuList', 
        items: [{
            id:'edit',
            text:'edit list',
            icon:'images/oxygen/16x16/actions/edit.png',
            handler: _editLstBtnHandler
        },{
            id:'delete',
            text:'delete list',
            icon:'images/oxygen/16x16/actions/edit-delete.png',
            handler: _deleteLstBtnHandler
        },'-',{
            id:'new contact',
            text:'new contact',
            icon:'images/oxygen/16x16/actions/add-user.png',
            handler: _addBtnHandler
        },{
            id:'new list',
            text:'new list',
            icon:'images/oxygen/16x16/actions/add-users.png',
            handler: _addLstBtnHandler
        }]
    });	

	
    var _exportBtnHandler = function(_button, _event) {
    }

    /**
     * opens up a new window to add/edit a contact
     *
     */
    var _openDialog = function(_id,_dtype) {
        var url;
        var w = 1024, h = 786;
        var popW = 950, popH = 600;
        
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
     * delete a contact on the server
     *
     */
    var _deleteContact = function(_contactIDs, _onSuccess, _onError) {
        var contactIDs = Ext.util.JSON.encode(_contactIDs);
        new Ext.data.Connection().request({
            url: 'index.php',
            method: 'post',
            scope: this,
            params: {method:'Addressbook.deleteContacts', _contactIDs:contactIDs},
            success: function(response, options) {
                //window.location.reload();
                //console.log(response);
                var decodedResponse;
                try{
                    decodedResponse = Ext.util.JSON.decode(response.responseText);
                    if(decodedResponse.success) {
                        //Ext.MessageBox.alert('Success!', 'Deleted contact!');
                        if(typeof _onSuccess == 'function') {
                            _onSuccess;
                        }
                    } else {
                        Ext.MessageBox.alert('Failure!', 'Deleting contact failed!');
                    }
                    //console.log(decodedResponse);
                } catch(e){
                    Ext.MessageBox.alert('Failure!', e.message);
                }
            },
            failure: function(response, options) {
            }
        });
		
		// if() {
			// new Ext.data.Connection().request({
	            // url: 'index.php',
	            // method: 'post',
	            // scope: this,
	            // params: {method:'Addressbook.deleteLists', _contactIDs:contactIDs},
	            // success: function(response, options) {
	                window.location.reload();
	                //console.log(response);
	                // var decodedResponse;
	                // try{
	                    // decodedResponse = Ext.util.JSON.decode(response.responseText);
	                    // if(decodedResponse.success) {
	                        Ext.MessageBox.alert('Success!', 'Deleted contact!');
	                        // if(typeof _onSuccess == 'function') {
	                            // _onSuccess;
	                        // }
	                    // } else {
	                        // Ext.MessageBox.alert('Failure!', 'Deleting contact failed!');
	                    // }
	                    //console.log(decodedResponse);
	                // } catch(e){
	                    // Ext.MessageBox.alert('Failure!', e.message);
	                // }
	            // },
	            // failure: function(response, options) {
	            // }
	        // });
		// }
    }
	
    var _exportContact = function(_btn, _event) {
        Ext.MessageBox.alert('Export', 'Not yet implemented.');
    }
	

	
    var _displayContactDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';
		
        var layout = new Ext.BorderLayout(document.body, {
            north: {split:false, initialSize:28},
            center: {autoScroll: true}
        });
        layout.beginUpdate();
        layout.add('north', new Ext.ContentPanel('header', {fitToFrame:true}));
        layout.add('center', new Ext.ContentPanel('content'));
        layout.endUpdate();

        var disableButtons = true;
        if(formData.values) {
            disableButtons = false;
        }		
        var tb = new Ext.Toolbar('header');
        tb.add({
            id: 'savebtn',
            cls:'x-btn-text-icon',
            text: 'Save and Close',
            icon:'images/oxygen/22x22/actions/document-save.png',
            tooltip: 'save this contact and close window',
            onClick: function (){
                if (addressedit.isValid()) {
                    var additionalData = {};
                    if(formData.values) {
                        additionalData._contactID = formData.values.contact_id;
                    } else {
                        additionalData._contactID = 0;
                    }
                    
                    addressedit.submit({
                        waitTitle:'Please wait!',
                        waitMsg:'saving contact...',
                        params:additionalData,
                        success:function(form, action, o) {
                            //Ext.MessageBox.alert("Information",action.result.welcomeMessage);
                            window.opener.Egw.Addressbook.reload();
                            //window.setTimeout("window.close()", 400);
                        },
                        failure:function(form, action) {
                            //Ext.MessageBox.alert("Error",action.result.errorMessage);
                        }
                    });
                } else {
                    Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
                }
            }
        },{
            id: 'savebtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/save-all.png',
            tooltip: 'apply changes for this contact',
            onClick: function (){
                if (addressedit.isValid()) {
                    var additionalData = {};
                    if(formData.values) {
                        additionalData._contactID = formData.values.contact_id;
                    } else {
                        additionalData._contactID = 0;
                    }
                    
                    addressedit.submit({
                        waitTitle:'Please wait!',
                        waitMsg:'saving contact...',
                        params:additionalData,
                        success:function(form, action, o) {
                            //Ext.MessageBox.alert("Information",action.result.welcomeMessage);
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
        },{
            id: 'deletebtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/edit-delete.png',
            tooltip: 'delete this contact',
            disabled: disableButtons,
            handler: function(_btn, _event) {
                if(formData.values.contact_id) {
                    Ext.MessageBox.wait('Deleting contact...', 'Please wait!');
                    _deleteContact([formData.values.contact_id]);
                    _reloadMainWindow(true);
                }
            }
        },{
            id: 'exportbtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/file-export.png',
            tooltip: 'export this contact',
            disabled: disableButtons,
            handler: _exportContact
        });
		
        var ds_country = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {method:'Egwbase.getCountryList'},
            root: 'results',
            id: 'shortName',
            fields: ['shortName', 'translatedName'],
            remoteSort: false
        });
        
        var ds_addressbooks = new Ext.data.SimpleStore({
            fields: ['id', 'addressbooks'],
            data: formData.config.addressbooks
        }); 

        // add a div, which will bneehe parent element for the grid
        var contentTag = Ext.Element.get('content');
        //var outerDivTag = contentTag.createChild({tag:'div', id:'outergriddiv', class:'x-box-mc'});
        //var outerDivTag = contentTag.createChild({tag:'div', id:'outergriddiv'});
        //outerDivTag.addClass('x-box-mc');
        //var formDivTag = outerDivTag.createChild({tag:'div', id:'formdiv'});
        
        var addressedit = new Ext.form.Form({
            labelWidth: 75, // label settings here cascade unless overridden
            url:'index.php?method=Addressbook.saveAddress',
            reader : new Ext.data.JsonReader({root: 'results'}, [
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
            ])
        });
        
        addressedit.on('beforeaction',function(_form, _action) {
            _form.baseParams = {};
            _form.baseParams._contactOwner = _form.getValues().contact_owner;
            if(formData.values && formData.values.contact_id) {
                _form.baseParams._contactID = formData.values.contact_id;
            } else {
                _form.baseParams._contactID = 0;
            }
        });
        
        addressedit.fieldset({legend:'Contact information'});
        
        addressedit.column(
            {width:'33%', labelWidth:90, labelSeparator:''},
            new Ext.form.TextField({fieldLabel:'First Name', name:'n_given', width:175}),
            new Ext.form.TextField({fieldLabel:'Middle Name', name:'n_middle', width:175}),
            new Ext.form.TextField({fieldLabel:'Last Name', name:'n_family', width:175, allowBlank:false})
        );


       var addressbookTrigger = new Ext.form.TriggerField({
            fieldLabel:'Addressbook', 
            name:'contact_owner', 
            width:175, 
            readOnly:true
        });
		
	

        addressbookTrigger.onTriggerClick = function(){			
		
			test = Ext.Element.get('iWindowContAdrTag');
			
			if(test != null) {
				test.remove();
			}
			
			var bodyTag			= Ext.Element.get(document.body);
            var containerTag	= bodyTag.createChild({tag: 'div',id: 'adrContainer'});
            var iWindowTag	= containerTag.createChild({tag: 'div',id: 'iWindowAdrTag'});
            var iWindowContTag  = containerTag.createChild({tag: 'div',id: 'iWindowContAdrTag'});

			if(!addressBookDialog) {
                var addressBookDialog = new Ext.LayoutDialog('iWindowAdrTag', {
                    modal: true,
                    width:700,
                    height:400,
                    shadow:true,
                    minWidth:700,
                    minHeight:400,
                    autoTabs:true,
                    proxyDrag:true,
                    // layout config merges with the dialog config
                    center:{
                        autoScroll:true,
                        tabPosition: 'top',
                        closeOnTab: true,
                        alwaysShowTabs: true
                    }
                });

				//################## Listenansicht #################

				var Tree = Ext.tree;
				
				treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
				treeLoader.on("beforeload", function(loader, node) {
					loader.baseParams.method   = node.attributes.application + '.getTree';
					loader.baseParams.node     = node.id;
			        loader.baseParams.datatype = node.attributes.datatype;
			        loader.baseParams.owner    = node.attributes.owner;
					loader.baseParams.modul    = 'contactedit';
				}, this);
				            
				var tree = new Tree.TreePanel('iWindowContAdrTag', {
					animate:true,
					loader: treeLoader,
					enableDD:true,
					//lines: false,
					ddGroup: 'TreeDD',
					enableDrop: true,			
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
				
				//application received globally
				root.appendChild(new Tree.AsyncTreeNode(application));

				// render the tree
				tree.render();
				root.expand(); 
				

				//###############Listenansichtende #################

				addressBookDialog.addKeyListener(27, this.hide);
                addressBookDialog.addButton("save", function() {
						if(tree.getSelectionModel().getSelectedNode()) {				
							var cnode = tree.getSelectionModel().getSelectedNode().id;
							
							var addressbook_id = tree.getNodeById(cnode).attributes.owner;	
						
							if( (addressbook_id > 0) || (addressbook_id < 0) ) {
								addressedit.setValues([{id:'contact_owner', value:addressbook_id}]);
								addressBookDialog.hide();
							} else {
							  Ext.MessageBox.alert('wrong selection','please select a valid addressbook');
							}
						} else {
							 Ext.MessageBox.alert('no selection','please select an addressbook');
						}
					    
                }, addressBookDialog);
				
                addressBookDialog.addButton("cancel", addressBookDialog.hide, addressBookDialog);
					
                var layout = addressBookDialog.getLayout();
                layout.beginUpdate();
                layout.add("center", new Ext.ContentPanel('iWindowContAdrTag', {	
                    autoCreate:true, 
                    title: 'Addressbook'
                }));
                layout.endUpdate();									
            }
            
            addressBookDialog.show();	
			
		}
		
        addressedit.column(
            {width:'33%', labelWidth:90, labelSeparator:''},
            new Ext.form.TextField({fieldLabel:'Prefix', name:'n_prefix', width:175}),
            new Ext.form.TextField({fieldLabel:'Suffix', name:'n_suffix', width:175}),
            addressbookTrigger
        );
/*        
        addressedit.column(
            {width:'33%', labelWidth:90, labelSeparator:''},
            new Ext.form.TextField({fieldLabel:'Suffix', name:'n_suffix', width:175})
        );
*/
        addressedit.end();

        addressedit.fieldset({legend:'Business information'});

            addressedit.column(
                {width:'33%', labelWidth:90, labelSeparator:''},
                new Ext.form.TextField({fieldLabel:'Company', name:'org_name', width:175}),
                new Ext.form.TextField({fieldLabel:'Street', name:'adr_one_street', width:175}),
                new Ext.form.TextField({fieldLabel:'Street 2', name:'adr_one_street2', width:175}),
                new Ext.form.TextField({fieldLabel:'Postalcode', name:'adr_one_postalcode', width:175}),
                new Ext.form.TextField({fieldLabel:'City', name:'adr_one_locality', width:175}),
                new Ext.form.TextField({fieldLabel:'Region', name:'adr_one_region', width:175}),
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
                    width:175
                })
            );

            addressedit.column(
                {width:'33%', labelWidth:90, labelSeparator:''},
                new Ext.form.TextField({fieldLabel:'Phone', name:'tel_work', width:175}),
                new Ext.form.TextField({fieldLabel:'Cellphone', name:'tel_cell', width:175}),
                new Ext.form.TextField({fieldLabel:'Fax', name:'tel_fax', width:175}),
                new Ext.form.TextField({fieldLabel:'Car phone', name:'tel_car', width:175}),
                new Ext.form.TextField({fieldLabel:'Pager', name:'tel_pager', width:175}),
                new Ext.form.TextField({fieldLabel:'Email', name:'contact_email', vtype:'email', width:175}),
                new Ext.form.TextField({fieldLabel:'URL', name:'contact_url', vtype:'url', width:175})
            );

            addressedit.column(
                {width:'33%', labelWidth:90, labelSeparator:''},
                new Ext.form.TextField({fieldLabel:'Unit', name:'org_unit', width:175}),			
                new Ext.form.TextField({fieldLabel:'Role', name:'contact_role', width:175}),
                new Ext.form.TextField({fieldLabel:'Title', name:'contact_title', width:175}),
                new Ext.form.TextField({fieldLabel:'Room', name:'contact_room', width:175}),
                new Ext.form.TextField({fieldLabel:'Name Assistent', name:'contact_assistent', width:175}),
                new Ext.form.TextField({fieldLabel:'Phone Assistent', name:'tel_assistent', width:175})
            );

        // fieldset end
        addressedit.end();

        addressedit.fieldset({legend:'Private information'});

            addressedit.column(
                {width:'33%', labelWidth:90, labelSeparator:''},
                new Ext.form.TextField({fieldLabel:'Street', name:'adr_two_street', width:175}),
                new Ext.form.TextField({fieldLabel:'Street2', name:'adr_two_street2', width:175}),
                new Ext.form.TextField({fieldLabel:'Postalcode', name:'adr_two_postalcode', width:175}),
                new Ext.form.TextField({fieldLabel:'City', name:'adr_two_locality', width:175}),
                new Ext.form.TextField({fieldLabel:'Region', name:'adr_two_region', width:175}),
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
                    width:175
                })
            );
            			
            addressedit.column(
                {width:'33%', labelWidth:90, labelSeparator:''},
                new Ext.form.DateField({fieldLabel:'Birthday', name:'contact_bday', format:formData.config.dateFormat, altFormats:'Y-m-d', width:175}),
                new Ext.form.TextField({fieldLabel:'Phone', name:'tel_home', width:175}),
                new Ext.form.TextField({fieldLabel:'Cellphone', name:'tel_cell_private', width:175}),
                new Ext.form.TextField({fieldLabel:'Fax', name:'tel_fax_home', width:175}),
                new Ext.form.TextField({fieldLabel:'Email', name:'contact_email_home', vtype:'email', width:175}),
                new Ext.form.TextField({fieldLabel:'URL', name:'contact_url_home', vtype:'url', width:175})
            );
            
            addressedit.column(
                {width:'33%', labelSeparator:'', hideLabels:true},
                new Ext.form.TextArea({
                    //fieldLabel: 'Address',
                    name: 'contact_note',
                    grow: false,
                    preventScrollbars:false,
                    width:'95%',
                    maxLength:255,
                    height:150
                })
            );
            
        //fieldset end
        addressedit.end();
        
        var categoriesTrigger = new Ext.form.TriggerField({
            fieldLabel:'Categories', 
            name:'categories', 
            width:320, 
            readOnly:true
        });
        categoriesTrigger.onTriggerClick = function(){
            var containerTag	= Ext.Element.get('container');
            var iWindowTag	= containerTag.createChild({tag: 'div',id: 'iWindowTag'});
            var iWindowContTag  = containerTag.createChild({tag: 'div',id: 'iWindowContTag'});
            
            var	ds_category = new Ext.data.SimpleStore({
                fields: ['category_id', 'category_realname'],
                data: [
                    ['1', 'erste Kategorie'],
                    ['2', 'zweite Kategorie'],
                    ['3', 'dritte Kategorie'],
                    ['4', 'vierte Kategorie'],
                    ['5', 'fuenfte Kategorie'],
                    ['6', 'sechste Kategorie'],
                    ['7', 'siebte Kategorie'],
                    ['8', 'achte Kategorie']
                ]
            });
            
            ds_category.load();
            
            ds_checked = new Ext.data.SimpleStore({
                fields: ['category_id', 'category_realname'],
                data: [
                    ['2', 'zweite Kategorie'],
                    ['5', 'fuenfte Kategorie'],
                    ['6', 'sechste Kategorie'],
                    ['8', 'achte Kategorie']
                ]
            });
            
            ds_checked.load();
            
            var categoryedit = new Ext.form.Form({
                labelWidth: 75, // label settings here cascade unless overridden
                url:'index.php?method=Addressbook.saveAdditionalData',
                reader : new Ext.data.JsonReader({root: 'results'}, [
                    {name: 'category_id'},
                    {name: 'category_realname'},
                ])
            });
			
            var i= 1;
            var checked = new Array();
		
            ds_checked.each( function(record){
                checked[record.data.category_id] = record.data.category_realname;
            });
		
            ds_category.each( function(fields){
                if( (i % 12) == 1) {
                    categoryedit.column({width:'33%', labelWidth:50, labelSeparator:''});
                }
                
                if(checked[fields.data.category_id]) {
                    categoryedit.add(new Ext.form.Checkbox({
                        boxLabel: fields.data.category_realname, 
                        name: fields.data.category_realname, 
                        checked: true
                    }));
                } else {
                    categoryedit.add(new Ext.form.Checkbox({
                        boxLabel: fields.data.category_realname, 
                        name: fields.data.category_realname
                    }));
                }
                
                if( (i % 12) == 0) {
                    categoryedit.end();
                }
                
                i = i + 1;
            });
            
            categoryedit.render('iWindowContTag');
            
            if(!dialog) {
                var dialog = new Ext.LayoutDialog('iWindowTag', {
                    modal: true,
                    width:700,
                    height:400,
                    shadow:true,
                    minWidth:700,
                    minHeight:400,
                    autoTabs:true,
                    proxyDrag:true,
                    // layout config merges with the dialog config
                    center:{
                        autoScroll:true,
                        tabPosition: 'top',
                        closeOnTab: true,
                        alwaysShowTabs: true
                    }
                });
                
                dialog.addKeyListener(27, this.hide);
                dialog.addButton("save", function() {
                    Ext.MessageBox.alert('Todo', 'Not yet implemented!');
                    dialog.hide;
                }, dialog);
				
                dialog.addButton("cancel", function() {
                    //window.location.reload();
                    Ext.MessageBox.alert('Todo', 'Not yet implemented!');
                    dialog.hide;
                }, dialog);
					
                var layout = dialog.getLayout();
                layout.beginUpdate();
                layout.add("center", new Ext.ContentPanel('iWindowContTag', {	
                    autoCreate:true, 
                    title: 'Category'
                }));
                layout.endUpdate();									
            }
            
            dialog.show();
        }
        
        addressedit.column(
            {width:'45%', labelWidth:80, labelSeparator:' ', labelAlign:'right'},
            categoriesTrigger
        );
        
        var listsTrigger = new Ext.form.TriggerField({fieldLabel:'Lists', name:'lists', width:320, readOnly:true});
        
        listsTrigger.onTriggerClick = function(){
            var containerTag    = Ext.Element.get('container');
            var iWindowTag      = containerTag.createChild({tag: 'div',id: 'iWindowTag'});
            var iWindowContTag  = containerTag.createChild({tag: 'div',id: 'iWindowContTag'});

            var	ds_lists = new Ext.data.SimpleStore({
                fields: ['list_id', 'list_realname'],
                data: [
                    ['1', 'Liste A'],
                    ['2', 'Liste B'],
                    ['3', 'Liste C'],
                    ['4', 'Liste D'],
                    ['5', 'Liste E'],
                    ['6', 'Liste F'],
                    ['7', 'Liste G'],
                    ['8', 'Liste H']
                ]
            });
            
            ds_lists.load();

            ds_checked = new Ext.data.SimpleStore({
                fields: ['list_id', 'list_realname'],
                data: [
                    ['2', 'Liste B'],
                    ['5', 'Liste E'],
                    ['6', 'Liste F'],
                    ['8', 'Liste H']
                ]
            });
            ds_checked.load();
            
            var listsedit = new Ext.form.Form({
                labelWidth: 75, // label settings here cascade unless overridden
                url:'index.php?method=Addressbook.saveAdditionalData',
                reader : new Ext.data.JsonReader(
                    {root: 'results'}, 
                    [
                        {name: 'list_id'},
                        {name: 'list_realname'},					
                    ]
                )
            });		
            
            var i= 1;
            var checked = new Array();
            
            ds_checked.each( function(record){
                checked[record.data.list_id] = record.data.list_realname;						
            });
									
            ds_lists.each( function(fields){
                if( (i % 12) == 1) listsedit.column({width:'33%', labelWidth:50, labelSeparator:''});
								
                if(checked[fields.data.list_id]) {
                    listsedit.add(new Ext.form.Checkbox({boxLabel: fields.data.list_realname, name: fields.data.list_realname, checked: true}));
                } else {
                    listsedit.add(new Ext.form.Checkbox({boxLabel: fields.data.list_realname, name: fields.data.list_realname}));
                }
                
                if( (i % 12) == 0) {
                    listsedit.end();
                }
				
                i = i + 1;			
            });
						
            listsedit.render('iWindowContTag');	
							
            if(!dialog){											
                var dialog = new Ext.LayoutDialog('iWindowTag', {
                    modal: true,
                    width:700,
                    height:400,
                    shadow:true,
                    minWidth:700,
                    minHeight:400,
                    autoTabs:true,
                    proxyDrag:true,
                    // layout config merges with the dialog config
                    center:{
                        autoScroll:true,
                        tabPosition: 'top',
                        closeOnTab: true,
                        alwaysShowTabs: true
                    }
                });
                
                dialog.addKeyListener(27, this.hide);
                dialog.addButton("save", function() {
                    Ext.MessageBox.alert('Todo', 'Not yet implemented!');
                }, dialog);
								
                dialog.addButton("cancel", function() {
                    window.location.reload(); dialog.hide
                }, dialog);						

                var layout = dialog.getLayout();
                layout.beginUpdate();
                layout.add("center", new Ext.ContentPanel('iWindowContTag', {
                    autoCreate:true, title: 'Lists'
                }));
                layout.endUpdate();									
            }
            dialog.show();
        }
        
        addressedit.column(
            {width:'45%', labelWidth:80, labelSeparator:' ', labelAlign:'right'},
            listsTrigger
        );
        
        addressedit.column(
            {width:'10%', labelWidth:50, labelSeparator:' ', labelAlign:'right'},
            new Ext.form.Checkbox({fieldLabel:'Private', name:'categories', width:10})
        );
        addressedit.render('content');
        
        return addressedit;
    }
	
		

    var _setContactDialogValues = function(_dialog, _formData) {
		for (var fieldName in _formData) {
            var field = _dialog.findField(fieldName);
            if(field) {
                //console.log(fieldName + ' => ' + _formData[fieldName]);
                field.setValue(_formData[fieldName]);
            }
        }
    }
	

    // public functions and variables
    return {
        // public functions
        show: _showAddressGrid,
        
        reload: function() {
            contactDS.reload();
        },
        
        handleDragDrop: function(e) {
            alert('Best Regards From Addressbook');
        },
        
        openDialog: function() {
            _openDialog();
        },
        
        displayContactDialog: function() {
            var dialog = _displayContactDialog();
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


    ////////////////////////////////////////////////////////////////////////////
    // distributionlist dialog
    ////////////////////////////////////////////////////////////////////////////
    var _displayDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';
        
        var layout = new Ext.BorderLayout(document.body, {
            north: {split:false, initialSize:28},
            center: {split:false, initialSize:70},
            south: {split:false, initialSize:350, autoScroll: true}
        });
        layout.beginUpdate();
        layout.add('north', new Ext.ContentPanel('header', {fitToFrame:true}));
        layout.add('center', new Ext.ContentPanel('content', {fitToFrame:true}));
        layout.add('south', new Ext.ContentPanel('south', {fitToFrame:true}));
        layout.endUpdate();

                
        var disableButtons = true;
        if(formData.values) {
            disableButtons = false;
        }       
        var tb = new Ext.Toolbar('header');
        tb.add({
            id: 'savebtn',
            cls:'x-btn-text-icon',
            text: 'Save and Close',
            icon:'images/oxygen/22x22/actions/document-save.png',
            tooltip: 'save this list and close window',
            onClick: function (){
                if (listedit.isValid()) {
                    var additionalData = {};
                    if(formData.values) {
                        additionalData._contactID = formData.values.contact_id;
                    } else {
                        additionalData._contactID = 0;
                    }
                    
                    listedit.submit({
                        waitTitle:'Please wait!',
                        waitMsg:'saving contact...',
                        params:additionalData,
                        success:function(form, action, o) {
                            //Ext.MessageBox.alert("Information",action.result.welcomeMessage);
                            window.opener.Egw.Addressbook.reload();
                            //window.setTimeout("window.close()", 400);
                        },
                        failure:function(form, action) {
                            //Ext.MessageBox.alert("Error",action.result.errorMessage);
                        }
                    });
                } else {
                    Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
                }
            }
        },{
            id: 'savebtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/save-all.png',
            tooltip: 'apply changes for this list',
            onClick: function (){
                if (listedit.isValid()) {
                    var additionalData = {};
                    if(formData.values) {
                        additionalData._contactID = formData.values.contact_id;
                    } else {
                        additionalData._contactID = 0;
                    }
                    
                    listedit.submit({
                        waitTitle:'Please wait!',
                        waitMsg:'saving contact...',
                        params:additionalData,
                        success:function(form, action, o) {
                            //Ext.MessageBox.alert("Information",action.result.welcomeMessage);
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
        },{
            id: 'deletebtn',
            cls:'x-btn-icon-22',
            icon:'images/oxygen/22x22/actions/edit-delete.png',
            tooltip: 'delete this contact',
            disabled: disableButtons,
            handler: function(_btn, _event) {
                if(formData.values.contact_id) {
                    Ext.MessageBox.wait('Deleting contact...', 'Please wait!');
                    _deleteContact([formData.values.contact_id]);
                    _reloadMainWindow(true);
                }
            }
        //},{
        //    id: 'exportbtn',
        //    cls:'x-btn-icon-22',
        //    icon:'images/oxygen/22x22/actions/file-export.png',
        //    tooltip: 'export this contact',
        //    disabled: disableButtons,
        //    handler: _exportContact
        });
        
        // list all available addressbooks - to asign list to
        var ds_addressbooks = new Ext.data.SimpleStore({
            fields: ['id', 'addressbooks'],
            data: formData.config.addressbooks
        });

        // add a div, which will bneehe parent element for the grid
        var contentTag = Ext.Element.get('content');
        
        var listedit = new Ext.form.Form({
            labelWidth: 75, // label settings here cascade unless overridden
            url:'index.php',
            baseParams: {method: 'Addressbook.saveList'}
        });
        
        listedit.on('beforeaction',function(_form, _action) {
            _form.baseParams._listOwner = _form.getValues().list_owner;
            //console.log(ds_listMembers.getRange(0));
            //_form.baseParams._listmembers = Ext.util.JSON.encode(ds_listMembers.getRange());
            _form.baseParams._listmembers = _encodeDataSourceEntries(ds_listMembers);
                        
            if(formData.values && formData.values.list_id) {
                _form.baseParams._listId = formData.values.list_id;
            } else {
                _form.baseParams._listId = 0;
            }
            //console.log(_form.baseParams); 
        });
     

      var addressbookTrigger = new Ext.form.TriggerField({
            fieldLabel:'Addressbook', 
            name:'list_owner', 
            width:325, 
            readOnly:true
        });
		
	

        addressbookTrigger.onTriggerClick = function(){			
		
			test = Ext.Element.get('iWindowContAdrTag');
			
			if(test != null) {
				test.remove();
			}
			
			var bodyTag			= Ext.Element.get(document.body);
            var containerTag	= bodyTag.createChild({tag: 'div',id: 'adrContainer'});
            var iWindowTag	= containerTag.createChild({tag: 'div',id: 'iWindowAdrTag'});
            var iWindowContTag  = containerTag.createChild({tag: 'div',id: 'iWindowContAdrTag'});

			if(!addressBookDialog) {
                var addressBookDialog = new Ext.LayoutDialog('iWindowAdrTag', {
                    modal: true,
                    width:375,
                    height:400,
                    shadow:true,
                    minWidth:375,
                    minHeight:400,
                    autoTabs:true,
                    proxyDrag:true,
                    // layout config merges with the dialog config
                    center:{
                        autoScroll:true,
                        tabPosition: 'top',
                        closeOnTab: true,
                        alwaysShowTabs: true
                    }
                });

				//################## Listenansicht #################

				var Tree = Ext.tree;
				
				treeLoader = new Tree.TreeLoader({dataUrl:'index.php'});
				treeLoader.on("beforeload", function(loader, node) {
					loader.baseParams.method   = node.attributes.application + '.getTree';
					loader.baseParams.node     = node.id;
			        loader.baseParams.datatype = node.attributes.datatype;
			        loader.baseParams.owner    = node.attributes.owner;
					loader.baseParams.modul    = 'contactedit';
				}, this);
				            
				var tree = new Tree.TreePanel('iWindowContAdrTag', {
					animate:true,
					loader: treeLoader,
					enableDD:true,
					//lines: false,
					ddGroup: 'TreeDD',
					enableDrop: true,			
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
				
				//application received globally
				root.appendChild(new Tree.AsyncTreeNode(application));

				// render the tree
				tree.render();
				root.expand(); 
				

				//###############Listenansichtende #################

	 
				addressBookDialog.addKeyListener(27, this.hide);
                addressBookDialog.addButton("save", function() {
						if(tree.getSelectionModel().getSelectedNode()) {				
							var cnode = tree.getSelectionModel().getSelectedNode().id;
							
							var addressbook_id = tree.getNodeById(cnode).attributes.owner;	
												
							if( (addressbook_id > 0) || (addressbook_id < 0) ) {
								listedit.setValues([{id:'list_owner', value:addressbook_id}]);
								addressBookDialog.hide();
							} else {
							  Ext.MessageBox.alert('wrong selection','please select a valid addressbook');
							}
						} else {
							 Ext.MessageBox.alert('no selection','please select an addressbook');
						}
					    
                }, addressBookDialog);
				
                addressBookDialog.addButton("cancel", addressBookDialog.hide, addressBookDialog);
					
                var layout = addressBookDialog.getLayout();
                layout.beginUpdate();
                layout.add("center", new Ext.ContentPanel('iWindowContAdrTag', {	
                    autoCreate:true, 
                    title: 'Addressbook'
                }));
                layout.endUpdate();									
            }
            
            addressBookDialog.show();	
			
		}

	 
        listedit.fieldset({legend:'list information'});
                
        listedit.column(
            {width:'100%', labelWidth:90, labelSeparator:''},
   		    addressbookTrigger,
            new Ext.form.TextField({fieldLabel:'List Name', name:'list_name', width:325}),
            new Ext.form.TextArea({fieldLabel:'List Description', name:'list_description', width:325, grow: false })
        );          
        listedit.end();
               
		if(formData.values) {
				var c_owner = formData.values.list_owner;
				var c_id 	= formData.values.list_id;
		} else {
				var c_owner = -1;
				var c_id 	= -1;
		}
			   
        searchDS = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method:   'Addressbook.getOverview', 
                owner:    c_owner, 
                options:  '{"displayContacts":true,"displayLists":false}',  
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
            remoteSort: true,
            success: function(response, options) {},
            failure: function(response, options) {}
        });
        
        //contactDS.on("beforeload", function() {
        //  console.log('before load');
        //});

        searchDS.setDefaultSort('n_family', 'asc');
        //searchDS.load({params:{start:0, limit:50}});
         
                    
        // Custom rendering Template
        var resultTpl = new Ext.Template(
            '<div class="search-item">',
                '{n_family}, {n_given} {contact_email}',
            '</div>'
        );
    
        // search for contacts to add to current list
        var list_search = new Ext.form.ComboBox({
        	store: searchDS,
            displayField:'n_family',
            typeAhead: false,
            loadingText: 'Searching...',
            width: 415,
            pageSize:10,
            hideTrigger:true,
            tpl: resultTpl,
            onSelect: function(_record){ // override default onSelect to do redirect
            	var record = new listMemberRecord({
                	contact_id: _record.data.contact_id,
                    n_family: _record.data.n_family,
                    contact_email: _record.data.contact_email
                });
                
				ds_listMembers.add(record);
                ds_listMembers.sort('n_family');
                
                list_search.reset();
                list_search.collapse();
            }
        });
    
        list_search.on('specialkey', function(_this, _e){
			if(searchDS.getCount() == 0) {
				var regExp  = /^[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4}|museum)$/;
				
				var aussage = regExp.exec(list_search.getValue());
				
				if(aussage && (_e.getKey() == _e.ENTER || _e.getKey() == e.RETURN ) ) {
					var record = new listMemberRecord({
						contact_id: '-1',
						n_family: '',
						contact_email: list_search.getValue()
					});
					ds_listMembers.add(record);
					ds_listMembers.sort('n_family');
					list_search.reset();
				}
			}
 		});
	
		
        listedit.fieldset({legend:'select new list members'});
        listedit.column(
           {width:'100%', labelWidth:0, labelSeparator:''},               
            list_search         
        );          
        listedit.end();     
        listedit.render('content');

        var listMemberRecord = Ext.data.Record.create([        
        	{name: 'contact_id', type: 'int'},
            {name: 'n_family', type: 'string'},                            
            {name: 'contact_email', type: 'string'}  
        ]);

        // data store for listmember grid
		if(formData.values) var listmembers = formData.values.list_members;
	
		
        var ds_listMembers = new Ext.data.SimpleStore({
            fields: ['contact_id', 'n_family', 'contact_email'],
            data: listmembers
        });
        ds_listMembers.sort('n_family', 'ASC');

        // columnmodel for listmember grid
        var cm_listMembers = new Ext.grid.ColumnModel([{
            resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family'
        },{
            resizable: true, id: 'contact_email', header: 'eMail address', dataIndex: 'contact_email'
        }]);
        cm_listMembers.defaultSortable = true; // by default columns are sortable
        
        var listGrid = new Ext.grid.Grid("south", {
            ds: ds_listMembers,
            cm: cm_listMembers,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
            autoSizeColumns: true,
            monitorWindowResize: false,
            trackMouseOver: true,
            contextMenu: 'ctxListMenu',   
            autoExpandColumn: 'contact_email'
        }); 
    
        listGrid.on('rowcontextmenu', function(grid, rowIndex, eventObject) {
            eventObject.stopEvent();
            var record = grid.getDataSource().getAt(rowIndex);
            if(record.data.contact_tid == 'l') {
                ctxListMenu.showAt(eventObject.getXY());
            } else {
                ctxListMenu.showAt(eventObject.getXY());
            }
        });
        
        // set any options
        listGrid.render('south');  
             
        /**
         * onclick handler for deleteListItem
         *
         */
        var _deleteLstItemHandler = function(_button, _event) {
            var contactIDs = Array();
            var selectedRows = listGrid.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                ds_listMembers.remove(selectedRows[i]);
            }
        }
        
        var ctxListMenu = new Ext.menu.Menu({
            id:'ctxListMenu', 
            items: [{
                id:'delete',
                text:'delete entry',
                icon:'images/oxygen/16x16/actions/edit-delete.png',
                handler: _deleteLstItemHandler
            }]
        });

        return listedit;
    } 
    
    ////////////////////////////////////////////////////////////////////////////
    // set the dialog field to their initial value
    ////////////////////////////////////////////////////////////////////////////
    var _setDialogValues = function(_dialog, _formData) {
        _dialog.findField('list_name').setValue(_formData['list_name']);
        _dialog.findField('list_description').setValue(_formData['list_description']);
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
            if(formData.values) {
                _setDialogValues(dialog, formData.values);
            }
        }
    }
}(); // end of Egw.Addressbook.ListEditDialog