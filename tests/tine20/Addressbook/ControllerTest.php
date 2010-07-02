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
 * @todo        update tests to use new search/count functions
 * @todo        remove old function calls
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Addressbook_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var bool allow the use of GLOBALS to exchange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Controller Tests');
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
        $GLOBALS['Addressbook_ControllerTest'] = array_key_exists('Addressbook_ControllerTest', $GLOBALS) ? $GLOBALS['Addressbook_ControllerTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];
        
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
            'jpegphoto'             => file_get_contents(dirname(__FILE__) . '/../Tinebase/ImageHelper/phpunit-logo.gif'),
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Laars',
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
            'jpegphoto'             => '',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->id,
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
            	
        $this->objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
        ));
        
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
     * try to add a contact
     *
     */
    public function testAddContact()
    {
        $contact = $this->objects['initialContact'];
        $contact->notes = new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note']));
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        $GLOBALS['Addressbook_ControllerTest']['contactId'] = $contact->getId();
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * try to get a contact
     *
     */
    public function testGetContact()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->get($GLOBALS['Addressbook_ControllerTest']['contactId']);
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * test getImage function
     *
     */
    public function testGetImage()
    {
        $image = Addressbook_Controller::getInstance()->getImage($GLOBALS['Addressbook_ControllerTest']['contactId']);
        $this->assertType('Tinebase_Model_Image', $image);
        $this->assertEquals($image->width, 94);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByOwner()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',         'operator' => 'contains', 'value' => $this->objects['initialContact']->n_family),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByAddressbookId()
    {
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );        
        $container = $personalContainer[0];
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        ));
        $filter->container = array($container->getId());
        $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountOfAllContacts()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',         'operator' => 'contains', 'value' => $this->objects['initialContact']->n_family),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'all'),
        ));
        $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to update a contact
     *
     */
    public function testUpdateContact()
    {
        $this->objects['updatedContact']->setId($GLOBALS['Addressbook_ControllerTest']['contactId']);
        $contact = Addressbook_Controller_Contact::getInstance()->update($this->objects['updatedContact']);

        $this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
        $this->assertEquals($this->objects['updatedContact']->n_given." ".$this->objects['updatedContact']->n_family, $contact->n_fn);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_by', 'operator' => 'equals', 'value' => Zend_Registry::get('currentAccount')->getId())
        ));
        $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        $this->assertTrue($count > 0);
        
        $date = Zend_Date::now();
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_time', 'operator' => 'equals', 'value' => $date->toString('yyyy-MM-dd'))
        ));
        $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        $this->assertTrue($count > 0);
    }

    /**
     * test remove image
     *
     */
    public function testRemoveContactImage()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->get($GLOBALS['Addressbook_ControllerTest']['contactId']);
        $contact->jpegphoto = '';
        $this->setExpectedException('Exception');
        $image = Addressbook_Controller::getInstance()->getImage($contact->id);
    }
    
    /**
     * tests that exception gets thrown when contact has no image
     *
     */
    public function testGetImageException()
    {
        $this->setExpectedException('Exception');
        Addressbook_Controller::getInstance()->getImage($GLOBALS['Addressbook_ControllerTest']['contactId']);
    }
    
    /**
     * try to delete a contact
     *
     */
    public function testDeleteContact()
    {
        Addressbook_Controller_Contact::getInstance()->delete($GLOBALS['Addressbook_ControllerTest']['contactId']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $contact = Addressbook_Controller_Contact::getInstance()->get($GLOBALS['Addressbook_ControllerTest']['contactId']);
    }

    /**
     * try to delete a contact
     *
     */
    public function testDeleteUserAccountContact()
    {
        $this->setExpectedException('Addressbook_Exception_AccessDenied');
        $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        Addressbook_Controller_Contact::getInstance()->delete($userContact->getId());
    }
    
    /**
     * try to create a personal folder 
     *
     */
    public function testCreatePersonalFolder()
    {
        $account = Zend_Registry::get('currentAccount');
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account);
        $this->assertEquals(1, count($folder));
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account->getId());
        $this->assertEquals(1, count($folder));
    }
    
    /**
     * test in week operator of creation time filter
     */
    public function testCreationTimeWeekOperator()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count1 = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'creation_time', 'operator' => 'inweek',   'value' => 0),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count2 = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        $this->assertEquals($count1, $count2);
    }
    
    /**
     * test equals operator of creation time filter
     */
    public function testCreationTimeEqualsOperator()
    {
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count1 = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        
        $date = Zend_Date::now();
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'creation_time', 'operator' => 'equals',   'value' => $date->toString('yyyy-MM-dd')),
            array('field' => 'containerType', 'operator' => 'equals',   'value' => 'personal'),
            array('field' => 'owner',         'operator' => 'equals',   'value' => Zend_Registry::get('currentAccount')->getId()),
        ));
        $count2 = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        $this->assertEquals($count1, $count2);
    }
}
