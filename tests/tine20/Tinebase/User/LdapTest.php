<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Group_SqlTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_User_LdapTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql group backend
     *
     * @var Tinebase_User_Sql
     */
    protected $_backendSQL = NULL;
    
    /**
     * ldap group backend
     *
     * @var Tinebase_User_LDAP
     */
    protected $_backendLDAP = NULL;
        
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
        $this->_backendLDAP = Tinebase_User::factory(Tinebase_User::LDAP);
        $this->_backendSQL  = Tinebase_User::factory(Tinebase_User::SQL);
        
        $groupBackend = Tinebase_Group::factory(Tinebase_Group::LDAP);
        
        $groups = $groupBackend->getGroups();
        
        $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => $groups[0]->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => $groups[0]->id,
            'accountLastName'       => 'Tine 2.0 Updated',
            'accountFirstName'      => 'PHPUnit Updated',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));         
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
     * try to add an account
     *
     */
    public function testAddAccount()
    {
        $account = $this->_backendLDAP->addUser($this->objects['initialAccount']);
        $this->assertEquals($this->objects['initialAccount']['accountLoginName'], $account->accountLoginName);
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($account->accountId);
        $this->assertTrue(!empty($contact->creation_time));
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $accounts = $this->_backendLDAP->getUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));
    }
    
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountByLoginName()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit', 'Tinebase_Model_FullUser');
        
        $this->assertEquals($this->objects['initialAccount']['accountLoginName'], $account->accountLoginName);
    }
    
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountById()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit', 'Tinebase_Model_FullUser');
        
        $account = $this->_backendLDAP->getUserById($account->getId(), 'Tinebase_Model_FullUser');
        
        $this->assertEquals($this->objects['initialAccount']['accountLoginName'], $account->accountLoginName);
    }
    
    /**
     * try to update an account
     *
     */
    public function testUpdateAccount()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit', 'Tinebase_Model_FullUser');
        
        $this->objects['updatedAccount']->accountId = $account->getId();
        $account = $this->_backendLDAP->updateUser($this->objects['updatedAccount']);
        
        $this->assertEquals($this->objects['updatedAccount']['accountLoginName'], $account->accountLoginName);
        #$this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
/*    public function testSetStatusEnabled()
    {
        $this->_backendLDAP->setStatus($this->objects['initialAccount'], 'enabled');
        
        $account = $this->_backendLDAP->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertEquals('enabled', $account->accountStatus);
    } */
    
    /**
     * try to disable an account
     *
     */
/*    public function testSetStatusDisabled()
    {
        $this->_backendLDAP->setStatus($this->objects['initialAccount'], 'disabled');

        $account = $this->_backendLDAP->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertEquals('disabled', $account->accountStatus);
    } */
    
    /**
     * try to update the logintimestamp
     *
     */
    public function testSetLoginTime()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit-updated', 'Tinebase_Model_FullUser');
        
        $this->_backendLDAP->setLoginTime($account, '127.0.0.1');
    }
    
    /**
     * try to set the expirydate
     *
     */
    public function testSetExpiryDate()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit-updated', 'Tinebase_Model_FullUser');
        
        $this->_backendLDAP->setExpiryDate($account, Zend_Date::now());
        
        $account = $this->_backendLDAP->getUserById($account, 'Tinebase_Model_FullUser');
        
        $this->assertType('Zend_Date', $account->accountExpires);
    }
    
    /**
     * try to unset the expirydate
     *
     */
    public function testUnsetExpiryDate()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit-updated', 'Tinebase_Model_FullUser');
        
        $this->_backendLDAP->setExpiryDate($account, NULL);
        
        $account = $this->_backendLDAP->getUserById($account, 'Tinebase_Model_FullUser');
        
        $this->assertEquals(NULL, $account->accountExpires);
    }
    
    /**
     * try to delete an accout
     *
     */
    public function testDeleteAccount()
    {
        $account = $this->_backendLDAP->getUserByLoginName('tine20phpunit-updated', 'Tinebase_Model_FullUser');
        
        $this->_backendLDAP->deleteUser($account);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $account = $this->_backendLDAP->getUserById($account, 'Tinebase_Model_FullUser');        
    }
    }		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Group_SqlTest::main') {
    Tinebase_Group_SqlTest::main();
}
