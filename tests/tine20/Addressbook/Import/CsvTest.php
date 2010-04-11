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
     * @var string $_filename
     */
    protected $_filename = NULL;
    
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // cleanup
        if (file_exists($this->_filename)) {
            unlink($this->_filename);
        }
    }
    
    /**
     * test import data
     *
     */
    public function testImport()
    {
        // import
        $result = $this->_doImport(array('dryrun' => 1), new Addressbook_Model_ContactFilter(array()));
        
        // check
        $this->assertGreaterThan(0, $result['totalcount'], 'Didn\'t import anything.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $result['results'][0]['account_id']);
    }

    /**
     * test import data
     *
     */
    public function testImportDuplicates()
    {
        $internalContainer = Tinebase_Container::getInstance()->getInternalContainer(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::GRANT_READ);
        $options = array(
            'dryrun'        => 0,
            'container_id'  => $internalContainer->getId(),
            'duplicates'    => 1,
        );
        // import
        $result = $this->_doImport($options, new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',    'operator' => 'equals', 'value' => $internalContainer->getId()),
        )));
        
        //print_r($result);
        
        // check
        $this->assertGreaterThan(0, $result['duplicatecount'], 'no duplicates.');
    }
    
    /**
     * 
     * @param array $_options
     * @param Addressbook_Model_ContactFilter $_exportFilter
     * @return array
     */
    protected function _doImport(array $_options, Addressbook_Model_ContactFilter $_exportFilter)
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        $this->_instance = new Addressbook_Import_Csv($definition, Addressbook_Controller_Contact::getInstance(), $_options);
        
        // export first
        $exporter = new Addressbook_Export_Csv();
        $this->_filename = $exporter->generate($_exportFilter);
        
        // then import
        $result = $this->_instance->import($this->_filename);
        
        return $result;
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_Import_CsvTest::main') {
    Addressbook_Import_CsvTest::main();
}
