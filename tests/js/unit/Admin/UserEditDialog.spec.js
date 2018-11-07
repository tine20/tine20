/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'
// TODO is all of this required? move to widgets?
require('Locale')
require('Locale/Gettext')
require('common')
require('ux/Printer/Printer')
require('ux/Printer/renderers/Base')
require('extInit')
require('data/Record')
require('data/RecordProxy')
require('widgets/grid/RendererManager')
require('widgets/grid/GridPanel')
require('Models')
require('widgets/dialog/EditDialog')
require('widgets/exportAction')
require('widgets/ActionUpdater')
require('widgets/grid/ExportButton')
require('widgets/dialog/TokenModeEditDialogPlugin')
require('widgets/customfields/EditDialogPlugin')
require('Admin/js/user/Users')

require('Admin/js/user/EditDialog')

describe('UserEditDialog', () => {
  global.log()

  let uit

  beforeEach(() => {
    uit = Tine.Admin.UserEditDialog.prototype
  })

  it('validates valid username', () => {
    expect(uit.validateLoginName('abcder.adgef917')).to.be.true
    expect(uit.validateLoginName('abcder_adgef917')).to.be.true
    expect(uit.validateLoginName('Abcder_Adgef917')).to.be.true
  })

  it('does not validate invalid username', () => {
    expect(uit.validateLoginName('aöäüp.adgef917')).to.be.false
    expect(uit.validateLoginName('}[/adgef917')).to.be.false
  })
})
