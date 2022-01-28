/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Tinebase.widgets.keyfield');

/**
 * key field store
 * 
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @param       {Object} config
 * @constructor
 */
Tine.Tinebase.widgets.keyfield.Store = function(config) {
    // init app
    config.app = Ext.isString(config.app) ? Tine.Tinebase.appMgr.get(config.app) : config.app;

    // get keyField config
    if (config.app.getRegistry()) {
        if (! config.keyFieldConfig) {
            config.keyFieldConfig = config.app.getRegistry().get('config')[config.keyFieldName];
        }

        // init data / translate values
        var data = config.keyFieldConfig && config.keyFieldConfig.value && config.keyFieldConfig.value.records && config.keyFieldConfig.value.records.length ? config.keyFieldConfig.value.records : [];
        const translationList = Locale.getTranslationList(config?.keyFieldConfig?.definition?.localeTranslationList);
        Ext.each(data, function (d) {
            d.i18nValue = translationList ? translationList[d.id] : (d.value ? config.app.i18n._hidden(d.value) : "");
            d.id = String(d.id);
        });
        config.data = data;
    }
    
    if (! config.keyFieldConfig) {
        throw ('No keyfield config found for ' + config.keyFieldName + ' in ' + config.app.appName + ' registry.');
    }
    
    var modelName = config.keyFieldConfig.definition
                    && config.keyFieldConfig.definition.options
                    && config.keyFieldConfig.definition.options.recordModel ?
                        config.keyFieldConfig.definition.options.recordModel :
                        "Tinebase_Model_KeyFieldRecord",
        recordClass = Tine.Tinebase.data.RecordMgr.get(modelName) || Tine.Tinebase.Model.KeyFieldRecord,
        fields = [].concat(recordClass.getFieldDefinitions());

    // add 'virtual' field
    fields.push({name: 'i18nValue'});
    fields.push({name: '_itemCls'});
    this.recordClass = Ext.data.Record.create(fields);

    config.fields = this.recordClass;

    Tine.Tinebase.widgets.keyfield.Store.superclass.constructor.call(this, config);
};

/**
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @class       Tine.Tinebase.widgets.keyfield.Store
 * @extends     Ext.data.JsonStore
 */
Ext.extend(Tine.Tinebase.widgets.keyfield.Store, Ext.data.JsonStore, {
    /**
     * @cfg {String/Application} app
     */
    app: null,
    
    /**
     * @cfg {String} keyFieldName 
     * name of key field
     */
    keyFieldName: null,
    
    /**
     * @property keyFieldConfig
     * @type Object
     */
    keyFieldConfig: null
});

/**
 * manages key field stores
 * 
 * @namespace   Tine.Tinebase.widgets.keyfield
 * @class       Tine.Tinebase.widgets.keyfield.StoreMgr
 * @singleton
 */
Tine.Tinebase.widgets.keyfield.StoreMgr = function(){
    return {
        /**
         * returns key field record store
         * 
         * @param {String/Application} app
         * @param {String} keyFieldName 
         * @return Ext.data.Store
         */
        get: function(app, keyFieldName) {
            var appName = Ext.isString(app) ? app : app.appName;
                
            return new Tine.Tinebase.widgets.keyfield.Store({
                app: app,
                keyFieldName: keyFieldName
            });
            
        },

        has: function(app, keyFieldName) {
            var store;
            try {
                store = this.get(app, keyFieldName);
            } catch (e){}

            return !! store;
        }
    };
}();

Tine.Tinebase.widgets.keyfield.getDefinition = function (appName, configName) {
    const rawDef = Tine.Tinebase.appMgr.get(appName).getRegistry().get('config')[configName];
    return Object.assign({ ... rawDef.definition }, {... rawDef.value });
}

Tine.Tinebase.widgets.keyfield.getDefinitionFromMC = function (recordClass, fieldName) {
    const fieldDef = Tine.Tinebase.data.RecordMgr.get(recordClass).getField(fieldName).fieldDefinition;
    return Tine.Tinebase.widgets.keyfield.getDefinition(
        _.get(fieldDef, 'config.appName', recordClass.getMeta('appName')),
        _.get(fieldDef, 'name')
    );

}
