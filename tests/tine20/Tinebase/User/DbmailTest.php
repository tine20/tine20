<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: DbmailTest.php 9112 2009-07-06 06:12:09Z l.kneschke@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Group_SqlTest::main');
}

/**
 * Test class for Tinebase_EmailUser_Imap_Dbmail
 */
class Tinebase_User_DbmailTest extends PHPUnit_Framework_TestCase
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
    protected $_objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_DbmailTest');
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
        $this->_backend = Tinebase_EmailUser::getInstance(Tinebase_Model_Config::IMAP);

        $this->_objects['addedUser'] = $this->_addUser();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // delete email account
        $this->_backend->deleteUser(Tinebase_Core::getUser()->getId());
    }
    
    /**
     * try to add an email account
     *
     */
    public function testAddEmailAccount()
    {
        $this->assertEquals(array(
            'emailUID' => abs(crc32(Tinebase_Core::getUser()->getId())),
            'emailUserId' => Tinebase_Core::getUser()->accountLoginName,
            'emailPassword' => '',
            'emailMailQuota' => 1000000,
            'emailMailSize' => 0,
            'emailSieveQuota' => 0,
            'emailSieveSize' => 0,
            'emailLastLogin' => '1979-11-03 22:05:58'
        ), $this->_objects['addedUser']->toArray());
    }
    
    /**
     * try to update an email account
     * 
     */
    public function testUpdateAccount()
    {
        // update user
        $this->_objects['addedUser']->emailMailQuota = 2000000;
        
        $updatedUser = $this->_backend->updateUser(Tinebase_Core::getUser(), $this->_objects['addedUser']);
        
        $this->assertEquals(2000000, $updatedUser->emailMailQuota);
    }
    
    /**
     * try to update an email account
     * 
     */
    public function testSetPassword()
    {
        // set pw
        $this->_objects['addedUser']->emailPassword = 'password';
        
        $updatedUser = $this->_backend->updateUser(Tinebase_Core::getUser(), $this->_objects['addedUser']);
        
        $this->assertEquals(md5('password'), $updatedUser->emailPassword);
    }
    
    /**
     * add new email user
     * 
     * @return Tinebase_Model_EmailUser
     */
    protected function _addUser()
    {
        $emailUser = new Tinebase_Model_EmailUser(array(
            'emailMailQuota'    => 1000000
        ));
        $addedUser = $this->_backend->addUser(Tinebase_Core::getUser(), $emailUser);
        
        return $addedUser;
    }
}	
