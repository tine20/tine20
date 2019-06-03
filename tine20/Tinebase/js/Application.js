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

    this.hasMainScreen = Ext.isBoolean(this.hasMainScreen) ? this.hasMainScreen :
        (this.getMainScreen != Tine.Tinebase.Application.prototype.getMainScreen ||
        typeof Tine[this.appName].MainScreen === 'function');

    this.i18n = new Locale.Gettext();
    this.i18n.textdomain(this.appName);

    this.init();
    if (Tine.CoreData && Tine.CoreData.Manager) {
        Tine.log.debug('Tine.Tinebase.Application - register core data for ' + this.appName)
        this.registerCoreData.defer(500, this);
    }
    this.initAutoHooks();
    this.initRoutes();
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
    hasMainScreen: null,

    /**
     * @cfg {Object} routes
     */
    routes: null,

    /**
     * @cfg {String} defaultRoute
     */
    defaultRoute : 'mainscreen',

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

    formatMessage: function(template) {
        arguments[0] = this.i18n._hidden(template);
        return formatMessage.apply(formatMessage, arguments);
    },

    /**
     * returns iconCls of this application
     * 
     * @param {String} target
     * @return {String}
     */
    getIconCls: function(target) {
        return 'ApplicationIconCls ' + this.appName + 'IconCls';
    },
    
    /**
     * returns the mainscreen of this application
     * 
     * @return {Tine.widgets.app.MainScreen}
     */
    getMainScreen: function() {
        if (this.hasMainScreen && !this.mainScreen) {
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
     * returns true if a specific feature is enabled for this application
     * 
     * @param {String} featureName
     * @return {Boolean}
     */
    featureEnabled: function(featureName) {
        var featureConfig = Tine[this.appName].registry.get("config").features,
            result = featureConfig && featureConfig.value[featureName];

        if (result == undefined) {
            // check defaults if key is missing
            result = featureConfig
                && featureConfig.definition
                && featureConfig.definition['default']
                && featureConfig.definition['default'][featureName];
        }

        return result;
    },
    
    /**
     * template function for subclasses to initialize application
     */
    init: Ext.emptyFn,

    /**
     * template function for subclasses to register app core data
     */
    registerCoreData: Ext.emptyFn,

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

    initRoutes: function() {
        var route, action;

        if (this.routes) {
            for (route in this.routes) {
                if (this.routes.hasOwnProperty(route)) {
                    action = this.routes[route];
                    if (Ext.isString(action) && Ext.isFunction(this[action])) {
                        action = this[action].createDelegate(this);
                    }
                    if (route[0] != '/') {
                        route = '/' + this.appName + '/' + route;
                    }
                    Tine.Tinebase.router.on(route, action);
                }
            }
        }

        // default mainscreen route
        if (!this.routes || !this.routes['']) {
            var me = this;
            Tine.Tinebase.router.on('/' + this.appName, function() {
                Tine.Tinebase.MainScreenPanel.show(me);
            });
        }
    },

    /**
     * @param {String} action
     * @param {Array} params
     */
    dispatchRoute: function(action, params) {
        var route, methodName, paramNames;

        if (this.routes) {
            for (route in this.routes) {
                if (this.routes.hasOwnProperty(route)) {
                    paramNames = route.split('/');
                    if (action == paramNames.shift()) {
                        methodName = this.routes[route].action;
                        break;
                    }
                }
            }
        }

        if (methodName) {
            // @TODO validate parameters according to docs

            return this[methodName].apply(this, params);
        }

        Ext.MessageBox.show(Ext.apply(defaults, {
            title: i18n._('Not Supported'),
            msg: i18n._('Your request is not supported by this version.'),
            fn: function() {
                Tine.Tinebase.common.reload();
            }
        }));
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

Tine.Tinebase.featureEnabled = function(featureName) {
    // need to create a "dummy" app to call featureEnabled()
    var tinebaseApp = new Tine.Tinebase.Application({
        appName: 'Tinebase'
    });
    return tinebaseApp.featureEnabled(featureName);
};