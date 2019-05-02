/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.config');


/**
 * config edit field manager
 *
 *  @singleton
 */
Tine.Admin.config.FieldManager = function() {
    var items = {};

    return {

        /**
         * create field for given configRecord value
         *
         * @param configRecord
         * @param options
         * @returns Ext.form.Field|null
         */
        create: function (configRecord, options) {
            var type = configRecord.get('type'),
                constr = Ext.form.TextField;

            if (items.hasOwnProperty(type)) {
                constr = items[type]
            } else {
                switch (type) {
                    case 'string':
                        constr = Ext.form.TextField;
                        break;

                    case 'bool':
                    case 'boolean':
                        options = Ext.apply({
                            mode: 'local',
                            forceSelection: true,
                            allowEmpty: false,
                            triggerAction: 'all',
                            editable: false,
                            store: [[true, i18n._('Yes')], [false, i18n._('No')]]
                        }, options);
                        constr = Ext.form.ComboBox;
                        break;

                    case 'keyField':
                        var store = [];
                        Ext.each(configRecord.get('options')['records'], function(record) {
                            store.push([record.id, options.app.i18n._hidden(record.value)]);
                        });

                        options = Ext.apply({
                            mode: 'local',
                            forceSelection: true,
                            allowEmpty: false,
                            triggerAction: 'all',
                            editable: false,
                            store: store
                        }, options);
                        constr = Ext.form.ComboBox;
                        break;

                    case 'keyFieldConfig':
                        constr = Tine.Tinebase.widgets.keyfield.ConfigField;
                        break;

                    case 'record':
                        var recordOptions = configRecord.get('options'),
                            recordClass = Tine.Tinebase.data.RecordMgr.get(recordOptions.appName, recordOptions.modelName);

                        options = Ext.apply({
                            recordClass: recordClass
                        }, options);

                        constr = Tine.Tinebase.widgets.form.RecordPickerComboBox;
                        break;

                    default:
                        constr = null;
                        break;
                }
            }

            return Ext.isFunction(constr) ? new constr(options) : null;

        },

        /**
         * register editor field for given config
         *
         *  @static
         *  @param type string
         *  @param constructor Function
         */
        register: function (type, constructor) {
            items[type] = constructor;
        }
    };
}();