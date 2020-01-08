<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Frontend_Json_ContainerTest extends TestCase
{
    /**
     * @var Tinebase_Frontend_Json_Container
     */
    protected $_backend = NULL;
    
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_backend = new Tinebase_Frontend_Json_Container();
    }

    /**
     * try to add an account
     */
    public function testAddContainer()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');

        $this->assertEquals('Tine 2.0 Unittest', $container['name']);
        $this->assertEquals('Addressbook_Model_Contact', $container['model']);
        $this->assertTrue($container['account_grants'][Tinebase_Model_Grants::GRANT_ADMIN]);

        Tinebase_Container::getInstance()->deleteContainer($container['id']);
    }

    public function testGetContainer()
    {
        $containers = $this->_backend->getContainer('Addressbook_Model_Contact', Tinebase_Model_Container::TYPE_SHARED, false);
        self::assertGreaterThanOrEqual(1, count($containers), 'should find at least INTERNAL CONTACTS');
    }

    /**
     * try to add an account
     *
     */
    public function testDeleteContainer()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');

        $this->assertEquals('Tine 2.0 Unittest', $container['name']);
        $this->assertEquals('Addressbook_Model_Contact', $container['model']);
        $this->_backend->deleteContainer($container['id']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($container['id']);
        
    }
        
    /**
     * try to add an account
     *
     */
    public function testRenameContainer()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');

        $this->assertEquals('Tine 2.0 Unittest', $container['name']);

        
        $container = $this->_backend->renameContainer($container['id'], 'Tine 2.0 Unittest renamed');

        $this->assertEquals('Tine 2.0 Unittest renamed', $container['name']);
        $this->assertEquals('Addressbook_Model_Contact', $container['model']);

        $this->_backend->deleteContainer($container['id']);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        $container = Tinebase_Container::getInstance()->getContainerById($container['id']);
    }
    
    /**
     * try to add an account
     *
     */
    public function testGetContainerGrants()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');

        $this->assertEquals('Tine 2.0 Unittest', $container['name']);


        $grants = $this->_backend->getContainerGrants($container['id']);

        $this->assertEquals(1, $grants['totalcount']);
        $this->assertTrue($grants['results'][0]["readGrant"]);
        $this->assertEquals(Zend_Registry::get('currentAccount')->getId(), $grants['results'][0]["account_id"]);

        $this->_backend->deleteContainer($container['id']);

        $this->setExpectedException('Tinebase_Exception_NotFound');

        $container = Tinebase_Container::getInstance()->getContainerById($container['id']);
    }
            
    /**
     * try to set container grants
     *
     */
    public function testSetContainerGrants()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Tine 2.0 Unittest', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');

        $this->assertEquals('Tine 2.0 Unittest', $container['name']);
        
        $newGrants = array(
            array(
                'account_id'     => Zend_Registry::get('currentAccount')->getId(),
                'account_type'   => 'user',
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => false,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
                Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA     => true,
            )
        );
        
        $grants = $this->_backend->setContainerGrants($container['id'], $newGrants);
        
        self::assertEquals(1, count($grants['results']));
        $containergrants = $grants['results'][0];
        self::assertFalse($containergrants["deleteGrant"]);
        self::assertTrue($containergrants["adminGrant"]);
        self::assertTrue(isset($containergrants[ Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA]),
            print_r($containergrants, true));
        self::assertTrue($containergrants[ Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA]);


        $this->_backend->deleteContainer($container['id']);

        $this->setExpectedException('Tinebase_Exception_NotFound');

        Tinebase_Container::getInstance()->getContainerById($container['id']);
    }
    
    /**
     * try to search containers
     *
     */
    public function testSearchContainers()
    {
        $container = $this->_backend->addContainer('Addressbook', 'Winter', Tinebase_Model_Container::TYPE_PERSONAL, 'Contact');
        $this->assertEquals('Winter', $container['name']);
        
        $filter = array(array(
                'field'     => 'query',
                'operator'     => 'contains',
                'value'     => 'Winter'
        ),array('field' => 'application_id', 'operator' => 'equals', 'value' =>
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()));
        $paging = array(
                'start'    => 0,
                'limit'    => 1
        );
        
        $result = $this->_backend->searchContainers($filter, $paging);

        $this->assertGreaterThan(0, $result['totalcount']);
        $this->assertEquals($container['name'], $result['results'][0]['name']);
        $this->assertTrue(isset($result['results'][0]['account_grants']['readGrant']), 'account_grants missing');
        $this->assertTrue(isset($result['results'][0]['ownerContact']['email']), 'ownerContact missing');
    }

    public function testDuplicateContainerOnGetContainer()
    {
        // create new user
        $user = $this->_createTestUser();
        Tinebase_Core::setUser($user);

        $container1 = $this->_backend->getContainer(Addressbook_Model_Contact::class,
            Tinebase_Model_Container::TYPE_PERSONAL,
            $user->getId());

        self::assertEquals(1, count($container1));
        self::assertEquals(Addressbook_Model_Contact::class, $container1[0]['model']);

        $container2 = $this->_backend->getContainer(Addressbook_Model_List::class,
            Tinebase_Model_Container::TYPE_PERSONAL,
            $user->getId());

        self::assertTrue(is_array($container2));
        self::assertEquals(1, count($container2), 'no new container should be created');
        self::assertEquals($container1, $container2, 'no new container should be created: '
            . print_r($container2, true));
    }

    public function testGetCalendarSharedContainer()
    {
        $containers = $this->_backend->getContainer('Calendar',
            Tinebase_Model_Container::TYPE_SHARED,
            false);
        self::assertTrue(is_array($containers));
    }
}
