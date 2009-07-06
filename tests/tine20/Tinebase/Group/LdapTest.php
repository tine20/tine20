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
class Tinebase_Group_LdapTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql group backend
     *
     * @var Tinebase_Group_Sql
     */
    protected $_backendSQL = NULL;
    
    /**
     * ldap group backend
     *
     * @var Tinebase_Group_LDAP
     */
    protected $_backendLDAP = NULL;
    
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
        $this->_backendLDAP = Tinebase_Group::factory(Tinebase_Group::LDAP);
        $this->_backendSQL  = Tinebase_Group::factory(Tinebase_Group::SQL);
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
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
        $group = $this->_backendLDAP->addGroup($this->objects['initialGroup']);
        
        $this->assertNotNull($group->id);
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get all groups containing phpunit in their name
     *
     */
    public function testGetGroups()
    {
        $groups = $this->_backendLDAP->getGroups('phpunit');
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = $this->_backendLDAP->getGroupByName('tine20phpunit');
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $group = $this->_backendLDAP->getGroupByName($this->objects['initialGroup']->name);
        
        $group = $this->_backendLDAP->getGroupById($group->getId());
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }
        
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $group = $this->_backendLDAP->getGroupByName($this->objects['initialGroup']->name);

        $this->objects['updatedGroup']->id = $group->getId();
        $group = $this->_backendLDAP->updateGroup($this->objects['updatedGroup']);
        
        $this->assertEquals($this->objects['updatedGroup']->name, $group->name);
        $this->assertEquals($this->objects['updatedGroup']->description, $group->description);
    }
    
    /**
     * try to delete a group
     *
     */
    public function testDeleteGroups()
    {
        $group = $this->_backendLDAP->getGroupByName($this->objects['updatedGroup']->name);
        $this->_backendLDAP->deleteGroups($group->getId());

        $this->setExpectedException('Exception');

        $group = $this->_backendLDAP->getGroupById($group->getId());
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Group_SqlTest::main') {
    Tinebase_Group_SqlTest::main();
}
