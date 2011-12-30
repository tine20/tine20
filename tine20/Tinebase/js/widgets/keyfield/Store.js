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
    config.keyFieldConfig = config.app.getRegistry().get('config')[config.keyFieldName];
    
    // init data / translate values
    var data = config.keyFieldConfig && config.keyFieldConfig.value && Ext.isArray(config.keyFieldConfig.value.records) ? config.keyFieldConfig.value.records : [];
    Ext.each(data, function(d) {
        d.i18nValue = d.value ? config.app.i18n._hidden(d.value) : "";
    });
    config.data = data;
    
    if (! config.keyFieldConfig) {
        throw ('No keyfield config found for ' + config.keyFieldName + ' in ' + config.app.appName + ' registry.');
    }
    
    var modelName = config.keyFieldConfig.definition && config.keyFieldConfig.definition.options ? config.keyFieldConfig.definition.options['recordModel'] : "Tinebase_Config_KeyFieldRecord",
        modelParts = modelName.split('_'),
        recordClass = Tine[modelParts[0]] && Tine[modelParts[0]]['Model'] && Tine[modelParts[0]]['Model'][modelParts[2]] ? Tine[modelParts[0]]['Model'][modelParts[2]] : null;
    
    config.fields = recordClass ? recordClass : ['id', 'value', 'icon', 'system', 'i18nValue'];
    
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
    var stores = {};
    
    return {
        /**
         * returns key field record store
         * 
         * @param {String/Application} app
         * @param {String} keyFieldName 
         * @return Ext.data.Store
         */
        get: function(app, keyFieldName) {
            var appName = Ext.isString(app) ? app : app.appName,
                key = appName + '_' + keyFieldName;
                
            if (! stores[key]) {
                stores[key] = new Tine.Tinebase.widgets.keyfield.Store({
                    app: app,
                    keyFieldName: keyFieldName
                });
            }
            
            return stores[key];
        }
    }
}();
