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
            //'owner'                 => '', //-- create import container
            //'title'                 => '',
            'n_family'              => 'Nachname',
            'n_given'               => 'Vorname',
            //'n_prefix'              => array('Anrede', ' ', 'Titel'),
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
        
        //print_r($contactRecords->toArray());
        
        $this->assertEquals(3, count($contactRecords));
        $this->assertEquals('Krehl, Albert', $contactRecords[0]->n_fileas);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_Import_CsvTest::main') {
    Addressbook_Import_CsvTest::main();
}
