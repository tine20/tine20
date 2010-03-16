/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ContactGrid.js 6638 2009-02-09 11:56:32Z c.weiss@metaways.de $
 */

Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.Application
 * @extends     Tine.Tinebase.Application
 * Addressbook Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 */
Tine.Addressbook.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Addressbook', 'Addressbooks', 1);
    }
});

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.MainScreen
 * @extends     Tine.Tinebase.widgets.app.MainScreen
 * MainScreen of the Addressbook Application <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 */
Tine.Addressbook.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    activeContentType: 'Contact'
});



Tine.Addressbook.TreePanel = function(config) {
    Ext.apply(this, config);
    
    //var accountBackend = Tine.Tinebase.registry.get('accountBackend');
    //if (accountBackend == 'Sql') {
       this.extraItems = [{
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._("Internal Contacts"),
            cls: "file",
            containerType: 'internal',
            container: {path: '/internal'},
            id: "internal",
            children: [],
            leaf: false,
            expanded: true
        }];
    //}
    
    this.id = 'Addressbook_Tree';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Addressbook.Model.Contact;
    Tine.Addressbook.TreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Addressbook.TreePanel , Tine.widgets.container.TreePanel);


Tine.Addressbook.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Addressbook.FilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.FilterPanel, Tine.widgets.grid.PersistentFilterPicker, {
    filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ContactFilter'}]
});
