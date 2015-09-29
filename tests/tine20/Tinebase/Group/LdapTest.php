<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Group_LdapTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql group backend
     *
     * @var Tinebase_Group_Sql
     */
    protected $_groupSQL = NULL;
    
    /**
     * ldap group backend
     *
     * @var Tinebase_Group_LDAP
     */
    protected $_groupLDAP = NULL;
    
    /**
     * ldap user backend
     *
     * @var Tinebase_User_Ldap
     */
    protected $_userLDAP = NULL;
    
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
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP) {
            $this->markTestSkipped('LDAP backend not enabled');
        }

        $this->_groupLDAP = Tinebase_Group::factory(Tinebase_Group::LDAP);
        $this->_userLDAP  = Tinebase_User::factory(Tinebase_User::LDAP);
        $this->_groupSQL  = Tinebase_Group::factory(Tinebase_Group::SQL);
                
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        ));
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
        ));
         
        $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => 'must be set to valid groupid',
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));

        $this->objects['groups'] = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        $this->objects['users'] = new Tinebase_Record_RecordSet('Tinebase_Model_FullUser');
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP) {
            return;
        }

        $this->_groupLDAP->deleteGroups($this->objects['groups']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Deleting users: ' . print_r($this->objects['users']->toArray(), true));

        $this->_userLDAP->deleteUsers($this->objects['users']->getArrayOfIds());
    }
    
    /**
     * try to add a group
     *
     * @return Tinebase_Model_Group
     */
    public function testAddGroup()
    {
        $group = $this->_groupLDAP->addGroup($this->objects['initialGroup']);
        $this->objects['groups']->addRecord($group);
        
        $this->assertNotNull($group->id);
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
        return $group;
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $this->testAddGroup();
        $groups = $this->_groupLDAP->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $this->testAddGroup();
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $this->testAddGroup();
        $group = $this->_groupLDAP->getGroupByName($this->objects['initialGroup']->name);
        
        $group = $this->_groupLDAP->getGroupById($group->getId());
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * test setting group members
     *
     */
    public function testSetGroupMembers()
    {
        $group = $this->testAddGroup();

        $user = $this->_addUserToGroup($group);
        $this->_groupLDAP->setGroupMembers($group, array($user));
        
        $groupMembers = $this->_groupLDAP->getGroupMembers($group);
        
        $this->assertEquals(1, count($groupMembers));
        
        $this->_groupLDAP->removeGroupMember($group, $user);
        
        $this->_userLDAP->deleteUser($user);
    }

    protected function _addUserToGroup($group)
    {
        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        $this->objects['users']->addRecord($user);
        return $user;
    }
    
    /**
     * test setting no group members
     *
     */
    public function testSetNoGroupMembers()
    {
        $group = $this->testAddGroup();

        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        $this->objects['users']->addRecord($user);

        $this->_groupLDAP->setGroupMembers($group, array($user));
        
        $this->_groupLDAP->setGroupMembers($group, array());
        
        $groupMembers = $this->_groupLDAP->getGroupMembers($group);
        
        $this->assertEquals(0, count($groupMembers));
        
        $this->_userLDAP->deleteUser($user);
    }
    
    /**
     * test adding group members
     *
     */
    public function testAddGroupMember()
    {
        $group = $this->testAddGroup();

        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        $this->objects['users']->addRecord($user);
        
        $this->_groupLDAP->addGroupMember($group, $user);
        
        $groupMembers = $this->_groupLDAP->getGroupMembers($group);
        
        $this->assertEquals(1, count($groupMembers));
        
        $this->_groupLDAP->removeGroupMember($group, $user);
        
        $this->_userLDAP->deleteUser($user);
    }
    
    /**
     * test deleting groupmembers
     *
     */
    public function testRemoveGroupMember()
    {
        $group = $this->testAddGroup();

        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        $this->objects['users']->addRecord($user);
        
        $this->_groupLDAP->addGroupMember($group, $user);
                
        $this->_groupLDAP->removeGroupMember($group, $user);
        
        $groupMembers = $this->_groupLDAP->getGroupMembers($group);
        
        $this->assertEquals(0, count($groupMembers));
        
        $this->_userLDAP->deleteUser($user);
    }
    
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $group = $this->testAddGroup();

        $this->objects['updatedGroup']->id = $group->getId();
        $group = $this->_groupLDAP->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to delete a group
     *
     */
    public function testDeleteGroups()
    {
        $group = $this->testAddGroup();
        $this->_groupLDAP->deleteGroups($group->getId());

        $this->setExpectedException('Exception');

        $group = $this->_groupLDAP->getGroupById($group->getId());
    }

    /**
     * @see 0009852: improve cache cleaning after LDAP sync
     */
    public function testSyncGroups()
    {
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();

        $group = $this->testAddGroup();
        $user = $this->_addUserToGroup($group);

        // add user to group (only in LDAP)
        $this->_groupLDAP->addGroupMemberInSyncBackend($defaultUserGroup->getId(), $user);

        // trigger caching
        $memberships = $this->_groupLDAP->getGroupMembers($defaultUserGroup);
        $this->assertFalse(in_array($user->getId(), $memberships));

        // sync users
        Tinebase_User::syncUsers(array('syncContactData' => TRUE));

        // check group memberships
        $memberships = $this->_groupLDAP->getGroupMembers($defaultUserGroup);
        $this->assertTrue(in_array($user->getId(), $memberships), 'group memberships not updated: ' . print_r($memberships, true));
    }
}
