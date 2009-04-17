<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_AbstractTest::main');
}

/**
 * Test class for Tinebase_User_Abstract
 */
class Tinebase_User_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test
     *
     * @var Tinebase_User_Abstract
     */
    protected $_uit = NULL;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_AbstractTest');
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
        $this->_uit = Tinebase_User::getInstance();
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
     * test generation of loginnames
     */
    public function testGenerateUserName()
    {
        $user = new Tinebase_Model_FullUser(array(
            'accountFirstName' => 'Leonie',
            'accountLastName'  => 'Weiss',
            'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup
        ), true);
        
        $createdUserIds = array();
        for ($i=0; $i<10; $i++) {
            $user->accountLoginName = $this->_uit->generateUserName($user);
            $createdUserIds[] = $this->_uit->addUser($user)->getId();
            $user->setId(NULL);
        }
        
        $this->_uit->deleteUsers($createdUserIds);
    }
    
    public function testCachePassword()
    {
        Tinebase_User::getInstance()->cachePassword('secret');
        $this->assertEquals('secret', Tinebase_User::getInstance()->getCachedPassword());
    }
}       
