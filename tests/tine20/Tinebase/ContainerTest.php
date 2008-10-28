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
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            //'account_grants'    => 'Tine 2.0',
        ));

        $this->objects['grants'] = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'     => Zend_Registry::get('currentAccount')->getId(),
                'account_type'   => 'user',
                //'account_name'   => 'not used',
                'readGrant'      => true,
                'addGrant'       => true,
                'editGrant'      => true,
                'deleteGrant'    => true,
                'adminGrant'     => true
            )            
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
        $container = Tinebase_Container::getInstance()->addContainer($this->objects['initialContainer'], $this->objects['grants']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
        
    /**
     * try to get container by id
     *
     */
    public function testGetContainerById()
    {
        $container = Tinebase_Container::getInstance()->getContainerById($this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
    
    /**
     * try to get container by name
     *
     */
    public function testGetContainerByName()
    {
        $container = Tinebase_Container::getInstance()->getContainerByName(
            'Addressbook',
            $this->objects['initialContainer']->name,
            $this->objects['initialContainer']->type
        );
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
    
    
    /**
     * try to set new container name
     *
     */
    public function testSetContainerName()
    {
        $container = Tinebase_Container::getInstance()->setContainerName($this->objects['initialContainer'], 'renamed container');
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals('renamed container', $container->name);
    }
    
    /**
     * try to add an existing container. should throw an exception
     *
     */
    public function testDeleteContainer()
    {
        Tinebase_Container::getInstance()->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to get all grants of a container
     *
     */
    public function testGetGrantsOfContainer()
    {
        $container = Tinebase_Container::getInstance()->addContainer($this->objects['initialContainer'], $this->objects['grants']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
        $this->assertTrue(Tinebase_Container::getInstance()->hasGrant(Zend_Registry::get('currentAccount'), $this->objects['initialContainer'], Tinebase_Model_Container::GRANT_READ));

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->objects['initialContainer']);
        $this->assertType('Tinebase_Record_RecordSet', $grants);

        $grants = $grants->toArray();
        $this->assertTrue($grants[0]["readGrant"]);
        $this->assertTrue($grants[0]["addGrant"]);
        $this->assertTrue($grants[0]["editGrant"]);
        $this->assertTrue($grants[0]["deleteGrant"]);
        $this->assertTrue($grants[0]["adminGrant"]);
                
        Tinebase_Container::getInstance()->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to get grants of a account on a container
     *
     */
    public function testGetGrantsOfAccount()
    {
        $container = Tinebase_Container::getInstance()->addContainer($this->objects['initialContainer'], $this->objects['grants']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
        $this->assertTrue(Tinebase_Container::getInstance()->hasGrant(Zend_Registry::get('currentAccount'), $this->objects['initialContainer'], Tinebase_Model_Container::GRANT_READ));

        $grants = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $this->objects['initialContainer']);
        $this->assertType('Tinebase_Model_Grants', $grants);
        $this->assertTrue($grants->readGrant);
        $this->assertTrue($grants->addGrant);
        $this->assertTrue($grants->editGrant);
        $this->assertTrue($grants->deleteGrant);
        $this->assertTrue($grants->adminGrant);
                
        Tinebase_Container::getInstance()->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to add an account
     *
     */
    public function testSetGrants()
    {
        $container = Tinebase_Container::getInstance()->addContainer($this->objects['initialContainer'], $this->objects['grants']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);

        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(
                array(
                    'account_id'     => Zend_Registry::get('currentAccount')->getId(),
                    'account_type'   => 'user',
                    //'account_name'   => 'not used',
                    'readGrant'      => true,
                    'addGrant'       => false,
                    'editGrant'      => true,
                    'deleteGrant'    => true,
                    'adminGrant'     => true
             ))
         );
        
        $grants = Tinebase_Container::getInstance()->setGrants($this->objects['initialContainer'], $newGrants);
        $this->assertType('Tinebase_Record_RecordSet', $grants);

        $grants = $grants->toArray();
        $this->assertTrue($grants[0]["readGrant"]);
        $this->assertFalse($grants[0]["addGrant"]);
        $this->assertTrue($grants[0]["editGrant"]);
        $this->assertTrue($grants[0]["deleteGrant"]);
        $this->assertTrue($grants[0]["adminGrant"]);
                
        Tinebase_Container::getInstance()->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to add an account
     *
     */
    public function testGetInternalContainer()
    {
        $container = Tinebase_Container::getInstance()->getInternalContainer(Zend_Registry::get('currentAccount'), 'Addressbook');
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals(Tinebase_Model_Container::TYPE_INTERNAL, $container->type);
    }
    
    /**
     * try to other users who gave grants to current account
     *
     */
    public function testGetOtherUsers()
    {
        $otherUsers = Tinebase_Container::getInstance()->getOtherUsers(Zend_Registry::get('currentAccount'), 'Addressbook', Tinebase_Model_Container::GRANT_READ);
        
        $this->assertType('Tinebase_Record_RecordSet', $otherUsers);
    }
    
    /**
     * try to get container by acl
     *
     */
    public function testGetContainerByAcl()
    {
        $container = Tinebase_Container::getInstance()->addContainer($this->objects['initialContainer'], $this->objects['grants']);

        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
        $this->assertTrue(Tinebase_Container::getInstance()->hasGrant(Zend_Registry::get('currentAccount'), $this->objects['initialContainer'], Tinebase_Model_Container::GRANT_READ));

        $readableContainer = Tinebase_Container::getInstance()->getContainerByAcl(Zend_Registry::get('currentAccount'), 'Addressbook', Tinebase_Model_Container::GRANT_READ);
        $this->assertType('Tinebase_Record_RecordSet', $readableContainer);
        $this->assertTrue(count($readableContainer) >= 2);

        Tinebase_Container::getInstance()->deleteContainer($container);
    }
    
    /**
     * test getGrantsOfRecords
     *
     */
    public function testGetGrantsOfRecords()
    {
        $userId = Zend_Registry::get('currentAccount')->getId();
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($userId);
        $records = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        $records->addRecord($contact);
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfRecords($records, $userId, 'container_id');
        
        $this->assertTrue(is_array($records[0]['container_id']));
        $this->assertGreaterThan(0, count($records[0]['container_id']['account_grants']));
        $this->assertEquals('internal', $records[0]['container_id']['type']);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Tinebase_ContainerTest::main') {
    Tinebase_ContainerTest::main();
}
