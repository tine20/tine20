/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as globals from 'globalFakes'

require('configManager')
require('data/Record')
require('data/RecordProxy')
require('common')

require('Felamimail/js/Model')

describe('Message', () => {
  // @todo make logging work
  globals.log(Tine.log.DEBUG)

  beforeEach(() => {
    globals.registry()
    globals.registry('Felamimail')

    Tine.Tinebase.registry.set('primarydomain', 'example.org')
    Tine.Tinebase.registry.set('secondarydomains', 'example.net,example.com')
    Tine.Felamimail.registry.set('config', {
      flagIconOwnDomain: {
        value: 'mydomain.icon',
        definition: {
          type: 'string'
        }
      },
      flagIconOtherDomain: {
        value: 'otherdomain.icon',
        definition: {
          type: 'string'
        }
      },
      flagIconOtherDomainRegex: {
        value: '\\.org',
        definition: {
          type: 'string'
        }
      }
    })
  })

  it('can access icon config', () => {
    const mydomainIcon = Tine.Tinebase.configManager.get('flagIconOwnDomain', 'Felamimail')
    expect(mydomainIcon).to.equal('mydomain.icon')
  })

  it('can be instanciated', () => {
    const record = new Tine.Felamimail.Model.Message({
      from_email: 'somemail@example.org'
    })

    expect(record).to.be.instanceof(Tine.Felamimail.Model.Message)
  })

  it('returns correct flag icon for mydomain (primary)', () => {
    const recordFromMyDomain = new Tine.Felamimail.Model.Message({
      from_email: 'somemail@example.org'
    })
    expect(recordFromMyDomain.getTine20Icon()).to.equal('mydomain.icon')
  })

  it('returns correct flag icon for mydomain (secondary)', () => {
    const recordFromMySecondaryDomain = new Tine.Felamimail.Model.Message({
      from_email: 'somemail@example.net'
    })
    expect(recordFromMySecondaryDomain.getTine20Icon()).to.equal('mydomain.icon')
  })

  it('returns correct flag icon for otherdomain', () => {
    const recordFromOtherDomain = new Tine.Felamimail.Model.Message({
      from_email: 'somemail@somedomain.org'
    })
    expect(recordFromOtherDomain.getTine20Icon()).to.equal('otherdomain.icon')
  })

  it('returns correct flag icon for other tine20 client', () => {
    const recordFromOtherDomain = new Tine.Felamimail.Model.Message({
      from_email: 'somemail@somedomain.com'
    })
    expect(recordFromOtherDomain.getTine20Icon()).to.equal('images/favicon.svg')
  })
})
