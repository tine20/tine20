/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as global from 'globalFakes'

// TODO generic bootstrap
require('Locale')
require('Locale/Gettext')
require('common')
require('ux/Printer/Printer')
require('ux/Printer/renderers/Base')
require('extInit')
require('data/Record')
require('data/RecordProxy')
require('data/TitleRendererManager')
require('widgets/grid/RendererManager')
require('widgets/grid/GridPanel')
require('Models')

require('widgets/relation/GenericPickerGridPanel')

describe('GenericPickerGridPanel', () => {
  global.log()

  let uit

  beforeEach(() => {
    uit = Tine.widgets.relation.GenericPickerGridPanel.prototype
  })

  it('renders a missing related_record', () => {
    require('../../../../../../tine20/Projects/js/Model')
    var result = uit.relatedRecordRenderer(undefined,
      {}, new Tine.Tinebase.Model.Relation({ 'related_model': 'Projects_Model_Project' }))
    expect(result).to.equal('No Access')
  })

  it('renders a related_record', () => {
    require('../../../../../../tine20/Projects/js/Model')
    var result = uit.relatedRecordRenderer({
      number: '1223',
      status: 'IN-PROCESS',
      title: 'ascasc'
    }, null, new Tine.Tinebase.Model.Relation({ 'related_model': 'Projects_Model_Project' }))
    expect(result).to.equal('ascasc')
  })
})
