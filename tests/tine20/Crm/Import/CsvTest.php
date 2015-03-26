<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Crm_Import_Csv
 */
class Crm_Import_CsvTest extends ImportTestCase
{
    protected $_importerClassName = 'Crm_Import_Csv';
    protected $_exporterClassName = 'Crm_Export_Csv';
    protected $_modelName = 'Crm_Model_Lead';

    /**
     * test import
     */
    public function testImport()
    {
        $result = $this->_importHelper('leads.csv');
        $this->assertEquals(2, $result['totalcount'], 'should import 2 records: ' . print_r($result, true));

        $firstLead = $result['results']->getFirstRecord();
        $this->assertContains('neuer lead', $firstLead->lead_name);
        $this->assertEquals(1, count($firstLead->tags));
        $this->assertEquals(5, count($firstLead->relations),
            'relations not imported for first lead ' . print_r($firstLead->toArray(), true));
        $this->assertEquals(6, count($result['results'][1]->relations),
            'relations not imported for second lead ' . print_r($result['results'][1]->toArray(), true));
    }

    protected function _importHelper($importFilename, $definitionName = 'crm_tine_import_csv', $dryrun = true)
    {
        $this->_testNeedsTransaction();

        $this->_testContainer = $this->_getTestContainer('Crm');
        $this->_filename = dirname(__FILE__) . '/files/' . $importFilename;
        $this->_deleteImportFile = false;

        $options = array(
            'container_id'  => $this->_testContainer->getId(),
            'dryrun' => $dryrun,
        );

        $result = $this->_doImport($options, $definitionName);

        return $result;
    }
}
