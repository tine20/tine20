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
 * @todo        remove old function calls
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
        $this->objects['filter'] = array(
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
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getAllContacts(NULL, 'id', 'ASC', 0, 10, NULL);
        $filter['containerType'] = 'all';
        $contacts = $json->searchContacts(Zend_Json::encode($filter));
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }    

    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByOwner()
    {
        $json = new Addressbook_Json();
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getContactsByOwner(NULL, Zend_Registry::get('currentAccount')->getId(), 'id', 'ASC', 0, 10, NULL);
        $filter['containerType'] = 'personal';
        $filter['owner'] = Zend_Registry::get('currentAccount')->getId();
        $contacts = $json->searchContacts(Zend_Json::encode($filter));
        
        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
        
    /**
     * try to get shared contacts
     *
     */
    public function testGetSharedContacts()
    {
        $json = new Addressbook_Json();
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getSharedContacts(NULL, 'id', 'ASC', 0, 10, NULL);
        $filter['containerType'] = 'shared';
        $contacts = $json->searchContacts(Zend_Json::encode($filter));
        
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
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getOtherPeopleContacts(NULL, 'id', 'ASC', 0, 10, NULL);
        $filter['containerType'] = 'otherUsers';
        $contacts = $json->searchContacts(Zend_Json::encode($filter));
        
        $this->assertEquals(0, $contacts['totalcount']);
    }
        
    /**
     * try to get contacts by owner
     *
     */
    public function testGetContactsByAddressbookId()
    {
        $json = new Addressbook_Json();
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getContactsByAddressbookId($this->container->id, NULL, 'id', 'ASC', 0, 10, NULL);
        $filter['container'] = array($this->container->id);
        $contacts = $json->searchContacts(Zend_Json::encode($filter));
        
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
        $filter = $this->objects['filter'];
        
        //$contacts = $json->getUsers(NULL, 'id', 'ASC', 10, 0, NULL);
        $filter['containerType'] = 'internal';
        $contacts = $json->searchContacts(Zend_Json::encode($filter));

        $this->assertGreaterThan(0, $contacts['totalcount']);
    }
    
    /**
     * try to delete a contact
     *
     */
    public function testAddGetDeleteContact()
    {
        $newContact = array(
            'n_family'  => 'PHPUNIT',
            'owner'     => $this->container->id
        );

        $json = new Addressbook_Json();

        $contact = $json->saveContact(Zend_Json::encode($newContact));

        $this->assertArrayNotHasKey('errorMessage', $contact);
        $this->assertGreaterThan(0, $contact['updatedData']['id'], 'returned contactId not > 0');

        $contactId = $contact['updatedData']['id'];

        $contact = $json->getContact($contactId);

        $this->assertEquals($contactId, $contact['contact']['id']);

        $json->deleteContacts($contactId);

        $this->setExpectedException('UnderflowException');
        
        $contact = $json->getContact($contactId);
    }
}		
