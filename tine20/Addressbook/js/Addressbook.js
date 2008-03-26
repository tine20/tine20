Ext.namespace('Tine.Addressbook');

Tine.Addressbook = {

    getPanel: function()
    {
        var treePanel =  new Tine.widgets.container.TreePanel({
            id: 'Addressbook_Tree',
            iconCls: 'AddressbookTreePanel',
            title: 'Contacts',
            itemName: 'contacts',
            folderName: 'addressbook',
            appName: 'Addressbook',
            border: false,
            extraItems:[{
	            text: "Internal Contacts",
	            cls: "file",
	            containerType: 'internalContainer',
	            id: "internal",
	            children: [],
	            leaf: false,
	            expanded: true
	        }] 
        });
        
        treePanel.on('click', function(_node, _event) {
            Tine.Addressbook.Main.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
        
        return treePanel;       
    }
}

Tine.Addressbook.Main = {
	actions: {
	    addContact: null,
	    editContact: null,
	    deleteContact: null,
	    exportContact: null,
	},
	
	handlers: {
	    /**
	     * onclick handler for addBtn
	     */
	    addContact: function(_button, _event) {
	        Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
	    },

        /**
         * onclick handler for editBtn
         */
        editContact: function(_button, _event) {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
            var contactId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + contactId, 850, 600);
        },

        /**
         * onclick handler for exportBtn
         */
        exportContact: function(_button, _event) {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
            var contactId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContact&_format=pdf&_contactId=' + contactId, 768, 1024);
        },
        
	    /**
	     * onclick handler for deleteBtn
	     */
	    deleteContact: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected contacts?', function(_button){
	            if (_button == 'yes') {
	            
	                var contactIds = new Array();
	                var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
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
	                    text: 'Deleting contact(s)...',
	                    success: function(_result, _request){
	                        Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
	                    }
	                });
	            }
	        });
	    }    
	},
	
	renderer: {
        contactTid: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
	        switch(_data) {
	            default:
	                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
	        }
	    }		
	},

    initComponent: function()
    {
        this.actions.addContact = new Ext.Action({
            text: 'add contact',
            handler: this.handlers.addContact,
            iconCls: 'action_addContact',
            scope: this
        });
        
        this.actions.editContact = new Ext.Action({
            text: 'edit contact',
            disabled: true,
            handler: this.handlers.editContact,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteContact = new Ext.Action({
            text: 'delete contact',
            disabled: true,
            handler: this.handlers.deleteContact,
            iconCls: 'action_delete',
            scope: this
        });

        this.actions.exportContact = new Ext.Action({
            text: 'export contact',
            disabled: true,
            handler: this.handlers.exportContact,
            iconCls: 'action_export',
            scope: this
        });
    },
	
    displayContactsToolbar: function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('Addressbook_Contacts_Grid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var contactToolbar = new Ext.Toolbar({
            id: 'Addressbook_Contacts_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addContact, 
                this.actions.editContact,
                this.actions.deleteContact,
                this.actions.exportContact,
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(contactToolbar);
    },

    displayContactsGrid: function() 
    {
    	// the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Addressbook.Model.Contact,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('n_family', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying contacts {0} - {1} of {2}',
            emptyMsg: "No contacts to display"
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'tid', header: 'Type', dataIndex: 'tid', width: 30, renderer: this.renderer.contactTid },
            { resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family' },
            { resizable: true, id: 'n_given', header: 'Given name', dataIndex: 'n_given', width: 80 },
            { resizable: true, id: 'n_fn', header: 'Full name', dataIndex: 'n_fn', hidden: true },
            { resizable: true, id: 'n_fileas', header: 'Name + Firm', dataIndex: 'n_fileas', hidden: true },
            { resizable: true, id: 'email', header: 'eMail', dataIndex: 'email', width: 150, hidden: false },
            { resizable: true, id: 'bday', header: 'Birthday', dataIndex: 'bday', hidden: true },
            { resizable: true, id: 'org_name', header: 'Organisation', dataIndex: 'org_name', width: 200 },
            { resizable: true, id: 'org_unit', header: 'Unit', dataIndex: 'org_unit' , hidden: true },
            { resizable: true, id: 'title', header: 'Title', dataIndex: 'title', hidden: true },
            { resizable: true, id: 'role', header: 'Role', dataIndex: 'role', hidden: true },
            { resizable: true, id: 'room', header: 'Room', dataIndex: 'room', hidden: true },
            { resizable: true, id: 'adr_one_street', header: 'Street', dataIndex: 'adr_one_street', hidden: true },
            { resizable: true, id: 'adr_one_locality', header: 'Locality', dataIndex: 'adr_one_locality', width: 150, hidden: false },
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
            { resizable: true, id: 'email_home', header: 'eMail (private)', dataIndex: 'email_home', hidden: true },
            { resizable: true, id: 'url', header: 'URL', dataIndex: 'url', hidden: true },
            { resizable: true, id: 'url_home', header: 'URL (private)', dataIndex: 'url_home', hidden: true },
            { resizable: true, id: 'note', header: 'Note', dataIndex: 'note', hidden: true },
            { resizable: true, id: 'tz', header: 'Timezone', dataIndex: 'tz', hidden: true },
            { resizable: true, id: 'geo', header: 'Geo', dataIndex: 'geo', hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteContact.setDisabled(true);
                this.actions.editContact.setDisabled(true);
                this.actions.exportContact.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteContact.setDisabled(false);
                this.actions.editContact.setDisabled(true);
                this.actions.exportContact.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteContact.setDisabled(false);
                this.actions.editContact.setDisabled(false);
                this.actions.exportContact.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Addressbook_Contacts_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'n_family',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No contacts to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
		        id:'ctxMenuContacts', 
		        items: [
		            this.actions.editContact,
		            this.actions.deleteContact,
		            this.actions.exportContact,
		            '-',
		            this.actions.addContact 
		        ]
		    });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.data.id, 850, 600);
            } catch(e) {
                // alert(e);
            }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function(_node)
    {
        var dataStore = Ext.getCmp('Addressbook_Contacts_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.containerType) {
            case 'internalContainer':
                dataStore.baseParams.method = 'Addressbook.getAccounts';
                break;

            case Tine.Tinebase.container.TYPE_SHARED:
                dataStore.baseParams.method = 'Addressbook.getSharedContacts';
                break;

            case 'OtherUsers':
                dataStore.baseParams.method = 'Addressbook.getOtherPeopleContacts';
                break;

            case 'all':
                dataStore.baseParams.method = 'Addressbook.getAllContacts';
                break;


            case Tine.Tinebase.container.TYPE_PERSONAL:
                dataStore.baseParams.method = 'Addressbook.getContactsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner.accountId;
                break;

            case 'singleContainer':
                dataStore.baseParams.method        = 'Addressbook.getContactsByAddressbookId';
                dataStore.baseParams.addressbookId = _node.attributes.container.id;
                break;                
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function(_node) 
    {
    	this.initComponent();
    	
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Addressbook_Contacts_Toolbar') {
            this.displayContactsToolbar();
            this.displayContactsGrid();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Addressbook_Contacts_Grid')) {
            setTimeout ("Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload()", 200);
        }
    }
}

Tine.Addressbook.ContactEditDialog = {
	handlers: {
	    applyChanges: function(_button, _event, _closeWindow) 
	    {
            var form = Ext.getCmp('contactDialog').getForm();

            if(form.isValid()) {
                form.updateRecord(Tine.Addressbook.ContactEditDialog.contactRecord);
        
                Ext.Ajax.request({
                    params: {
                        method: 'Addressbook.saveContact', 
                        contactData: Ext.util.JSON.encode(Tine.Addressbook.ContactEditDialog.contactRecord.data)
                    },
                    success: function(_result, _request) {
                    	if(window.opener.Tine.Addressbook) {
                            window.opener.Tine.Addressbook.Main.reload();
                    	}
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            //this.updateContactRecord(Ext.util.JSON.decode(_result.responseText));
                            //this.updateToolbarButtons(formData.config.addressbookRights);
                            //form.loadRecord(this.contactRecord);
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save account.'); 
                    },
                    scope: this 
                });
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
	    },

	    saveAndClose: function(_button, _event) 
        {
            this.handlers.applyChanges(_button, _event, true);
        },

	    deleteContact: function(_button, _event) 
	    {
	        var contactIds = Ext.util.JSON.encode([formData.values.id]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Addressbook.deleteContacts', 
	                _contactIds: contactIds
	            },
	            text: 'Deleting contact...',
	            success: function(_result, _request) {
                    if(window.opener.Tine.Addressbook) {
	                   window.opener.Tine.Addressbook.reload();
                    }
                    window.close();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.'); 
	            } 
	        });                           
	    },
	    
	    exportContact: function(_button, _event) 
	    {
	        var contactIds = Ext.util.JSON.encode([formData.values.id]);

	        //@todo implement
	    }
	},
	
	editContactDialog: [{
        layout:'column',
        border:false,
        //deferredRender:false,
        //anchor:'100%',
        autoHeight: true,
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
            },
            new Tine.widgets.container.selectionComboBox({
                fieldLabel:'Addressbook',
                name: 'owner',
                anchor:'95%',
                itemName: 'Addressbook',
                appName: 'Addressbook'
            })]
        }, {
            columnWidth:.4,
            layout: 'form',
            border:false,
            items: [{
                xtype:'textarea',
                name: 'note',
                fieldLabel: 'Notes',
                grow: false,
                preventScrollbars:false,
                anchor:'95%',
                height: 120
            }]
        }]
    },{
        xtype:'tabpanel',
        plain:true,
        activeTab: 0,
        deferredRender:false,
        anchor:'100%',
        defaults:{bodyStyle:'padding:10px'},
        border: false,
        items:[{
            title:'Business information',
            layout:'column',
            deferredRender:false,
            border:false,
            autoHeight: true,
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
                    store: new Ext.data.JsonStore({
			            baseParams: {
			            	method:'Tinebase.getCountryList'
			            },
			            root: 'results',
			            id: 'shortName',
			            fields: ['shortName', 'translatedName'],
			            remoteSort: false
			        }),
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
                    name:'email', 
                    vtype:'email',
                    anchor:'95%'
                },{
                    xtype:'textfield',
                    fieldLabel:'URL', 
                    name:'url', 
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
                    name:'role',
                    anchor:'95%'
                }, {
                    xtype:'textfield',
                    fieldLabel:'Title', 
                    name:'title',
                    anchor:'95%'
                }, {
                    xtype:'textfield',
                    fieldLabel:'Room', 
                    name:'room',
                    anchor:'95%'
                },{
                    xtype:'textfield',
                    fieldLabel:'Name Assistent', 
                    name:'assistent',
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
                    store: new Ext.data.JsonStore({
			            baseParams: {
			            	method:'Tinebase.getCountryList'
			            },
			            root: 'results',
			            id: 'shortName',
			            fields: ['shortName', 'translatedName'],
			            remoteSort: false
			        }),
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
                            name:'bday', 
                            format:'d.m.Y', 
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
                    fieldLabel:'Email', name:'email_home', vtype:'email',
                    anchor:'95%'
                },{
                    xtype:'textfield',
                    fieldLabel:'URL', name:'url_home', vtype:'url',
                    anchor:'95%'
                }]
            },{
                columnWidth:.333,
                layout: 'form',
                border:false//,
                //items: [
                //    new Ext.form.FieldSet({
                //        id:'photo', 
                //        legend:'Photo'
                // })
                //]
            }]
        }]
    }],

    contactRecord: null,
    
    updateContactRecord: function(_contactData)
    {
        if(_contactData.bday && _contactData.bday !== null) {
            _contactData.bday = Date.parseDate(_contactData.bday, 'c');
        }

        this.contactRecord = new Tine.Addressbook.Model.Contact(_contactData);
    },

    updateToolbarButtons: function(_rights)
    {
        if(_rights & 4) {
            Ext.getCmp('contactDialog').action_saveAndClose.enable();
            Ext.getCmp('contactDialog').action_applyChanges.enable();
            // which rights are needed for pdf export?
	        Ext.getCmp('contactDialog').action_export.enable();
        }

        if(_rights & 8) {
            Ext.getCmp('contactDialog').action_delete.enable();
        }
        
    },

    display: function(_contactData) 
    {
        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'contactDialog',
            //title: 'the title',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteContact,
            handlerExport: this.handlers.exportContact,
            items: this.editContactDialog
        });

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateContactRecord(_contactData);
        this.updateToolbarButtons(_contactData.owner.account_grants);
        
        dialog.getForm().loadRecord(this.contactRecord);
        
        if(this.contactRecord.data.adr_one_countrydisplayname) {
            //console.log('set adr_one_countryname to ' + this.contactRecord.data.adr_one_countrydisplayname);
            dialog.getForm().findField('adr_one_countryname').setRawValue(this.contactRecord.data.adr_one_countrydisplayname);
        }

        if(this.contactRecord.data.adr_two_countrydisplayname) {
            //console.log('set adr_two_countryname to ' + this.contactRecord.data.adr_two_countrydisplayname);
            dialog.getForm().findField('adr_two_countryname').setRawValue(this.contactRecord.data.adr_two_countrydisplayname);
        }
    }
    
}

Ext.namespace('Tine.Addressbook.Model');

Tine.Addressbook.Model.Contact = Ext.data.Record.create([
    {name: 'id'},
    {name: 'tid'},
    {name: 'owner'},
    {name: 'private'},
    {name: 'cat_id'},
    {name: 'n_family'},
    {name: 'n_given'},
    {name: 'n_middle'},
    {name: 'n_prefix'},
    {name: 'n_suffix'},
    {name: 'n_fn'},
    {name: 'n_fileas'},
    {name: 'bday', type: 'date', dateFormat: 'c' },
    {name: 'org_name'},
    {name: 'org_unit'},
    {name: 'title'},
    {name: 'role'},
    {name: 'assistent'},
    {name: 'room'},
    {name: 'adr_one_street'},
    {name: 'adr_one_street2'},
    {name: 'adr_one_locality'},
    {name: 'adr_one_region'},
    {name: 'adr_one_postalcode'},
    {name: 'adr_one_countryname'},
    {name: 'label'},
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
    {name: 'email'},
    {name: 'email_home'},
    {name: 'url'},
    {name: 'url_home'},
    {name: 'freebusy_uri'},
    {name: 'calendar_uri'},
    {name: 'note'},
    {name: 'tz'},
    {name: 'geo'},
    {name: 'pubkey'},
    {name: 'created'},
    {name: 'creator'},
    {name: 'modified'},
    {name: 'modifier'},
    {name: 'jpegphoto'},
    {name: 'account_id'}
]);
