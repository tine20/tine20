<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Tinebase_User_Abstract
 */
class Tinebase_User_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test
     *
     * @var Tinebase_User_Abstract
     */
    protected $_uit = NULL;
    
    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_uit = Tinebase_User::getInstance();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $this->_deleteDummyUsers();
    }
    
    /**
     * test generation of login names
     */
    public function testGenerateUserName()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // error: Zend_Ldap_Exception: 0x44 (Already exists;
            // Entry CN=Leonie Weiss,CN=Users,DC=example,DC=org already exists):
            // adding: cn=Leonie Weiss,cn=Users,dc=example,dc=org
            $this->markTestSkipped('skipped for ad backends as it does not allow duplicate CNs');
        }

        $user = new Tinebase_Model_FullUser(array(
            'accountFirstName' => 'Leonie',
            'accountLastName'  => 'Weiss',
            'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup
        ), true);
        
        $createdUserIds = array();
        for ($i=0; $i<10; $i++) {
            $user->accountLoginName = $this->_uit->generateUserName($user);
            $createdUserIds[] = $this->_uit->addUser($user)->getId();
            $user->setId(NULL);
        }
        
        $this->_uit->deleteUsers($createdUserIds);
    }
    
    /**
     * test User with the same Name
     * Suffix for accountLoginName and accountFullName
     */
    public function testDoubleUserNames()
    {
        $user1 = new Tinebase_Model_FullUser(array(
            'accountFirstName' => 'Leonie',
            'accountLastName'  => 'Weiss',
            'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup
        ), true);
        $user1->accountLoginName = $this->_uit->generateUserName($user1);
        $user1->accountFullName = $this->_uit->generateAccountFullName($user1);
        $this->_uit->addUser($user1);
        
        $user2 = new Tinebase_Model_FullUser(array(
            'accountFirstName' => 'Leonie',
            'accountLastName'  => 'Weiss',
            'accountPrimaryGroup' => Tinebase_Core::getUser()->accountPrimaryGroup
        ), true);
        $user2->accountLoginName = $this->_uit->generateUserName($user2);
        $user2->accountFullName = $this->_uit->generateAccountFullName($user2);
        $this->_uit->addUser($user2);
        
        $this->assertEquals('weissle', $user1->accountLoginName);
        $this->assertEquals('weissle00', $user2->accountLoginName);
        
        $this->assertEquals('Leonie Weiss', $user1->accountFullName);
        $this->assertEquals('Leonie Weiss00', $user2->accountFullName);
        
        $this->_uit->deleteUser($user1->getId());
        $this->_uit->deleteUser($user2->getId());
    }
    
    public function testCachePassword()
    {
        //Tinebase_User::getInstance()->cachePassword('secret');
        //$this->assertEquals('secret', Tinebase_User::getInstance()->getCachedPassword());
        $InCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials('username', 'secret');
        
        $outCache = new Tinebase_Model_CredentialCache(array(
            'id'    => $InCache->getId(),
            'key'   => $InCache->key
        ));
        
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($outCache);
        $this->assertEquals('username', $outCache->username);
        $this->assertEquals('secret', $outCache->password);
    }
        
    public function testResolveUsersWithOneField()
    {
        $this->_createAndStoreDummyUsers(1);
               
        // test resolveUsers with one record and one field
        $dummyUser = $this->_objects[0];
        $dummyRecord = new Tinebase_Record_DummyRecord(array('test_1' => $dummyUser->getId()), true);

        $this->assertFalse($dummyRecord->test_1 instanceof Tinebase_Model_User);
        $this->_uit->resolveUsers($dummyRecord, 'test_1');
        $this->assertTrue($dummyRecord->test_1 instanceof Tinebase_Model_User);
     }
     
     public function testResolveUsersWithMultipleFields()
     {
        $this->_createAndStoreDummyUsers(2);
        $dummyRecord = new Tinebase_Record_DummyRecord(array('test_1' => $this->_objects[0]->getId(), 'test_2' => $this->_objects[1]->getId()), true);

        $this->_uit->resolveUsers($dummyRecord, array('test_1', 'test_2'));
        $this->assertTrue($dummyRecord->test_1 instanceof Tinebase_Model_User);
        $this->assertTrue($dummyRecord->test_2 instanceof Tinebase_Model_User);
        $this->assertNotEquals($dummyRecord->test_1->getId(), $dummyRecord->test_2->getId());
     }
     
    public function testResolveMultipleUsersWithMultipleFields()
    {
        $this->_createAndStoreDummyUsers(3);
               
        $dummyRecordSet = new Tinebase_Record_RecordSet('Tinebase_Record_DummyRecord');
        $dummyRecordSet->addRecord(new Tinebase_Record_DummyRecord(array('test_1' => $this->_objects[0]->getId(), 'test_2' => $this->_objects[1]->getId()), true));
        $dummyRecordSet->addRecord(new Tinebase_Record_DummyRecord(array('test_1' => $this->_objects[0]->getId()), true));
        $this->_uit->resolveMultipleUsers($dummyRecordSet, array('test_1', 'test_2'));
        $this->assertTrue($dummyRecordSet[0]->test_1 instanceof Tinebase_Model_User);
        $this->assertEquals($dummyRecordSet[0]->test_1->getId(), $this->_objects[0]->getId());
        $this->assertTrue($dummyRecordSet[0]->test_2 instanceof Tinebase_Model_User);
        $this->assertTrue($dummyRecordSet[1]->test_1 instanceof Tinebase_Model_User);
        $this->assertNull($dummyRecordSet[1]->test_2);
    }

    public function testResolveUserWithNonExistentUser()
    {
        $dummyId = Tinebase_Record_Abstract::generateUID();

        $dummyRecord = new Tinebase_Record_DummyRecord(array('test_1' => $dummyId), true);

        //test without option "addNonExistentUsers"
        $this->_uit->resolveUsers($dummyRecord, 'test_1', false);
        $this->assertFalse($dummyRecord->test_1 instanceof Tinebase_Model_User);
        $this->assertEquals($dummyRecord->test_1, $dummyId);
        
        //test with option "addNonExistentUsers"
        $dummyRecord = new Tinebase_Record_DummyRecord(array('test_1' => $dummyId), true);

        $this->_uit->resolveUsers($dummyRecord, 'test_1', true);
        $this->assertTrue($dummyRecord->test_1 instanceof Tinebase_Model_User);
        $this->assertEquals($dummyRecord->test_1->accountFirstName, $this->_uit->getNonExistentUser()->accountFirstName);
    }
    
    protected function _createAndStoreDummyUsers($count)
    {
        for ($i=0; $i<$count; $i++) {
            $dummyUser = new Tinebase_Model_FullUser(array(
                'accountLoginName'      => 'dummy_'.$i,
                'accountStatus'         => 'enabled',
                'accountExpires'        => NULL,
                'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
                'accountLastName'       => 'Dummy',
                'accountFirstName'      => 'No.'.$i,
                'accountEmailAddress'   => 'phpunit@' . TestServer::getPrimaryMailDomain(),
            ));
            $this->_uit->addUser($dummyUser);
            $this->_objects[] = $dummyUser;
        }
    }
    
    protected function _deleteDummyUsers()
    {
        foreach ($this->_objects as $object) {
            $this->_uit->deleteUser($object->getId());
        }
    }
}       
