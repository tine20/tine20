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
        $this->objects['newUserData'] = array(
        	'accountEmailAddress' => 'p.schuele@metaways.de',
        	'accountFirstName' => 'Philipp',
            'accountLastName' => 'Test',
            'accountLoginName' => 'ptest',
        ); 
                
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
     * try to check if a username is unique
     *
     * @todo 	activate & add a user with the same username to db first
     */
    public function testCheckUniqueUsername()
    {
     	//$result = Tinebase_Account_Registration::getInstance()->checkUniqueUsername($this->objects['newUserData']->accountLoginName);
    } 

    /**
     * try to register a user
     *
     * @todo 	activate & check result
     */
    public function testRegisterUser()
    {
    	//$result = Tinebase_Account_Registration::getInstance()->registerUser ( $this->objects['newUserData'] );
    }    

    /**
     * try to register a user
     *
     * @todo 	activate & check result
     */
    public function testSendLostPasswordMail()
    {
    	//$result = Tinebase_Account_Registration::getInstance()->sendLostPasswordMail ( $this->objects['newUserData']->accountLoginName );
    }    
    
    /**
     * try to activate an account
     *
     * @todo 	activate & check result
     */
    public function testActivateAccount()
    {
    	/*$hash = md5($this->objects['newUserData']->accountEmailAddress);
    	$result = Tinebase_Account_Registration::getInstance()->activateAccount ( $hash );*/
    }    
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Account_RegistrationTest::main') {
    Tinebase_Account_RegistrationTest::main();
}
