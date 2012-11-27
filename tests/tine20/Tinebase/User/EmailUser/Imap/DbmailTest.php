<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_Imap_DbmailTest::main');
}

/**
 * Test class for Tinebase_EmailUser_Imap_Dbmail
 */
class Tinebase_User_EmailUser_Imap_DbmailTest extends PHPUnit_Framework_TestCase
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
    
    protected $_config;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_Imap_DbmailTest');
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
        
        if (!array_key_exists('Tinebase_EmailUser_Imap_Dbmail', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Dbmail MySQL plugin not enabled');
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
        $user->imapUser = new Tinebase_Model_EmailUser(array(
            'emailMailQuota' => 1000
        ));
        
        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;

        #var_dump($testUser->toArray());
        #var_dump($testUser->imapUser->toArray());
        #var_dump(array($user->getId(), sprintf("%u", crc32($user->getId()))));
        #var_dump($this->_config);
        
        $this->assertEquals($user->imapUser->emailMailQuota, $testUser->imapUser->emailMailQuota, 'emailMailQuota');
        $this->assertTrue(in_array($testUser->imapUser->emailUserId, array($user->getId(), sprintf("%u", crc32($user->getId())))), 'emailUserId');
        $this->assertEquals(empty($this->_config['domain']) ? $user->accountLoginName : $user->accountLoginName . '@' . $this->_config['domain'], 
            $testUser->imapUser->emailUsername, 'emailUsername');
                
        return $user;
    }
        
    /**
     * try to update an user
     *
     */
    public function testUpdateUser()
    {
        $user = $this->testAddUser();
        $user->imapUser = new Tinebase_Model_EmailUser(array(
            'emailMailQuota' => 2000
        ));
                
        $testUser = $this->_backend->updateUser($user);
        
        #var_dump($testUser->toArray());
        
        $this->assertEquals($user->imapUser->emailMailQuota, $testUser->imapUser->emailMailQuota, 'emailMailQuota');
        $this->assertTrue(in_array($testUser->imapUser->emailUserId, array($user->getId(), sprintf("%u", crc32($user->getId())))), 'emailUserId');
        $this->assertEquals(empty($this->_config['domain']) ? $user->accountLoginName : $user->accountLoginName . '@' . $this->_config['domain'], 
            $testUser->imapUser->emailUsername, 'emailUsername');
    }        
}    
