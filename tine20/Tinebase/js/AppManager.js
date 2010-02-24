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
        
        // if the app is not in the namespace, we don't initialise it
        // we don't have a Tinebase 'Application'
        if (Tine[app.name] && ! app.name.match(/(Tinebase|ActiveSync)/)) {
            app.appName = app.name;
            app.isInitialised = false;
            
            this.apps.add(app.appName, app);
        }
    }
};

Ext.apply(Tine.Tinebase.AppManager.prototype, {
    /**
     * @cfg {Tine.Application}
     */
    defaultApp: null,
    
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
            var appObj = this.getAppObj(app);
            appObj.isInitialised = true;
            Ext.applyIf(appObj, app);
            this.apps.replace(appName, appObj);
        }
        
        return this.apps.get(appName);
    },
    
    /**
     * returns appObject of default app
     * 
     * @return {Tine.Application}
     */
    getDefault: function() {
        if (! this.defaultApp) {
            var defaultAppName = (Tine.Tinebase.registry.get('preferences') && Tine.Tinebase.registry.get('preferences').get('defaultapp')) 
                ? Tine.Tinebase.registry.get('preferences').get('defaultapp') 
                : this.defaultAppName;
                
            this.defaultApp = this.get(defaultAppName) || this.apps.first();
        }
        
        return this.defaultApp;
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
                // make a legacy Tine.Application
                return this.getLegacyApp(app);
            }
            
            return typeof(Tine[app.appName].Application) == 'function' ? new Tine[app.appName].Application(app) : new Tine.Tinebase.Application(app);
            
        } catch(e) {
            console.error('Initialising of Application "' + app.appName + '" failed with the following message:' + e);
            console.warn(e);
            return false;
        }
    },
    
    /**
     * @private
     */
    getLegacyApp: function(app) {
        var appPanel = Tine[app.appName].getPanel();
        var appObj =  new Tine.Tinebase.Application(app);
        var mainScreen = new Tine.Tinebase.widgets.app.MainScreen(appObj);
        
        Ext.apply(mainScreen, {
            appPanel: appPanel,
            show: function() {
                Tine.Tinebase.MainScreen.setActiveTreePanel(appPanel, true);
                appPanel.fireEvent('beforeexpand', appPanel);
            }
        });
        Ext.apply(appObj, {
            mainScreen: mainScreen
        });
        appPanel.on('render', function(p) {
            p.header.remove();
            // additionally to removing the DOM node, we also need to reset the 
            // header class variable, as IE evals "if (this.header)" to true otherwise 
            p.header = false;
            p.doLayout();
        });
        
        return appObj;
    }
});