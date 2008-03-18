<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Account_SqlTest::main');
}

/**
 * Test class for Tinebase_Account
 */
class Tinebase_Account_SqlTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Account_SqlTest');
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
        $this->objects['initialAccount'] = new Tinebase_Account_Model_FullAccount(array(
            'accountId'             => 100,
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => 2,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        $this->objects['updatedAccount'] = new Tinebase_Account_Model_FullAccount(array(
            'accountId'             => 100,
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => 2,
            'accountLastName'       => 'Tine 2.0 Updated',
            'accountFirstName'      => 'PHPUnit Updated',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        )); 
        
        return;
        
		$this->expectFailure['TestRecord']['testSetId'][] = array('2','3');
		$this->expectFailure['TestRecord']['testSetId'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecord']['testSetId'][] = array('2','2');
		
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','3');
		$this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('30000000','3000000000000000000000000000');
		$this->expectSuccess['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','2');
		
		$this->expectSuccess['TestRecord']['testSetFromArray'][] = array(array('test_1'=>'2', 'test_2'=>NULL), 'test_1');
		$this->expectFailure['TestRecord']['testSetFromArrayException'][] = array('Tinebase_Record_Exception_Validation', array('test_2' => 'string'), );
		$this->expectFailure['TestRecord']['testSetTimezoneException'][] = array('Exception', 'UTC', );
		
    	$dummy = array(
					'test_id'=>2, 
					'test_2'=>'',
					'date_single' => $date->getIso(), 
					'date_multiple'=>'');
  	  	$this->expectSuccess['TestRecord']['testToArray'][] = array($dummy);
  	  	
  	  	
  	  	$this->expectSuccess['TestRecord']['__set'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['__get'][] = array('test_3', 4 );
  	  	
  	  	$this->expectSuccess['TestRecord']['test__isset'][] = array('test_id');
  	  	
  	  	$this->expectFailure['TestRecord']['test__isset'][] = array('string');
  	  	
  	  	
  	  	$this->expectFailure['TestRecord']['test__setException'][] = array( 'UnexpectedValueException', 'test_100',);
		$this->expectFailure['TestRecord']['test__getException'][] = array( 'UnexpectedValueException', 'test_100',);
		
  	  	
  	  	$this->expectFailure['TestRecord']['testOffsetUnset'][] = array( 'Tinebase_Record_Exception_NotAllowed', 'test_2',);
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
        $account = Tinebase_Account_Sql::getInstance()->addAccount($this->objects['initialAccount']);
        
        $this->assertEquals($this->objects['initialAccount']['accountId'], $account->accountId);
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $accounts = Tinebase_Account_Sql::getInstance()->getAccounts('phpunit', 'accountStatus');
        
        $this->assertEquals(1, count($accounts));
    }
    
    /**
     * try to get the account with the loginName tine20phpunit
     *
     */
    public function testGetAccountByLoginName()
    {
        $account = Tinebase_Account_Sql::getInstance()->getAccountByLoginName('tine20phpunit', 'Tinebase_Account_Model_FullAccount');
        
        $this->assertEquals($this->objects['initialAccount']['accountLoginName'], $account->accountLoginName);
    }

    
    /**
     * try to update an account
     *
     */
    public function testUpdateAccount()
    {
        $account = Tinebase_Account_Sql::getInstance()->updateAccount($this->objects['updatedAccount']);
        
        $this->assertEquals($this->objects['updatedAccount']['accountLoginName'], $account->accountLoginName);
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to enable an account
     *
     */
    public function testSetStatusEnabled()
    {
        Tinebase_Account_Sql::getInstance()->setStatus($this->objects['initialAccount'], 'enabled');
        
        $account = Tinebase_Account_Sql::getInstance()->getAccountById($this->objects['initialAccount'], 'Tinebase_Account_Model_FullAccount');
        
        $this->assertEquals('enabled', $account->accountStatus);
    }
    
    /**
     * try to disable an account
     *
     */
    public function testSetStatusDisabled()
    {
        Tinebase_Account_Sql::getInstance()->setStatus($this->objects['initialAccount'], 'disabled');

        $account = Tinebase_Account_Sql::getInstance()->getAccountById($this->objects['initialAccount'], 'Tinebase_Account_Model_FullAccount');
        
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to update the logintimestamp
     *
     */
    public function testSetLoginTime()
    {
        Tinebase_Account_Sql::getInstance()->setLoginTime($this->objects['initialAccount'], '127.0.0.1');
    }
    
    /**
     * try to set the expirydate
     *
     */
    public function testSetExpiryDate()
    {
        Tinebase_Account_Sql::getInstance()->setExpiryDate($this->objects['initialAccount'], Zend_Date::now());
    }
    
    /**
     * try to delete an accout
     *
     */
    public function testDeleteAccount()
    {
        $this->setExpectedException('Exception');

        Tinebase_Account_Sql::getInstance()->deleteAccount($this->objects['initialAccount']);

        $account = Tinebase_Account_Sql::getInstance()->getAccountById($this->objects['initialAccount'], 'Tinebase_Account_Model_FullAccount');
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Account_SqlTest::main') {
    Tinebase_Account_SqlTest::main();
}
