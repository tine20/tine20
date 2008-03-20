<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Account_RegistrationTest::main');
}

/**
 * Test class for Tinebase_Account
 */
class Tinebase_Account_RegistrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * @var user data for registration
     */
    protected $userData = array();

    /**
     * @var user data for registration with email
     */
    protected $userDataMail = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Account_RegistrationTest');
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
        $this->userData =  array(
        	'accountEmailAddress' => 'p.schuele@metaways.de',
        	'accountFirstName' => 'Philipp',
            'accountLastName' => 'Test',
            'accountLoginName' => 'ptest',
        ); 

        $this->userDataMail =  array(
        	//'accountEmailAddress' => 'p.schuele@metaways.de',
        	'accountEmailAddress' => 'test@example.org',
        	'accountFirstName' => 'Philippo',
            'accountLastName' => 'Testet',
            'accountLoginName' => 'ptestet',
        ); 
        
        $this->objects['registration'] = new Tinebase_Account_Model_Registration ( array(
        	'email' => $this->userData['accountEmailAddress'],
        	'login_hash' => md5($this->userData['accountLoginName']),
            'login_name' => $this->userData['accountLoginName'],
        )); 

        $this->objects['registrationDummy'] = new Tinebase_Account_Model_Registration ( array(
        	'email' => 'test@example.org',
        	'login_hash' => md5('dummy_test'),
            'login_name' => 'dummy_test',
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
     * try to register a user
     *
     * @todo 	check result
     */
    public function testRegisterUser()
    {

    	// don't send mail
    	$result = Tinebase_Account_Registration::getInstance()->registerUser ( $this->userData, false );
    	
    	// check account
    	$account = Tinebase_Account::getInstance()->getFullAccountByLoginName ( $this->userData['accountLoginName'] );
    	
    	$this->assertEquals( $account->accountLastName,  $this->userData['accountLastName'] );
    	
    	// check registration
    	$registration = Tinebase_Account_Registration::getInstance()->getRegistrationByHash($this->objects['registration']->login_hash);

    	$this->assertEquals( $registration->email,  $this->objects['registration']->email );
   		$this->assertEquals( $registration->email_sent,  0 );
    	// check config settings
   		
    }    

    /**
     * try to register a user with email
     *
     * @todo 	activate & check result
     */
    public function testRegisterUserSendMail()
    {
    	// send mail
    	$result = Tinebase_Account_Registration::getInstance()->registerUser ( $this->userDataMail );

    	$this->assertEquals(  $result, true );
    	
    	// check registration
    	$registration = Tinebase_Account_Registration::getInstance()->getRegistrationByHash(md5($this->userDataMail['accountLoginName']));

    	$this->assertEquals(  $registration->email_sent,1 );
    	    	
    }
    
    /**
     * try to check if a username is unique
     *
     */
    public function testCheckUniqueUsername()
    {
     	$result = Tinebase_Account_Registration::getInstance()->checkUniqueUsername($this->objects['registration']->login_name);
     	
     	$this->assertEquals( $result, false );

     	$result = Tinebase_Account_Registration::getInstance()->checkUniqueUsername($this->objects['registrationDummy']->login_name);
     	
     	$this->assertEquals( $result, true );
    } 

    
    /**
     * try to register a user
     *
     * @todo 	activate & check result
     */
    public function testSendLostPasswordMail()
    {
    	//$result = Tinebase_Account_Registration::getInstance()->sendLostPasswordMail ( $this->objects['registration']->login_name );
    }    
    
    /**
     * try to activate an account
     *
     * @todo 	activate & check result
     */
    public function testActivateAccount()
    {
    	// $result = Tinebase_Account_Registration::getInstance()->activateAccount ( $this->objects['registration']->login_hash );
    }    

    
    /**
     * try to delete a registration
     *
     * @todo 	check result
     */
    public function testDeleteRegistrationByLoginName()
    {
    	// delete registration
		Tinebase_Account_Registration::getInstance()->deleteRegistrationByLoginName ( $this->objects['registration']->login_name );

    	// delete account afterwards
		$account = Tinebase_Account::getInstance()->getAccountByLoginName($this->objects['registration']->login_name, 'Tinebase_Account_Model_FullAccount');
		Tinebase_Account::getInstance()->deleteAccount( $account );
		
		// delete email account
   		// delete registration
		Tinebase_Account_Registration::getInstance()->deleteRegistrationByLoginName ( $this->userDataMail['accountLoginName'] );

    	// delete account afterwards
		$account = Tinebase_Account::getInstance()->getAccountByLoginName($this->userDataMail['accountLoginName'], 'Tinebase_Account_Model_FullAccount');
		Tinebase_Account::getInstance()->deleteAccount( $account );
		
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Account_RegistrationTest::main') {
    Tinebase_Account_RegistrationTest::main();
}
