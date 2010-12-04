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
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_Smtp_PostfixTest::main');
}

/**
 * Test class for Tinebase_PostfixTest
 */
class Tinebase_User_EmailUser_Smtp_PostfixTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_Smtp_PostfixTest');
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
        $smtpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        if (!isset($smtpConfig['backend']) || !(ucfirst($smtpConfig['backend']) == Tinebase_EmailUser::POSTFIX) || $smtpConfig['active'] != true) {
            $this->markTestSkipped('Postfix MySQL backend not configured or not enabled');
        }
        
        $this->_backend = Tinebase_EmailUser::getInstance(Tinebase_Model_Config::SMTP);
        
        $personas = Zend_Registry::get('personas');
        $this->_objects['user'] = clone $personas['jsmith'];

        $this->_objects['addedUsers'] = array();
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
        foreach ($this->_objects['addedUsers'] as $user) {
            $this->_backend->inspectDeleteUser($user->getId());
        }
    }
    
    /**
     * try to add an email account
     */
    public function testAddEmailAccount()
    {
        $emailUser = clone $this->_objects['user'];
        $emailUser->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailForwards' => array('unittest@tine20.org', 'test@tine20.org'),
            'emailAliases'  => array('bla@tine20.org', 'blubb@tine20.org')
        ));
        
        $this->_backend->inspectAddUser($this->_objects['user'], $emailUser);
        $this->_objects['addedUsers']['emailUser'] = $this->_objects['user'];
        
        #var_dump($this->_objects['user']->smtpUser->toArray());
        
        $this->assertEquals(array(
            'emailUserId'       => $this->_objects['user']->getId(),
        	'emailAddress'      => $this->_objects['user']->accountEmailAddress,
            'emailUsername'     => $this->_objects['user']->smtpUser->emailUsername,
            'emailForwardOnly'  => 0,
            'emailAliases'      => array('bla@tine20.org', 'blubb@tine20.org'),
            'emailForwards'     => array('unittest@tine20.org', 'test@tine20.org')
        ), $this->_objects['user']->smtpUser->toArray());
        
        return $this->_objects['addedUsers']['emailUser'];
    }
    
    /**
     * try to update an email account
     */
    public function testUpdateAccount()
    {
        // add smtp user
        $user = $this->testAddEmailAccount();
        
        // update user
        $user->smtpUser->emailForwardOnly = 1;
        $user->smtpUser->emailAliases = array();
        $user->smtpUser->emailForwards = array('test@tine20.org');
        $this->_objects['user']->accountEmailAddress = 'j.smith@tine20.org';
        
        $this->_backend->inspectUpdateUser($this->_objects['user'], $user);
        
        //print_r($updatedUser->toArray());
        
        $this->assertEquals(array(
            'emailUserId'       => $this->_objects['user']->getId(),
        	'emailAddress'      => $this->_objects['user']->accountEmailAddress,
            'emailUsername'     => $this->_objects['user']->smtpUser->emailUsername,
            'emailForwardOnly'  => 1,
            'emailAliases'      => array(),
            'emailForwards'     => array('test@tine20.org')
        ), $this->_objects['user']->smtpUser->toArray());
    }
    
    /**
     * try to update an email account
     */
    public function testSetPassword()
    {
        // add smtp user
        $user = $this->testAddEmailAccount();
        
        $this->_backend->inspectSetPassword($this->_objects['user']->getId(), Tinebase_Record_Abstract::generateUID());
        
        //$this->assertEquals(md5('password'), $updatedUser->emailPassword);
    }
}	
