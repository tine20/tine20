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
    this.instance = ++Tine.Tinebase.PresenceObserver.instance;
    this.checkTask = new Ext.util.DelayedTask(this.checkPresence, this);
    this.startChecking();
};

Tine.Tinebase.PresenceObserver.instance = 0;
Tine.Tinebase.PresenceObserver.prototype = {
    /**
     * @cfg {integer} maxAbsenceTime in minutes
     */
    maxAbsenceTime: 240,

    /**
     * @cfg {integer} presenceCheckInterval in seconds
     */
    presenceCheckInterval: 5,

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
     * @property {Ext.util.DelayedTask} checkTask
     */
    checkTask: null,

    startChecking: function() {
        const now = new Date();
        let nextCheck = this.maxAbsenceTime * 60000;
        nextCheck = nextCheck > 60000 ? nextCheck - 60000 : nextCheck; // always check one minute before end

        this.state = 'presence';
        this.setLastPresence(now);

        Tine.log.debug(this.instance + ' Tine.Tinebase.PresenceObserver.startChecking register fist presence check for ' +  now.add(Date.MILLI, nextCheck));
        this.checkTask.delay(nextCheck);
    },

    stopChecking: function() {
        this.checkTask.cancel();
    },

    checkPresence: function() {
        var now = new Date(),
            nowTS = now.getTime(),
            lastPresence = this.getLastPresence(),
            ttl = (this.maxAbsenceTime * 60000 - (nowTS - lastPresence))/1000,
            state = lastPresence + this.maxAbsenceTime * 60000 <= nowTS ? 'absence' : 'presence';

        Tine.log.debug(this.instance + ' Tine.Tinebase.PresenceObserver.checkPresence checking presence now ' + now);
        Tine.log.debug(this.instance + ' Tine.Tinebase.PresenceObserver.checkPresence state: "' + state + '" (ttl='+ttl+'s) as last presence detected at ' + new Date(lastPresence));

        if (state == 'absence') {
            Tine.log.info(this.instance + ' Tine.Tinebase.PresenceObserver.checkPresence no presence detected for ' + this.maxAbsenceTime + ' minutes');
            if (this.state == 'presence') {
                this.state = 'absence';
                this.absenceCallback.call(this.scope, new Date(lastPresence), this);
            }
            // wait for user to return
            this.checkTask.delay(this.presenceCheckInterval * 1000);

        } else {
            let nextCheck = this.maxAbsenceTime * 60000 - (nowTS - lastPresence);
            nextCheck = nextCheck > 60000 ? nextCheck - 60000 : nextCheck; // always check one minute before end

            Tine.log.debug(this.instance + ' Tine.Tinebase.PresenceObserver.checkPresence next presence check at ' + now.add(Date.MILLI, nextCheck));
            this.state = 'presence';
            this.checkTask.delay(nextCheck);
            this.presenceCallback.call(this.scope, new Date(lastPresence), this, ttl);
        }
    },

    /**
     * get last presence timestamp
     *
     * @returns {number}
     */
    getLastPresence: function() {

        var myLastPresence = // last prsence from this window
                Tine.Tinebase.PresenceObserver.lastPresence,
            otherLastPresence = // last presence set by other windows
                Tine.Tinebase.registry.get('lastPresence');

        if (myLastPresence > otherLastPresence) {
            this.setLastPresence(myLastPresence);
        }

        return Tine.Tinebase.PresenceObserver.lastPresence;
    },

    setLastPresence: function(ts) {
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