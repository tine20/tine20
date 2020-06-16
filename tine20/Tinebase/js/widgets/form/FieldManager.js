/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

import 'widgets/form/JsonField';
import 'widgets/form/XmlField';

/**
 * central form field manager
 * - get form field for a given field
 * - register form field for a given field
 *
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.FieldManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.form.FieldManager = function() {
    var fields = {};

    return {
        /**
         * const for category editDialog
         */
        CATEGORY_EDITDIALOG: 'editDialog',

        /**
         * const for category propertyGrid
         */
        CATEGORY_PROPERTYGRID: 'propertyGrid',

        /**
         * get form field of well known field names
         *
         * @param {String} fieldName
         * @return {Object}
         */
        getByFieldname: function(fieldName) {
            var field = null;

            return field;
        },

        /**
         * get form field by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
         * @param {Object} config
         * @return {Object}
         */
        getByModelConfig: function(appName, modelName, fieldName, category, config) {
            var recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                modelConfig = recordClass ? recordClass.getModelConfiguration() : null,
                fieldDefinition = modelConfig && modelConfig.fields ? modelConfig.fields[fieldName] : {};

            // have self contained fieldDefinition 
            fieldDefinition.appName = appName;
            fieldDefinition.fieldName = fieldName;
            
            return this.getByFieldDefinition(fieldDefinition, category, config);
        },

        getByFieldDefinition: function(fieldDefinition, category, config) {
            category = category || Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG;
            config = config || {};
            
            var field = {},
                fieldType = fieldDefinition.type || 'textfield',
                app = Tine.Tinebase.appMgr.get(fieldDefinition.appName),
                i18n = fieldDefinition.useGlobalTranslation ? window.i18n : app.i18n;

            if (fieldType === 'virtual' && fieldDefinition.config) {
                fieldType = fieldDefinition.config.type || 'textfield';
                fieldDefinition = fieldDefinition.config;
            }

            field.fieldLabel = i18n._hidden(fieldDefinition.label || fieldDefinition.fieldName);
            field.name = fieldDefinition.name || fieldDefinition.fieldName;
            field.disabled = !! (fieldDefinition.readOnly || fieldDefinition.disabled);
            field.allowBlank = !! (fieldDefinition.validators && fieldDefinition.validators.allowEmpty);
            // make field available via recordForm.formfield_NAME
            field.ref = '../../formfield_' + field.name;

            if (fieldDefinition['default']) {
                field['default'] = i18n._hidden(fieldDefinition['default']);
            }

            switch (fieldType) {
                case 'money':
                    field.xtype = 'extuxmoneyfield';
                    if (fieldDefinition.hasOwnProperty('allowNegative')) {
                        field.allowNegative = fieldDefinition.allowNegative;
                    }
                    break;
                case 'date':
                case 'datetime_separated_date':
                    field.xtype = 'datefield';
                    if (fieldDefinition.dateFormat) {
                        field.dateFormat = fieldDefinition.dateFormat;
                    }
                    break;
                case 'time':
                    field.xtype = 'timefield';
                    break;
                case 'datetime':
                    field.xtype = 'datetimefield'; // form ux.datetimefield
                    break;
                case 'bool':
                case 'boolean':
                    if (category === 'editDialog') {
                        field.xtype = 'checkbox';
                        field.boxLabel = field.fieldLabel;
                        field.hideLabel = true;
                    } else {
                        field.xtype = 'booleancombo';
                    }
                    break;
                case 'integer':
                    field.xtype = 'numberfield';
                    field.allowDecimals = false;

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'percent') {
                        field.xtype = 'extuxnumberfield';
                        field.useThousandSeparator = false;
                        field.suffix = ' %';
                    }

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'durationSec') {
                        field.xtype = 'durationspinner';
                        field.baseUnit = 'seconds';
                    }
                    
                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'minutes') {
                        field.xtype = 'durationspinner';
                        field.baseUnit = 'minutes';
                    }

                    if (fieldDefinition.max) {
                        field.maxValue = fieldDefinition.max;
                    }

                    if (fieldDefinition.min) {
                        field.minValue = fieldDefinition.min;
                    }
                    break;
                case 'float':
                    field.xtype = 'numberfield';
                    field.decimalPrecision = 2;

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'percent') {
                        field.xtype = 'extuxnumberfield';
                        field.suffix = ' %';
                    }

                    if (fieldDefinition.max) {
                        field.maxValue = fieldDefinition.max;
                    }

                    if (fieldDefinition.min) {
                        field.minValue = fieldDefinition.min;
                    }
                    break;
                case 'user':
                    field.xtype = 'addressbookcontactpicker';
                    field.userOnly = true;
                    field.useAccountRecord = true;
                    break;
                case 'relation':
                    if (fieldDefinition.config && fieldDefinition.config.appName && fieldDefinition.config.modelName) {
                        field.xtype = 'tinerelationpickercombo';
                        field.recordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];
                        field.app = fieldDefinition.config.appName;
                        field.relationType = fieldDefinition.config.type;
                        field.modelUnique = true;
                        if (fieldDefinition.config.additionalFilterSpec) {
                            field.additionalFilterSpec = fieldDefinition.config.additionalFilterSpec;
                        }
                        // TODO pass degree and other options in config?
                        field.relationDegree = 'sibling';
                    }
                    break;
                case 'record':
                    if (fieldDefinition.config && fieldDefinition.config.appName && fieldDefinition.config.modelName) {
                        var picker = Tine.widgets.form.RecordPickerManager.get(
                            fieldDefinition.config.appName,
                            fieldDefinition.config.modelName,
                            Ext.apply(field, config)
                        );
                        field = picker;
                    }
                    break;
                case 'records':
                    if (category === 'editDialog') {
                        field.xtype = 'wdgt.pickergrid';
                        field.recordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];
                        field.isFormField = true;
                        field.fieldName = fieldDefinition.fieldName;
                        field.hideHeaders = true;
                        field.height = 170; // 5 records
                    } else {
                        var picker = Tine.widgets.form.RecordsPickerManager.get(
                            fieldDefinition.config.appName,
                            fieldDefinition.config.modelName,
                            Ext.apply(field, config)
                        );
                        field = picker;
                    }
                    break;
                case 'keyfield':
                    field.xtype = 'widget-keyfieldcombo';
                    field.app = app;
                    field.keyFieldName = fieldDefinition.name;
                    break;
                case 'text':
                case 'fulltext':
                    field.xtype = 'textarea';
                    field.height = 70; // 5 lines
                    break;
                case 'stringAutocomplete':
                    field.xtype = 'tine.widget.field.AutoCompleteField';
                    field.appName = fieldDefinition.config.appName;
                    field.modelName = fieldDefinition.config.modelName;
                    break;
                case 'numberableStr':
                case 'numberableInt':
                    field.xtype = 'textfield';
                    field.disabled = true;
                    break;
                case 'json':
                    field.xtype = 'tw-jsonfield';
                    field.height = 150; // 12 lines
                    break;
                case 'xml':
                    field.xtype = 'tw-xmlfield';
                    field.height = 150; // 12 lines
                    break;
                default:
                    field.xtype = 'textfield';
                    if (fieldDefinition.length) {
                        field.maxLength = fieldDefinition.length;
                    }
                    break;
            }

            Ext.apply(field, config);

            return field;
        },

        /**
         * returns form field for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
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
            var field = fields[categoryKey] ? fields[categoryKey] : fields[genericKey];

            // check for common names
            if (! field) {
                field = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! field) {
                field = this.getByModelConfig(appName, modelName, fieldName, category, config);
            }

            return field;
        },

        /**
         * register renderer for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Object} field
         * @param {String} category {editDialog|propertyGrid} optional.
         */
        register: function(appName, modelName, fieldName, field, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            fields[category ? categoryKey : genericKey] = field;
        },

        /**
         * check if a field is explicitly registered
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            return (fields[categoryKey] ? fields[categoryKey] : fields[genericKey]) ? true : false;
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
