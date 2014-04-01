/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.data');

/**
 * central title renderer manager for records
 * 
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.TitleRendererManager
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @singleton
 */
Tine.Tinebase.data.TitleRendererManager = function() {
    var renderers = {};
    
    return {
        
        /**
         * returns renderer for given field
         * 
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {gridPanel|displayPanel} optional.
         * @return {Function}
         */
        get: function(appName, modelName) {
            return renderers[appName+modelName];
        },
        
        /**
         * register renderer for given field
         * 
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Function} renderer
         * @param {String} category {gridPanel|displayPanel} optional.
         * @param {Object} scope to call renderer in, optional.
         */
        register: function(appName, modelName, renderer) {
            renderers[appName+modelName] = renderer;
        },
        
        /**
         * check if a renderer is explicitly registered
         * 
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {gridPanel|displayPanel} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName) {
            // check for registered renderer
            return renderers[appName+modelName] ? true : false;
        }
    };
}();