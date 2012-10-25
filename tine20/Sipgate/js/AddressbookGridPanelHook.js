/**
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AddressbookGridPanelHook.js 22 2011-05-01 21:00:08Z alex $
 *
 */
 
Ext.ns('Tine.Sipgate');

/**
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.AddressbookGridPanelHook
 * 
 * <p>Sipgate Addressbook Hook</p>
 * 
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Sipgate.AddressbookGridPanelHook = function(config) {

    Tine.log.info('initialising sipgate addressbook hooks');
    
    Ext.apply(this, config);

    this.callMenu = new Ext.menu.Menu({
    });

    this.smsMenu = new Ext.menu.Menu({
    });
    
    this.callContactAction = new Ext.Action({
        text: this.app.i18n._('Call contact'),
        iconCls: 'SipgateIconCls',
        disabled: true,
        scope: this,
        actionUpdater: this.updateCallAction,
        menu: this.callMenu,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
            
    this.callContactBtn = Ext.apply(new Ext.Button(this.callContactAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }),


    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ActionToolbar-leftbtngrp', this.callContactBtn, 30);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu', this.callContactAction, 130);
     
    this.composeSmsAction = new Ext.Action({
        text: this.app.i18n._('Compose SMS'),
        iconCls: 'SmsIconCls',
        disabled: true,
        scope: this,
        actionUpdater: this.updateSmsAction,
        menu: this.smsMenu,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.composeSmsBtn = Ext.apply(new Ext.Button(this.composeSmsAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }),    
    
    // register in toolbar + contextmenu
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ActionToolbar-leftbtngrp', this.composeSmsBtn, 30);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu', this.composeSmsAction, 140);
    
};

Ext.apply(Tine.Sipgate.AddressbookGridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Sipgate.Application
     * @private
     */
    app: null,
    
    /**
     * @property callContactAction
     * @type Ext.Action
     * @private
     */
    callContactAction: null,
    
    /**
     * @property callContactBtn
     * @type Ext.Button
     * @private
     */
    callContactBtn: null,
 
    /**
     * @property composeSmsAction
     * @type Ext.Action
     * @private
     */
    composeSmsAction: null,
    
    /**
     * @property composeSmsBtn
     * @type Ext.Button
     * @private
     */
    composeSmsBtn: null,
    
    /**
     * @property ContactGridPanel
     * @type Tine.Addressbook.ContactGridPanel
     * @private
     */
    ContactGridPanel: null,
    
    /**
     * @property callMenu
     * @type Ext.menu.Menu
     * @private
     */
    callMenu: null,

    /**
     * @property smsMenu
     * @type Ext.menu.Menu
     * @private
     */
    smsMenu: null,
    
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
     * calls a contact
     * @param {Button} btn 
     */
    onCallContact: function(btn) {
        var number;

        var contact = this.getContactGridPanel().grid.getSelectionModel().getSelected();
        
        if (! contact) {
            return;
        }
        
        if (!Ext.isEmpty(contact.get(btn.field))) {
            number = contact.get(btn.field);
        } else if(!Ext.isEmpty(contact.data.tel_work)) {
            number = contact.data.tel_work;
        } else if (!Ext.isEmpty(contact.data.tel_cell)) {
            number = contact.data.tel_cell;
        } else if (!Ext.isEmpty(contact.data.tel_cell_private)) {
            number = contact.data.tel_cell_private;
        } else if (!Ext.isEmpty(contact.data.tel_home)) {
            number = contact.data.tel_home;
        }
        var lineId = Tine.Sipgate.registry.get('preferences').get('phoneId');
        if(lineId) {
            Tine.Sipgate.lineBackend.dialNumber(lineId, number, contact);
        } else {
            Tine.Sipgate.DialNumberDialog.openWindow({number: number, contact: contact});
        }
    },

    /**
     * compose an SMS to selected contacts
     * 
     * @param {Button} btn 
     */
    onComposeSms: function(btn) {
        var number;

        var contact = this.getContactGridPanel().grid.getSelectionModel().getSelected();
        
        if (! contact) {
            return;
        }
        
        if (!Ext.isEmpty(contact.get(btn.field))) {
            number = contact.get(btn.field);
        } else if(!Ext.isEmpty(contact.data.tel_work)) {
            number = contact.data.tel_work;
        } else if (!Ext.isEmpty(contact.data.tel_cell)) {
            number = contact.data.tel_cell;
        } else if (!Ext.isEmpty(contact.data.tel_cell_private)) {
            number = contact.data.tel_cell_private;
        } else if (!Ext.isEmpty(contact.data.tel_home)) {
            number = contact.data.tel_home;
        }
        
        var popUpWindow = Tine.Sipgate.SmsEditDialog.openWindow({
            contact: contact,
            number: number
        });
        
    },    
    
    /**
     * add to action updater the first time we render
     */
    onRender: function() {
        var actionUpdater = this.getContactGridPanel().actionUpdater,
            registeredActions = actionUpdater.actions;
            
        if (registeredActions.indexOf(this.callContactAction) < 0) {
            actionUpdater.addActions([this.callContactAction]);
        } 
        
        if (registeredActions.indexOf(this.composeSmsAction) < 0) {
            actionUpdater.addActions([this.composeSmsAction]);
        }
    },
    
    /**
     * updates call menu
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateCallAction: function(action, grants, records) {

        if (action.isHidden()) {
            return;
        }
        
        this.callMenu.removeAll();
        action.setDisabled(true);

        if (records.length == 1) {
            var contact = records[0];
            
            if (! contact) {
                return false;
            }
            
            if(!Ext.isEmpty(contact.data.tel_work)) {
                this.callMenu.add({
                   text: this.app.i18n._('Work') + ' ' + contact.data.tel_work + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_work',
                   iconCls: 'SipgateIconCls'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_home)) {
                this.callMenu.add({
                   text: this.app.i18n._('Home') + ' ' + contact.data.tel_home + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_home',
                   iconCls: 'SipgateIconCls'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell)) {
                this.callMenu.add({
                   text: this.app.i18n._('Cell') + ' ' + contact.data.tel_cell + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_cell',
                   iconCls: 'SmsIconCls'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell_private)) {
                this.callMenu.add({
                   text: this.app.i18n._('Cell private') + ' ' + contact.data.tel_cell_private + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_cell_private',
                   iconCls: 'SmsIconCls'
                });
                action.setDisabled(false);
            }
        }
    },
    
    /**
     * updates sms menu
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateSmsAction: function(action, grants, records) {
        if (action.isHidden()) {
            return;
        }
        
        this.smsMenu.removeAll();
        action.setDisabled(true);

        if (records.length == 1) {
            var contact = records[0];
            
            if (! contact) {
                return false;
            }
            
            if(!Ext.isEmpty(contact.data.tel_cell)) {
                this.smsMenu.add({
                   text: this.app.i18n._('Cell') + ' ' + contact.data.tel_cell + '',
                   scope: this,
                   handler: this.onComposeSms,
                   field: 'tel_cell',
                   iconCls: 'SmsIconCls'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell_private)) {
                this.smsMenu.add({
                   text: this.app.i18n._('Cell private') + ' ' + contact.data.tel_cell_private + '',
                   scope: this,
                   handler: this.onComposeSms,
                   field: 'tel_cell_private',
                   iconCls: 'SmsIconCls'
                });
                action.setDisabled(false);
            }
        }
    }    
});
