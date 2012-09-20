<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo implement search test
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Controller_List
 */
class Addressbook_Controller_ListTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * the controller
     * 
     * @var Addressbook_Controller_List
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(FALSE);
        
        $this->_instance = Addressbook_Controller_List::getInstance();
        
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
            'name'         => 'initial list',
            'container_id' => $container->getId(),
            'members'      => array($this->objects['contact1'], $this->objects['contact2']),
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
        Tinebase_TransactionManager::getInstance()->rollBack();
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(TRUE);
    }
    
    /**
     * try to add a list
     * 
     * @return Addressbook_Model_List
     */
    public function testAddList()
    {
        $list = $this->objects['initialList'];

        $list = $this->_instance->create($list, FALSE);
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
        
        return $list;
    }
    
    /**
     * try to get a list
     */
    public function testGetList()
    {
        $list = $this->_instance->get($this->testAddList()->getId());
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
    }
    
    /**
     * try to update a list
     */
    public function testUpdateList()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $this->assertEquals(1, count($list->members));
        $contactId = $list->members[0];
        $contact = Addressbook_Controller_Contact::getInstance()->get($contactId);
        
        $this->assertEquals($this->objects['contact2']->adr_one_locality, $contact->adr_one_locality);
    }

    /**
     * try to add list member
     */
    public function testAddListMember()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $list = $this->_instance->addListMember($list, $this->objects['contact1']);
        
        $this->assertTrue(in_array($this->objects['contact1']->getId(), $list->members), 'contact1 not found in members: ' . print_r($list->members, TRUE));
        $this->assertTrue(in_array($this->objects['contact2']->getId(), $list->members), 'contact2 not found in members: ' . print_r($list->members, TRUE));
    }

    /**
     * try to remove list member
     */
    public function testRemoveListMember()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact1'], $this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $list = $this->_instance->removeListMember($list, $this->objects['contact1']);
        $this->assertEquals($list->members, array($this->objects['contact2']->getId()));
    }

    /**
     * try to delete a list
     */
    public function testDeleteList()
    {
        $id = $this->testAddList()->getId();
        $this->_instance->delete($id);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $list = $this->_instance->get($id);
    }
    
    /**
     * testHiddenMembers
     * 
     * @see 0007122: hide hidden users from lists
     */
    public function testHiddenMembers()
    {
        $group = new Tinebase_Model_Group(array(
            'name'          => 'testgroup',
            'description'   => 'test group',
            'visibility'    => Tinebase_Model_Group::VISIBILITY_DISPLAYED
        ));
        $group = Admin_Controller_Group::getInstance()->create($group);
        $list = $this->_instance->get($group->list_id);
        
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $list->members = array($sclever->contact_id);
        $list = $this->_instance->update($list);
        
        // hide sclever
        $sclever->visibility = Tinebase_Model_User::VISIBILITY_HIDDEN;
        Admin_Controller_User::getInstance()->update($sclever, NULL, NULL);
        
        // fetch list and check hidden members
        $listGet = $this->_instance->get($list->getId());
        $listSearch = $this->_instance->search(new Addressbook_Model_ListFilter(array(array(
            'field'    => 'id',
            'operator' => 'in',
            'value'    => array($list->getId()),
        ))))->getFirstRecord();
        $listGetMultiple = $this->_instance->getMultiple(array($list->getId()))->getFirstRecord();
        foreach (array('get' => $listGet, 'search' => $listSearch, 'getMultiple' => $listGetMultiple) as $fn => $listRecord) {
            $this->assertTrue($listRecord instanceof Addressbook_Model_List, $fn . ' did not return a list: ' . var_export($listRecord, TRUE));
            $this->assertEquals(0, count($listRecord->members), 'Hidden sclever should not appear in list members returned by ' . $fn
                . '(): ' . print_r($listRecord->toArray(), TRUE));
        }
    }
}
