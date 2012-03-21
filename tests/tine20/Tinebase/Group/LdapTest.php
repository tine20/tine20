<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Group_SqlTest::main');
}

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
     * @var Tinebase_User_LDAP
     */
    protected $_userLDAP = NULL;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Group_SqlTest');
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
     * try to add a group
     *
     */
    public function testAddGroup()
    {
        $group = $this->_groupLDAP->addGroup($this->objects['initialGroup']);
        
        $this->assertNotNull($group->id);
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $groups = $this->_groupLDAP->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
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
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        try {
            $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        } catch (Exception $e) {
            $user = $this->_userLDAP->getUserByLoginName($this->objects['initialAccount']->accountLoginName);
        }
        
        $this->_groupLDAP->setGroupMembers($group, array($user));
        
        $groupMembers = $this->_groupLDAP->getGroupMembers($group);
        
        $this->assertEquals(1, count($groupMembers));
        
        $this->_groupLDAP->removeGroupMember($group, $user);
        
        $this->_userLDAP->deleteUser($user);
    }
    
    /**
     * test setting no group members
     *
     */
    public function testSetNoGroupMembers()
    {
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        try {
            $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        } catch (Exception $e) {
            $user = $this->_userLDAP->getUserByLoginName($this->objects['initialAccount']->accountLoginName);
        }
        
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
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        try {
            $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        } catch (Exception $e) {
            $user = $this->_userLDAP->getUserByLoginName($this->objects['initialAccount']->accountLoginName);
        }
        
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
        $group = $this->_groupLDAP->getGroupByName('tine20phpunit');
        
        $this->objects['initialAccount']->accountPrimaryGroup = $group->getId();
        try {
            $user = $this->_userLDAP->addUser($this->objects['initialAccount']);
        } catch (Exception $e) {
            $user = $this->_userLDAP->getUserByLoginName($this->objects['initialAccount']->accountLoginName);
        }
        
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
        $group = $this->_groupLDAP->getGroupByName($this->objects['initialGroup']->name);

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
        $group = $this->_groupLDAP->getGroupByName($this->objects['updatedGroup']->name);
        $this->_groupLDAP->deleteGroups($group->getId());

        $this->setExpectedException('Exception');

        $group = $this->_groupLDAP->getGroupById($group->getId());
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_Group_SqlTest::main') {
    Tinebase_Group_SqlTest::main();
}
