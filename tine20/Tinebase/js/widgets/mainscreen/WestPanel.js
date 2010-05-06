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
 * @extends     Ext.ux.Portal
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo make save button working again -> move to here
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
};

Ext.extend(Tine.widgets.mainscreen.WestPanel, Ext.ux.Portal, {
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
    
    layout: 'column',
    cls : 'x-portal',
    defaultType : 'portalcolumn',
    border: false,

    stateful: true,
    stateEvents: ['collapse', 'expand', 'drop'],
    
    /**
     * called after rendering process
     */
    afterRender: function() {
        Tine.widgets.mainscreen.WestPanel.superclass.afterRender.apply(this, arguments);
        
        this.getPortalColumn().items.each(function(item, idx) {
            // kill x-scrollers
            if (item.getEl && item.getEl()) {
                this.xsrollKiller(item);
            } else {
                item.on('afterrender', this.xsrollKiller, this);
            }
            
            //bubble state events
            item.enableBubble(['collapse', 'expand']);
        }, this);
        
        // enable vertical scrolling
        this.body.applyStyles('overflow-y: auto');
    },
    
    /**
     * applies state to cmp
     * 
     * @param {Object} state
     */
    applyState: function(state) {
        var collection = this.getPortalColumn().items,
            c = new Array(collection.getCount()), k = collection.keys, items = collection.items;
        
        Ext.each(state.order, function(position, idx) {
            c[idx] = {key: k[position], value: items[position], index: position};
        }, this);
        
        for(var i = 0, len = c.length; i < len; i++){
            items[i] = c[i].value;
            k[i] = c[i].key;
        }
        collection.fireEvent('sort', collection);
        
        collection.each(function(item, idx) {
            if (item.getEl()) {
                item[state.collapsed[idx] ? 'collapse' : 'expand'](false);
            } else {
                item.collapsed = !!state.collapsed[idx];
            }
        }, this);
        
    },
    
    /**
     * returns additional items for the westpanel
     * template fn to be overrwiten by subclasses
     * 
     * @return {Array} of Ext.Panel
     */
    getAdditionalItems: function() {
        return this.additionalItems || [];
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
     * returns the one and only portalcolumn of this west panel
     * 
     * @return {Ext.ux.PortalColumn}
     */
    getPortalColumn: function() {
        if (! this.portalColumn) {
            
            var items = [];
        
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
                        titleCollapse:true,
                        draggable : true
                    };
                }
                
                items.push(Ext.apply(this.getContainerTreePanel(), {
                    title: isContainerTreePanel ? containersName : false,
                    collapsed: isContainerTreePanel
                }, this.defaults));
                
            }
            
            if (this.hasFavoritesPanel) {
                items.unshift(Ext.apply(this.getFavoritesPanel(), {
                    title: _('Favorites')
                }, this.defaults));
            }
            
            items = items.concat(this.getAdditionalItems());
            
            // save origianl/programatical position
            // NOTE: this has to be done before applyState!
            Ext.each(items, function(item, idx) {
                item.startPosition = idx;
            }, this);
        
            this.portalColumn = new Ext.ux.PortalColumn({
                columnWidth: 1,
                items: items
            });
        }
        
        return this.portalColumn;
    },
    
    /**
     * gets state of this cmp
     */
    getState: function() {
        var state = {
            order: [],
            collapsed: []
        };
        
        this.getPortalColumn().items.each(function(item, idx) {
            state.order.push(item.startPosition);
            state.collapsed.push(item.collapsed);
        }, this);
        
        return state;
    },
    
    /**
     * inits this west panel
     */
    initComponent: function() {
        this.stateId = this.app.appName + '-mainscreen-westpanel';
        
        this.items = this.getPortalColumn();
        Tine.widgets.mainscreen.WestPanel.superclass.initComponent.apply(this, arguments);
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
