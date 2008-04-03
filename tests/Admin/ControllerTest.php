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
     * try to get Users group
     *
     */
    public function testGetGroup()
    {        
        $group = Admin_Controller::getInstance()->getGroup(2);
        
        $this->assertEquals('Users', $group->name);
    }    

 
}		
	

if (PHPUnit_MAIN_METHOD == 'Admin_ControllerTest::main') {
    Admin_ControllerTest::main();
}
