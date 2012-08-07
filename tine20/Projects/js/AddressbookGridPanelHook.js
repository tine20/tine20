/*
 * Tine 2.0
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.AddressbookGridPanelHook
 * 
 * <p>Projects Addressbook Hook</p>
 * <p>
 * </p>
 * 
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @constructor
 */
Tine.Projects.AddressbookGridPanelHook = function(config) {
    Tine.log.info('initialising projects addressbook hooks');
    Ext.apply(this, config);

    var text = this.app.i18n.n_hidden(Tine.Projects.Model.Project.prototype.recordName, Tine.Projects.Model.Project.prototype.recordsName, 1);
    
    this.addProjectAction = new Ext.Action({
        requiredGrant: 'readGrant',
        text: text,
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onUpdateProject,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.newProjectAction = new Ext.Action({
        requiredGrant: 'readGrant',
        text: text,
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onAddProject,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu-Add', this.addProjectAction, 90);
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu-New', this.newProjectAction, 90);
};

Ext.apply(Tine.Projects.AddressbookGridPanelHook.prototype, {
    
    /**
     * @property app
     * @type Tine.Projects.Application
     * @private
     */
    app: null,
    
    /**
     * @property handleProjectsAction
     * @type Tine.widgets.ActionUpdater
     * @private
     */
    handleProjectsAction: null,
    
    /**
     * @property ContactGridPanel
     * @type Tine.Addressbook.ContactGridPanel
     * @private
     */
    ContactGridPanel: null,
    
    projectsMenu: null,
    
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
     * adds contacts to a new project
     */
    onAddProject: function() {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel(),
            filter = this.getFilter(ms),
            sm = this.getContactGridPanel().selectionModel;
            
        if(!filter) {
            var addRelations = this.getSelectionsAsArray();
        } else {
            var addRelations = true;
        }
        
        cp.onEditInNewWindow.call(cp, 'add', null, [{
            ptype: 'addrelations_edit_dialog', selectionFilter: filter, addRelations: addRelations, callingApp: 'Addressbook', callingModel: 'Contact'
        }]);
    },
    
    /**
     * adds contacts to an existing project
     */
    onUpdateProject: function() {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel(),
            filter = this.getFilter(ms),
            sm = this.getContactGridPanel().selectionModel;
        
        if(!filter) {
            var addRelations = this.getSelectionsAsArray(),
                count = this.getSelectionsAsArray().length;
        } else {
            var addRelations = true,
                count = sm.store.totalLength;
        }

        Tine.Projects.AddToProjectPanel.openWindow(
            {count: count, selectionFilter: filter, addRelations: addRelations, callingApp: 'Addressbook', callingModel: 'Contact'}
        );
    },
    
    /**
     * returns the current filter if is filter selection
     * @param {Tine.widgets.MainScreen}
     * @return {Object}
     */
    getFilter: function(ms) {
        var sm = this.getContactGridPanel().selectionModel,
            addRelations = this.getSelectionsAsArray(),
            filter = null;
            
        if(sm.isFilterSelect) {
            var filter = sm.getSelectionFilter();
        }
        return filter;
    },
    
    /**
     * gets the current selection as an array 
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
