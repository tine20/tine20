/**
 * Tine 2.0
 * 
 * @package     Dialer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Dialer');

/**
 * entry point, required by tinebase
 * creates and returnes app tree panel
 */
Tine.Dialer.getPanel = function(){
    var tree = new Ext.tree.TreePanel({
        id: 'dialerTree',
        iconCls: 'DialerIconCls',
        title: 'Dialer',
        border: false,
        root: new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        })
    });

    
    tree.on('click', function(node){
        Tine.Dialer.Main.show(node);
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



Tine.Dialer.Main = {
	actions: 
	{
	   	dialNumber: null
	},
	
	initComponent: function()
    {
        this.actions.dialNumber = new Ext.Action({
            text: 'Dial number',
            tooltip: 'Initiate a new outgoing call',
            handler: this.handlers.dialNumber,
            iconCls: 'action_DialNumber',
            scope: this
        });
    	
    },
    
    handlers: 
    {
    	dialNumber: function(_button, _event) 
    	{
            Ext.MessageBox.prompt('Number', 'Please enter number to dial:', function(_button, _number){
                if (_button == 'ok') {                
		            Ext.Ajax.request({
		                url: 'index.php',
		                params: {
		                    method: 'Dialer.dialNumber',
		                    number: _number
		                },
		                success: function(_result, _request){
		                    //Ext.getCmp('Addressbook_Contacts_Grid').getStore().reload();
		                },
		                failure: function(result, request){
		                    //Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the conctact.');
		                }
		            });
                }
            });    		
    	}
    },
 
    displayToolbar: function()
    {
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('Dialer_Grid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var toolbar = new Ext.Toolbar({
            id: 'Dialer_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.dialNumber, 
/*                '-',
                this.actions.exportContact,
                new Ext.Toolbar.MenuButton(this.actions.callContact),*/
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    },
    
    displayGrid: function() 
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
        
        //Ext.StoreMgr.add('ContactsStore', dataStore);
        
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
            { resizable: true, id: 'n_family', header: 'Family name', dataIndex: 'n_family' },
            { resizable: true, id: 'n_given', header: 'Given name', dataIndex: 'n_given', width: 80 },
            { resizable: true, id: 'n_fn', header: 'Full name', dataIndex: 'n_fn', hidden: true },
            { resizable: true, id: 'n_fileas', header: 'Name + Firm', dataIndex: 'n_fileas', hidden: true }
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
                this.actions.exportContact.setDisabled(true);
                this.actions.callContact.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteContact.setDisabled(false);
                this.actions.editContact.setDisabled(false);
                this.actions.exportContact.setDisabled(false);

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
                Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Addressbook.editContact&_contactId=' + record.data.id, 800, 600);
            } catch(e) {
                // alert(e);
            }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },

    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        /*menu.add(
            {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
        );*/

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('DialerTreePanel');
        //if(Tine.Addressbook.rights.indexOf('admin') > -1) {
        //    adminButton.setDisabled(false);
        //} else {
            adminButton.setDisabled(true);
        //}

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('DialerTreePanel');
        preferencesButton.setDisabled(true);
    },
    
	show: function(_mode) 
	{	
        this.initComponent();
        
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Dialer_Toolbar') {
            this.displayToolbar();
            this.displayGrid();
            this.updateMainToolbar();
        }
        //this.loadData(_node);		
	}
}