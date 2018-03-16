/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

// TODO generic bootstrap?
require('Locale')
require('Locale/Gettext')
require('common')
require('ux/Array')
require('extInit')
require('tineInit')
require('data/Record')
require('data/RecordProxy')

describe('EditDialog', () => {
  global.log()

  let uit

  beforeEach(() => {
    uit = Tine.widgets.dialog.EditDialog.prototype
  })

  it('copies relations of contacts', () => {
    require('../../../../../../tine20/Addressbook/js/Model')
    var record = new Tine.Addressbook.Model.Contact({
      n_fn: 'some contact',
      relations: [{
        id: 'relationid'
      }]
    })
    var copy = uit.getCopyRecordData(record, Tine.Addressbook.Model.Contact, false)
    expect(copy.relations).to.not.be.undefined
  })

  it('does not copy relations if in copyOmitFields', () => {
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
