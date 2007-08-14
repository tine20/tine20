Ext.namespace('Egw');

var EGWNameSpace = Egw;

Egw.Addressbook = function() {

    var ds;
    
    var dialog;

    //Ext.namespace('Ext.exampledata');

    // private function
    var _showAddressGrid = function(_layout, _node) {

        var center = _layout.getRegion('center', false);

        // remove the first contentpanel from center region
        center.remove(0);
		
        // add a div, which will bneehe parent element for the grid
        var contentTag = Ext.Element.get('content');
        var outerDivTag = contentTag.createChild({tag: 'div',id: 'outergriddiv'});

        // create the Data Store
        ds = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {method:'Addressbook.getData', _datatype:'address', nodeid:_node.attributes.id},
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
		
        //ds.on("beforeload", function() {
        //  console.log('before load');
        //});

        ds.setDefaultSort('contact_id', 'desc');

        ds.load({params:{start:0, limit:50}});

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
            icon:'images/oxygen/16x16/actions/add-user.png',
            tooltip: 'add new contact',
            onClick: function() {
                _openDialog();
            }
        });

        pagingHeader.insertButton(1, {
            id: 'editbtn',
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/edit-user.png',
            tooltip: 'edit current contact',
            disabled: true,
            onClick: function() {
                _openDialog();
            }
        });

        pagingHeader.insertButton(2, {
            id: 'deletebtn',
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/delete-user.png',
            tooltip: 'delete selected contacts',
            disabled: true,
            onClick: function() {
                var deletedRows = Array();
                var selectedRows = grid.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    deletedRows.push(selectedRows[i].id);
                }
                _deleteContact(deletedRows, function() {EGWNameSpace.Addressbook.reload();});
                ds.reload();
            }
        });

        pagingHeader.insertButton(3, {
            id: 'exportbtn',
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/file-export.png',
            tooltip: 'export selected contacts',
            disabled: false,
            onClick: _openDialog
        });

        pagingHeader.insertButton(4, new Ext.Toolbar.Separator());

        center.add(new Ext.GridPanel(grid));
		
        grid.on('rowclick', function(gridP, rowIndexP, eventP) {
            var rowCount = grid.getSelectionModel().getCount();
			
            var btns = pagingHeader.items.map;
			
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

        grid.on('rowdblclick', function(gridPar, rowIndexPar, ePar) {
            var record = gridPar.getDataSource().getAt(rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            try {
                _openDialog(record.data.contact_id);
            } catch(e) {
            //	alert(e);
            }
        });
		
        grid.on('rowcontextmenu', function(grid, rowIndex, eventObject) {
            eventObject.stopEvent();
            ctxMenuAddress.showAt(eventObject.getXY());
        });
    }
	
    var ctxMenuAddress = new Ext.menu.Menu({
        id:'ctxMenuAddress', 
        items: [{
            id:'edit',
            text:'edit contact',
            icon:'images/oxygen/16x16/actions/edit-user.png'
        },{
            id:'delete',
            text:'delete contact',
            icon:'images/oxygen/16x16/actions/delete-user.png'
        },'-',{
            id:'new',
            text:'new contact',
            icon:'images/oxygen/16x16/actions/add-user.png'
        }]
    });


    var _openDialog = function(_id) {
        var url;
        
        if(_id) {
            url = 'index.php?getpopup=addressbook.editcontact&contactid=' + _id;
        } else {
            url = 'index.php?getpopup=addressbook.editcontact';
        }
        //console.log(url);
        appId = 'addressbook';
        var popup = window.open(
            url, 
            'popupname',
            'width=950,height=600,directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=no,dependent=no'
        );
        
        return;
    }
	
    var _reloadMainWindow = function(closeCurrentWindow) {
        var closeCurrentWindow = (closeCurrentWindow == null) ? false : closeCurrentWindow;
        
        window.opener.EGWNameSpace.Addressbook.reload();
        if(closeCurrentWindow == true) {
            window.setTimeout("window.close()", 400);
        }
    }
	
    var _deleteContact = function(_contactIDs, _onSuccess, _onError) {
        var contactIDs = Ext.util.JSON.encode(_contactIDs);
        new Ext.data.Connection().request({
            url: 'index.php',
            method: 'post',
            scope: this,
            params: {method:'Addressbook.deleteAddress', _contactIDs:contactIDs},
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
            icon:'images/oxygen/16x16/actions/document-save.png',
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
                            window.opener.EGWNameSpace.Addressbook.reload();
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
        },{
            id: 'savebtn',
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/save-all.png',
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
                            window.opener.EGWNameSpace.Addressbook.reload();
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
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/edit-delete.png',
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
            cls:'x-btn-icon',
            icon:'images/oxygen/16x16/actions/file-export.png',
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
        
        addressedit.fieldset({legend:'Contact information'});
        
        addressedit.column(
            {width:'33%', labelWidth:90, labelSeparator:''},
            new Ext.form.TextField({fieldLabel:'First Name', name:'n_given', width:175}),
            new Ext.form.TextField({fieldLabel:'Middle Name', name:'n_middle', width:175}),
            new Ext.form.TextField({fieldLabel:'Last Name', name:'n_family', width:175, allowBlank:false})
        );

        addressedit.column(
            {width:'33%', labelWidth:90, labelSeparator:''},
            new Ext.form.TextField({fieldLabel:'Prefix', name:'n_prefix', width:175}),
            new Ext.form.TextField({fieldLabel:'Suffix', name:'n_suffix', width:175})
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
                   		var containerTag 	= Ext.Element.get('container');
						var iWindowTag 		= containerTag.createChild({tag: 'div',id: 'iWindowTag'});
						var iWindowContTag 	= containerTag.createChild({tag: 'div',id: 'iWindowContTag'});

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
							reader : new Ext.data.JsonReader({root: 'results'}, [
								{name: 'list_id'},
								{name: 'list_realname'},					
							])
						});		
								
						var i= 1;									
						var checked = new Array();
						
						ds_checked.each( function(record){
							checked[record.data.list_id] = record.data.list_realname;						
						});
									
						ds_lists.each( function(fields){
						if( (i % 12) == 1) listsedit.column({width:'33%', labelWidth:50, labelSeparator:''});
								
						if(checked[fields.data.list_id]) listsedit.add(new Ext.form.Checkbox({boxLabel: fields.data.list_realname, name: fields.data.list_realname, checked: true}));
						else listsedit.add(new Ext.form.Checkbox({boxLabel: fields.data.list_realname, name: fields.data.list_realname}));
						if( (i % 12) == 0) listsedit.end();
				
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
								 Ext.MessageBox.alert('Todo', 'Not yet implemented!');}, dialog);
								
							dialog.addButton("cancel", function() {
								window.location.reload(); dialog.hide}, dialog);						

							var layout = dialog.getLayout();
							layout.beginUpdate();
							layout.add("center", new Ext.ContentPanel('iWindowContTag', {
									autoCreate:true, title: 'Lists'}));
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

    // public stuff
    return {
        // public functions
        show: _showAddressGrid,
        
        reload: function() {
            ds.reload();
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

