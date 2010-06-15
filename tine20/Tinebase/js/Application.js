/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
 * @version     $Id$
 */
Tine.Tinebase.Application = function(config) {
    config = config || {};
    Ext.apply(this, config);
    
    Tine.Tinebase.Application.superclass.constructor.call(this);
    
    this.i18n = new Locale.Gettext();
    this.i18n.textdomain(this.appName);
    
    this.init();
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
     * @return {String}
     */
    getIconCls: function() {
        return this.appName + 'IconCls';
    },
    
    /**
     * returns the mainscreen of this application
     * 
     * @return {Tine.widgets.app.MainScreen}
     */
    getMainScreen: function() {
        if (!this.mainScreen) {
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
     * template function for subclasses is called before app activation. Return false to cancle activation
     */
    onBeforeActivate: Ext.emptyFn,
    
    /**
     * template function for subclasses is called before app deactivation. Return false to cancle deactivation
     */
    onBeforeDeActivate: Ext.emptyFn
    
});
