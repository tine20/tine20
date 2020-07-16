/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.user');


/**
 * User grid panel
 * 
 * @namespace   Tine.Admin.user
 * @class       Tine.Admin.user.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.user.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @property isLdapBackend
     * @type Boolean
     */
    isLdapBackend: false,
    
    newRecordIcon: 'action_addContact',
    recordClass: Tine.Admin.Model.User,
    recordProxy: Tine.Admin.userBackend,
    defaultSortInfo: {field: 'accountLoginName', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        id: 'gridAdminUsers',
        autoExpandColumn: 'accountDisplayName'
    },
    
    initComponent: function() {
        this.gridConfig.cm = this.getColumnModel();
        this.isLdapBackend = Tine.Tinebase.registry.get('accountBackend') == 'Ldap';
        this.isEmailBackend = Tine.Tinebase.registry.get('manageImapEmailUser');
        Tine.Admin.user.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        this.actionEnable = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Enable Account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this, ['enabled']),
            iconCls: 'action_enable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, [['disabled', 'blocked', 'expired']], true)
        });
    
        this.actionDisable = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Disable Account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this, ['disabled']),
            iconCls: 'action_disable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, [['enabled']], true)
        });
    
        this.actionResetPassword = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reset Password'),
            disabled: true,
            handler: this.resetPasswordHandler,
            iconCls: 'action_password',
            scope: this
        });

        this.actionResetPin = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reset Pin'),
            disabled: true,
            handler: this.resetPinHandler,
            iconCls: 'action_password',
            hidden: ! (Tine.Tinebase.registry.get('config').userPin && Tine.Tinebase.registry.get('config').userPin.value),
            scope: this
        });

        this.actionUpdater.addActions([
            this.actionEnable,
            this.actionDisable,
            this.actionResetPassword,
            this.actionResetPin
        ]);
        
        Tine.Admin.user.GridPanel.superclass.initActions.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n.n_('User', 'Users', 1),    field: 'query',       operators: ['contains']}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },

    /**
     * on update after edit
     *
     * @param {String|Tine.Tinebase.data.Record} record
     */
    onUpdateRecord: function (record) {
        Tine.Admin.customfield.GridPanel.superclass.onUpdateRecord.apply(this, arguments);

        // reload app if current user changed
        const updatedRecord = Ext.util.JSON.decode(record);
        if (Tine.Tinebase.registry.get('currentAccount').accountId === updatedRecord.accountId) {
            Tine.Tinebase.common.confirmApplicationRestart();
        }
    },

    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actionEnable), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actionDisable), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actionResetPassword), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actionResetPin), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actionEnable,
            this.actionDisable,
            '-',
            this.actionResetPassword,
            this.actionResetPin
        ];
        
        return items;
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns columns
     * @private
     * @return Array
     */
    getColumns: function(){
        return [
            { header: this.app.i18n._('ID'), id: 'accountId', dataIndex: 'accountId', width: 50},
            { header: this.app.i18n._('Status'), id: 'accountStatus', dataIndex: 'accountStatus', hidden: this.isLdapBackend, width: 50, renderer: this.statusRenderer},
            { header: this.app.i18n._('Display name'), id: 'accountDisplayName', dataIndex: 'accountDisplayName', hidden: false},
            { header: this.app.i18n._('Login name'), id: 'accountLoginName', dataIndex: 'accountLoginName', width: 160, hidden: false},
            { header: this.app.i18n._('Last name'), id: 'accountLastName', dataIndex: 'accountLastName'},
            { header: this.app.i18n._('First name'), id: 'accountFirstName', dataIndex: 'accountFirstName'},
            { header: this.app.i18n._('E-mail'), id: 'accountEmailAddress', dataIndex: 'accountEmailAddress', width: 200, hidden: false},
            { header: this.app.i18n._('E-mail usage'), id: 'emailQuota', hidden: this.isEmailBackend, dataIndex: 'emailUser', renderer: this.emailQuotaRenderer, sortable: false},
            { header: this.app.i18n._('Filesystem usage'), id: 'fileQuota', dataIndex: 'filesystemSize', renderer: this.fileQuotaRenderer, hidden: false, sortable: false},
            { header: this.app.i18n._('OpenID'), id: 'openid', dataIndex: 'openid', width: 200},
            { header: this.app.i18n._('Last login at'), id: 'accountLastLogin', dataIndex: 'accountLastLogin', hidden: this.isLdapBackend, width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Last login from'), id: 'accountLastLoginfrom', hidden: this.isLdapBackend, dataIndex: 'accountLastLoginfrom'},
            { header: this.app.i18n._('Password Must Change'), id: 'password_must_change', dataIndex: 'password_must_change', width: 200, renderer: Tine.Tinebase.common.booleanRenderer, hidden: false},
            { header: this.app.i18n._('Password changed'), id: 'accountLastPasswordChange', dataIndex: 'accountLastPasswordChange', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer, hidden: false},
            { header: this.app.i18n._('Expires'), id: 'accountExpires', dataIndex: 'accountExpires', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer, hidden: false}
        ].concat(this.getModlogColumns());
    },
    
    enableDisableButtonHandler: function(status) {
        var accountIds = new Array();
        var selectedRows = this.grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            accountIds.push(selectedRows[i].id);
        }
        
        Ext.Ajax.request({
            url : 'index.php',
            method : 'post',
            params : {
                method : 'Admin.setAccountState',
                accountIds : accountIds,
                status: status
            },
            scope: this,
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        this.loadGridData({
                            removeStrategy: 'keepBuffered'
                        });
                    }
                }
            }
        });
    },
    
    /**
     * updates enable/disable actions
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     * @param {Boolean} isFilterSelect
     * @param {Array} requiredAccountStatus
     */
    enableDisableActionUpdater: function(action, grants, records, isFilterSelect, requiredAccountStatus) {
        let enabled = records.length > 0;
        if (requiredAccountStatus) {
            Ext.each(records, function (record) {
                enabled &= requiredAccountStatus.indexOf(record.get('accountStatus')) >= 0;// === requiredAccountStatus;
                return enabled;
            }, this);
        }
        
        action.setDisabled(!enabled);
    },
    
    /**
     * reset password
     * 
     * TODO add checkbox for must change pw
     * TODO add pw repeat (see user edit dialog)
     */
    resetPasswordHandler: function(_button, _event) {
        Ext.MessageBox.prompt(this.app.i18n._('Set new password'), this.app.i18n._('Please enter the new password:'), function(_button, _text) {
            if(_button == 'ok') {
                var accountObject = this.grid.getSelectionModel().getSelected().data;
                
                Ext.Ajax.request( {
                    params: {
                        method    : 'Admin.resetPassword',
                        account   : accountObject,
                        password  : _text,
                        mustChange: false
                    },
                    scope: this,
                    callback : function(_options, _success, _response) {
                        if(_success === true) {
                            var result = Ext.util.JSON.decode(_response.responseText);
                            if(result.success === true) {
                                this.grid.getStore().reload();
                            }
                        } else {
                            Tine.Tinebase.ExceptionHandler.handleRequestException(_response);
                        }
                    }
                });
            }
        }, this);
    },

    resetPinHandler: function(_button, _event) {
        Ext.MessageBox.prompt(this.app.i18n._('Set new pin'), this.app.i18n._('Please enter the new pin:'), function(_button, _text) {
            if(_button == 'ok') {
                var accountObject = this.grid.getSelectionModel().getSelected().data;

                Ext.Ajax.request( {
                    params: {
                        method    : 'Admin.resetPin',
                        account   : accountObject,
                        password  : _text
                    },
                    scope: this,
                    callback : function(_options, _success, _response) {
                        if(_success === true) {
                            var result = Ext.util.JSON.decode(_response.responseText);
                            if(result.success === true) {
                                this.grid.getStore().reload();
                            }
                        } else {
                            Tine.Tinebase.ExceptionHandler.handleRequestException(_response);
                        }
                    }
                });
            }
        }, this);
    },
    
    statusRenderer: function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch(_value) {
            case 'blocked':
                gridValue = "<img src='images/icon-set/icon_action_minus.svg' width='16' height='16'/>";
                break;

            case 'enabled':
                gridValue = "<img src='images/icon-set/icon_ok.svg' width='16' height='16'/>";
                break;

            case 'disabled':
                gridValue = "<img src='images/icon-set/icon_stop.svg' width='16' height='16'/>";
                break;

            case 'expired':
                gridValue = "<img src='images/icon-set/icon_time.svg' width='16' height='16'/>";
                break;

            default:
                gridValue = _value;
                break;
        }
        
        return gridValue;
    },

    emailQuotaRenderer: function(_value) {
        var quota = _value['emailMailQuota'] ? _value['emailMailQuota'] : 0,
            size = _value['emailMailSize'] ? _value['emailMailSize'] : 0;

        return Tine.widgets.grid.QuotaRenderer(size, quota, /*use SoftQuota*/ false);
    },

    fileQuotaRenderer: function(_value, _cellObject, _record) {
        var accountConfig = _record.get('xprops') ? _record.get('xprops') : null,
            quotaConfig = Tine.Tinebase.configManager.get('quota'),
            quota = accountConfig && accountConfig['personalFSQuota'] ? parseInt(accountConfig['personalFSQuota'])  : quotaConfig.totalByUserInMB * 1024 * 1024,
            size =  _record.get('filesystemSize') ? parseInt(_record.get('filesystemSize')) : 0;

        return Tine.widgets.grid.QuotaRenderer(size, quota, /*use SoftQuota*/ true);
    }
});
