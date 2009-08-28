<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: EmailTest.php 9112 2009-07-06 06:12:09Z l.kneschke@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Group_SqlTest::main');
}

/**
 * Test class for Tinebase_EmailUser
 */
class Tinebase_User_EmailTest extends PHPUnit_Framework_TestCase
{
    /**
     * email user backend
     *
     * @var Tinebase_EmailUser_Abstract
     */
    protected $_backend = NULL;
        
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailTest');
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
        $this->_backend = Tinebase_EmailUser::getInstance();
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
     * try to add an email account
     *
     */
    public function testAddEmailAccount()
    {
        $user = Tinebase_Core::getUser();
        $emailUser = new Tinebase_Model_EmailUser(array(
            'emailQuota'    => 1000000
        ));
        $addedUser = $this->_backend->addUser($user, $emailUser);
        
        $this->assertEquals(array(
            'emailUID' => abs(crc32($user->getId())),
            'emailUserId' => $user->accountLoginName,
            'emailPassword' => '',
            'emailQuota' => 1000000,
            'emailLastLogin' => '1979-11-03 22:05:58'
        ), $addedUser->toArray());
        
        // delete email account
        $this->_backend->deleteUser($user->getId());
    }
    
    /**
     * try to update an email account
     * 
     * @todo implement
     */
    public function testUpdateAccount()
    {
    }
    
    /**
     * try to update an email account
     * 
     * @todo implement
     */
    public function testSetPassword()
    {
    }
    
}	
