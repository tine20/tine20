/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase');


Tine.Tinebase.AppTabsPanel = Ext.extend(Ext.TabPanel, {
    
    /**
     * @cfg {Array} defaultTabs
     *
    defaultTabs: [
        'Addressbook',
        'Calendar',
        'Felamimail',
        'Tasks'
    ],*/
    
    activeTab: 1,
    
    /**
     * init appTabsPanel
     */
    initComponent: function() {
        this.initMenu();
        
        this.items = [{
            id: this.id + '-menu',
            // NOTE: there is no easy way to add the standard split arrows
            title: Tine.title + ' &#8595;',
            iconCls: 'tine-favicon'
        }].concat(this.getDefaultTabItems());
        
        Tine.Tinebase.appMgr.on('activate', this.onActivateApp, this);
        this.on('beforetabchange', this.onBeforeTabChange, this);
        this.on('add', this.onTabChange, this);
        this.on('remove', this.onTabChange, this);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * init the combined appchooser/tine menu
     */
    initMenu: function() {
        this.menu = new Ext.menu.Menu({
            layout: 'column',
            width: 400,
            autoHeight: true,
            style: {
                'background-image': 'none'
            },
            defaults: {
                xtype: 'menu',
                floating: false,
                columnWidth: 0.5,
                hidden: false,
                style: {
                    'border-color': 'transparent'
                }
            },
            items: [{
                items: this.getAppItems()
            }, {
                items: [{
                    text: 'logout'
                }]
            }]
        });
    },
    
    /**
     * get app items for the tabPanel
     * 
     * @return {Array}
     */
    getAppItems: function() {
        var appItems = [];
        Tine.Tinebase.appMgr.getAll().each(function(app) {
            appItems.push({
                text: app.getTitle(),
                iconCls: app.getIconCls(),
                handler: this.onAppItemClick.createDelegate(this, [app])
            });
        }, this);
        
        return appItems.reverse();
    },
    
    /**
     * get default tab items configurations
     * 
     * @return {Array}
     */
    getDefaultTabItems: function() {
        /*
        var tabItems = [];
        
        Ext.each(this.defaultTabs, function(appName) {
            var app = Tine.Tinebase.appMgr.get(appName);
            if (app) {
                tabItems.push(this.getTabItem(app));
            }
        }, this);
        
        return tabItems;
        */
        return [this.getTabItem(Tine.Tinebase.appMgr.getDefault())];
    },
    
    /**
     * get tab item configuration
     * 
     * @param {Tine.Application} app
     * @return {Object}
     */
    getTabItem: function(app) {
        return {
            id: this.id + '-' + app.appName,
            title: app.getTitle(),
            iconCls: app.getIconCls(),
            closable: true,
            listeners: {
                scope: this,
                beforeclose: this.onBeforeTabClose,
                activate: this.onTabActivate
            }
        };
    },
    
    /**
     * executed when an app get activated by mainscreen
     * 
     * @param {Tine.Application} app
     */
    onActivateApp: function(app) {
        var tab = this.getItem(this.id + '-' + app.appName) || this.add(this.getTabItem(app));
        
        this.setActiveTab(tab);
    },
    
    /**
     * executed when an app item in this.menu is clicked
     * 
     * @param {Tine.Application} app
     */
    onAppItemClick: function(app) {
        handler: Tine.Tinebase.appMgr.activate(app);
        
        this.menu.hide();
    },
    
    /**
     * executed on tab changes
     * 
     * @param {TabPanel} this
     * @param {Panel} newTab The tab being activated
     * @param {Panel} currentTab The current active tab
     */
    onBeforeTabChange: function(tp, newTab, currentTab) {
        if (this.items.indexOf(newTab) == 0) {
            this.menu.show(this.getTabEl(0), 'tl-bl');
            return false;
        }
    },
    
    /**
     * executed before a tab panel is closed
     * 
     * @param {Ext.Panel} tab
     * @return {boolean}
     */
    onBeforeTabClose: function(tab) {
        // don't close last app panel
        return this.items.getCount() > 2;
    },
    
    /**
     * executed when a tab gets activated
     * 
     * @param {Ext.Panel} tab
     */
    onTabActivate: function(tab) {
        var appName = tab.id.split('-').pop();
        var app = Tine.Tinebase.appMgr.get(appName);
        
        // fixme
        if (Ext.getCmp('treecards').rendered) {
            Tine.Tinebase.appMgr.activate(app);
        }
    },
    
    /**
     * executed when tabs chages
     */
    onTabChange: function() {
        var tabCount = this.items.getCount();
        var closable = tabCount > 2;
        
        for (var i=1, el; i<tabCount; i++) {
            this.items.get(i).closable = closable;
            
            el = this.getTabEl(i);
            if (el) {
                Ext.get(el)[closable ? 'addClass' : 'removeClass']('x-tab-strip-closable');
            }
        }
    }
    
});