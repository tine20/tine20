/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* global Ext,Tine */
class AbstractProvider extends Ext.util.Observable {
  /**
   * @cfg {String} area name of area
   */
  /**
   * @cfg {String} validity 'session', 'lifetime', 'presence' or 'someotherthingonlyproviderunderstands',
   */
  /**
   * @cfg {Number} lifetime lifetime in minutes
   */
  /**
   * @cfg {Boolean} individual each area must be unlocked individually
   * (when applied hierarchically / with same provider) -> NOT YET IMPLEMENTED
   */
  /**
   * @property {Number} expires
   */
  /**
   * @property {Boolean} isUnlocking
   */

  constructor (config) {
    super(config)
    this.expires = 0
    this.isUnlocking = false
    this.addEvents(
      /**
       * @event lock
       * Fires when the provider locked the area.
       * @param {AbstractProvider} this The Provider
       */
      'lock',
      /**
       * @event unlocking
       * Fires when the provider started the async unlocking action.
       * @param {AbstractProvider} this The Provider
       */
      'unlocking',
      /**
       * @event unlock
       * Fires when the provider unlocked the area.
       * @param {AbstractProvider} this The Provider
       */
      'unlock',
      /**
       * @event stateChange
       * Fires when the state of this provider changes.
       * @param {AbstractProvider} this The Provider
       * @param {Object} state the output of the provider {@link #getState} function
       */
      'stateChange'
    )

    Object.assign(this, config)
  }

  isLocked (/* askServer */) {
    let me = this
    return new Promise((resolve) => {
      me.updateState()
      resolve(me.expires < new Date().getTime())
    })
  }

  unlock () {
    let me = this
    return new Promise((resolve) => {
      me.expires = new Date().getTime() + me.lifetime * 60000
      me.assertTimerRunning()
      me.fireEvent('stateChange', me, me.getState())
      me.fireEvent('unlock', me)
      resolve(me.expires)
    })
  }

  lock () {
    let me = this
    return new Promise((resolve) => {
      if (me.presenceObserver) {
        me.presenceObserver.stopChecking()
      }
      if (me.timer) {
        clearTimeout(me.timer)
      }
      me.expires = 0
      me.fireEvent('stateChange', me, me.getState())
      me.fireEvent('lock', me)
      resolve(me.expires)
    })
  }

  assertTimerRunning () {
    let me = this
    if (String(me.validity).toLowerCase() === 'presence') {
      if (!me.presenceObserver) {
        me.presenceObserver = new Tine.Tinebase.PresenceObserver({
          maxAbsenceTime: me.lifetime,
          absenceCallback: me.lock.bind(me),
          presenceCallback: me.updateState.bind(me)
        })
      } else {
        me.presenceObserver.startChecking()
      }
    } else {
      if (!me.timer) {
        let lifetime = Math.min(new Date().getTime() - me.expires, me.lifetime * 60000)
        me.timer = setTimeout(me.lock.bind(me), lifetime)
      }
    }
  }

  setState (state) {
    let _ = window.lodash
    let expires = _.get(state, 'expires', 0)
    if (!_.isNumber(expires)) {
      _.set(state, 'expires', new Date(expires).getTime())
    }
    Object.assign(this, state)
  }

  getState () {
    return {
      area: this.area,
      expires: this.expires
    }
  }

  updateState () {
    let me = this
    if (me.expires && String(me.validity).toLowerCase() === 'presence' && me.presenceObserver) {
      let expires = me.presenceObserver.getLastPresence() + me.lifetime * 60000
      if (expires !== me.expires) {
        me.expires = expires
        me.fireEvent('stateChange', me, me.getState())
      }
    }
    // timers are not running after page reload / new window
    if (me.expires > new Date().getTime()) {
      me.assertTimerRunning()
    }
  }
}

export default AbstractProvider
