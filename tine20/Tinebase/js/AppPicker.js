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
     * @cfg {Ext.util.Observable} apps (required)
     */
    apps: null,
    
    /**
     * @cfg {String} defaultAppName (required)
     */
    defaultAppName: '',
    
    layout: 'border',
    border: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.appTitle = this.apps.get(this.defaultAppName).getTitle();
        
        this.initLayout();
        Tine.Tinebase.AppPicker.superclass.initComponent.call(this);
    },
    
    initLayout: function() {
        this.items = [{
            region: 'north',
            layout: 'fit',
            border: false,
            height: 40,
            baseCls: 'x-panel-header',
            html: '<div class ="app-panel-title">' + this.getAppTitle() + '</div>'
        }, {
            region: 'center',
            layout: 'card',
            border: false
        }, new Tine.Tinebase.AppPile({
            split: true,
            width: 200,
            collapsible:true,
            collapseMode: 'mini',
            header: false,
            region: 'south',
            apps: this.apps,
            defaultAppName: this.defaultAppName,
            scope: this,
            handler: function(app) {
                this.setAppTitle(app.getTitle());
                app.getMainScreen().show();
            }})
        ];
    },
    
    setAppTitle: function(appTitle) {
        this.appTitle = appTitle;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        document.title = Tine.title + postfix + ' - ' + appTitle;
        this.items.get(0).body.dom.innerHTML = '<div class ="app-panel-title">' + appTitle + '</div>';
    },
    
    getAppTitle: function() {
        return this.appTitle;
    },
    
    getTreeCardPanel: function() {
        return this.items.get(1);
    }
});

Tine.Tinebase.AppPile = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Ext.util.Observable} apps (required)
     */
    apps: null,
    /**
     * @cfg {String} defaultAppName (required)
     */
    defaultAppName: '',
    /**
     * @cfg {Object} scope
     * scope hander is called int
     */
    scope: null,
    /**
     * @cfg {Function} handler
     * click handler of apps
     */
    handler: null,
    
    /**
     * @private
     * @property {Object} items
     * holds internal item elements
     */
    els: {},
    
    /**
     * @private
     */
    border: false,
    layout: 'fit',
    autoScroll: true,
    
    /**
     * @private
     * @todo: register app.on('titlechange', ...)
     */
    initComponent: function() {
        Tine.Tinebase.AppPile.superclass.initComponent.call(this);
        
        this.tpl = new Ext.XTemplate(
            '<div class="x-panel-header x-panel-header-noborder x-unselectable x-accordion-hd">',
                '<img class="x-panel-inline-icon {iconCls}" src="' + Ext.BLANK_IMAGE_URL + '"/>',
                '<span class="x-panel-header-text app-panel-apptitle-text">{title}</span>',
            '</div>'
        ).compile();

    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Tinebase.AppPile.superclass.onRender.call(this, ct, position);

        this.apps.sort("ASC", function(app1, app2) {
            return parseInt(app1.order, 10) < parseInt(app2.order, 10) ? 1 : -1;
        });
        
        this.apps.each(function(app) {
            this.els[app.appName] = this.tpl.insertFirst(this.body, {title: app.getTitle(), iconCls: app.getIconCls()}, true);
            this.els[app.appName].setStyle('cursor', 'pointer');
            this.els[app.appName].addClassOnOver('app-panel-header-over');
            this.els[app.appName].on('click', this.onAppTitleClick, this, app);
            
        }, this);
        
        // limit to max pile height
        this.on('resize', function() {
            var appHeaders = Ext.DomQuery.select('div[class^=x-panel-header]', this.el.dom);
            for (var i=0, height=0; i<appHeaders.length; i++) {
                height += Ext.fly(appHeaders[i]).getHeight();
            }
            if (arguments[2] && arguments[2] > height) {
                this.setHeight(height);
            }
        });
        this.setActiveItem(this.els[this.defaultAppName]);
    },
    
    /**
     * @private
     */
    onAppTitleClick: function(e, dom, app) {
        this.setActiveItem(Ext.get(dom));
        this.handler.call(this.scope|| this, app);
    },
    
    /**
     * @private
     */
    setActiveItem: function(el) {
        for (var appName in this.els) {
            if (el == this.els[appName] || el.parent() == this.els[appName]) {
                this.els[appName].addClass('app-panel-header-active');
            } else {
                this.els[appName].removeClass('app-panel-header-active');
            }
        }
    }
});