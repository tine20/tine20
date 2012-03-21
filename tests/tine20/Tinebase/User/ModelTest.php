<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        write test class for each of the account models?
 * @todo        implement other tests for the model functions
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_ModelTest::main');
}

/**
 * Test class for Tinebase_User_Model_*
 */
class Tinebase_User_ModelTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_ModelTest');
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
        $this->objects['account'] = Zend_Registry::get('currentAccount');
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
     * try to get account applications 
     *
     */
    public function testGetApplications()
    {
        $applications = $this->objects['account']->getApplications();
        
        $this->assertGreaterThan(0, count($applications));
    }

    /**
     * try to get account applications 
     *
     */
    public function testGetRights()
    {
        $rights = $this->objects['account']->getRights('Admin');

        $this->assertGreaterThan(0, count($rights));
    }
    
    /**
     * try to check right
     *
     */
    public function testHasRight()
    {
        $hasRight = $this->objects['account']->hasRight('Admin', Tinebase_Acl_Rights::ADMIN );

        $this->assertTrue($hasRight);
    }

    /**
     * try to get account group memberships 
     *
     */
    public function testGetGroupMemberships()
    {
        $groups = $this->objects['account']->getGroupMemberships();

        $this->assertGreaterThan(0, count($groups));
    }
}        
