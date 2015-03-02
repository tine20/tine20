/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * compatibility plugin for store to replace Ext.util.MixedCollection in registry & preferences
 */
;(function(store, _) {
    var _set = _.set,
        _remove = _.remove,
        _clear = _.clear,
        _get = _.get,
        _on = _.storeAPI.on,
        _isPrefExp = /\.preferences$/;

    _.stringify = function(d) {
        return d === undefined || typeof d === "function" ? d+'' : Ext.encode(d);
    };
    
    _.parse = function(s) {
        // if it doesn't parse, return as is
        try{ return Ext.decode(s); }catch(e){ return s; }
    };

    _.containsKey = function(key) {
        if (key && key.match(_isPrefExp)) {
            var parts = key.split('.');
            return !! Tine[parts[parts.length-3]]['preferences'];
        }

        return this.has(key);
    };

    _.get = function(area, key) {
        if (key && key.match(_isPrefExp)) {
            var parts = key.split('.');
            return Tine[parts[parts.length-3]]['preferences'];
        }
        return _get.apply(this, arguments);
    };

    /**
     * NOTE as localStorage don't sends events for own window, we need to
     * intercept store write options
     */
    _.set = function(area, key, string) {
        var oldValue = this.get(area, key),
            ret = _set.apply(this, arguments);

        this.fireEvent(key, oldValue, string);

        return ret;
    };

    _.remove = function(area, key) {
        var oldValue = this.get(area, key),
            ret = _remove.apply(this, arguments);

        this.fireEvent(key, oldValue, undefined);

        return ret;
    };

    _.clear = function(area) {
        // we come here without on clear without namespace only
        // hence no need to implement now
    };

    /**
     * fire a simulated storage event
     */
    _.fireEvent = function(key, oldValue, newValue) {
        var event;
        if (document.createEvent) {
            event = document.createEvent('StorageEvent');
            event.initStorageEvent('storage', true, true, key, oldValue, newValue, window.location.href, window.localStorage);

            return dispatchEvent(event);
        } else {
            // IE < 11?
            event = document.createEventObject();
            event.eventType = "storage";
            //event.eventName = "storage";
            event.key = key;
            event.newValue = newValue;
            event.oldValue = oldValue;

            return document.fireEvent("onstorage", event);
        }
    };

    /**
     * NOTE: in Ext.util.MixedCollection you can't register for specific keys
     *       so we add this capability as a fours parameter
     * NOTE: we only support the replace event yet and do no mapping computations
     */
    _.fn('on', function(event, fn, scope, key) {
        if (event != 'replace') {
            throw new Ext.Error('event ' + event + ' not implemented in store.compat');
        }

        _on.call(this, key, function(e) {
            if (!key || key == e.key) {
                if (e.oldValue != e.newValue) {
                    return fn.call(scope||window, e.key, e.oldValue, e.newValue);
                }
            }
        });
    });

    _.fn('add', _.storeAPI.set);
    _.fn('replace', _.storeAPI.set);
    _.fn('containsKey', _.containsKey);

})(window.store, window.store._);