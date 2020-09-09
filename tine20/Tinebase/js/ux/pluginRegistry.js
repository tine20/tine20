/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Ext.ux');

/**
 * plugin registry for Ext.Component
 *
 * NOTE: a component needs to query this registry for registered plugins! We don't automatically query
 *       this registry for performance reasons.
 */
Ext.ux.pluginRegistry = function(){
    var map = {};
    return {
        /**
         * register plugin
         *
         * @param name
         * @param plugin
         */
        register: function(name, plugin) {
            if (! map[name]) {
                map[name] = [];
            }

            map[name].push(plugin);
        },

        /**
         * get registered plugins
         *
         * @param name
         * @return {Array}
         */
        get: function(name) {
            return map[name] || [];
        },

        /**
         * adds registered plugins for given component. helper fn to be called during initComponent in a specific component which
         * want's to use this pluginRegistry
         *
         * @param {Ext.Component} cmp
         * @param {String} name optional, defaults to canonical name
         */
        addRegisteredPlugins: function(cmp, name) {
            var name = name || Tine.Tinebase.CanonicalPath.getPath(cmp),
                existingPlugins = cmp.plugins || [],
                registeredPlugins = this.get(name);

            cmp.plugins = existingPlugins.concat(registeredPlugins);
            return cmp.plugins;
        }
    };
}();