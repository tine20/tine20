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
    records: [],

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
     * @param records
     */
    getMailAddresses: async function(records = []) {
        const mailAddresses = [];
        const promises = [];
        
        _.each(records, (record) => {
            if (this.contactInRelation && record.get('relations')) {
                Ext.each(record.get('relations'), function(relation) {
                    if (relation.type === this.relationType) {
                        promises.push(this.addRecipientTokenFromContacts(mailAddresses, [relation.related_record]));
                    }
                }, this);
            } else if (Ext.isFunction(this.addMailFromRecord)) {
                // addMailFromRecord can be defined in config
                promises.push(this.addMailFromRecord(mailAddresses, record));
            } else {
                promises.push(this.addRecipientTokenFromContacts(mailAddresses, [record]));
            }
        }, this);
        
        await Promise.allSettled(promises).then(() => {
            if (mailAddresses.length > 0) {
                this.mailAddresses = mailAddresses;
            }
        })
        
        return this.mailAddresses;
    },
    
    /**
     * add mail address from addressbook (if available) and add it to mailAddresses array
     *
     * @param {Array} mailAddresses
     * @param contacts
     */
    addRecipientTokenFromContacts: function (mailAddresses, contacts) {
        return new Promise(async (resolve, reject) => {
            if (!contacts || contacts.length === 0) {
                resolve();
            }
    
            // no exact matches are necessary - use the same regex as in \Tinebase_Mail::EMAIL_ADDRESS_CONTAINED_REGEXP
            const emailRegEx = /([a-z0-9_\+-\.&]+@[a-z0-9-\.]+\.[a-z]{2,63})/i;
    
            await _.reduce(contacts, async (prev, contact) => {
                return prev.then(async () => {
                    contact = Ext.isFunction(contact.beginEdit) ? contact : new Tine.Addressbook.Model.Contact(contact);
                    const memberIds = contact.get("members");
    
                    if (memberIds && memberIds.length > 0) {
                        let {results: memberContacts} = await Tine.Addressbook.searchContacts([{
                            field: 'id', operator: 'in', value: _.compact(_.uniq(memberIds))
                        }]);
        
                        await this.addRecipientTokenFromContacts(mailAddresses, memberContacts);
                    } else {
                        const email = typeof contact.getPreferredEmail == 'function' ? contact.getPreferredEmail() : null;
                        const emailType = contact.get('email') === email ? 'email' : contact.get('email_home') === email ? 'email_home' : 'email';
        
                        if (email && email.match(emailRegEx)) {
                            const existEmail = _.find(mailAddresses, {email: email});
                            if (!existEmail) {
                                let token = {
                                    'email': email,
                                    'email_type': emailType,
                                    'type': contact.get('type'),
                                    'n_fileas': contact.get('n_fileas'),
                                    'name': contact.get('n_fn'),
                                    'record_id': contact.get('id')
                                };
                                mailAddresses.push(token);
                            }
                        }
                    }
                })
            }, Promise.resolve());
    
            resolve();
        });
    },
    
    /**
     * compose an email to selected contacts
     *
     * @param {Button} btn
     * @param to
     */
    onComposeEmail: async function (btn, to) {
        const sm = this.getGridPanel().grid ? this.getGridPanel().grid.getSelectionModel() : null;
        const records = (sm && sm.isFilterSelect) ? sm.getSelections(): this.records;
        
        var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
            contentPanelConstructorInterceptor: async (config) => {
                const waitingText = this.app.i18n._('Loading Recipients...');
                const mask = await config.setWaitText(waitingText);
                const mailAddresses = await this.getMailAddresses(records);
                
                const record = new Tine.Felamimail.Model.Message({
                    subject: (this.subject) ? this.subject : '',
                    body: this.body,
                    massMailingFlag: this.massMailingFlag
                }, 0);
    
                switch (to) {
                    case "TO":
                        record.set('to', mailAddresses);
                        break;
                    case "CC":
                        record.set('cc', mailAddresses);
                        break;
                    case "BCC":
                        record.set('bcc', mailAddresses);
                        break;
                    case "mass":
                        to = 'bcc';
                        record.set('massMailingFlag', true);
                        record.set('bcc', mailAddresses);
                        break;
                }
                config.record = record;
                config.listeners = {
                    single: true,
                    load: function() {
                        mask.hide();
                    }
                };
            },
            selectionFilter: sm && sm.isFilterSelect ? Ext.encode({
                to: to,
                filter: sm.getSelectionFilter()
            }) : null,
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
        this.records = records.length ? records :   
            action.initialConfig?.selections?.length ? action.initialConfig.selections : [];
    
        if (action.text ===  this.app.i18n._('Compose email') && this.records.length > 0){
            let hasEmailAddress = false;
            _.each(this.records, (record) => {
                if ((typeof record.getPreferredEmail == 'function' && record.getPreferredEmail() !== '') ||
                    (record.get('members') && record.get('members').length > 0) ||
                    (record.get('attendee') && record.get('attendee').length > 0)
                ){
                    hasEmailAddress = true;
                }
            })
            this.composeMailAction.setDisabled(!hasEmailAddress);
        }

        if (!action.text && action.initialConfig?.selectionModel) {
            const isComposeItem = _.find(['To', 'CC', 'BCC', 'Mass Mailing'], (name) => {
                return action.initialConfig.text === this.app.i18n._(name);
            });
    
            if (isComposeItem) {
                const isFilterSelect = action.initialConfig.selectionModel.isFilterSelect;
                action.setDisabled(isFilterSelect ? action.initialConfig.text !== this.app.i18n._('Mass Mailing') : false);
            }
        }

        
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
