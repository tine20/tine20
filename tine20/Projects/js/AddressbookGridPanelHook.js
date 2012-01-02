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
    
    this.addProjectAction = new Ext.Action({
        requiredGrant: 'readGrant',
        text: this.app.i18n._('Project'),
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onAddProject,
        listeners: {
            scope: this,
            render: this.onRender
        }
    });
    
    this.newProjectAction = new Ext.Action({
        requiredGrant: 'readGrant',
        text: this.app.i18n._('Project'),
        iconCls: this.app.getIconCls(),
        scope: this,
        handler: this.onNewProject,
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
    onNewProject: function() {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel();
 
        Tine.Projects.ProjectEditDialog.openWindow({selectedRecords: Ext.encode(this.getSelectionsAsArray())});
    },
    
    /**
     * adds contacts to an existing project
     */
    onAddProject: function() {
        var cont = this.getSelectionsAsArray();
        var window = Tine.Projects.AddToProjectPanel.openWindow({attendee: cont});
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
