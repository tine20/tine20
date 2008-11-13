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
        
        var app = this.apps.get(appName);
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
    getAppObj: function(appName) {
        try{
            // legacy
            if (typeof(Tine[appName].getPanel) == 'function') {
                var appPanel = Tine[appName].getPanel();
                // make a legacy Tine.Application
                // return application;
            }
            
            return new Tine[appName]();
            
        } catch(e) {
            console.error('Initialising of Application "' + appName + '" failed with the following message:' + e);
            console.warn(e);
            return false;
        }
    }
});