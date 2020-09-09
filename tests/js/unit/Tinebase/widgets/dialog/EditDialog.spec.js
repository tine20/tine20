/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

// TODO generic bootstrap?
require('common')
require('ux/Array')
require('extInit')
require('data/Record')
require('data/RecordProxy')

describe('EditDialog', () => {
  global.log()
  global.i18n()

  let uit

  beforeEach(() => {
    uit = Tine.widgets.dialog.EditDialog.prototype
  })

  it('copies relations of tasks', () => {
    require('Tasks/js/Models')
    var record = new Tine.Tasks.Model.Task({
      summary: 'some task',
      relations: [{
        id: 'relationid'
      }]
    })
    var copy = uit.getCopyRecordData(record, Tine.Tasks.Model.Task, false)
    expect(copy.relations).to.not.be.undefined
  })

  it('does not copy relations if in copyOmitFields', () => {
    require('../../../../../../tine20/Addressbook/js/Model')
    var ContactModelWithCopyOmitRelation = Tine.Tinebase.data.Record.create(Tine.Addressbook.Model.ContactArray, {
      appName: 'Addressbook',
      modelName: 'Contact',
      idProperty: 'id',
      titleProperty: 'n_fn',
      // ngettext('Contact', 'Contacts', n); gettext('Contacts');
      recordName: 'Contact',
      recordsName: 'Contacts',
      containerProperty: 'container_id',
      // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
      containerName: 'Addressbook',
      containersName: 'Addressbooks',
      copyOmitFields: ['account_id', 'type', 'relations']
    })
    var record = new ContactModelWithCopyOmitRelation({
      n_fn: 'some contact',
      relations: [{
        id: 'relationid'
      }]
    })
    var copy = uit.getCopyRecordData(record, ContactModelWithCopyOmitRelation, false)
    expect(copy.n_fn).to.equal('some contact (copy)')
    expect(copy.relations).to.be.undefined
  })
})
