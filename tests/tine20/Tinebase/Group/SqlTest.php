<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
class Tinebase_Group_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql user backend
     *
     * @var Tinebase_Group_Sql
     */
    protected $_backend = NULL;
    
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
        $this->_backend = new Tinebase_Group_Sql();
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
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
        $group = $this->_backend->addGroup($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $groups = $this->_backend->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = $this->_backend->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $group = $this->_backend->getGroupById($this->objects['initialGroup']->id);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
        
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $group = $this->_backend->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to delete a group
     *
     */
    public function testDeleteGroups()
    {
        $this->_backend->deleteGroups($this->objects['initialGroup']);

        $this->setExpectedException('Exception');

        $group = $this->_backend->getGroupById($this->objects['initialGroup']);
    }
    
    
    public function testSetGroupMembershipsWithRecordset()
    {
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit1',
            'description'   => 'group1'
        )); 
        
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit2',
            'description'   => 'group2'
        ));
        
        $groupdId1 = $this->_backend->addGroup($groups[0]);
        $groupdId2 = $this->_backend->addGroup($groups[1]);
        
        $accountId = Tinebase_Core::getUser()->getId();
        $_groupIds = new Tinebase_Record_RecordSet('Tinebase_Model_Group', $groups);
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, $_groupIds);
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupdId1);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupdId2);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $this->_backend->deleteGroups(array($groupdId1, $groupdId2));
        
    }
    
    public function testSetGroupMembershipsWithArray()
    {
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit1',
            'description'   => 'group1'
        )); 
        
        $groups[] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit2',
            'description'   => 'group2'
        ));
        
        $groupId1 = $this->_backend->addGroup($groups[0]);
        $groupId2 = $this->_backend->addGroup($groups[1]);
        
        
        $accountId = Tinebase_Core::getUser()->getId();
        $this->_backend->setGroupMembershipsInSqlBackend($accountId, array($groupId1->id, $groupId2->id, $groupId1->id));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupId1);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        $getGroupMembersArray = $this->_backend->getGroupMembers($groupId2);
        $this->assertTrue(in_array($accountId, $getGroupMembersArray));
        
        
        $this->_backend->deleteGroups(array($groupId1, $groupId2));
        
        
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Group_SqlTest::main') {
    Tinebase_Group_SqlTest::main();
}
