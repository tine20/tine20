/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.container');

Tine.widgets.container.grantDialog = Ext.extend(Tine.widgets.AccountpickerActiondialog, {
	/**
	 * @cfg {Tine.Tinebase.container.models.container}
	 * Container to manage grants for
	 */
	grantContainer: null,
	/**
	 * @cfg {string}
	 * Name of container folders, e.g. Addressbook
	 */
	folderName: null,
	/**
	 * @property {Object}
	 * Models 
	 */
	models: {
		containerGrant : Tine.Tinebase.Model.Grant
	},
	selectType: 'both',
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
            
            var recordIndex = cgd.getRecordIndex(account);
			
			if (recordIndex === false) {
				var record = new cgd.models.containerGrant({
                    id: null,
					accountId: account.data.data,
                    accountType: account.data.type,
					readGrant: true,
					addGrant: false,
					editGrant: false,
					deleteGrant: false,
					adminGrant: false
				});
				dataStore.addSorted(record);
                Ext.getCmp('AccountsActionSaveButton').enable();
                Ext.getCmp('AccountsActionApplyButton').enable();
            }
			grantsSelectionModel.selectRow(cgd.getRecordIndex(account));
		},
		accountsActionApply: function(button, event, closeWindow) {
			// we somehow lost scope...
            var cgd = Ext.getCmp('ContainerGrantsDialog');
			if (cgd.grantContainer) {
				var container = cgd.grantContainer;
				Ext.MessageBox.wait(_('Please wait'), _('Updateing Grants'));
				
				var grants = [];
				var grantsStore = cgd.dataStore;
				
				grantsStore.each(function(_record){
                    var grant = new Tine.Tinebase.Model.Grant(_record.data);
                    grant.data.accountId = _record.data.accountType == 'group' ?
                        _record.data.accountId.id :
                        _record.data.accountId.accountId;
					grants.push(grant.data);
				});
				
				Ext.Ajax.request({
					params: {
						method: 'Tinebase_Container.setContainerGrants',
						containerId: container.id,
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
        
        this.folderName = this.folderName ? this.folderName : _('Folder');
        this.title = 'Manage permissions for ' + this.folderName + ': "' + Ext.util.Format.htmlEncode(this.grantContainer.name) + '"';
		this.actions = {
	        addAccount: new Ext.Action({
	            text: 'add account',
	            disabled: true,
				scope: this,
	            handler: this.handlers.addAccount,
	            iconCls: 'action_addContact'
	        }),
	        removeAccount: new Ext.Action({
	            text: _('remove account'),
	            disabled: true,
				scope: this,
	            handler: this.handlers.removeAccount,
	            iconCls: 'action_deleteContact'
	        })
		};
		this.dataStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Tinebase_Container.getContainerGrants',
                containerId: this.grantContainer.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            // auto gernerate id's, as user/group ids are not unique atm.
            //id: 'id',
            fields: this.models.containerGrant
        });
	    
		Ext.StoreMgr.add('ContainerGrantsStore', this.dataStore);
		
        //this.dataStore.setDefaultSort('accountId', 'asc');
        
        this.dataStore.load();
        
        this.dataStore.on('update', function(_store){
            Ext.getCmp('AccountsActionSaveButton').enable();
            Ext.getCmp('AccountsActionApplyButton').enable();
        }, this);
        
        var columns = [
            new Ext.ux.grid.CheckColumn({
                header: _('Read'),
                dataIndex: 'readGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Add'),
                dataIndex: 'addGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Edit'),
                dataIndex: 'editGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Delete'),
                dataIndex: 'deleteGrant',
                width: 55
            })
        ];
        
        if (this.grantContainer.type == 'shared') {
            columns.push(new Ext.ux.grid.CheckColumn({
                header: _('Admin'),
                dataIndex: 'adminGrant',
                width: 55
            }));
        }
        
        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: true, 
                id: 'accountId', 
                header: _('Name'), 
                dataIndex: 'accountId', 
                renderer: Tine.Tinebase.Common.accountRenderer,
                width: 70
            }
            ].concat(columns)
        );

        
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
            title: _('Permissions'),
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            plugins: columns, // [readColumn, addColumn, editColumn, deleteColumn],
            autoExpandColumn: 'accountId',
            bbar: permissionsBottomToolbar,
            border: false
        });
		
		this.items = [
		   this.GrantsGridPanel
		];
		Tine.widgets.container.grantDialog.superclass.initComponent.call(this);
    },
	// private
	onRender: function(ct, position){
		Tine.widgets.container.grantDialog.superclass.onRender.call(this, ct, position);
		
		this.getUserSelection().on('accountdblclick', function(account){
            this.handlers.addAccount(account);   
        }, this);
	},
    /**
     * returns index of record in this.dataStore
     * @private
     */
    getRecordIndex: function(account) {
        var cgd = Ext.getCmp('ContainerGrantsDialog');
        var dataStore = cgd.dataStore;
        
        var id = false;
        dataStore.each(function(item){
            if ((item.data.accountType == 'user' || item.data.accountType == 'account') &&
                    account.data.type == 'user' &&
                    item.data.accountId.accountId == account.data.id) {
                id = item.id;
            } else if (item.data.accountType == 'group' &&
                    account.data.type == 'group' &&
                    item.data.accountId.id == account.data.id) {
                id = item.id;
            }
        });
        return id ? dataStore.indexOfId(id) : false;
    }
});