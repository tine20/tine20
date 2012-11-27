<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_User_EmailUser_Smtp_LdapDbmailSchemaTest extends PHPUnit_Framework_TestCase
{
    /**
     * ldap group backend
     *
     * @var Tinebase_User_LDAP
     */
    protected $_backend = NULL;
        
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    protected $_config;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_SqlTest');
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
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP) {
            $this->markTestSkipped('LDAP backend not enabled');
        }
        
        $this->_backend = Tinebase_User::getInstance();
        
        if (!array_key_exists('Tinebase_EmailUser_Smtp_LdapDbmailSchema', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Dbmail LDAP plugin not enabled');
        }
        
        $this->_config = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        $this->objects['users'] = array();
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
            'emailForwards'    => array('unittest@tine20.org', 'test@tine20.org'),
            'emailAliases'     => array('bla@tine20.org', 'blubb@tine20.org')
        ));
        
        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;

        #var_dump($testUser->toArray());
        #var_dump($this->_config);
        
        $this->assertEquals(array('unittest@tine20.org', 'test@tine20.org'), $testUser->smtpUser->emailForwards);
        $this->assertEquals(array('bla@tine20.org', 'blubb@tine20.org'),     $testUser->smtpUser->emailAliases);
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
        $user->smtpUser->emailAliases = array('bla@tine20.org');
        $user->smtpUser->emailForwards = array();
        $user->accountEmailAddress = 'j.smith@tine20.org';
        
        $testUser = $this->_backend->updateUser($user);
        
        $this->assertEquals(array(),                 $testUser->smtpUser->emailForwards, 'forwards mismatch');
        $this->assertEquals(array('bla@tine20.org'), $testUser->smtpUser->emailAliases,  'aliases mismatch');
        $this->assertEquals(false,                   $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals('j.smith@tine20.org',    $testUser->smtpUser->emailAddress);
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
     */
    public function testSetPassword()
    {
        // add smtp user
        $user = $this->testAddUser();
        
        $this->_backend->setPassword($user, Tinebase_Record_Abstract::generateUID());
        
        //$this->assertEquals(md5('password'), $updatedUser->emailPassword);
    }
}

