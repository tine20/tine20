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

Tine.Tinebase.AppPicker = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {Array} appPanels
     * @legacy
     * ordered list of appPanels
     */
    appPanels: [],
    /**
     * @cfg {Ext.Panel} defaultAppPanel
     * @legacy
     */
    defaultAppPanel: null,
    
    layout: 'border',
    border: false,
    
    initComponent: function() {
        this.initAppPile();
        
        this.initLayout();
        Tine.Tinebase.AppPicker.superclass.initComponent.call(this);
        
        document.title = 'Tine 2.0 - ' + this.defaultAppPanel.title;
    },
    
    
    initAppPile: function() {
        // legacy: init app pile and cards
        var appPileItems = [];
        var activeItem;
        
        var appPanel, appItem;
        for (var i=0; i<this.appPanels.length; i++) {
            appPanel = this.appPanels[i];
            //console.log(appPanel.header);
            //appPanel.header = false;
            appPanel.on('render', function(p) {
                p.header.remove()
                //p.header.hide();
                p.doLayout();
                //console.log(p);
                //p.layout.layout();
            });
            
            appItem = {
                appName: appPanel.appName,
                iconCls: appPanel.iconCls,
                title: appPanel.title,
                panel: appPanel
            }
            
            appPileItems.push(appItem);
            if (appPanel == this.defaultAppPanel) {
                activeItem = appItem;
            }
        }
        
        this.appPile = new Tine.Tinebase.AppPile({
            appItems: appPileItems,
            activeItem: activeItem
        });
        
        this.appPile.on('showapplication', function(appItem) {
            var cp = this.getTreeCardPanel();
            var tp = this.items.get(0);
            
            cp.layout.setActiveItem(appItem.panel);
            tp.body.dom.innerHTML = '<div class ="app-panel-title">' + appItem.title + '</div>'
            
            // update domument title
            document.title = 'Tine 2.0 - ' + appItem.title;
            
            // legacy: fire beforeexpand
            appItem.panel.fireEvent('beforeexpand', appItem.panel);
        }, this);
    },
    
    initLayout: function() {
        this.items = [{
            region: 'north',
            layout: 'fit',
            border: false,
            height: 40,
            baseCls: 'x-panel-header',
            html: '<div class ="app-panel-title">' + this.defaultAppPanel.title + '</div>'
        }, {
            region: 'center',
            layout: 'card',
            border: false,
            activeItem: this.defaultAppPanel,
            items: this.appPanels
        }, {
            region: 'south',
            layout: 'fit',
            border: false,
            height: this.appPanels.length * 24,
            items: this.appPile
        }];
    },
    
    getTreeCardPanel: function() {
        return this.items.get(1);
    }
});

Tine.Tinebase.AppPile = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Array} appItems
     * ordered list off app arrays (appName/title/iconCls)
     */
    appItems: [],
    /**
     * @cfg {Object} activeItem
     * active app abject
     */
    activeItem: null,
    
    // private
    border: false,
    
    /*
    setTitle: function(appName, title) {
        
    },
    
    setIconCls: function(appName, icon) {
        
    },
    */
    
    initComponent: function() {
        Tine.Tinebase.AppPile.superclass.initComponent.call(this);
        
        this.tpl = new Ext.XTemplate(
            '<div class="x-panel-header x-panel-header-noborder x-unselectable x-accordion-hd">',
                '<img class="x-panel-inline-icon {iconCls}" src="' + Ext.BLANK_IMAGE_URL + '"/>',
                '<span class="x-panel-header-text">{title}</span>',
            '</div>'
        ).compile();
    },
    
    onRender: function(ct, position) {
        Tine.Tinebase.AppPile.superclass.onRender.call(this, ct, position);
        
        var item;
        for (var i=this.appItems.length; i>0; i--) {
            item = this.appItems[i-1];
            item.el = this.tpl.insertFirst(this.bwrap, item, true);
            item.el.setStyle('cursor', 'pointer');
            item.el.addClassOnOver('app-panel-header-over');
            item.el.on('click', this.onAppTitleClick, this, item);
        }
        
        this.setActiveItem(this.activeItem);
    },
    
    onAppTitleClick: function(e, dom, item) {
        this.setActiveItem(item);
        this.fireEvent('showapplication', item);
    },
    
    setActiveItem: function(item) {
        var current;
        for (var i=0; i<this.appItems.length; i++) {
            current = this.appItems[i];
            if (item == current) {
                current.el.addClass('app-panel-header-active');
            } else {
                current.el.removeClass('app-panel-header-active');
            }
        }
    }
});