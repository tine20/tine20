/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase');

/**
 * Tine 2.0 jsclient MainScreen.
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MainScreenPanel
 * @extends     Ext.Panel
 * @singleton   
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.MainScreenPanel = Ext.extend(Ext.Panel, {
    
    border: false,
    layout: {
        type:'vbox',
        align:'stretch',
        padding:'0'
    },
    /**
     * the active app
     * @type {Tine.Tinebase.Application}
     */
    app: null,
    
    /**
     * @private
     */
    initComponent: function() {
        // NOTE: this is a cruid method to create some kind of singleton...
        Tine.Tinebase.MainScreen = this;
        
        this.initLayout();
        Tine.Tinebase.appMgr.on('activate', this.onAppActivate, this);

        this.supr().initComponent.apply(this, arguments);
    },
    
    /**
     * @private
     */
    initLayout: function() {
        this.items = [{
            ref: 'topBox',
            cls: 'tine-mainscreen-topbox',
            border: false,
            html: '<div class="tine-mainscreen-topbox-left"></div><div class="tine-mainscreen-topbox-middle"></div><div class="tine-mainscreen-topbox-right"></div>'
        }, {
            cls: 'tine-mainscreen-mainmenu',
            height: 20,
            layout: 'fit',
            border: false,
            items: this.getMainMenu()
        }, {
            ref: 'appTabs',
            cls: 'tine-mainscreen-apptabs',
            hidden: this.hideAppTabs,
            border: false,
            height: 20,
            items: new Tine.Tinebase.AppTabsPanel({
                plain: true
            })
        }, {
            ref: 'centerPanel',
            cls: 'tine-mainscreen-centerpanel',
            flex: 1,
            border: false,
            layout: 'card'
        }];
    },
    
    /**
     * returns main menu
     * 
     * @return {Ext.Menu}
     */
    getMainMenu: function() {
        if (! this.mainMenu) {
            this.mainMenu = new Tine.Tinebase.MainMenu({
                showMainMenu: false
            });
        }
        
        return this.mainMenu;
    },

    /**
     * returns center (card) panel
     *
     * @returns {Ext.Panel}
     */
    getCenterPanel: function() {
        return this.centerPanel;
    },

    /**
     * appMgr app activation listener
     * 
     * @param {Tine.Application} app
     */
    onAppActivate: function(app) {
        Tine.log.info('Activating app ' + app.appName);
        
        this.app = app;
        
        // set document / browser title
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '',
            // some apps (Felamimail atm) can add application specific title postfixes
            // TODO generalize this
            appPostfix = (document.title.match(/^\([0-9]+\) /)) ? document.title.match(/^\([0-9]+\) /)[0] : '';
        document.title = Ext.util.Format.stripTags(appPostfix + Tine.title + postfix  + ' - ' + app.getTitle());
    },
    
    /**
     * executed after rendering process
     * 
     * @private
     */
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        
        this.activateDefaultApp();
        
        // check for new version 
        if (Tine.Tinebase.common.hasRight('check_version', 'Tinebase')) {
            Tine.widgets.VersionCheck();
        }
        
        if (Tine.Tinebase.registry.get('mustchangepw')) {
            var passwordDialog = new Tine.Tinebase.PasswordChangeDialog({
                title: i18n._('Your password expired. Please enter a new user password:')
            });
            passwordDialog.show();
        }
    },

    /**
     * activate default application
     * 
     * NOTE: this fn waits for treecard panel to be rendered
     * 
     * @private
     */
    activateDefaultApp: function() {
        if (this.getCenterPanel().rendered) {
            Tine.Tinebase.appMgr.activate();
        } else {
            this.activateDefaultApp.defer(10, this);
        }
    },

    /**
     * set the active center panel
     * @param panel
     */
    setActiveCenterPanel: function(panel, keep) {
        if (panel.app) {
            // neede for legacy handling
            this.app = panel.app;
        }
        var cardPanel = this.getCenterPanel();

        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(cardPanel, panel, keep);
    },


    /**
     * sets the active content panel
     *
     * @deprecated
     * @param {Ext.Panel} item Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveContentPanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveContentPanel is deprecated, use <App>.Mainscreen.setActiveContentPanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveContentPanel(panel, keep);
    },

    /**
     * sets the active tree panel
     *
     * @deprecated
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveTreePanel is deprecated, use <App>.Mainscreen.setActiveTreePanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveTreePanel(panel, keep);
    },

    /**
     * sets the active module tree panel
     *
     * @deprecated
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveModulePanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveModulePanel is deprecated, use <App>.Mainscreen.setActiveModulePanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveModulePanel(panel, keep);
    },

    /**
     * sets item
     *
     * @deprecated
     * @param {Ext.Toolbar} panel toolbar to activate
     * @param {Bool} keep keep panel
     */
    setActiveToolbar: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveToolbar is deprecated, use <App>.Mainscreen.setActiveToolbar instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveToolbar(panel, keep);
    },

    /**
     * gets the currently displayed toolbar
     *
     * @deprecated
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.getActiveToolbar is deprecated, use <App>.Mainscreen.getActiveToolbar instead ' + new Error().stack);
        return this.app.getMainScreen().getActiveToolbar();
    }
});
