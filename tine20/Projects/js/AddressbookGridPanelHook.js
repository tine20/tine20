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
    
    
    this.projectsMenu = new Ext.menu.Menu({
        items: [{
           text: this.app.i18n._('New Project'),
           scope: this,
           handler: this.onAddProject,
           iconCls: this.app.getIconCls()
        }, {
           text: this.app.i18n._('Add to Project'),
           scope: this,
           handler: this.onUpdateProject,
           iconCls: this.app.getIconCls()
        }]
    });
    
    
    // NOTE: due to the action updater this action is bound the the adb grid only!
    this.handleProjectsAction = new Ext.Action({
        actionType: 'add',
        requiredGrant: 'readGrant',
        allowMultiple: true,
        text: this.app.i18n._('Projects...'),
        iconCls: this.app.getIconCls(),
        scope: this,
        menu: this.projectsMenu
    });
        
    // register in contextmenu
    Ext.ux.ItemRegistry.registerItem('Addressbook-GridPanel-ContextMenu', this.handleProjectsAction, 90);
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
     * compose an email to selected contacts
     * 
     * @param {Button} btn 
     */
    onAddProject: function() {
        var ms = this.app.getMainScreen(),
            cp = ms.getCenterPanel();
            
        var contacts = this.getContactGridPanel().grid.getSelectionModel().getSelections(); 
        
        cp.onEditInNewWindow.call(cp, 'add', {attendee: contacts});
        
    },
    
       
    onUpdateProject: function() {
        var cont = this.getSelectionsAsArray();
        var window = Tine.Projects.AddToProjectPanel.openWindow({attendee: cont});
    },
    
    getSelectionsAsArray: function() {
        var contacts = this.getContactGridPanel().grid.getSelectionModel().getSelections(),
            cont = [];
            
        Ext.each(contacts, function(contact) {
           if(contact.data) cont.push(contact.data);
        });
        
        return cont;
    }
});
