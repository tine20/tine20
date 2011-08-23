/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * manages key field renderers
 * 
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @class       Tine.Tinebase.widgets.keyfield.Renderer
 * @singleton
 */
Tine.Tinebase.widgets.keyfield.Renderer = function(){
    var renderers = {};
    
    return {
        /**
         * returns key field record renderer
         * 
         * @param {String/Application}  app
         * @param {String}              keyFieldName 
         * @return Ext.data.Store
         */
        get: function(app, keyFieldName) {
            var appName = Ext.isString(app) ? app : app.appName,
                app = Tine.Tinebase.appMgr.get(appName),
                key = appName + '_' + keyFieldName;
                
            if (! renderers[key]) {
                renderers[key] = function(id) {
                    var store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(app, keyFieldName),
                        record = store.getById(id);
                    
                    return Ext.util.Format.htmlEncode(record ? record.get('i18nValue') : i18n._hidden(id));
                }
            }
            
            return renderers[key];
        },
        
        /**
         * render a given value
         * 
         * @param {String/Application}  app
         * @param {String}              keyFieldName 
         * @return Ext.data.Store
         */
        render: function(app, keyFieldName, id) {
            var renderer = this.get(app, keyFieldName);
            
            return renderer(id);
        },
        
        /**
         * register a custom renderer
         * 
         * @param {String/Application}  app
         * @param {String}              keyFieldName 
         * @param {Function}            renderer
         */
        register: function(app, keyFieldName, renderer) {
            var appName = Ext.isString(app) ? app : app.appName,
                key = appName + '_' + keyFieldName;
                
            renderers[key] = renderer;
        }
    }
}();
