/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* global Ext,Tine,i18n,postal */
import GenericProvider from 'MFA/Providers/Generic'
import UserPasswordProvider from 'MFA/Providers/UserPassword'
import PinProvider from 'MFA/Providers/Pin'
import TokenProvider from 'MFA/Providers/Token'
import SmsProvider from 'MFA/Providers/Sms'
import YubicoOTPProvider from 'MFA/Providers/YubicoOTP'
import HTOTPAuthenticatorProvider from 'MFA/Providers/HTOTPAuthenticator'
import WebAuthnProvider from 'MFA/Providers/WebAuthn'

let providerMap = {
  Tinebase_Model_MFA_TOTPUserConfig: HTOTPAuthenticatorProvider,
  Tinebase_Model_MFA_HOTPUserConfig: HTOTPAuthenticatorProvider,
  Tinebase_Model_MFA_SmsUserConfig: SmsProvider,
  Tinebase_Model_MFA_UserPassword: UserPasswordProvider,
  Tinebase_Model_MFA_PinUserConfig: PinProvider,
  Tinebase_Model_MFA_TokenUserConfig: TokenProvider,
  Tinebase_Model_MFA_YubicoOTPUserConfig: YubicoOTPProvider,
  Tinebase_Model_MFA_WebAuthnUserConfig: WebAuthnProvider
}

class AreaLocks extends Ext.util.Observable {
  /**
   * @property {Object} areaOptions run time area options
   */
  /**
   * @property {Object} providerInstances areaName: providerInstance
   */
  /**
   * @property {Object} presenceObservers areaName: presenceObserver
   */
  /**
   * @property {Object} timers areaName: timer
   */

  constructor (config) {
    super(config);
    
    this.dataSafeAreaName = 'Tinebase_datasafe'
    this.LoginAreaName = 'Tinebase_login'
    
    this.clearClientLockState()
    
    this.areaOptions = {}
    this.providerInstances = {}
    this.presenceObservers = {}
    this.timers = {}
    this.maskElCollection = []
    
    this.addEvents(
        /**
         * @event locking
         * Fires when an area is locking async.
         * @param {AreaLocks} this
         * @param {string} areaName
         * @param {Object} lockState the output of {@link #getLockState} function
         */
        'locking',
        /**
         * @event lock
         * Fires when an area got locked.
         * @param {AreaLocks} this
         * @param {string} areaName
         * @param {Object} lockState the output of {@link #getLockState} function
         */
        'lock',
        /**
         * @event unlocking
         * Fires when an area is unlocking async.
         * @param {AreaLocks} this
         * @param {string} areaName
         * @param {Object} lockState the output of {@link #getLockState} function
         */
        'unlocking',
        /**
         * @event unlock
         * Fires when an area got unlocked.
         * @param {AreaLocks} this
         * @param {string} areaName
         * @param {Object} lockState the output of {@link #getLockState} function
         */
        'unlock',
        /**
         * @event stateChange
         * Fires when the state of a lock changes.
         * @param {AreaLocks} this
         * @param {string} areaName
         * @param {Object} lockState the output of {@link #getLockState} function
         */
        'stateChange'
    )

    Object.assign(this, config)

    const configs = _.get(Tine.Tinebase.configManager.get('areaLocks', 'Tinebase'), 'records', []);
    configs.forEach((config) => {
      this.assertTimerRunning(config.area_name);
    })
  }

  async getProvider (areaName, optionOverrides) {
    const opts = this.getOptions(areaName, optionOverrides)
    const mfaDevices = opts.mfaDevices || await Tine.Tinebase_AreaLock.getUsersMFAUserConfigs(areaName)
    let selectedDevice = mfaDevices[0]
    
    _.each(mfaDevices, (mfaDevice) => {
      mfaDevice.device_name = mfaDevice.mfa_config_id + (mfaDevice.note ? ` (${mfaDevice.note})` : '')
    })
    if (mfaDevices.length < 1) {
      throw new Error('NOVALIDMFA');
    }
    if (mfaDevices.length > 1) {
      selectedDevice = _.find(mfaDevices, {
        id: await Tine.widgets.dialog.MultiOptionsDialog.getOption({
          title: window.i18n._('Choose MFA Deivce'),
          questionText: window.i18n._('This area is locked. Which device should be used to unlock.'),
          height: 100 + mfaDevices.length * 30,
          allowCancel: true,
          options: _.map(mfaDevices, (mfaDevice) => {
            return { text: mfaDevice.device_name, name: mfaDevice.id };
          })
        })
      })
    }
    
    let providerClass = _.get(providerMap, _.get(selectedDevice, 'config_class'), GenericProvider)
    const key = `${areaName}.${providerClass}`
    
    if (!this.providerInstances[key]) {
      // @TODO give areaLock or mfaDevice info or lockState into provider?
      this.providerInstances[key] = new providerClass(_.assign({
        areaName: areaName,
        mfaDevice: selectedDevice
      }, opts))
    }
    if (this.providerInstances[key].mfaDevice !== selectedDevice) {
      this.providerInstances[key].mfaDevice=selectedDevice;
      this.providerInstances[key].passwordFieldLabel=selectedDevice.device_name;
    }
    return this.providerInstances[key]
  }

  /**
   * get areaLock config
   *
   * config definition: @see Tinebase_Model_AreaLockConfig
   * [{
   *   area_name: string
   *   areas: [],                     // name of area
   *   provider_configs: [],          // array of alternative provider configs
   *   validity: 'once', '            // 'session', 'lifetime', 'presence'
   *   lifetime: seconds,             // absolute lifetime from unlock
   * }]
   *
   * @param {String} areaName
   * @return {Object}
   */
  getConfig (areaName, overrides) {
    const config = Tine.Tinebase.configManager.get('areaLocks', 'Tinebase')
    const areaConfig = _.find(_.get(config, 'records', []), { area_name: areaName })

    // hack to get _user specific_ provider_configs from exception
    if (overrides && areaConfig) {
      _.assign(areaConfig, overrides)
      Tine.Tinebase.configManager.set('areaLocks', config, 'Tinebase')
    }
    return areaConfig
  }

  /**
   * get areaLock state from registry
   *
   * [{
   *   area: 'string',
   *   expires: datetime (user timezone)
   * }]
   */
  getLockState (areaName) {
    let lockStates = Tine.Tinebase.registry.get('areaLocks')

    return _.find(lockStates, { area: areaName }) || {area: areaName, expires: '1970-01-01 01:00:00'}
  }

  /**
   * set areaLock state in registry
   *
   * @param {String} areaName
   * @param {Object} lockState
   */
  setLockState (areaName, lockState) {
    const lockStates = Tine.Tinebase.registry.get('areaLocks') || []
    const oldLockSate = _.find(lockStates, { area: areaName })
    
    _.remove(lockStates, { area: areaName })
    lockStates.push(lockState)
    Tine.Tinebase.registry.set('areaLocks', lockStates)
    if (JSON.stringify(oldLockSate !== JSON.stringify(lockState))) {
      this.fireEvent('stateChange', this, areaName, lockState)
    }
  }

  // requred when page reload happend while (un)locking
  clearClientLockState () {
    const lockStates = Tine.Tinebase.registry.get('areaLocks') || []
    _.each(lockStates, (lockState) => {
      delete lockState.isUnlocking
      delete lockState.isLocking
    })
    Tine.Tinebase.registry.set('areaLocks', lockStates)
  }

  /**
   *
   * @param {String} selector
   * @param {Ext.Element} maskEl
   */
  registerMaskEl (selector, maskEl, skipMsg) {
    maskEl.skipMsg = skipMsg;
    this.maskElCollection.push({ selector, maskEl });
  }

  async manageMask (areaName) {
    const config = this.getConfig(areaName, {}) || {};
    const maskEls = _.compact([this.getOptions(areaName)?.maskEl]) // legacy
    _.forEach(config.areas, (areaSelector) => {
      const selectorRe = new RegExp(`^${areaSelector}(\..*)*`)
      this.maskElCollection.forEach((mer) => {
        if (mer.selector.match(selectorRe) && mer.maskEl?.dom) {
          maskEls.push(mer.maskEl);
        }
      })
    });

    if (maskEls.length) {
      const isLocked = await this.isLocked(areaName)
      if (isLocked) {
        const lockState = this.getLockState(areaName)
        if (lockState.isUnlocking) {
          maskEls.forEach((maskEl) => { if (!maskEl.skipMsg) { maskEl.mask(i18n._('Unlocking ...'), 'x-mask-loading') } })
        } else {
          maskEls.forEach((maskEl) => {
            let mask = maskEl.mask(maskEl.skipMsg ? '' : i18n._('Click here to unlock this area.'), 'tb-arealocks-msg')
            mask.next().on('click', () => {
              this.unlock(areaName)
            })
          })
        }
      } else {
        maskEls.forEach((maskEl) => { maskEl.unmask() })
      }
    }
  }

  assertTimerRunning (areaName) {
    const conf = this.getConfig(areaName)
    const lockState = this.getLockState(areaName)
    const validity = String(_.get(conf, 'validity', 'session')).toLowerCase()
    let expires = lockState.expires
    
    if (validity === 'presence') {
      if (!this.presenceObservers[areaName]) {
        this.presenceObservers[areaName] = new Tine.Tinebase.PresenceObserver({
          maxAbsenceTime: conf.lifetime,
          absenceCallback: _.bind(this.lock, this, areaName),
          presenceCallback: (lastPresenceDate, po, ttl) => {
            const lockState = this.getLockState(areaName)
            const expires = new Date(lastPresenceDate.getTime() + conf.lifetime * 60000).format(Date.patterns.ISO8601Long)
            if (expires !== lockState.expires) {
              this.setLockState(areaName, _.assign(lockState, {expires: expires}))
            }
            postal.publish({
              channel: 'areaLocks',
              topic: [areaName, 'ttl'].join('.'),
              data: {expires, ttl}
            })
          }
        })
      } else {
        this.presenceObservers[areaName].startChecking()
        if (expires >= new Date().format(Date.patterns.ISO8601Long)) {
          expires = new Date(this.presenceObservers[areaName].getLastPresence() + conf.lifetime * 60000).format(Date.patterns.ISO8601Long)
        }
      }
    } else if (validity === 'lifetime') {
      if (!this.timer[areaName]) {
        const timeout = Math.min(Date.parseDate(lockState.expires, Date.patterns.ISO8601Long).getTime() - new Date().getTime(), conf.lifetime * 60000)
        this.timer[areaName] = setTimeout(_.bind(this.lock, this, areaName), timeout)
        expires = new Date(new Date().getTime() + timeout).format(Date.patterns.ISO8601Long)
      }
    }

    if (expires !== lockState.expires) {
      this.setLockState(areaName, _.assign(lockState, {expires: expires}))
    }
  }
  
  // public functions
  
  /**
   * lock given area
   * @param {String} areaName
   * @return {Promise<*>}
   */
  async lock (areaName) {
    const lockState = this.getLockState(areaName)
    if (lockState.isUnlocking) return
    
    this.setLockState(areaName, _.assign(lockState, {isLocking: true}))
    this.fireEvent('locking', areaName, lockState)
    
    if (this.presenceObservers[areaName]) {
      this.presenceObservers[areaName].stopChecking()
    }
    if (this.timers[areaName]) {
      clearTimeout(this.timers[areaName])
      delete this.timers[areaName]
    }
    this.setLockState(areaName, _.assign(lockState, await Tine.Tinebase_AreaLock.lock(areaName), {isLocking: false}))
    this.manageMask(areaName)

    postal.publish({
      channel: 'areaLocks',
      topic: [areaName, 'locked'].join('.'),
      data: lockState
    })

    // auto unlock
    let options = this.getOptions(areaName)
    if (options.maskEl && options.maskEl.isVisible(true)) {
      this.unlock(areaName)
    }
    
    this.fireEvent('lock', this, areaName, lockState)
    return lockState.expires
  }

  async unlock (areaName, opts) {
    const lockState = this.getLockState(areaName)
    this.manageMask(areaName)
    
    if (await this.isLocked(areaName) && !lockState.isUnlocking) {
      this.manageMask(areaName)
      this.setLockState(areaName, _.assign(lockState, {isUnlocking: true}))
      this.fireEvent('unlocking', areaName, lockState)
      
      try {
        const provider = await this.getProvider(areaName, opts)
        const serverLockState = await provider.unlock()
        this.setLockState(areaName, _.assign(lockState, serverLockState, {isUnlocking: false}))
        this.assertTimerRunning(areaName)
        this.manageMask(areaName)

        this.fireEvent('unlocked', areaName, this.getLockState(areaName))
        postal.publish({
          channel: 'areaLocks',
          topic: [areaName, 'unlocked'].join('.'),
          data: lockState
        })
        
      } catch (e) {
        this.setLockState(areaName, _.assign(lockState, {isUnlocking: false}))
        this.manageMask(areaName)
        return this.onMFAFail(areaName, e, opts)
      }
    }
  }

  async onMFAFail (areaName, e, opts) {
    return new Promise((resolve, reject) => {
      if (e.message === 'NOVALIDMFA') {
        Ext.MessageBox.show({
          title: window.i18n._('MFA device missing'),
          msg: window.i18n._('No matching MFA device for this area found. Configure a matching device please.'),
          icon: Ext.MessageBox.INFO,
          buttons: Ext.MessageBox.OK
        });
        return resolve();
      }
      if (e.message !== 'USERABORT') {
        Tine.log.error(e)
        Ext.MessageBox.show({
          title: window.i18n._hidden(e.message),
          msg: window.i18n._('Try again?'),
          icon: Ext.MessageBox.WARNING,
          buttons: Ext.MessageBox.YESNO,
          fn: (btn) => {
            if (btn === 'yes') {
              const retryMethod = _.get(opts, 'retryMethod', _.bind(this.unlock, this))
              return resolve(retryMethod(areaName, opts))
            }
            return this.onMFAFail(areaName, new Error('USERABORT'), opts).then(resolve).catch((e) => reject(e))
          }
        })
      } else {
        // Note: we only reject if the USERABORTMethod is set to 'reject' as most userland code can't cope with the rejection
        const userAbortMethod = _.get(opts, 'USERABORTMethod', (e) => { return e });
        if (userAbortMethod === 'reject') {
          return reject(e)
        }
        return resolve(userAbortMethod())
      }
    });
  }
  
  isLocked (areaName, askServer) {
    const lockState = this.getLockState(areaName)
    
    if (askServer) {
      return Tine.Tinebase_AreaLock.getState(areaName).then((serverData) => {
        this.setLockState(areaName, _.assign(lockState, serverData))
        return this.isLocked(areaName)
      })
    }
    
    return lockState.expires ? Date.parseDate(lockState.expires, Date.patterns.ISO8601Long).getTime() < new Date().getTime() : true;
  }


  /**
   * returns areaNames of all areaLocks matching given selectors
   * @param {Array} areaSelectors
   * @param {Boolean} lockedOnly
   * @return {Array}
   */
  getLocks (areaSelectors, lockedOnly) {
    areaSelectors = _.isArray(areaSelectors) ? areaSelectors : [areaSelectors];
    // take parent selectors into account (e.g. Sales.Boilerplates is also matched by Sales)
    areaSelectors = _.uniq(_.reduce(areaSelectors, (areaSelectors, areaSelector) => {
      areaSelector.split('.').reduce((selector, part) => {
        const areaSelector = _.compact([selector, part]).join('.');
        areaSelectors.push(areaSelector);
        return areaSelector;
      }, '');
      return areaSelectors;
    }, []));
    const configs = _.get(Tine.Tinebase.configManager.get('areaLocks', 'Tinebase'), 'records', []);
    return _.compact(_.reduce(configs, (locks, conf, idx) => {
      const areaName = _.get(conf, 'area_name');
      const hasIntersection = _.intersection(_.get(conf, 'areas', []), areaSelectors).length;
      return _.concat(locks, (hasIntersection && (!lockedOnly || this.isLocked(areaName))) ? areaName : []);
    }, []))
  }
  
  setOptions (areaName, options) {
    this.areaOptions[areaName] = options
  }

  getOptions (areaName, overrides) {
    const opts =  this.areaOptions[areaName] || {}
    return _.assign(opts, overrides || {})
    // return _.assign(overrides || {}, _.pick(opts, _.keys(overrides)));
  }

  handleAreaLockException (exception) {
    const areaName = exception.area
    const mfaDevices = exception.mfaUserConfigs
    this.setLockState(areaName,{area: areaName, expires: '1970-01-01 01:00:00'});
    this.manageMask(areaName)
    return this.unlock(areaName, {mfaDevices, USERABORTMethod: 'reject'})
  }
}

export { AreaLocks }
