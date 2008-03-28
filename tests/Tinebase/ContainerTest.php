<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_ContainerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ContainerTest extends PHPUnit_Framework_TestCase
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
		$suite  = new PHPUnit_Framework_TestSuite('Tinebase_ContainerTest');
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
       $this->objects['initialContainer'] = new Tinebase_Model_Container(array(
            'id'                => 123,
            'name'              => 'tine20phpunit',
            'type'              => Tinebase_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            //'account_grants'    => 'Tine 2.0',
        )); 
/*        
        $this->objects['updatedContainer'] = new Tinebase_Container_Model_FullContainer(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 Updated',
            'accountFirstName'      => 'PHPUnit Updated',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        )); 
  */  	
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
    public function testAddContainer()
    {
        $container = Tinebase_Container::getInstance()->new_addContainer($this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
    
    /**
     * try to add an existing container. should throw an exception
     *
     */
    public function testAddContainerTwice()
    {
        $this->setExpectedException('Zend_Db_Statement_Exception');
        
        $container = clone($this->objects['initialContainer']);
        $container->setId(NULL);
        
        $container = Tinebase_Container::getInstance()->new_addContainer($container);
    }
    
    /**
     * try to add an account
     *
     */
    public function testGetContainer()
    {
        $container = Tinebase_Container::getInstance()->new_getContainer($this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
    
    /**
     * try to add an existing container. should throw an exception
     *
     */
    public function testDeleteContainer()
    {
        Tinebase_Container::getInstance()->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('UnderflowException');
        
        $container = Tinebase_Container::getInstance()->new_getContainer($this->objects['initialContainer']);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_ContainerTest::main') {
    Tinebase_ContainerTest::main();
}
