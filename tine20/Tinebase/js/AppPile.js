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


Tine.Tinebase.AppPile = Ext.extend(Ext.Panel, {
    /**
     * @property apps
     * @type Ext.util.Observable
     */
    apps: null,
    
    /**
     * @property defaultApp
     * @type Tine.Application
     */
    defaultApp: null,
    
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
        this.apps = Tine.Tinebase.appMgr.getAll();
        this.defaultApp = Tine.Tinebase.appMgr.getDefault();
        
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
        this.setActiveItem(this.els[this.defaultApp.appName]);
    },
    
    /**
     * @private
     */
    onAppTitleClick: function(e, dom, app) {
        this.setActiveItem(Ext.get(dom));
        Tine.Tinebase.appMgr.activate(app);
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