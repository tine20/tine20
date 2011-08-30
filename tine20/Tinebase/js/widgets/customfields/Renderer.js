/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.widgets.customfields');

/**
 * manages key field renderers
 * 
 * @namespace   Tine.widgets.customfields
 * @class       Tine.widgets.customfields.Renderer
 * @singleton
 */
Tine.widgets.customfields.Renderer = function(){
    var renderers = {};
    
    return {
        /**
         * returns key field record renderer
         * 
         * @param {String/Application}  app
         * @param {Record}              cfConfig 
         * @param {String}              what pipe seperated field with text|icon
         * @return Ext.data.Store
         */
        get: function(app, cfConfig, what) {
            var appName = Ext.isString(app) ? app : app.appName,
                app = Tine.Tinebase.appMgr.get(appName),
                cfDefinition = cfConfig.get('definition'),
                cfName = cfConfig.get('name'),
                what = what ? what : 'text|icon',
                whatParts = what.split('|'),
                key = appName + cfConfig.id + what;
                
            if (! renderers[key]) {
                if (['keyfield' /*, 'bool', 'boolean'*/].indexOf(Ext.util.Format.lowercase(cfDefinition.type)) > -1) {
                    // NOTE existingkeyfields might come from an other app!
                    var app = cfDefinition.options && Ext.isString(cfDefinition.options.app) ? cfDefinition.options.app : app;
                    var keyFieldName = cfDefinition.options && Ext.isString(cfDefinition.options.keyFieldName) ? cfDefinition.options.keyFieldName : cfName;
                    renderers[key] = function(customfields) {
                        return Tine.Tinebase.widgets.keyfield.Renderer.render(app, keyFieldName, customfields[cfName]);
                    };
                    
                } else {
                    renderers[key] = function(customfields) {
                        return Ext.util.Format.htmlEncode(customfields[cfName]); 
                    };
                }
            }
            
            return renderers[key];
        },
//        
//        /**
//         * render a given value
//         * 
//         * @param {String/Application}  app
//         * @param {String}              keyFieldName 
//         * @return Ext.data.Store
//         */
//        render: function(app, keyFieldName, id) {
//            var renderer = this.get(app, keyFieldName);
//            
//            return renderer(id);
//        },
//        
//        /**
//         * register a custom renderer
//         * 
//         * @param {String/Application}  app
//         * @param {String}              keyFieldName 
//         * @param {Function}            renderer
//         */
//        register: function(app, keyFieldName, renderer) {
//            var appName = Ext.isString(app) ? app : app.appName,
//                key = appName + '_' + keyFieldName;
//                
//            renderers[key] = renderer;
//        }
    }
}();
