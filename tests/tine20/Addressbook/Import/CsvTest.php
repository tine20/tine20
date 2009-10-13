<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Import_CsvTest::main');
}

/**
 * Test class for Addressbook_Import_Csv
 */
class Addressbook_Import_CsvTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Addressbook_Import_Csv instance
     */
    protected $_instance = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Csv Import Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $definitionBackend = new Tinebase_ImportExportDefinition();
        $definition = $definitionBackend->getByProperty('adb_tine_import_csv');
        
        $this->_instance = new Addressbook_Import_Csv($definition, Addressbook_Controller_Contact::getInstance(), array('dryrun' => 1));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
    
    /**
     * test import data
     *
     */
    public function testImport()
    {
        // export first
        $exporter = new Addressbook_Export_Csv();
        $filename = $exporter->generate(new Addressbook_Model_ContactFilter(array()));
        
        // then import
        $result = $this->_instance->import($filename);
        
        // check
        $this->assertGreaterThan(0, $result['totalcount'], 'Didn\'t import anything.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $result['results'][0]['account_id']);
        
        //cleanup
        unset($filename);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_Import_CsvTest::main') {
    Addressbook_Import_CsvTest::main();
}
