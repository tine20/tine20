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
      handler: this.onComposeEmailTO,
      allowMultiple: true,
      listeners: {
        scope: this,
        render: this.onRender
      },
      menu: {
        items: [
          this.composeMailActionTO = new Ext.Action({
              actionType: 'add',
              text: this.app.i18n._('To'),
              iconCls: this.app.getIconCls(),
              disabled: true,
              scope: this,
              actionUpdater: this.updateAction,
              handler: this.onComposeEmailTO,
              listeners: {
                  scope: this,
                  render: this.onRender
              }
          }),
          this.composeMailActionCC = new Ext.Action({
              actionType: 'add',
              text: this.app.i18n._('CC'),
              iconCls: this.app.getIconCls(),
              disabled: true,
              scope: this,
              actionUpdater: this.updateAction,
              handler: this.onComposeEmailCC,
              listeners: {
                  scope: this,
                  render: this.onRender
              }
          }),
          this.composeMailActionBCC = new Ext.Action({
              actionType: 'add',
              text: this.app.i18n._('BCC'),
              iconCls: this.app.getIconCls(),
              disabled: true,
              scope: this,
              actionUpdater: this.updateAction,
              handler: this.onComposeEmailBCC,
              listeners: {
                  scope: this,
                  render: this.onRender
              }
          })
        ]
      }
    });
    this.composeMailBtn = Ext.apply(new Ext.Button(this.composeMailActionTO), {
        text: this.app.i18n._('Compose email'),
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    });
    
    // register in toolbar + contextmenu
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.modelName + '-GridPanel-ActionToolbar-leftbtngrp', this.composeMailBtn, 30);
    Ext.ux.ItemRegistry.registerItem(this.foreignAppName + '-' + this.modelName + '-GridPanel-ContextMenu', this.composeMailAction, 80);
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
    composeMailActionTO: null,
    
    /**
     * @property composeMailAction
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionCC: null,
    
    /**
     * @property composeMailAction
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionBCC: null,
    
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
    addMailFromRecord: null,
    mailAddresses: null,
    subject: null,
    subjectField: null,
    
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
                       this.addMailFromAddressBook(mailAddresses, relation.related_record);
                    }
                }, this);
            } else if (Ext.isFunction(this.addMailFromRecord)){
                // addMailFromRecord can be defined in config
                this.addMailFromRecord(mailAddresses, record);
            } else {
                this.addMailFromAddressBook(mailAddresses, record);
            }
            
        }, this);
        
        Tine.log.debug('Tine.Felamimail.GridPanelHook::getMailAddresses - Got ' + mailAddresses.length + ' email addresses.');
        if (mailAddresses.length > 0) {
            Tine.log.debug(mailAddresses);
            this.mailAddresses = mailAddresses;
        }
        
        return mailAddresses.unique();
    },
    
    /**
     * add mail address from addressbook (if available) and add it to mailAddresses array
     * 
     * @param {Array} mailAddresses
     * @param {Tine.Addressbook.Model.Contact|Object} contact
     */
    addMailFromAddressBook: function(mailAddresses, contact) {
        if (! contact) {
            return;
        }
        if (! Ext.isFunction(contact.beginEdit)) {
            contact = new Tine.Addressbook.Model.Contact(contact);
        }
        
        if (!contact.get("members")) {
            var mailAddress = (contact.getPreferedEmail()) ? Tine.Felamimail.getEmailStringFromContact(contact) : null;
            if (mailAddress)
                mailAddresses.push(mailAddress);
        } else {
            Ext.each(contact.get("emails").split(","), function(mail) {
                mailAddresses.push(mail);
            });
        }
    },
    
    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmail: function(btn,to) {
        if (this.getGridPanel().grid) {
            var sm = this.getGridPanel().grid.getSelectionModel(),
                mailAddresses = sm.isFilterSelect ? null : this.getMailAddresses(this.getGridPanel().grid.getSelectionModel().getSelections());
        } else {
            var sm = null,
                mailAddresses = this.mailAddresses;
        }

        if( to == "CC")
        {
            var record = new Tine.Felamimail.Model.Message({
                subject: (this.subject) ? this.subject : '',
                cc: mailAddresses
            }, 0);
        }
        else if( to == "BCC")
        {
            var record = new Tine.Felamimail.Model.Message({
                subject: (this.subject) ? this.subject : '',
                bcc: mailAddresses
            }, 0);
        }
        else
        {
            var record = new Tine.Felamimail.Model.Message({
                subject: (this.subject) ? this.subject : '',
                to: mailAddresses
            }, 0);
        }
        var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
            selectionFilter: sm && sm.isFilterSelect ? Ext.encode(sm.getSelectionFilter()) : null,
            record: record
        });
    },
    
    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmailTO: function(btn) {
        this.onComposeEmail( btn, "TO" );
    },

    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmailCC: function(btn) {
        this.onComposeEmail( btn, "CC" );
    },

    /**
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeEmailBCC: function(btn) {
        this.onComposeEmail( btn, "BCC" );
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
        if (registeredActions.indexOf(this.composeMailActionTO) < 0) {
            actionUpdater.addActions([this.composeMailActionTO]);
        }
        if (registeredActions.indexOf(this.composeMailActionCC) < 0) {
            actionUpdater.addActions([this.composeMailActionCC]);
        }
        if (registeredActions.indexOf(this.composeMailActionBCC) < 0) {
            actionUpdater.addActions([this.composeMailActionBCC]);
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
        this.mailAddresses = [];
        action.setDisabled(this.getMailAddresses(records).length == 0);
        if (this.subjectField && records.length > 0) {
            this.subject = records[0].get(this.subjectField);
        }
    }
});
