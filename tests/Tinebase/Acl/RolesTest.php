<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Acl
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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Acl_RolesTest::main');
}

/**
 * Test class for Tinebase_Acl_Roles
 */
class Tinebase_Acl_RolesTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_Acl_RolesTest');
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
        $this->objects['application'] = Tinebase_Application::getInstance()->getApplicationByName('Crm');
        $this->objects['user'] = new Tinebase_Account_Model_FullAccount(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 

        // add account for group / role member tests
        try {
            Tinebase_Account::getInstance()->getAccountById($this->objects['user']->accountId) ;
        } catch ( Exception $e ) {
            Tinebase_Account::getInstance()->addAccount (  $this->objects['user'] );
        }
        
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
        // remove account
        Tinebase_Account::getInstance()->deleteAccount (  $this->objects['user']->accountId );             
    }

    /**
     * try to check if user with a role has right
     *
     * @todo    create role and add rights for application first
     */
    public function testHasRight()
    {
        $result = Tinebase_Acl_Roles::getInstance()->hasRight(
            $this->objects['application']->getId(), 
            $this->objects['user']->getId(), 
            Tinebase_Acl_Rights::ADMIN
        );
    }    
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_Acl_RolesTest::main') {
    Tinebase_Acl_RolesTest::main();
}
