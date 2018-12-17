/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.QuickLookRegistry
 * @singleton
 *
 * @todo think about adding a generalized registry / @see Tine.Tinebase.ExceptionHandlerRegistry and others
 */
Tine.Filemanager.QuickLookRegistry = function() {
    return {

        items: null,

        /**
         * registers a handler
         *
         * @param {String} contentType
         * @param {String} xtype panel xtype
         */
        registerContentType: function(contentType, xtype) {
            this.register('contentTypes', contentType, xtype);
        },

        /**
         * registers a handler
         *
         * @param {String} extension
         * @param {String} xtype panel xtype
         */
        registerExtension: function(extension, xtype) {
            this.register('extensions', extension, xtype);
        },

        /**
         * registers a handler
         *
         * @param {String} type
         * @param {String} key
         * @param {String} value
         */
        register: function(type, key, value) {
            this.initItems();
            this.items[type][key] = value;
            Tine.Filemanager.registry.set('quickLookRegistry', this.items);
        },

        /**
         * returns a xtype for a contentType
         *
         * @param {String} contentType
         * @return {String}
         */
        getContentType: function(contentType) {
            return this.get('contentTypes', contentType);
        },

        /**
         * returns a xtype
         *
         * @param {String} extension
         * @param {String} xtype panel xtype
         */
        getExtension: function(extension, xtype) {
            return this.get('extensions', extension, xtype);
        },

        /**
         * returns a xtype for a key
         *
         * @param {String} type
         * @param {String} key
         * @return {String}
         */
        get: function(type, key) {
            this.initItems();
            if (this.items[type].hasOwnProperty(key)) {
                return this.items[type][key];
            }

            return null;
        },

        /**
         * checks if an extension item has been registered already
         *
         * @param {String} extension
         * @param {String} xtype panel xtype
         */
        hasExtension: function(extension, xtype) {
            return this.has('extensions', extension, xtype);
        },

        /**
         * checks if an item has been registered already
         *
         * @param {String} contentType
         * @return {Bool}
         */
        hasContentType: function(contentType) {
            return this.has('contentTypes', contentType);
        },

        /**
         * checks if an item has been registered already
         *
         * @param {String} type
         * @param {String} key
         * @return {Bool}
         */
        has: function(type, key) {
            this.initItems();
            if (this.items[type].hasOwnProperty(key)) {
                return true;
            }
            
            return false;
        },

        /**
         * fetch items from Filemanager registry
         */
        initItems: function() {
            if (! this.items) {
                this.items = Tine.Filemanager.registry.get('quickLookRegistry') || {
                    contentTypes: {},
                    extensions: {}
                };
            }
        }
    }
}();
