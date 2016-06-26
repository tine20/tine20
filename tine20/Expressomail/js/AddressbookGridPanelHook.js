/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.AddressbookGridPanelHook
 * 
 * <p>Expressomail Addressbook Hook</p>
 * <p>
 * </p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Expressomail.AddressbookGridPanelHook = function(config) {
    Tine.log.info('initialising expressomail addressbook hooks');
    Ext.apply(this, config);
    
    // NOTE: due to the action updater this action is bound the the adb grid only!
    this.composeMailAction = new Ext.Action({
        actionType: 'add',
        text: this.app.i18n._('Compose email'),
        iconCls: this.app.getIconCls(),
        disabled: true,
        scope: this,
        handler: this.onComposeEmail,
        actionUpdater: this.updateAction,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.composeMailBtn = Ext.apply(new Ext.Button(this.composeMailAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    });
    
    // register in toolbar + contextmenu
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ActionToolbar-leftbtngrp', this.composeMailBtn, 30);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu', this.composeMailAction, 80);
};

Ext.apply(Tine.Expressomail.AddressbookGridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Expressomail.Application
     * @private
     */
    app: null,
    
    /**
     * @property composeMailAction
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailAction: null,
    
    /**
     * @property composeMailBtn
     * @type Ext.Button
     * @private
     */
    composeMailBtn: null,
    
    /**
     * @property ContactGridPanel
     * @type Tine.Addressbook.ContactGridPanel
     * @private
     */
    ContactGridPanel: null,
    
    /**
     * get addressbook contact grid panel
     */
    getContactGridPanel: function() {
        if (! this.ContactGridPanel) {
            this.ContactGridPanel = Tine.Tinebase.appMgr.get('Addressbook').getMainScreen().getCenterPanel();
        }
        
        return this.ContactGridPanel;
    },
    
    /**
     * return mail addresses of given contacts 
     * 
     * @param {Array} contacts
     * @param {String} prefered
     * @return {Array}
     */
    getMailAddresses: function(contacts, prefered) {
        var mailAddresses = [];
        
        Ext.each(contacts, function(contact) {
            if (! Ext.isFunction(contact.beginEdit)) {
                contact = new Tine.Addressbook.Model.Contact(contact);
            }
            
            var mailAddress = contact.getPreferedEmail();
            if (mailAddress) {
                mailAddresses.push(mailAddress);
            }
        }, this);
        
        return mailAddresses;
    },
    
    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmail: function(btn) {
        var sm = this.getContactGridPanel().grid.getSelectionModel(),
            mailAddresses = sm.isFilterSelect ? sm.getSelectionFilter() : this.getMailAddresses(this.getContactGridPanel().grid.getSelectionModel().getSelections()),
            selectCount = sm.getCount();
        
        if (selectCount > 50){
            
            mailAddresses = [];
            Ext.MessageBox.show({
                title: '',
                msg: i18n._('Number of contacts exceeds the maximum of 50 permitted.'),
                buttons: Ext.MessageBox.OK,
                scope: this,
                icon: Ext.MessageBox.ERROR
            });
            
        } else{
        
            var defaults = Tine.Expressomail.Model.Message.getDefaultData();
            defaults.body = Tine.Expressomail.getSignature();
            defaults.to = mailAddresses;

            var record = new Tine.Expressomail.Model.Message(defaults, 0);
            var popupWindow = Tine.Expressomail.MessageEditDialog.openWindow({
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                record: record
            });
        }
    },

    
    /**
     * add to action updater the first time we render
     */
    onRender: function() {
        var actionUpdater = this.getContactGridPanel().actionUpdater,
            registeredActions = actionUpdater.actions;
            
        if (registeredActions.indexOf(this.composeMailAction) < 0) {
            actionUpdater.addActions([this.composeMailAction]);
        }
    },
    
    /**
     * updates compose button
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateAction: function(action, grants, records) {
        action.setDisabled(this.getMailAddresses(this.getContactGridPanel().grid.getSelectionModel().getSelections()).length == 0);
    }
});
