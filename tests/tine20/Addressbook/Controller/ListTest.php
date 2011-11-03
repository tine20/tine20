<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Addressbook_Controller_ListTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook List Controller Tests');
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
        $GLOBALS['Addressbook_Controller_ListTest'] = array_key_exists('Addressbook_Controller_ListTest', $GLOBALS) ? $GLOBALS['Addressbook_ListControllerTest'] : array();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];

        $this->objects['contact1'] = new Addressbook_Model_Contact(array(
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
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact1',
            'n_fileas'              => 'Contact1, List',
            'n_given'               => 'List',
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
        $this->objects['contact1'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact1'], FALSE);
        
        $this->objects['contact2'] = new Addressbook_Model_Contact(array(
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
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact2',
            'n_fileas'              => 'Contact2, List',
            'n_given'               => 'List',
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
        $this->objects['contact2'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact2'], FALSE);
        
        $this->objects['initialList'] = new Addressbook_Model_List(array(
            'name'	=> 'initial list',
            'container_id' => $container->getId(),
            'members' => array($this->objects['contact1'], $this->objects['contact2'])
        )); 
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Addressbook_Controller_Contact::getInstance()->delete(array(
            $this->objects['contact1']->getId(),
            $this->objects['contact2']->getId()
        ));
    }
    
    /**
     * try to add a list
     *
     */
    public function testAddList()
    {
        $list = $this->objects['initialList'];

        $list = Addressbook_Controller_List::getInstance()->create($list, FALSE);
        
        $GLOBALS['Addressbook_ListControllerTest']['listId'] = $list->getId();
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
    }
    
    /**
     * try to get a list
     *
     */
    public function testGetList()
    {
        $list = Addressbook_Controller_List::getInstance()->get($GLOBALS['Addressbook_ListControllerTest']['listId']);
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
        $this->assertEquals($GLOBALS['Addressbook_ListControllerTest']['listId'], $list->getId());
    }
    
    /**
     * try to update a list
     *
     */
    public function testUpdateList()
    {
        $list = Addressbook_Controller_List::getInstance()->get($GLOBALS['Addressbook_ListControllerTest']['listId']);
        $list->members = array($this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
        
        #$this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
        #$this->assertEquals($this->objects['updatedContact']->n_given." ".$this->objects['updatedContact']->n_family, $contact->n_fn);
    }

    /**
     * try to add list member
     *
     */
    public function testAddListMember()
    {
        $list = Addressbook_Controller_List::getInstance()->get($GLOBALS['Addressbook_ListControllerTest']['listId']);
        $list->members = array($this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
        
        $list = Addressbook_Controller_List::getInstance()->addListMember($list, $this->objects['contact1']);
        
        $this->assertTrue(in_array($this->objects['contact1']->getId(), $list->members), 'contact1 not found in members: ' . print_r($list->members, TRUE));
        $this->assertTrue(in_array($this->objects['contact2']->getId(), $list->members), 'contact2 not found in members: ' . print_r($list->members, TRUE));
    }

    /**
     * try to remove list member
     *
     */
    public function testRemoveListMember()
    {
        $list = Addressbook_Controller_List::getInstance()->get($GLOBALS['Addressbook_ListControllerTest']['listId']);
        $list->members = array($this->objects['contact1'], $this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
        
        $list = Addressbook_Controller_List::getInstance()->removeListMember($list, $this->objects['contact1']);
        #var_dump($list->members);
        $this->assertEquals($list->members, array($this->objects['contact2']->getId()));
    }

    /**
     * try to delete a list
     *
     */
    public function testDeleteList()
    {

        Addressbook_Controller_List::getInstance()->delete($GLOBALS['Addressbook_ListControllerTest']['listId']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $list = Addressbook_Controller_List::getInstance()->get($GLOBALS['Addressbook_ListControllerTest']['listId']);
    }

    /**
     * try to delete a contact
     *
     */
    public function _testDeleteUserAccountContact()
    {
        $this->setExpectedException('Addressbook_Exception_AccessDenied');
        $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        Addressbook_Controller_Contact::getInstance()->delete($userContact->getId());
    }    
}
