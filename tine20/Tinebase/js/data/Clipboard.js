/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.data');

/**
 * tine 2.0 record clipboard
 * 
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.Clipboard
 * 
 * <p>Record Clipboard</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @singleton
 */
Tine.Tinebase.data.Clipboard = function() {
    var items = {};
    
    return {
        clear: function(appName, modelName) {
            var key = this.getKey(appName, modelName);
            items[key] = [];
        },
        
        getKey: function(appName, modelName) {
            
            var key = appName + modelName;
            
            if (! items.hasOwnProperty(key)) {
                items[key] = [];
            }
            
            return key;
        },
        
        push: function(record) {
            Tine.log.debug('Putting record to clipboard:');
            Tine.log.debug(record);
            
            var key = this.getKey(record.appName, record.modelName);
            
            items[key].push(record);
        },
        
        pull: function(appName, modelName, stay) {
            var key = this.getKey(appName, modelName);
            
            if (items[key].length == 0) {
                return null;
            }
            
            if (! stay) {
                Tine.log.debug('Releasing record from clipboard:');
                var record = items[key].pop();
            } else {
                Tine.log.debug('Fetching record from clipboard:');
                var i = items[key].length - 1;
                var record = items[key][i];
            }

            Tine.log.debug(record);
            
            return record;
        },
        
        /**
         * returns the ids of all records by appName and modelName as array
         * 
         * @return array
         */
        getIds: function(appName, modelName) {
            var key = appName + modelName;
            var ret = [];
            
            if (! items.hasOwnProperty(key)) {
                return ret;
            }
            
            if (items[key].length == 0) {
                return ret;
            }
            
            for (var index = 0; index < items[key].length; index++) {
                ret.push(items[key][index].get('id'));
            }
            
            return ret;
        },
        
        has: function(appName, modelName) {
            var key = appName + modelName;
            if (items.hasOwnProperty(key)) {
                return items[key].length > 0;
            }
            return false;
        }
    }
}();