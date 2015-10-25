/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.GenericContactGridPanelHook
 * 
 * Hook prototype for other applications to hook into the addressbook contact grid context menu<br>
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.Addressbook.GenericContactGridPanelHook = function(config) {
    Tine.log.info('initialising addressbook hook');
    Ext.apply(this, config);
    
    this.recordClass = Tine[this.app.name].Model[this.modelName];
    var text = this.recordClass.getMeta('recordName');
    
    this.addRecordAction = new Ext.Action({
        actionType: 'add',
        requiredGrant: 'readGrant',
        text: text,
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onUpdateRecord,
        allowMultiple: true,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.newRecordAction = new Ext.Action({
        actionType: 'new',
        requiredGrant: 'readGrant',
        text: text,
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onAddRecord,
        allowMultiple: true,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    Ext.ux.ItemRegistry.registerItem('Addressbook-Contact-GridPanel-ContextMenu-Add', this.addRecordAction, 100);
    Ext.ux.ItemRegistry.registerItem('Addressbook-Contact-GridPanel-ContextMenu-New', this.newRecordAction, 100);
};

Ext.apply(Tine.Addressbook.GenericContactGridPanelHook.prototype, {
    
    /**
     * The hooking application
     * 
     * @type Tine.Tinebase.Application
     */
    app: null,
    
    /**
     * The hooking model name of the
     * 
     * @type 
     */
    modelName: null,
    
    /**
     * The Addressbook Contact Grid to hook into
     * 
     * @type {Tine.Addressbook.ContactGridPanel}
     */
    contactGridPanel: null,
        
    /**
     * get addressbook contact grid panel
     */
    getContactGridPanel: function() {
        if (! this.contactGridPanel) {
            this.contactGridPanel = Tine.Tinebase.appMgr.get('Addressbook').getMainScreen().getCenterPanel();
        }
        return this.contactGridPanel;
    },

    /**
     * add selected contacts to a new record
     * 
     * @param {Button} btn 
     */
    onAddRecord: function(btn) {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel(),
            filter = this.getFilter(ms);
        
        if (!filter) {
            var addRelations = this.getSelectionsAsArray();
        } else {
            var addRelations = true;
        }

        cp.onEditInNewWindow.call(cp, 'add', null, [{
            ptype: 'addrelations_edit_dialog', selectionFilter: filter, addRelations: addRelations, callingApp: 'Addressbook', callingModel: 'Contact'
        }]);
    },
    
    /**
     * adds selected contacts to an existing record
     */
    onUpdateRecord: function() {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel(),
            filter = this.getFilter(ms),
            sm = this.getContactGridPanel().selectionModel;
            
        if (! filter) {
            var addRelations = this.getSelectionsAsArray(),
                count = this.getSelectionsAsArray().length;
        } else {
            var addRelations = true,
                count = sm.store.totalLength;
        }
        var cn = 'AddTo' + this.modelName + 'Panel';
        
        Tine[this.app.name][cn].openWindow({
            count: count, selectionFilter: filter, addRelations: addRelations, callingApp: 'Addressbook', callingModel: 'Contact'
        });
    },
    
    /**
     * returns the current filter if is filter selection
     * @param {Tine.widgets.MainScreen}
     * @return {Object}
     */
    getFilter: function(ms) {
        var sm = this.getContactGridPanel().selectionModel,
            filter = null;
        
        if (sm.isFilterSelect) {
            var filter = sm.getSelectionFilter();
        }
        return filter;
    },
    
    /**
     * returns selected contacts
     * @returns {Array}
     */
    getSelectionsAsArray: function() {
        var contacts = this.getContactGridPanel().grid.getSelectionModel().getSelections(),
            cont = [];
        
        Ext.each(contacts, function(contact) {
           if (contact.data) cont.push(contact.data);
        });
        
        return cont;
    },
    
    /**
     * add to action updater the first time we render
     */
    onRender: function() {
        var actionUpdater = this.getContactGridPanel().actionUpdater,
            registeredActions = actionUpdater.actions;

        if (registeredActions.indexOf(this.addRecordAction) < 0) {
            actionUpdater.addActions([this.addRecordAction]);
        }

        if (registeredActions.indexOf(this.newRecordAction) < 0) {
            actionUpdater.addActions([this.newRecordAction]);
        }
    }
});