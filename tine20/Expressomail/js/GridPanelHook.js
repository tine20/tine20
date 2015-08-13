/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.GridPanelHook
 * 
 * <p>Expressomail Gridpanel Hook</p>
 * <p>
 * </p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Expressomail.GridPanelHook = function(config) {
    Ext.apply(this, config);

    Tine.log.info('Tine.Expressomail.GridPanelHook::Initialising Expressomail ' + this.foreignAppName + ' hooks.');
    
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
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.recordTypeName + '-GridPanel-ActionToolbar-leftbtngrp', this.composeMailBtn, 30);
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.recordTypeName + '-GridPanel-ContextMenu', this.composeMailAction, 80);
    
    if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1' && Tine.Tinebase.registry.get('preferences').get('windowtype')=='Ext') {
        this.composeMailAction_encrypted = new Ext.Action({
            actionType: 'add',
            text: this.app.i18n._('Compose email (encrypted)'),
            iconCls: this.app.getIconCls(),
            disabled: true,
            scope: this,
            encrypted: true,
            actionUpdater: this.updateAction,
            handler: this.onComposeEmail,
            listeners: {
                scope: this,
                render: this.onRender
            }
        });

        this.composeMailBtn_encrypted = Ext.apply(new Ext.Button(this.composeMailAction_encrypted), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.recordTypeName + '-GridPanel-ActionToolbar-leftbtngrp', this.composeMailBtn_encrypted, 30);
        Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.recordTypeName + '-GridPanel-ContextMenu', this.composeMailAction_encrypted, 80);
    }
};

Ext.apply(Tine.Expressomail.GridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Expressomail.Application
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
     * set to true if a email (or multiple emails) happen to be list
     * @property listEmails
     * @private
     */
    listEmails: false,

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
            if ((record.data.type != 'list') && (record.data.type != 'group')){
                if (this.contactInRelation && record.get('relations')) {
                    Ext.each(record.get('relations'), function(relation) {
                        if (relation.type === this.relationType) {
                        this.addMailFromAddressbook(mailAddresses, relation.related_record);
                        }
                    }, this);
                } else {
                    this.addMailFromAddressbook(mailAddresses, record);
                }
            }else {
                this.listEmails = true;
                mailAddresses.push(record.data.id);
            }
        }, this);

        Tine.log.debug('Tine.Expressomail.GridPanelHook::getMailAddresses - Got ' + mailAddresses.length + ' email addresses.');
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
    addMailFromAddressbook: function(mailAddresses, contact) {
        if (! contact) {
            return;
        }
        if (! Ext.isFunction(contact.beginEdit)) {
            contact = new Tine.Addressbook.Model.Contact(contact);
        }
        
        if (!contact.get("members")) {
            mailAddresses.push(Tine.Expressomail.getEmailStringFromContact(contact));
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
        if (this.listEmails){
            var strText = 'Fetching emails from the list';
            if (mailAddresses.length > 1){
                strText = 'Fetching emails from the lists';
            }
            Ext.Msg.wait(this.app.i18n.gettext(strText), this.app.i18n.gettext('Please wait'));
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Addressbook.searchListContactsFormated',
                    listId: mailAddresses
                },
                success: function (response, request) {
                    var recordData = Ext.util.JSON.decode(response.responseText);
                    Ext.Msg.hide();
                    this.openEmailWindow(btn, sm, recordData);
                },
                failure: function (response, request) {
                    Ext.MessageBox.alert(this.app.i18n.gettext('Error'),
                    this.app.i18n.gettext('A error happened while fetching the List emails'));
                }
            });
        }else {
            this.openEmailWindow(btn, sm, mailAddresses);
        }
    },

    /**
     * Open the edit email window
     */
    openEmailWindow: function(btn, sm, mailAddresses){
        var popupWindow = Tine.Expressomail.MessageEditDialog.openWindow({
            encrypted: btn.encrypted,
            mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
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
            
        if (registeredActions.indexOf(this.composeMailAction_encrypted) < 0) {
            actionUpdater.addActions([this.composeMailAction_encrypted]);
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
