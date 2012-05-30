/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.form');
 
 /**
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.RecordPickerManager
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.widgets.form.RecordPickerManager = function() {

    var items = {};
  
    return {
        /**
         * returns a registered recordpicker or creates the default one
         * @param {String/Tinebase.Application} appName      expands to recordClass: Tine[appName].Model[modelName],
         * @param {String/Tinebase.data.Record} modelName               recordProxy: Tine[appName][modelName.toLowerCase() + 'Backend'])
         * @param {Object} config       additional Configuration
         * @return {Object} recordpicker
         */
        get: function(appName, modelName, config) {
            config = config || {};
    
            var appName = Ext.isString(appName) ? appName : appName.appName,
                modelName = Ext.isFunction(modelName) ? modelName.getMeta('modelName') : modelName,
                key = appName+modelName;

            if(items[key]) {   // if registered
                if(Ext.isString(items[key])) { // xtype
                    return Ext.ComponentMgr.create(config, items[key]);
                } else {
                    return new items[key](config);
                }
            } else {    // not registered, create default
                var defaultconfig = {
                    recordClass: Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                    recordProxy: Tine[appName][modelName.toLowerCase() + 'Backend'],
                    loadingText: _('Searching...')
                };
                Ext.apply(defaultconfig, config);
                return new Tine.Tinebase.widgets.form.RecordPickerComboBox(defaultconfig);
            }
        },
        
        /**
         * Registers a component
         * @param {String} appName          the application registered for
         * @param {String} modelName        the registered model name
         * @param {String/Object} component the component or xtype to register 
         */
        register: function(appName, modelName, component) {
            
            if(!Tine.hasOwnProperty('log')) {
                this.register.defer(100, this, [appName, modelName, component]);
                return false;
            }
            
            var appName = Ext.isString(appName) ? appName : appName.appName,
                modelName = Ext.isFunction(modelName) ? modelName.getMeta('modelName') : modelName,
                key = appName+modelName;

            if(!items[key]) {
                Tine.log.debug('RecordPickerManager::registerItem: ' + appName + modelName);
                items[key] = component;
            }
        }
    };
}();
