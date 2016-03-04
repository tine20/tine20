/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.MainScreen
 * @extends     Ext.Panel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.widgets.MainScreen = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {String} activeContentType
     */
    activeContentType: null,
    /**
     * @cfg {Array} contentTypes
     */
    contentTypes: null,
    /**
     * @cfg {String} centerPanelClassName
     * name of centerpanel class name suffix in namespace of this app (defaults to GridPanel)
     * the class name will be expanded to Tine[this.appName][contentType + this.centerPanelClassNameSuffix]
     */
    centerPanelClassNameSuffix: 'GridPanel',
    /**
     * @cfg {Bool} useModuleTreePanel
     * use modulePanel (defaults to null -> autodetection)
     */
    useModuleTreePanel: null,

    layout: 'border',

    initComponent: function() {
        this.useModuleTreePanel = Ext.isArray(this.contentTypes) && this.contentTypes.length > 1;
        this.initLayout();

        Tine.widgets.MainScreen.superclass.initComponent.apply(this, arguments);
    },

    /**
     * @private
     */
    initLayout: function() {
        this.items = [{
            ref: 'northCardPanel',
            cls: 'tine-mainscreen-centerpanel-north',
            region: 'north',
            layout: 'card',
            activeItem: 0,
            height: 59,
            border: false,
            items: []
        }, {
            ref: 'centerCardPanel',
            cls: 'tine-mainscreen-centerpanel-center',
            region: 'center',
            animate: true,
            border: false,
            layout: 'card',
            activeItem: 0,
            defaults: {
                hideMode: 'offsets'
            },
            items: []
        }, {
            ref: 'westRegionPanel',
            cls: 'tine-mainscreen-centerpanel-west',
            region: 'west',
            //id: 'west',
            stateful: false,
            split: true,
            width: 200,
            minSize: 100,
            maxSize: 300,
            border: false,
            collapsible:true,
            collapseMode: 'mini',
            header: false,
            layout: 'fit',
            listeners: {
                afterrender: function() {
                    // add to scrollmanager
                    if (arguments[0] && arguments[0].hasOwnProperty('body')) {
                        Ext.dd.ScrollManager.register(arguments[0].body);
                    }
                }
            },
            autoScroll: true,
            tbar: [{
                buttonAlign : 'center'
            }],
            items: [{
                ref: '../moduleCardPanel',
                cls: 'tine-mainscreen-centerpanel-west-modules',
                border: false,
                autoScroll: false,
                autoHeight: true,
                style: {
                    width: '100%'
                },
                layout: 'card',
                activeItem: 0,
                items: []
            }, {
                ref: '../westCardPanel',
                cls: 'tine-mainscreen-centerpanel-west-treecards',
                border: false,
                style: {
                    width: '100%'
                },
                autoScroll: false,
                layout: 'card',
                activeItem: 0,
                items: []
            }]
         }];
    },

    /**
     * shows/activates this app mainscreen
     *
     * @return {Tine.widgets.MainScreen} this
     */
    activate: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            Tine.Tinebase.MainScreen.setActiveCenterPanel(this, true);

            // lazy loading
            this.showWestCardPanel();
            this.showCenterPanel();
            this.showNorthPanel();
            this.showModuleTreePanel();

            this.fireEvent('show', this);
        }
        return this;
    },

    /**
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        return (this.activeContentType) ? this.activeContentType : '';
    },
     
    /**
     * get center panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getCenterPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();
        
        if (! this[contentType + this.centerPanelClassNameSuffix]) {
            try {
                this[contentType + this.centerPanelClassNameSuffix] = new Tine[this.app.appName][contentType + this.centerPanelClassNameSuffix]({
                    app: this.app,
                    plugins: [this.getWestPanel().getFilterPlugin(contentType)]
                });
            } catch (e) {
                Tine.log.error('Could not create centerPanel "Tine.' + this.app.appName + '.' + contentType + this.centerPanelClassNameSuffix + '"');
                Tine.log.error(e.stack ? e.stack : e);
                this[contentType + this.centerPanelClassNameSuffix] = new Ext.Panel({html: 'ERROR'});
            }
        }
        
        return this[contentType + this.centerPanelClassNameSuffix];
    },

    /**
     * get north panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();
        
        if (! this[contentType + 'ActionToolbar']) {
            try {
                this[contentType + 'ActionToolbar'] = this[contentType + this.centerPanelClassNameSuffix].getActionToolbar();
            } catch (e) {
                Tine.log.error('Could not create northPanel');
                Tine.log.error(e.stack ? e.stack : e);
                this[contentType + 'ActionToolbar'] = new Ext.Panel({html: 'ERROR'});
            }
        }

        return this[contentType + 'ActionToolbar'];
    },
    
    /**
     * get module tree panel
     * 
     * @return {Ext.Panel}
     */
    getModuleTreePanel: function() {
        if (! this.moduleTreePanel) {
            if (this.useModuleTreePanel) {
                this.moduleTreePanel = new Tine.widgets.ContentTypeTreePanel({
                    app: this.app, 
                    contentTypes: this.contentTypes,
                    contentType: this.getActiveContentType()
                });
                this.moduleTreePanel.on('click', function (node, event) {
                    if(node != this.lastClickedNode) {
                        this.lastClickedNode = node;
                        this.fireEvent('selectionchange');
                    }
                });
            } else {
                this.moduleTreePanel = new Ext.Panel({html:'', border: false, frame: false});
            } 
        }
        return this.moduleTreePanel;
    },
    
    /**
     * get panel for westCardPanel region of given contentType
     *
     * NOTE: do not confuse this with westRegionPanel!
     * 
     * @return {Ext.Panel}
     */
    getWestPanel: function() {
        var contentType = this.getActiveContentType(),
            wpName = contentType + 'WestPanel';
            
        if (! this[wpName]) {
            var wpconfig = {
                    app: this.app, 
                    contentTypes: this.contentTypes,
                    contentType: contentType,
                    listeners: {
                        scope: this,
                        selectionchange: function() {
                            var cp = this.getCenterPanel();
                            if(cp) {
                                try {
                                    var grid = cp.getGrid();
                                    if(grid) {
                                        var sm = grid.getSelectionModel();
                                        if(sm) {
                                            sm.clearSelections();
                                            cp.actionUpdater.updateActions(sm.getSelectionsCollection());
                                        }
                                    }
                                } catch (e) {
                                    // do nothing - no grid
                                }
                            }
                        }
                    }
                };
            try {
                if(Tine[this.app.name].hasOwnProperty(wpName)) this[wpName] = new Tine[this.app.appName][wpName](wpconfig);
                else this[wpName] = new Tine.widgets.mainscreen.WestPanel(wpconfig);
            } catch (e) {
                Tine.log.error('Could not create westPanel');
                Tine.log.error(e.stack ? e.stack : e);
                this[wpName] = new Ext.Panel({html: 'ERROR'});
            }
        }


        return this[wpName];
    },

    /**
     * shows center panel in mainscreen
     */
    showCenterPanel: function() {
        this.setActiveContentPanel(this.getCenterPanel(this.getActiveContentType()), true);
    },
    
    /**
     * shows module tree panel in mainscreen
     */
    showModuleTreePanel: function() {
        this.setActiveModulePanel(this.getModuleTreePanel(), true);
    },
    
    /**
     * shows west panel in mainscreen
     */
    showWestCardPanel: function() {
        // add save favorites button to toolbar if favoritesPanel exists
        var westPanel = this.getWestPanel(),
            favoritesPanel = Ext.isFunction(westPanel.getFavoritesPanel) ? westPanel.getFavoritesPanel() : false,
            westPanelToolbar = this.westRegionPanel.getTopToolbar();

        westPanelToolbar.removeAll();

        if (favoritesPanel) {
            westPanelToolbar.addButton({
                xtype: 'button',
                text: _('Save current view as favorite'),
                iconCls: 'action_saveFilter',
                scope: this,
                handler: function() {
                    favoritesPanel.saveFilter.call(favoritesPanel);
                }
            });

            westPanelToolbar.show();
        } else {
            westPanelToolbar.hide();
        }
        
        westPanelToolbar.doLayout();

        this.setActiveTreePanel(westPanel, true);
    },
    
    /**
     * shows north panel in mainscreen
     */
    showNorthPanel: function() {
        this.setActiveToolbar(this.getNorthPanel(this.getActiveContentType()), true);
    },

    /**
     * sets the active content panel
     *
     * @param {Ext.Panel} item Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveContentPanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.centerCardPanel, panel, keep);
    },

    /**
     * sets the active tree panel
     *
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.westCardPanel, panel, keep);
    },

    /**
     * sets the active module tree panel
     *
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveModulePanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.moduleCardPanel, panel, keep);
    },

    /**
     * sets item
     *
     * @param {Ext.Toolbar} panel toolbar to activate
     * @param {Bool} keep keep panel
     */
    setActiveToolbar: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.northCardPanel, panel, keep);
    },

    /**
     * gets the currently displayed toolbar
     *
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        var northPanel = this.northCardPanel;

        if (northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
            return northPanel.layout.activeItem.el;
        } else {
            return false;
        }
    }
});
