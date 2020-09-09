/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import * as global from 'globalFakes'
require('configManager')

describe('configManager', () => {
  beforeEach(() => {
    global.registry()
  })

  let uit = Tine.Tinebase.configManager

  it('loads config from registry', () => {
    Tine.Tinebase.registry.set('config', {
      someKey: {
        value: true,
        definition: {
          type: 'bool'
        }
      }
    })

    expect(uit.get('someKey')).to.be.true
  })

  it('saves config to registry', () => {
    uit.set('setbyconfigmanager', 'value')
    expect(Tine.Tinebase.registry.get('config')).to.have.a.property('setbyconfigmanager')
  })

  it('can cope with non existing registries', () => {
    expect(uit.get('key', 'value', 'app')).to.be.undefined
    expect(() => uit.set('key', 'value', 'app')).to.not.throw()
  })

  it('can get/set simple config values', () => {
    uit.set('key', 'value')
    expect(uit.get('key')).to.equal('value')
  })

  it('can get nested values by path', () => {
    uit.set('one', { 'two': 'three' })
    expect(uit.get('one.two')).to.equal('three')
  })

  it('can set nested values by path', () => {
    uit.set('do.did', 'done')
    expect(uit.get('do')).to.deep.equal({ 'did': 'done' })
  })
})
