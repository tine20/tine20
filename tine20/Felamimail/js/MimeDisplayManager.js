/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Felamimail');

/**
 * display panel item registry per MIME type
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MimeDisplayManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.Felamimail.MimeDisplayManager = function() {
    var items = {},
        alternatives = {};
    
    return {
        /**
         * creates a new details panel for given mimeType
         * 
         * @param {String} mimeType
         * @return {Tine.widgets.grid.DisplayPanel}
         */
        create: function(mimeType, config) {
            var c = this.get(mimeType);
            
            if (! c) {
                throw new Ext.Error('No details panel registered for ' + mimeType);
            }
            
            return new c(config || {});
        },
        
        /**
         * returns basic form of MIME type if a display panel is registered for it
         * 
         * @param {String} mimeType
         * @return {String}
         */
        getMainType: function(mimeType) {
            var mainType = alternatives[mimeType];
            
            return items.hasOwnProperty(mainType) ? mainType : null;
        },
        
        /**
         * returns Display Panel for given MIME type
         * 
         * @param  {String} mimeType
         * @return {Function} consturctor function of a Tine.widgets.grid.DisplayPanel 
         */
        get: function(mimeType) {
            var mainType = this.getMainType(mimeType);
            
            return mainType ? items[mainType] : null;
        },
        
        /**
         * register Display Panel for given MIME type
         * 
         * @param {String/Array} mimeType
         * @param {Function} displayPanel consturctor function of a Tine.widgets.grid.DisplayPanel 
         * @param {Array} otherTypes
         */
        register: function(mimeType, displayPanel, otherTypes) {
            if (items.hasOwnProperty(mimeType)) {
                throw new Ext.Error('There is already an registration for ' + mimeType);
            }
            
            items[mimeType] = displayPanel;
            alternatives[mimeType] = mimeType;
            
            if (Ext.isArray(otherTypes)) {
                Ext.each(otherTypes, function(otherType) {
                    if (alternatives.hasOwnProperty(otherType)) {
                        throw new Ext.Error(alternatives[otherType] + ' is already registered as alternative for ' + otherType);
                    }
                    alternatives[otherType] = mimeType;
                }, this);
            }
        }
    };
}();