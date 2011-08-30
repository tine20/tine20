/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

Tine.widgets.customfields.ConfigManager = function() {
    var stores = {};
    
    var getStore = function(app) {
        app = Tine.Tinebase.appMgr.get(app);
        if (! stores[app.appName]) {
            var allCfs = app.getRegistry().get('customfields');
            stores[app.appName] = new Ext.data.JsonStore({
                fields: Tine.Tinebase.Model.Customfield,
                data: allCfs ? allCfs : []
            });
        }
        
        return stores[app.appName];
    };
    
    return {
        /**
         * returns a single field config
         * 
         * @param {String/Application}  app
         * @param {String}              model
         * @param {String}              name
         * @return {Record}
         */
        getConfig: function (app, model, name) {
            var cfStore = getStore(app),
                cfConfig = null;
            
            cfStore.clearFilter(true);
            cfStore.filter('model', model);
            cfConfig = cfStore.find('name', name);
            cfConfig = cfConfig > -1 ? cfStore.getAt(cfConfig): null;
            cfStore.clearFilter(true);
            
            return cfConfig;
            
        },
        
        /**
         * returns a single field config
         * 
         * @param {String/Application}  app
         * @param {String}              model
         * @return {Array}
         */
        getConfigs: function(app, model) {
            var cfStore = getStore(app),
                cfConfigs = [];
            
            cfStore.clearFilter(true);
            cfStore.filter('model', model);
            cfStore.each(function(r) {cfConfigs.push(r);});
            cfStore.clearFilter(true);
            
            return cfConfigs;
        }
    }
}();