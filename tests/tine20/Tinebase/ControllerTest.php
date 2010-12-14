<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

//if (!defined('PHPUnit_MAIN_METHOD')) {
//    define('PHPUnit_MAIN_METHOD', 'Tinebase_ControllerTest::main');
//}

/**
 * Test class for Tinebase_Controller
 */
class Tinebase_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * controller instance
     * 
     * @var Tinebase_Controller
     */
    protected $_instance = NULL;
    
    /**
     * run
     * 
     * @see http://matthewturland.com/2010/08/19/process-isolation-in-phpunit/
     * @param $result
     */
//    public function run(PHPUnit_Framework_TestResult $result = NULL)
//    {
//        $this->setPreserveGlobalState(false);
//        return parent::run($result);
//    }
        
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
//    public static function main()
//    {
//		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_ControllerTest');
//        PHPUnit_TextUI_TestRunner::run($suite);
//	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = Tinebase_Controller::getInstance();
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
     * test login and logout in separate process
     * 
     * @runInSeparateProcess
     */
    public function testLoginAndLogout()
    {
        $config = Zend_Registry::get('testConfig');
        
        $configData = @include('phpunitconfig.inc.php');
        $config = new Zend_Config($configData);
        
        $result = $this->_instance->login($config->username, $config->password, $config->ip, 'TineUnittest2');
        
        $this->assertTrue($result);
        
        // just call change pw for fun and coverage ;)
        $result = $this->_instance->changePassword($config->password, $config->password);
        
        $result = $this->_instance->logout($config->ip);
        
        $this->assertEquals('', session_id());
    }
}

//if (PHPUnit_MAIN_METHOD == 'Tinebase_ControllerTest::main') {
//    Tinebase_AsyncJobTest::main();
//}
