/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine', 'Tine.Tinebase');

/**
 * checks users presence according to dom events
 *
 * NOTE: this method intercepts Ext.EventObjectImpl as a lot of events might not bubble till window object
 *       This needs to be adopted as more other frameworks we introduce
 *
 * @namespace  Tine.Tinebase
 * @class      Tine.Tinebase.PresenceObserver
 */
Tine.Tinebase.PresenceObserver = function(config) {
    Ext.apply(this, config);

    this.checkTask = new Ext.util.DelayedTask(this.checkPresence, this);
    this.startChecking();
};

Tine.Tinebase.PresenceObserver.prototype = {
    /**
     * @cfg {integer} maxAbsenseTime in minutes
     */
    maxAbsenseTime: 240,

    /**
     * @cfg {Function} callback to be called when absence is detected
     */
    absenceCallback: Ext.emptyFn,

    /**
     * @cfg {Function} callback to be called each time presence is detected
     */
    presenceCallback: Ext.emptyFn,

    /**
     * @cfg {Object} scope to the callbacks are called in
     */
    scope: window,

    /**
     * @property {Number} lastPresenceCheck
     */
    lastPresenceCheck: null,

    /**
     * @property {Ext.util.DelayedTask} checkTask
     */
    checkTask: null,

    startChecking: function() {
        var now = new Date();
        firstCheck = now.add(Date.MINUTE, this.maxAbsenseTime);

        this.setLastPresece(now);

        Tine.log.debug('Tine.Tinebase.PresenceObserver.startChecking register fist presence check for ' + firstCheck);
        this.checkTask.delay(this.maxAbsenseTime * 60000);
    },

    stopChecking: function() {
        this.checkTask.cancel();
    },

    checkPresence: function() {
        var now = new Date(),
            nowTS = now.getTime(),
            lastPresence = this.getLastPresence();

        Tine.log.debug('Tine.Tinebase.PresenceObserver.checkPresence checking presece now ' + now);
        Tine.log.debug('Tine.Tinebase.PresenceObserver.checkPresence last presence detected at ' + new Date(lastPresence));

        if (lastPresence + this.maxAbsenseTime * 60000 < nowTS) {
            Tine.log.info('Tine.Tinebase.PresenceObserver.checkPresence no presence detected for ' + this.maxAbsenseTime + ' minutes');
            this.absenceCallback.call(this.scope, lastPresence, this);
        } else {
            var nextCheck = this.maxAbsenseTime * 60000 - (nowTS - lastPresence);
            Tine.log.debug('Tine.Tinebase.PresenceObserver.checkPresence next presence check at ' + now.add(Date.MILLI, nextCheck));
            this.checkTask.delay(nextCheck);
            this.absenceCallback.call(this.scope, nextCheck, this);
        }
    },

    /**
     * get last presence timestramp
     *
     * @returns {number}
     */
    getLastPresence: function() {

        var myLastPresence = // last prsence from this window
                Tine.Tinebase.PresenceObserver.lastPresence,
            otherLastPresence = // last presence set by other windows
                Tine.Tinebase.registry.get('lastPresence');

        if (myLastPresence > otherLastPresence) {
            this.setLastPresece(myLastPresence);
        }

        return Tine.Tinebase.PresenceObserver.lastPresence;
    },

    setLastPresece: function(ts) {
        ts = ts.getTime ? ts.getTime() : ts;
        Tine.Tinebase.PresenceObserver.lastPresence = ts;
        Tine.Tinebase.registry.set('lastPresence', ts);
    }
};

/**
 * @static {Number} lastPresence last event timestamp
 */
Tine.Tinebase.PresenceObserver.lastPresence = null;

Ext.EventObjectImpl.prototype.setEvent = Ext.EventObjectImpl.prototype.setEvent.createInterceptor(function(e) {
    // timeStamp property of object sucks, see https://developers.google.com/web/updates/2016/01/high-res-timestamps
    Tine.Tinebase.PresenceObserver.lastPresence = new Date().getTime();
});