/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.MainScreen
 * @extends     Ext.util.Observable
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @constructor
 */
Tine.widgets.MainScreen = function(config) {
    Ext.apply(this, config);
    
    this.addEvents(
        /**
         * @event beforeshow
         * Fires before the component is shown. Return false to stop the show.
         * @param {Ext.Component} this
         */
        'beforeshow',
        /**
         * @event show
         * Fires after the component is shown.
         * @param {Ext.Component} this
         */
        'show'
    );
    
    if (this.hasContainerTreePanel || this.hasContainerTreePanel === null) {
        this.hasContainerTreePanel = true;
    }
    
    if (this.hasFavoritesPanel || (this.hasFavoritesPanel === null && Tine[this.app.appName].FilterPanel)) {
        this.hasFavoritesPanel = true;
    }
    
    Tine.widgets.MainScreen.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.MainScreen, Ext.util.Observable, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    
    /**
     * @cfg {String} activeContentType
     */
    activeContentType: '',
    
    /**
     * @cfg {String} centerPanelClassName
     * name of centerpanel class name suffix in namespace of this app (defaults to GridPanel)
     * the class name will be expanded to Tine[this.appName][contentType + this.centerPanelClassNameSuffix]
     */
    centerPanelClassNameSuffix: 'GridPanel',
    
    /**
     * @cfg {String} containerTreeClass
     * name of container tree class in namespace of this app (defaults to TreePanel)
     * the class name will be expanded to Tine[this.appName][this.containerTreePanelClassName]
     */
    containerTreePanelClassName: 'TreePanel',
    
    /**
     * @cfg {String} favoritesPanelClassName
     * name of favorites class in namespace of this app (defaults to FilterPanel)
     * the class name will be expanded to Tine[this.appName][this.favoritesPanelClassName]
     */
    favoritesPanelClassName: 'FilterPanel',
    
    /**
     * @cfg {Bool} hasContainerTreePanel
     * west panel has containerTreePanel (defaults to null -> autodetection)
     */
    hasContainerTreePanel: null,
    
    /**
     * @cfg {Bool} hasFavoritesPanel
     * west panel has favorites panel (defaults to null -> autodetection)
     */
    hasFavoritesPanel: null,
    
    /**
     * @cfg {Object} westPanelItemsDefauls
     * defaults for west panel items
     */
    westPanelItemsDefauls: {
        collapsible: true,
        baseCls: 'ux-arrowcollapse',
        animCollapse: true,
        titleCollapse:true
    },
    
    /**
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        return this.activeContentType;
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
            this[contentType + this.centerPanelClassNameSuffix] = new Tine[this.app.appName][contentType + this.centerPanelClassNameSuffix]({
                app: this.app,
                plugins: [this.containerTreePanel.getFilterPlugin()]
            });
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
            this[contentType + 'ActionToolbar'] = this[contentType + this.centerPanelClassNameSuffix].getActionToolbar();
        }
        
        return this[contentType + 'ActionToolbar'];
    },
    
    /**
     * get west panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @return {Ext.Panel}
     */
    getWestPanel: function() {
        if (! this.westPanel) {
            var items = [];
            if (this.hasFavoritesPanel) {
                items.push(Ext.apply(this.getFavoritesPanel(), {
                    title: _('Favorites')
                }, this.westPanelItemsDefauls));
            }
            
            if (this.hasContainerTreePanel) {
                var containerTreePanel = this.getContainerTreePanel();
                
                var containersName = containerTreePanel.recordClass ? 
                    this.app.i18n._hidden(containerTreePanel.recordClass.getMeta('containersName')) :
                    _('containers');
            
                items.push(Ext.apply(this.getContainerTreePanel(), {
                    title: containersName,
                    collapsed: true
                }, this.westPanelItemsDefauls));
            }
            
            var baseCls = this.westPanelItemsDefauls.baseCls;
            var xsrollKiller = function(cmp) {
                var panelEls = cmp.getEl().child('div[class^=' + baseCls + '-body]');
                if (panelEls) {
                    panelEls.applyStyles('overflow-x: hidden');
                }
            };
            
            this.westPanel = new Ext.Panel({
                layout: 'hfit',
                border: false,
                items: items,
                listeners: {
                    scope: this,
                    afterrender: function(cmp) {
                        xsrollKiller(cmp);
                        cmp.items.each(function(item){
                            item.on('afterrender', xsrollKiller);
                        }, this);
                    }
                }
            });
        }
        
        return this.westPanel;
    },
    
    /**
     * returns containerTree panel
     * 
     * @return {Tine.Tinebase.widgets.ContainerTreePanel}
     */
    getContainerTreePanel: function() {
        if (this.hasContainerTreePanel && !this.containerTreePanel) {
            this.containerTreePanel = new Tine[this.app.appName][this.containerTreePanelClassName]({app: this.app});
        }
        
        return this.containerTreePanel;
    },
    
    /**
     * returns favorites panel
     * 
     * @return {Ext.Panel}
     */
    getFavoritesPanel: function() {
        if (this.hasFavoritesPanel && !this.favoritesPanel) {
            this.favoritesPanel = new Tine[this.app.appName][this.favoritesPanelClassName]({
                app: this.app,
                treePanel: this.containerTreePanel
            });
        }
        
        return this.favoritesPanel;
    },
    
    /**
     * shows/activates this app mainscreen
     * 
     * @return {Tine.widgets.MainScreen} this
     */
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.showWestPanel();
            this.showCenterPanel();
            this.showNorthPanel();
            
            this.fireEvent('show', this);
        }
        return this;
    },
    
    /**
     * shows center panel in mainscreen
     */
    showCenterPanel: function() {
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.getCenterPanel(this.getActiveContentType()), true);
    },
    
    /**
     * shows west panel in mainscreen
     */
    showWestPanel: function() {
        Tine.Tinebase.MainScreen.setActiveTreePanel(this.getWestPanel(), true);
    },
    
    /**
     * shows north panel in mainscreen
     */
    showNorthPanel: function() {
        Tine.Tinebase.MainScreen.setActiveToolbar(this.getNorthPanel(this.getActiveContentType()), true);
    }
});
