<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_SqlTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Tinebase_User_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * sql user backend
     *
     * @var Tinebase_User_Sql
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
        $this->_backend = new Tinebase_User_Sql();
        
        $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 100,
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 100,
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
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
        $account = $this->_backend->addUser($this->objects['initialAccount']);
        $this->assertEquals($this->objects['initialAccount']['accountId'], $account->accountId);
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($account->accountId);
        $this->assertTrue(!empty($contact->creation_time));
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $accounts = $this->_backend->getUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));
    }
    
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountByLoginName()
    {
        $account = $this->_backend->getUserByLoginName('tine20phpunit', 'Tinebase_Model_FullUser');
        
        $this->assertEquals($this->objects['initialAccount']['accountLoginName'], $account->accountLoginName);
    }

    
    /**
     * try to update an account
     *
     */
    public function testUpdateAccount()
    {
        $account = $this->_backend->updateUser($this->objects['updatedAccount']);
        
        $this->assertEquals($this->objects['updatedAccount']['accountLoginName'], $account->accountLoginName);
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatusEnabled()
    {
        $this->_backend->setStatus($this->objects['initialAccount'], 'enabled');
        
        $account = $this->_backend->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertEquals('enabled', $account->accountStatus);
    }
    
    /**
     * try to disable an account
     *
     */
    public function testSetStatusDisabled()
    {
        $this->_backend->setStatus($this->objects['initialAccount'], 'disabled');

        $account = $this->_backend->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to update the logintimestamp
     *
     */
    public function testSetLoginTime()
    {
        $this->_backend->setLoginTime($this->objects['initialAccount'], '127.0.0.1');
    }
    
    /**
     * try to set the expirydate
     *
     */
    public function testSetExpiryDate()
    {
        $this->_backend->setExpiryDate($this->objects['initialAccount'], Zend_Date::now());
        
        $account = $this->_backend->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertType('Zend_Date', $account->accountExpires);
    }
    
    /**
     * try to unset the expirydate
     *
     */
    public function testUnsetExpiryDate()
    {
        $this->_backend->setExpiryDate($this->objects['initialAccount'], NULL);
        
        $account = $this->_backend->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
        
        $this->assertEquals(NULL, $account->accountExpires);
    }
    
    /**
     * try to delete an accout
     *
     */
    public function testDeleteAccount()
    {
        $this->_backend->deleteUser($this->objects['initialAccount']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $account = $this->_backend->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');        
    }
    
    public function testSanitizeAccountPrimaryGroupId()
    {
        $account = Tinebase_Core::get('currentAccount');
        $originalGroupId = $account->accountPrimaryGroup;
        $defaultGroupId = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        $adminGroupId   = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        $nonExistingId  = '77777666999';
        
        $account->accountPrimaryGroup = $defaultGroupId;  
        $this->assertEquals($defaultGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($defaultGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $adminGroupId; 
        $this->assertEquals($adminGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($adminGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $nonExistingId; 
        $this->assertEquals($defaultGroupId, $account->sanitizeAccountPrimaryGroup());
        $this->assertEquals($defaultGroupId, $account->accountPrimaryGroup);
        
        $account->accountPrimaryGroup = $originalGroupId;
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_User_SqlTest::main') {
    Tinebase_User_SqlTest::main();
}
