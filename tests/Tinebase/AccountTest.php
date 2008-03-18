<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_AccountTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_AccountTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_AccountTest');
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
        $this->objects['initialGroup'] = new Tinebase_Group_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Group_Model_Group(array(
            'id'            => 10,
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated group'
        )); 
        
        return;
        
		$this->expectFailure['TestRecord']['testSetId'][] = array('2','3');
		$this->expectFailure['TestRecord']['testSetId'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecord']['testSetId'][] = array('2','2');
		
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','3');
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','2');
		
		$this->expectSuccess['TestRecord']['testSetFromArray'][] = array(array('test_1'=>'2', 'test_2'=>NULL), 'test_1');
		$this->expectFailure['TestRecord']['testSetFromArrayException'][] = array('Tinebase_Record_Exception_Validation', array('test_2' => 'string'), );
		$this->expectFailure['TestRecord']['testSetTimezoneException'][] = array('Exception', 'UTC', );
		
    	$dummy = array(
					'test_id'=>2, 
					'test_2'=>'',
					'date_single' => $date->getIso(), 
					'date_multiple'=>'');
  	  	$this->expectSuccess['TestRecord']['testToArray'][] = array($dummy);
  	  	
  	  	
  	  	$this->expectSuccess['TestRecord']['__set'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['__get'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['test__isset'][] = array('test_id');
  	  	
  	  	$this->expectFailure['TestRecord']['test__isset'][] = array('string');
  	  	
  	  	
  	  	$this->expectFailure['TestRecord']['test__setException'][] = array( 'UnexpectedValueException', 'test_100',);
		$this->expectFailure['TestRecord']['test__getException'][] = array( 'UnexpectedValueException', 'test_100',);
		
  	  	
  	  	$this->expectFailure['TestRecord']['testOffsetUnset'][] = array( 'Tinebase_Record_Exception_NotAllowed', 'test_2',);
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
        $group = Tinebase_Group_Sql::getInstance()->addGroup($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $groups = Tinebase_Group_Sql::getInstance()->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = Tinebase_Group_Sql::getInstance()->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $group = Tinebase_Group_Sql::getInstance()->getGroupById($this->objects['initialGroup']->id);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
        
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $group = Tinebase_Group_Sql::getInstance()->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to delete a group
     *
     */
    public function testDeleteGroup()
    {
        Tinebase_Group_Sql::getInstance()->deleteGroup($this->objects['initialGroup']);

        $this->setExpectedException('Exception');

        $account = Tinebase_Group_Sql::getInstance()->getGroupById($this->objects['initialGroup']);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_AccountTest::main') {
    Tinebase_AccountTest::main();
}
