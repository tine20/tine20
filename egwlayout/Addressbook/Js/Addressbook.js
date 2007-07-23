var EGWNameSpace = EGWNameSpace || {};

EGWNameSpace.Addressbook = function() {

	var ds;

	// private function
	var _showAddressGrid = function(_layout) {

		var center = _layout.getRegion('center', false);

		// remove the first contentpanel from center region
		center.remove(0);
		
		// add a div, which will bneehe parent element for the grid
		var contentTag = Ext.Element.get('content');
		var outerDivTag = contentTag.createChild({tag: 'div',id: 'outergriddiv'});

		// create the Data Store
		ds = new Ext.data.JsonStore({
			url: 'jsonrpc.php',
			baseParams: {application:'Addressbook_Json', datatype:'address', func:'getData'},
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

		ds.setDefaultSort('contact_id', 'desc');

		ds.load();

		var cm = new Ext.grid.ColumnModel([{
				resizable: true,
				id: 'contact_id',
				header: 'Id',
				dataIndex: 'contact_id',
				width: 30
			},{
				resizable: true,
				id: 'n_family',
				header: 'Family name',
				dataIndex: 'n_family'
			},{
				resizable: true,
				id: 'n_given',
				header: 'Given name',
				dataIndex: 'n_given'
			},{
				resizable: true,
				header: 'Middle name',
				dataIndex: 'n_middle',
				hidden: true
			},{
				resizable: true,
				id: 'n_prefix',
				header: 'Prefix',
				dataIndex: 'n_prefix',
				hidden: true
			},{
				resizable: true,
				header: 'Suffix',
				dataIndex: 'n_suffix',
				hidden: true
			},{
				resizable: true,
				header: 'Full name',
				dataIndex: 'n_fn',
				hidden: true
			},{
				resizable: true,
				header: 'Birthday',
				dataIndex: 'contact_bday',
				hidden: true
			},{
				resizable: true,
				header: 'Organisation',
				dataIndex: 'org_name'
			},{
				resizable: true,
				header: 'Unit',
				dataIndex: 'org_unit'
			},{
				resizable: true,
				header: 'Title',
				dataIndex: 'contact_title',
				hidden: true
			},{
				resizable: true,
				header: 'Role',
				dataIndex: 'contact_role',
				hidden: true
			},{
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
				console.log(1);
				btns.editbtn.disable();
				btns.deletebtn.disable();
			} else if(rowCount == 1) {
				console.log(2);
				btns.editbtn.enable();
				btns.deletebtn.enable();
			} else {
				console.log(3);
				btns.editbtn.disable();
				btns.deletebtn.enable();
			}
		});
	}

	var _openDialog = function() {
		appId = 'addressbook';
		var popup = window.open('index.php?application=addressbook','popupname','width=400,height=400,directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no');

		return;
	}
	
	var _showEditDialog = function() {
		var simple = new Ext.form.Form({
			labelWidth: 75, // label settings here cascade unless overridden
			url:'jsonrpc.php?application=Addressbook_Json&func=editAddress'
		});
		
		simple.add(
			new Ext.form.TextField({
				fieldLabel: 'First Name',
				name: 'n_given',
				width:175
			}),
			
			new Ext.form.TextField({
				fieldLabel: 'Last Name',
				name: 'n_family',
				width:175,
				allowBlank:false
			}),
			
			new Ext.form.TextField({
				fieldLabel: 'Company',
				name: 'org_name',
				width:175
			}),
			
			new Ext.form.TextField({
				fieldLabel: 'Email',
				name: 'contact_email',
				vtype:'email',
				width:175
			})
		);
		
		simple.addButton('Save', function (){
			if (simple.isValid()) {
				simple.submit({
					waitMsg:'submitting, please wait...',
					success:function(form, action, o) {
						//Ext.MessageBox.alert("Information",action.result.welcomeMessage);
						window.opener.EGWNameSpace.Addressbook.reload();
						window.close();
					},
					failure:function(form, action) {
						Ext.MessageBox.alert("Error",action.result.errorMessage);
					}
				});
			}else{
				Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
			}
		}, simple);
		
		simple.addButton('Cancel', function (){
			window.close()
		});
		
		simple.render('content');	
			
	};

	// public stuff
	return {
		// public functions
		show: function(_layout) {
			_showAddressGrid(_layout);
		},
		
		reload: function() {
			ds.load();
		},
		
		handleDragDrop: function(e) {
			alert('Best Regards From Addressbook');
		},
		
		openDialog: function() {
			_openDialog();
		},

		alertme: function() {
			_showEditDialog();
		}
	}
	
}(); // end of application

