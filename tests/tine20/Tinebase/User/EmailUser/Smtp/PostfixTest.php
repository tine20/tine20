<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_PostfixTest
 */
class Tinebase_User_EmailUser_Smtp_PostfixTest extends PHPUnit_Framework_TestCase
{
    /**
     * email user backend
     *
     * @var Tinebase_User_Plugin_Abstract
     */
    protected $_backend = NULL;
        
    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * mailserver domain
     * 
     * @var string
     */
    protected $_mailDomain = 'tine20.org';
    
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
        $this->_backend = Tinebase_User::getInstance();
        
        if (!array_key_exists('Tinebase_EmailUser_Smtp_Postfix', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Postfix SQL plugin not enabled');
        }
        
        $this->objects['users'] = array();
        
        $config = TestServer::getInstance()->getConfig();
        if ($config->maildomain) {
            $this->_mailDomain = $config->maildomain;
        }
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        foreach ($this->objects['users'] as $user) {
            $this->_backend->deleteUser($user);
        }
    }
    
    /**
     * try to add an user
     * 
     * @return Tinebase_Model_FullUser
     */
    public function testAddUser()
    {
        $user = Tinebase_User_LdapTest::getTestRecord();
		$user->smtpUser = new Tinebase_Model_EmailUser(array(
		    'emailAddress'     => $user->accountEmailAddress,
		    'emailForwardOnly' => true,
            'emailForwards'    => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'emailAliases'     => array('bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain)
		));
		
        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;

        $this->assertEquals(array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain), $testUser->smtpUser->emailForwards);
        $this->assertEquals(array('bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain),     $testUser->smtpUser->emailAliases);
        $this->assertEquals(true,                                            $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals($user->accountEmailAddress,                      $testUser->smtpUser->emailAddress);
        
        return $testUser;
    }
    
    /**
     * try to update an email account
     */
    public function testUpdateUser()
    {
        // add smtp user
        $user = $this->testAddUser();
        
        // update user
        $user->smtpUser->emailForwardOnly = 1;
        $user->smtpUser->emailAliases = array('bla@' . $this->_mailDomain);
        $user->smtpUser->emailForwards = array();
        $user->accountEmailAddress = 'j.smith@' . $this->_mailDomain;
        
        $testUser = $this->_backend->updateUser($user);
        
        $this->assertEquals(array(),                 $testUser->smtpUser->emailForwards, 'forwards mismatch');
        $this->assertEquals(array('bla@' . $this->_mailDomain), $testUser->smtpUser->emailAliases,  'aliases mismatch');
        $this->assertEquals(false,                   $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals('j.smith@' . $this->_mailDomain,    $testUser->smtpUser->emailAddress);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatus()
    {
        $user = $this->testAddUser();

        
        $this->_backend->setStatus($user, Tinebase_User::STATUS_DISABLED);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $testUser->accountStatus);

        
        $this->_backend->setStatus($user, Tinebase_User::STATUS_ENABLED);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
    }
    
    /**
     * try to update an email account
     * 
     * @todo add assertion again
     */
    public function testSetPassword()
    {
        // add smtp user
        $user = $this->testAddUser();
        
        $this->_backend->setPassword($user, Tinebase_Record_Abstract::generateUID());
        
        //$this->assertEquals(md5('password'), $updatedUser->emailPassword);
    }
}	
