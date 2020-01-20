/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * central column manager
 * - get column for a given column
 * - register column for a given column
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ColumnManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.grid.ColumnManager = function() {
    var columns = {};

    return {
        /**
         * const for category editDialog
         */
        CATEGORY_EDITDIALOG: 'editDialog',

        /**
         * const for category mainScreen
         */
        CATEGORY_PROPERTYGRID: 'mainScreen',

        /**
         * get column of well known column names
         *
         * @param {String} columnName
         * @return {Object}
         */
        getByFieldname: function(columnName) {
            var column = null;

            return column;
        },

        /**
         * get column by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @param {Object} config
         * @return {Object}
         */
        getByModelConfig: function(appName, modelName, fieldName, category, config) {
            var _ = window.lodash,
                column = {},
                recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                modelConfig = recordClass ? recordClass.getModelConfiguration() : null,
                fieldDefinition = _.get(modelConfig, 'fields.' + fieldName , {}),
                fieldType = fieldDefinition.type || 'string',
                app = Tine.Tinebase.appMgr.get(appName),
                i18n = fieldDefinition.useGlobalTranslation ? window.i18n : app.i18n;

            if (fieldDefinition.type === 'virtual') {
                fieldDefinition = fieldDefinition.config;
            }

            // don't show multiple record fields
            if (fieldDefinition.type == 'records') {
                return null;
            }

            // don't show parent property in dependency of an editDialog
            if (this.editDialog && fieldDefinition.hasOwnProperty('config') && fieldDefinition.config.isParent) {
                return null;
            }

            // don't show record field if the user doesn't have the right on the application
            if (fieldDefinition.type == 'record' && !(fieldDefinition.config && fieldDefinition.config.doNotCheckModuleRight) && (! Tine.Tinebase.common.hasRight('view', fieldDefinition.config.appName, fieldDefinition.config.modelName.toLowerCase() + 's'))) {
                return null;
            }

            if(fieldDefinition.type == 'attachments') {
                config.width = 20;
                config.resizable = false;
                config.header = window.i18n._('Attachments');
                config.tooltip = config.header;
            }

            if(fieldDefinition.type == 'image') {
                config.width = 20;
                config.resizable = false;
                config.header = fieldDefinition.label == 'Image' ? window.i18n._('Image') : i18n._(fieldDefinition.label);
                config.tooltip = config.header;
            }

            if(fieldDefinition.type == 'date') {
                config.width = 90;
            }

            if(fieldDefinition.type == 'money') {
                config.align = 'right';
            }

            // If no label exists, don't use in grid
            if (! fieldDefinition.label) {
                return null;
            }

            Ext.applyIf(column, {
                id: fieldName,
                dataIndex: fieldName,
                header: i18n._(fieldDefinition.label),
                hidden: fieldDefinition.hasOwnProperty('shy') ? fieldDefinition.shy : false,    // defaults to false
                sortable: (fieldDefinition.hasOwnProperty('sortable') && fieldDefinition.sortable == false) ? false : true // defaults to true
            });

            if (fieldDefinition.hasOwnProperty('summaryType')) {
                column.summaryType = fieldDefinition.summaryType;
            }

            var renderer = Tine.widgets.grid.RendererManager.get(appName, modelName, fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
            if (renderer) {
                column.renderer = renderer;
            }

            Ext.apply(column, config);

            return column;
        },

        /**
         * returns column for given column
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @param {Object} config
         * @return {Object}
         */
        get: function(appName, modelName, fieldName, category, config) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]),
                config = config || {};

            // check for registered renderer
            var column = columns[categoryKey] ? columns[categoryKey] : columns[genericKey];

            // check for common names
            if (! column) {
                column = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! column) {
                column = this.getByModelConfig(appName, modelName, fieldName, category, config);
            }

            return column;
        },

        /**
         * register renderer for given column
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Object} column
         * @param {String} category {editDialog|mainScreen} optional.
         */
        register: function(appName, modelName, fieldName, column, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            columns[category ? categoryKey : genericKey] = column;
        },

        /**
         * check if a column is explicitly registered
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            return (columns[categoryKey] ? columns[categoryKey] : columns[genericKey]) ? true : false;
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