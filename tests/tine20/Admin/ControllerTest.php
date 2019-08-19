<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo reactivate tests and make them independent
 */

/**
 * Test class for Tinebase_Admin
 */
class Admin_ControllerTest extends TestCase
{
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

//        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
//            'id'            => 'test-controller-group',
//            'name'          => 'tine20phpunit',
//            'description'   => 'initial test group'
//        ));
//
//        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
//            'id'            => 'test-controller-group',
//            'name'          => 'tine20phpunit updated',
//            'description'   => 'updated test group'
//        ));
//
//        $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
//            'accountId'             => 'dflkjgldfgdfgd',
//            'accountLoginName'      => 'tine20phpunit',
//            'accountStatus'         => 'enabled',
//            'accountExpires'        => NULL,
//            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
//            'accountLastName'       => 'Tine 2.0',
//            'accountFirstName'      => 'PHPUnit',
//            'accountEmailAddress'   => 'phpunit@metaways.de' // use $this->_getMailDomain()
//        ));
//
//        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
//            'accountLoginName'      => 'tine20phpunit-updated',
//            'accountStatus'         => 'disabled',
//            'accountExpires'        => NULL,
//            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
//            'accountLastName'       => 'Tine 2.0 Updated',
//            'accountFirstName'      => 'PHPUnit Updated',
//            'accountEmailAddress'   => 'phpunit@tine20.org' /7 use $this->_getMailDomain()
//        ));
//
//            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
//                $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
//
//                $this->objects['initialGroup']->container_id = $internalAddressbook->getId();
//                $this->objects['updatedGroup']->container_id = $internalAddressbook->getId();
//                $this->objects['initialAccount']->container_id = $internalAddressbook->getId();
//                $this->objects['updatedAccount']->container_id = $internalAddressbook->getId();
//            }
    }

    /**
     * try to add an account
     */
    public function testAddAccount()
    {
        $this->markTestSkipped('TODO make this test independent');

        $account = Admin_Controller_User::getInstance()->create($this->objects['initialAccount'], 'lars', 'lars');
        $this->assertTrue(!empty($account->accountId));
        $this->assertEquals($this->objects['initialAccount']->accountLoginName, $account->accountLoginName);
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($account->accountId);
        $this->assertTrue(!empty($contact->creation_time));
        $this->assertEquals(Tinebase_Core::getUser()->accountId, $account->created_by, 'created_by not matching');
        $this->assertTrue($account->creation_time instanceof Tinebase_DateTime, 'creation time not set: ' . print_r($account->toArray(), true));
        $this->assertEquals(Tinebase_DateTime::now()->format('Y-m-d'), $account->creation_time->format('Y-m-d'));
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     */
    public function testGetAccounts()
    {
        $this->markTestSkipped('TODO make this test independent');

        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($this->objects['initialAccount']['accountLoginName']);
                
        $this->assertEquals(1, count($accounts));
    }
    
    /**
     * try to delete an accout
     */
    public function testDeleteAccount()
    {
        $this->markTestSkipped('TODO make this test independent');

        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($this->objects['initialAccount']['accountLoginName']);
        
        Admin_Controller_User::getInstance()->delete($accounts->getArrayOfIds());
        
        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($this->objects['initialAccount']['accountLoginName']);

        $this->assertEquals(0, count($accounts));
    }

    /**
     * try to delete self
     */
    public function testDeleteSelf()
    {
        $this->markTestSkipped('TODO make this test independent');

        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        Admin_Controller_User::getInstance()->delete(Tinebase_Core::getUser()->getId());
    }

    /**
     * try to add a group
     */
    public function testAddGroup()
    {
        $this->markTestSkipped('TODO make this test independent');

        $group = Admin_Controller_Group::getInstance()->create($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
        $this->assertEquals(Tinebase_Core::getUser()->accountId, $group->created_by);
        $this->assertEquals(Tinebase_DateTime::now()->format('Y-m-d'), $group->creation_time->format('Y-m-d'));
    }
    
    /**
     * try to get all groups
     */
    public function testGetGroups()
    {
        $this->markTestSkipped('TODO make this test independent');

        $groups = Admin_Controller_Group::getInstance()->search($this->objects['initialGroup']->name);
        
        $this->assertEquals(1, count($groups));
    }    

    /**
     * try to get Users group
     *
     */
    public function testGetGroup()
    {
        $this->markTestSkipped('TODO make this test independent');

        $groups = Admin_Controller_Group::getInstance()->search($this->objects['initialGroup']->name);
        
        $group = Admin_Controller_Group::getInstance()->get($groups[0]->getId());
        
        $this->assertEquals($this->objects['initialGroup']->name, $group->name);
    }    

    /**
     * try to delete a group
     *
     */
    public function testDeleteGroups()
    {
        $this->markTestSkipped('TODO make this test independent');

        $groups = Admin_Controller_Group::getInstance()->search($this->objects['initialGroup']->name);
        
        Admin_Controller_Group::getInstance()->delete($groups->getArrayOfIds());

        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');

        Admin_Controller_Group::getInstance()->get($groups[0]->getId());
    }

    /**
     * testCustomFieldCreate
     *
     * @todo should create cf via Admin_Controller_Customfield
     */
    public function testCustomFieldCreate()
    {
        $cf = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => 'unittest_test',
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));

        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($cf->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);
    }

    /**
     * testCustomFieldDelete
     */
    public function testCustomFieldUpdate()
    {
        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        $result->name = 'changed name';
        $updatedCF = Admin_Controller_Customfield::getInstance()->update($result);
        static::assertEquals($result->name, $updatedCF->name);

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instanceSeq);
        static::assertEquals(2, $modifications->count(), 'no replication modifications found');

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('rollback did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getFirstRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getLastRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals($updatedCF->name, $lookupCf->name);
    }

    /**
     * testCustomFieldDelete
     */
    public function testCustomFieldDelete()
    {
        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        $deleted = Admin_Controller_Customfield::getInstance()->delete([$result->getId()]);

        $this->assertEquals(1, count($deleted));

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instanceSeq);
        static::assertEquals(2, $modifications->count(), 'no replication modifications found');

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('rollback did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getFirstRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getLastRecord()]));

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('delete replication did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    public function testFailedListCreation()
    {
        $controllerMock = Admin_Controller_GroupMock::getInstance();
        $result = null;
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = null;
        try {
            try {
                $controllerMock->create(new Tinebase_Model_Group([
                    'name' => __FUNCTION__
                ]));
                static::fail('exception expected');
            } catch (Exception $e) {
                static::assertEquals('kabum', $e->getMessage());
            }

            $result = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter([
                ['field' => 'name', 'operator' => 'equals', 'value' => __FUNCTION__]
            ]));
            static::assertEquals(0, $result->count());
        } finally {
            if (null !== $result && $result->count() > 0) {
                $oldPurge = Addressbook_Controller_List::getInstance()->purgeRecords(true);
                try {
                    Addressbook_Controller_List::getInstance()->delete($result);
                } finally {
                    Addressbook_Controller_List::getInstance()->purgeRecords($oldPurge);
                }
            }
        }
    }

    public function testRoleUpdateReplication()
    {
        $adminRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $exampleApplication = Tinebase_Application::getInstance()->getApplicationByName('ExampleApplication');

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        foreach ($members as &$member) {
            $member['id'] = $member['account_id'];
            $member['type'] = $member['account_type'];
            unset($member['role_id']);
            unset($member['account_id']);
            unset($member['account_type']);
        }
        $members[] = [
            'id'    => $userGroup->getId(),
            'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
        ];

        $appRights = new Tinebase_Record_RecordSet(Tinebase_Model_RoleRight::class,
            Tinebase_Acl_Roles::getInstance()->getRoleRights($adminRole->getId()), true);
        $exampleRight = $appRights->filter('application_id', $exampleApplication->getId())->filter('right', 'admin')
            ->getFirstRecord();
        static::assertNotNull($exampleRight);

        $appRights->removeRecord($exampleRight);
        $appRights = $appRights->toArray();
        $appRights[] = [
            'application_id' => $exampleApplication->getId(),
            'right'          => 'foo'
        ];

        // this will add a role member, remove a right, add a right in that order
        Admin_Controller_Role::getInstance()->update($adminRole, $members, $appRights);

        $appRights = new Tinebase_Record_RecordSet(Tinebase_Model_RoleRight::class,
            Tinebase_Acl_Roles::getInstance()->getRoleRights($adminRole->getId()), true);
        $exampleRight = $appRights->filter('application_id', $exampleApplication->getId())->filter('right', 'foo')
            ->getFirstRecord();
        $appRights->removeRecord($exampleRight);
        $appRights = $appRights->toArray();

        // this will remove a right
        Admin_Controller_Role::getInstance()->update($adminRole, $members, $appRights);


        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instance_seq);
        static::assertEquals(4, $modifications->count(), 'modifications count unexpected');

        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        // add a role member
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        static::assertEquals(1, count($members));
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        static::assertEquals(2, count($members));

        // remove a right
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(2, count($rights));
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(1, count($rights));

        // add a right
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(2, count($rights));

        // remove a right
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(1, count($rights));
    }
}
