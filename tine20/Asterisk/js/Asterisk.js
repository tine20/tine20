/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */
 
Ext.namespace('Tine.Asterisk');

Tine.Asterisk = function() {
	
	/**
	 * builds the asterisk applications tree
	 */
    var _initialTree = [{
        text: 'Phones',
        cls: 'treemain',
        allowDrag: false,
        allowDrop: true,
        id: 'phones',
        icon: false,
        children: [],
        leaf: null,
        expanded: true,
        dataPanelType: 'phones',
        viewRight: 'phones'
    },{
        text: "Config",
		cls: "treemain",
		allowDrag: false,
		allowDrop: true,
		id: "config",
		icon: false,
		children: [],
		leaf: null,
		expanded: true,
		dataPanelType: "config",
		viewRight: 'config'
	},{
		text :"Lines",
		cls :"treemain",
		allowDrag :false,
		allowDrop :true,
		id :"lines",
		icon :false,
		children :[],
		leaf :null,
		expanded :true,
		dataPanelType :"lines",
		viewRight: 'lines'
	},{
		text :"Settings",
		cls :"treemain",
		allowDrag :false,
		allowDrop :true,
		id :"settings",
		icon :false,
		children :[],
		leaf :null,
		expanded :true,
		dataPanelType :"settings",
		viewRight:'settings'
	},{
		text :"Software",
		cls :"treemain",
		allowDrag :false,
		allowDrop :true,
		id :"software",
		icon :false,
		children :[],
		leaf :null,
		expanded :true,
		dataPanelType :"software",
		viewRight: 'software'
	}];

	/**
     * creates the asterisk menu tree
     *
     */
    var _getAsteriskTree = function() 
    {
        var translation = new Locale.Gettext();
        translation.textdomain('Asterisk');        
        
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.Registry.get('jsonKey'),
                method: 'Asterisk.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Asterisk',
            id: 'asterisk-tree',
            iconCls: 'AsteriskIconCls',
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

        for(var i=0; i<_initialTree.length; i++) {
        	
        	var node = new Ext.tree.AsyncTreeNode(_initialTree[i]);
    	
        	// check view right
        	if ( _initialTree[i].viewRight && !Tine.Tinebase.hasRight('view', _initialTree[i].viewRight) ) {
                node.disabled = true;
        	}
        	
            treeRoot.appendChild(node);
        }

        
        treePanel.on('click', function(_node, _event) {
        	if ( _node.disabled ) {
        		return false;
        	}
        	
        	var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        	switch(_node.attributes.dataPanelType) {
                case 'phones':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAsteriskPhones') {
                        Ext.getCmp('gridAsteriskPhones').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Asterisk.Phones.Main.show(_node);
                    }
                    break;                    
                    
                case 'config':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAsteriskConfig') {
                        Ext.getCmp('gridAsteriskConfig').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Asterisk.Config.Main.show(_node);
                    }
                    break;                                        
                    
                case 'software':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAsteriskSoftware') {
                        Ext.getCmp('gridAsteriskSoftware').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Asterisk.Software.Main.show(_node);
                    }
                    break;                      
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/phones');
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
    };
    
    // public functions and variables
    return {
        getPanel: _getAsteriskTree
    };
    
}();


Ext.namespace('Tine.Asterisk.Phones');

Tine.Asterisk.Phones.Main = {
       
	actions: {
	    addPhone: null,
	    editPhone: null,
	    deletePhone: null
	},
	
	handlers: {
	    /**
	     * onclick handler for addPhone
	     */
	    addPhone: function(_button, _event) 
	    {
	        Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Asterisk.editPhone&phoneId=', 450, 300);
	    },

        /**
         * onclick handler for editPhone
         */
        editPhone: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Asterisk_Phones_Grid').getSelectionModel().getSelections();
            var phoneId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Asterisk.editPhone&phoneId=' + phoneId, 450, 300);
        },
        
	    /**
	     * onclick handler for deletePhone
	     */
	    deletePhone: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected phones?', function(_button){
	            if (_button == 'yes') {
	            
	                var phoneIds = [];
	                var selectedRows = Ext.getCmp('Asterisk_Phones_Grid').getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    phoneIds.push(selectedRows[i].id);
	                }
	                
	                phoneIds = Ext.util.JSON.encode(phoneIds);
	                
	                Ext.Ajax.request({
	                    url: 'index.php',
	                    params: {
	                        method: 'Asterisk.deletePhones',
	                        _phoneIds: phoneIds
	                    },
	                    text: 'Deleting phone(s)...',
	                    success: function(_result, _request){
	                        Ext.getCmp('Asterisk_Phones_Grid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.');
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
        this.translation.textdomain('Asterisk');
    
        this.actions.addPhone = new Ext.Action({
            text: this.translation._('add phone'),
            handler: this.handlers.addPhone,
            iconCls: 'action_addPhone',
            scope: this
        });
        
        this.actions.editPhone = new Ext.Action({
            text: this.translation._('edit phone'),
            disabled: true,
            handler: this.handlers.editPhone,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deletePhone = new Ext.Action({
            text: this.translation._('delete phone'),
            disabled: true,
            handler: this.handlers.deletePhone,
            iconCls: 'action_delete',
            scope: this
        });
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
        //if(Tine.Asterisk.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('AsteriskTreePanel');
        preferencesButton.setDisabled(true);
    },
	
    displayPhonesToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Asterisk_Phones_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
        
        var tagFilter = new Tine.widgets.tags.TagCombo({
            app: 'Asterisk',
            blurOnSelect: true
        });
        tagFilter.on('change', onFilterChange, this);
        
        var phoneToolbar = new Ext.Toolbar({
            id: 'Asterisk_Phones_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addPhone, 
                this.actions.editPhone,
                this.actions.deletePhone,
                '->',
                this.translation._('Filter: '), tagFilter,
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(phoneToolbar);
    },

    displayPhonesGrid: function() 
    {
    	// the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Asterisk.Phones.Phone,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
            _dataStore.baseParams.tagFilter = Ext.getCmp('TagCombo').getValue();
        }, this);   
        
        Ext.StoreMgr.add('PhonesStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying phones {0} - {1} of {2}'),
            emptyMsg: this.translation._("No phones to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('Id'), dataIndex: 'id', width: 30, hidden: true },
            { resizable: true, id: 'macaddress', header: this.translation._('MAC address'), dataIndex: 'macaddress',width: 60 },
            { resizable: true, id: 'model', header: this.translation._('phone model'), dataIndex: 'model', width: 100, hidden: true },
            { resizable: true, id: 'swversion', header: this.translation._('phone sw version'), dataIndex: 'swversion', width: 80, hidden: true },
            { resizable: true, id: 'ipaddress', header: this.translation._('phone IP address'), dataIndex: 'ipaddress', width: 110 },
            { resizable: true, id: 'last_modified_time', header: this.translation._('last modified'), dataIndex: 'last_modified_time', width: 100, hidden: true },
            { resizable: true, id: 'software_id', header: this.translation._('class id'), dataIndex: 'software_id', width: 20, hidden: true },
            {
                resizable: true,
                id: 'description',
                header: this.translation._('description'),
                dataIndex: 'description',
                width: 250
            }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deletePhone.setDisabled(true);
                this.actions.editPhone.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deletePhone.setDisabled(false);
                this.actions.editPhone.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Asterisk_Phones_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No phones to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
		        id:'ctxMenuPhones', 
		        items: [
		            this.actions.editPhone,
		            this.actions.deletePhone,
		            '-',
		            this.actions.addPhone 
		        ]
		    });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('phonesWindow', 'index.php?method=Asterisk.editPhone&phoneId=' + record.data.id, 450, 300);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Asterisk_Phones_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deletePhone();
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
        var dataStore = Ext.getCmp('Asterisk_Phones_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.dataPanelType) {
            case 'phones':
                dataStore.baseParams.method = 'Asterisk.getPhones';
                break;
                
            case 'config':
                dataStore.baseParams.method = 'Asterisk.getConfig';
                break;                
                
            case 'lines':
                dataStore.baseParams.method = 'Asterisk.getLines';
                break;                
                
            case 'settings':
                dataStore.baseParams.method = 'Asterisk.getSettings';
                break;                
                
            case 'software':
                dataStore.baseParams.method = 'Asterisk.getSoftware';
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
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Asterisk_Phones_Toolbar') {
            this.initComponent();
            this.displayPhonesToolbar();
            this.displayPhonesGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Asterisk_Phones_Grid')) {
            setTimeout ("Ext.getCmp('Asterisk_Phones_Grid').getStore().reload()", 200);
        }
    }
};

Tine.Asterisk.Phones.Data = {
    
    
    loadConfigData: function() {

        var configDataStore = new Ext.data.JsonStore({
        	baseParams: {
                method: 'Asterisk.getConfig',
                sort: 'description',
                dir: 'ASC',
                query: ''
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'description'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        configDataStore.setDefaultSort('description', 'asc');
               
        return configDataStore;
    },
    
    
    loadSoftwareData: function() {

        var softwareDataStore = new Ext.data.JsonStore({
        	baseParams: {
                method: 'Asterisk.getSoftware',
                sort: 'description',
                dir: 'ASC',
                query: ''
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: [
                {name: 'id'},
                {name: 'model'},
                {name: 'description'}
            ],
            
            // turn on remote sorting
            remoteSort: true
        });

        softwareDataStore.setDefaultSort('description', 'asc');

        Ext.StoreMgr.add('swData', softwareDataStore);               
         
        return softwareDataStore;
    }    
};
    

Tine.Asterisk.Phones.EditDialog =  {

    	phoneRecord: null,
        
        
    	updatePhoneRecord: function(_phoneData)
    	{
            if(_phoneData.last_modified_time && _phoneData.last_modified_time !== null) {
                _phoneData.last_modified_time = Date.parseDate(_phoneData.last_modified_time, 'c');
            }
            this.phoneRecord = new Tine.Asterisk.Phones.Phone(_phoneData);
    	},
    	
        
    	deletePhone: function(_button, _event)
    	{
	        var phoneIds = Ext.util.JSON.encode([this.phoneRecord.get('id')]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Asterisk.deletePhones', 
	                phoneIds: phoneIds
	            },
	            text: 'Deleting phone...',
	            success: function(_result, _request) {
	                window.opener.Tine.Asterisk.Phones.Main.reload();
	                window.close();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the phone.'); 
	            } 
	        });    		
    	},
    	
        applyChanges: function(_button, _event, _closeWindow) 
        {
        	var form = Ext.getCmp('asterisk_editPhoneForm').getForm();

        	if(form.isValid()) {
        		form.updateRecord(this.phoneRecord);
	    
	            Ext.Ajax.request({
	                params: {
	                    method: 'Asterisk.savePhone', 
	                    phoneData: Ext.util.JSON.encode(this.phoneRecord.data)
	                },
	                success: function(_result, _request) {
	                	if(window.opener.Tine.Asterisk.Phones) {
                            window.opener.Tine.Asterisk.Phones.Main.reload();
	                	}
                        if(_closeWindow === true) {
                            window.close();
                        } else {
		                	this.updatePhoneRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
		                	this.updateToolbarButtons();
		                	form.loadRecord(this.phoneRecord);
                        }
	                },
	                failure: function ( result, request) { 
	                    Ext.MessageBox.alert('Failed', 'Could not save phone.'); 
	                },
	                scope: this 
	            });
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
    	},

        saveChanges: function(_button, _event) 
        {
        	this.applyChanges(_button, _event, true);
        },
                  
                  
        editPhoneDialog:  [{
            layout:'form',
            //frame: true,
            border:false,
            width: 440,
            height: 280,
            items: [{
                    labelSeparator: '',
                    xtype:'textarea',
                    name: 'description',
                    fieldLabel: 'Description',
                    grow: false,
                    preventScrollbars:false,
                    anchor:'100%',
                    height: 60
                } , {
                    layout:'column',
                    border:false,
                    items: [{
                        columnWidth: .5,
                        layout: 'form',
                        border: false,
                        items:[{
                            xtype: 'textfield',
                            fieldLabel: 'MAC Address',
                            name: 'macaddress',
                            maxLength: 12,
                            anchor:'98%',
                            allowBlank: false
                        }, 
                            new Ext.form.ComboBox({
                                fieldLabel: 'Model',
                                id: 'modelCombo',
                                name: 'model',
                                mode: 'local',
                                displayField:'model',
                                valueField:'key',
                                anchor:'98%',                    
                                triggerAction: 'all',
                                allowBlank: false,
                                editable: false,
                                store: new Ext.data.SimpleStore(
                                    {
                                        fields: ['key','model'],
                                        data: [
                                            ['snom300','Snom 300'],
                                            ['snom320','Snom 320'],
                                            ['snom360','Snom 360'],
                                            ['snom370','Snom 370']                                        
                                        ]
                                    }
                                )
                            }) ,
                        {
                            xtype: 'combo',
                            fieldLabel: 'Config',
                            name: 'config',
                            mode: 'remote',
                            displayField:'description',
                            valueField:'id',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            editable: false,
                            forceSelection: true,
                            store: Tine.Asterisk.Phones.Data.loadConfigData()
                        } ]
                    } , {
                    columnWidth: .5,
                    layout: 'form',
                    border: false,
                    items:[{
                        xtype: 'textfield',
                        fieldLabel: 'current Software Version',
                        name: 'swversion',
                        maxLength: 40,
                        anchor:'100%',                    
                        readOnly: true
                    }, 
                        new Ext.form.ComboBox({
                            fieldLabel: 'load new SW Version',
                            name: 'newswversion',
                            id: 'newSWCombo',
                            mode: 'remote',
                            displayField:'description',
                            valueField:'id',
                            anchor:'100%',                    
                            triggerAction: 'all',
                            editable: false,
                            forceSelection: true
                        }) , 
                    {
                        xtype: 'textfield',
                        fieldLabel: 'current IP Address',
                        name: 'ipaddress',
                        maxLength: 20,
                        anchor:'100%',  
                        readOnly: true
                    }]
                }]
            }]
        }],
        

        
        updateToolbarButtons: function()
        {
            if(this.phoneRecord.get('id') > 0) {
                Ext.getCmp('asterisk_editPhoneForm').action_delete.enable();
            }
        },
        
        display: function(_phoneData) 
        {       
            // Ext.FormPanel
		    var dialog = new Tine.widgets.dialog.EditRecord({
		        id : 'asterisk_editPhoneForm',
		        //title: 'the title',
		        labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deletePhone,
		        items: this.editPhoneDialog
		    });

            Ext.getCmp('newSWCombo').disable();
            Ext.getCmp('newSWCombo').on('focus', function(_field) {
                var _newValue = Ext.getCmp('modelCombo').getValue();
                if(!Ext.StoreMgr.get('swData')) {
                     Tine.Asterisk.Phones.Data.loadSoftwareData();
                }
                Ext.StoreMgr.get('swData').filter('model',_newValue);
           }); 
    
            Ext.getCmp('modelCombo').on('change', function(_box, _newValue, _oldValue) {
               if(_newValue) {

                   Ext.getCmp('newSWCombo').enable();
               }  
           }); 

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
	        
	        //if (!arguments[0]) var task = {};
                    
            this.updatePhoneRecord(_phoneData);
            this.updateToolbarButtons();           
	        dialog.getForm().loadRecord(this.phoneRecord);
           
        }
   
};



Ext.namespace('Tine.Asterisk.Config');

Tine.Asterisk.Config.Main = {
       
	actions: {
	    addConfig: null,
	    editConfig: null,
	    deleteConfig: null
	},
	
	handlers: {
	    /**
	     * onclick handler for addConfig
	     */
	    addConfig: function(_button, _event) 
	    {
	        Tine.Tinebase.Common.openWindow('configWindow', 'index.php?method=Asterisk.editConfig&configId=', 500, 450);
	    },

        /**
         * onclick handler for editConfig
         */
        editConfig: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Asterisk_Config_Grid').getSelectionModel().getSelections();
            var configId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('configWindow', 'index.php?method=Asterisk.editConfig&configId=' + configId, 500, 450);
        },
        
	    /**
	     * onclick handler for deleteConfig
	     */
	    deleteConfig: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected config?', function(_button){
	            if (_button == 'yes') {
	            
	                var configIds = [];
	                var selectedRows = Ext.getCmp('Asterisk_Config_Grid').getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    configIds.push(selectedRows[i].id);
	                }
	                
	                configIds = Ext.util.JSON.encode(configIds);
	                
	                Ext.Ajax.request({
	                    url: 'index.php',
	                    params: {
	                        method: 'Asterisk.deleteConfig',
	                        _configIds: configIds
	                    },
	                    text: 'Deleting config...',
	                    success: function(_result, _request){
	                        Ext.getCmp('Asterisk_Config_Grid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the config.');
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
        this.translation.textdomain('Asterisk');
    
        this.actions.addConfig = new Ext.Action({
            text: this.translation._('add config'),
            handler: this.handlers.addConfig,
            iconCls: 'action_addConfig',
            scope: this
        });
        
        this.actions.editConfig = new Ext.Action({
            text: this.translation._('edit config'),
            disabled: true,
            handler: this.handlers.editConfig,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteConfig = new Ext.Action({
            text: this.translation._('delete config'),
            disabled: true,
            handler: this.handlers.deleteConfig,
            iconCls: 'action_delete',
            scope: this
        });
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Asterisk.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('AsteriskTreePanel');
        preferencesButton.setDisabled(true);
    },
	
    displayConfigToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Asterisk_Config_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
        
        var tagFilter = new Tine.widgets.tags.TagCombo({
            app: 'Asterisk',
            blurOnSelect: true
        });
        tagFilter.on('change', onFilterChange, this);
        
        var configToolbar = new Ext.Toolbar({
            id: 'Asterisk_Config_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addConfig, 
                this.actions.editConfig,
                this.actions.deleteConfig,
                '->',
                this.translation._('Filter: '), tagFilter,
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(configToolbar);
    },

    displayConfigGrid: function() 
    {
    	// the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Asterisk.Config.Config,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
            _dataStore.baseParams.tagFilter = Ext.getCmp('TagCombo').getValue();
        }, this);   
        
        //Ext.StoreMgr.add('ConfigStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying config {0} - {1} of {2}'),
            emptyMsg: this.translation._("No config to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'firmware_interval', header: this.translation._('FW Interval'), dataIndex: 'firmware_interval', width: 10, hidden: true },
            { resizable: true, id: 'firmware_status', header: this.translation._('FW Status'), dataIndex: 'firmware_status', width: 100 },
            { resizable: true, id: 'update_policy', header: this.translation._('Update Policy'), dataIndex: 'update_policy', width: 30, hidden: true },
            { resizable: true, id: 'setting_server', header: this.translation._('Server Setting'), dataIndex: 'setting_server', width: 100 },
            { resizable: true, id: 'admin_mode', header: this.translation._('Admin Mode'), dataIndex: 'admin_mode', width: 10, hidden: true },
            { resizable: true, id: 'ntp_server', header: this.translation._('NTP Server'), dataIndex: 'ntp_server', width: 50 },
            { resizable: true, id: 'webserver_type', header: this.translation._('Webserver Type'), dataIndex: 'webserver_type', width: 30, hidden: true },
            { resizable: true, id: 'https_port', header: this.translation._('HTTPS Port'), dataIndex: 'https_port', width: 10 },
            { resizable: true, id: 'http_user', header: this.translation._('HTTP User'), dataIndex: 'http_user', width: 15, hidden: true },
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 10, hidden: true },
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 70 },
            { resizable: true, id: 'filter_registrar', header: this.translation._('Filter Registrar'), dataIndex: 'filter_registrar', width: 10, hidden: true },
            { resizable: true, id: 'callpickup_dialoginfo', header: this.translation._('CP Dialoginfo'), dataIndex: 'callpickup_dialoginfo', width: 10, hidden: true },
            { resizable: true, id: 'pickup_indication', header: this.translation._('Pickup Indic.'), dataIndex: 'pickup_indication', width: 10, hidden: true }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteConfig.setDisabled(true);
                this.actions.editConfig.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteConfig.setDisabled(false);
                this.actions.editConfig.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteConfig.setDisabled(false);
                this.actions.editConfig.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Asterisk_Config_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No config to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
		        id:'ctxMenuConfig', 
		        items: [
		            this.actions.editConfig,
		            this.actions.deleteConfig,
		            '-',
		            this.actions.addConfig 
		        ]
		    });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('configWindow', 'index.php?method=Asterisk.editConfig&configId=' + record.data.id, 500, 450);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Asterisk_Config_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteConfig();
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
        var dataStore = Ext.getCmp('Asterisk_Config_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.dataPanelType) {
            case 'phones':
                dataStore.baseParams.method = 'Asterisk.getPhones';
                break;
                
            case 'config':
                dataStore.baseParams.method = 'Asterisk.getConfig';
                break;                
                
            case 'lines':
                dataStore.baseParams.method = 'Asterisk.getLines';
                break;                
                
            case 'settings':
                dataStore.baseParams.method = 'Asterisk.getSettings';
                break;                
                
            case 'software':
                dataStore.baseParams.method = 'Asterisk.getSoftware';
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
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Asterisk_Config_Toolbar') {
            this.initComponent();
            this.displayConfigToolbar();
            this.displayConfigGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Asterisk_Config_Grid')) {
            setTimeout ("Ext.getCmp('Asterisk_Config_Grid').getStore().reload()", 200);
        }
    }
};



Tine.Asterisk.Config.EditDialog =  {

    	configRecord: null,
    	
    	updateConfigRecord: function(_configData)
    	{
            this.configRecord = new Tine.Asterisk.Config.Config(_configData);
    	},
    	
    	deleteConfig: function(_button, _event)
    	{
	        var configIds = Ext.util.JSON.encode([this.configRecord.get('id')]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Asterisk.deleteConfig', 
	                phoneIds: configIds
	            },
	            text: 'Deleting config...',
	            success: function(_result, _request) {
	                window.opener.Tine.Asterisk.Config.Main.reload();
	                window.close();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the config.'); 
	            } 
	        });    		
    	},
    	
        applyChanges: function(_button, _event, _closeWindow) 
        {
        	var form = Ext.getCmp('asterisk_editConfigForm').getForm();

        	if(form.isValid()) {
        		form.updateRecord(this.configRecord);
	    
	            Ext.Ajax.request({
	                params: {
	                    method: 'Asterisk.saveConfig', 
	                    configData: Ext.util.JSON.encode(this.configRecord.data)
	                },
	                success: function(_result, _request) {
	                	if(window.opener.Tine.Asterisk.Config) {
                            window.opener.Tine.Asterisk.Config.Main.reload();
	                	}
                        if(_closeWindow === true) {
                            window.close();
                        } else {
		                	this.updateConfigRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
		                	this.updateToolbarButtons();
		                	form.loadRecord(this.configRecord);
                        }
	                },
	                failure: function ( result, request) { 
	                    Ext.MessageBox.alert('Failed', 'Could not save config.'); 
	                },
	                scope: this 
	            });
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
    	},

        saveChanges: function(_button, _event) 
        {
        	this.applyChanges(_button, _event, true);
        },
        
        editConfigDialog: [{
            layout:'form',
            //frame: true,
            border:false,
            width: 490,
            height: 410,
            items: [{
                    xtype: 'textfield',
                    fieldLabel: 'Firmware Status Address',
                    name: 'firmware_status',
                    maxLength: 255,
                    anchor:'100%',
                    allowBlank: false
                } , {
                    xtype: 'textfield',
                    fieldLabel: 'Server Settings Address',
                    name: 'setting_server',
                    maxLength: 255,
                    anchor:'100%',
                    allowBlank: false
                } , {
                    xtype: 'textfield',
                    fieldLabel: 'NTP Server Address',
                    name: 'ntp_server',
                    maxLength: 255,
                    anchor:'100%',
                    allowBlank: false
                } , {
                    labelSeparator: '',
                    xtype:'textarea',
                    name: 'description',
                    fieldLabel: 'Description',
                    grow: false,
                    preventScrollbars:false,
                    anchor:'100%',
                    height: 30
                } , {
                    layout:'column',
                    border:false,
                    items: [{
                        columnWidth: .5,
                        layout: 'form',
                        border: false,
                        items:[{
                            xtype: 'combo',
                            fieldLabel: 'Update Policy',
                            name: 'update_policy',
                            mode: 'local',
                            displayField:'policy',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore(
                                {
                                    fields: ['key','policy'],
                                    data: [
                                            ['auto_update', 'auto update'], 
                                            ['ask_for_update', 'ask for update'],  
                                            ['never_update_firm', 'never update firm'],  
                                            ['never_update_boot', 'never update boot'],  
                                            ['settings_only', 'settings only'],  
                                            ['never_update', 'never update']
                                    ]
                                }
                            )
                        } , {
                            xtype: 'combo',
                            fieldLabel: 'Webserver Type',
                            name: 'webserver_type',
                            mode: 'local',
                            displayField:'wwwtype',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore(
                                {
                                    fields: ['key','wwwtype'],
                                    data: [
                                            ['https', 'https'],
                                            ['http', 'http'],
                                            ['http_https', 'http https'],
                                            ['off', 'off']
                                    ]
                                }
                            )
                        } , {
                            xtype: 'combo',
                            fieldLabel: 'Filter Registrar',
                            name: 'filter_registrar',
                            mode: 'local',
                            displayField:'bool',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore(
                                {
                                    fields: ['key','bool'],
                                    data: [
                                            ['on', 'on'],
                                            ['off', 'off']
                                    ]
                                }
                            )
                        } , {
                            xtype: 'combo',
                            fieldLabel: 'Call Pickup Dialog Info',
                            name: 'callpickup_dialoginfo',
                            mode: 'local',
                            displayField:'bool',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore(
                                {
                                    fields: ['key','bool'],
                                    data: [
                                            ['on', 'on'],
                                            ['off', 'off']
                                    ]
                                }
                            )
                        } , {
                            xtype: 'combo',
                            fieldLabel: 'Pickup Indication',
                            name: 'pickup_indication',
                            mode: 'local',
                            displayField:'bool',
                            valueField:'key',
                            anchor:'98%',                    
                            triggerAction: 'all',
                            allowBlank: false,
                            editable: false,
                            store: new Ext.data.SimpleStore(
                                {
                                    fields: ['key','bool'],
                                    data: [
                                            ['on', 'on'],
                                            ['off', 'off']
                                    ]
                                }
                            )
                        }]
                    } , {
                    columnWidth: .5,
                    layout: 'form',
                    border: false,
                    items:[{
                            xtype: 'textfield',
                            fieldLabel: 'Firmware Interval',
                            name: 'firmware_interval',
                            maxLength: 11,
                            anchor:'98%',
                            allowBlank: false
                        },{
                        xtype: 'combo',
                        fieldLabel: 'Admin Mode',
                        name: 'admin_mode',
                        mode: 'local',
                        displayField:'bool',
                        valueField:'key',
                        anchor:'98%',                    
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: new Ext.data.SimpleStore(
                            {
                                fields: ['key','bool'],
                                data: [
                                        ['true', 'true'],
                                        ['false', 'false']
                                ]
                            }
                          )
                    },{
                        xtype: 'textfield',
                        fieldLabel: 'Admin Mode Password',
                        name: 'admin_mode_password',
                        inputType: 'password',
                        maxLength: 20,
                        anchor:'100%'
                    },{
                        xtype: 'textfield',
                        fieldLabel: 'HTTP User',
                        name: 'http_user',
                        maxLength: 20,
                        anchor:'100%'
                    },{
                        xtype: 'textfield',
                        fieldLabel: 'HTTP Password',
                        name: 'http_pass',
                        inputType: 'password',
                        maxLength: 20,
                        anchor:'100%'
                    }]
                }]
            }]
        }],
        
        updateToolbarButtons: function()
        {
            if(this.configRecord.get('id') > 0) {
                Ext.getCmp('asterisk_editConfigForm').action_delete.enable();
            }
        },
        
        display: function(_configData) 
        {       	
            // Ext.FormPanel
		    var dialog = new Tine.widgets.dialog.EditRecord({
		        id : 'asterisk_editConfigForm',
		        //title: 'the title',
		        labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteConfig,
		        items: this.editConfigDialog
		    });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
	        
	        //if (!arguments[0]) var task = {};
            this.updateConfigRecord(_configData);
            this.updateToolbarButtons();           
	        dialog.getForm().loadRecord(this.configRecord);
        }
   
};





Ext.namespace('Tine.Asterisk.Software');

Tine.Asterisk.Software.Main = {
       
	actions: {
	    addSoftware: null,
	    editSoftware: null,
	    deleteSoftware: null
	},
	
	handlers: {
	    /**
	     * onclick handler for addSoftware
	     */
	    addSoftware: function(_button, _event) 
	    {
	        Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Asterisk.editSoftware&softwareId=', 450, 300);
	    },

        /**
         * onclick handler for editSoftware
         */
        editSoftware: function(_button, _event) 
        {
            var selectedRows = Ext.getCmp('Asterisk_Software_Grid').getSelectionModel().getSelections();
            var softwareId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Asterisk.editSoftware&softwareId=' + softwareId, 450, 300);
        },
        
	    /**
	     * onclick handler for deleteSoftware
	     */
	    deleteSoftware: function(_button, _event) {
	        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected software?', function(_button){
	            if (_button == 'yes') {
	            
	                var softwareIds = [];
	                var selectedRows = Ext.getCmp('Asterisk_Software_Grid').getSelectionModel().getSelections();
	                for (var i = 0; i < selectedRows.length; ++i) {
	                    softwareIds.push(selectedRows[i].id);
	                }
	                
	                softwareIds = Ext.util.JSON.encode(softwareIds);
	                
	                Ext.Ajax.request({
	                    url: 'index.php',
	                    params: {
	                        method: 'Asterisk.deleteSoftware',
	                        _softwareIds: softwareIds
	                    },
	                    text: 'Deleting software...',
	                    success: function(_result, _request){
	                        Ext.getCmp('Asterisk_Software_Grid').getStore().reload();
	                    },
	                    failure: function(result, request){
	                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the software.');
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
        this.translation.textdomain('Asterisk');
    
        this.actions.addSoftware = new Ext.Action({
            text: this.translation._('add software'),
            handler: this.handlers.addSoftware,
            iconCls: 'action_addSoftware',
            scope: this
        });
        
        this.actions.editSoftware = new Ext.Action({
            text: this.translation._('edit software'),
            disabled: true,
            handler: this.handlers.editSoftware,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteSoftware = new Ext.Action({
            text: this.translation._('delete software'),
            disabled: true,
            handler: this.handlers.deleteSoftware,
            iconCls: 'action_delete',
            scope: this
        });
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('AddressbookTreePanel');
        //if(Tine.Asterisk.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('AsteriskTreePanel');
        preferencesButton.setDisabled(true);
    },
	
    displaySoftwareToolbar: function()
    {
        var onFilterChange = function(_field, _newValue, _oldValue){
            // only refresh data on new query strings
            if (_newValue != _oldValue) {
                Ext.getCmp('Asterisk_Software_Grid').getStore().load({
                    params: {
                        start: 0,
                        limit: 50
                    }
                });
            }
        };
        
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 240
        }); 
        quickSearchField.on('change', onFilterChange, this);
        
        var tagFilter = new Tine.widgets.tags.TagCombo({
            app: 'Asterisk',
            blurOnSelect: true
        });
        tagFilter.on('change', onFilterChange, this);
        
        var softwareToolbar = new Ext.Toolbar({
            id: 'Asterisk_Software_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addSoftware, 
                this.actions.editSoftware,
                this.actions.deleteSoftware,
                '->',
                this.translation._('Filter: '), tagFilter,
                this.translation._('Search: '), quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(softwareToolbar);
    },

    displaySoftwareGrid: function() 
    {
    	// the datastore
        var dataStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Asterisk.Software.Software,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('description', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
            _dataStore.baseParams.tagFilter = Ext.getCmp('TagCombo').getValue();
        }, this);   
        
        //Ext.StoreMgr.add('SoftwareStore', dataStore);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation._('Displaying software {0} - {1} of {2}'),
            emptyMsg: this.translation._("No software to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'id', header: this.translation._('id'), dataIndex: 'id', width: 20, hidden: true },
            { resizable: true, id: 'softwareimage', header: this.translation._('Software Image'), dataIndex: 'softwareimage', width: 200 },
            { resizable: true, id: 'model', header: this.translation._('Phone Model'), dataIndex: 'model', width: 150 },
            { resizable: true, id: 'description', header: this.translation._('Description'), dataIndex: 'description', width: 150 }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteSoftware.setDisabled(true);
                this.actions.editSoftware.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteSoftware.setDisabled(false);
                this.actions.editSoftware.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteSoftware.setDisabled(false);
                this.actions.editSoftware.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Asterisk_Software_Grid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No software to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
		        id:'ctxMenuSoftware', 
		        items: [
		            this.actions.editSoftware,
		            this.actions.deleteSoftware,
		            '-',
		            this.actions.addSoftware 
		        ]
		    });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            //console.log('id: ' + record.data.id);
            try {
                Tine.Tinebase.Common.openWindow('softwareWindow', 'index.php?method=Asterisk.editSoftware&softwareId=' + record.data.id, 450, 300);
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Asterisk_Software_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteSoftware();
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
        var dataStore = Ext.getCmp('Asterisk_Software_Grid').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        switch(_node.attributes.dataPanelType) {
            case 'phones':
                dataStore.baseParams.method = 'Asterisk.getPhones';
                break;
                
            case 'config':
                dataStore.baseParams.method = 'Asterisk.getConfig';
                break;                
                
            case 'lines':
                dataStore.baseParams.method = 'Asterisk.getLines';
                break;                
                
            case 'settings':
                dataStore.baseParams.method = 'Asterisk.getSettings';
                break;                
                
            case 'software':
                dataStore.baseParams.method = 'Asterisk.getSoftware';
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
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Asterisk_Software_Toolbar') {
            this.initComponent();
            this.displaySoftwareToolbar();
            this.displaySoftwareGrid();
            this.updateMainToolbar();
        }
        this.loadData(_node);
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('Asterisk_Software_Grid')) {
            setTimeout ("Ext.getCmp('Asterisk_Software_Grid').getStore().reload()", 200);
        }
    }
};



Tine.Asterisk.Software.EditDialog =  {

    	softwareRecord: null,
    	
    	updateSoftwareRecord: function(_softwareData)
    	{
            this.softwareRecord = new Tine.Asterisk.Software.Software(_softwareData);
    	},
    	
    	deleteSoftware: function(_button, _event)
    	{
	        var softwareIds = Ext.util.JSON.encode([this.softwareRecord.get('id')]);
	            
	        Ext.Ajax.request({
	            url: 'index.php',
	            params: {
	                method: 'Asterisk.deleteSoftware', 
	                phoneIds: softwareIds
	            },
	            text: 'Deleting software...',
	            success: function(_result, _request) {
	                window.opener.Tine.Asterisk.Software.Main.reload();
	                window.close();
	            },
	            failure: function ( result, request) { 
	                Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the software.'); 
	            } 
	        });    		
    	},
    	
        applyChanges: function(_button, _event, _closeWindow) 
        {
        	var form = Ext.getCmp('asterisk_editSoftwareForm').getForm();

        	if(form.isValid()) {
        		form.updateRecord(this.softwareRecord);
	    
	            Ext.Ajax.request({
	                params: {
	                    method: 'Asterisk.saveSoftware', 
	                    softwareData: Ext.util.JSON.encode(this.softwareRecord.data)
	                },
	                success: function(_result, _request) {
	                	if(window.opener.Tine.Asterisk.Software) {
                            window.opener.Tine.Asterisk.Software.Main.reload();
	                	}
                        if(_closeWindow === true) {
                            window.close();
                        } else {
		                	this.updateSoftwareRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
		                	this.updateToolbarButtons();
		                	form.loadRecord(this.softwareRecord);
                        }
	                },
	                failure: function ( result, request) { 
	                    Ext.MessageBox.alert('Failed', 'Could not save software.'); 
	                },
	                scope: this 
	            });
	        } else {
	            Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
	        }
    	},

        saveChanges: function(_button, _event) 
        {
        	this.applyChanges(_button, _event, true);
        },
        
        editSoftwareDialog: [{
            layout:'form',
            //frame: true,
            border:false,
            width: 440,
            height: 280,
            items: [{
	            xtype: 'combo',
	            fieldLabel: 'Model',
	            name: 'model',
	            mode: 'local',
	            displayField:'model',
	            valueField:'key',
	            anchor:'100%',                    
	            triggerAction: 'all',
	            allowBlank: false,
	            editable: false,
	            forceSelection: true,
	            store: new Ext.data.SimpleStore(
	                {
	                    fields: ['key','model'],
	                    data: [
	                        ['snom300','Snom 300'],
	                        ['snom320','Snom 320'],
	                        ['snom360','Snom 360'],
	                        ['snom370','Snom 370']                                        
	                    ]
	                }
	            )
            } , {
                xtype: 'textfield',
                fieldLabel: 'Software Version',
                name: 'softwareimage',
                maxLength: 128,
                anchor:'100%'
            }, {
                //labelSeparator: '',
                xtype:'textarea',
                name: 'description',
                fieldLabel: 'Description',
                grow: false,
                preventScrollbars:false,
                anchor:'100%',
                height: 60
            }]
        }],
        
        updateToolbarButtons: function()
        {
            if(this.softwareRecord.get('id') > 0) {
                Ext.getCmp('asterisk_editSoftwareForm').action_delete.enable();
            }
        },
        
        display: function(_softwareData) 
        {       	
            if (!arguments[0]) {
                var _softwareData = {model:'snom320'};
            }

            // Ext.FormPanel
		    var dialog = new Tine.widgets.dialog.EditRecord({
		        id : 'asterisk_editSoftwareForm',
		        layout: 'fit',
		        //title: 'the title',
		        labelWidth: 120,
                labelAlign: 'top',
                handlerScope: this,
                handlerApplyChanges: this.applyChanges,
                handlerSaveAndClose: this.saveChanges,
                handlerDelete: this.deleteSoftware,
		        items: this.editSoftwareDialog
		    });

            var viewport = new Ext.Viewport({
                layout: 'border',
                frame: true,
                //height: 300,
                items: dialog
            });
	        
            this.updateSoftwareRecord(_softwareData);
            this.updateToolbarButtons();           
            dialog.getForm().loadRecord(this.softwareRecord);
        }
   
};


Tine.Asterisk.Phones.Phone = Ext.data.Record.create([
    {name: 'id'},
    {name: 'macaddress'},
    {name: 'model'},
    {name: 'swversion'},
    {name: 'ipaddress'},
    {name: 'last_modified_time'},
    {name: 'software_id'},
    {name: 'description'}
]);



Tine.Asterisk.Phones.Config = Ext.data.Record.create([
    {name: 'firmware_interval'},
    {name: 'firmware_status'},
    {name: 'update_policy'},
    {name: 'setting_server'},
    {name: 'admin_mode'},
    {name: 'admin_mode_password'},
    {name: 'ntp_server'},
    {name: 'webserver_type'},
    {name: 'https_port'},
    {name: 'http_user'},
    {name: 'http_pass'},
    {name: 'id'},
    {name: 'description'},
    {name: 'filter_registrar'},
    {name: 'callpickup_dialoginfo'},
    {name: 'pickup_indication'}
]);


Tine.Asterisk.Config.Config = Ext.data.Record.create([
    {name: 'firmware_interval'},
    {name: 'firmware_status'},
    {name: 'update_policy'},
    {name: 'setting_server'},
    {name: 'admin_mode'},
    {name: 'admin_mode_password'},
    {name: 'ntp_server'},
    {name: 'webserver_type'},
    {name: 'https_port'},
    {name: 'http_user'},
    {name: 'http_pass'},
    {name: 'id'},
    {name: 'description'},
    {name: 'filter_registrar'},
    {name: 'callpickup_dialoginfo'},
    {name: 'pickup_indication'}
]);

Tine.Asterisk.Software.Software = Ext.data.Record.create([
    {name: 'id'},
    {name: 'description'},
    {name: 'model'},
    {name: 'softwareimage'}
]);
