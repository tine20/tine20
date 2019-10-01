/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

Tine.widgets.customfields.ConfigManager = function() {
    var stores = {};
    var idMap = {};
    
    var getStore = function(app) {
        app = Tine.Tinebase.appMgr.get(app);
        if (! stores[app.appName]) {
            var _ = window.lodash,
                allCfs = (Ext.isFunction(app.getRegistry)) ? app.getRegistry().get('customfields') : null;

            // set defaults -- uiconfig are empty strings :-(
            _.each(allCfs, function(cfConfig) {
                _.each({tab: 'customfields', 'group': '', sort: 0}, function(defaultValue, field) {
                    if (_.get(cfConfig, 'definition.uiconfig.' + field, '') == '') {
                        _.set(cfConfig, 'definition.uiconfig.' + field, defaultValue);
                    }
                });
            });

            stores[app.appName] = new Ext.data.JsonStore({
                fields: Tine.Tinebase.Model.Customfield,
                data: allCfs ? allCfs : []
            });
            
            // place customefield keyFieldConfig in registry so we can use the standard widgets
            // keyfields are used for key/value customfields with a defined store 
            stores[app.appName].each(function(cfConfig) {
                idMap[cfConfig.id] = cfConfig;
                var definition = cfConfig.get('definition'),
                    options = definition.options ? definition.options : {},
                    keyFieldConfig = definition.keyFieldConfig ? definition.keyFieldConfig : null;
                    
                if (keyFieldConfig) {
                    var config = app.getRegistry().get('config');
                    config[cfConfig.get('name')] = keyFieldConfig;
                    app.getRegistry().set('config', config);
                }
            });
        }
        
        return stores[app.appName];
    };
    
    /**
     * convert config record to record field
     * 
     * @param  {Record} cfConfig
     * @return {Ext.data.Field} field definition
     */
    var config2Field = function(cfConfig) {
        var def = cfConfig.get('definition');
        
        return new Ext.data.Field(Ext.apply({
            name: '#' + cfConfig.get('name')
        }, def));
    };
    
    return {
        /**
         * returns single field config by id
         *
         * @param customfieldId
         * @param asField
         * @return {Record}
         */
        getById: function(customfieldId, asField) {
            var cfConfig = idMap[customfieldId];

            return asField ? config2Field(cfConfig) : cfConfig;
        },

        /**
         * returns a single field config
         * 
         * @param {String/Application}  app
         * @param {String}              model
         * @param {String}              name
         * @param {Boolean}             asField
         * @return {Record}
         */
        getConfig: function (app, model, name, asField) {
            var cfStore = getStore(app),
                cfConfig = null;

            model = model.match(/_Model_/) ? model : (app.appName ? app.appName : app) + '_Model_' + model;
            
            cfStore.clearFilter(true);
            cfStore.filter('model', model);
            cfConfig = cfStore.findExact('name', name);
            cfConfig = cfConfig > -1 ? cfStore.getAt(cfConfig): null;
            cfStore.clearFilter(true);
            
            return asField ? config2Field(cfConfig) : cfConfig;
            
        },
        
        /**
         * returns a single field config
         * 
         * @param {String/Application}  app
         * @param {String}              model
         * @param {Boolean}             asFields
         * @return {Array}
         */
        getConfigs: function(app, model, asFields) {
            if (Ext.isFunction(model.getMeta)) {
                model = model.getMeta('appName') + '_Model_' + model.getMeta('modelName');
            }
            
            var cfStore = getStore(app),
                cfConfigs = [];
            
            cfStore.clearFilter(true);
            cfStore.filter('model', model);
            cfStore.each(function(r) {cfConfigs.push(r);});
            cfStore.clearFilter(true);
            
            if (asFields) {
                Ext.each(cfConfigs, function(cfConfig, idx) {
                    cfConfigs[idx] = config2Field(cfConfig);
                }, this);
            }
            
            return cfConfigs;
        }
    }
}();