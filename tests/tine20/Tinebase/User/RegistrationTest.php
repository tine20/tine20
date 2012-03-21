<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_RegistrationTest::main');
}

/**
 * Test class for Tinebase_User
 */
class Tinebase_User_RegistrationTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_RegistrationTest');
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
            'accountEmailAddress' => 'p.schuele@metaways.de',
            //'accountEmailAddress' => 'test@example.org',
            'accountFirstName' => 'Philippo',
            'accountLastName' => 'Testet',
            'accountLoginName' => 'ptestet',
        );
        
        $this->objects['registration'] = new Tinebase_Model_Registration ( array(
            'email' => $this->userData['accountEmailAddress'],
            'login_hash' => md5($this->userData['accountLoginName']),
            'login_name' => $this->userData['accountLoginName'],
        ));

        $this->objects['registrationDummy'] = new Tinebase_Model_Registration ( array(
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
     * @todo reactivate 'expires'-test
     */
    public function testRegisterUser()
    {

        // don't send mail
        $result = Tinebase_User_Registration::getInstance()->registerUser( $this->userData, false );
        
        // check account
        $account = Tinebase_User::getInstance()->getFullUserByLoginName( $this->userData['accountLoginName'] );
        
        $this->assertEquals( $account->accountLastName,  $this->userData['accountLastName'] );
        
        // check if "expires" in config.ini set to 0 before this check 
        /*
        try {
            // get config
            $config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'registration');
        } catch (Zend_Config_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' no config for registration found! '. $e->getMessage());
        }

        if ( isset($config->expires) && $config->expires > 0) {
           $this->assertNotEquals( NULL, $account->accountExpires, "account expires" );
        } else {
           $this->assertEquals( NULL, $account->accountExpires, "account won't expire" );
        }
        */
        
        // check registration
        $registration = Tinebase_User_Registration::getInstance()->getRegistrationByHash($this->objects['registration']->login_hash);

        $this->assertEquals( $registration->email,  $this->objects['registration']->email, "email address is not the same" );
           $this->assertEquals( 0, $registration->email_sent, "email sent" );
        // check config settings
           
    }    

    /**
     * try to register a user with email
     *
     */
    public function testRegisterUserSendMail()
    {
        // disabled for the moment
        /*
        // send mail
        $result = Tinebase_User_Registration::getInstance()->registerUser ( $this->userDataMail );

        $this->assertEquals(  $result, true );
        
        // check registration
        $registration = Tinebase_User_Registration::getInstance()->getRegistrationByHash(md5($this->userDataMail['accountLoginName']));

        $this->assertEquals(  $registration->email_sent,1 );
        */                
    }

   /**
     * try to update a registration
     *
     */
    public function testUpdateRegistration()
    {
        
        // check registration update
        $registration = Tinebase_User_Registration::getInstance()->getRegistrationByHash(md5($this->userData['accountLoginName']));
        
        $updatedRegistration = Tinebase_User_Registration::getInstance()->updateRegistration($registration);
        
        $this->assertEquals(  $registration->date,$updatedRegistration->date );
        $this->assertEquals(  $registration->status,$updatedRegistration->status );
        
    }
    
    /**
     * try to check if a username is unique
     *
     */
    public function testCheckUniqueUsername()
    {
         $result = Tinebase_User_Registration::getInstance()->checkUniqueUsername($this->objects['registration']->login_name);
         
         $this->assertEquals( $result, false );

         $result = Tinebase_User_Registration::getInstance()->checkUniqueUsername($this->objects['registrationDummy']->login_name);
         
         $this->assertEquals( $result, true );
    } 

    
    /**
     * try to register a user
     *
     * @todo     implement & activate & check result
     */
    public function testSendLostPasswordMail()
    {
        //$result = Tinebase_User_Registration::getInstance()->sendLostPasswordMail ( $this->objects['registration']->login_name );
        //$this->markTestIncomplete('This test has not been implemented yet.');
    }    
    
    /**
     * try to activate an account
     *
     */
    public function testActivateAccount()
    {
        $result = Tinebase_User_Registration::getInstance()->activateUser( $this->objects['registration']->login_hash );

        // check account
        $account = Tinebase_User::getInstance()->getFullUserByLoginName( $this->userData['accountLoginName'] );
        
        $this->assertEquals( $account->accountExpires, NULL );
    }    

    
    /**
     * try to delete a registration
     *
     */
    public function testDeleteRegistrationByLoginName()
    {
        // delete registration
        Tinebase_User_Registration::getInstance()->deleteRegistrationByLoginName ( $this->objects['registration']->login_name );

        // delete account afterwards
        $account = Tinebase_User::getInstance()->getUserByLoginName($this->objects['registration']->login_name, 'Tinebase_Model_FullUser');
        Tinebase_User::getInstance()->deleteUser( $account );
        
        // delete email account
           // delete registration
           /*
        Tinebase_User_Registration::getInstance()->deleteRegistrationByLoginName ( $this->userDataMail['accountLoginName'] );

        // delete account afterwards
        $account = Tinebase_User::getInstance()->getUserByLoginName($this->userDataMail['accountLoginName'], 'Tinebase_Model_FullUser');
        Tinebase_User::getInstance()->deleteUser( $account );
        */        
    }
    
    /**
     * try to fetch a non-existant registration
     *
     */
    public function testGetNonExistantRegistration()
    {
        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');
        
        $registration = Tinebase_User_Registration::getInstance()->getRegistrationByHash(md5($this->userData['accountLoginName']));
    }
    
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_User_RegistrationTest::main') {
    Tinebase_User_RegistrationTest::main();
}
