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
 
Ext.ns('Tine.Tinebase');

Tine.Tinebase.AppManager = function() {
    /**
     * @property {Ext.util.MixedCollection} apps
     * enabled apps
     */
    this.apps = new Ext.util.MixedCollection({});
    
    // fill this.apps with registry data
    var enabledApps = Tine.Tinebase.registry.get('userApplications');
    var app;
    for(var i=0; i<enabledApps.length; i++) {
        app = enabledApps[i];

        //we don't have a Tinebase 'Application'
        if (app.name == 'Tinebase') {
            continue;
        }
            
        app.appName = app.name;
        app.isInitialised = false;
        this.apps.add(app.appName, app);
    }
};

Ext.extend(Tine.Tinebase.AppManager, {
    
    /**
     * returns an appObject
     * 
     * @param {String} appName
     * @return {Tine.Application}
     */
    get: function(appName) {
        if (! this.isEnabled(appName)) {
            return false;
        }
        
        var app = this.apps.get(app);
        if (! app.isInitialised) {
            var appObj = this.getAppObj(appName);
            appObj.isInitialised = true;
            Ext.applyIf(appObj, app);
            this.apps.replace(appName, appObj);
        }
        
        return this.apps.get(appName);
    },
    
    /**
     * returns a list of all apps for current user
     */
    getAll: function() {
        this.initAll();
        return this.apps;
    },
    
    /**
     * checks wether a given app is enabled for current user or not
     */
    isEnabled: function(appName) {
        var app = this.apps.get(appName);
        return app ? app.status == 'enabled' : false;
    },
    
    /**
     * initialises all enabled apps
     * @private
     */
    initAll: function() {
        this.apps.each(function(app) {
            this.get(app.appName);
        }, this);
    },
    
    /**
     * @private
     */
    getAppObj: function(app) {
        try{
            // legacy
            if (typeof(Tine[app.appName].getPanel) == 'function') {
                var appPanel = Tine[app.appName].getPanel();
                // make a legacy Tine.Application
                return this.getLegacyApp(app, appPanel);
            }
            
            return new Tine[app.appName](app);
            
        } catch(e) {
            console.error('Initialising of Application "' + app.appName + '" failed with the following message:' + e);
            console.warn(e);
            return false;
        }
    },
    
    /**
     * @private
     */
    getLegacyApp: function(app, appPanel) {
        var appObj =  new Tine.Tinebase.Application(app);
        var mainScreen = new Tine.Tinebase.widgets.app.MainScreen(appObj);
        
        Ext.override(mainScreen, {
            appPanel: appPanel,
            show: function() {
                Tine.Tinebase.MainScreen.setActiveTreePanel(this.appPanel, true);
                this.appPanel.fireEvent('beforeexpand', this.appPanel);
            }
        });
        
        Ext.override(appObj, {
            mainScreen: mainScreen
        });
    }
});