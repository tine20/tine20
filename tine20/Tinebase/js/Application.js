/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Tinebase');

/**
 * @class Tine.Tinebase.Application
 * @extends Ext.util.Observable
 * @consturctor
 * <p>Abstract base class for all Tine applications</p>
 */
Tine.Tinebase.Application = function(config) {
    config = config || {}
    Ext.apply(this, config);
    
    Tine.Tinebase.Application.superclass.constructor.call(this);
    
    this.i18n = new Locale.Gettext();
    this.i18n.textdomain(this.appName);
};

Ext.extend(Tine.Tinebase.Application, Ext.util.Observable , {
    
    /**
     * @cfg {String} appName
     * untranslated application name (requierd)
     */
    appName: null,
    
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
    }
});
