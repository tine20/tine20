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
     * @var bool allow the use of GLOBALS to exchange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * @var array test objects
     */
    protected $_objects = array();

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
        // initialise global for this test suite
        $GLOBALS['Addressbook_Import_CsvTest'] = array_key_exists('Addressbook_Import_CsvTest', $GLOBALS) 
            ? $GLOBALS['Addressbook_Import_CsvTest'] 
            : array();
        
        $this->_instance = Addressbook_Import_Factory::factory('Csv');
        
        $this->_objects['filename'] = dirname(__FILE__) . '/files/test.csv';
        $this->_objects['mapping'] = array(
            'adr_one_locality'      => 'Ort',
            'adr_one_postalcode'    => 'Plz',
            'adr_one_street'        => 'Straße',
            'org_name'              => 'Name1',
            'org_unit'              => 'Name2',
            'note'                  => array(
                'Mitarbeiter'       => 'inLab Spezi',
                'Anzahl Mitarbeiter' => 'ANZMitarbeiter',
                'Bemerkung'         => 'Bemerkung',
            ),
            'tel_work'              => 'TelefonZentrale',
            'tel_cell'              => 'TelefonDurchwahl',
            'n_family'              => 'Nachname',
            'n_given'               => 'Vorname',
            'n_prefix'              => array('Anrede', 'Titel'),
        );        
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
     * read csv test
     *
     */
    public function testRead()
    {
        $contactRecords = $this->_instance->read($this->_objects['filename'], $this->_objects['mapping']);
        $GLOBALS['Addressbook_Import_CsvTest']['records'] = $contactRecords;
        
        $this->assertEquals(3, count($contactRecords));
        $this->assertEquals('Krehl, Albert', $contactRecords[0]->n_fileas);
        $this->assertEquals('Herr Dr.', $contactRecords[0]->n_prefix);
        
        $note = $contactRecords[0]->note;
        $this->assertEquals(1, preg_match("/Mitarbeiter: Meister/", $note));
        $this->assertEquals(1, preg_match("/Anzahl Mitarbeiter: 20/", $note));
    }
    
    /**
     * test import data
     *
     */
    public function testImport()
    {
        $contactRecords = $GLOBALS['Addressbook_Import_CsvTest']['records'];
        
        $importedContacts = $this->_instance->import($contactRecords);

        $this->assertEquals(3, count($importedContacts));
        
        $firstImportedContact = Addressbook_Controller::getInstance()->getContact($importedContacts[0]->getId());
        $this->assertEquals('Krehl, Albert', $firstImportedContact->n_fileas);
        $this->assertEquals('Herr Dr.', $firstImportedContact->n_prefix);
        $note = $firstImportedContact->note;
        $this->assertEquals(1, preg_match("/Mitarbeiter: Meister/", $note));
        $this->assertEquals(1, preg_match("/Anzahl Mitarbeiter: 20/", $note));
        
        // delete imported contacts
        foreach ($importedContacts as $contact) {
            Addressbook_Controller::getInstance()->deleteContact($contact->getId());
        }
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_Import_CsvTest::main') {
    Addressbook_Import_CsvTest::main();
}
