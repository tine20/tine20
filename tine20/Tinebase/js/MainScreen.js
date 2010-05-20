/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
 * @version     $Id$
 */
Tine.Tinebase.MainScreen = Ext.extend(Ext.Panel, {
    
    border: false,
    layout: {
        type:'vbox',
        align:'stretch',
        padding:'0'
    },
    
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
            height: 18,
            layout: 'fit',
            border: false,
            items: this.getMainMenu()
        }, {
            cls: 'tine-mainscreen-apptabs',
            hidden: this.appPickerStyle != 'tabs',
            border: false,
            height: Ext.isGecko ? 24 : 22,
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
                //height: 26,
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
                    layout: 'fit',
                    border: false,
                    height: 40,
                    baseCls: 'x-panel-header',
                    html: '<div class ="app-panel-title"></div>'
                }, {
                    cls: 'tine-mainscreen-centerpanel-west-treecards',
                    border: false,
                    id: 'treecards',
                    region: 'center',
                    layout: 'card',
                    activeItem: 0,
                    items: []
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
        // set document / browser title
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        document.title = Tine.title + postfix  + ' - ' + app.getTitle();
        
        // set left top title
        Ext.DomQuery.selectNode('div[class=app-panel-title]').innerHTML = app.getTitle();
        
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

        if(northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
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
        if(cardPanel.items) {
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
