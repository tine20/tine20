/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AddressbookGridPanelHook
 * 
 * <p>Calendar Addressbook Hook</p>
 * <p>
 * </p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Calendar.AddressbookGridPanelHook = function(config) {
    Tine.log.info('initialising calendar addressbook hooks');
    Ext.apply(this, config);
    
    this.addEventAction = new Ext.Action({
        actionType: 'add',
        requiredGrant: 'readGrant',
        text: this.app.i18n._('Event'),
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onUpdateEvent,
        allowMultiple: true,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.newEventAction = new Ext.Action({
        actionType: 'new',
        requiredGrant: 'readGrant',
        text: this.app.i18n._('Event'),
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onAddEvent,
        allowMultiple: true,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu-Add', this.addEventAction, 100);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu-New', this.newEventAction, 100);
    
};

Ext.apply(Tine.Calendar.AddressbookGridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Calendar.Application
     * @private
     */
    app: null,
       
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
     * add selected contacts to a new event
     * 
     * @param {Button} btn 
     */
    onAddEvent: function(btn) {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel(),
            cont = this.getSelectionsAsArray(),
            attendee = []; 
        
        attendee.push(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
            user_type: 'user',
            user_id: Tine.Tinebase.registry.get('currentAccount'),
            status: 'ACCEPTED'
        }));
        
        Ext.each(cont, function(contact) {
        	attendee.push(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
        		user_id: contact
        	}));
        });
        
        cp.onEditInNewWindow.call(cp, 'add', {attendee: attendee});
    },
    
    /**
     * adds selected contacts to an existing event
     */
    onUpdateEvent: function() {
        var cont = this.getSelectionsAsArray();
        
        var window = Tine.Calendar.AddToEventPanel.openWindow({attendee: cont});        
    },
    
    /**
     * returns selected contacts
     * @returns {Array}
     */
    getSelectionsAsArray: function() {
        var contacts = this.getContactGridPanel().grid.getSelectionModel().getSelections(),
            cont = [];
            
        Ext.each(contacts, function(contact) {
           if(contact.data) cont.push(contact.data);
        });
        
        return cont;
    },
    
    /**
     * add to action updater the first time we render
     */
    onRender: function() {
        var actionUpdater = this.getContactGridPanel().actionUpdater,
            registeredActions = actionUpdater.actions;
            
        if (registeredActions.indexOf(this.addEventAction) < 0) {
            actionUpdater.addActions([this.addEventAction]);
        }
        
        if (registeredActions.indexOf(this.newEventAction) < 0) {
        	actionUpdater.addActions([this.newEventAction]);
        }        
    }

});
