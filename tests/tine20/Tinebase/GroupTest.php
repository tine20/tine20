<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_GroupTest::main');
}

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
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'id'            => 'testgrouplkfdshew7fdfwo8efw',
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'id'            => 'testgrouplkfdshew7fdfwo8efw',
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
        )); 

        $this->objects['noIdGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit noid',
            'description'   => 'noid group'
        )); 

        $this->objects['account1'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 'testaccountdsjdsud8hjd10',
            'accountLoginName'      => 'tine20phpunit1',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
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

        // add accounts for group member tests
        try {
        	Tinebase_User::getInstance()->getUserById($this->objects['account1']->accountId) ;
        } catch ( Exception $e ) {
	        Tinebase_User::getInstance()->addUser(  $this->objects['account1'] );
        }
        try {
        	Tinebase_User::getInstance()->getUserById($this->objects['account2']->accountId) ;
        } catch ( Exception $e ) {
	        Tinebase_User::getInstance()->addUser(  $this->objects['account2'] );
        }
        try {
        	Tinebase_User::getInstance()->getUserById($this->objects['account3']->accountId) ;
        } catch ( Exception $e ) {
	        Tinebase_User::getInstance()->addUser(  $this->objects['account3'] );
        }
        
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
		// remove accounts for group member tests
        Tinebase_User::getInstance()->deleteUser(  $this->objects['account1']->accountId );
        Tinebase_User::getInstance()->deleteUser(  $this->objects['account2']->accountId );
        Tinebase_User::getInstance()->deleteUser(  $this->objects['account3']->accountId );
    }
    
    /**
     * try to add a group
     *
     */
    public function testAddGroup()
    {
        $group = Tinebase_Group::getInstance()->addGroup($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
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
        $group = Tinebase_Group::getInstance()->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to set/get group members
     *
     */
    public function testSetGroupMembers()
    {        
    	$setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
    	
    	$getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
        
    	$this->assertEquals($setGroupMembersArray, $getGroupMembersArray);
    }		

    /**
     * try to add a group member
     *
     */
    public function testAddGroupMember()
    {
    	$setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
    	
        Tinebase_Group::getInstance()->addGroupMember($this->objects['initialGroup']->id, $this->objects['account3']->accountId);

		$getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
		
		$this->assertEquals ( array($this->objects['account1']->accountId, $this->objects['account2']->accountId, $this->objects['account3']->accountId), $getGroupMembersArray);
    }		
    
    /**
     * try to remove a group member
     *
     */
    public function testRemoveGroupMember()
    {
    	$setGroupMembersArray = array ( $this->objects['account1']->accountId, $this->objects['account2']->accountId, $this->objects['account3']->accountId );
        Tinebase_Group::getInstance()->setGroupMembers($this->objects['initialGroup']->id, $setGroupMembersArray );
    	
        Tinebase_Group::getInstance()->removeGroupMember($this->objects['initialGroup']->id, $this->objects['account3']->accountId);
		
		$getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($this->objects['initialGroup']->id);
		
		$this->assertEquals ( 2, count($getGroupMembersArray) );
		$this->assertEquals ( array($this->objects['account1']->accountId, $this->objects['account2']->accountId), $getGroupMembersArray);

    }

    /**
     * try to delete a group
     *
     */
    public function testDeleteGroup()
    {
        Tinebase_Group::getInstance()->deleteGroups($this->objects['initialGroup']);

        $this->setExpectedException('Exception');

        $group = Tinebase_Group::getInstance()->getGroupById($this->objects['initialGroup']);
    }

  /**
     * try to convert group id and check if correct exceptions are thrown 
     *
     */
    public function testConvertGroupIdToInt()
    {
        $this->setExpectedException('Exception');
    	
        Tinebase_Model_Group::convertGroupIdToInt (0);
  
    }

  	/**
     * try to convert id of group object and check if correct exceptions are thrown 
     *
     */
    public function testConvertGroupIdToIntWithGroup()
    {
        $this->setExpectedException('Exception');
    	
        Tinebase_Model_Group::convertGroupIdToInt ( $this->objects['noIdGroup'] );
  
    }
    
    public function testGetDefaultGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    public function testGetDefaultAdminGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    public function testSetGroupMemberships()
    {
        $currentGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        Tinebase_Group::getInstance()->setGroupMemberships(Tinebase_Core::getUser(), $currentGroupMemberships);
        $newGroupMemberships = Tinebase_Core::getUser()->getGroupMemberships();
        $this->assertEquals($currentGroupMemberships, $newGroupMemberships);
    }
    
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_GroupTest::main') {
    Tinebase_GroupTest::main();
}
