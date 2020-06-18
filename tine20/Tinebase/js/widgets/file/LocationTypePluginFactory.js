/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file');

Tine.Tinebase.widgets.file.LocationTypePluginFactory = (function() {
    const pluginFactories = {
        'download': async function(config) {
            return import('./LocationTypePlugin/Download').then(() => {
                return new Tine.Tinebase.widgets.file.LocationTypePlugin.Download(config);
            });
        },
        'upload': async function(config) {
            return import('./LocationTypePlugin/Upload').then(() => {
                return new Tine.Tinebase.widgets.file.LocationTypePlugin.Upload(config);
            });
        }
    };
    
    return {
        isRegistered: function(type) {
            return pluginFactories.hasOwnProperty(type);
        },
        
        register: function(type, factory) {
            pluginFactories[type] = factory;
        },

        create: async function(type, config) {
            const factory = pluginFactories[type];
            return await factory(config);
        }
    }
})();
