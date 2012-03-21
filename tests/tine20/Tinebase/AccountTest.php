<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_UserTest::main');
}

/**
 * Test class for Tinebase_Group
 * @depricated, some fns might be moved to other testclasses
 */
class Tinebase_UserTest extends PHPUnit_Framework_TestCase
{
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_UserTest');
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
       $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));
        
        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 Updated',
            'accountFirstName'      => 'PHPUnit Updated',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));
        
        $this->objects['deleteAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 11,
            'accountLoginName'      => 'tine20phpunit-delete',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 delete',
            'accountFirstName'      => 'PHPUnit delete',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));

        $this->objects['noIdAccount'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit-noid',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 noid',
            'accountFirstName'      => 'PHPUnit noid',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));
 
        return;
        
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
        $account = Tinebase_User::getInstance()->addUser($this->objects['initialAccount']);
        
        $this->assertEquals(10, $account->accountId);
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $accounts = Tinebase_User::getInstance()->getUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));

        // test with sort dir
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus', 'DESC');
        
        $this->assertEquals(1, count($accounts));
    }

    /**
     * try to get all full accounts containing phpunit in there name
     *
     */
    public function testGetFullAccounts()
    {
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));

        // test with sort dir
        $accounts = Tinebase_User::getInstance()->getFullUsers('phpunit', 'accountStatus', 'ASC');
        
        $this->assertEquals(1, count($accounts));
    }
 
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountByLoginName()
    {
        $account = Tinebase_User::getInstance()->getUserByLoginName('tine20phpunit');
        
        // Tinebase_Model_User has no accountLoginName
        $this->assertEquals('10', $account->accountId);
    }
    
    /**
     * try to get the full account with the loginName tine20phpunit
     *
     */
    public function testGetFullAccountByLoginName()
    {
        $account = Tinebase_User::getInstance()->getFullUserByLoginName('tine20phpunit');
        
        $this->assertEquals('tine20phpunit', $account->accountLoginName);
    }

    /**
     * try to get the account with the id 10
     *
     */
    public function testGetAccountById()
    {
        $account = Tinebase_User::getInstance()->getUserById(10);
        
        $this->assertEquals('10', $account->accountId);
    }

    /**
     * try to get the full account with the id 10
     *
     */
    public function testGetFullAccountById()
    {
        $account = Tinebase_User::getInstance()->getFullUserById(10);
        
        $this->assertEquals('10', $account->accountId);
    }
    
    /**
     * try to update an account
     *
     */
    public function testUpdateAccount()
    {
        $account = Tinebase_User::getInstance()->updateUser($this->objects['updatedAccount']);
        
        $this->assertEquals('tine20phpunit-updated', $account->accountLoginName);
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatusEnabled()
    {
        Tinebase_User::getInstance()->setStatus($this->objects['initialAccount'], 'enabled');
        
        $account = Tinebase_User::getInstance()->getFullUserById($this->objects['initialAccount']);
        
        $this->assertEquals('enabled', $account->accountStatus);
    }
    
    /**
     * try to disable an account
     *
     */
    public function testSetStatusDisabled()
    {
        Tinebase_User::getInstance()->setStatus($this->objects['initialAccount'], 'disabled');

        $account = Tinebase_User::getInstance()->getFullUserById($this->objects['initialAccount']);
        
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to update the logintimestamp
     *
     * @todo    check if set correctly
     */
    public function testSetLoginTime()
    {
        Tinebase_User::getInstance()->setLoginTime($this->objects['initialAccount'], '127.0.0.1');
    }
    
    /**
     * try to set the expirydate
     *
     * @todo    check if set correctly
     */
    public function testSetExpiryDate()
    {
        Tinebase_User::getInstance()->setExpiryDate($this->objects['initialAccount'], Tinebase_DateTime::now());
    }

   /**
     * try to set the blocked until date
     *
     * @todo    check if set correctly
     */
    public function testSetBlockedDate()
    {
        $date = Tinebase_DateTime::now();
        $date->add ( '12:00:00' );
        Tinebase_User::getInstance()->setBlockedDate($this->objects['initialAccount'], $date );
    }
 
    /**
     * try to delete an account
     *
     */
    public function testDeleteAccount()
    {
        $this->setExpectedException('Exception');

        Tinebase_User::getInstance()->deleteUser($this->objects['initialAccount']);

        $account = Tinebase_User::getInstance()->getUserById($this->objects['initialAccount'], 'Tinebase_Model_FullUser');
    }

   /**
     * try to delete multiple accounts
     *
     */
    public function testDeleteAccounts()
    {
        $this->setExpectedException('Exception');
        
        $todelete = array ( 10, 11 );

        Tinebase_User::getInstance()->deleteUsers( $todelete );

        $account = Tinebase_User::getInstance()->getUserById($this->objects['deleteAccount'], 'Tinebase_Model_FullUser');
    }

   /**
     * try to convert account id and check if correct exceptions are thrown 
     *
     */
    public function testConvertAccountIdToInt()
    {
        $this->setExpectedException('Exception');
        
        Tinebase_Model_User::convertUserIdToInt(0);
  
    }

      /**
     * try to convert id of account object and check if correct exceptions are thrown 
     *
     */
    public function testConvertAccountIdToIntWithAccount()
    {
        $this->setExpectedException('Exception');
        
        Tinebase_Model_User::convertUserIdToInt( $this->objects['noIdAccount'] );
  
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_UserTest::main') {
    Tinebase_UserTest::main();
}
