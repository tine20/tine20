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
         * @param Tine.Tinebase.Application app
         * @param Tine.Tinebase.data.Record recordClass
         */
        get: function(app, recordClass) {
            var key = app.name + recordClass.getMeta('modelName');
            // create if not cached
            if(items[key] === undefined) this.create(recordClass, key);
            return items[key];
        },

        /**
         * returns the relations config existence
         * @param Tine.Tinebase.Application app
         * @param Tine.Tinebase.data.Record recordClass
         * @return {Boolean}
         */
        has: function(app, recordClass) {
            var key = app.name + recordClass.getMeta('modelName');
            // create if not cached
            if(items[key] === undefined) this.create(recordClass, key);
            return items[key] ? true : false;
        },

        /**
         * creates the relations config if found in registry
         * @param Tine.Tinebase.Application app
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
                        text: rec.getRecordName() + ' (' + rec.getAppName() + ')'
                    });
                }
            });

            // set to false, so not try again
            if(items[key].length == 0) items[key] = false;
        }
    };

}();