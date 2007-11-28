Ext.namespace('Egw.Addressbook');

Egw.Addressbook = function(){
    /**
     * holds the current tree context menu
     *
     * gets set by Egw.Addressbook.setTreeContextMenu()
     */
    var _treeNodeContextMenu = null;

    /**
     * the initial tree to be displayed in the left treePanel
     */
    var _initialTree = [{
        text: 'All Addressbooks',
        cls: "treemain",
        nodeType: 'allAddressbooks',
        id: 'allAddressbooks',
        children: [{
            text: "Internal Contacts",
            cls: "file",
            nodeType: "internalAddressbook",
            id: "internalAddressbook",
            children: [],
            leaf: false,
            expanded: true
        }, {
            text: 'My Addressbooks',
            cls: 'file',
            nodeType: 'userAddressbooks',
            id: 'userAddressbooks',
            leaf: null,
            owner: Egw.Egwbase.Registry.get('currentAccount').account_id
        }, {
            text: "Shared Addressbooks",
            cls: "file",
            nodeType: "sharedAddressbooks",
            children: null,
            leaf: null
        }, {
            text: "Other Users Addressbooks",
            cls: "file",
            nodeType: "otherAddressbooks",
            children: null,
            leaf: null
        }]
    }];
    
    var _handler_addAddressbook = function(_button, _event) {
        Ext.MessageBox.prompt('New addressbook', 'Please enter the name of the new addressbook:', function(_btn, _text) {
            if(_treeNodeContextMenu !== null && _btn == 'ok') {

                //console.log(_treeNodeContextMenu);
                var type = 'personal';
                if(_treeNodeContextMenu.attributes.nodeType == 'sharedAddressbooks') {
                	type = 'shared';
                }
                
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Addressbook.addAddressbook',
                        name: _text,
                        type: type,
                        owner: _treeNodeContextMenu.attributes.owner
                    },
                    text: 'Creating new addressbook...',
                    success: function(_result, _request){
                        //Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
                        //_treeNodeContextMenu.expand(false, false);
                        //console.log(_result);
                        if(_treeNodeContextMenu.isExpanded()) {
                        	var responseData = Ext.util.JSON.decode(_result.responseText);
	                        var newNode = new Ext.tree.TreeNode({
	                            leaf: true,
	                            cls: 'file',
	                            nodeType: 'singleAddressbook',
	                            addressbookId: responseData.addressbookId,
	                            text: _text
	                        });
                            _treeNodeContextMenu.appendChild(newNode);
                        } else {
                        	_treeNodeContextMenu.expand(false);
                        }
                    },
                    failure: function(result, request){
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
                    }
                });
            }
        });
    };

    var _handler_renameAddressbook = function(_button, _event) {
        var resulter = function(_btn, _text) {
            if(_treeNodeContextMenu !== null && _btn == 'ok') {

                //console.log(_treeNodeContextMenu);
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Addressbook.renameAddressbook',
                        addressbookId: _treeNodeContextMenu.attributes.addressbookId,
                        name: _text
                    },
                    text: 'Renamimg addressbook...',
                    success: function(_result, _request){
                        _treeNodeContextMenu.setText(_text);
                    },
                    failure: function(result, request){
                        //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
                    }
                });
            }
        };
        
        Ext.MessageBox.show({
            title: 'Rename addressbook',
            msg: 'Please enter the new name of the addressbook:',
            buttons: Ext.MessageBox.OKCANCEL,
            value: _treeNodeContextMenu.text,
            fn: resulter,
            prompt: true,
            icon: Ext.MessageBox.QUESTION
        });
        
    };

    var _handler_deleteAddressbook = function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the addressbook ' + _treeNodeContextMenu.text + ' ?', function(_button){
            if (_button == 'yes') {
            
                //console.log(_treeNodeContextMenu);
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Addressbook.deleteAddressbook',
                        addressbookId: _treeNodeContextMenu.attributes.addressbookId
                    },
                    text: 'Deleting Addressbook...',
                    success: function(_result, _request){
                        if(_treeNodeContextMenu.isSelected()) {
                            Ext.getCmp('Addressbook_Tree').getSelectionModel().select(_treeNodeContextMenu.parentNode);
                            Ext.getCmp('Addressbook_Tree').fireEvent('click', _treeNodeContextMenu.parentNode);
                        }
                        _treeNodeContextMenu.remove();
                    },
                    failure: function(_result, _request){
                        Ext.MessageBox.alert('Failed', 'The addressbook could not be deleted.');
                    }
                });
            }
        });
    };

    var _action_addAddressbook = new Ext.Action({
        text: 'add addressbook',
        handler: _handler_addAddressbook
    });

    var _action_deleteAddressbook = new Ext.Action({
        text: 'delete addressbook',
        iconCls: 'action_delete',
        handler: _handler_deleteAddressbook
    });

    var _action_renameAddressbook = new Ext.Action({
        text: 'rename addressbook',
        iconCls: 'action_rename',
        handler: _handler_renameAddressbook
    });

    var _action_permisionsAddressbook = new Ext.Action({
    	disabled: true,
        text: 'permissions',
        handler: _handler_deleteAddressbook
    });

    var _contextMenuUserAddressbooks = new Ext.menu.Menu({
        items: [
            _action_addAddressbook
        ]
    });
    
    var _contextMenuSingleAddressbook= new Ext.menu.Menu({
        items: [
            _action_renameAddressbook,
            _action_deleteAddressbook,
            _action_permisionsAddressbook
        ]
    });
    
    /**
     * creates the address grid
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
                case 'otherAddressbooks':
                    _loader.baseParams.method   = 'Addressbook.getOtherUsers';
                    break;
                    
                case 'sharedAddressbooks':
                    _loader.baseParams.method   = 'Addressbook.getSharedAddressbooks';
                    break;

                case 'userAddressbooks':
                    _loader.baseParams.method   = 'Addressbook.getAddressbooksByOwner';
                    _loader.baseParams.owner    = _node.attributes.owner;
                    break;
            }
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Contacts',
            id: 'Addressbook_Tree',
            iconCls: 'AddressbookTreePanel',
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

        for(var i=0; i< _initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            Egw.Addressbook.Contacts.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root/allAddressbooks');
                _panel.selectPath('/root/allAddressbooks');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            _treeNodeContextMenu = _node;
            //console.log(_node.attributes.nodeType);
            switch(_node.attributes.nodeType) {
                case 'userAddressbooks':
                case 'sharedAddressbooks':
                    _contextMenuUserAddressbooks.showAt(_event.getXY());
                    break;

                case 'singleAddressbook':
                    _contextMenuSingleAddressbook.showAt(_event.getXY());
                    break;

                default:
                    //console.log(_node.attributes.nodeType);
                    break;
            }
        });

        return treePanel;
    };

    /**
     * reload main window
     *
     */
/*    var _reloadMainWindow = function(closeCurrentWindow) {
        closeCurrentWindow = (closeCurrentWindow == null) ? false : closeCurrentWindow;
        
        window.opener.Egw.Addressbook.reload();
        if(closeCurrentWindow == true) {
            window.setTimeout("window.close()", 400);
        }
    }*/
    
    /**
     * displays the addressbook select dialog
     * shared between contact and list edit dialog
     */
    var _displayAddressbookSelectDialog = function(_fieldName){         
                
        //if(!addressBookDialog) {
                   
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
            
            var treeLoader = new Ext.tree.TreeLoader({
                dataUrl:'index.php',
                baseParams: {
                    jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
                    location: 'mainTree'
                }
                
            });
            treeLoader.on("beforeload", function(_loader, _node) {
                switch(_node.attributes.nodeType) {
                    case 'otherAddressbooks':
                        _loader.baseParams.method   = 'Addressbook.getOtherUsers';
                        break;
                        
                    case 'sharedAddressbooks':
                        _loader.baseParams.method   = 'Addressbook.getSharedAddressbooks';
                        break;
    
                    case 'userAddressbooks':
                        _loader.baseParams.method   = 'Addressbook.getAddressbooksByOwner';
                        _loader.baseParams.owner    = _node.attributes.owner;
                        break;
                }
            }, this);
                            
            var tree = new Ext.tree.TreePanel({
                animate:true,
                id: 'addressbookTree',
                loader: treeLoader,
                containerScroll: true,
                rootVisible:false
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
                if(_node.attributes.nodeType == 'singleAddressbook') {                
                    Ext.getCmp(_fieldName).setValue(_node.attributes.addressbookId);
                    Ext.getCmp(_fieldName + '_name').setValue(_node.text);
                    addressBookDialog.hide();
                }
            });

            addressBookDialog.add(tree);
    
            addressBookDialog.show();
                           
            tree.expandPath('/root/allAddressbooks');
            tree.getNodeById('internalAddressbook').disable();
        //}
                
    };
    
    
    // public functions and variables
    return {
        // public functions
        displayAddressbookSelectDialog: _displayAddressbookSelectDialog,
        
        getPanel:           _getTreePanel,
        
        setTreeContextMenu: function (_contextMenu) {
            _treeContextMenu = _contextMenu;
        },
        
        reload:             function() {
            Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
        }
    };
    
}();


Egw.Addressbook.Shared = function(){
    /**
     * holds the current tree context menu
     * 
     * gets set by Egw.Addressbook.setTreeContextMenu()
     */
    var _treeContextMenu = null;

    /**
     * the initial tree to display in the left treePanel
     */
    var _initialTree = [{
        text: "All Contacts",
        cls: "treemain",
        allowDrag: false,
        allowDrop: true,
        id: "allcontacts",
        icon: "images\/oxygen\/16x16\/apps\/kaddressbook.png",
        application: "Addressbook",
        datatype: "allcontacts",
        children: [{
            text: "My Contacts",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "mycontacts",
            icon: false,
            application: "Addressbook",
            datatype: "contacts",
            children: [],
            leaf: null,
            contextMenuClass: "ctxMenuContactsTree",
            expanded: true,
            owner: "currentuser",
            jsonMethod: "Addressbook.getContactsByOwner",
            dataPanelType: "contacts"
        }, {
            text: "All Users",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "accounts",
            icon: false,
            application: "Addressbook",
            datatype: "accounts",
            children: [],
            leaf: null,
            contextMenuClass: null,
            expanded: true,
            owner: 0
        }, {
            text: "Other Users Contacts",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "otheraddressbooks",
            icon: false,
            application: "Addressbook",
            datatype: "otheraddressbooks",
            children: null,
            leaf: null,
            contextMenuClass: null,
            owner: "otheraddressbooks",
            jsonMethod: "Addressbook.getContactsByOwner",
            dataPanelType: "contacts"
        }, {
            text: "Shared Contacts",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "sharedaddressbooks",
            icon: false,
            application: "Addressbook",
            datatype: "sharedaddressbooks",
            children: null,
            leaf: null,
            contextMenuClass: null,
            owner: "sharedaddressbooks",
            jsonMethod: "Addressbook.getContactsByOwner",
            dataPanelType: "contacts"
        }],
        leaf: null,
        contextMenuClass: null,
        owner: "allcontacts",
        jsonMethod: "Addressbook.getContactsByOwner",
        dataPanelType: "contacts"
    }, {
        text: "All Lists",
        cls: "treemain",
        allowDrag: false,
        allowDrop: true,
        id: "alllists",
        icon: "images\/oxygen\/16x16\/apps\/kaddressbook.png",
        application: "Addressbook",
        datatype: "alllists",
        children: [{
            text: "My Lists",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "mylists",
            icon: false,
            application: "Addressbook",
            datatype: "lists",
            children: null,
            leaf: null,
            contextMenuClass: null,
            owner: "currentuser",
            jsonMethod: "Addressbook.getListsByOwner",
            dataPanelType: "lists"
        }, {
            text: "Other Users Lists",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "otherlists",
            icon: false,
            application: "Addressbook",
            datatype: "otherlists",
            children: null,
            leaf: null,
            contextMenuClass: null,
            owner: "otherlists",
            jsonMethod: "Addressbook.getListsByOwner",
            dataPanelType: "lists"
        }, {
            text: "Shared Lists",
            cls: "file",
            allowDrag: false,
            allowDrop: true,
            id: "sharedlists",
            icon: false,
            application: "Addressbook",
            datatype: "sharedlists",
            children: null,
            leaf: null,
            contextMenuClass: null,
            owner: "sharedlists",
            jsonMethod: "Addressbook.getListsByOwner",
            dataPanelType: "lists"
        }],
        leaf: null,
        contextMenuClass: null,
        owner: "alllists",
        jsonMethod: "Addressbook.getListsByOwner",
        dataPanelType: "lists"
    }];
    
    /**
     * creates the address grid
     *
     */
    var _getTreePanel = function() 
    {
		var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
                location: 'mainTree',
                method: 'Addressbook.getSubTree'
            }

        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
            _loader.baseParams.datatype = _node.attributes.datatype;
            _loader.baseParams.owner    = _node.attributes.owner;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
        	title: 'Contacts',
            id: 'Addressbook_Tree',
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

        for(var i=0; i< _initialTree.length; i++) {
        	treeRoot.appendChild(new Ext.tree.AsyncTreeNode(_initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
        	//action_edit.setDisabled(true);
			//action_delete.setDisabled(true);

        	switch(_node.attributes.dataPanelType) {
        		case 'contacts':
                    Egw.Addressbook.Contacts.show(_node);
        			
        			break;
        			
        		case 'lists':
                    Egw.Addressbook.Lists.show(_node);
        			
        			break;
        	}
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
        	if(_panel.getSelectionModel().getSelectedNode() === null) {
        		_panel.expandPath('/root/alllists');
        		_panel.expandPath('/root/allcontacts');
				_panel.selectPath('/root/allcontacts');
        	}
			_panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
        	_event.stopEvent();
        	//_node.select();
        	//_node.getOwnerTree().fireEvent('click', _node);
        	//console.log(_node.attributes.contextMenuClass);
            if (_treeContextMenu !== null) {
                _treeContextMenu.showAt(_event.getXY());
            }
        });

		return treePanel;
    };

    /**
     * reload main window
     *
     */
/*    var _reloadMainWindow = function(closeCurrentWindow) {
        closeCurrentWindow = (closeCurrentWindow == null) ? false : closeCurrentWindow;
        
        window.opener.Egw.Addressbook.reload();
        if(closeCurrentWindow == true) {
            window.setTimeout("window.close()", 400);
        }
    }*/
	
    /**
     * displays the addressbook select dialog
     * shared between contact and list edit dialog
     */
    var _displayAddressbookSelectDialog = function(_fieldName){         
                
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
			
            var treeLoader = new Ext.tree.TreeLoader({
            	dataUrl:'index.php',
	            baseParams: {
	                jsonKey: Egw.Egwbase.Registry.get('jsonKey'),
	                method: 'Addressbook.getSubTree',
	                location: 'selectFolder'
	            }
            });
            treeLoader.on("beforeload", function(_loader, _node) {
                _loader.baseParams.node        = _node.id;
                _loader.baseParams.datatype    = _node.attributes.datatype;
                _loader.baseParams.owner       = _node.attributes.owner;
            }, this);
                            
            var tree = new Ext.tree.TreePanel({
                animate:true,
				id: 'addressbookTree',
                loader: treeLoader,
                containerScroll: true,
                rootVisible:false
            });
            
            // set the root node
            var root = new Ext.tree.TreeNode({
                text: 'root',
                draggable:false,
                allowDrop:false,
                id:'root'
            });
            tree.setRootNode(root);             
            
            // add the initial tree nodes    
            Ext.each(formData.config.initialTree, function(_treeNode) {
                root.appendChild(new Tree.AsyncTreeNode(_treeNode));                    
            });
          
            tree.on('click', function(_node) {
                if(_node.attributes.datatype == 'contacts') {                
                    Ext.getCmp(_fieldName).setValue(_node.attributes.owner);
                	Ext.getCmp(_fieldName + '_name').setValue(_node.text);
                    addressBookDialog.hide();
                }
            });

			addressBookDialog.add(tree);
	
			addressBookDialog.show();   			
		}
                
    };
    
	
    // public functions and variables
    return {
        // public functions
        displayAddressbookSelectDialog: _displayAddressbookSelectDialog,
        
        getPanel:           _getTreePanel,
        
        setTreeContextMenu: function (_contextMenu) {
            _treeContextMenu = _contextMenu;
        },
        
        reload:             function() {
            Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
        }
    };
	
}(); // end of application

Egw.Addressbook.Contacts = function(){
    /**
     * onclick handler for addBtn
     */
    var _addBtnHandler = function(_button, _event) {
        Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
    };
    
    /**
     * onclick handler for addLstBtn
     */
    var _addLstBtnHandler = function(_button, _event) {
        Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=', 800, 450);
    };
    
    /**
     * onclick handler for deleteBtn
     */
    var _deleteBtnHandler = function(_button, _event) {
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
    };
    
    /**
     * onclick handler for editBtn
     */
    var _editBtnHandler = function(_button, _event) {
        var selectedRows = Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getSelections();
        var contactId = selectedRows[0].id;
        
        Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + contactId, 850, 600);
    };

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
        text: 'edit contact',
        disabled: true,
        handler: _editBtnHandler,
        iconCls: 'action_edit'
    });
        
    var action_delete = new Ext.Action({
        text: 'delete contact',
        disabled: true,
        handler: _deleteBtnHandler,
        iconCls: 'action_delete'
    });

    var _ctxMenuGrid = new Ext.menu.Menu({
        id:'ctxMenuAddress1', 
        items: [
            action_edit,
            action_delete,
            '-',
            action_addContact 
        ]
    });
    
    var _showToolbar = function()
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
        });
        
        var contactToolbar = new Ext.Toolbar({
            id: 'Addressbook_Contacts_Toolbar',
            split: false,
            height: 26,
            items: [
                action_addContact, 
                /*action_addList, */
                action_edit,
                action_delete,
                '-',
                'Sort by: ',
                ' ',
                {
                    text: 'Addressbooks',
                    enableToggle: true,
                    toggleGroup: 'orderBy',
                    pressed: true
                    
                }, {
                    text: 'Tags',
                    enableToggle: true,
                    toggleGroup: 'orderBy',
                    disabled: true
                },
                '->', 'Search:', ' ',
/*              new Ext.ux.SelectBox({
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

        Egw.Egwbase.MainScreen.setActiveToolbar(contactToolbar);
    };
    
    /**
     * the datastore for contacts
     */
    var _createDataStore = function() {
        var dataStore = new Ext.data.JsonStore({
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
        
        dataStore.setDefaultSort('n_family', 'asc');

        //ds_contacts.on('beforeload', _setParameter);
        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        return dataStore;
    };

    var _renderContactTid = function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
        switch(_data) {
            case 'l':
                    return "<img src='images/oxygen/16x16/actions/users.png' width='12' height='12' alt='list'/>";
            default:
                    return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
        }
    };
        
   
    /**
     * creates the address grid
     *
     */
    var _showGrid = function() 
    {
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying contacts {0} - {1} of {2}',
            emptyMsg: "No contacts to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
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
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                action_delete.setDisabled(true);
                action_edit.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                action_delete.setDisabled(false);
                action_edit.setDisabled(true);
            } else {
                // only one row selected
                action_delete.setDisabled(false);
                action_edit.setDisabled(false);
            }
        });
        
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
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            //var record = _grid.getStore().getAt(rowIndex);
            _ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            try {
                Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.data.contact_id, 850, 600);
            } catch(e) {
                // alert(e);
            }
        });
        
        return;
    };

    /**
     * update datastore with node values and load datastore
     */
    var _loadData = function(_node)
    {
        var dataStore = Ext.getCmp('Addressbook_Contacts_Grid').getStore();
        
        //console.log(_node.attributes.nodeType);
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.nodeType) {
            case 'internalAddressbook':
                dataStore.baseParams.method = 'Addressbook.getAccounts';
                break;

            case 'sharedAddressbooks':
                dataStore.baseParams.method = 'Addressbook.getSharedContacts';
                break;

            case 'otherAddressbooks':
                dataStore.baseParams.method = 'Addressbook.getOtherPeopleContacts';
                break;

            case 'allAddressbooks':
                dataStore.baseParams.method = 'Addressbook.getAllContacts';
                break;


            case 'userAddressbooks':
                dataStore.baseParams.method = 'Addressbook.getContactsByOwner';
                dataStore.baseParams.owner  = _node.attributes.owner;
                break;

            case 'singleAddressbook':
                dataStore.baseParams.method        = 'Addressbook.getContactsByAddressbookId';
                dataStore.baseParams.addressbookId = _node.attributes.addressbookId;
                break;
        }
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    };

    return {
        show: function(_node) {
            var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();

            if(currentToolbar === false || currentToolbar.id != 'Addressbook_Contacts_Toolbar') {
                _showToolbar();
                _showGrid(_node);
            }
            _loadData(_node);
        }
    };
}();

Egw.Addressbook.Lists = function(){
    /**
     * onclick handler for addBtn
     */
    var _addBtnHandler = function(_button, _event) {
        Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=', 850, 600);
    };
    
    /**
     * onclick handler for addLstBtn
     */
    var _addLstBtnHandler = function(_button, _event) {
        Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=', 800, 450);
    };
    
    /**
     * onclick handler for deleteBtn
     */
    var _deleteBtnHandler = function(_button, _event) {
        var selectedNode = Ext.getCmp('contacts-tree').getSelectionModel().getSelectedNode();
        
        if(selectedNode.attributes.dataPanelType == 'lists') {
            var listIds = new Array();
            var selectedRows = contactGrid.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                listIds.push(selectedRows[i].id);
            }
            
            listIds = Ext.util.JSON.encode(listIds);
            
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Addressbook.deleteLists', 
                    listIds: listIds
                },
                text: 'Deleting list...',
                success: function(_result, _request) {
                    ds_contacts.reload();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the list.'); 
                } 
            });
        } else {
            var contactIds = new Array();
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
    };
    
    /**
     * onclick handler for editBtn
     */
    var _editBtnHandler = function(_button, _event) {
        var selectedNode = Ext.getCmp('contacts-tree').getSelectionModel().getSelectedNode();
        var selectedRows = contactGrid.getSelectionModel().getSelections();
        var contactId = selectedRows[0].id;
        
        if(selectedNode.attributes.dataPanelType == 'lists') {
            Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + contactId, 800, 450);
        }
        else {
            Egw.Egwbase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + contactId, 850, 600);
        }
    };

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
    
    var _ctxMenuGrid = new Ext.menu.Menu({
        id:'ctxMenuAddress2', 
        items: [
/*            action_edit,
            action_delete,
            '-',
            action_addList */
        ]
    });
    
    /**
     * create the datastore to fetch list informations
     */
    var _createDataStore = function() {
        var dataStore = new Ext.data.JsonStore({
            url: 'index.php',
            //baseParams: getParameterListsDataStore(_node),
            root: 'results',
            totalProperty: 'totalcount',
            id: 'list_id',
            fields: [
                {name: 'list_id'},
                {name: 'list_name'},
                {name: 'list_owner'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('list_name', 'asc');

        //dataStore.on('beforeload', _setParameter);        
        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        return dataStore;
    };
    
    /**
     * creates the address grid
     *
     */
    var _showGrid = function() 
    {
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying lists {0} - {1} of {2}',
            emptyMsg: "No lists to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: true, header: 'List name', id: 'list_name', dataIndex: 'list_name'}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                _action_delete.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                _action_delete.setDisabled(false);
            } else {
                // only one row selected
                _action_delete.setDisabled(false);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Addressbook_Lists_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            /*loadMask: true,*/
            autoExpandColumn: 'list_name',
            border: false
        });
        
        Egw.Egwbase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowclick', function(gridP, rowIndexP, eventP) {
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
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);

                action_edit.setDisabled(false);
                action_delete.setDisabled(false);
            }
            //var record = _grid.getStore().getAt(rowIndex);
            ctxMenuListGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.contact_id);
            try {
                Egw.Egwbase.Common.openWindow('listWindow', 'index.php?method=Addressbook.editList&_listId=' + record.data.list_id, 800, 450);
            } catch(e) {
            //  alert(e);
            }
        });
        
        return;
    };    

    /**
     * update datastore with node values and load datastore
     */
    var _loadData = function(_node)
    {
        var dataStore = Ext.getCmp('Addressbook_Lists_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        dataStore.baseParams.dataType = _node.attributes.datatype;
        dataStore.baseParams.owner    = _node.attributes.owner;
        dataStore.baseParams.method   = _node.attributes.jsonMethod;
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    };

    var _showToolbar = function()
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
        });
        
        var contactToolbar = new Ext.Toolbar({
            id: 'Addressbook_Lists_Toolbar',
            split: false,
            height: 26,
            items: [
                action_addContact, 
                action_addList,
                action_edit,
                action_delete,
                '->', 'Search:', ' ',
/*              new Ext.ux.SelectBox({
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

        Egw.Egwbase.MainScreen.setActiveToolbar(contactToolbar);
    };

    return {
        show: function(_node) {
            var currentToolbar = Egw.Egwbase.MainScreen.getActiveToolbar();

            if(currentToolbar === false || currentToolbar.id != 'Addressbook_Lists_Toolbar') {
                _showToolbar();
                _showGrid(_node);
            }
            _loadData(_node);
        }
    };
}();

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
			var additionalData = {
				jsonKey: Egw.Egwbase.Registry.get('jsonKey')
		    };
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
    };

    var handler_saveAndClose = function(_button, _event) 
    {
    	var contactForm = Ext.getCmp('contactDialog').getForm();
		contactForm.render();
    	
    	if(contactForm.isValid()) {
            var additionalData = {
                jsonKey: Egw.Egwbase.Registry.get('jsonKey')
            };
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
    };

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
        			    		
    };


   	var action_saveAndClose = new Ext.Action({
		text: 'save and close',
		handler: handler_saveAndClose,
		iconCls: 'action_saveAndClose',
		disabled: true
	});

   	var action_applyChanges = new Ext.Action({
		text: 'apply changes',
		handler: handler_applyChanges,
		iconCls: 'action_applyChanges',
		disabled: true
	});

   	var action_deleteContact = new Ext.Action({
		text: 'delete contact',
		handler: handler_deleteContact,
		iconCls: 'action_delete',
		disabled: true
	});

    /**
     * display the contact edit dialog
     *
     */
    var _displayDialog = function() {
        Ext.QuickTips.init();

        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';

        if(formData.config.addressbookRights & 4) {
        	action_saveAndClose.setDisabled(false);
        	action_applyChanges.setDisabled(false);
        }

        if(formData.config.addressbookRights & 8) {
            action_deleteContact.setDisabled(false);
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
			id: 'contact_owner_name',
            anchor:'95%',
            allowBlank: false,
            readOnly:true
        });

        addressbookTrigger.onTriggerClick = function() {
            Egw.Addressbook.displayAddressbookSelectDialog('contact_owner');
        };
		
		var _setParameter = function(_dataSource)
		{
                _dataSource.baseParams.method = 'Addressbook.getContacts';
                _dataSource.baseParams.options = Ext.encode({
                    displayContacts: false,
                    displayLists:    true
                });
        };
      
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
            var _litems;
            
            if (_index.getSelectionCount() > '1') {
                _litems = _listitems.split(",");
            } else if (_index.getSelectionCount() == '1') {
                _litems = new Array(_listitems);
            }
            
            _litems.sort(Numsort);
            _litems.reverse();
            
            for (var i = 0; i < _selected_items; i++) {
                var record = lists_store.getAt(_litems[i]);
                lists_store2.add(record);
            }
            lists_store2.sort('contact_tid', 'ASC');
            
            
            for (var i = 0; i < _selected_items; i++) {
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
				
				for(var i=0; i<_selected_items; i++)
				{	
					var record = lists_store2.getAt(_litems[i]);
					lists_store.add(record);	
				}
					lists_store.sort('contact_tid', 'ASC');
					
				for(var i=0; i<_selected_items; i++)
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
						anchor:'95% 100%'
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
						border:false /*,
						items: [
							new Ext.form.FieldSet({
								id:'photo', 
								legend:'Photo'
						})]*/		
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
	                title: 'Categories',
	                disabled: true,
	                layout: 'column',
					deferredRender:false,
					border:false,
	                items: [{
					}]
	            }]
	        },{
				xtype: 'hidden',
				name: 'contact_owner',
				id: 'contact_owner'
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
        
/*        var ds_addressbooks = new Ext.data.SimpleStore({
            fields: ['id', 'addressbooks'],
            data: formData.config.addressbooks
        }); 
*/
    };

    var setContactDialogValues = function(_formData) {
    	var form = Ext.getCmp('contactDialog').getForm();
    	
    	form.setValues(_formData);
    	
    	form.findField('contact_owner_name').setRawValue(formData.config.addressbookName);
    	
    	if(formData.config.oneCountryName) {
    		//console.log('set adr_one_countryname to ' + formData.config.oneCountryName);
	    	form.findField('adr_one_countryname').setRawValue(formData.config.oneCountryName);
    	}

    	if(formData.config.twoCountryName) {
    		//console.log('set adr_two_countryname to ' + formData.config.twoCountryName);
	    	form.findField('adr_two_countryname').setRawValue(formData.config.twoCountryName);
    	}
    };

    var _exportContact = function(_btn, _event) {
        Ext.MessageBox.alert('Export', 'Not yet implemented.');
    };
    
    // public functions and variables
    return {
        display: function() {
            var dialog = _displayDialog();
            if(formData.values) {
                setContactDialogValues(formData.values);
            }
        }       
    };
    
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
		var ds_listMembers = Ext.getCmp('listGrid').getStore();
		var listMembers = new Array();
		ds_listMembers.each(function(_record) {
			listMembers.push(_record.data);
		});
		
    	if(contactForm.isValid()) {
			var additionalData = {
				listMembers: Ext.util.JSON.encode(listMembers)
			};

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
    };

    var handler_saveAndClose = function(_button, _event) 
    {
    	var contactForm = Ext.getCmp('listDialog').getForm();
		var ds_listMembers = Ext.getCmp('listGrid').getStore();
		var listMembers = new Array();
		ds_listMembers.each(function(_record) {
			listMembers.push(_record.data);
		});
		
    	if(contactForm.isValid()) {
			var additionalData = {
				listMembers: Ext.util.JSON.encode(listMembers)
			};
			    		
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
    };

    var handler_deleteList = function(_button, _event) 
    {
		var listIds = Ext.util.JSON.encode([formData.values.list_id]);
			
		Ext.Ajax.request({
			url: 'index.php',
			params: {
				method: 'Addressbook.deleteLists', 
				listIds: listIds
			},
			text: 'Deleting list...',
			success: function(_result, _request) {
  				window.opener.Egw.Addressbook.reload();
   				window.setTimeout("window.close()", 400);
			},
			failure: function ( result, request) { 
				Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.'); 
			} 
		});
        			    		
    };

    var handler_removeListMember = function(_button, _event)
    {
		var listGrid = Ext.getCmp('listGrid');
		var listStore = listGrid.getStore();
        
		var selectedRows = listGrid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            listStore.remove(selectedRows[i]);
        }    	
        
        action_removeListMember.setDisabled(true);
    };
	
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

   	var action_deleteList = new Ext.Action({
		text: 'delete list',
		handler: handler_deleteList,
		iconCls: 'action_delete'
	});	

	var action_removeListMember = new Ext.Action({
		text: 'remove list member',
		disabled: true,
		handler: handler_removeListMember,
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
				action_deleteList,
				'-',
				action_removeListMember
			]
		});

		var addressbookTrigger = new Ext.form.TriggerField({
            fieldLabel:'Addressbook', 
			id: 'list_owner_name',
            anchor:'100%',
            allowBlank: false,
            readOnly:true
        });
        
        addressbookTrigger.onTriggerClick = function() {
            Egw.Addressbook.displayAddressbookSelectDialog("list_owner");
        };		
		
		var searchDS = new Ext.data.JsonStore({
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
			fieldLabel: 'Add new list members',
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

        list_search.on('change', function(_this, _newValue, _oldValue){
        	console.log(_newValue + ' ' + _oldValue);
        });

        list_search.on('specialkey', function(_this, _e){
			if(searchDS.getCount() == 0) {
				var regExp  = /^[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4}|museum)$/;
				
				var aussage = regExp.exec(list_search.getValue());
				
				if(aussage && (_e.getKey() == _e.ENTER || _e.getKey() == _e.RETURN ) ) {
                    var contactEmail = list_search.getValue();
                    var position = contactEmail.indexOf('@');
                    if(position != -1) {
                        var familyName = Ext.util.Format.capitalize(contactEmail.substr(0, position));
                    } else {
                        var familyName = contactEmail;
                    }
					var record = new listMemberRecord({
						contact_id:       null,
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
			deferredRender:false,
			id: 'listDialog',
			width: 300,
			items: [{
				layout: 'form',
				border:false,
				anchor:'100%',
				items:[ addressbookTrigger
					,{
						xtype:'textfield',
						fieldLabel:'List Name', 
						id:'list_name',
						anchor:'100%'
					}, {
						xtype:'textarea',
						fieldLabel:'List Description', 
						id:'list_description',
						height: 100,
						grow: false,
						anchor:'100%'
					}, list_search, {
						xtype: 'hidden',
						id: 'list_owner'
					}, {
						xtype: 'hidden',
						id: 'list_id'
					}
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
			var listmembers = new Array();
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
            items: [action_removeListMember]
        });

        var listGrid = new Ext.grid.GridPanel({
            store: ds_listMembers,
            cm: cm_listMembers,
			id: 'listGrid',
			layout: 'fit',
            selModel: new Ext.grid.RowSelectionModel({multiSelect:true}),
            autoSizeColumns: true,
            /*monitorWindowResize: false,*/
            trackMouseOver: true,
			/*autoWidth: true,*/
			/*height: 300,*/
            contextMenu: 'ctxListMenu',   
            autoExpandColumn: 'contact_email'
        }); 
		
		listGrid.on('rowclick', function(_gridPanel, _rowIndex, _eventObject) {
			var rowCount = _gridPanel.getSelectionModel().getCount();
            
			if(rowCount < 1) {
				action_removeListMember.setDisabled(true);
			} else {
				action_removeListMember.setDisabled(false);
			}
		});
    	
		
        listGrid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
        	_eventObject.stopEvent();
			if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
				_grid.getSelectionModel().selectRow(_rowIndex);

				action_removeListMember.setDisabled(false);
			}
            ctxListMenu.showAt(_eventObject.getXY());
        });
        
		
		var viewport = new Ext.Viewport({
			layout: 'border',
			items: [{
				region: 'north',
				id: 'north-panel',
				split: false,
				border:false,
				tbar: contactToolbar
			},{
				region: 'west',
				id: 'west-panel',
				useShim:true,
				layout: 'fit',
				width: 300,
				split: false,
				border:false,
				items: [listedit]
			},{
				region: 'center',
				id: 'center-panel',
				useShim:true,
				layout: 'fit',
				split: false,
				border:false,
				items: [listGrid]
			}]
		});   
		
/*		Ext.EventManager.on(window, 'beforeunload', function() {
			Ext.Msg.confirm('Name', 'Please enter your name:', function(_btn){
				console.log(_btn);
    			if (btn == 'ok'){
        			// process text value and close...
    			}
			});
        	return false;
    	});*/  
    };
    
    var _onAddressSelect = function(_addressbooName, _addressbookId) {
        listedit.setValues([{id:'list_owner', value:_addressbookId}]);
    };
    
    ////////////////////////////////////////////////////////////////////////////
    // set the dialog field to their initial value
    ////////////////////////////////////////////////////////////////////////////

    var _setDialogValues = function(_formData) {
		var form = Ext.getCmp('listDialog').getForm();

		form.setValues(_formData);
		
		form.findField('list_owner_name').setRawValue(formData.config.addressbookName);
    };
    
    var _encodeDataSourceEntries = function(_dataSource) {
        var jsonData = new Array();
        
        _dataSource.each(function(_record){
            jsonData.push(_record.data);
        }, this);
        
        return Ext.util.JSON.encode(jsonData);
    };
    
    // public functions and variables
     return {
        display: function() {
			var dialog = _displayDialog();
		//	var dialog = Ext.getCmp('listDialog');
		
            if(formData.values) {
                _setDialogValues(formData.values);
            }
        }
    };
}(); // end of Egw.Addressbook.ListEditDialog