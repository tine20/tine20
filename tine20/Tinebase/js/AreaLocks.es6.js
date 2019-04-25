/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* global Ext,Tine,i18n,postal */
import UserPasswordProvider from 'AreaLocks/UserPasswordProvider'
import PinProvider from 'AreaLocks/PinProvider'
import TokenProvider from 'AreaLocks/TokenProvider'

let providers = {
  UserPassword: UserPasswordProvider,
  Pin: PinProvider,
  Token: TokenProvider
}

class AreaLocks {
  /**
   * @property {Object} areaOptions run time area options
   */
  /**
   * @property {Object} providerInstances areaName: providerInstance
   */
  /**
   * @property {Object} unlockPromises areaName: unlockPromise
   */

  constructor () {
    this.areaOptions = {}
    this.providerInstances = {}
    this.unlockPromises = {}
  }

  getProvider (config, lockState) {
    if (!this.providerInstances[config.area]) {
      if (!providers[config.provider]) {
        Tine.log.error('AreaLocks.getProvider no such provider: ' + config.provider)
        return
      }

      this.providerInstances[config.area] = new providers[config.provider](config)
      this.providerInstances[config.area].on('unlock', this.onUnlock, this)
      this.providerInstances[config.area].on('lock', this.onLock, this)
      this.providerInstances[config.area].on('unlocking', this.manageMask.bind(this, config.area), this)
      this.providerInstances[config.area].on('stateChange', this.onStateChange, this)
    }

    this.providerInstances[config.area].setState(lockState)
    return this.providerInstances[config.area]
  }

  onUnlock (provider) {
    let area = provider.area
    this.manageMask(area)

    postal.publish({
      channel: 'areaLocks',
      topic: [area, 'unlocked'].join('.'),
      data: provider.getState()
    })
  }

  onLock (provider) {
    let me = this
    let area = provider.area
    this.manageMask(area)

    postal.publish({
      channel: 'areaLocks',
      topic: [area, 'locked'].join('.'),
      data: provider.getState()
    })

    // auto unlock
    let options = me.getOptions(area)
    if (options.maskEl && options.maskEl.isVisible(true)) {
      me.unlock(area)
    }
  }

  onStateChange (provider, state) {
    this.setLockState(provider.area, state)
  }

  /**
   * get areaLock config
   *
   * config definition:
   * [{
   *   area: 'string',        // name of area
   *   provider: 'string',    // PIN, token(auth_privaicyIdea), password, capture, ip, ...
   *   validity: 'once', '    // 'session', 'lifetime', 'presence', 'someotherthingonlyproviderunderstands',
   *   lifetime: seconds,     // absolute lifetime from unlock
   *   individual: true,      // each area must be unlocked individually (when applied hierarchically / with same provider) -> NOT YET
   *
   *   ... provider specific _public_ options
   * }]
   *
   * @param {String} area
   * @return {Object}
   */
  getConfig (area) {
    const _ = window.lodash
    const config = Tine.Tinebase.configManager.get('areaLocks', 'Tinebase')
    const areaConfig = _.find(_.get(config, 'records', []), { area: area })

    return areaConfig
  }

  /**
   * get areaLock state from registry
   *
   * [{
   *   area: 'string',
   *   expires: datetime (user timezone)
   *   ... provider specific _public_ state (would need server side event system - might not be needed)
   * }]
   */
  getLockState (area) {
    const _ = window.lodash
    let lockStates = Tine.Tinebase.registry.get('areaLocks')

    return _.find(lockStates, { area: area })
  }

  /**
   * set areaLock state in registry
   *
   * @param {String|Provider} area
   * @param {Object} lockState
   */
  setLockState (area, lockState) {
    const _ = window.lodash

    if (!Ext.isString(area)) {
      lockState = area.getState()
      area = area.area
    }

    let lockStates = Tine.Tinebase.registry.get('areaLocks') || []

    _.remove(lockStates, { area: area })
    lockStates.push(lockState)
    Tine.Tinebase.registry.set('areaLocks', lockStates)
  }

  manageMask (area) {
    let me = this
    let options = me.getOptions(area)

    // @TODO: support array of maskEls?
    if (options.maskEl) {
      me.isLocked(area).then((isLocked) => {
        if (isLocked) {
          let conf = this.getConfig(area)
          if (!conf) return Promise.reject(new Error('no areaLock configured for: ' + area))

          let lockState = this.getLockState(area)
          let provider = this.getProvider(conf, lockState)

          if (provider.isUnlocking) {
            options.maskEl.mask(i18n._('Unlocking ...'), 'x-mask-loading')
          } else {
            let mask = options.maskEl.mask(i18n._('Click here to unlock this area.'), 'tb-arealocks-msg')
            mask.next().on('click', () => {
              me.unlock(area)
            })
          }
        } else {
          options.maskEl.unmask()
        }
      })
    }
  }

  // public functions

  lock (area) {
    let me = this
    return me.isLocked(area)
      .then((locked) => {
        if (locked) {
          // it's already locked
          return Promise.resolve()
        } else {
          let conf = me.getConfig(area)
          let lockState = me.getLockState(area)
          let provider = me.getProvider(conf, lockState)

          return provider.lock()
        }
      })
  }

  unlock (area) {
    let me = this
    return me.isLocked(area)
      .then((locked) => {
        if (!locked) {
          // it's already unlocked
          return Promise.resolve()
        } else {
          let conf = me.getConfig(area)
          let lockState = me.getLockState(area)
          let provider = me.getProvider(conf, lockState)

          me.manageMask(area)
          if (!me.unlockPromises[area]) {
            me.unlockPromises = provider.unlock()
              .finally(() => {
                me.unlockPromises[area] = null
              })
              .catch(() => {
                // @TODO show failure message?
                me.unlock(area)
              })
          }
          return me.unlockPromises[area]
        }
      })
  }

  setOptions (area, options) {
    this.areaOptions[area] = options
  }

  getOptions (area) {
    return this.areaOptions[area] || {}
  }

  isLocked (area, askServer) {
    let conf = this.getConfig(area)
    if (!conf) return Promise.reject(new Error('no areaLock configured for: ' + area))

    let lockState = this.getLockState(area)
    let provider = this.getProvider(conf, lockState)
    return provider.isLocked(askServer)
  }

  hasLock (area) {
    let conf = this.getConfig(area)
    return !!conf
  }
}

export { AreaLocks, providers }
