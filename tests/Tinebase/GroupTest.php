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
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * try to delete a group
     *
     */
    public function testDeleteGroup()
    {
        Tinebase_Group::getInstance()->deleteGroup($this->objects['initialGroup']);

        $this->setExpectedException('Exception');

        $group = Tinebase_Group::getInstance()->getGroupById($this->objects['initialGroup']);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_GroupTest::main') {
    Tinebase_GroupTest::main();
}
