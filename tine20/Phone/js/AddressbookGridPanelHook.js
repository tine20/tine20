/*
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Phone');

/**
 * @namespace   Tine.Phone
 * @class       Tine.Phone.AddressbookGridPanelHook
 * 
 * <p>Phone Addressbook Hook</p>
 * <p>
 * </p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Phone.AddressbookGridPanelHook = function(config) {
    Tine.log.info('initialising phone addressbook hooks');
    Ext.apply(this, config);
    
    // NOTE: due to the action updater this action is bound the the adb grid only!
    this.phoneMenu = new Ext.menu.Menu({
    });
        
    this.callContactAction = new Ext.Action({
        text: this.app.i18n._('Call contact'),
        iconCls: 'PhoneIconCls',
        disabled: true,
        scope: this,
        handler: this.onComposeEmail,
        actionUpdater: this.updateAction,
        menu: this.phoneMenu,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
            
    this.callContactBtn = Ext.apply(new Ext.SplitButton(this.callContactAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top',
        arrowAlign:'right'
    }),

    // register in toolbar + contextmenu
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ActionToolbar-leftbtngrp', this.callContactBtn, 30);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu', this.callContactAction, 80);
};

Ext.apply(Tine.Phone.AddressbookGridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Phone.Application
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
     * @property ContactGridPanel
     * @type Tine.Addressbook.ContactGridPanel
     * @private
     */
    ContactGridPanel: null,
    
    /**
     * @property phoneMenu
     * @type Ext.menu.Menu
     * @private
     */
    phoneMenu: null,
    
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

        Tine.Phone.dialPhoneNumber(number);
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
    },
    
    /**
     * updates call menu
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateAction: function(action, grants, records) {
        if (action.isHidden()) {
            return;
        }
        
        this.phoneMenu.removeAll();
        action.setDisabled(true);
            
        if (records.length == 1) {
            var contact = records[0];
            
            if (! contact) {
                return false;
            }
            
            if(!Ext.isEmpty(contact.data.tel_work)) {
                this.phoneMenu.add({
                   text: this.app.i18n._('Work') + ' ' + contact.data.tel_work + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_work'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_home)) {
                this.phoneMenu.add({
                   text: this.app.i18n._('Home') + ' ' + contact.data.tel_home + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_home'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell)) {
                this.phoneMenu.add({
                   text: this.app.i18n._('Cell') + ' ' + contact.data.tel_cell + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_cell'
                });
                action.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell_private)) {
                this.phoneMenu.add({
                   text: this.app.i18n._('Cell private') + ' ' + contact.data.tel_cell_private + '',
                   scope: this,
                   handler: this.onCallContact,
                   field: 'tel_cell_private'
                });
                action.setDisabled(false);
            }
        }
    }
});
