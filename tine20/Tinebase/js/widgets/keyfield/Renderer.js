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
         * @param {String}              what pipe seperated field with text|icon
         * @return Ext.data.Store
         */
        get: function(app, keyFieldName, what) {
            var appName = Ext.isString(app) ? app : app.appName,
                app = Tine.Tinebase.appMgr.get(appName),
                store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(app, keyFieldName),
                what = what ? what : 'text|icon',
                whatParts = what.split('|'),
                key = appName + keyFieldName + what;
                
            if (! renderers[key]) {
                renderers[key] = function(id) {
                    if (! id) return "";
                    var record = store.getById(id),
                        i18nValue = record ? record.get('i18nValue') : app.i18n._hidden(id),
                        icon = record ? record.get('icon') : null,
                        string = '';
                        
                    if (whatParts.indexOf('icon') > -1 && icon) {
                        string = string + '<img src="' + icon + '" class="tine-keyfield-icon" ext:qtip="' + Ext.util.Format.htmlEncode(i18nValue) + '" />';
                    }
                        
                    if (whatParts.indexOf('text') > -1 && i18nValue) {
                        string = string + Ext.util.Format.htmlEncode(i18nValue);
                    }
                    
                    return string;
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
