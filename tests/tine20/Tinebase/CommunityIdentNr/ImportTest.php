<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  CommunityIdentNr
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Import tests for CommunityIdentNr
 *
 * @package     Tinebase
 * @subpackage  CommunityIdentNr
 */
class Tinebase_CommunityIdentNr_ImportTest extends ImportTestCase
{
    protected $_modelName = Tinebase_Model_CommunityIdentNr::class;
    protected $_deleteImportFile = false;

    public function testXlsImport()
    {
        $this->_filename = __DIR__ . '/files/gemeindeNr2.xlsx';
        $definition = 'tinebase_import_communityidentnr';
        $result = $this->_doImport([], $definition);

        $this->assertEquals(15, $result['totalcount'], print_r($result, true));
        $this->assertEquals(0, $result['failcount'], print_r($result, true));
        $record = $result['results']->getFirstRecord();
        $this->assertEquals('Schleswig-Holstein',
            $record->gemeindenamen, print_r($record->toArray(), true));
        
        $updatedRecord = Tinebase_Controller_CommunityIdentNr::getInstance()->get($record->id);
        $this->assertNotEmpty($updatedRecord->relations, 'No relation Found!');
        $this->assertEquals('IMPORTFILE', $updatedRecord->relations->getFirstRecord()->type, 'Importfile relation is missing!');
    }
}
