<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement more tests!
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_ApplicationTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ApplicationTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_ApplicationTest');
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
     * try to get all application rights
     */
    public function testGetAllRights()
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('Admin');
        $rights = Tinebase_Application::getInstance()->getAllRights($application->getId());
        
        //print_r($rights);
        
        $this->assertGreaterThan(0, count($rights));

        $application = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $rights = Tinebase_Application::getInstance()->getAllRights($application->getId());
        
        //print_r($rights);
        
        $this->assertGreaterThan(0, count($rights));
    }
}		
