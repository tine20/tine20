/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'
import AbstractProvider from 'AreaLocks/AbstractProvider'

require('configManager')
require('PresenceObserver')

describe('AreaLocks', () => {
  global.log()

  let mockProvider
  let AreaLocks
  let uit

  beforeEach(() => {
    global.registry()

    // @TODO: get rid of inject-loader, it hurts with phantomjs
    /* eslint import/no-webpack-loader-syntax: off */
    let fileInjector = require('inject-loader!AreaLocks.es6')

    // mock the providers
    mockProvider = sinon.spy()
    mockProvider.on = sinon.spy()

    AreaLocks = fileInjector({
      'AreaLocks/UserPasswordProvider': AbstractProvider,
      'AreaLocks/PinProvider': AbstractProvider,
      'AreaLocks/TokenProvider': AbstractProvider
    }).AreaLocks

    Tine.Tinebase.configManager.set('areaLocks', {
      'records': [{
        'area': 'area51',
        'provider': 'Pin',
        'validity': 'PRESENCE',
        'lifetime': 15
      }]
    })

    uit = new AreaLocks()
  })

  it('can be instanciated', () => {
    expect(uit).to.be.instanceof(AreaLocks)
  })

  it('knows locks from config', () => {
    expect(uit.hasLock('area51')).to.be.true
    expect(uit.hasLock('area52')).to.be.false
  })

  it('locks areas per default', () => {
    return expect(uit.isLocked('area51')).to.eventually.be.true
  })

  // it('does a lot more things')
})
