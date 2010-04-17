/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.ns('Tine.Admin.user');


/**
 * User grid panel
 */
Tine.Admin.user.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
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
        loadMask: true,
        autoExpandColumn: 'accountDisplayName'
    },
    
    initComponent: function() {
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        this.isLdapBackend = Tine.Tinebase.registry.get('accountBackend') == 'Ldap';
            
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
            text: this.app.i18n._('enable account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this),
            iconCls: 'action_enable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, 'disabled', true)
        });
    
        this.actionDisable = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('disable account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this),
            iconCls: 'action_disable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, 'enabled', true)
        });
    
        this.actionResetPassword = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('reset password'),
            disabled: true,
            handler: this.resetPasswordHandler,
            iconCls: 'action_password',
            scope: this
        });
        
        this.actionUpdater.addActions([
            this.actionEnable,
            this.actionDisable,
            this.actionResetPassword
        ]);
        
        this.supr().initActions.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('User'),    field: 'query',       operators: ['contains']}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
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
            this.actionResetPassword
        ];
        
        return items;
    },
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [
            { header: this.app.i18n._('ID'), id: 'accountId', dataIndex: 'accountId', hidden: true, width: 50},
            { header: this.app.i18n._('Status'), id: 'accountStatus', dataIndex: 'accountStatus', hidden: this.isLdapBackend, width: 50, renderer: this.statusRenderer},
            { header: this.app.i18n._('Displayname'), id: 'accountDisplayName', dataIndex: 'accountDisplayName'},
            { header: this.app.i18n._('Loginname'), id: 'accountLoginName', dataIndex: 'accountLoginName', width: 200},
            { header: this.app.i18n._('Last name'), id: 'accountLastName', dataIndex: 'accountLastName', hidden: true},
            { header: this.app.i18n._('First name'), id: 'accountFirstName', dataIndex: 'accountFirstName', hidden: true},
            { header: this.app.i18n._('Email'), id: 'accountEmailAddress', dataIndex: 'accountEmailAddress', width: 200},
            { header: this.app.i18n._('OpenID'), id: 'openid', dataIndex: 'openid', width: 200, hidden: true},
            { header: this.app.i18n._('Last login at'), id: 'accountLastLogin', dataIndex: 'accountLastLogin', hidden: this.isLdapBackend, width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Last login from'), id: 'accountLastLoginfrom', hidden: this.isLdapBackend, dataIndex: 'accountLastLoginfrom'},
            { header: this.app.i18n._('Password changed'), id: 'accountLastPasswordChange', dataIndex: 'accountLastPasswordChange', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Expires'), id: 'accountExpires', dataIndex: 'accountExpires', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer}
        ];
    },
    
    enableDisableButtonHandler: function(_button, _event) {
        var status = 'disabled';
        if(_button.id == 'Admin_User_Action_Enable') {
            status = 'enabled';
        }
        
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
                        this.grid.getStore().reload();
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
     */
    enableDisableActionUpdater: function(action, grants, records, requiredAccountStatus) {
        var enabled = true && records.length > 0;
        Ext.each(records, function(record){
            enabled &= record.get('accountStatus') === requiredAccountStatus;
            return enabled;
        }, this);
        
        action.setDisabled(!enabled);
    },
    
    /**
     * reset password
     * 
     * @todo add checkbox for must change pw
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
                        }
                    }
                });
            }
        }, this);
    },
    
    statusRenderer: function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
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
    }
});
