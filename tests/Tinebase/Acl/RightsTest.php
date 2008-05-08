<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Acl
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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Acl_RightsTest::main');
}

/**
 * Test class for Tinebase_Acl_Roles
 */
class Tinebase_Acl_RightsTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Acl_RightsTest');
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
        $this->objects['adminAccount'] = Tinebase_Account::getInstance()->getAccountByLoginName('tine20admin');
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
     * try to check if admin user has admin right for admin application
     *
     */
    public function testHasRight()
    {
        $result = Tinebase_Acl_Roles::getInstance()->hasRight(
            Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(), 
            $this->objects['adminAccount']->getId(), 
            Tinebase_Acl_Rights::ADMIN
        );
        
        $this->assertTrue($result);
                
    }    
    
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Acl_RightsTest::main') {
    Tinebase_Acl_RightsTest::main();
}
