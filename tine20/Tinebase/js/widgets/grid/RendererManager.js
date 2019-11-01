/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

require('./AttachmentRenderer');
require('./ImageRenderer');
require('./jsonRenderer');

/**
 * central renderer manager
 * - get renderer for a given field
 * - register renderer for a given field
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.RendererManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.grid.RendererManager = function() {
    var renderers = {};
    
    return {
        /**
         * const for category gridPanel
         */
        CATEGORY_GRIDPANEL: 'gridPanel',
        
        /**
         * const for category displayPanel
         */
        CATEGORY_DISPLAYPANEL: 'displayPanel',
        
        /**
         * default renderer - quote content
         */
        defaultRenderer: function(value) {
            return value ? Ext.util.Format.htmlEncode(value) : '';
        },
        
        /**
         * get renderer of well known field names
         * 
         * @param {String} fieldName
         * @return Function/null
         */
        getByFieldname: function(fieldName) {
            var renderer = null;
            
            if (fieldName == 'tags') {
                renderer = Tine.Tinebase.common.tagsRenderer;
            } else if (fieldName == 'notes') {
                // @TODO
                renderer = function(value) {return value ? i18n._('has notes') : '';};
            } else if (fieldName == 'relations') {
                renderer = Tine.Tinebase.common.relationsRenderer;
            } else if (fieldName == 'customfields') {
                // @TODO
                // we should not come here!
            } else if (fieldName == 'container_id') {
                renderer = Tine.Tinebase.common.containerRenderer;
            } else if (fieldName == 'attachments') {
                renderer = Tine.widgets.grid.attachmentRenderer;
            }
            
            return renderer;
        },

        /**
         * get renderer by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {boolean} cf
         * @return {Function}
         */
        getByDataType: function (appName, modelName, fieldName, cf = false) {
            if(cf){
                var cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(appName, modelName, fieldName.replace(/^#/,''));
                return Tine.widgets.customfields.Renderer.get(appName, cfConfig);
            } else {
                var renderer = null,
                    recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                    fieldDefinition = recordClass ? recordClass.getField(fieldName) : null,
                    fieldType = fieldDefinition ? fieldDefinition.type : 'auto';
            }
            switch (fieldType) {
                case 'date':
                    renderer = Tine.Tinebase.common.dateRenderer;
                    break;
                case 'boolean':
                    renderer = Tine.Tinebase.common.booleanRenderer;
                    break;
                case 'keyField':
                    var keyFieldName = fieldDefinition.keyFieldConfigName;
                    renderer = Tine.Tinebase.widgets.keyfield.Renderer.get(appName, keyFieldName);
                    break;
                case 'image':
                    renderer = Tine.widgets.grid.imageRenderer;
                    break;
                case 'json':
                    renderer = Tine.widgets.grid.jsonRenderer;
                    break;
                case 'records':
                case 'recodList':
                    //@Todo add records/list renderer!
            }

            return renderer;
        },

        /**
         * returns renderer for given field
         * 
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {gridPanel|displayPanel} optional.
         * @return {Function}
         */
        get: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);
                
            // check for registered renderer
            var renderer = renderers[categoryKey] ? renderers[categoryKey] : renderers[genericKey];
            
            // check for common names
            if (! renderer) {
                renderer = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! renderer) {
                renderer = this.getByDataType(appName, modelName, fieldName, String(fieldName).match(/^#.+/));
            }

            return renderer ? renderer : this.defaultRenderer;
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
        register: function(appName, modelName, fieldName, renderer, category, scope) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);
                
            renderers[category ? categoryKey : genericKey] = scope ? renderer.createDelegate(scope) : renderer;
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
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);
                
            // check for registered renderer
            return (renderers[categoryKey] ? renderers[categoryKey] : renderers[genericKey]) ? true : false;
        },
        
        /**
         * returns the modelName by modelName or record
         * 
         * @param {Record/String} modelName
         * @return {String}
         */
        getModelName: function(modelName) {
            return Ext.isFunction(modelName) ? modelName.getMeta('modelName') : modelName;
        },
        
        /**
         * returns the modelName by appName or application instance
         * 
         * @param {String/Tine.Tinebase.Application} appName
         * @return {String}
         */
        getAppName: function(appName) {
            return Ext.isString(appName) ? appName : appName.appName;
        },
        
        /**
         * returns a key by joining the array values
         * 
         * @param {Array} params
         * @return {String}
         */
        getKey: function(params) {
             return params.join('_');
        }
    };
}();