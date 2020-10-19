<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ExampleApplication
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for ExampleApplication_ImportTest
 */
class ExampleApplication_ImportTest extends ImportTestCase
{
    protected $_modelName = 'ExampleApplication_Model_ExampleRecord';
    protected $_deleteImportFile = false;

    public function testGenericImportExport()
    {
        $this->_filename = __DIR__ . '/files/import.csv';
        $result = $this->_doImport();

        $this->assertEquals(1, $result['totalcount'], print_r($result, true));
        $this->assertEquals(0, $result['failcount'], print_r($result, true));
        $record = $result['results']->getFirstRecord();
        $this->assertEquals('minimal example record by PHPUnit::ExampleApplication_ImportTest', $record->name, print_r($record->toArray(), true));
    }

    public function testCliImport()
    {
        $this->_filename = __DIR__ . '/files/import.csv';
        $cli = new ExampleApplication_Frontend_Cli();
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array(
            'model=' . $this->_modelName,
            $this->_filename
        ));

        ob_start();
        $cli->import($opts);
        $out = ob_get_clean();

        $this->assertStringContainsString('Imported 1 records', $out);
    }
}
