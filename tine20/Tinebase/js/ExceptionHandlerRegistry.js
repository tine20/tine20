/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Tinebase');

/**
 * @namespace Tine.Tinebase
 * @class Tine.Tinebase.ExceptionHandlerRegistry
 * @singleton
 * 
 * 
 */
Tine.Tinebase.ExceptionHandlerRegistry = function() {
    return {
        
        items: {},
        
        /**
         * registers a handler
         * 
         * @param {String} appName
         * @param {Function} handler
         */
        register: function(appName, handler) {
            this.items[appName] = handler;
        },
        
        /**
         * returns a handler for an application
         * 
         * @param {String} appName
         * @return {Function}
         */
        get: function(appName) {
            if (this.items.hasOwnProperty(appName)) {
                return this.items[appName];
            }

            return null;
        },
        
        /**
         * checks if a application exception handler has been registered already
         * 
         * @param {String} appName
         * @return {Bool}
         */
        has: function(appName) {
            if (this.items.hasOwnProperty(appName)) {
                return true;
            }
            
            return false;
        }
    }
}();
