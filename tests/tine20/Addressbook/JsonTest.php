<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        fix some of the search tests ("this is not working with a new database")
 * @todo        add testSetImage
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Addressbook_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * instance of test class
     *
     * @var Addressbook_Frontend_Json
     */
    protected $_instance;
    
    /**
     * @var bool allow the use of GLOBALS to exchange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * container to use for the tests
     *
     * @var Tinebase_Model_Container
     */
    protected $container;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Json Tests');
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
        $this->_instance = new Addressbook_Frontend_Json();
        
        // initialise global for this test suite
        $GLOBALS['Addressbook_JsonTest'] = array_key_exists('Addressbook_JsonTest', $GLOBALS) ? $GLOBALS['Addressbook_JsonTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        if ($personalContainer->count() === 0) {
            $this->container = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Addressbook', 'PHPUNIT');
        } else {
            $this->container = $personalContainer[0];
        }
            	
        // define filter
        $this->objects['paging'] = array(
            'start' => 0,
            'limit' => 10,
            'sort' => 'n_fileas',
            'dir' => 'ASC',
        );

        return;
        
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
     * try to get all contacts
     *
     */
    public function testGetAllContacts()
    {
        $paging = $this->objects['paging'];
                
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }    

    /**
     * try to get other people contacts
     *
     */
    public function testGetOtherPeopleContacts()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'otherUsers'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThanOrEqual(0, $contacts['totalcount'], 'getting other peoples contacts failed');
    }
        
    /**
     * try to get accounts
     *
     */
    public function testGetAccounts()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'internal'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);

        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    
    /**
     * test to add a contact
     *
     */
    public function testAddContact()
    {
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',            
        );
        
        $newContactData = array(
            'n_family'          => 'PHPUNIT',
            'container_id'      => $this->container->id,
            'notes'             => array($note),
            'tel_cell_private'  => '+49TELCELLPRIVATE',
        );        

        $newContact = $this->_instance->saveContact($newContactData);
        $this->assertEquals($newContactData['n_family'], $newContact['n_family'], 'Adding contact failed');
        
        $GLOBALS['Addressbook_JsonTest']['addedContactId'] = $newContact['id'];
    }
    
    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByTelephone()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'telephone', 'operator' => 'contains', 'value' => '+49TELCELLPRIVATE')
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        $this->assertEquals(1, $contacts['totalcount']);
    }

    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByAddressbookId()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'singleContainer'),
            array('field' => 'container', 'operator' => 'equals',   'value' => $this->container->id),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    /**
     * try to get contacts by owner / container_id
     *
     */
    public function testGetContactsByOwner()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',  'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
        
    /**
     * try to get shared contacts
     *
     */
    public function testGetSharedContacts()
    {
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'shared'),
        );
        $contacts = $this->_instance->searchContacts($filter, $paging);
        
        // this is not working with a new database
        #$this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    
    /**
     * test getting contact
     *
     */
    public function testGetContact()
    {
        $contactId = $GLOBALS['Addressbook_JsonTest']['addedContactId'];
        
        $contact = $this->_instance->getContact($contactId);
        
        $this->assertEquals('PHPUNIT', $contact['n_family'], 'getting contact failed');
    }

    /**
     * test updateing of a contct
     *
     */
    public function testUpdateContact()
    {
        $contactId = $GLOBALS['Addressbook_JsonTest']['addedContactId'];
        
        $contact = $this->_instance->getContact($contactId);
        
        $contact['n_family'] = 'PHPUNIT UPDATE';
        $updatedContact = $this->_instance->saveContact($contact);
        
        $this->assertEquals($contactId, $updatedContact['id'], 'updated produced a new contact');
        $this->assertEquals('PHPUNIT UPDATE', $updatedContact['n_family'], 'updating data failed');
        
    }
    
    /**
     * test setting a contact image
     * 
     * NOTE: we can't test the upload yet, so we needd to simulate the upload
     *
     *
    public function testSetImage()
    {
        
    }
    */
    
    /**
     * test deleting contact
     *
     */
    public function testDeleteContact()
    {
        $contactId = $GLOBALS['Addressbook_JsonTest']['addedContactId'];
        
        $this->_instance->deleteContacts($contactId);
        
        $this->setExpectedException('Exception');
        $contact = $this->_instance->getContact($contactId);
    }
    
    /**
     * get all salutations
     *
     */
    public function testGetSalutations()
    {
        $salutations = $this->_instance->getSalutations();
        
        $this->assertGreaterThan(2, $salutations['totalcount']);
    }
    
    /**
     * test import data
     *
     */
    public function testImport()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        
        // export first and create files array
        $exporter = new Addressbook_Export_Csv();
        $filename = $exporter->generate(new Addressbook_Model_ContactFilter(array()));
        $files = array(
            array('name' => $filename, 'path' => $filename)
        );
        
        // then import
        $result = $this->_instance->importContacts($files, $definition->getId(), $this->container->getId(), TRUE);
        //print_r($result);
        
        // check
        $this->assertGreaterThan(0, $result['totalcount'], 'Didn\'t import anything.');
        $this->assertEquals(0, $result['failcount'], 'Import failed for one or more recors.');
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $result['results'][0]['account_id'], 'Did not get user record.');
        
        //cleanup
        unset($filename);
    }    
}		
