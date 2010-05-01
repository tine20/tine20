/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.widgets.mainscreen');

/**
 * @namespace   Tine.widgets.mainscreen
 * @class       Tine.widgets.mainscreen.WestPanel
 * @extends     Ext.Panel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @constructor
 */
Tine.widgets.mainscreen.WestPanel = function(config) {
    Ext.apply(this, config);
    
    if (this.hasContainerTreePanel || this.hasContainerTreePanel === null) {
        this.hasContainerTreePanel = true;
    }
    
    if (this.hasFavoritesPanel || (this.hasFavoritesPanel === null && Tine[this.app.appName].FilterPanel)) {
        this.hasFavoritesPanel = true;
    }
    
    this.defaults = {};
    
    Tine.widgets.mainscreen.WestPanel.superclass.constructor.apply(this, arguments);
    
    this.on('added', this.onItemAdd, this);
};

Ext.extend(Tine.widgets.mainscreen.WestPanel, Ext.Panel, {
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
    
    layout: 'hfit',
    border: false,
    
    /**
     * called after the rendering process
     */
    afterRender: function() {
        Tine.widgets.mainscreen.WestPanel.superclass.afterRender.apply(this, arguments);
        
        // enable vertical scrolling
        this.body.applyStyles('overflow-y: auto');
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
     * inits this west panel
     */
    initComponent: function() {
        this.items = [];
        
        if (this.hasContainerTreePanel) {
            var containerTreePanel = this.getContainerTreePanel();
            
            var containersName = containerTreePanel.recordClass ? 
                this.app.i18n._hidden(containerTreePanel.recordClass.getMeta('containersName')) :
                _('containers');
            
            // recheck if container tree is a container tree as in apps not dealing
            // with containers we don't want a collapsed arrow header
            var isContainerTreePanel = typeof containerTreePanel.selectContainerPath === 'function';
            
            if (isContainerTreePanel) {
                this.defaults = {
                    collapsible: true,
                    baseCls: 'ux-arrowcollapse',
                    animCollapse: true,
                    titleCollapse:true
                };
            }
            
            this.items.push(Ext.apply(this.getContainerTreePanel(), {
                title: isContainerTreePanel ? containersName : false,
                collapsed: isContainerTreePanel
            }, this.defaults));
            
        }
        
        if (this.hasFavoritesPanel) {
            this.items.unshift(Ext.apply(this.getFavoritesPanel(), {
                title: _('Favorites')
            }, this.defaults));
        }
        
        // why the f* isn't this done by Ext?
        Ext.each(this.items, function(item, idx) {
            this.onItemAdd(this, item, idx)
        }, this);
        
        Tine.widgets.mainscreen.WestPanel.superclass.initComponent.apply(this, arguments);
    },
    
    /**
     * called when an item gets added to this panel
     * 
     * @param {WestPanel} westPanel
     * @param {Ext.Component} cmp
     * @param {Number} number
     */
    onItemAdd: function(westPanel, cmp, number) {
        // kill x-scrollers
        if (cmp.getEl()) {
            this.xsrollKiller(cmp);
        } else {
            cmp.on('afterrender', this.xsrollKiller, this);
        }
        
    },
    
    /**
     * kill x scrollers of given component
     * 
     * @param {Ext.Component} cmp
     */
    xsrollKiller:  function(cmp) {
        var panelEls = cmp.getEl().child('div[class^=' + this.defaults.baseCls + '-body]');
        if (panelEls) {
            panelEls.applyStyles('overflow-x: hidden');
        }
    }
});