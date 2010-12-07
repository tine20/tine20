<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_Imap_CyrusTest::main');
}

/**
 * Test class for Tinebase_EmailUser_Imap_Cyrus
 */
class Tinebase_User_EmailUser_Imap_CyrusTest extends PHPUnit_Framework_TestCase
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
    
    protected $_config;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_Imap_CyrusTest');
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
        
        if (!array_key_exists('Tinebase_EmailUser_Imap_Cyrus', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Cyrus IMAP plugin not enabled');
        }
        
        $this->_config = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        
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

        #var_dump($testUser->imapUser->toArray());
        #var_dump($this->_config);
        
        #$this->assertEquals($user->imapUser->emailMailQuota, $testUser->imapUser->emailMailQuota, 'emailMailQuota');
        $this->assertEquals(empty($this->_config['domain']) ? $user->accountLoginName : $user->accountLoginName . '@' . $this->_config['domain'], 
            $testUser->imapUser->emailUserId, 'emailUserId');
        $this->assertEquals(empty($this->_config['domain']) ? $user->accountLoginName : $user->accountLoginName . '@' . $this->_config['domain'], 
            $testUser->imapUser->emailUsername, 'emailUsername');
                
        return $user;
    }
        
    /**
     * try to update an email account
     */
    public function testUpdateAccount()
    {
        // add smtp user
        $user = $this->testAddEmailAccount();
        
        // update user
        $user->imapUser->emailMailQuota = 600;
        
        $this->_backend->inspectUpdateUser($this->_objects['user'], $user);
        
        //print_r($user->toArray());
        
        $this->assertEquals(array(
            'emailUserId'      => $this->_objects['user']->getId(),
            'emailUsername'    => $this->_objects['user']->imapUser->emailUsername,
            'emailUID'         => !empty($this->_config['dovecot']['uid']) ? $this->_config['dovecot']['uid'] : '1000',
            'emailGID'         => !empty($this->_config['dovecot']['gid']) ? $this->_config['dovecot']['gid'] : '1000',
            'emailLastLogin'   => null,
            'emailMailQuota'   => 600,
            'emailMailSize'    => 0,
            'emailSieveQuota'  => 0,
            'emailSieveSize'   => 0,
        ), $this->_objects['user']->imapUser->toArray());
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
