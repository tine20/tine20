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
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_Plugin_SambaTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_User_Plugin_SambaTest extends PHPUnit_Framework_TestCase
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
        
        $this->_backend = Tinebase_User::factory(Tinebase_User::LDAP);
        
        if (!array_key_exists('Tinebase_User_Plugin_Samba', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Samba LDAP plugin not enabled');
        }
        
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
        $user->sambaSAM = new Tinebase_Model_SAMUser(array(
            'homeDrive' => 'H:',
            'homePath'  => '\\\\smbserver\\homes'
        ));
        
        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;
        
        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertTrue(!empty($testUser->sambaSAM->sid), 'no sid set');
        $this->assertEquals('H:', $testUser->sambaSAM->homeDrive);
        $this->assertEquals('\\\\smbserver\\homes', $testUser->sambaSAM->homePath);
        
        return $user;
    }
    
    /**
     * try to update an user
     *
     */
    public function testUpdateUser()
    {
        $user = $this->testAddUser();
        $user->sambaSAM->homeDrive = 'P:';
        
        $testUser = $this->_backend->updateUser($user);
        
        $this->assertEquals($user->accountLoginName, $testUser->accountLoginName);
        $this->assertTrue(!empty($testUser->sambaSAM->sid), 'no sid set');
        $this->assertEquals('P:', $testUser->sambaSAM->homeDrive);
        $this->assertEquals('\\\\smbserver\\homes', $testUser->sambaSAM->homePath);
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
        $this->assertEquals('[UD         ]', $testUser->sambaSAM->acctFlags);
        
        $this->_backend->setStatus($user, Tinebase_User::STATUS_ENABLED);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
        $this->assertEquals('[U          ]', $testUser->sambaSAM->acctFlags);
    }
    
    /**
     * try to set password
     *
     */
    public function testSetPassword()
    {
        $user = $this->testAddUser();

        $this->_backend->setPassword($user, Tinebase_Record_Abstract::generateUID(), true, false);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertNotEquals($user->accountLastPasswordChange, $testUser->accountLastPasswordChange);
        $this->assertNotEquals($user->sambaSAM->pwdLastSet,      $testUser->sambaSAM->pwdLastSet);
    }
        
    /**
     * try to set the expirydate
     *
     */
    public function testSetExpiryDate()
    {
        $user = $this->testAddUser();
        
        
        $this->_backend->setExpiryDate($user, Tinebase_DateTime::now()->subDay(1));
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals('Tinebase_DateTime', get_class($testUser->accountExpires), 'wrong type');
        $this->assertEquals(Tinebase_User::STATUS_EXPIRED,   $testUser->accountStatus);
        $this->assertNotEquals($user->sambaSAM->kickoffTime, $testUser->sambaSAM->kickoffTime);
        

        $this->_backend->setExpiryDate($user, NULL);
        
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(NULL,                          $testUser->accountExpires);
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
        $this->assertEquals(null,                          $testUser->sambaSAM->kickoffTime);
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_User_Plugin_SambaTest::main') {
    Tinebase_Group_SqlTest::main();
}
