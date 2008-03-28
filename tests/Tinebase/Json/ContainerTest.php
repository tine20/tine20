<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Json_ContainerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Json_ContainerTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Json_ContainerTest');
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
     * try to add an account
     *
     */
    public function testAddContainer()
    {
        $json = new Tinebase_Json_Container();
        
        $container = $json->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Container::TYPE_PERSONAL);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }    
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Json_ContainerTest::main') {
    Tinebase_Json_ContainerTest::main();
}
