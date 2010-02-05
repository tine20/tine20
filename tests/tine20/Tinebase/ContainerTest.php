<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * unit under test (UIT)
     * @var Tinebase_Container
     */
    protected $_instance;

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
        $this->_instance = Tinebase_Container::getInstance();
        
        $this->objects['initialContainer'] = $this->_instance->addContainer(new Tinebase_Model_Container(array(
            'name'              => 'tine20phpunit',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            //'account_grants'    => 'Tine 2.0',
        )));

        $this->objects['grants'] = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'     => Tinebase_Core::getUser()->getId(),
                'account_type'   => 'user',
                //'account_name'   => 'not used',
                Tinebase_Model_Grants::READGRANT      => true,
                Tinebase_Model_Grants::ADDGRANT       => true,
                Tinebase_Model_Grants::EDITGRANT      => true,
                Tinebase_Model_Grants::DELETEGRANT    => true,
                Tinebase_Model_Grants::ADMINGRANT     => true
            )            
        ));
        
        $this->objects['contactsToDelete'] = array();
        
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
        try {
	        $this->_instance->deleteContainer($this->objects['initialContainer']);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        
        foreach ($this->objects['contactsToDelete'] as $contactId) {
            Addressbook_Controller_Contact::getInstance()->delete($contactId);
        }
    }
    
    /**
     * try to get container by id
     *
     */
    public function testGetContainerById()
    {
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
    }
    
    /**
     * try to get container by name
     *
     */
    public function testGetContainerByName()
    {
        $container = $this->_instance->getContainerByName(
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
        $container = $this->_instance->setContainerName($this->objects['initialContainer'], 'renamed container');
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals('renamed container', $container->name);
    }
    
    /**
     * try to add an existing container. should throw an exception
     *
     */
    public function testDeleteContainer()
    {
        $this->_instance->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to delete an existing container with a contact 
     */
    public function testDeleteContainerWithContact()
    {
        // add contact to container
        $contact = new Addressbook_Model_Contact(array(
            'n_family'              => 'Tester',
            'container_id'          => $this->objects['initialContainer']->getId()
        ));
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        // delete container
        $this->_instance->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
        
        Addressbook_Controller_Contact::getInstance()->delete($contact->getId());
    }
    
    /**
     * try to get all grants of a container
     *
     */
    public function testGetGrantsOfContainer()
    {
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::READGRANT));

        $grants = $this->_instance->getGrantsOfContainer($this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Record_RecordSet', $grants);

        $grants = $grants->toArray();
        $this->assertTrue($grants[0]["readGrant"]);
        $this->assertTrue($grants[0]["addGrant"]);
        $this->assertTrue($grants[0]["editGrant"]);
        $this->assertTrue($grants[0]["deleteGrant"]);
        $this->assertTrue($grants[0]["adminGrant"]);
                
        $this->_instance->deleteContainer($this->objects['initialContainer']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
    }
    
    /**
     * try to get grants of a account on a container
     *
     */
    public function testGetGrantsOfAccount()
    {
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::READGRANT));

        $grants = $this->_instance->getGrantsOfAccount(Tinebase_Core::getUser(), $this->objects['initialContainer']);
        
        $this->assertType('Tinebase_Model_Grants', $grants);
        $this->assertTrue($grants->{Tinebase_Model_Grants::READGRANT});
        $this->assertTrue($grants->{Tinebase_Model_Grants::ADDGRANT});
        $this->assertTrue($grants->{Tinebase_Model_Grants::EDITGRANT});
        $this->assertTrue($grants->{Tinebase_Model_Grants::DELETEGRANT});
        $this->assertTrue($grants->{Tinebase_Model_Grants::ADMINGRANT});
    }
    
    /**
     * try to add an account
     *
     */
    public function testSetGrants()
    {
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(
                array(
                    'account_id'     => Tinebase_Core::getUser()->getId(),
                    'account_type'   => 'user',
                    //'account_name'   => 'not used',
                    Tinebase_Model_Grants::READGRANT      => true,
                    Tinebase_Model_Grants::ADDGRANT       => false,
                    Tinebase_Model_Grants::EDITGRANT      => true,
                    Tinebase_Model_Grants::DELETEGRANT    => true,
                    Tinebase_Model_Grants::ADMINGRANT     => true
             ))
         );
        
        $grants = $this->_instance->setGrants($this->objects['initialContainer'], $newGrants);
        $this->assertType('Tinebase_Record_RecordSet', $grants);

        $grants = $grants->toArray();
        $this->assertTrue($grants[0]["readGrant"]);
        $this->assertFalse($grants[0]["addGrant"]);
        $this->assertTrue($grants[0]["editGrant"]);
        $this->assertTrue($grants[0]["deleteGrant"]);
        $this->assertTrue($grants[0]["adminGrant"]);
    }
    
    /**
     * try to add an account
     *
     */
    public function testGetInternalContainer()
    {
        $container = $this->_instance->getInternalContainer(Tinebase_Core::getUser(), 'Addressbook');
        
        $this->assertType('Tinebase_Model_Container', $container);
        $this->assertEquals(Tinebase_Model_Container::TYPE_INTERNAL, $container->type);
    }
    
    /**
     * try to other users who gave grants to current account
     *
     */
    public function testGetOtherUsers()
    {
        $otherUsers = $this->_instance->getOtherUsers(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::READGRANT);
        
        $this->assertType('Tinebase_Record_RecordSet', $otherUsers);
    }
    
    /**
     * try to get container by acl
     *
     */
    public function testGetContainerByAcl()
    {
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::READGRANT));

        $readableContainer = $this->_instance->getContainerByAcl(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::READGRANT);
        $this->assertType('Tinebase_Record_RecordSet', $readableContainer);
        $this->assertTrue(count($readableContainer) >= 2);
    }
    
    /**
     * test getGrantsOfRecords
     *
     */
    public function testGetGrantsOfRecords()
    {
        $userId = Tinebase_Core::getUser()->getId();
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($userId);
        $records = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        $records->addRecord($contact);
        
        $grants = $this->_instance->getGrantsOfRecords($records, $userId, 'container_id');
        
        $this->assertTrue(is_array($records[0]['container_id']));
        $this->assertGreaterThan(0, count($records[0]['container_id']['account_grants']));
        $this->assertEquals('internal', $records[0]['container_id']['type']);
    }
    
    /**
     * try to move a contact to another container 
     */
    public function testMoveContactToContainer()
    {
        // add contact to container
        $personalContainer = $this->_instance->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Addressbook');
        $contact = new Addressbook_Model_Contact(array(
            'n_family'              => 'Tester',
            'container_id'          => $personalContainer->getId()
        ));
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        $this->objects['contactsToDelete'][] = $contact->getId();
        
        $this->_instance->moveRecordsToContainer($this->objects['initialContainer']->getId(), array($contact->getId()), 'Addressbook', 'Contact');
        
        $movedContact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $movedContact->container_id, 'contact has not been moved');
    }
    
}		

if (PHPUnit_MAIN_METHOD == 'Tinebase_ContainerTest::main') {
    Tinebase_ContainerTest::main();
}
