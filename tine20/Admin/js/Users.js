/*
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         add all actions to toolbar (some are only in ctx menu at the moment) 
 */
Ext.namespace('Tine.Admin.Users');
Tine.Admin.Users.Main = function() {

    var _createDataStore = function() {
        /**
         * the datastore for lists
         */
        var dataStore = new Ext.data.DirectStore({
            directFn: Tine.Admin.getUsers,
            reader: new Ext.data.JsonReader({
                root: 'results',
                totalProperty: 'totalcount',
                id: 'accountId'
            },Tine.Admin.Model.User),
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
              
            case 'expired':
              gridValue = "<img src='images/oxygen/16x16/status/user-away.png' width='12' height='12'/>";
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
        
        // @deprecated
        updateMainToolbar : function() {
        },

        addButtonHandler: function(_button, _event) {
            Tine.Admin.Users.EditDialog.openWindow({
                record: new Tine.Admin.Model.User({}),
                listeners: {
                    scope: this,
                    'update' : function(record) {
                        Ext.getCmp('AdminUserGrid').getStore().reload();
                    }
                }
            });
        },

        editButtonHandler: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminUserGrid').getSelectionModel().getSelections();
            var account = selectedRows[0];
            Tine.Admin.Users.EditDialog.openWindow({
                record: account,
                listeners: {
                    scope: this,
                    'update' : function(record) {
                        Ext.getCmp('AdminUserGrid').getStore().reload();
                    }
                }
            });
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
    
        /**
         * reset password
         * 
         * @todo add checkbox for must change pw
         */
        resetPasswordHandler: function(_button, _event) {
            Ext.MessageBox.prompt(this.translation.gettext('Set new password'), this.translation.gettext('Please enter the new password:'), function(_button, _text) {
                if(_button == 'ok') {
                    var accountObject = Ext.util.JSON.encode(Ext.getCmp('AdminUserGrid').getSelectionModel().getSelected().data);
                    
                    Ext.Ajax.request( {
                        params: {
                            method    : 'Admin.resetPassword',
                            account   : accountObject,
                            password  : _text,
                            mustChange: false
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
                            Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occurred while trying to delete the account(s).'));
                        },
                        scope: this
                    });
                }
            }, this);
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
                emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
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
                    /*
                    '-',
                    this.actionEnable,
                    this.actionDisable,
                    this.actionResetPassword,
                    */                    
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
            if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts') ) {
                this.actionAddAccount.setDisabled(false);
            }
            
            var ctxMenuGrid = new Ext.menu.Menu({
                /*id:'AdminAccountContextMenu',*/ 
                items: [
                    this.actionEditAccount,
                    this.actionDeleteAccount,
                    '-',
                    this.actionEnable,
                    this.actionDisable,
                    this.actionResetPassword,
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
            
            var accountBackend = Tine.Tinebase.registry.get('accountBackend');
            var ldapBackend = (accountBackend == 'Ldap');
            
            var columnModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true,
                    resizable: true
                },
                columns: [
                    { header: this.translation.gettext('ID'), id: 'accountId', dataIndex: 'accountId', hidden: true, width: 50},
                    { header: this.translation.gettext('Status'), id: 'accountStatus', dataIndex: 'accountStatus', hidden: ldapBackend, width: 50, renderer: _renderStatus},
                    { header: this.translation.gettext('Displayname'), id: 'accountDisplayName', dataIndex: 'accountDisplayName'},
                    { header: this.translation.gettext('Loginname'), id: 'accountLoginName', dataIndex: 'accountLoginName', width: 200},
                    { header: this.translation.gettext('Last name'), id: 'accountLastName', dataIndex: 'accountLastName', hidden: true},
                    { header: this.translation.gettext('First name'), id: 'accountFirstName', dataIndex: 'accountFirstName', hidden: true},
                    { header: this.translation.gettext('Email'), id: 'accountEmailAddress', dataIndex: 'accountEmailAddress', width: 200},
                    { header: this.translation.gettext('OpenID'), id: 'openid', dataIndex: 'openid', width: 200, hidden: true},
                    { header: this.translation.gettext('Last login at'), id: 'accountLastLogin', dataIndex: 'accountLastLogin', hidden: ldapBackend, width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
                    { header: this.translation.gettext('Last login from'), id: 'accountLastLoginfrom', hidden: ldapBackend, dataIndex: 'accountLastLoginfrom'},
                    { header: this.translation.gettext('Password changed'), id: 'accountLastPasswordChange', dataIndex: 'accountLastPasswordChange', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
                    { header: this.translation.gettext('Expires'), id: 'accountExpires', dataIndex: 'accountExpires', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer}
                ]
            });
            
            var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
            
            rowSelectionModel.on('selectionchange', function(_selectionModel) {
                var rowCount = _selectionModel.getCount();
    
                if ( Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts') ) {
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
                Tine.Admin.Users.EditDialog.openWindow({
                    record: record,
                    listeners: {
                        scope: this,
                        'update' : function(record) {
                            Ext.getCmp('AdminUserGrid').getStore().reload();
                        }
                    } 
                });
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
                iconCls: 'action_password',
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

/************** models *****************/

Ext.ns('Tine.Admin.Model');

/**
 * Model of an account
 */
Tine.Admin.Model.UserArray = [
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
    { name: 'accountEmailAddress' },
    { name: 'accountHomeDirectory' },
    { name: 'accountLoginShell' },
    { name: 'openid'},
    { name: 'visibility'},
    { name: 'sambaSAM' },
    { name: 'emailUser' }
];

Tine.Admin.Model.User = Tine.Tinebase.data.Record.create(Tine.Admin.Model.UserArray, {
    appName: 'Admin',
    modelName: 'User',
    idProperty: 'accountId',
    titleProperty: 'accountDisplayName',
    // ngettext('User', 'Users', n);
    recordName: 'User',
    recordsName: 'Users'
});

Tine.Admin.Model.SAMUserArray = [
    { name: 'sid'              },
    { name: 'primaryGroupSID'  },
    { name: 'acctFlags'        },
    { name: 'homeDrive'        },
    { name: 'homePath'         },
    { name: 'profilePath'      },
    { name: 'logonScript'      },
    { name: 'logonTime',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'logoffTime',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'kickoffTime',   type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdLastSet',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdCanChange',  type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdMustChange', type: 'date', dateFormat: Date.patterns.ISO8601Long }
];

Tine.Admin.Model.SAMUser = Tine.Tinebase.data.Record.create(Tine.Admin.Model.SAMUserArray, {
    appName: 'Admin',
    modelName: 'SAMUser',
    idProperty: 'sid',
    titleProperty: null,
    // ngettext('Samba User', 'Samba Users', n);
    recordName: 'Samba User',
    recordsName: 'Samba Users'
});

Tine.Admin.Model.EmailUserArray = [
    { name: 'emailUID' },
    { name: 'emailGID' },
    { name: 'emailMailQuota' },
    { name: 'emailMailSize' },
    { name: 'emailSieveQuota' },
    { name: 'emailSieveSize' },
    { name: 'emailLastLogin', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'emailUserId' },
    { name: 'emailAliases' },
    { name: 'emailForwards' },
    { name: 'emailForwardOnly' },
    { name: 'emailAddress' },
    { name: 'emailUsername' }
];

Tine.Admin.Model.EmailUser = Tine.Tinebase.data.Record.create(Tine.Admin.Model.EmailUserArray, {
    appName: 'Admin',
    modelName: 'EmailUser',
    idProperty: 'sid',
    titleProperty: null,
    // ngettext('Email User', 'Email Users', n);
    recordName: 'Email User',
    recordsName: 'Email Users'
});

/************** backends *****************/

Tine.Admin.userBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'User',
    recordClass: Tine.Admin.Model.User,
    idProperty: 'accountId'
});

Tine.Admin.samUserBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'SAMUser',
    recordClass: Tine.Admin.Model.SAMUser,
    idProperty: 'sid'
});

Tine.Admin.emailUserBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'EmailUser',
    recordClass: Tine.Admin.Model.EmailUser,
    idProperty: 'emailUID'
});
