/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

require('Filemanager/js/QuickLookRegistry')
require('Felamimail/js/Felamimail')

describe('QuickLookRegistry', () => {
  global.log()

  let uit

  beforeEach(() => {
    // QL registry is persisted in the Filemanager registry
    global.registry('Filemanager')

    uit = Tine.Filemanager.QuickLookRegistry

    // mock for hasRight
    Tine.Tinebase.common.hasRight = function () {
      return true
    }
  })

  it('can register an item', () => {
    uit.register('text/plain', 'myxtype')
    expect(uit.has('text/plain')).to.be.true
    expect(uit.get('text/plain')).to.be.string('myxtype')
  })

  it('has item for email content type after Felamimail init', () => {
    // initialize Felamimail
    Tine.Felamimail.Application.prototype.registerQuickLookPanel()

    expect(uit.has('message/rfc822')).to.be.true
    expect(uit.get('message/rfc822')).to.be.string('felamimaildetailspanel')
  })
})
