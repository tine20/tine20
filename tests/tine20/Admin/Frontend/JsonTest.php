<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Admin json frontend
 */
class Admin_Frontend_JsonTest extends Admin_Frontend_TestCase
{
    /**
     * try to save group data
     * 
     * @return array
     */
    public function testAddGroup()
    {
        $result = $this->_createGroup();
        $this->assertEquals('initial group', $result['description']);
        return $result;
    }
    
    /**
     * try to get all groups
     *
     */
    public function testGetGroups()
    {
        $groups = $this->_json->getGroups(NULL, 'id', 'ASC', 0, 10);
        $this->assertGreaterThan(0, $groups['totalcount']);
    }

    /**
     * try to update group data
     */
    public function testUpdateGroup()
    {
        $data = $this->_createGroup();
        $data['description'] = 'updated group';

        // add group members array
        $userArray = $this->_createTestUser();
        $groupMembers = array($userArray['accountId']);
        $data['members'] = $groupMembers;

        $result = $this->_json->saveGroup($data);

        $this->assertGreaterThan(0,sizeof($result['members']));
        $this->assertEquals($data['description'], $result['description']);
        $this->assertEquals(Tinebase_Core::getUser()->accountId, $result['last_modified_by'], 'last_modified_by not matching');
    }

    /**
     * try to get group members
     */
    public function testGetGroupMembers()
    {
        $group = $this->_createGroup();
        
        // set group members
        $userArray = $this->_createTestUser();
        Tinebase_Group::getInstance()->setGroupMembers($group['id'], array($userArray['accountId']));
        
        // get group members with json
        $getGroupMembersArray = $this->_json->getGroupMembers($group['id']);
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($userArray['accountId']);
        
        $this->assertTrue(isset($getGroupMembersArray['results'][0]));
        $this->assertEquals($contact->n_fileas, $getGroupMembersArray['results'][0]['name']);
        $this->assertGreaterThan(0, $getGroupMembersArray['totalcount']);
    }
    
    /**
     * try to delete group
     *
     */
    public function testDeleteGroup()
    {
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        
        $group = $this->_createGroup();
        // delete group with json.php function
        $group = Tinebase_Group::getInstance()->getGroupByName($group['name']);
        $result = $this->_json->deleteGroups(array($group->getId()));
        
        $this->assertTrue($result['success']);
        
        // try to get deleted group
        $this->expectException('Tinebase_Exception_Record_NotDefined');
        
        // get group by name
        Tinebase_Group::getInstance()->getGroupByName($group['name']);
    }
    
    /**
     * try to get all access log entries
     */
    public function testGetAccessLogs()
    {
        $user = new Tinebase_Model_FullUser($this->_createTestUser());
        $this->_addAccessLog($user, 'Unittest');
        $accessLogs = $this->_json->searchAccessLogs($this->_getAccessLogFilter(), array());
      
        // check total count
        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
    }
    
    /**
     * add access log entry
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param String $_clienttype
     */
    protected function _addAccessLog($_user, $_clienttype)
    {
        Tinebase_AccessLog::getInstance()->create(new Tinebase_Model_AccessLog(array(
            'sessionid'     => 'test_session_id',
            'login_name'    => $_user->accountLoginName,
            'ip'            => '127.0.0.1',
            'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
            'result'        => Zend_Auth_Result::SUCCESS,
            'account_id'    => $_user->getId(),
            'clienttype'    => $_clienttype,
        )));
    }
    
    /**
     * get access log filter helper
     * 
     * @param string $_loginname
     * @param string $_clienttype
     * @return array
     */
    protected function _getAccessLogFilter($_loginname = NULL, $_clienttype = NULL)
    {
        $result = array(
            array(
                'field' => 'li', 
                'operator' => 'within', 
                'value' => 'dayThis'
            ),
        );
        
        if ($_loginname !== NULL) {
            $result[] = array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => $_loginname
            );
        }

        if ($_clienttype !== NULL) {
            $result[] = array(
                'field' => 'clienttype', 
                'operator' => 'equals', 
                'value' => $_clienttype
            );
        }
        
        return $result;
    }
    
    /**
     * try to get all access log entries
     */
    public function testGetAccessLogsWithDeletedUser()
    {
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        
        $clienttype = 'Unittest';
        $user = $this->_createTestUser();
        $this->_addAccessLog($user, $clienttype);

        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete($user['accountId']);
        $accessLogs = $this->_json->searchAccessLogs($this->_getAccessLogFilter($user['accountLoginName'], $clienttype), array());

        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
        $testLogEntry = $accessLogs['results'][0];
        $expectedDisplayName = $user->accountDisplayName;
        $this->assertEquals($expectedDisplayName, $testLogEntry['account_id']['accountDisplayName']);
        $this->assertEquals($clienttype, $testLogEntry['clienttype']);
        
        $this->_json->deleteAccessLogs(array($testLogEntry['id']));
    }
    
    /**
     * try to delete access log entries
     */
    public function testDeleteAccessLogs()
    {
        $accessLogs = $this->_json->searchAccessLogs($this->_getAccessLogFilter('tine20admin'), array());
        
        $deleteLogIds = array();
        foreach ($accessLogs['results'] as $log) {
            $deleteLogIds[] = $log['id'];
        }
        
        // delete logs
        if (!empty($deleteLogIds)) {
            $this->_json->deleteAccessLogs($deleteLogIds);
        }
        
        $accessLogs = $this->_json->searchAccessLogs($this->_getAccessLogFilter('tine20admin'), array());
        $this->assertEquals(0, sizeof($accessLogs['results']), 'results not matched');
        $this->assertEquals(0, $accessLogs['totalcount'], 'totalcount not matched');
    }
    
    /**
     * try to get an application
     */
    public function testGetApplication()
    {
        $application = $this->_json->getApplication(Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId());
        
        $this->assertEquals($application['status'], Tinebase_Application::getInstance()->getApplicationByName('Crm')->status);
        
    }

    /**
     * try to get applications
     */
    public function testGetApplications()
    {
        $applications = $this->_json->getApplications(NULL, NULL, 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $applications['totalcount']);
    }

    /**
     * try to set application state
     */
    public function testSetApplicationState()
    {
        $this->_json->setApplicationState(array(Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId()), 'disabled');
        
        $application = $this->_json->getApplication(Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId());

        $this->assertEquals($application['status'], 'disabled');

        // enable again
        $this->_json->setApplicationState(array(Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId()), 'enabled');
    }

    /**
     * try to add role and set members/rights
     */
    public function testAddRole()
    {
        // account to add as role member
        $user = $this->_createTestUser();
        $account = Tinebase_User::getInstance()->getUserById($user['accountId']);
        
        $roleData = $this->_getRole()->toArray();
        $roleMembers = array(
            array(
                "id"    => $account->getId(),
                "type"  => "user",
                "name"  => $account->accountDisplayName,
            )
        );
        $roleRights = array(
            array(
                "application_id"    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
                "right"  => Tinebase_Acl_Rights::RUN,
            )
        );
        
        $result = $this->_json->saveRole($roleData, $roleMembers, $roleRights);
        
        // get role id from result
        $roleId = $result['id'];
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->_getRole()->name);
        
        $this->assertEquals($role->getId(), $roleId);
        // check role members
        $result = $this->_json->getRoleMembers($role->getId());
        $this->assertGreaterThan(0, $result['totalcount']);
    }

    /**
     * try to get role rights
     */
    public function testGetRoleRights()
    {
        $this->testAddRole();
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->_getRole()->name);
        $rights = $this->_json->getRoleRights($role->getId());
        
        //print_r ($rights);
        $this->assertGreaterThan(0, $rights['totalcount']);
        $this->assertEquals(Tinebase_Acl_Rights::RUN, $rights['results'][0]['right']);
    }
    
    /**
     * try to save role
     */
    public function testUpdateRole()
    {
        $this->testAddRole();
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->_getRole()->name);
        $role->description = "updated description";
        $roleArray = $role->toArray();
        
        $result = $this->_json->saveRole($roleArray, array(),array());
        
        $this->assertEquals("updated description", $result['description']);
    }

    /**
     * try to get roles
     */
    public function testGetRoles()
    {
        $roles = $this->_json->getRoles(NULL, NULL, 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $roles['totalcount']);
    }
    
    /**
     * try to delete roles
     */
    public function testDeleteRoles()
    {
        $this->testAddRole();
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->_getRole()->name);
        
        $result = $this->_json->deleteRoles(array($role->getId()));
        
        $this->assertTrue($result['success']);
        
        // try to get it, shouldn't be found
        $this->expectException('Tinebase_Exception_NotFound');
        Tinebase_Acl_Roles::getInstance()->getRoleByName($this->_getRole()->name);
    }

    /**
     * try to get all role rights
     */
    public function testGetAllRoleRights()
    {
        $allRights = $this->_json->getAllRoleRights();
        
        $this->assertGreaterThan(0, $allRights);
        $this->assertTrue(isset($allRights[0]['text']));
        $this->assertTrue(isset($allRights[0]['application_id']));
        $this->assertGreaterThan(0, $allRights[0]['children']);
    }

    /**
    * try to get role rights for app
    * 
    * @see 0006374: if app has no own rights, tinebase rights are shown
    */
    public function testGetRoleRightsForActiveSyncAndTinebase()
    {
        $allRights = $this->_json->getAllRoleRights();
        
        $appRightsFound = NULL;
        $tinebaseRights = NULL;
        foreach ($allRights as $appRights) {
            if ($appRights['text'] === 'ActiveSync' || $appRights['text'] === 'Tinebase') {
                $appRightsFound[$appRights['text']] = array();
                foreach($appRights['children'] as $right) {
                    $appRightsFound[$appRights['text']][] = $right['right'];
                }
            }
        }
        
        $this->assertTrue(! empty($appRightsFound));
        
        $expectedTinebaseRights = array(
            'report_bugs',
            'check_version',
            'manage_own_profile',
            'manage_own_state'
        );
        
        $tinebaseRightsFound = array_intersect($appRightsFound['ActiveSync'], $expectedTinebaseRights);
        $this->assertEquals(0, count($tinebaseRightsFound), 'found Tinebase_Rights: ' . print_r($tinebaseRightsFound, TRUE));
        $tinebaseRightsFound = array_intersect($appRightsFound['Tinebase'], $expectedTinebaseRights);
        $this->assertEquals(4, count($tinebaseRightsFound), 'did not find Tinebase_Rights: ' . print_r($appRightsFound['Tinebase'], TRUE));
    }
    
    /**
     * testDeleteGroupBelongingToRole
     * 
     * @see 0007578: Deleting a group belonging to a role => can not use the role anymore !
     */
    public function testDeleteGroupBelongingToRole()
    {
        $group = $this->testAddGroup();
        $roleData = $this->_getRole()->toArray();
        $roleMembers = array(
            array(
                "id"    => $group['id'],
                "type"  => "group",
            )
        );
        
        $result = $this->_json->saveRole($roleData, $roleMembers, array());
        $this->_json->deleteGroups(array($group['id']));
        
        $role = $this->_json->getRole($result['id']);
        
        $this->assertEquals(0, $role['roleMembers']['totalcount'], 'role members should be empty: ' . print_r($role['roleMembers'], TRUE));
    }
    
    /**
     * try to save tag and update without rights
     *
     * @return array
     */
    public function testSaveTagAndUpdateWithoutRights()
    {
        $tagData = $this->_getTagData();
        $this->objects['tag'] = $this->_json->saveTag($tagData);
        $this->assertEquals($tagData['name'], $this->objects['tag']['name']);
        
        $this->objects['tag']['rights'] = array();
        $result = $this->_json->saveTag($this->objects['tag']);
        self::assertEquals(0, count($result['rights']));
        return $result;
    }

    /**
     * try to save tag without view right
     */
    public function testSaveTagWithoutViewRight()
    {
        $tagData = $this->_getTagData();
        $tagData['rights'] = array(array(
            'account_id' => 0,
            'account_type' => 'anyone',
            'account_name' => 'Anyone',
            'view_right' => false,
            'use_right' => false
        ));
        $result = $this->_json->saveTag($tagData);
        self::assertEquals(0, count($result['rights']));
    }

    /**
     * try to save tag and update without rights
     */
    public function testDeleteTagWithoutRights()
    {
        $tag = $this->testSaveTagAndUpdateWithoutRights();
        $result = $this->_json->deleteTags([$tag['id']]);
        self::assertEquals('success', $result['status']);
    }

    /**
     * get tag data
     * 
     * @return array
     */
    protected function _getTagData()
    {
        return array(
            'rights' => array(
                array(
                    'account_id' => 0,
                    'account_type' => 'anyone',
                    'account_name' => 'Anyone',
                    'view_right' => true,
                    'use_right' => true
                )
            ),
            'contexts' => array('any'),
            'name' => 'supertag',
            'description' => 'xxxx',
            'color' => '#003300'
        );
    }
    
    /**
     * testSaveTagWithoutAnyone
     * 
     * @see 0009934: can't save shared tag with anyoneAccountDisabled
     */
    public function testSaveTagWithoutAnyone()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ANYONE_ACCOUNT_DISABLED, true);
        
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $tagData = $this->_getTagData();
        $tagData['rights'] = array(array(
            'account_id' => $defaultUserGroup->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            'view_right' => true,
            'use_right' => true
        ));
        $this->objects['tag'] = $this->_json->saveTag($tagData);
        
        $this->assertEquals('supertag', $this->objects['tag']['name']);
        $this->assertEquals(1, count($this->objects['tag']['rights']));
    }

    public function testSearchTagWithoutViewRight()
    {
        $tagData = $this->_getTagData();
        $tagData['rights'] = array(array(
            'account_id' => $this->_personas['sclever']->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'view_right' => true,
            'use_right' => true
        ));
        $this->objects['tag'] = $this->_json->saveTag($tagData);
        self::assertEquals('supertag', $this->objects['tag']['name']);
        self::assertEquals(1, count($this->objects['tag']['rights']));

        $result = $this->_json->getTags('supertag', 'name', 'ASC', 0, 10);
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        self::assertEquals('supertag', $result['results'][0]['name'], print_r($result, true));
    }

    /**
     * test searchContainers
     */
    public function testSearchContainers()
    {
        $personalAdb = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();
        Tinebase_Container::getInstance()->resetClassCache();
        
        $addressbook = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $filter = array(
            array('field' => 'application_id',  'operator' => 'equals',     'value' => $addressbook->getId()),
            array('field' => 'type',            'operator' => 'equals',     'value' => Tinebase_Model_Container::TYPE_PERSONAL),
            array('field' => 'name',            'operator' => 'contains',   'value' => Tinebase_Core::getUser()->accountFirstName),
        );
        
        $result = $this->_json->searchContainers($filter, array());
        
        $this->assertGreaterThan(0, $result['totalcount']);
        $this->assertEquals(3, count($result['filter']));
        
        $found = FALSE;
        foreach ($result['results'] as $container) {
            if ($container['id'] === $personalAdb->getId()) {
                $found = TRUE;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * test saveUpdateDeleteContainer
     */
    public function testSaveUpdateDeleteContainer()
    {
        $container = $this->_saveContainer();
        static::assertEquals(Tinebase_Core::getUser()->getId(), $container['created_by']);
        
        // update container
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $container['name'] = 'testcontainerupdated';
        $container['account_grants'] = $this->_getContainerGrants();
        
        $containerUpdated = $this->_json->saveContainer($container);
        static::assertEquals('testcontainerupdated', $containerUpdated['name']);
        static::assertTrue($containerUpdated['account_grants'][0][Tinebase_Model_Grants::GRANT_ADMIN]);
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instance_seq);
        static::assertEquals(3, $modifications->count(), 'modification count does not match: '
            . print_r($modifications->toArray(), true));
        $firstModification = new Tinebase_Record_Diff(json_decode($modifications->getFirstRecord()->new_value, true));
        static::assertTrue(isset($firstModification->diff['account_grants']), 'expect account_grants to be set');
        static::assertTrue(isset($firstModification->diff['seq']), 'expect seq to be set');
        static::assertEquals(2, count($firstModification->diff), 'expect only account_grants to be set');
        $secondModification = new Tinebase_Record_Diff(json_decode($modifications->getLastRecord()->new_value,
            true));
        static::assertTrue(isset($secondModification->diff['name']), 'expect name to be set');
        static::assertEquals(3, count($secondModification->diff));

        // check history of updated container
        $tfj = new Tinebase_Frontend_Json();
        $filter = array(array(
            'field' => 'record_id',
            'operator' => 'equals',
            'value' => $containerUpdated['id']
        ), array(
            'field' => "record_model",
            'operator' => "equals",
            'value' => 'Tinebase_Model_Container'
        ));
        $sort = array(
            'sort' => array('note_type_id', 'creation_time')
        );
        $history = $tfj->searchNotes($filter, $sort);
        $this->assertEquals(3, $history['totalcount'], print_r($history, TRUE));

        // change container via Tinebase_Frontend_Json_Container -> should also write a note
        $tfjc = new Tinebase_Frontend_Json_Container();
        $tfjc->renameContainer($containerUpdated['id'], 'testcontainerupdatedAgain');
        $history = $tfj->searchNotes($filter, $sort);
        $this->assertEquals(4, $history['totalcount'], print_r($history, TRUE));

        $deleteResult = $this->_json->deleteContainers(array($container['id']));
        static::assertEquals('success', $deleteResult['status']);
    }
    
    /**
     * try to change container app
     */
    public function testChangeContainerApp()
    {
        $container = $this->_saveContainer();
        
        $container['application_id'] = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
        $this->expectException('Tinebase_Exception_Record_NotAllowed');
        $this->_json->saveContainer($container);
    }
    
    /**
     * testContainerNotification
     */
    public function testContainerNotification()
    {
        // prepare smtp transport
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (empty($smtpConfig)) {
             $this->markTestSkipped('No SMTP config found: this is needed to send notifications.');
        }
        $mailer = Tinebase_Smtp::getDefaultTransport();
        // make sure all messages are sent if queue is activated
        $queueConfig = Tinebase_Config::getInstance()->{Tinebase_Config::ACTIONQUEUE};
        if ($queueConfig->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }
        $mailer->flush();
        
        // create and update container
        $container = $this->_saveContainer();
        $container['type'] = Tinebase_Model_Container::TYPE_PERSONAL;
        $container['note'] = 'changed to personal';
        $container['account_grants'] = $this->_getContainerGrants();
        $this->_json->saveContainer($container);
        
        // make sure messages are sent if queue is activated
        if ($queueConfig->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }

        // check notification message
        $messages = $mailer->getMessages();
        $this->assertGreaterThan(0, count($messages));
        $notification = $messages[0];
        
        $translate = Tinebase_Translation::getTranslation('Admin');
        $body = quoted_printable_decode($notification->getBodyText(TRUE));
        $this->assertStringContainsString($container['note'],  $body, $body);
        
        $subject = $notification->getSubject();
        if (strpos($subject, 'UTF-8') !== FALSE) {
            $this->assertEquals(iconv_mime_encode('Subject', $translate->_('Your container has been changed'), array(
                'scheme'        => 'Q',
                'line-length'   => 500,
            )), 'Subject: ' . $subject);
        } else {
            $this->assertEquals($translate->_('Your container has been changed'), $subject);
        }
        $this->assertTrue(in_array(Tinebase_Core::getUser()->accountEmailAddress, $notification->getRecipients()));
    }
    
    /**
     * testContainerCheckOwner
     */
    public function testContainerCheckOwner()
    {
        $container = $this->_saveContainer(Tinebase_Model_Container::TYPE_PERSONAL);
        
        $personas = Zend_Registry::get('personas');
        $container['account_grants'] = $this->_getContainerGrants();
        $container['account_grants'][] = array(
            'account_id'     => $personas['jsmith']->getId(),
            'account_type'   => 'user',
            Tinebase_Model_Grants::GRANT_ADMIN     => true
        );
        $this->expectException(Tinebase_Exception_SystemGeneric::class);
        $this->_json->saveContainer($container);
    }

    /**
     * test create container with bad xprops
     */
    public function testCreateContainerBadXprops()
    {
        static::expectException(Tinebase_Exception_Record_Validation::class);
        $this->_json->saveContainer(array(
            "type" => Tinebase_Model_Container::TYPE_SHARED,
            "backend" => "Sql",
            "name" => "asdfgsadfg",
            "color" => "#008080",
            "application_id" => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            "model" => Addressbook_Model_Contact::class,
            "note" => "",
            'xprops' => '{a":"b"}',
        ));
    }

    /**
     * test create container with bad xprops
     */
    public function testUpdateContainerBadXprops()
    {
        $container = $this->_json->saveContainer(array(
            "type" => Tinebase_Model_Container::TYPE_SHARED,
            "backend" => "Sql",
            "name" => "asdfgsadfg",
            "color" => "#008080",
            "application_id" => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            "model" => Addressbook_Model_Contact::class,
            "note" => "",
        ));

        static::expectException(Tinebase_Exception_Record_Validation::class);
        $container['xprops'] = '{a":"b"}';
        $this->_json->saveContainer($container);
    }

    /**
     * test create container
     */
    public function testCreateContainerAndDeleteContents()
    {
        $container = $this->_json->saveContainer(array(
            "type" => Tinebase_Model_Container::TYPE_SHARED,
            "backend" => "Sql",
            "name" => "asdfgsadfg",
            "color" => "#008080",
            "application_id" => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            "model" => Addressbook_Model_Contact::class,
            "note" => "",
            'xprops' => '{"a":"b"}',
        ));
        // check if the model was set
        static::assertEquals($container['model'], 'Addressbook_Model_Contact');
        static::assertEquals($container['xprops'], ['a' => 'b']);
        $contact = new Addressbook_Model_Contact(array('n_given' => 'max', 'n_family' => 'musterman', 'container_id' => $container['id']));
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        $this->_json->deleteContainers(array($container['id']));
        
        $cb = new Addressbook_Backend_Sql();
        $del = $cb->get($contact->getId(), true);
        // record should be deleted
        $this->assertEquals($del->is_deleted, 1);
        
        try {
            Addressbook_Controller_Contact::getInstance()->get($contact->getId(), $container['id']);
            $this->fail('The expected exception was not thrown');
        } catch (Tinebase_Exception_NotFound $e) {
            // ok;
        }
        // record should not be found
        $this->assertEquals($e->getMessage(), 'Addressbook_Model_Contact record with id = ' . $contact->getId().' not found!');
    }

    /**
     * test create container with model
     * 0013120: Via Admin-App created calendar-container have a wrong model
     */
    public function testCreateContainerWithModel()
    {
        $container = $this->_json->saveContainer(array(
            "type" => Tinebase_Model_Container::TYPE_SHARED,
            "backend" => "Sql",
            "name" => "testtest",
            "color" => "#008080",
            "application_id" => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            "model" => "Tine.Calendar.Model.Event",
            "note" => ""
        ));

        // check if the model was set
        $this->assertEquals($container['model'], 'Calendar_Model_Event');
    }
    
    /**
     * saves and returns container
     * 
     * @param string $_type
     * @return array
     */
    protected function _saveContainer($_type = Tinebase_Model_Container::TYPE_SHARED)
    {
        $data = array(
            'name'              => 'testcontainer',
            'type'              => $_type,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => Addressbook_Model_Contact::class,
        );
        
        $container = $this->_json->saveContainer($data);
        $this->objects['container'] = $container['id'];
        
        $this->assertEquals($data['name'], $container['name']);
        
        return $container;
    }
    
    /**
     * get container grants
     * 
     * @return array
     */
    protected function _getContainerGrants()
    {
        return array(array(
            'account_id'     => Tinebase_Core::getUser()->getId(),
            'account_type'   => 'user',
            Tinebase_Model_Grants::GRANT_READ      => true,
            Tinebase_Model_Grants::GRANT_ADD       => true,
            Tinebase_Model_Grants::GRANT_EDIT      => true,
            Tinebase_Model_Grants::GRANT_DELETE    => false,
            Tinebase_Model_Grants::GRANT_ADMIN     => true
        ));
    }

    /**
     * testUpdateGroupMembershipAndContainerGrants
     * 
     * @see 0007150: container grants are not updated if group memberships change
     */
    public function testUpdateGroupMembershipAndContainerGrants()
    {
        $container = $this->_saveContainer();
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $container['account_grants'] = array(array(
            'account_id'     => $adminGroup->getId(),
            'account_type'   => 'group',
            Tinebase_Model_Grants::GRANT_READ      => true,
            Tinebase_Model_Grants::GRANT_ADD       => true,
            Tinebase_Model_Grants::GRANT_EDIT      => true,
            Tinebase_Model_Grants::GRANT_DELETE    => false,
            Tinebase_Model_Grants::GRANT_ADMIN     => true
        ));
        $containerUpdated = $this->_json->saveContainer($container);
        
        $userArray = $this->_createTestUser();
        Tinebase_Group::getInstance()->setGroupMembers($adminGroup->getId(), array($userArray['accountId']));
        
        $containers = Tinebase_Container::getInstance()->getContainerByACL($userArray['accountId'], Addressbook_Model_Contact::class, Tinebase_Model_Grants::GRANT_ADD);
        $this->assertTrue(count($containers->filter('name', 'testcontainer')) === 1, 'testcontainer ' . print_r($containerUpdated, TRUE) . ' not found: ' . print_r($containers->toArray(), TRUE));
    }
    
    /**
     * testPhpinfo
     * 
     * @see 0007182: add "server info" section to admin
     */
    public function testPhpinfo()
    {
        $info = $this->_json->getServerInfo();
        self::assertArrayHasKey('html', $info);
        self::assertStringContainsString("phpinfo()", $info['html']);
        self::assertStringContainsString("PHP Version =>", $info['html']);
    }

    protected function createExampleAppRecord()
    {
        if (!Tinebase_Application::getInstance()->isInstalled('ExampleApplication')) {
            self::markTestSkipped('Test needs ExampleApplication');
        }

        return ExampleApplication_Controller_ExampleRecord::getInstance()->create(
            new ExampleApplication_Model_ExampleRecord([
                'name' => Tinebase_Record_Abstract::generateUID(),
            ]));
    }

    protected function prepareExampleAppConfig()
    {
        $record = $this->createExampleAppRecord();
        ExampleApplication_Config::getInstance()->{ExampleApplication_Config::EXAMPLE_RECORD} = $record->getId();
        return $record;
    }

    public function testSearchConfigsRecord()
    {
        $initialRecord = $this->prepareExampleAppConfig();

        $result = $this->_json->searchConfigs([
            'application_id' => Tinebase_Application::getInstance()
                ->getApplicationByName(ExampleApplication_Config::APP_NAME)->getId()
        ], []);

        $this->assertGreaterThanOrEqual(2, $result['totalcount']);

        $exampleRecord = null;
        foreach($result['results'] as $configData) {
            if ($configData['name'] === ExampleApplication_Config::EXAMPLE_RECORD) {
                $exampleRecord = $configData;
                break;
            }
        }

        static::assertNotNull($exampleRecord);
        static::assertStringContainsString($initialRecord->name, $exampleRecord['value']);

        return $exampleRecord;
    }

    public function testGetConfigRecord()
    {
        $exampleRecord = $this->testSearchConfigsRecord();

        $fetchedExampleRecord = $this->_json->getConfig($exampleRecord['id']);

        static::assertEquals($exampleRecord['value'], $fetchedExampleRecord['value']);
    }

    public function testUpdateConfigRecord()
    {
        $exampleRecord = $this->testSearchConfigsRecord();
        $newExampleRecord = $this->createExampleAppRecord();
        $exampleRecord['value'] = json_encode($newExampleRecord->toArray());

        $result = $this->_json->saveConfig($exampleRecord);
        self::assertStringContainsString($newExampleRecord->name, $result['value']);

        // try to save null value
        $result['value'] = null;
        $result = $this->_json->saveConfig($result);
        // it seems that we convert config to string "null" if null
        self::assertEquals('null', $result['value'], print_r($result, true));
        self::assertNull(ExampleApplication_Config::getInstance()->get($result['name']));
    }

    public function testSearchConfigs()
    {
        $result = $this->_json->searchConfigs(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId()
        ), array());

        $this->assertGreaterThanOrEqual(2, $result['totalcount']);

        $attendeeRoles = NULL;
        foreach($result['results'] as $configData) {
            if ($configData['name'] == 'attendeeRoles') {
                $attendeeRoles = $configData;
                break;
            }
        }

        $this->assertNotNull($attendeeRoles);
        $this->assertContains('{', $attendeeRoles);

        return $attendeeRoles;
    }

    public function testGetConfig()
    {
        $attendeeRoles = $this->testUpdateConfig();

        $fetchedAttendeeRoles = $this->_json->getConfig($attendeeRoles['id']);

        $this->assertEquals($attendeeRoles['value'], $fetchedAttendeeRoles['value']);
    }

    public function testUpdateConfig()
    {
        $attendeeRoles = $this->testSearchConfigs();

        $keyFieldConfig = json_decode($attendeeRoles['value'], true);
        $keyFieldConfig['records'][] = array(
            'id'    => 'CHAIR',
            'value' => 'Chair'
        );
        $attendeeRoles['value'] = json_encode($keyFieldConfig);
        if ($attendeeRoles['id'] === 'virtual-attendeeRoles') {
            $attendeeRoles['id'] = '';
        }

        $this->_json->saveConfig($attendeeRoles);

        $updatedAttendeeRoles = $this->testSearchConfigs();
        $this->assertEquals($attendeeRoles['value'], $updatedAttendeeRoles['value']);

        $keyFieldConfig = json_decode($attendeeRoles['value'], true);
        $keyFieldConfig['records'][] = array(
            'id'    => 'COCHAIR',
            'value' => 'Co-Chair'
        );
        $updatedAttendeeRoles['value'] = json_encode($keyFieldConfig);
        $this->_json->saveConfig($updatedAttendeeRoles);

        $reUpdatedAttendeeRoles = $this->testSearchConfigs();
        $this->assertEquals($updatedAttendeeRoles['value'], $reUpdatedAttendeeRoles['value']);

        return $updatedAttendeeRoles;
    }

    /**
     * @see 0011504: deactivated user is removed from group when group is saved
     */
    public function testDeactivatedUserGroupSave()
    {
        $this->_skipIfLDAPBackend('FIXME: Zend_Ldap_Exception: 0x44 (Already exists): adding: cn=tine20phpunitgroup,ou=groups,...');

        // deactivate user
        $userArray = $this->_createTestUser();

        Admin_Controller_User::getInstance()->setAccountStatus($userArray['accountId'], Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);
        $savedGroup = $this->_saveGroup($userArray);

        // check group memberships
        $this->assertEquals(1, $savedGroup['members']['totalcount']);
    }

    protected function _saveGroup($userArray, $additionalData = [])
    {
        try {
            $group = Tinebase_Group::getInstance()->getGroupByName('tine20phpunitgroup');
            $groupArray = $this->_json->getGroup($group->getId());
            $this->assertEquals(1, $groupArray['members']['totalcount']);
        } catch (Tinebase_Exception_Record_NotDefined $ternd) {
            $groupArray = $this->_createGroup();
        }
        $groupArray['container_id'] = $groupArray['container_id']['id'];
        $groupArray['members'] = array($userArray['accountId']);
        $groupArray = array_merge($groupArray, $additionalData);
        return $this->_json->saveGroup($groupArray);
    }

    /**
     * @see 0011504: deactivated user is removed from group when group is saved
     */
    public function testBlockedUserGroupSave()
    {
        $this->_skipIfLDAPBackend('FIXME: Zend_Ldap_Exception: 0x44 (Already exists): adding: cn=tine20phpunitgroup,ou=groups,...');

        // deactivate user
        $userArray = $this->_createTestUser();
        $userArray['lastLoginFailure'] = Tinebase_DateTime::now()->toString();
        $userArray['loginFailures'] = 10;

        $savedGroup = $this->_saveGroup($userArray);

        // check group memberships
        $this->assertEquals(1, $savedGroup['members']['totalcount']);
    }

    public function testAccountOnlyGroup()
    {
        $this->_skipIfLDAPBackend('FIXME: Zend_Ldap_Exception: 0x44 (Already exists): adding: cn=tine20phpunitgroup,ou=groups,...');

        $userArray = $this->_createTestUser();
        $savedGroup = $this->_saveGroup($userArray, ['account_only' => 0]);
        self::assertEquals('0', $savedGroup['account_only']);
        $savedGroup =$this->_saveGroup($userArray, ['account_only' => '']);
        self::assertEquals('0', $savedGroup['account_only']);
        $savedGroup =$this->_saveGroup($userArray, ['account_only' => false]);
        self::assertEquals('0', $savedGroup['account_only']);
    }

    public function testSearchQuotaNodes()
    {
        $filterNullResult = $this->_json->searchQuotaNodes();
        $filterRootResult = $this->_json->searchQuotaNodes(array(array(
            'field'     => 'path',
            'operator'  => 'equals',
            'value'     => '/'
        )));

        static::assertEquals($filterNullResult['totalcount'], $filterRootResult['totalcount']);
        static::assertGreaterThan(0, $filterNullResult['totalcount']);
        foreach ($filterNullResult['results'] as $node) {
            Tinebase_Application::getInstance()->getApplicationById($node['name']);
        }

        $filterAppResult = $this->_json->searchQuotaNodes(array(array(
            'field'     => 'path',
            'operator'  => 'equals',
            'value'     => '/' . Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId()
        )));

        static::assertEquals(1, $filterAppResult['totalcount']);
        static::assertEquals('folders', $filterAppResult['results'][0]['name']);

        $filterAppResult = $this->_json->searchQuotaNodes(array(array(
            'field'     => 'path',
            'operator'  => 'equals',
            'value'     => '/' . Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId()
        )));

        $imapBackend = null;
        try {
            $imapBackend = Tinebase_EmailUser::getInstance();
        } catch (Tinebase_Exception_NotFound $tenf) {}
        if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
            static::assertEquals(2, $filterAppResult['totalcount']);
            static::assertEquals('Emails', $filterAppResult['results'][1]['name']);

            $dovecotResult = $this->_json->searchQuotaNodes(array(array(
                'field'     => 'path',
                'operator'  => 'equals',
                'value'     => '/' . Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId() .
                    '/Emails'
            )));
            static::assertGreaterThanOrEqual(1, $dovecotResult['totalcount']);

            $domains = array_unique(array_merge(
                Tinebase_EmailUser::getAllowedDomains(Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP)),
                $imapBackend->getAllDomains()
            ));
            foreach ($dovecotResult['results'] as $result) {
                static::assertTrue(false !== ($idx = array_search($result['name'], $domains)), 'unknown or duplicate domain');
                unset($domains[$idx]);
            }

            $dovecotResult = $this->_json->searchQuotaNodes(array(array(
                'field'     => 'path',
                'operator'  => 'equals',
                'value'     => '/' . Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId() .
                    '/Emails/' . $dovecotResult['results'][0]['name']
            )));
            static::assertGreaterThanOrEqual(1, $dovecotResult['totalcount']);

        } else {
            static::assertEquals(1, $filterAppResult['totalcount']);
        }
        static::assertEquals('folders', $filterAppResult['results'][0]['name']);
    }

    public function testResourceContainerGet()
    {
        $resource = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource([
            'name'                 => 'Meeting Room',
            'description'          => 'Our main meeting room',
            'email'                => 'room@example.com',
            'is_location'          => TRUE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ]]
        ]));

        $container = $this->_json->getContainer($resource->container_id);
        static::assertTrue(isset($container['xprops']['Calendar']['Resource']['resource_id']) &&
            $container['xprops']['Calendar']['Resource']['resource_id'] === $resource->getId(), 'xprops not set');
        static::assertTrue(isset($container['xprops']['Tinebase']['Container']['GrantsModel']) &&
            $container['xprops']['Tinebase']['Container']['GrantsModel'] === Calendar_Model_ResourceGrants::class,
            'xprops not set');

        return $container;
    }

    public function testResourceContainerUpdate()
    {
        $oldContainer = $this->testResourceContainerGet();
        $oldAcl = Tinebase_Container::getInstance()->getGrantsOfContainer($oldContainer['id'], true);

        $newContainer = $oldContainer;
        $newContainer['name'] = 'newName';
        $newContainer = $this->_json->saveContainer($newContainer);
        static::assertEquals('newName', $newContainer['name']);

        $newAcl = Tinebase_Container::getInstance()->getGrantsOfContainer($oldContainer['id'], true);
        $diff = $newAcl->diff($oldAcl);

        static::assertTrue($diff->isEmpty(), 'acl changed where they shouldn\'t: ' . print_r($diff->toArray(), true));
    }
}
