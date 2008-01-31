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
    getPermsPanel: function() {
        var _removeAccountHandler = function(_button, _event) {
            var selectedRows = Ext.getCmp('Addressbook_Grants_Grid').getSelectionModel().getSelections();
            var grantsStore = Ext.getCmp('Addressbook_Grants_Grid').getStore();
            for (var i = 0; i < selectedRows.length; ++i) {
                grantsStore.remove(selectedRows[i]);
            }
    
            Ext.getCmp('Addressbook_Grants_SaveButton').enable();
            Ext.getCmp('Addressbook_Grants_ApplyButton').enable();
        };
    
        var _addAccountHandler = function(_button, _event) {
            var grantsStore = Ext.getCmp('Addressbook_Grants_Grid').getStore();
            var grantsSelectionModel = Ext.getCmp('Addressbook_Grants_Grid').getSelectionModel();
            var accountsSelectionModel = Ext.getCmp('Egwbase_Accounts_Grid').getSelectionModel();
            
            var selectedRows = accountsSelectionModel.getSelections();
    
            var currentRecordId; 
            var addedRows = false;
    
            for (var i = 0; i < selectedRows.length; ++i) {
                currentRecordId = selectedRows[i].id;
                if(grantsStore.getById(selectedRows[i].id) === undefined) {
                
                    var grantsRecord = Ext.data.Record.create(
                        {name: 'accountId'},
                        {name: 'accountName'},
                        {name: 'readGrant'},
                        {name: 'addGrant'},
                        {name: 'editGrant'},
                        {name: 'deleteGrant'}
                    );
                    
                    grantsStore.addSorted(new grantsRecord({
                        accountId: selectedRows[i].data.accountId,
                        accountName: selectedRows[i].data.accountDisplayName,
                        readGrant: true,
                        addGrant: false,
                        editGrant: false,
                        deleteGrant: false
                    }, selectedRows[i].id));
                    
                    addedRows = true;
                }
            }
            
            grantsSelectionModel.selectRow(grantsStore.indexOfId(currentRecordId));
            
            if(addedRows === true) {
                Ext.getCmp('Addressbook_Grants_SaveButton').enable();
                Ext.getCmp('Addressbook_Grants_ApplyButton').enable();
            }
        };
    
    
        var action_addAccount = new Ext.Action({
            text: 'add account',
            disabled: true,
            handler: _addAccountHandler,
            iconCls: 'action_addContact'
        });
    
        var action_removeAccount = new Ext.Action({
            text: 'remove account',
            disabled: true,
            handler: _removeAccountHandler,
            iconCls: 'action_deleteContact'
        });
        
        var dataStore = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Egwbase.getContainerGrants',
                containerId: ''
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: [
                {name: 'accountId'},
                {name: 'accountName'},
                {name: 'readGrant'},
                {name: 'addGrant'},
                {name: 'editGrant'},
                {name: 'deleteGrant'}
            ]
            // turn off remote sorting
            //remoteSort: false
        });
        
        dataStore.setDefaultSort('accountName', 'asc');
        
        dataStore.load();
        
        dataStore.on('update', function(_store){
            Ext.getCmp('Addressbook_Grants_SaveButton').enable();
            Ext.getCmp('Addressbook_Grants_ApplyButton').enable();
        });
        
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
            deleteColumn/*,
            new Ext.grid.CheckColumn({
                header: "Admin",
                dataIndex: 'admin',
                width: 55
            }) */
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        var permissionsBottomToolbar = new Ext.Toolbar({
            items: [
                action_removeAccount
            ]
        });
        

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                action_removeAccount.setDisabled(true);
            } else {
                // only one row selected
                action_removeAccount.setDisabled(false);
            }
        });
        
        var gridPanel = new Ext.grid.EditorGridPanel({
            region: 'center',
            id: 'Addressbook_Grants_Grid',
            title: 'Permissions',
            store: dataStore,
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
        
        return gridPanel;
    }
})