/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

require('data/Record')

describe('Record', () => {
  global.log()

  // needs to be uppercase because we want to create a new record with "new Uit()"
  let Uit

  beforeEach(() => {
    Uit = Tine.Tinebase.data.Record.create([
      { name: 'id' },
      { name: 'name' }
    ], {
      appName: 'Tinebase',
      modelName: 'TestModel',
      idProperty: 'id',
      titleProperty: 'name',
      recordName: 'TestModel',
      recordsName: 'TestModels'
    })
  })

  it('sets null values correctly', () => {
    var record = new Uit({
      'name': null
    })
    record.set('name', 'null')
    expect(record.data.name).to.equal('null')
    record.set('name', null)
    expect(record.data.name).to.equal(null)
  })
})
