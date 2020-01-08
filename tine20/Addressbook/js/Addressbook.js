/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

require('./StructurePanel');
require('./Model/ContactPersonalGrants');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.Application
 * @extends     Tine.Tinebase.Application
 * Addressbook Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Addressbook.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Contact')
     */
    addButtonText: 'New Contact',
    
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Addressbook', 'Addressbooks', 1);
    },

    /** 
     * Overide get main screen to allow for feature gating
     *
     **/
    getMainScreen: function() {
        var mainscreen = Tine.Addressbook.Application.superclass.getMainScreen.call(this);

        if (!Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureListView')
            && !Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureResources')) {
            mainscreen.useModuleTreePanel = false;
        }

        return mainscreen;
    },

    registerCoreData: function() {
        Tine.CoreData.Manager.registerGrid('adb_lists', Tine.Addressbook.ListGridPanel, {
            app: this,
            initialLoadAfterRender: false
        });

        Tine.CoreData.Manager.registerGrid(
            'adb_list_roles',
            Tine.widgets.grid.GridPanel,
            {
                recordClass: Tine.Addressbook.Model.ListRole,
                app: this,
                initialLoadAfterRender: false,
                // TODO move this to a generic place
                gridConfig: {
                    autoExpandColumn: 'name',
                    columns: [{
                        id: 'id',
                        header: this.i18n._("ID"),
                        width: 150,
                        sortable: true,
                        dataIndex: 'id',
                        hidden: true
                    }, {
                        id: 'name',
                        header: this.i18n._("Name"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'name'
                    }, {
                        id: 'description',
                        header: this.i18n._("Description"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'description',
                        hidden: true
                    }]
                }
            }
        );
        
        Tine.CoreData.Manager.registerGrid(
            'adb_industries',
            Tine.widgets.grid.GridPanel,
            {
                recordClass: Tine.Addressbook.Model.Industry,
                app: this,
                initialLoadAfterRender: false,
                // TODO move this to a generic place
                gridConfig: {
                    autoExpandColumn: 'name',
                    columns: [{
                        id: 'id',
                        header: this.i18n._("ID"),
                        width: 150,
                        sortable: true,
                        dataIndex: 'id',
                        hidden: true
                    }, {
                        id: 'name',
                        header: this.i18n._("Name"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'name'
                    }, {
                        id: 'description',
                        header: this.i18n._("Description"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'description',
                        hidden: true
                    }]
                }
            }
        );
    }
});

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.MainScreen
 * @extends     Tine.widgets.MainScreen
 * MainScreen of the Addressbook Application <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Addressbook.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Contact',
    contentTypes: [
        {model: 'Contact', requiredRight: null, singularContainerMode: false}
    ],

    initComponent: function() {
        var app = Tine.Tinebase.appMgr.get('Addressbook');

        if (app.featureEnabled('featureListView')) {
            this.contentTypes.push({model: 'List', requiredRight: null, singularContainerMode: false});
        }

        if (app.featureEnabled('featureStructurePanel')) {
            this.contentTypes.push({
                contentType: 'structure',
                app: app,
                text: app.i18n._('Structure'), // _('Structure')
                iconCls: 'AddressbookStructure',
                xtype: 'addressbook.structurepanel'
            });
        }

        // only show if calendar is available and user has manage_resources right
        if (app.featureEnabled('featureResources')
            && Tine.Tinebase.common.hasRight('run', 'Calendar')
            && Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources')
        ) {
            var cal = Tine.Tinebase.appMgr.get('Calendar');
            this.contentTypes.push({
                contentType: 'resource',
                app: cal,
                text: cal.i18n._('Resources'),
                iconCls: 'CalendarResource',
                xtype: 'calendar.resourcegridpanel',
                ownActionToolbar: false,
                singularContainerMode: true
            });
        }

        Tine.Addressbook.MainScreen.superclass.initComponent.call(this);
    }
});

Tine.Addressbook.ContactTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'Addressbook_Contact_Tree';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Addressbook.Model.Contact;
    Tine.Addressbook.ContactTreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Addressbook.ContactTreePanel , Tine.widgets.container.TreePanel);

Tine.Addressbook.ListTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'Addressbook_List_Tree';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Addressbook.Model.List;
    Tine.Addressbook.ListTreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Addressbook.ListTreePanel , Tine.widgets.container.TreePanel);


Tine.Addressbook.handleRequestException = Tine.Tinebase.ExceptionHandler.handleRequestException;

Tine.Addressbook.ContactFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Addressbook.ContactFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.ContactFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ContactFilter'}]
});

Tine.Addressbook.ListFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Addressbook.ListFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.ListFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ListFilter'}]
});

// register Contact related renderers -> needed e.g. for duplicate resolve dlg as Contact is no mcv2 app yet
Tine.Tinebase.appMgr.isInitialised('Addressbook').then(() => {
    _.each(Tine.Addressbook.ContactGridPanel.getBaseColumns(Tine.Tinebase.appMgr.get('Addressbook').i18n), (col) => {
        if (col.renderer) {
            Tine.widgets.grid.RendererManager.register('Addressbook', 'Contact', col.dataIndex, col.renderer);
        }
    });
});

// register grants for calendar containers
Tine.widgets.container.GrantsManager.register('Addressbook_Model_Contact', function(container) {
    var _ = window.lodash,
        me = this,
        grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

    grants.push('privateData');
    return grants;
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    privateDataGrantTitle: 'Private', // i18n._('Private')
    privateDataGrantDescription: 'The grant to access contacts private information', // i18n._('The grant to access contacts private information')

});