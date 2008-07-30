Ext.namespace('Tine.Addressbook');

/**************************** panel ****************************************/

Tine.Addressbook = {

    getPanel: function()
    {
        var translation = new Locale.Gettext();
        translation.textdomain('Addressbook');
    	
        var accountBackend = Tine.Tinebase.Registry.get('accountBackend');
        if (accountBackend == 'Sql') {
            var internalContactsleaf = {
                text: translation._("Internal Contacts"),
                cls: "file",
                containerType: 'internal',
                id: "internal",
                children: [],
                leaf: false,
                expanded: true
            };
        }
        
        var treePanel =  new Tine.widgets.container.TreePanel({
            id: 'Addressbook_Tree',
            iconCls: 'AddressbookIconCls',
            title: translation._('Addressbook'),
            itemName: translation._('contacts'),
            folderName: 'addressbook',
            appName: 'Addressbook',
            border: false,
            extraItems: internalContactsleaf ? internalContactsleaf : [] 
        });
        
        treePanel.on('click', function(_node, _event) {
            Tine.Addressbook.Main.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
        
        return treePanel;       
    }
};

/**************************** main dialog **********************************/

Tine.Addressbook.Main = {
	actions: {
	    addContact: null,
	    editContact: null,
	    deleteContact: null,
	    exportContact: null,
	    callContact: null
	},
	
    /**
     * holds underlaying store
     */
    store: null,
    
    /**
     * @cfg {Object} paging defaults
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'n_family',
        dir: 'ASC'
    },
    
    /**
     * @cfg {Array} default filters
     * @todo container filters not in filter logig yet!
     * @see store.on(beforeload)
     */
    //filter: [],
    	
	handlers: {
	    /**
	     * onclick handler for addBtn
	     */
	    addContact: function(_button, _event) 
	    {
            var popupWindow = new Tine.Addressbook.EditPopup({
                //contactId:
                //containerId:
            });
        },

        /**
         * onclick handler for editBtn
         */
        editContact: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
            var contactId = selectedRows[0].data.id;
            
            var popupWindow = new Tine.Addressbook.EditPopup({
                contactId: contactId
                //containerId:
            });            
        },

        /**
         * onclick handler for exportBtn
         */
        exportContact: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();            
            var toExportIds = [];
        
            for (var i = 0; i < selectedRows.length; ++i) {
                toExportIds.push(selectedRows[i].data.id);
            }
            
            var contactIds = Ext.util.JSON.encode(toExportIds);

            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContact&_format=pdf&_contactIds=' + contactIds, 768, 1024);
        },

        /**
         * onclick handler for exportBtn
         */
        callContact: function(_button, _event) 
        {
            var number;

            var contact = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelected();

            switch(_button.getId()) {
                case 'Addressbook_Contacts_CallContact_Work':
                    number = contact.data.tel_work;
                    break;
                case 'Addressbook_Contacts_CallContact_Home':
                    number = contact.data.tel_home;
                    break;
                case 'Addressbook_Contacts_CallContact_Cell':
                    number = contact.data.tel_cell;
                    break;
                case 'Addressbook_Contacts_CallContact_CellPrivate':
                    number = contact.data.tel_cell_private;
                    break;
                default:
                    if(!Ext.isEmpty(contact.data.tel_work)) {
                    	number = contact.data.tel_work;
                    } else if (!Ext.isEmpty(contact.data.tel_cell)) {
                        number = contact.data.tel_cell;
                    } else if (!Ext.isEmpty(contact.data.tel_cell_private)) {
                        number = contact.data.tel_cell_private;
                    } else if (!Ext.isEmpty(contact.data.tel_home)) {
                    	number = contact.data.tel_work;
                    }
                    break;
            }

            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Dialer.dialNumber',
                    number: number
                },
                success: function(_result, _request){
                    //Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
                },
                failure: function(result, request){
                    //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
                }
            });
        },
        
        
	    /**
	     * onclick handler for deleteBtn
	     */
	    deleteContact: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected contacts?', function(_button){
	            if (_button == 'yes') {
	            
	                var contactIds = [];
	                var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    contactIds.push(selectedRows[i].data.id);
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
            //switch(_data) {
            //    default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
            //}
	    }		
	},

    initComponent: function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
    
        this.actions.addContact = new Ext.Action({
            text: this.translation._('add contact'),
            handler: this.handlers.addContact,
            iconCls: 'action_addContact',
            scope: this
        });
        
        this.actions.editContact = new Ext.Action({
            text: this.translation._('edit contact'),
            disabled: true,
            handler: this.handlers.editContact,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteContact = new Ext.Action({
            text: this.translation._('delete contact'),
            disabled: true,
            handler: this.handlers.deleteContact,
            iconCls: 'action_delete',
            scope: this
        });

        this.actions.exportContact = new Ext.Action({
            text: this.translation._('export as pdf'),
            disabled: true,
            handler: this.handlers.exportContact,
            iconCls: 'action_exportAsPdf',
            scope: this
        });

        this.actions.callContact = new Ext.Action({
        	id: 'Addressbook_Contacts_CallContact',
            text: this.translation._('call contact'),
            disabled: true,
            handler: this.handlers.callContact,
            iconCls: 'DialerIconCls',
            menu: new Ext.menu.Menu({
                id: 'Addressbook_Contacts_CallContact_Menu'
            }),
            scope: this
        });
        
        // init grid store
        this.initStore();
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        /*menu.add(
            {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
        );*/

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Addressbook.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('AddressbookTreePanel');
        preferencesButton.setDisabled(true);
    },
	
    displayContactsToolbar: function()
    {
        var contactToolbar = new Ext.Toolbar({
            id: 'Addressbook_Contacts_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addContact, 
                this.actions.editContact,
                this.actions.deleteContact,
                '-',
                this.actions.exportContact,
                ( Tine.Dialer && Tine.Dialer.rights && Tine.Dialer.rights.indexOf('run') > -1 ) ? new Ext.Toolbar.MenuButton(this.actions.callContact) : '',
                //'->',
                //this.translation._('Filter: '), tagFilter,
                //this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(contactToolbar);
    },

    displayContactsGrid: function() 
    {
        // the filter toolbar
        var filterToolbar = new Tine.widgets.FilterToolbar({
            id : 'addressbookFilterToolbar',
            filterModel: [
                {label: this.translation._('Contact'), field: 'query', opfilter: ['contains'], opdefault: 'contains'},
                {label: this.translation._('First Name'), field: 'n_given', opdefault: 'contains'},
                {label: this.translation._('Last Name'), field: 'n_family', opdefault: 'contains'},
                {label: this.translation._('Company'), field: 'org_name', opdefault: 'contains'},
                {label: this.translation._('Postal Code') + ' (' + this.translation._('Company Address') + ')', field: 'adr_one_postalcode', opdefault: 'equals'},
                {label: this.translation._('City') + '  (' + this.translation._('Company Address') + ')', field: 'adr_one_locality', opdefault: 'contains'}
                //{label: 'Full Name', field: 'n_fn', opdefault: 'equals'},
                //{label: 'Container', field: 'owner'},
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        filterToolbar.on('filtertrigger', function() {
            this.store.load({});
        }, this);
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying contacts {0} - {1} of {2}'),
            emptyMsg: this.translation._("No contacts to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'tid', header: this.translation._('Type'), dataIndex: 'tid', width: 30, renderer: this.renderer.contactTid },
            { resizable: true, id: 'n_family', header: this.translation._('Last Name'), dataIndex: 'n_family', hidden: true },
            { resizable: true, id: 'n_given', header: this.translation._('First Name'), dataIndex: 'n_given', width: 80, hidden: true },
            { resizable: true, id: 'n_fn', header: this.translation._('Full Name'), dataIndex: 'n_fn', hidden: true },
            { resizable: true, id: 'n_fileas', header: this.translation._('Display Name'), dataIndex: 'n_fileas'},
            { resizable: true, id: 'org_name', header: this.translation._('Company'), dataIndex: 'org_name', width: 200 },
            { resizable: true, id: 'org_unit', header: this.translation._('Unit'), dataIndex: 'org_unit' , hidden: true },
            { resizable: true, id: 'title', header: this.translation._('Job Title'), dataIndex: 'title', hidden: true },
            { resizable: true, id: 'role', header: this.translation._('Job Role'), dataIndex: 'role', hidden: true },
            { resizable: true, id: 'room', header: this.translation._('Room'), dataIndex: 'room', hidden: true },
            { resizable: true, id: 'adr_one_street', header: this.translation._('Street'), dataIndex: 'adr_one_street', hidden: true },
            { resizable: true, id: 'adr_one_locality', header: this.translation._('City'), dataIndex: 'adr_one_locality', width: 150, hidden: false },
            { resizable: true, id: 'adr_one_region', header: this.translation._('Region'), dataIndex: 'adr_one_region', hidden: true },
            { resizable: true, id: 'adr_one_postalcode', header: this.translation._('Postalcode'), dataIndex: 'adr_one_postalcode', hidden: true },
            { resizable: true, id: 'adr_one_countryname', header: this.translation._('Country'), dataIndex: 'adr_one_countryname', hidden: true },
            { resizable: true, id: 'adr_two_street', header: this.translation._('Street (private)'), dataIndex: 'adr_two_street', hidden: true },
            { resizable: true, id: 'adr_two_locality', header: this.translation._('City (private)'), dataIndex: 'adr_two_locality', hidden: true },
            { resizable: true, id: 'adr_two_region', header: this.translation._('Region (private)'), dataIndex: 'adr_two_region', hidden: true },
            { resizable: true, id: 'adr_two_postalcode', header: this.translation._('Postalcode (private)'), dataIndex: 'adr_two_postalcode', hidden: true },
            { resizable: true, id: 'adr_two_countryname', header: this.translation._('Country (private)'), dataIndex: 'adr_two_countryname', hidden: true },
            { resizable: true, id: 'email', header: this.translation._('Email'), dataIndex: 'email', width: 150},
            { resizable: true, id: 'tel_work', header: this.translation._('Phone'), dataIndex: 'tel_work', hidden: false },
            { resizable: true, id: 'tel_cell', header: this.translation._('Mobile'), dataIndex: 'tel_cell', hidden: false },
            { resizable: true, id: 'tel_fax', header: this.translation._('Fax'), dataIndex: 'tel_fax', hidden: true },
            { resizable: true, id: 'tel_car', header: this.translation._('Car phone'), dataIndex: 'tel_car', hidden: true },
            { resizable: true, id: 'tel_pager', header: this.translation._('Pager'), dataIndex: 'tel_pager', hidden: true },
            { resizable: true, id: 'tel_home', header: this.translation._('Phone (private)'), dataIndex: 'tel_home', hidden: true },
            { resizable: true, id: 'tel_fax_home', header: this.translation._('Fax (private)'), dataIndex: 'tel_fax_home', hidden: true },
            { resizable: true, id: 'tel_cell_private', header: this.translation._('Mobile (private)'), dataIndex: 'tel_cell_private', hidden: true },
            { resizable: true, id: 'email_home', header: this.translation._('Email (private)'), dataIndex: 'email_home', hidden: true },
            { resizable: true, id: 'url', header: this.translation._('Web'), dataIndex: 'url', hidden: true },
            { resizable: true, id: 'url_home', header: this.translation._('URL (private)'), dataIndex: 'url_home', hidden: true },
            { resizable: true, id: 'note', header: this.translation._('Note'), dataIndex: 'note', hidden: true },
            { resizable: true, id: 'tz', header: this.translation._('Timezone'), dataIndex: 'tz', hidden: true },
            { resizable: true, id: 'geo', header: this.translation._('Geo'), dataIndex: 'geo', hidden: true },
            { resizable: true, id: 'bday', header: this.translation._('Birthday'), dataIndex: 'bday', hidden: true }
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
                this.actions.callContact.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteContact.setDisabled(false);
                this.actions.editContact.setDisabled(true);
                this.actions.exportContact.setDisabled(false);
                this.actions.callContact.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteContact.setDisabled(false);
                this.actions.editContact.setDisabled(false);
                this.actions.exportContact.setDisabled(false);
                
                if(Tine.Dialer && Tine.Dialer.rights && Tine.Dialer.rights.indexOf('run') > -1) {
	                var callMenu = Ext.menu.MenuMgr.get('Addressbook_Contacts_CallContact_Menu');
	                callMenu.removeAll();
	                var contact = _selectionModel.getSelected();
	                if(!Ext.isEmpty(contact.data.tel_work)) {
		                callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Work', 
		                   text: 'work ' + contact.data.tel_work + '',
		                   handler: this.handlers.callContact
		                });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_home)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Home', 
	                       text: 'home ' + contact.data.tel_home + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_cell)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_Cell', 
	                       text: 'cell ' + contact.data.tel_cell + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
	                if(!Ext.isEmpty(contact.data.tel_cell_private)) {
	                    callMenu.add({
	                       id: 'Addressbook_Contacts_CallContact_CellPrivate', 
	                       text: 'cell private ' + contact.data.tel_cell_private + '',
	                       handler: this.handlers.callContact
	                    });
	                    this.actions.callContact.setDisabled(false);
	                }
                }
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Addressbook_Contacts_Grid',
            store: this.store,
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
            try {
                var popupWindow = new Tine.Addressbook.EditPopup({
                    contactId: record.data.id
                    //containerId:
                });                        
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteContact();
             }
        }, this);

        // temporary resizeing
        filterToolbar.on('bodyresize', function(ftb, w, h) {
            var availableGridHeight = Ext.getCmp('center-panel').getSize().height;
            gridPanel.setHeight(availableGridHeight - h);
        }, this);
        
        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(new Ext.Panel({
            border: false,
            layout: 'fit',
            tbar: filterToolbar,
            items: gridPanel
        }));
    },
    
    /**
     * init the contacts json grid store
     */
    initStore: function(){

        this.store = new Ext.data.JsonStore({
            idProperty: 'id',
            autoLoad: false,
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Addressbook.Model.Contact,
            remoteSort: true,
            baseParams: {
                method: 'Addressbook.searchContacts'
            },
            sortInfo: {
                field: this.paging.sort,
                direction: this.paging.dir
            }
        });
        
        // register store
        Ext.StoreMgr.add('ContactsGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            if (!options.params) {
                options.params = {};
            }
            
            // paging toolbar only works with this properties in the options!
            options.params.sort  = store.getSortState() ? store.getSortState().field : this.paging.sort,
            options.params.dir   = store.getSortState() ? store.getSortState().direction : this.paging.dir,
            options.params.start = options.params.start ? options.params.start : this.paging.start,
            options.params.limit = options.params.limit ? options.params.limit : this.paging.limit
            
            options.params.paging = Ext.util.JSON.encode(options.params);
            
            var filterToolbar = Ext.getCmp('addressbookFilterToolbar');
            var filter = filterToolbar ? filterToolbar.getFilter() : [];
            
            // add container to filter
            var nodeAttributes = Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode().attributes || {};
            filter.push(
                {field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                {field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                {field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.accountId : null        }
            );
            
            options.params.filter = Ext.util.JSON.encode(filter);
        }, this);
                        
        this.store.load({});
    },
    
    show: function(_node) 
    {
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Addressbook_Contacts_Toolbar') {
            this.initComponent();
            this.displayContactsToolbar();
            this.displayContactsGrid();
            this.updateMainToolbar();
        } else {
            // note: if node is clicked, it is not selected!
            _node.getOwnerTree().selectPath(_node.getPath());
        	
            this.store.load({});        	
        }
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Addressbook_Contacts_Grid')) {
            setTimeout ("Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload()", 200);
        }
    }
};

/**************************** edit dialog **********************************/

Tine.Addressbook.ContactEditDialog = {
	handlers: {
	    applyChanges: function(_button, _event, _closeWindow) 
	    {
            var form = Ext.getCmp('contactDialog').getForm();

            if(form.isValid()) {
                Ext.MessageBox.wait('Please wait a moment...', 'Saving Contact');
                form.updateRecord(Tine.Addressbook.ContactEditDialog.contactRecord);
                Tine.Addressbook.ContactEditDialog.contactRecord.set('jpegphoto', Ext.getCmp('addressbookeditdialog-jpegimage').getValue());
        
                Ext.Ajax.request({
                    params: {
                        method: 'Addressbook.saveContact', 
                        contactData: Ext.util.JSON.encode(Tine.Addressbook.ContactEditDialog.contactRecord.data)
                    },
                    success: function(_result, _request) {
                    	if(window.opener.Tine.Addressbook) {
                            window.opener.Tine.Addressbook.Main.reload();
                    	}
                    	
                    	// update record
                    	var contactData = Ext.util.JSON.decode(_result.responseText).updatedData; 
                    	Tine.Addressbook.ContactEditDialog.updateContactRecord(contactData);
                    	form.loadRecord(Tine.Addressbook.ContactEditDialog.contactRecord);
                    	
                        // notify opener and return contact data 
                        opener.Ext.ux.PopupWindowMgr.get(window).fireEvent('update', Tine.Addressbook.ContactEditDialog.contactRecord);                    		
                    	
                        if(_closeWindow === true) {
                          	opener.Ext.ux.PopupWindowMgr.get(window).purgeListeners();
                            window.close();
                        } else {
                            this.updateToolbarButtons(contactData.owner.account_grants, contactData.id);
                            
                            Ext.MessageBox.hide();
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save contact.'); 
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
	        var contactIds = Ext.util.JSON.encode([Tine.Addressbook.ContactEditDialog.contactRecord.data.id]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Addressbook.deleteContacts', 
	                _contactIds: contactIds
	            },
	            text: 'Deleting contact...',
	            success: function(_result, _request) {
                    if(window.opener.Tine.Addressbook) {
                        window.opener.Tine.Addressbook.Main.reload();
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
	    	// we have to create an array (json encoded) as param here because exportContact expects one (for multiple contact export)
	    	var contactIds = Ext.util.JSON.encode([_button.contactId]);

            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContact&_format=pdf&_contactIds=' + contactIds, 200, 150);                   
	    }
	},

    contactRecord: null,
    
    updateContactRecord: function(_contactData)
    {
        if(_contactData.bday && _contactData.bday !== null) {
            _contactData.bday = Date.parseDate(_contactData.bday, 'c');
        }

        this.contactRecord = new Tine.Addressbook.Model.Contact(_contactData);
    },

    updateToolbarButtons: function(_rights, contactId)
    {    	
        with(_rights) {
            Ext.getCmp('contactDialog').action_saveAndClose.setDisabled(!editGrant);
            Ext.getCmp('contactDialog').action_applyChanges.setDisabled(!editGrant);
            Ext.getCmp('contactDialog').action_delete.setDisabled(!deleteGrant);
        }
        
        // add contact id to export button and enable it if id is set
        if (contactId) {
        	Ext.getCmp('exportButton').contactId = contactId;
            Ext.getCmp('exportButton').setDisabled(false);
        } else {
        	Ext.getCmp('exportButton').setDisabled(true);
        }
    },

    display: function(_contactData) 
    {
        // export lead handler for edit contact dialog
        var  _export_contact = new Ext.Action({
        	id: 'exportButton',
            text: 'export as pdf',
            handler: this.handlers.exportContact,
            iconCls: 'action_exportAsPdf',
            disabled: false
        });         

        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            layout: 'hfit',
            id : 'contactDialog',
            tbarItems: [_export_contact],
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteContact,
            handlerExport: this.handlers.exportContact,
            items: Tine.Addressbook.ContactEditDialog.getEditForm(_contactData)
        });
        dialog.on('render', function(dialog){
            dialog.getForm().findField('n_prefix').focus(false, 250);
        });
        
        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateContactRecord(_contactData);
        this.updateToolbarButtons(_contactData.owner.account_grants, _contactData.id);
        
        dialog.getForm().loadRecord(this.contactRecord);
        Ext.getCmp('addressbookeditdialog-jpegimage').setValue(this.contactRecord.get('jpegphoto'));
        
        if(this.contactRecord.data.adr_one_countrydisplayname) {
            //console.log('set adr_one_countryname to ' + this.contactRecord.data.adr_one_countrydisplayname);
            dialog.getForm().findField('adr_one_countryname').setRawValue(this.contactRecord.data.adr_one_countrydisplayname);
        }

        if(this.contactRecord.data.adr_two_countrydisplayname) {
            //console.log('set adr_two_countryname to ' + this.contactRecord.data.adr_two_countrydisplayname);
            dialog.getForm().findField('adr_two_countryname').setRawValue(this.contactRecord.data.adr_two_countrydisplayname);
        }
    }
    
};

/**************************** models ***************************************/

Ext.namespace('Tine.Addressbook.Model');

Tine.Addressbook.Model.ContactArray = [
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
    {name: 'account_id'},
    {name: 'tags'},
    {name: 'notes'}
];

Tine.Addressbook.Model.Contact = Ext.data.Record.create(
    Tine.Addressbook.Model.ContactArray
);

