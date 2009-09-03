<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        finish
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Group_SqlTest::main');
}

/**
 * Test class for Tinebase_PostfixTest
 */
class Tinebase_User_PostfixTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_PostfixTest');
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
        $this->_backend = Tinebase_EmailUser::getInstance(Tinebase_Model_Config::SMTP);

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
     * @todo implement
     */
    public function testAddEmailAccount()
    {
        //print_r($this->_objects['addedUser']->toArray());
        
        $this->assertEquals(array(
            'emailAddress'      => Tinebase_Core::getUser()->accountEmailAddress,
            'emailUserId'       => Tinebase_Core::getUser()->accountLoginName,
            'emailForwardOnly'  => 0,
            'emailAliases'      => array('bla@tine20.org', 'blubb@tine20.org'),
            'emailForwards'     => array('test@tine20.org', 'unittest@tine20.org'),
        ), $this->_objects['addedUser']->toArray());
    }
    
    /**
     * try to update an email account
     * @todo implement
     */
    public function testUpdateAccount()
    {
        // update user
        /*
        $this->_objects['addedUser']->emailQuota = 2000000;
        
        $updatedUser = $this->_backend->updateUser(Tinebase_Core::getUser(), $this->_objects['addedUser']);
        
        $this->assertEquals(2000000, $updatedUser->emailQuota);
        */
    }
    
    /**
     * try to update an email account
     * @todo implement
     */
    public function testSetPassword()
    {
        // set pw
        /*
        $this->_objects['addedUser']->emailPassword = 'password';
        
        $updatedUser = $this->_backend->updateUser(Tinebase_Core::getUser(), $this->_objects['addedUser']);
        
        $this->assertEquals(md5('password'), $updatedUser->emailPassword);
        */
    }
    
    /**
     * add new email user
     * 
     * @return Tinebase_Model_EmailUser
     */
    protected function _addUser()
    {
        $emailUser = new Tinebase_Model_EmailUser(array(
            'emailForwards'     => array('unittest@tine20.org', 'test@tine20.org'),
            'emailAliases'      => array('bla@tine20.org', 'blubb@tine20.org'),
        ));
        $addedUser = $this->_backend->addUser(Tinebase_Core::getUser(), $emailUser);
        
        return $addedUser;
    }
}	
