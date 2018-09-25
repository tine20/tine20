/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

require('Tinebase/js/Models')
require('Filemanager/js/DocumentPreview')

describe('DocumentPreview', () => {
  global.log()

  let uit, emailFileNode, nonEmailfileNode

  beforeEach(() => {
    uit = Tine.Filemanager.DocumentPreview.prototype
    emailFileNode = new Tine.Tinebase.Model.Tree_Node({
      contenttype: 'message/rfc822',
      name: 'Re: etstst_37a807d9a9.eml'
    }, 2345)
    nonEmailfileNode = new Tine.Tinebase.Model.Tree_Node({
      contenttype: 'text/plain',
      name: 'some.txt'
    }, 1345)

    // mock for hasRight
    Tine.Tinebase.common.hasRight = function () {
      return true
    }
  })

  it('detects email file node', () => {
    expect(uit.hasEmailPreview(emailFileNode)).to.be.true
    expect(uit.hasEmailPreview(nonEmailfileNode)).to.be.false
  })
})
