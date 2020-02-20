<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Addressbook_Controller_List
 *
 * TODO move test cases to Addressbook_Controller_ListTest
 */
class Addressbook_ListControllerTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Addressbook_Model_Contact::class,
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
            'bday'                  => '1975-01-02 03:04:05',
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
        
        $this->objects['initialList'] = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List(array(
            'name'         => 'initial list',
            'container_id' => $container->getId(),
            'members'      => array($this->objects['contact1'], $this->objects['contact2'])
        )));
    }

    /**
     * try to add a list
     */
    public function testAddList()
    {
        $list = $this->objects['initialList'];
        $this->assertEquals($this->objects['initialList']->name, $list->name);
    }
    
    /**
     * try to get a list
     */
    public function testGetList()
    {
        $list = Addressbook_Controller_List::getInstance()->get($this->objects['initialList']);
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
        $this->assertEquals($this->objects['initialList']->getId(), $list->getId());
    }
    
    /**
     * try to update a list
     *
     * @todo add assertions
     */
    public function testUpdateList()
    {
        $list = $this->objects['initialList'];
        $list->members = array($this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
    }

    /**
     * try to add list member
     */
    public function testAddListMember()
    {
        $list = $this->objects['initialList'];
        $list->members = array($this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
        $list = Addressbook_Controller_List::getInstance()->addListMember($list, $this->objects['contact1']);
        
        $this->assertTrue(in_array($this->objects['contact1']->getId(), $list->members));
        $this->assertTrue(in_array($this->objects['contact2']->getId(), $list->members));
    }
    
    /**
     * testInternalAddressbookConfig
     * 
     * @see http://forge.tine20.org/mantisbt/view.php?id=5846
     */
    public function testInternalAddressbookConfig()
    {
        $list = $this->objects['initialList'];
        $list->container_id = NULL;
        $listBackend = new Addressbook_Backend_List();
        $listBackend->update($list);
        
        Admin_Config::getInstance()->delete(Tinebase_Config::APPDEFAULTS);
        $list = Addressbook_Controller_List::getInstance()->addListMember($list, $this->objects['contact1']);
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        
        $this->assertTrue(! empty($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK]), print_r($appConfigDefaults, TRUE));
    }

    /**
     * try to remove list member
     */
    public function testRemoveListMember()
    {
        $list = $this->objects['initialList'];
        $list->members = array($this->objects['contact1'], $this->objects['contact2']);
        
        $list = Addressbook_Controller_List::getInstance()->update($list);
        
        $list = Addressbook_Controller_List::getInstance()->removeListMember($list, $this->objects['contact1']);
        $this->assertEquals($list->members, array($this->objects['contact2']->getId()));
    }

    /**
     * try to delete a list
     */
    public function testDeleteList()
    {
        Addressbook_Controller_List::getInstance()->delete($this->objects['initialList']->getId());

        $this->setExpectedException('Tinebase_Exception_NotFound');
        $list = Addressbook_Controller_List::getInstance()->get($this->objects['initialList']);
    }

    /**
     * try to delete a contact
     */
    public function _testDeleteUserAccountContact()
    {
        $this->setExpectedException('Addressbook_Exception_AccessDenied');
        $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        Addressbook_Controller_Contact::getInstance()->delete($userContact->getId());
    }

    /**
     * @see 0011522: improve handling of group-lists
     */
    public function testChangeListWithoutManageGrant()
    {
        // try to set memberships without MANAGE_ACCOUNTS
        $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS, true);

        $listId = Tinebase_Group::getInstance()->getGroupByName('Secretary')->list_id;
        try {
            Addressbook_Controller_List::getInstance()->addListMember($listId, array($this->objects['contact1']->getId()));
            $this->fail('should not be possible to add list member to system group');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('No permission to add list member.', $tead->getMessage());
        }

        $listBeforeUpdate = Addressbook_Controller_List::getInstance()->get($listId);
        self::assertGreaterThan(0, count($listBeforeUpdate->members));
        $list = clone($listBeforeUpdate);
        // save the list and check if it still has its members
        Addressbook_Controller_List::getInstance()->update($list);
        $listBackend = new Addressbook_Backend_List();
        Tinebase_Core::getCache()->clean();
        $updatedList = $listBackend->get($listId);
        self::assertEquals($listBeforeUpdate->members, $updatedList->members);

        $updatedList->name = 'my new name';
        try {
            Addressbook_Controller_List::getInstance()->update($updatedList);
            $this->fail('should not be possible to set name of system group');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('You are not allowed to MANAGE_ACCOUNTS in application Admin !', $tead->getMessage());
        }
    }

    public function testAddSystemUserToList()
    {
        $list = $this->_createSystemList();
        $list->members = [Tinebase_Core::getUser()->contact_id];
        $updatedList = Addressbook_Controller_List::getInstance()->update($list);
        self::assertEquals(1, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // should be added to system group, too
        $groupMembers = Admin_Controller_Group::getInstance()->getGroupMembers($list->group_id);
        self::assertEquals(1, count($groupMembers),
            'user missing from group members: ' . print_r($groupMembers, true));

        // add another user and a non user contact to list
        $sclever = $this->_personas['sclever'];
        $updatedList->members = array_merge($updatedList->members, [$sclever->contact_id, $this->objects['contact1']->getId()]);
        $updatedListWithSclever = Addressbook_Controller_List::getInstance()->update($updatedList);
        self::assertEquals(3, count($updatedListWithSclever->members),
            'list members missing: ' . print_r($updatedListWithSclever->toArray(), true));

        $groupMembers = Admin_Controller_Group::getInstance()->getGroupMembers($list->group_id);
        self::assertEquals(2, count($groupMembers),
            'user missing from group members: ' . print_r($groupMembers, true));

        // set account_only in group -> user contacts should still be list member
        $adminJson = new Admin_Frontend_Json();
        $groupJson = $adminJson->getGroup($list->group_id);
        $groupJson['account_only'] = 1;
        $groupJson['members'] = $groupMembers;
        $groupJsonUpdated = $adminJson->saveGroup($groupJson);
        self::assertEquals(2, $groupJsonUpdated['members']['totalcount'], print_r($groupJsonUpdated, true));
    }

    protected function _createSystemList()
    {
        // create system group
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name'          => 'tine20phpunitgroup' . Tinebase_Record_Abstract::generateUID(6),
            'description'   => 'unittest group',
            'members'       => [],
        ]));

        // add system user contact to list
        $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->_listsToDelete[] = $list;
        return $list;
    }

    public function testAddNonSystemContactAndUpdategroupCheckModlog()
    {
        // create system list
        $list = $this->_createSystemList();

        // contacts (non-system + system)
        $list->members = [
            $this->objects['contact1']->getId(),
            Tinebase_Core::getUser()->contact_id,
        ];
        $updatedList = Addressbook_Controller_List::getInstance()->update($list);
        self::assertEquals(2, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // update group
        $adminJson = new Admin_Frontend_Json();
        $groupJson = $adminJson->getGroup($list->group_id);
        self::assertEquals(1, $groupJson['members']['totalcount'], print_r($groupJson, true));
        $groupJson['name'] = 'updated unittest group';
        $groupJson['members'] = [];
        $adminJson->saveGroup($groupJson);

        // contact should still be in the list!
        $updatedList = Addressbook_Controller_List::getInstance()->get($list->getId());
        self::assertEquals(1, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // check modlog
        $modlogs = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(
            'Addressbook',
            $list->getId(),
            Addressbook_Model_List::class
        );
        self::assertEquals(3, count($modlogs), 'should have 2 update and 1 create modlogs:'
            . print_r($modlogs->toArray(), true));
        $modlogs->sort('seq');
        self::assertEquals('created', $modlogs[0]->change_type);
        $diffSecondUpdate = json_decode($modlogs[2]->new_value);
        self::assertTrue(isset($diffSecondUpdate->diff->members));
        self::assertEquals(1, count($diffSecondUpdate->diff->members));
    }
}
