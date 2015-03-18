/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.account');

/**
 * @namespace   Tine.widgets.account
 * @class       Tine.widgets.account.ChangeAccountAction
 * @extends     Ext.Action
 */
Tine.widgets.account.ChangeAccountAction = function(config) {
    config.text = config.text ? config.text : _('Change user account');
    config.iconCls = 'tinebase-accounttype-group';
    config.tooltip = _('Switch to another user\'s account');
    
    config.handler = this.handleClick.createDelegate(this);
    Ext.apply(this, config);
    
    Tine.widgets.account.ChangeAccountAction.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.account.ChangeAccountAction, Ext.Action, {
    
    loadMask: null,
    returnToOriginalUser: false,
    
    getFormItems: function() {
        var roleChangeAllowed = Tine.Tinebase.registry.get("config").roleChangeAllowed.value,
            currentAccountName = Tine.Tinebase.registry.get('currentAccount').accountLoginName,
            store = [];
            
        Tine.log.debug(roleChangeAllowed);
        
        Ext.each(roleChangeAllowed[currentAccountName], function(account) {
            store.push([account, account]);
        });
        
        this.accountSelect = new Ext.form.ComboBox({
            hideLabel: true,
            anchor: '100%',
            app: this.app,
            forceSelection: true,
            store: store,
            listeners: {
                scope: this,
                render: function(field) {
                    field.focus(false, 500);
                },
                select: function() {
                    this.okButton.setDisabled(false);
                    // TODO submit on select?
                    //this.onOk();
                }
            }
        });
        
        return [{
            xtype: 'label',
            text: _('Switch to this user account:')
        }, this.accountSelect
        ];
    },
    
    handleClick: function() {
        
        if (this.returnToOriginalUser) {
            this.onOk();
            return;
        }
        
        this.okButton = new Ext.Button({
            text: _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onOk,
            disabled: true,
            iconCls: 'action_saveAndClose'
        });
        
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 150,
            padding: '5px',
            modal: true,
            title: _('Select Account'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                padding: '5px',
                items: this.getFormItems(),
                buttons: [{
                    text: _('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, this.okButton]
            }]
        });
    },
    
    onCancel: function() {
        this.win.close();
    },
    
    onOk: function() {
        if (this.win) {
            this.loadMask = new Ext.LoadMask(this.win.getEl(), {msg: _('Changing user account ...')});
            this.loadMask.show();
        }
        
        var accountToSelect = this.returnToOriginalUser ? null : this.accountSelect.getValue();
        Tine.Tinebase.changeUserAccount(accountToSelect, this.onSuccess.createDelegate(this));
    },
    
    onSuccess: function() {
        Tine.Tinebase.common.reload({});
    }
});
