/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

/**
 * <p>Abstract base class for all Tine applications</p>
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.Application
 * @extends     Ext.util.Observable
 * @consturctor
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.Application = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Tine.Tinebase.Application.superclass.constructor.call(this);
    
    this.i18n = new Locale.Gettext();
    this.i18n.textdomain(this.appName);
    
    this.init();
    this.initAutoHooks();
};

Ext.extend(Tine.Tinebase.Application, Ext.util.Observable , {
    
    /**
     * @cfg {String} appName
     * untranslated application name (required)
     */
    appName: null,
    
    /**
     * @cfg {Boolean} hasMainScreen
     */
    hasMainScreen: true,
    
    /**
     * @property {Locale.gettext} i18n
     */
    i18n: null,
    
    /**
     * returns title of this application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._(this.appName);
    },
    
    /**
     * returns iconCls of this application
     * 
     * @param {String} target
     * @return {String}
     */
    getIconCls: function(target) {
        return this.appName + 'IconCls';
    },
    
    /**
     * returns the mainscreen of this application
     * 
     * @return {Tine.widgets.app.MainScreen}
     */
    getMainScreen: function() {
        if (! this.mainScreen && typeof Tine[this.appName].MainScreen === 'function') {
            this.mainScreen = new Tine[this.appName].MainScreen({
                app: this
            });
        }
        
        return this.mainScreen;
    },
    
    /**
     * returns registry of this app
     * 
     * @return {Ext.util.MixedCollection}
     */
    getRegistry: function() {
        return Tine[this.appName].registry;
    },
    
    /**
     * template function for subclasses to initialize application
     */
    init: Ext.emptyFn,

    /**
     * init some auto hooks
     */
    initAutoHooks: function() {
        if (this.addButtonText) {
            Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', {
                text: this.i18n._hidden(this.addButtonText), 
                iconCls: this.getIconCls(),
                scope: this,
                handler: function() {
                    var ms = this.getMainScreen(),
                        cp = ms.getCenterPanel();
                        
                    cp.onEditInNewWindow.call(cp, {});
                }
            });
        }
    },
    
    /**
     * template function for subclasses is called before app activation. Return false to cancel activation
     */
    onBeforeActivate: Ext.emptyFn,
    
    /**
     * template function for subclasses is called after app activation.
     */
    onActivate: Ext.emptyFn,
    
    /**
     * template function for subclasses is called before app deactivation. Return false to cancel deactivation
     */
    onBeforeDeActivate: Ext.emptyFn,
    
    /**
     * template function for subclasses is called after app deactivation.
     */
    onDeActivate: Ext.emptyFn
    
});
