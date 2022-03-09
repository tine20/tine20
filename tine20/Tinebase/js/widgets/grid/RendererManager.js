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
            return [null, undefined].indexOf(value) < 0 ? Ext.util.Format.htmlEncode(value) : '';
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
            } else if (fieldName == 'color') {
                renderer = Tine.Tinebase.common.colorRenderer;
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
            if(!cf){
                var renderer = null,
                    recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                    field = recordClass ? recordClass.getField(fieldName) : null,
                    fieldDefinition = Object.assign({}, field, _.get(field, 'fieldDefinition', {}), _.get(field, 'fieldDefinition.conifg', {}), _.get(field, 'fieldDefinition.uiconfig', {})),
                    fieldType = fieldDefinition ? fieldDefinition.type : 'auto';
            }
            switch (fieldType) {
                case 'record':
                    if (Tine.Tinebase.common.hasRight('view', fieldDefinition.config.appName, fieldDefinition.config.modelName.toLowerCase())) {
                        renderer = function (value, row, record) {
                            var foreignRecordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];

                            if (foreignRecordClass && value) {
                                const record = Tine.Tinebase.data.Record.setFromJson(value, foreignRecordClass);
                                const titleProperty = foreignRecordClass.getMeta('titleProperty');
                                value = _.isFunction(_.get(record, 'getTitle')) ? record.getTitle() : _.get(record, titleProperty, '');

                                if (!!+_.get(record, 'data.is_deleted')) {
                                    value = '<span style="text-decoration: line-through;">' + value + '</span>';
                                }
                            }
                            return value;
                        };
                    } else {
                        renderer = null;
                    }
                    break;
                case 'integer':
                case 'float':
                    if (fieldDefinition.hasOwnProperty('specialType')) {
                        switch (fieldDefinition.specialType) {
                            case 'bytes1000':
                                renderer = function (value, cell, record) {
                                    return Tine.Tinebase.common.byteRenderer(value, cell, record, 2, true);
                                };
                                break;
                            case 'bytes':
                                renderer = function (value, cell, record) {
                                    return Tine.Tinebase.common.byteRenderer(value, cell, record, 2, false);
                                };
                                break;
                            case 'minutes':
                                renderer = Tine.Tinebase.common.minutesRenderer;
                                break;
                            case 'seconds':
                                renderer = Tine.Tinebase.common.secondsRenderer;
                                break;
                            case 'percent':
                                renderer = function (value, cell, record) {
                                    return Tine.Tinebase.common.percentRenderer(value, fieldDefinition.type, fieldDefinition.nullable);
                                };
                                break;
                            case 'discount':
                                if (fieldDefinition.singleField) {
                                    renderer = function (value, cell, record) {
                                        const type = record.get(fieldName.replace(/_sum$/, '_type'));
                                        if (type === 'PERCENTAGE') {
                                            value = record.get(fieldDefinition.fieldName.replace(/_sum$/, '_percentage'));
                                            return !value ? '' : Tine.Tinebase.common.percentRenderer(value, 'float');
                                        } else {
                                            return !value ? '' : Ext.util.Format.money(value);
                                        }
                                    }
                                } else {
                                    renderer = function (value, cell, record) {
                                        return !value ? '' : Ext.util.Format.money(value);
                                    }
                                }
                                break;

                            case 'durationSec':
                                renderer = function (value, cell, record) {
                                    return Ext.ux.form.DurationSpinner.durationRenderer(value, {
                                        baseUnit: 'seconds'
                                    });
                                };
                                break;
                            default:
                                renderer = Ext.util.Format.htmlEncode;
                        }

                        renderer = renderer.createSequence(function (value, metadata, record) {
                            if (metadata) {
                                metadata.css = 'tine-gird-cell-number';
                            }
                        });

                    }
                    break;
                case 'user':
                    renderer = Tine.Tinebase.common.usernameRenderer;
                    break;
                case 'keyfield':
                case 'keyField':
                    renderer = Tine.Tinebase.widgets.keyfield.Renderer.get(appName, _.get(fieldDefinition,
                        'keyFieldConfigName', fieldDefinition.name));
                    break;
                case 'datetime_separated_date':
                case 'date':
                    renderer = Tine.Tinebase.common.dateRenderer;
                    break;
                case 'datetime':
                    renderer = Tine.Tinebase.common.dateTimeRenderer;
                    break;
                case 'time':
                    renderer = Tine.Tinebase.common.timeRenderer;
                    break;
                case 'tag':
                    renderer = Tine.Tinebase.common.tagsRenderer;
                    break;
                case 'container':
                    renderer = Tine.Tinebase.common.containerRenderer;
                    break;
                case 'boolean':
                    renderer = Tine.Tinebase.common.booleanRenderer;
                    break;
                case 'money':
                    renderer = function (value, cell, record) {
                        return Ext.util.Format.money(value, {zeroMoney: fieldDefinition?.specialType === 'zeroMoney'}, fieldDefinition.nullable);
                    };
                    break;
                case 'attachments':
                    renderer = Tine.widgets.grid.attachmentRenderer;
                    break;
                case 'image':
                    renderer = Tine.widgets.grid.imageRenderer;
                    break;
                case 'json':
                    renderer = Tine.widgets.grid.jsonRenderer;
                    break;
                case 'relation':
                case 'relations':
                    let cc = fieldDefinition.config;

                    if (cc && cc.type && cc.appName && cc.modelName) {
                        let rendererObj = new Tine.widgets.relation.GridRenderer({
                            appName: appName,
                            type: cc.type,
                            foreignApp: cc.appName,
                            foreignModel: cc.modelName
                        });
                        renderer = _.bind(rendererObj.render, rendererObj);
                        break;
                    }
                    break;
                case 'hexcolor':
                    renderer = Tine.Tinebase.common.colorRenderer;
                    break;
                case 'model':
                    renderer = (classname, metaData, record) => {
                        const recordClass = Tine.Tinebase.data.RecordMgr.get(classname);
                        return recordClass ? recordClass.getRecordName() : classname;
                    };
                    break;
                case 'dynamicRecord':
                    const classNameField = fieldDefinition.config.refModelField;
                    renderer = (configRecord, metaData, record) => {
                        const configRcordClass = Tine.Tinebase.data.RecordMgr.get(record.get(classNameField));
                        return Tine.Tinebase.data.Record.setFromJson(configRecord, configRcordClass).getTitle();
                    };
                    break;
                case 'localizedString':
                    const type = _.get(fieldDefinition, 'config.type')
                    const languagesAvailableDef = _.get(recordClass.getModelConfiguration(), 'languagesAvailable')
                    const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinition(_.get(languagesAvailableDef, 'config.appName', appName), languagesAvailableDef.name)
                    const translationList = Locale.getTranslationList('Language')

                    renderer = function renderer(value, metaData, record, rowIndex, colIndex, store) {
                        const lang = store?.localizedLang || keyFieldDef.default
                        const localized = _.find(value, { language: lang })
                        const text = Ext.util.Format.htmlEncode(_.get(localized, 'text', ''));
                        const langCode = lang.toUpperCase();
                        const qtip = i18n._('This is a multilingual field:') + '<br />' + _.reduce(value, (text, localized) => {
                            return text + '<br />' + translationList[localized.language] + ': ' + Ext.util.Format.htmlEncode(localized.text)
                        }, '')

                        metaData.css = 'tine-grid-cell-action-wrap'
                        return  `${text}<div ext:qtip="${Ext.util.Format.htmlEncode(qtip)}" class="tine-grid-cell-action tine-grid-cell-localized">${langCode}</div>`
                    }
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

            if (!renderer && String(fieldName).match(/^#.+/)) {
                var cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(appName, modelName, fieldName.replace(/^#/,''));
                renderer = Tine.widgets.customfields.Renderer.get(appName, cfConfig);
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
