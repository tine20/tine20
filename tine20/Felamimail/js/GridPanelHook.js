/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridPanelHook
 * 
 * <p>Felamimail Gridpanel Hook</p>
 * <p>
 * </p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Felamimail.GridPanelHook = function(config) {
    Ext.apply(this, config);

    Tine.log.info('Tine.Felamimail.GridPanelHook::Initialising Felamimail ' + this.foreignAppName + ' hooks.');
    
    // NOTE: due to the action updater this action is bound the the adb grid only!
    this.composeMailAction = new Ext.Action({
        actionType: 'add',
        text: this.app.i18n._('Compose email'),
        iconCls: this.app.getIconCls(),
        disabled: true,
        scope: this,
        actionUpdater: this.updateAction,
        handler: this.onComposeEmail,
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
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-GridPanel-ActionToolbar-leftbtngrp', this.composeMailBtn, 30);
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-GridPanel-ContextMenu', this.composeMailAction, 80);
};

Ext.apply(Tine.Felamimail.GridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Felamimail.Application
     * @private
     */
    app: null,
    
    /**
     * foreign application name
     * @type String
     */
    foreignAppName: null,
    
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
     * @property gridPanel
     * @type Tine.Addressbook.gridPanel
     * @private
     */
    gridPanel: null,
    
    contactInRelation: false,
    relationType: null,
    
    /**
     * get addressbook contact grid panel
     */
    getGridPanel: function() {
        if (! this.gridPanel) {
            this.gridPanel = Tine.Tinebase.appMgr.get(this.foreignAppName).getMainScreen().getCenterPanel();
        }
        
        return this.gridPanel;
    },
    
    /**
     * return mail addresses of given contacts 
     * 
     * @param {Array} contacts
     * @param {String} prefered
     * @return {Array}
     */
    getMailAddresses: function(records) {
        var mailAddresses = [];
        
        Ext.each(records, function(record) {
            var contact = null;
            if (this.contactInRelation && record.get('relations')) {
                Ext.each(record.get('relations'), function(relation) {
                    if (relation.type === this.relationType) {
                       this.addMailFromContact(mailAddresses, relation.related_record);
                    }
                }, this);
            } else {
                this.addMailFromContact(mailAddresses, record);
            }
            
        }, this);
        
        Tine.log.debug('Tine.Felamimail.GridPanelHook::getMailAddresses - Got ' + mailAddresses.length + ' email addresses.');
        if (mailAddresses.length > 0) {
            Tine.log.debug(mailAddresses);
        }
        
        return mailAddresses;
    },
    
    /**
     * add mail address from contact (if available) and add it to mailAddresses array
     * 
     * @param {Array} mailAddresses
     * @param {Tine.Addressbook.Model.Contact|Object} contact
     */
    addMailFromContact: function(mailAddresses, contact) {
        if (! contact) {
            return;
        }
        if (! Ext.isFunction(contact.beginEdit)) {
            contact = new Tine.Addressbook.Model.Contact(contact);
        }
        
        var mailAddress = (contact.getPreferedEmail()) ? Tine.Felamimail.getEmailStringFromContact(contact) : null;
        
        if (mailAddress) {
            mailAddresses.push(mailAddress);
        }
    },
    
    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmail: function(btn) {
        var sm = this.getGridPanel().grid.getSelectionModel(),
            mailAddresses = sm.isFilterSelect ? null : this.getMailAddresses(this.getGridPanel().grid.getSelectionModel().getSelections());

        var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
            selectionFilter: sm.isFilterSelect ? Ext.encode(sm.getSelectionFilter()) : null,
            mailAddresses: mailAddresses ? Ext.encode(mailAddresses) : null
        });
    },

    
    /**
     * add to action updater the first time we render
     */
    onRender: function() {
        var actionUpdater = this.getGridPanel().actionUpdater,
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
        var sm = this.getGridPanel().grid.getSelectionModel();
        action.setDisabled(this.getMailAddresses(sm.getSelections()).length == 0);
    }
});
