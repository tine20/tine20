/*
 * egroupware 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Egw.widgets', 'Egw.widgets.container');

Egw.widgets.container.grantDialog = Ext.extend(Egw.widgets.AccountpickerActiondialog, {
	/**
	 * @cfg {Egw.Egwbase.container.models.container}
	 * Container to manage grants for
	 */
	grantContainer: null,
	/**
	 * @cfg {string}
	 * Name of container folders, e.g. Addressbook
	 */
	folderName: 'Folder',
	/**
	 * @property {Object}
	 * Models 
	 */
	models: {
		containerGrant : Egw.Egwbase.container.models.containerGrant
	},
	
	id: 'ContainerGrantsDialog',
	// private
	handlers: {
        removeAccount: function(_button, _event) {
            var selectedRows = this.GrantsGridPanel.getSelectionModel().getSelections();
            var grantsStore = this.dataStore;
            for (var i = 0; i < selectedRows.length; ++i) {
                grantsStore.remove(selectedRows[i]);
            }
    
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
        },
        addAccount: function(account){
			// we somehow lost scope...
			var cgd = Ext.getCmp('ContainerGrantsDialog');
			var dataStore = cgd.dataStore;
			var grantsSelectionModel = cgd.GrantsGridPanel.getSelectionModel();
			
			if (dataStore.getById(account.data.accountId) === undefined) {
				var record = new cgd.models.containerGrant({
					accountId: account.data.accountId,
					accountName: account.data,
					readGrant: true,
					addGrant: false,
					editGrant: false,
					deleteGrant: false
				}, account.data.accountId);
				dataStore.addSorted(record);
			}
			grantsSelectionModel.selectRow(dataStore.indexOfId(account.data.accountId));
			
			Ext.getCmp('AccountsActionSaveButton').enable();
			Ext.getCmp('AccountsActionApplyButton').enable();
		},
		accountsActionApply: function(button, event, closeWindow) {
			// we somehow lost scope...
            var cgd = Ext.getCmp('ContainerGrantsDialog');
			if (cgd.grantContainer) {
				var container = cgd.grantContainer;
				Ext.MessageBox.wait('Please wait', 'Updateing Grants for "' + container.container_name + '"');
				
				var grants = [];
				var grantsStore = cgd.dataStore;
				
				grantsStore.each(function(_record){
					grants.push(_record.data);
				});
				
				Ext.Ajax.request({
					params: {
						method: 'Egwbase.setContainerGrants',
						containerId: container.container_id,
						grants: Ext.util.JSON.encode(grants)
					},
					success: function(_result, _request){
						var grants = Ext.util.JSON.decode(_result.responseText);
						grantsStore.loadData(grants, false);
						
						Ext.MessageBox.hide();
						if (closeWindow){
							cgd.close();
						}
					}
				});
				
				Ext.getCmp('AccountsActionSaveButton').disable();
				Ext.getCmp('AccountsActionApplyButton').disable();
			}
		},
		accountsActionSave: function(button, event) {
			var cgd = Ext.getCmp('ContainerGrantsDialog');
			cgd.handlers.accountsActionApply(button, event, true);
		}
	},
	//private
    initComponent: function(){
        this.title = 'Manage permissions for ' + this.folderName + ': "' + this.grantContainer.container_name + '"';
		this.actions = {
	        addAccount: new Ext.Action({
	            text: 'add account',
	            disabled: true,
				scope: this,
	            handler: this.handlers.addAccount,
	            iconCls: 'action_addContact'
	        }),
	        removeAccount: new Ext.Action({
	            text: 'remove account',
	            disabled: true,
				scope: this,
	            handler: this.handlers.removeAccount,
	            iconCls: 'action_deleteContact'
	        })
		};
		this.dataStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Egwbase.getContainerGrants',
                containerId: this.grantContainer.container_id
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: this.models.containerGrant
        });
	    
		Ext.StoreMgr.add('ContainerGrantsStore', this.dataStore);
		
        this.dataStore.setDefaultSort('accountName', 'asc');
        
        this.dataStore.load();
        
        this.dataStore.on('update', function(_store){
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
        }, this);
        
        var readColumn = new Ext.grid.CheckColumn({
            header: 'Read',
            dataIndex: 'readGrant',
            width: 55
        });

        var addColumn = new Ext.grid.CheckColumn({
            header: 'Add',
            dataIndex: 'addGrant',
            width: 55
        });
        
        var editColumn = new Ext.grid.CheckColumn({
            header: "Edit",
            dataIndex: 'editGrant',
            width: 55
        });
        
        var deleteColumn = new Ext.grid.CheckColumn({
            header: "Delete",
            dataIndex: 'deleteGrant',
            width: 55
        });
        
        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: true, 
                id: 'accountName', 
                header: 'Name', 
                dataIndex: 'accountName', 
                renderer: Egw.Egwbase.Common.usernameRenderer,
                width: 70
            },
            readColumn,
            addColumn,
            editColumn,
            deleteColumn
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        var permissionsBottomToolbar = new Ext.Toolbar({
            items: [
                this.actions.removeAccount
            ]
        });
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.removeAccount.setDisabled(true);
            } else {
                // only one row selected
                this.actions.removeAccount.setDisabled(false);
            }
        }, this);
        
        this.GrantsGridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            title: 'Permissions',
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            plugins:[readColumn, addColumn, editColumn, deleteColumn],
            autoExpandColumn: 'accountName',
            bbar: permissionsBottomToolbar,
            border: false
        });
		
		this.items = [
		   this.GrantsGridPanel
		];
		Egw.widgets.container.grantDialog.superclass.initComponent.call(this);
    },
	// private
	onRender: function(ct, position){
		Egw.widgets.container.grantDialog.superclass.onRender.call(this, ct, position);
		
		this.getUserSelection().on('accountdblclick', function(account){
            this.handlers.addAccount(account);   
        }, this);
	}
});