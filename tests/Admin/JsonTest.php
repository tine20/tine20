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
    define('PHPUnit_MAIN_METHOD', 'Admin_JsonTest::main');
}

/**
 * Test class for Tinebase_Admin
 */
class Admin_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * container to use for the tests
     *
     * @var Tinebase_Model_Container
     */
    protected $container;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin Json Tests');
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
    	$this->objects['initialGroup'] = array (   'name' => 'test group',
    	                                           'description' => 'some description' );
    	
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
        $json = new Admin_Json();
        
        $groups = $json->getGroups(NULL, 'id', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $groups['totalcount']);
    }    

    /**
     * try to save group data
     *
     */
    public function testSaveGroup()
    {
        $json = new Admin_Json();
        
        $encodedData = Zend_Json::encode( $this->objects['initialGroup'] );
        
        $json->saveGroup( $encodedData );
        
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);
        
        $this->assertEquals($this->objects['initialGroup']['name'], $group->name);
    }    

    /**
     * try to delete group
     *
     */
    public function testDeleteGroup()
    {
    	$json = new Admin_Json();
    	
    	// get group by name
    	$group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);
    	
    	// delete group with json.php function
    	$groupId = Zend_Json::encode( array($group->getId()) );
    	$result = $json->deleteGroups( $groupId );
    	
    	$this->assertTrue( $result['success'] );
    	
    	// try to get deleted group
    	$this->setExpectedException('Tinebase_Record_Exception_NotDefined');
    	
        // get group by name
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']['name']);    	
    }    
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Admin_JsonTest::main') {
    Admin_JsonTest::main();
}
