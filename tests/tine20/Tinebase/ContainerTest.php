<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_instance = Tinebase_Container::getInstance();
        
        $this->objects['initialContainer'] = $this->_instance->addContainer(new Tinebase_Model_Container(array(
            'name'              => 'tine20phpunit',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => Tinebase_Core::getUser(),
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_Contact'
        )));

        $this->objects['grants'] = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'     => Tinebase_Core::getUser()->getId(),
                'account_type'   => 'user',
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true
            )
        ));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * try to get container by id
     *
     */
    public function testGetContainerById()
    {
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
        
        $this->assertEquals('Tinebase_Model_Container', get_class($container), 'wrong type');
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
        $this->_validateOwnerId($container);
        $this->_validatePath($container);
    }
    
    /**
     * validate owner id
     * 
     * @param Tinebase_Model_Container $_container
     */
    protected function _validateOwnerId(Tinebase_Model_Container $_container)
    {
        if ($_container->type == Tinebase_Model_Container::TYPE_SHARED)  {
            $this->assertTrue(empty($_container->owner_id), 'shared container does not have an owner');
        } else {
            $this->assertTrue(! empty($_container->owner_id), 'personal container must have an owner');
        }
    }
    
    /**
     * validate path
     * 
     * @param Tinebase_Model_Container $_container
     */
    protected function _validatePath(Tinebase_Model_Container $_container)
    {
        if ($_container->type == Tinebase_Model_Container::TYPE_SHARED)  {
            $this->assertEquals("/{$_container->type}/{$_container->getId()}", $_container->path);
        } else {
            $this->assertEquals("/{$_container->type}/{$_container->owner_id}/{$_container->getId()}", $_container->path);
        }
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
            $this->objects['initialContainer']->type,
            Tinebase_Core::getUser()->getId()
        );
        
        $this->assertEquals('Tinebase_Model_Container', get_class($container), 'wrong type');
        $this->assertEquals($this->objects['initialContainer']->name, $container->name);
        $this->_validateOwnerId($container);
        $this->_validatePath($container);
    }
    
    /**
     * try to set new container name
     */
    public function testSetContainerName()
    {
        $container = $this->_instance->setContainerName($this->objects['initialContainer'], 'renamed container');
        
        $this->assertEquals('Tinebase_Model_Container', get_class($container), 'wrong type');
        $this->assertEquals('renamed container', $container->name);
        $this->_validateOwnerId($container);
        $this->_validatePath($container);
    }

    /**
    * try to set readonly content seq
    */
    public function testSetContentSeq()
    {
        $container = $this->objects['initialContainer'];
        $initialContentSeq = $container->content_seq;
        $container->content_seq = 500;
        $updatedContainer = $this->_instance->update($container);
        
        $this->assertEquals($initialContentSeq, $updatedContainer->content_seq);
    }

    /**
    * try to set content seq that was NULL / test with deleted container
    */
    public function testSetNullContentSeq()
    {
        $container = $this->objects['initialContainer'];
        $db = Tinebase_Core::getDb();
        $db->update(SQL_TABLE_PREFIX . 'container', array(
            'content_seq' => NULL,
        ), $db->quoteInto($db->quoteIdentifier('id') . ' = ?', $container->getId()));
        $seq = $this->_instance->getContentSequence($container->getId());
        $this->assertEquals(NULL, $seq);
        
        $this->_instance->increaseContentSequence($container->getId());
        $seq = $this->_instance->getContentSequence($container->getId());
        $this->assertEquals(1, $seq);
        
        $this->_instance->deleteContainer($container);
        $seq = $this->_instance->getContentSequence($container->getId());
        $this->assertEquals(1, $seq);
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
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::GRANT_READ));

        $grants = $this->_instance->getGrantsOfContainer($this->objects['initialContainer']);
        
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($grants), 'wrong type');

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
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::GRANT_READ));

        $grants = $this->_instance->getGrantsOfAccount(Tinebase_Core::getUser(), $this->objects['initialContainer']);
        
        $this->assertEquals('Tinebase_Model_Grants', get_class($grants), 'wrong type');
        $this->assertTrue($grants->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue($grants->{Tinebase_Model_Grants::GRANT_ADD});
        $this->assertTrue($grants->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue($grants->{Tinebase_Model_Grants::GRANT_DELETE});
        $this->assertTrue($grants->{Tinebase_Model_Grants::GRANT_ADMIN});
    }
    
    /**
     * try to set grants
     */
    public function testSetGrants()
    {
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(array(
                    'account_id'     => Tinebase_Core::getUser()->getId(),
                    'account_type'   => 'user',
                    Tinebase_Model_Grants::GRANT_READ      => true,
                    Tinebase_Model_Grants::GRANT_ADD       => false,
                    Tinebase_Model_Grants::GRANT_EDIT      => true,
                    Tinebase_Model_Grants::GRANT_DELETE    => true,
                    Tinebase_Model_Grants::GRANT_ADMIN     => true
             ))
        );
        
        // get group and add grants for it
        $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
            array('field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE)
        )));
        $groupToAdd = $lists->getFirstRecord();
        $newGrants->addRecord(
            new Tinebase_Model_Grants(array(
                    'account_id'     => $groupToAdd->group_id,
                    'account_type'   => 'group',
                    Tinebase_Model_Grants::GRANT_READ      => true,
                    Tinebase_Model_Grants::GRANT_ADD       => false,
                    Tinebase_Model_Grants::GRANT_EDIT      => false,
                    Tinebase_Model_Grants::GRANT_DELETE    => false,
                    Tinebase_Model_Grants::GRANT_ADMIN     => false
             ))
        );
        
        $grants = $this->_instance->setGrants($this->objects['initialContainer'], $newGrants);
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($grants), 'wrong type');
        $this->assertEquals(2, count($grants));

        $grants = $grants->toArray();
        foreach ($grants as $grant) {
            if ($grant['account_id'] === Tinebase_Core::getUser()->getId()) {
                $this->assertTrue($grant["readGrant"], print_r($grant, TRUE));
                $this->assertFalse($grant["addGrant"], print_r($grant, TRUE));
                $this->assertTrue($grant["editGrant"], print_r($grant, TRUE));
                $this->assertTrue($grant["deleteGrant"], print_r($grant, TRUE));
                $this->assertTrue($grant["adminGrant"], print_r($grant, TRUE));
                $this->assertEquals(Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $grant['account_type']);
            } else {
                $this->assertTrue($grant["readGrant"], print_r($grant, TRUE));
                $this->assertEquals(Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $grant['account_type']);
            }
        }
    }
    
    /**
     * testDuplicateGrantsWithSetGrants
     */
    public function testDuplicateGrantsWithSetGrants()
    {
        $this->testSetGrants();
        
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(array(
                    'account_id'     => Tinebase_Core::getUser()->getId(),
                    'account_type'   => 'user',
                    Tinebase_Model_Grants::GRANT_ADMIN     => true
             ))
        );
        $grants = $this->_instance->setGrants($this->objects['initialContainer'], $newGrants);
        $this->assertEquals(1, count($grants));
        
        // check num of db rows
        $stmt = Tinebase_Core::getDb()->query('select * from '.Tinebase_Core::getDb()->quoteIdentifier(SQL_TABLE_PREFIX .'container_acl') . ' where ' . Tinebase_Core::getDb()->quoteInto(Tinebase_Core::getDb()->quoteIdentifier('container_id') . ' = ?', $this->objects['initialContainer']->getId()));
        $rows = $stmt->fetchAll();
        
        $this->assertEquals(1, count($rows));
    }
    
    /**
     * testOverwriteGrantsWithAddGrants
     * 
     * -> addGrants() should create no duplicates! 
     */
    public function testOverwriteGrantsWithAddGrants()
    {
        $result = $this->_instance->addGrants($this->objects['initialContainer'], 'user', Tinebase_Core::getUser()->getId(), array(Tinebase_Model_Grants::GRANT_ADMIN));
        $this->assertTrue($result);
        
        // check num of db rows
        $stmt = Tinebase_Core::getDb()->query('select * from '.Tinebase_Core::getDb()->quoteIdentifier(SQL_TABLE_PREFIX .'container_acl') . ' where ' . Tinebase_Core::getDb()->quoteInto(Tinebase_Core::getDb()->quoteIdentifier('container_id') . ' = ?', $this->objects['initialContainer']->getId()));
        $rows = $stmt->fetchAll();
        
        $this->assertEquals(7, count($rows));
    }
    
    /**
     * try to other users who gave grants to current account
     *
     */
    public function testGetOtherUsers()
    {
        $otherUsers = $this->_instance->getOtherUsers(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::GRANT_READ);
        
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($otherUsers), 'wrong type');
        $this->assertEquals(0, $otherUsers->filter('accountId', Tinebase_Core::getUser()->getId())->count(), 'current user must not be part of otherUsers');
    }
    
    /**
     * get shared containers
     *
     */
    public function testGetSharedContainer()
    {
        $container = $this->_instance->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'containerTest' . Tinebase_Record_Abstract::generateUID(),
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'backend'        => 'Sql'
        )));
        
        $otherUsers = $this->_instance->getOtherUsers(Tinebase_Core::getUser(), 'Addressbook', array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_ADMIN,
        ));
        
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($otherUsers), 'wrong type');
        $this->assertEquals(0, $otherUsers->filter('accountId', Tinebase_Core::getUser()->getId())->count(), 'current user must not be part of otherUsers');
    
        $this->_instance->deleteContainer($container->getId(), TRUE);
    }

    /**
     * testGetSearchContainerWithoutReadButWithAdminGrant
     */
    public function testGetSearchContainerWithoutReadButWithAdminGrant()
    {
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(array(
                    'account_id'     => Tinebase_Core::getUser()->getId(),
                    'account_type'   => 'user',
                    Tinebase_Model_Grants::GRANT_READ      => false,
                    Tinebase_Model_Grants::GRANT_ADD       => true,
                    Tinebase_Model_Grants::GRANT_EDIT      => true,
                    Tinebase_Model_Grants::GRANT_DELETE    => true,
                    Tinebase_Model_Grants::GRANT_ADMIN     => true
             ))
        );
        $grants = $this->_instance->setGrants($this->objects['initialContainer'], $newGrants);
        
        $container = $this->_instance->getContainerById($this->objects['initialContainer']);
        $this->assertTrue(is_object($container));
        
        $containers = $this->_instance->getPersonalContainer(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ);
        $container = $containers->find('name', $this->objects['initialContainer']->name);
        $this->assertTrue(is_object($container));
    }
    
    /**
     * try to get container by acl
     *
     */
    public function testGetContainerByAcl()
    {
        $this->assertTrue($this->_instance->hasGrant(Tinebase_Core::getUser(), $this->objects['initialContainer'], Tinebase_Model_Grants::GRANT_READ));

        $readableContainer = $this->_instance->getContainerByAcl(Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::GRANT_READ);
        $this->assertEquals('Tinebase_Record_RecordSet', get_class($readableContainer), 'wrong type');
        $this->assertTrue(count($readableContainer) >= 2);
        foreach($readableContainer as $container) {
            $this->_validateOwnerId($container);
            $this->_validatePath($container);
        }
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
        
        $this->assertTrue($records[0]['container_id'] instanceof Tinebase_Model_Container, 'contaienr_id is not resolved');
        $this->assertTrue(!empty($records[0]['container_id']['path']), 'path is not added');
        $this->assertGreaterThan(0, count($records[0]['container_id']['account_grants']));
        $this->assertEquals('shared', $records[0]['container_id']['type']);
    }
    
    /**
     * try to move a contact to another container and check content sequence
     */
    public function testMoveContactToContainer()
    {
        $sharedContainer = $this->_instance->addContainer(new Tinebase_Model_Container(array(
            'name'              => 'tine20shared',
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'owner_id'          => Tinebase_Core::getUser(),
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
        )));
        $initialContainer = $this->_instance->get($this->objects['initialContainer']);
        $contact = new Addressbook_Model_Contact(array(
            'n_family'              => 'Tester',
            'container_id'          => $sharedContainer->getId()
        ));
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        $filter = array(array('field' => 'id', 'operator' => 'in', 'value' => array($contact->getId())));
        $containerJson = new Tinebase_Frontend_Json_Container();
        $result = $containerJson->moveRecordsToContainer($initialContainer->getId(), $filter, 'Addressbook', 'Contact');
        $this->assertGreaterThan(0, count($result['results']), 'no contacts moved');
        $this->assertEquals($contact->getId(), $result['results'][0]);
        
        $movedContact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        
        $this->assertEquals($initialContainer->getId(), $movedContact->container_id, 'contact has not been moved');
        
        $contentSeqs = $this->_instance->getContentSequence(array($sharedContainer->getId(), $initialContainer->getId()));
        $this->assertEquals(2, $contentSeqs[$sharedContainer->getId()], 'content seq mismatch: ' . print_r($contentSeqs, TRUE));
        $this->assertEquals(1, $contentSeqs[$initialContainer->getId()], 'content seq mismatch: ' . print_r($contentSeqs, TRUE));
        
        // check container content
        $contentRecords = $this->_instance->getContentHistory($sharedContainer->getId());
        $this->assertEquals(2, count($contentRecords));
        $this->assertEquals($contact->getId(), $contentRecords->getFirstRecord()->record_id);
        $this->assertEquals(Tinebase_Model_ContainerContent::ACTION_CREATE, $contentRecords->getFirstRecord()->action);
    }
    
    /**
     * get not disabled users
     *
     */
    public function testGetNotDisabledUsers()
    {
        $user1 = Tinebase_User::getInstance()->getUserByLoginName('jsmith');
        $user2 = Tinebase_User::getInstance()->getUserByLoginName('pwulf');
        
        Tinebase_User::getInstance()->setStatus($user2, 'enabled');
        
        $container = Tinebase_Container::getInstance()->getPersonalContainer($user1, 'Calendar', $user1, Tinebase_Model_Grants::GRANT_READ);
        
        $oldGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($container->getFirstRecord()->id, TRUE);
                
        $newGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        $newGrants->addRecord(
            new Tinebase_Model_Grants(
                array(
                    'account_id'    => $user1->accountId,
                    'account_type'  => 'user',
                    Tinebase_Model_Grants::GRANT_READ     => true,
                    Tinebase_Model_Grants::GRANT_ADD      => true,
                    Tinebase_Model_Grants::GRANT_EDIT     => true,
                    Tinebase_Model_Grants::GRANT_DELETE   => true,
                    Tinebase_Model_Grants::GRANT_ADMIN    => true
                )
            )
        );
        $newGrants->addRecord(
            new Tinebase_Model_Grants(
                array(
                    'account_id'    => $user2->accountId,
                    'account_type'  => 'user',
                    Tinebase_Model_Grants::GRANT_READ     => true,
                    Tinebase_Model_Grants::GRANT_ADD      => true,
                    Tinebase_Model_Grants::GRANT_EDIT     => true,
                    Tinebase_Model_Grants::GRANT_DELETE   => true,
                    Tinebase_Model_Grants::GRANT_ADMIN    => true
                )
            )
        );
        
        Tinebase_Container::getInstance()->setGrants($container->getFirstRecord()->id, $newGrants, TRUE);
        
        $otherUsers = Tinebase_Container::getInstance()->getOtherUsers($user1, 'Calendar', array(
            Tinebase_Model_Grants::GRANT_READ
        ));
        
        $this->assertEquals(1, $otherUsers->filter('accountId', $user2->accountId)->count());
        
        Tinebase_User::getInstance()->setStatus($user2, 'disabled');
        
        $otherUsers = Tinebase_Container::getInstance()->getOtherUsers($user1, 'Calendar', array(
            Tinebase_Model_Grants::GRANT_READ
        ));
        
        Tinebase_User::getInstance()->setStatus($user2, 'enabled');
        Tinebase_Container::getInstance()->setGrants($container->getFirstRecord()->id, $oldGrants, TRUE);
        
        $this->assertEquals(0, $otherUsers->filter('accountId', $user2->accountId)->count());
    }
    
    /**
     * get other users container
     *
     */
    public function testGetOtherUsersContainer()
    {
        $user1 = Tinebase_User::getInstance()->getUserByLoginName('jsmith');
        $otherUsers = Tinebase_Container::getInstance()->getOtherUsersContainer($user1, 'Calendar', array(
            Tinebase_Model_Grants::GRANT_READ
        ));
        $this->assertTrue($otherUsers->getRecordClassName() === 'Tinebase_Model_Container');
        
        foreach($otherUsers as $container) {
            $this->_validateOwnerId($container);
            $this->_validatePath($container);
        }
    }

    /**
     * search container with owner filter
     */
    public function testSearchContainerByOwner()
    {
        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'owner', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        ));
        $result = Tinebase_Container::getInstance()->search($filter);
        
        $this->assertTrue(count($result) > 0);
        
        foreach ($result as $container) {
            $this->assertEquals(Tinebase_Model_Container::TYPE_PERSONAL, $container->type);
            $this->assertTrue(Tinebase_Container::getInstance()->hasGrant(
                Tinebase_Core::getUser()->getId(), $container->getId(), Tinebase_Model_Grants::GRANT_ADMIN
            ), 'no admin grant:' . print_r($container->toArray(), TRUE));
        }
    }

    /**
     * testSetContainerColor
     * 
     * @see 0006274: Can no longer change calendar's color
     */
    public function testSetContainerColor()
    {
        $result = $this->_instance->setContainerColor($this->objects['initialContainer'], '#99CC00');
        $this->assertEquals('#99CC00', $result->color);
    }
    
    /**
     * testGetPersonalContainer
     * @see https://gerrit.tine20.org/tine20/#/c/862
     */
    
    public function testGetPersonalContainer()
    {
        $uid=Tinebase_Core::getUser()->getId();
        $containers = Tinebase_Container::getInstance()->getPersonalContainer($uid, 'Addressbook_Model_Contact', $uid, Tinebase_Model_Grants::GRANT_READ);
        $container = $containers->filter('name', 'tine20phpunit')->getFirstRecord();
        $this->assertEquals($container->name, 'tine20phpunit');
        $this->assertEquals($container->model, 'Addressbook_Model_Contact');
    }
}
