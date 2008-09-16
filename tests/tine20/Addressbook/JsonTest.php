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
        // initialise global for this test suite
        $GLOBALS['Addressbook_JsonTest'] = array_key_exists('Addressbook_JsonTest', $GLOBALS) ? $GLOBALS['Addressbook_JsonTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->container = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Addressbook', 'PHPUNIT');
        } else {
            $this->container = $personalContainer[0];
        }
        
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
            'owner'                 => $this->container->id,
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
        
        $this->objects['updatedContact'] = new Addressbook_Model_Contact(array(
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
            'owner'                 => $this->container->id,
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
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
                
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }    

    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByOwner()
    {
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        // this is not working with a new database
        //$this->assertGreaterThan(0, $contacts['totalcount']);
    }
        
    /**
     * try to get shared contacts
     *
     */
    public function testGetSharedContacts()
    {
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'shared'),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        // this is not working with a new database        
        #$this->assertGreaterThan(0, $contacts['totalcount']);
    }
        
    /**
     * try to get other people contacts
     *
     */
    public function testGetOtherPeopleContacts()
    {
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'otherUsers'),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        $this->assertGreaterThanOrEqual(0, $contacts['totalcount'], 'getting other peoples contacts failed');
    }
        
    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByAddressbookId()
    {
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'singleContainer'),
            array('field' => 'container', 'operator' => 'equals',   'value' => $this->container->id),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        // this is not working with a new database
        #$this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    /**
     * try to get accounts
     *
     */
    public function testGetAccounts()
    {
        $json = new Addressbook_Json();
        $paging = $this->objects['paging'];
        
        $filter = array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'internal'),
        );
        $contacts = $json->searchContacts(Zend_Json::encode($filter), Zend_Json::encode($paging));

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
            'n_family'  => 'PHPUNIT',
            'owner'     => $this->container->id,
            'notes'     => Zend_Json::encode(array($note))
        );        

        $json = new Addressbook_Json();

        $newContact = $json->saveContact(Zend_Json::encode($newContactData));
        $this->assertEquals($newContactData['n_family'], $newContact['n_family'], 'Adding contact failed');
        
        $GLOBALS['Addressbook_JsonTest']['addedContactId'] = $newContact['id'];
    }
    
    /**
     * test getting contact
     *
     */
    public function testGetContact()
    {
        $contactId = $GLOBALS['Addressbook_JsonTest']['addedContactId'];
        $json = new Addressbook_Json();
        
        $contact = $json->getContact($contactId);
        
        $this->assertEquals('PHPUNIT', $contact['n_family'], 'getting contact failed');
    }

    /**
     * test updateing of a contct
     *
     */
    public function testUpdateContact()
    {
        $contactId = $GLOBALS['Addressbook_JsonTest']['addedContactId'];
        $json = new Addressbook_Json();
        
        $contact = $json->getContact($contactId);
        
        $contact['n_family'] = 'PHPUNIT UPDATE';
        $updatedContact = $json->saveContact(Zend_Json::encode($contact));
        
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
        $json = new Addressbook_Json();
        
        $json->deleteContacts($contactId);
        
        $this->setExpectedException('Exception');
        $contact = $json->getContact($contactId);
        
    }
    
}		
