<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_GroupTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_GroupTest');
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
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'id'            => 'testgrouplkfdshew7fdfwo8efw',
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        ));
        $this->objects['initialGroup'] = Tinebase_Group::getInstance()->addGroup($this->objects['initialGroup']);
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'id'            => 'testgrouplkfdshew7fdfwo8efw',
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
        ));
        
        $this->objects['noIdGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit noid',
            'description'   => 'noid group'
        ));
        
        // add accounts for group member tests
        $this->objects['account1'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 'testaccountdsjdsud8hjd10',
            'accountLoginName'      => 'tine20phpunit1',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de',
            'visibility'            => Tinebase_Model_User::VISIBILITY_DISPLAYED,
        ));
        
        $this->objects['account2'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 'testaccountdsjdsud8hjd11',
            'accountLoginName'      => 'tine20phpunit2',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 2',
            'accountFirstName'      => 'PHPUnit 2',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));

        $this->objects['account3'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 'testaccountdsjdsud8hjd12',
            'accountLoginName'      => 'tine20phpunit3',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 3',
            'accountFirstName'      => 'PHPUnit 3',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));
        foreach (array('account1', 'account2', 'account3') as $user) {
            Admin_Controller_User::getInstance()->create($this->objects[$user], NULL, NULL);
        }
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
    }
    
    /**
     * try to add a group
     */
    public function testAddGroup()
    {
        $this->assertEquals(Tinebase_Model_Group::VISIBILITY_HIDDEN, $this->objects['initialGroup']->visibility);
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $groups = Tinebase_Group::getInstance()->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = Tinebase_Group::getInstance()->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $group = Tinebase_Group::getInstance()->getGroupById($this->objects['initialGroup']->id);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
        
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $group = $this->objects['updatedGroup'];
        $group->visibility = 'displayed';
        $group->list_id = null;
        
        $group = Tinebase_Group::getInstance()->updateGroup($group);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
        $this->assertEquals('hidden', $group->visibility);
    }
    
    /**
     * try to set/get group members
     */
    public function testSetGroupMembers()
    {
        $setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
        
        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
        
        $this->assertEquals(sort($setGroupMembersArray), sort($getGroupMembersArray));
    }

    /**
     * try to add a group member
     */
    public function testAddGroupMember()
    {
        $setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
        
        Tinebase_Group::getInstance()->addGroupMember($this->objects['initialGroup']->id, $this->objects['account3']->accountId);

        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
        
        $expectedValues = array($this->objects['account1']->accountId, $this->objects['account2']->accountId, $this->objects['account3']->accountId);
        $this->assertEquals(sort($expectedValues), sort($getGroupMembersArray));
    }
    
    /**
     * try to remove a group member
     */
    public function testRemoveGroupMember()
    {
        $setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId, $this->objects['account3']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
        
        Tinebase_Group::getInstance()->removeGroupMember($this->objects['initialGroup']->id, $this->objects['account3']->accountId);
        
        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
        
        $this->assertEquals(2, count($getGroupMembersArray));
        $expectedValues = array($this->objects['account1']->accountId, $this->objects['account2']->accountId);
        $this->assertEquals(sort($expectedValues), sort($getGroupMembersArray));
    }

    /**
     * try to delete a group
     */
    public function testDeleteGroup()
    {
        Tinebase_Group::getInstance()->deleteGroups($this->objects['initialGroup']);
        
        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');
        $group = Tinebase_Group::getInstance()->getGroupById($this->objects['initialGroup']);
    }

  /**
     * try to convert group id and check if correct exceptions are thrown 
     */
    public function testConvertGroupIdToInt()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        Tinebase_Model_Group::convertGroupIdToInt (0);
    }

      /**
     * try to convert id of group object and check if correct exceptions are thrown 
     */
    public function testConvertGroupIdToIntWithGroup()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        Tinebase_Model_Group::convertGroupIdToInt($this->objects['noIdGroup']);
    }
    
    /**
     * testGetDefaultGroup
     */
    public function testGetDefaultGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    /**
     * testGetDefaultAdminGroup
     */
    public function testGetDefaultAdminGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    /**
     * testSetGroupMemberships
     */
    public function testSetGroupMemberships()
    {
        $currentGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        
        $newGroupMemberships = current($currentGroupMemberships);
                        
        Tinebase_Group::getInstance()->setGroupMemberships(Tinebase_Core::getUser(), array($newGroupMemberships));
        $newGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        
        $this->assertNotEquals($currentGroupMemberships, $newGroupMemberships);
        
        Tinebase_Group::getInstance()->setGroupMemberships(Tinebase_Core::getUser(), $currentGroupMemberships);
        $newGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        
        $this->assertEquals($currentGroupMemberships, $newGroupMemberships);
    }
    
    /**
     * testSyncLists
     * 
     * @see 0005768: create addressbook lists when migrating users
     */
    public function testSyncLists()
    {
        $group = $this->objects['initialGroup'];
        Tinebase_User::syncContact($this->objects['account1']);
        Tinebase_User::getInstance()->updateUserInSqlBackend($this->objects['account1']);
        
        $this->testSetGroupMembers();
        Tinebase_Group::syncListsOfUserContact(array($group->getId()), $this->objects['account1']->contact_id);
        $group = Tinebase_Group::getInstance()->getGroupById($this->objects['initialGroup']);
        $this->assertTrue(! empty($group->list_id), 'list id empty: ' . print_r($group->toArray(), TRUE));
        
        $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->assertEquals($group->getId(), $list->group_id);
        $this->assertEquals($group->name, $list->name);
        $this->assertTrue(! empty($list->members), 'list members empty: ' . print_r($list->toArray(), TRUE) 
            . ' should contain: ' . print_r($this->objects['account1']->toArray(), TRUE));
        $this->assertEquals($this->objects['account1']->contact_id, $list->members[0]);
        
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        $this->assertTrue(! empty($appConfigDefaults), 'app config defaults empty');
        $internal = $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK];
        $this->assertEquals($internal, $list->container_id, 'did not get correct internal container');
        
        // sync again -> should not change anything
        Tinebase_Group::syncListsOfUserContact(array($group->getId()), $this->objects['account1']->contact_id);
        $listAgain = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->assertEquals($list->toArray(), $listAgain->toArray());
        
        // change list id -> should get list by (group) name
        $group->list_id = NULL;
        $group = Tinebase_Group::getInstance()->updateGroup($group);
        Tinebase_Group::syncListsOfUserContact(array($group->getId()), $this->objects['account1']->contact_id);
        $this->assertEquals($list->getId(), Tinebase_Group::getInstance()->getGroupById($group)->list_id);
    }
}
