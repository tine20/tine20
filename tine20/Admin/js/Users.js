/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Admin.Users');
Tine.Admin.Users.Main = function() {

    var _createDataStore = function() {
        /**
         * the datastore for lists
         */
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getUsers'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: Tine.Admin.Users.Account,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('accountLoginName', 'asc');

        dataStore.on('beforeload', function(_dataSource) {
            _dataSource.baseParams.filter = Ext.getCmp('UserAdminQuickSearchField').getValue();
        });        
        
        dataStore.load({params:{start:0, limit:50}});
        
        return dataStore;
    };

    
    var _renderStatus = function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch(_value) {
            case 'enabled':
              gridValue = "<img src='images/oxygen/16x16/actions/dialog-apply.png' width='12' height='12'/>";
              break;
              
            case 'disabled':
              gridValue = "<img src='images/oxygen/16x16/actions/dialog-cancel.png' width='12' height='12'/>";
              break;
              
            default:
              gridValue = _value;
              break;
        }
        
        return gridValue;
    };

    /**
     * creates the address grid
     * 
     */
    
    // public functions and variables
    return {
        show: function() {
            this.initComponent();
            this.showToolbar();
            this.showMainGrid();            
            this.updateMainToolbar();        
        },
        
        updateMainToolbar : function() {
            var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
            menu.removeAll();
            /*menu.add(
                {text: 'product', handler: Tine.Crm.Main.handlers.editProductSource}
            );*/
    
            var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
            adminButton.setIconClass('AdminTreePanel');
            //if(Admin.Crm.rights.indexOf('admin') > -1) {
            //    adminButton.setDisabled(false);
            //} else {
                adminButton.setDisabled(true);
            //}
    
            var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
            preferencesButton.setIconClass('AdminTreePanel');
            preferencesButton.setDisabled(true);
        },

        addButtonHandler: function(_button, _event) {
            Tine.Admin.Users.EditDialog.openWindow({});
        },

        editButtonHandler: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminUserGrid').getSelectionModel().getSelections();
            var account = selectedRows[0];
            Tine.Admin.Users.EditDialog.openWindow({accountRecord: account});
        },
    
        enableDisableButtonHandler: function(_button, _event) {
            //console.log(_button);
            
            var status = 'disabled';
            if(_button.id == 'Admin_User_Action_Enable') {
                status = 'enabled';
            }
            
            var accountIds = new Array();
            var selectedRows = Ext.getCmp('AdminUserGrid').getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                accountIds.push(selectedRows[i].id);
            }
            
            Ext.Ajax.request({
                url : 'index.php',
                method : 'post',
                params : {
                    method : 'Admin.setAccountState',
                    accountIds : Ext.util.JSON.encode(accountIds),
                    status: status
                },
                callback : function(_options, _success, _response) {
                    if(_success === true) {
                        var result = Ext.util.JSON.decode(_response.responseText);
                        if(result.success === true) {
                            Ext.getCmp('AdminUserGrid').getStore().reload();
                        }
                    }
                }
            });
        },
    
        resetPasswordHandler: function(_button, _event) {
            Ext.MessageBox.prompt(this.translation.gettext('Set new password'), this.translation.gettext('Please enter the new password:'), function(_button, _text) {
                if(_button == 'ok') {
                    //var accountId = Ext.getCmp('AdminUserGrid').getSelectionModel().getSelected().id;
                    var accountObject = Ext.util.JSON.encode(Ext.getCmp('AdminUserGrid').getSelectionModel().getSelected().data);
                    
                    Ext.Ajax.request( {
                        params : {
                            method    : 'Admin.resetPassword',
                            account   : accountObject,
                            password  : _text
                        },
                        callback : function(_options, _success, _response) {
                            if(_success === true) {
                                var result = Ext.util.JSON.decode(_response.responseText);
                                if(result.success === true) {
                                    Ext.getCmp('AdminUserGrid').getStore().reload();
                                }
                            }
                        }
                    });
                }
            });
        },
        
        deleteButtonHandler: function(_button, _event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected account(s)?'), function(_confirmButton){
                if (_confirmButton == 'yes') {
                
                    var accountIds = new Array();
                    var selectedRows = Ext.getCmp('AdminUserGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        accountIds.push(selectedRows[i].id);
                    }
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteUsers',
                            accountIds: Ext.util.JSON.encode(accountIds)
                        },
                        text: this.translation.gettext('Deleting account(s)...'),
                        success: function(_result, _request){
                            Ext.getCmp('AdminUserGrid').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the account(s).'));
                        }
                    });
                }
            });
        },

        actionEnable: null,
        actionDisable: null,
        actionResetPassword: null,
        actionAddAccount: null,
        actionEditAccount: null,
        actionDeleteAccount: null,
        
        showToolbar: function() {
            var UserAdminQuickSearchField = new Ext.ux.SearchField({
                id: 'UserAdminQuickSearchField',
                width:240,
                emptyText: this.translation.gettext('enter searchfilter')
            }); 
            UserAdminQuickSearchField.on('change', function() {
                Ext.getCmp('AdminUserGrid').getStore().load({params:{start:0, limit:50}});
            });
            
            var applicationToolbar = new Ext.Toolbar({
                id: 'AdminUserToolbar',
                split: false,
                height: 26,
                items: [
                    this.actionAddAccount,
                    this.actionEditAccount,
                    this.actionDeleteAccount,
                    '-',
                    '->',
                    this.translation.gettext('Search:'), ' ',
    /*                new Ext.ux.SelectBox({
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
                    UserAdminQuickSearchField
                ]
            });
            
            Tine.Tinebase.MainScreen.setActiveToolbar(applicationToolbar);
        },
        
        showMainGrid: function() {
            if ( Tine.Tinebase.hasRight('manage', 'Admin', 'accounts') ) {
                this.actionAddAccount.setDisabled(false);
            }
            
            var ctxMenuGrid = new Ext.menu.Menu({
                /*id:'AdminAccountContextMenu',*/ 
                items: [
                    this.actionEditAccount,
                    this.actionEnable,
                    this.actionDisable,
                    this.actionResetPassword,
                    this.actionDeleteAccount,
                    '-',
                    this.actionAddAccount 
                ]
            });
        
            var dataStore = _createDataStore();
            
            var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
                pageSize: 50,
                store: dataStore,
                displayInfo: true,
                displayMsg: this.translation.gettext('Displaying accounts {0} - {1} of {2}'),
                emptyMsg: this.translation.gettext("No accounts to display")
            }); 
            
            var columnModel = new Ext.grid.ColumnModel([
                {resizable: true, header: this.translation.gettext('ID'), id: 'accountId', dataIndex: 'accountId', hidden: true, width: 50},
                {resizable: true, header: this.translation.gettext('Status'), id: 'accountStatus', dataIndex: 'accountStatus', width: 50, renderer: _renderStatus},
                {resizable: true, header: this.translation.gettext('Displayname'), id: 'accountDisplayName', dataIndex: 'accountDisplayName'},
                {resizable: true, header: this.translation.gettext('Loginname'), id: 'accountLoginName', dataIndex: 'accountLoginName'},
                {resizable: true, header: this.translation.gettext('Last name'), id: 'accountLastName', dataIndex: 'accountLastName', hidden: true},
                {resizable: true, header: this.translation.gettext('First name'), id: 'accountFirstName', dataIndex: 'accountFirstName', hidden: true},
                {resizable: true, header: this.translation.gettext('Email'), id: 'accountEmailAddress', dataIndex: 'accountEmailAddress', width: 200},
                {resizable: true, header: this.translation.gettext('Last login at'), id: 'accountLastLogin', dataIndex: 'accountLastLogin', width: 130, renderer: Tine.Tinebase.Common.dateTimeRenderer},
                {resizable: true, header: this.translation.gettext('Last login from'), id: 'accountLastLoginfrom', dataIndex: 'accountLastLoginfrom'},
                {resizable: true, header: this.translation.gettext('Password changed'), id: 'accountLastPasswordChange', dataIndex: 'accountLastPasswordChange', width: 130, renderer: Tine.Tinebase.Common.dateTimeRenderer},
                {resizable: true, header: this.translation.gettext('Expires'), id: 'accountExpires', dataIndex: 'accountExpires', width: 130, renderer: Tine.Tinebase.Common.dateTimeRenderer}
            ]);
            
            columnModel.defaultSortable = true; // by default columns are sortable
    
            var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
            
            rowSelectionModel.on('selectionchange', function(_selectionModel) {
                var rowCount = _selectionModel.getCount();
    
                if ( Tine.Tinebase.hasRight('manage', 'Admin', 'accounts') ) {
                    if(rowCount < 1) {
                        this.actionEditAccount.setDisabled(true);
                        this.actionDeleteAccount.setDisabled(true);
                        this.actionEnable.setDisabled(true);
                        this.actionDisable.setDisabled(true);
                        this.actionResetPassword.setDisabled(true);
                        //_action_settings.setDisabled(true);
                    } else if (rowCount > 1){
                        this.actionEditAccount.setDisabled(true);
                        this.actionDeleteAccount.setDisabled(false);
                        this.actionEnable.setDisabled(false);
                        this.actionDisable.setDisabled(false);
                        this.actionResetPassword.setDisabled(true);
                        //_action_settings.setDisabled(true);
                    } else {
                        this.actionEditAccount.setDisabled(false);
                        this.actionDeleteAccount.setDisabled(false);
                        this.actionEnable.setDisabled(false);
                        this.actionDisable.setDisabled(false);
                        this.actionResetPassword.setDisabled(false);
                        //_action_settings.setDisabled(false);              
                    }
                }
            }, this);
                    
            var grid_accounts = new Ext.grid.GridPanel({
                id: 'AdminUserGrid',
                store: dataStore,
                cm: columnModel,
                tbar: pagingToolbar,     
                autoSizeColumns: false,
                selModel: rowSelectionModel,
                enableColLock:false,
                loadMask: true,
                autoExpandColumn: 'accountDisplayName',
                border: false
            });
            
            Tine.Tinebase.MainScreen.setActiveContentPanel(grid_accounts);
    
            grid_accounts.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
                _eventObject.stopEvent();
                if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                    _grid.getSelectionModel().selectRow(_rowIndex);
    
                    this.actionEnable.setDisabled(false);
                    this.actionDisable.setDisabled(false);
                }
                //var record = _grid.getStore().getAt(rowIndex);
                ctxMenuGrid.showAt(_eventObject.getXY());
            }, this);
            
            grid_accounts.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
                var record = _gridPar.getStore().getAt(_rowIndexPar);
                Tine.Admin.Users.EditDialog.openWindow({accountRecord: record});
            });
            
            grid_accounts.on('keydown', function(e){
                 if(e.getKey() == e.DELETE && Ext.getCmp('AdminUserGrid').getSelectionModel().getCount() > 0){
                     this.deleteButtonHandler();
                 }
            }, this);
            
        },
        
        initComponent: function() {
            this.translation = new Locale.Gettext();
            this.translation.textdomain('Admin');
        
            this.actionAddAccount = new Ext.Action({
                text: this.translation.gettext('add account'),
                disabled: true,
                handler: this.addButtonHandler,
                iconCls: 'action_addContact',
                scope: this
            });
            
            this.actionEditAccount = new Ext.Action({
                text: this.translation.gettext('edit account'),
                disabled: true,
                handler: this.editButtonHandler,
                iconCls: 'action_edit',
                scope: this
            });

            this.actionDeleteAccount = new Ext.Action({
                text: this.translation.gettext('delete account'),
                disabled: true,
                handler: this.deleteButtonHandler,
                iconCls: 'action_delete',
                scope: this
            });            
            
            this.actionEnable = new Ext.Action({
                text: this.translation.gettext('enable account'),
                disabled: true,
                handler: this.enableDisableButtonHandler,
                iconCls: 'action_enable',
                id: 'Admin_User_Action_Enable',
                scope: this
            });
        
            this.actionDisable = new Ext.Action({
                text: this.translation.gettext('disable account'),
                disabled: true,
                handler: this.enableDisableButtonHandler,
                iconCls: 'action_disable',
                id: 'Admin_User_Action_Disable',
                scope: this
            });
        
            this.actionResetPassword = new Ext.Action({
                text: this.translation.gettext('reset password'),
                disabled: true,
                handler: this.resetPasswordHandler,
                /*iconCls: 'action_disable',*/
                id: 'Admin_User_Action_resetPassword',
                scope: this
            });
        },
        
        reload: function() {
            if(Ext.ComponentMgr.all.containsKey('AdminUserGrid')) {
                setTimeout ("Ext.getCmp('AdminUserGrid').getStore().reload()", 200);
            }
        }
           
        
    };
    
}();

Tine.Admin.Users.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {

    /**
     * @cfg {Tine.Admin.Users.Account}
     */
    accountRecord: null,
    
    windowNamePrefix: 'userEditWindow_',
    
    id : 'admin_editAccountForm',
    labelWidth: 120,
    labelAlign: 'side',
    
    updateRecord: function(_accountData) {
        if(_accountData.accountExpires && _accountData.accountExpires !== null) {
            _accountData.accountExpires = Date.parseDate(_accountData.accountExpires, Date.patterns.ISO8601Long);
        }
        if(_accountData.accountLastLogin && _accountData.accountLastLogin !== null) {
            _accountData.accountLastLogin = Date.parseDate(_accountData.accountLastLogin, Date.patterns.ISO8601Long);
        }
        if(_accountData.accountLastPasswordChange && _accountData.accountLastPasswordChange !== null) {
            _accountData.accountLastPasswordChange = Date.parseDate(_accountData.accountLastPasswordChange, Date.patterns.ISO8601Long);
        }
        if(!_accountData.accountPassword) {
            _accountData.accountPassword = null;
        }

        this.accountRecord = new Tine.Admin.Users.Account(_accountData, _accountData.accountId ? _accountData.accountId : 0);
    },
    
    handlerDelete: function(_button, _event) {
        var accountIds = Ext.util.JSON.encode([this.accountRecord.get('accountId')]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Admin.deleteUsers', 
                accountIds: accountIds
            },
            text: this.translation.gettext('Deleting account...'),
            success: function(_result, _request) {
                window.opener.Tine.Admin.Users.Main.reload();
                window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the account.')); 
            } 
        });         
    },
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();

        if(form.isValid()) {
            Ext.MessageBox.wait(this.translation._('Please Wait'), this.translation._('Saving User Account'));
            form.updateRecord(this.accountRecord);
            if(this.accountRecord.data.accountFirstName) {
                this.accountRecord.data.accountFullName = this.accountRecord.data.accountFirstName + ' ' + this.accountRecord.data.accountLastName;
                this.accountRecord.data.accountDisplayName = this.accountRecord.data.accountLastName + ', ' + this.accountRecord.data.accountFirstName;
            } else {
                this.accountRecord.data.accountFullName = this.accountRecord.data.accountLastName;
                this.accountRecord.data.accountDisplayName = this.accountRecord.data.accountLastName;
            }
    
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveUser', 
                    accountData: Ext.util.JSON.encode(this.accountRecord.data),
                    password: form.findField('accountPassword').getValue(),
                    passwordRepeat: form.findField('accountPassword2').getValue()                        
                },
                success: function(response) {
                    if(window.opener.Tine.Admin.Users) {
                        window.opener.Tine.Admin.Users.Main.reload();
                    }
                    if(_closeWindow === true) {
                        window.close();
                    } else {
                        this.onRecordLoad(response);
                        /*
                        this.updateRecord(Ext.util.JSON.decode(_result.responseText));
                        this.updateToolbarButtons();
                        form.loadRecord(this.accountRecord);
                        */
                    }
                    Ext.MessageBox.hide();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save user account.')); 
                },
                scope: this 
            });
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },
    
    GetEditAccountDialog: function() { return [{
        layout:'column',
        //frame: true,
        border:false,
        autoHeight: true,
        items:[{
            //frame: true,
            columnWidth:.6,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%'
            },
            items: [{
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('First Name'),
                    name: 'accountFirstName'
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Last Name'),
                    name: 'accountLastName',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Login Name'),
                    name: 'accountLoginName',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Password'),
                    name: 'accountPassword',
                    inputType: 'password',
                    emptyText: this.translation.gettext('no password set')
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Password again'),
                    name: 'accountPassword2',
                    inputType: 'password',
                    emptyText: this.translation.gettext('no password set')
                },  new Tine.widgets.group.selectionComboBox({
                    fieldLabel: this.translation.gettext('Primary group'),
                    name: 'accountPrimaryGroup',
                    displayField:'name',
                    valueField:'id'
                }), 
                {
                    xtype: 'textfield',
                    vtype: 'email',
                    fieldLabel: this.translation.gettext('Emailaddress'),
                    name: 'accountEmailAddress'
                }
            ]
        },{
            columnWidth:.4,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%'
            },
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: this.translation.gettext('Status'),
                    name: 'accountStatus',
                    mode: 'local',
                    displayField:'status',
                    valueField:'key',
                    triggerAction: 'all',
                    allowBlank: false,
                    editable: false,
                    store: new Ext.data.SimpleStore(
                        {
                            fields: ['key','status'],
                            data: [
                                ['enabled','enabled'],
                                ['disabled','disabled']
                            ]
                        }
                    )
                }, 
                new Ext.ux.form.ClearableDateField({ 
                    fieldLabel: this.translation.gettext('Expires'),
                    name: 'accountExpires',
                    emptyText: this.translation.gettext('never')
                }), {
                    xtype: 'datetimefield',
                    fieldLabel: this.translation.gettext('Last login at'),
                    name: 'accountLastLogin',
                    emptyText: this.translation.gettext('never logged in'),
                    hideTrigger: true,
                    readOnly: true
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.translation.gettext('Last login from'),
                    name: 'accountLastLoginfrom',
                    emptyText: this.translation.gettext('never logged in'),
                    readOnly: true
                }, {
                    xtype: 'datetimefield',
                    fieldLabel: this.translation.gettext('Password set'),
                    name: 'accountLastPasswordChange',
                    emptyText: this.translation.gettext('never'),
                    hideTrigger: true,
                    readOnly: true
                }
            ]
        }]
    }];},
    
    updateToolbarButtons: function() {
        if(this.accountRecord.get('accountId') > 0) {
            Ext.getCmp('admin_editAccountForm').action_delete.enable();
        }
    },
    
    initComponent: function() {
        this.accountRecord = this.accountRecord ? this.accountRecord : new Tine.Admin.Users.Account({}, 0);
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getUser',
                userId: this.accountRecord.id
            }
        });
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.items = this.GetEditAccountDialog();
        
        Tine.Admin.Users.EditDialog.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('accountFirstName').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.accountRecord.id) {
            window.document.title = this.translation.gettext('Add New User Account');
        } else {
            window.document.title = sprintf(this.translation._('Edit User Account "%s"'), this.accountRecord.get('accountDisplayName'));
        }
        
        this.getForm().loadRecord(this.accountRecord);
        this.updateToolbarButtons();
        Ext.MessageBox.hide();
    }
});

/**
 * Users Edit Popup
 */
Tine.Admin.Users.EditDialog.openWindow = function (config) {
    config.accountRecord = config.accountRecord ? config.accountRecord : new Tine.Admin.Users.Account({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 450,
        name: Tine.Admin.Users.EditDialog.prototype.windowNamePrefix + config.accountRecord.id,
        layout: Tine.Admin.Users.EditDialog.prototype.windowLayout,
        itemsConstructor: 'Tine.Admin.Users.EditDialog',
        itemsConstructorConfig: config
    });
    return window;
};

/**
 * Model of an account
 */
Tine.Admin.Users.Account = Ext.data.Record.create([
    // tine record fields
    { name: 'accountId' },
    { name: 'accountFirstName' },
    { name: 'accountLastName' },
    { name: 'accountLoginName' },
    { name: 'accountPassword' },
    { name: 'accountDisplayName' },
    { name: 'accountFullName' },
    { name: 'accountStatus' },
    { name: 'accountPrimaryGroup' },
    { name: 'accountExpires', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'accountLastLogin', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'accountLastPasswordChange', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'accountLastLoginfrom' },
    { name: 'accountEmailAddress' }
]);