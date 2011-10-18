<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * @var boolean
     */
    protected $_deleteImportFile = TRUE; 
    
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
        if (file_exists($this->_filename) && $this->_deleteImportFile) {
            unlink($this->_filename);
        }
    }
    
    /**
     * test import duplicate data
     */
    public function testImportDuplicates()
    {
        $internalContainer = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
        $options = array(
            'container_id'  => $internalContainer->getId(),
        );
        $result = $this->_doImport($options, 'adb_tine_import_csv', new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',    'operator' => 'equals', 'value' => $internalContainer->getId()),
        )));
        
        $this->assertGreaterThan(0, $result['duplicatecount'], 'no duplicates.');
        $this->assertTrue($result['exceptions'] instanceof Tinebase_Record_RecordSet);
    }
    
    /**
     * import google contacts
     */
    public function testImportGoogleContacts()
    {
        $this->_filename = dirname(__FILE__) . '/files/google_contacts.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array('dryrun' => TRUE), 'adb_google_import_csv');
        
        $this->assertEquals(5, $result['totalcount']);
        $this->assertEquals('Niedersachsen Ring 22', $result['results'][4]->adr_one_street);
        $this->assertEquals('abc@here.de', $result['results'][3]->email);
        $this->assertEquals('+49227913452', $result['results'][0]->tel_work);
    }
    
    /**
     * import helper
     * 
     * @param array $_options
     * @param string $_definitionName
     * @param Addressbook_Model_ContactFilter $_exportFilter
     * @return array
     */
    protected function _doImport(array $_options, $_definitionName, Addressbook_Model_ContactFilter $_exportFilter = NULL)
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($_definitionName);
        $this->_instance = Addressbook_Import_Csv::createFromDefinition($definition, $_options);
        
        // export first
        if ($_exportFilter !== NULL) {
            $exporter = new Addressbook_Export_Csv($_exportFilter, Addressbook_Controller_Contact::getInstance());
            $this->_filename = $exporter->generate();
        }
        
        // then import
        $result = $this->_instance->importFile($this->_filename);
        
        return $result;
    }
}
