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
 
Ext.ns('Tine.Tinebase.widgets.app');

Tine.Tinebase.widgets.app.MainScreen = function(config) {
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
    Tine.Tinebase.widgets.app.MainScreen.superclass.constructor.call(this);
};

Ext.extend(Tine.Tinebase.widgets.app.MainScreen, Ext.util.Observable, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    
    /**
     * @property {Locale.Gettext} i18n
     */
    
    /**
     * shows/activates this app mainscreen
     * 
     * @return {Tine.Tinebase.widgets.app.MainScreen} this
     */
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.setNorthPanel();
            this.setWestPanel();
            this.setCenterPanel();
            
            this.fireEvent('show', this);
        }
        return this;
    },
    
    onHide: function() {
        
    },
    
    getTitle: function() {
        return this.i18n._(this.appName);
    },
    
    /**
     * sets title in mainscreen
     */
    setTitle: function(title) {
        this.mainScreen.setTitle(this, appName, this.i18n._(this.appName));
    },
    
    /**
     * sets west panel in mainscreen
     */
    setWestPanel: function() {
        
    },
    
    /**
     * sets center panel in mainscreen
     */
    setCenterPanel: function() {
        
    },
    
    /**
     * sets north panel in mainscreen
     */
    setNorthPanel: function() {
        
    }
    
});