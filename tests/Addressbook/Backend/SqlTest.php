<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Backend_SqlTest::main');
}

/**
 * Test class for Tinebase_Account
 */
class Addressbook_Backend_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Addressbook_Backend_SqlTest');
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
        $this->objects['initialContact'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 20,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => 10,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        )); 
        
        $this->objects['updatedAccount'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 20,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => 10,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        )); 
        
        return;
        
		$this->expectFailure['TestRecord']['testSetId'][] = array('2','3');
		$this->expectFailure['TestRecord']['testSetId'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecord']['testSetId'][] = array('2','2');
		
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','3');
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','2');
		
		$this->expectSuccess['TestRecord']['testSetFromArray'][] = array(array('test_1'=>'2', 'test_2'=>NULL), 'test_1');
		$this->expectFailure['TestRecord']['testSetFromArrayException'][] = array('Tinebase_Record_Exception_Validation', array('test_2' => 'string'), );
		$this->expectFailure['TestRecord']['testSetTimezoneException'][] = array('Exception', 'UTC', );
		
    	$dummy = array(
					'test_id'=>2, 
					'test_2'=>'',
					'date_single' => $date->getIso(), 
					'date_multiple'=>'');
  	  	$this->expectSuccess['TestRecord']['testToArray'][] = array($dummy);
  	  	
  	  	
  	  	$this->expectSuccess['TestRecord']['__set'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['__get'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['test__isset'][] = array('test_id');
  	  	
  	  	$this->expectFailure['TestRecord']['test__isset'][] = array('string');
  	  	
  	  	
  	  	$this->expectFailure['TestRecord']['test__setException'][] = array( 'UnexpectedValueException', 'test_100',);
		$this->expectFailure['TestRecord']['test__getException'][] = array( 'UnexpectedValueException', 'test_100',);
		
  	  	
  	  	$this->expectFailure['TestRecord']['testOffsetUnset'][] = array( 'Tinebase_Record_Exception_NotAllowed', 'test_2',);
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
     * try to add a contact
     *
     */
    public function testAddContact()
    {
        $contact = Addressbook_Backend_Sql::getInstance()->addContact($this->objects['initialContact']);
        
        $this->assertEquals(20, $contact->id);
    }

    /**
     * try to update a contact
     *
     */
    public function testUpdateContact()
    {
    }

    /**
     * try to delete a contact
     *
     */
    public function testDeleteContact()
    {
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Addressbook_Backend_SqlTest::main') {
    Addressbook_Backend_SqlTest::main();
}
