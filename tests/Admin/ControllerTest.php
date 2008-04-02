<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add more tests!
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Admin_ControllerTest::main');
}

/**
 * Test class for Tinebase_Admin
 */
class Admin_ControllerTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin Controller Tests');
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
    	$this->objects['initialGroup'] = array (   'id' => 10,
    	                                           'name' => 'test group',
    	                                           'description' => 'some description' );
        /*$this->objects['updateGroup'] = array (   'name' => 'test group',
                                                   'description' => 'some description updated' );
    	*/
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
     * try to get all groups
     *
     */
    public function testGetGroups()
    {        
        $groups = Admin_Controller::getInstance()->getGroups(NULL, 'id', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, sizeof($groups));
    }    

    /**
     * try to get group
     *
     */
    public function testGetGroup()
    {        
    	//@todo check added initial group
        $group = Admin_Controller::getInstance()->getGroup(2);
        
        $this->assertEquals('Users', $group->name);
    }    

    /**
     * try to update group data
     *
     */
    public function testUpdateGroup()
    {
        /*$json = new Admin_Json();

        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);
        
        $data = $this->objects['updateGroup'];
        $data['id'] = $group->getId();
        $encodedData = Zend_Json::encode( $data );
        
        $json->saveGroup( $encodedData );

        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);
        
        $this->assertEquals($this->objects['updateGroup']['description'], $group->description); */
    }    
    
    /**
     * try to delete group
     *
     */
    public function testDeleteGroup()
    {
    	/*$json = new Admin_Json();
    	
    	// get group by name
    	$group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);
    	
    	// delete group with json.php function
    	$groupId = Zend_Json::encode( array($group->getId()) );
    	$result = $json->deleteGroups( $groupId );
    	
    	$this->assertTrue( $result['success'] );
    	
    	// try to get deleted group
    	$this->setExpectedException('Tinebase_Record_Exception_NotDefined');
    	
        // get group by name
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);   */ 	
    }    
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Admin_ControllerTest::main') {
    Admin_ControllerTest::main();
}
