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
          }),
            this.composeMailActionMass = new Ext.Action({
                actionType: 'add',
                text: this.app.i18n._('Mass Mailing'),
                iconCls: this.app.getIconCls(),
                disabled: true,
                scope: this,
                actionUpdater: this.updateAction,
                handler: this.onComposeEmailMass,
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
     * @property composeMailActionTO
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionTO: null,
    
    /**
     * @property composeMailActionCC
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionCC: null,
    
    /**
     * @property composeMailActionBCC
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionBCC: null,

    /**
     * @property composeMailActionMass
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    composeMailActionMass: null,

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

    // TODO move to a messageData object
    subject: null,
    body: null,
    massMailingFlag: false,

    subjectField: null,
    subjectFn: null,
    bodyFn: null,
    massMailingFlagFn: null,

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
     * @return {Array}
     */
    getMailAddresses: function(records) {
        var mailAddresses = [];
        
        Ext.each(records, function(record) {
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
        
        if (mailAddresses.length > 0) {
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

        // no exact matches are necessary - use the same regex as in \Tinebase_Mail::EMAIL_ADDRESS_CONTAINED_REGEXP
        // TODO find a good generic place for this const
        const emailRegEx = /([a-z0-9_\+-\.&]+@[a-z0-9-\.]+\.[a-z]{2,63})/i;
        
        if (!contact.get("members")) {
            var mailAddress = (contact.getPreferredEmail()) ? Tine.Felamimail.getEmailStringFromContact(contact) : null;
            if (mailAddress && mailAddress.match(emailRegEx))
                mailAddresses.push(mailAddress);
        } else {
            var emails = contact.get("emails");
            if (emails) {
                Ext.each(emails.split(","), function (mail) {
                    if (mail.match(emailRegEx)) {
                        mailAddresses.push(mail);
                    }
                });
            }
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
                mailAddresses = sm.isFilterSelect
                    ? null
                    : this.getMailAddresses(this.getGridPanel().grid.getSelectionModel().getSelections());
        } else {
            var sm = null,
                mailAddresses = this.mailAddresses;
        }

        var record = new Tine.Felamimail.Model.Message({
            subject: (this.subject) ? this.subject : '',
            body: this.body,
            massMailingFlag: this.massMailingFlag
        }, 0);

        if (to == "CC") {
            record.set('cc', mailAddresses);
        } else if (to == "BCC") {
            record.set('bcc', mailAddresses);
        } else {
            record.set('to', mailAddresses);
        }

        if (to == 'mass') {
            record.set('massMailingFlag', true);
        }

        var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
            selectionFilter: sm && sm.isFilterSelect ? Ext.encode({
                to: to,
                filter: sm.getSelectionFilter()
            }) : null,
            record: record
        });
    },

    /**
     * compose an email to selected contacts
     *
     * @param {Button} btn
     */
    onComposeEmailTO: function (btn) {
        this.onComposeEmail(btn, "TO");
    },

    /**
     * compose an email to selected contacts
     *
     * @param {Button} btn
     */
    onComposeEmailCC: function (btn) {
        this.onComposeEmail(btn, "CC");
    },

    /**
     * compose an email to selected contacts
     *
     * @param {Button} btn
     */
    onComposeEmailBCC: function (btn) {
        this.onComposeEmail(btn, "BCC");
    },

    /**
     * compose mass maiiling to selected contacts
     *
     * @param {Button} btn
     */
    onComposeEmailMass: function (btn) {
        this.onComposeEmail(btn, "mass");
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
        } else if (Ext.isFunction(this.subjectFn)) {
            this.subject = this.subjectFn(records[0]);
        }
        if (Ext.isFunction(this.bodyFn)) {
            this.body = this.bodyFn(records[0]);
        }
        if (Ext.isFunction(this.massMailingFlagFn)) {
            this.massMailingFlag = this.massMailingFlagFn(records[0]);
        }
    }
});
