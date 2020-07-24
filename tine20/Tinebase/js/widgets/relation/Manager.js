/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.relation');

/**
 * @namespace   Tine.widgets.relation
 * @class       Tine.widgets.relation.Manager
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.widgets.relation.Manager = function() {

    var items = {};
    var ignoreApplications = ['Admin', 'Setup', 'Tinebase', 'ActiveSync'];

    return {
        /**
         * returns the relations config
         * @param {String/Tine.Tinebase.Application} app
         * @param {String/Tine.Tinebase.data.Record} recordClass
         * @param {Array} ignoreModels the php model names to ignore (they won't be returned)
         */
        get: function(app, recordClass, ignoreModels) {
            var key = this.getKey(app, recordClass);

            // create if not cached
            if(items[key] === undefined) {
                if (Ext.isString(recordClass)) {
                    app = Tine.Tinebase.common.resolveApp(app);
                    recordClass = Tine[app]['Model'][recordClass];
                }
                this.create(recordClass, key);
            }
            
            var allRelations = items[key];
            var usedRelations = [];
            // sort out ignored models
            if (ignoreModels) {
                Ext.each(allRelations, function(relation) {
                    if(ignoreModels.indexOf(relation.relatedApp + '_Model_' + relation.relatedModel) == -1) {
                        usedRelations.push(relation);
                    }
                }, this);
            } else {
                return allRelations;
            }
            
            return usedRelations;
        },

        /**
         * returns the relations config existence
         * @param {String/Tine.Tinebase.Application} app
         * @param {String/Tine.Tinebase.data.Record} recordClass
         * @return {Boolean}
         */
        has: function(app, recordClass) {
            var key = this.getKey(app, recordClass);
            
            // create if not cached
            if(items[key] === undefined) this.create(recordClass, key);
            return items[key] ? true : false;
        },

        /**
         * creates the relations config if found in registry
         * @param Tine.Tinebase.data.Record recordClass
         * @param {String} key
         * @return {Boolean}
         */
        create: function(recordClass, key) {
            var registered = [];
            if(!items[key]) items[key] = [];

            // add generic relations when no config exists
            Tine.Tinebase.data.RecordMgr.each(function(rec) {
                if (Tine.Tinebase.common.hasRight('run', rec.getMeta('appName')) && (ignoreApplications.indexOf(rec.getMeta('appName')) == -1) && (rec.getFieldNames().indexOf('relations') > -1)) {
                    items[key].push({
                        ownModel: recordClass.getMeta('recordName'),
                        relatedApp: rec.getMeta('appName'),
                        relatedModel: rec.getMeta('modelName'),
                        text: rec.getMeta('modelName') === 'Node' ? rec.getAppName()  : rec.getRecordName() +  '  (' + rec.getAppName() + ')'
                    });
                }
            });

            // set to false, so not try again
            if(items[key].length == 0) items[key] = false;
        },
        
        /**
         * returns the key (appName + modelName)
         * @param {String/Tine.Tinebase.Application} appName
         * @param {String/Tine.Tinebase.data.Record} modelName
         * @return {String}
         */
        getKey: function(appName, modelName) {
            var appName = Tine.Tinebase.common.resolveApp(appName);
            var modelName = Tine.Tinebase.common.resolveModel(modelName);
            return appName + modelName;
        }
    };

}();