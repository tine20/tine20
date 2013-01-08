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
 * @class       Tine.Tinebase.MainScreen
 * @extends     Ext.Panel
 * @singleton   
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.MainScreen = Ext.extend(Ext.Panel, {
    
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
     * @cfg {String} appPickerStyle "tabs" or "pile" defaults to "tabs"
     */
    appPickerStyle: 'tabs',
    
    /**
     * @private
     */
    initComponent: function() {
        // NOTE: this is a cruid method to create some kind of singleton...
        Tine.Tinebase.MainScreen = this;
        
        this.initLayout();
        Tine.Tinebase.appMgr.on('activate', this.onAppActivate, this);
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * @private
     */
    initLayout: function() {
        this.items = [{
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
            cls: 'tine-mainscreen-apptabs',
            hidden: this.appPickerStyle != 'tabs',
            border: false,
            height: Ext.isGecko ? 22 : 20,
            items: new Tine.Tinebase.AppTabsPanel({
                plain: true
            })
        }, {
            cls: 'tine-mainscreen-centerpanel',
            flex: 1,
            layout: 'border',
            border: false,
            items: [{
                cls: 'tine-mainscreen-centerpanel-north',
                region: 'north',
                layout: 'card',
                activeItem: 0,
                height: 59,
                border: false,
                id:     'north-panel-2',
                items: []
            }, {
                cls: 'tine-mainscreen-centerpanel-center',
                region: 'center',
                id: 'center-panel',
                animate: true,
                border: false,
                layout: 'card',
                activeItem: 0,
                items: []
            }, {
                cls: 'tine-mainscreen-centerpanel-west',
                region: 'west',
                id: 'west',
                stateful: false,
                layout: 'border',
                split: true,
                width: 200,
                minSize: 100,
                maxSize: 300,
                border: false,
                collapsible:true,
                collapseMode: 'mini',
                header: false,
                items: [{
                    cls: 'tine-mainscreen-centerpanel-west-apptitle',
                    hidden: this.appPickerStyle != 'pile',
                    region: 'north',
                    border: false,
                    height: 40,
                    baseCls: 'x-panel-header',
                    html: '<div class ="app-panel-title"></div>'
                }, {
                    cls: 'tine-mainscreen-centerpanel-west-center',
                    border: false,
                    region: 'center',
                    layout: {
                        type: 'column',
                        align: 'stretch'
                    },
                    items:[{
                        xtype: 'toolbar',
                        height: 27,
                        flex: 0,
                        buttonAlign : 'center',
                        style: {
                            padding: '2px'
                        },
                        items: [],
                        ref: '../../../westPanelToolbar'
                    }, {
                        border: false,
                        frame: false,
                        id: 'westpanel-scroll-wrapper',
                        autoScroll:true,
                        style: {
                                width: '100%',
                                'overflow-y': 'auto',
                                'overflow-x': 'hidden'
                            },
                        layout: {
                            type: 'column',
                            align: 'stretch'
                        },
                        doLayout: function(shallow, force) {
                            var el = this.getEl();
                            this.supr().doLayout.call(this, shallow, force);
                            var wrap = Ext.get('west').select('div.x-panel-body.x-panel-body-noheader.x-panel-body-noborder.x-border-layout-ct').getStyle('height'),
                                height = wrap.first().getHeight() - 27;
                            el.setStyle('height', height + 'px');
                            el.dom.firstChild.firstChild.style.overflow = 'hidden';
                            },
                        items: [{
                            cls: 'tine-mainscreen-centerpanel-west-modules',
                            border: false,
                            autoScroll: false,
                            style: {
                                width: '100%'
                            },
                            id: 'moduletree',
                            flex: 1,
                            layout: 'card',
                            activeItem: 0,
                            items: []
                        }, {
                            cls: 'tine-mainscreen-centerpanel-west-treecards',
                            border: false,
                            style: {
                                width: '100%'
                            },
                            autoScroll: false,
                            flex:1,
                            id: 'treecards',
                            layout: 'card',
                            activeItem: 0,
                            items: []
                        }]
                    }]
                }, new Tine.Tinebase.AppPile({
                    cls: 'tine-mainscreen-centerpanel-west-apppile',
                    hidden: this.appPickerStyle != 'pile',
                    region: 'south',
                    layout: 'fit',
                    border: false,
                    split: true,
                    collapsible:true,
                    collapseMode: 'mini',
                    header: false
                })]
            }]
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
                showMainMenu: this.appPickerStyle != 'tabs'
            });
        }
        
        return this.mainMenu;
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
        document.title = appPostfix + Tine.title + postfix  + ' - ' + app.getTitle();
        
        // set left top title
        Ext.DomQuery.selectNode('div[class=app-panel-title]').innerHTML = app.getTitle();
        
        // add save favorites button to toolbar if favoritesPanel exists
        var westPanel = app.getMainScreen().getWestPanel();
        var favoritesPanel = Ext.isFunction(westPanel.getFavoritesPanel) ? westPanel.getFavoritesPanel() : null;
        
        this.westPanelToolbar.removeAll();
        if (favoritesPanel) {
            this.westPanelToolbar.addButton({
                xtype: 'button',
                text: _('Save current view as favorite'),
                iconCls: 'action_saveFilter',
                scope: this,
                handler: function() {
                    favoritesPanel.saveFilter.call(favoritesPanel);
                }
            });
            
            this.westPanelToolbar.show();
        } else {
            this.westPanelToolbar.hide();
        }
        
        this.westPanelToolbar.doLayout();
        
        this.getEl().select('div#treecards div.x-column-layout-ct').setStyle('height', null);
        this.getEl().select('div#moduletree div.ux-arrowcollapse-body.ux-arrowcollapse-body-noborder').setStyle('height', null);
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
                title: _('Your password expired. Please enter a new user password:')
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
        if (Ext.getCmp('treecards').rendered) {
            Tine.Tinebase.appMgr.activate();
        } else {
            this.activateDefaultApp.defer(10, this);
        }
    },
    
    /**
     * sets the active content panel
     * 
     * @param {Ext.Panel} item Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveContentPanel: function(panel, keep) {
        var cardPanel = Ext.getCmp('center-panel');
        panel.keep = keep;
        
        this.cleanupCardPanelItems(cardPanel);
        this.setActiveCardPanelItem(cardPanel, panel);
    },
    
    /**
     * sets the active tree panel
     * 
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        var cardPanel = Ext.getCmp('treecards');
        panel.keep = keep;
        this.cleanupCardPanelItems(cardPanel);
        this.setActiveCardPanelItem(cardPanel, panel);
    },
    
    /**
     * sets the active module tree panel
     * 
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveModulePanel: function(panel, keep) {
        var modulePanel = Ext.getCmp('moduletree');
        panel.keep = keep;
        this.cleanupCardPanelItems(modulePanel);
        this.setActiveCardPanelItem(modulePanel, panel);
    },
    
    /**
     * sets item
     * 
     * @param {Ext.Toolbar} panel toolbar to activate
     * @param {Bool} keep keep panel
     */
    setActiveToolbar: function(panel, keep) {
        var cardPanel = Ext.getCmp('north-panel-2');
        panel.keep = keep;
        
        this.cleanupCardPanelItems(cardPanel);
        this.setActiveCardPanelItem(cardPanel, panel);
    },
    
    /**
     * gets the currently displayed toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        var northPanel = Ext.getCmp('north-panel-2');

        if (northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
            return northPanel.layout.activeItem.el;
        } else {
            return false;
        }
    },
    
    /**
     * remove all items which should not be keeped -> don't have a keep flag
     * 
     * @param {Ext.Panel} cardPanel
     */
    cleanupCardPanelItems: function(cardPanel) {
        if (cardPanel.items) {
            for (var i=0,p; i<cardPanel.items.length; i++){
                p =  cardPanel.items.get(i);
                if (! p.keep) {
                    cardPanel.remove(p);
                }
            }  
        }
    },
    
    /**
     * add or set given item
     * 
     * @param {Ext.Panel} cardPanel
     * @param {Ext.Panel} item
     */
    setActiveCardPanelItem: function(cardPanel, item) {
        if (cardPanel.items.indexOf(item) !== -1) {
            cardPanel.layout.setActiveItem(item.id);
        } else {
            cardPanel.add(item);
            cardPanel.layout.setActiveItem(item.id);
            cardPanel.doLayout();
        }
    }
});
