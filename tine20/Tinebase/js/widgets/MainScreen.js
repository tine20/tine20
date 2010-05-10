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
     * @cfg {Function} westPanelXType
     * constructor of westpanel class 
     */
    westPanelXType: 'tine.widgets.mainscreen.westpanel',
    
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
                //plugins: [this.getContainerTreePanel().getFilterPlugin()]
                plugins: [this.getWestPanel().getFilterPlugin(contentType)]
            });
        }
        
        return this[contentType + this.centerPanelClassNameSuffix];
    },
    
    /**
     * convinience fn to get container tree panel from westpanel
     * 
     * @return {Tine.widgets.container.containerTreePanel}
     *
    getContainerTreePanel: function() {
        return this.getWestPanel().getContainerTreePanel();
    },
    */
    
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
            this.westPanel = Ext.ComponentMgr.create({
                app: this.app
            }, this.westPanelXType);
        }
        
        return this.westPanel;
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
