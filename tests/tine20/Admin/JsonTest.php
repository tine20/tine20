<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Admin json frontend
 */
class Admin_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Json
     */
    protected $_json;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin Json Tests');
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
        
        $this->_json = new Admin_Frontend_Json();
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        ));
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'updated group'
        ));
        
        $this->objects['user'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));
        
        if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
            $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);

            $this->objects['initialGroup']->container_id = $internalAddressbook->getId();
            $this->objects['updatedGroup']->container_id = $internalAddressbook->getId();
            $this->objects['user']->container_id = $internalAddressbook->getId();
        }

        $this->objects['application'] = Tinebase_Application::getInstance()->getApplicationByName('Crm');
       
        $this->objects['role'] = new Tinebase_Model_Role(array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
        
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        // add account for group / role member tests
        $this->objects['user'] = Admin_Controller_User::getInstance()->create($this->objects['user'], 'lars', 'lars');
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
     * try to get all accounts
     */
    public function testGetAccounts()
    {
        $accounts = $this->_json->getUsers('PHPUnit', 'accountDisplayName', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $accounts['totalcount']);
    }
    
    /**
     * testGetUserCount
     * 
     * @see 0006544: fix paging in admin/users grid
     */
    public function testGetUserCount()
    {
        $this->testSetAccountState();
        $accounts = $this->_json->getUsers('PHPUnit', 'accountDisplayName', 'ASC', 0, 100);
        $this->assertEquals(count($accounts['results']), $accounts['totalcount'], print_r($accounts['results'], TRUE));
    }
    
    /**
     * get account that doesn't exist (by id)
     */
    public function testGetNonExistentAccountById()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $id = 12334567;
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $user = Tinebase_User::getInstance()->getUserById($id);
    }

    /**
     * get account that doesn't exist (by login name)
     */
    public function testGetNonExistentAccountByLoginName()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $loginName = 'something';
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $user = Tinebase_User::getInstance()->getUserByLoginName($loginName);
    }
    
    /**
     * try to save group data
     * 
     * @return array
     */
    public function testAddGroup()
    {
        $result = $this->_json->saveGroup($this->objects['initialGroup']->toArray(), array());
        
        $this->assertEquals($this->objects['initialGroup']->description, $result['description']);
        
        return $result;
    }
    
    /**
     * try to save an account
     * 
     * @return array
     */
    public function testSaveAccount()
    {
        $this->testAddGroup();
        
        $accountData = $this->objects['user']->toArray();
        $accountData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getGroupByName('tine20phpunit')->getId();
        $accountData['accountPassword'] = 'test';
        $accountData['accountPassword2'] = 'test';
        $accountData['accountFirstName'] = 'PHPUnitup';
        
        $account = $this->_json->saveUser($accountData);
        
        $this->assertTrue(is_array($account));
        $this->assertEquals('PHPUnitup', $account['accountFirstName']);
        $this->assertEquals(Tinebase_Group::getInstance()->getGroupByName('tine20phpunit')->getId(), $account['accountPrimaryGroup']['id']);
        // check password
        $authResult = Tinebase_Auth::getInstance()->authenticate($account['accountLoginName'], 'test');
        $this->assertTrue($authResult->isValid());
        
        return $account;
    }

    /**
     * try to create an account with existing login name 
     * 
     * @return array
     * 
     * @see 0006770: check if username already exists when creating new user / changing username
     */
    public function testSaveAccountWithExistingName()
    {
        $accountData = $this->objects['user']->toArray();
        unset($accountData['accountId']);
        
        try {
            $account = $this->_json->saveUser($accountData);
            $this->fail('Creating an account with existing login name should throw exception: ' . print_r($account, TRUE));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }
        
        $this->assertEquals('Login name already exists. Please choose another one.', $tesg->getMessage());
        
        $accountData = $this->objects['user']->toArray();
        $accountData['accountId'] = $this->objects['user']->getId();
        $accountData['accountLoginName'] = Tinebase_Core::getUser()->accountLoginName;
        
        try {
            $account = $this->_json->saveUser($accountData);
            $this->fail('Updating an account with existing login name should throw exception: ' . print_r($account, TRUE));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }
        
        $this->assertEquals('Login name already exists. Please choose another one.', $tesg->getMessage());
    }
    
    /**
     * try to save a hidden account
     */
    public function testSaveHiddenAccount()
    {
        $accountData = $this->objects['user']->toArray();
        $accountData['visibility'] = Tinebase_Model_User::VISIBILITY_HIDDEN;
        $accountData['container_id'] = 0;
        
        $account = $this->_json->saveUser($accountData);
        
        $this->assertTrue(is_array($account));
        $this->assertTrue(! empty($account['contact_id']));
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        $this->assertEquals($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK], $account['container_id']['id']);
    }    
    
    /**
     * testUpdateUserWithoutContainerACL
     * 
     * @see 0006254: edit/create user is not possible
     */
    public function testUpdateUserWithoutContainerACL()
    {
        $account = $this->testSaveAccount();
        $internalContainer = Tinebase_Container::getInstance()->get($account['container_id']['id']);
        Tinebase_Container::getInstance()->setGrants($internalContainer, new Tinebase_Record_RecordSet('Tinebase_Model_Grants'), TRUE, FALSE);
        
        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account['groups'] = array($account['accountPrimaryGroup'], Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId());
        $account['container_id'] = $internalContainer->getId();
        $account = $this->_json->saveUser($account);
        
        $this->assertEquals(2, $account['groups']['totalcount']);
    }
    
    /**
     * testUpdateUserRemoveGroup
     * 
     * @see 0006762: user still in admin role when admin group is removed
     */
    public function testUpdateUserRemoveGroup()
    {
        $account = $this->testSaveAccount();
        $internalContainer = Tinebase_Container::getInstance()->get($account['container_id']['id']);
        Tinebase_Container::getInstance()->setGrants($internalContainer, new Tinebase_Record_RecordSet('Tinebase_Model_Grants'), TRUE, FALSE);
        
        $adminGroupId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account['groups'] = array($account['accountPrimaryGroup'], $adminGroupId);
        $account['container_id'] = $account['container_id']['id'];
        $account = $this->_json->saveUser($account);
        
        $roles = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account['accountId']);
        $adminRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $this->assertEquals(array($adminRole->getId()), $roles);

        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account['groups'] = array($account['accountPrimaryGroup']);
        
        if (is_array($account['container_id']) && is_array($account['container_id']['id']))
        {
            $account['container_id'] = $account['container_id']['id'];
        }
        
        $account = $this->_json->saveUser($account);
        
        
        $roles = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account['accountId']);
        $this->assertEquals(array(), $roles);
    }

    /**
     * testUpdateUserRemovedPrimaryGroup
     * 
     * @see 0006710: save user fails if primary group no longer exists
     */
    public function testUpdateUserRemovedPrimaryGroup()
    {
        $this->testAddGroup();
        
        $accountData = $this->objects['user']->toArray();
        $accountData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getGroupByName('tine20phpunit')->getId();
        
        Admin_Controller_Group::getInstance()->delete(array($accountData['accountPrimaryGroup']));
        
        $savedAccount = $this->_json->saveUser($accountData);
        $this->assertEquals(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $savedAccount['accountPrimaryGroup']['id']);
    }
    
    /**
     * try to delete accounts 
     */
    public function testDeleteAccounts()
    {
        Admin_Controller_User::getInstance()->delete($this->objects['user']->accountId);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserById($this->objects['user']->getId());
    }

    /**
     * try to set account state
     */
    public function testSetAccountState()
    {
        $this->_json->setAccountState(array($this->objects['user']->getId()), 'disabled');
        
        $account = Tinebase_User::getInstance()->getFullUserById($this->objects['user']);
        
        $this->assertEquals('disabled', $account->accountStatus);
    }
    
    /**
     * try to reset password
     */
    public function testResetPassword()
    {
        $this->_json->resetPassword($this->objects['user']->toArray(), 'password', FALSE);
        
        $authResult = Tinebase_Auth::getInstance()->authenticate($this->objects['user']->accountLoginName, 'password');
        $this->assertTrue($authResult->isValid());
    }
    
    /**
     * testAccountContactModlog
     * 
     * @see 0006688: contact of new user should have modlog information
     */
    public function testAccountContactModlog()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->objects['user']->getId());
        
        $this->assertTrue(! empty($contact->creation_time));
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contact->created_by);
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
        $this->testAddGroup();
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        // set data array
        $data = $this->objects['updatedGroup']->toArray();
        $data['id'] = $group->getId();
        
        // add group members array
        $groupMembers = array($this->objects['user']->accountId);
        
        $result = $this->_json->saveGroup($data, $groupMembers);

        $this->assertGreaterThan(0,sizeof($result['groupMembers']));
        $this->assertEquals($this->objects['updatedGroup']->description, $result['description']);
    }    

    /**
     * try to get group members
     */
    public function testGetGroupMembers()
    {
        $this->testAddGroup();
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['updatedGroup']->name);

        // set group members
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), array($this->objects['user']->accountId));
        
        // get group members with json
        $getGroupMembersArray = $this->_json->getGroupMembers($group->getId());
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->objects['user']->accountId);
        
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
        $this->testAddGroup();
        // delete group with json.php function
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        $result = $this->_json->deleteGroups(array($group->getId()));
        
        $this->assertTrue($result['success']);
        
        // try to get deleted group
        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');
        
        // get group by name
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
    }
    
    /**
     * try to get all access log entries
     */
    public function testGetAccessLogs()
    {
        $this->_addAccessLog($this->objects['user'], 'Unittest');
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
        $clienttype = 'Unittest';
        $user = $this->objects['user'];
        $this->_addAccessLog($user, $clienttype);
        
        Admin_Controller_User::getInstance()->delete($user->getId());
        $accessLogs = $this->_json->searchAccessLogs($this->_getAccessLogFilter($user->accountLoginName, $clienttype), array());

        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
        $testLogEntry = $accessLogs['results'][0];
        $this->assertEquals(Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName, $testLogEntry['account_id']['accountDisplayName']);
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
        $application = $this->_json->getApplication($this->objects['application']->getId());
        
        $this->assertEquals($application['status'], $this->objects['application']->status);
        
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
        $this->_json->setApplicationState(array($this->objects['application']->getId()), 'disabled');
        
        $application = $this->_json->getApplication($this->objects['application']->getId());

        $this->assertEquals($application['status'], 'disabled');

        // enable again
        $this->_json->setApplicationState(array($this->objects['application']->getId()), 'enabled');
    }

    /**
     * try to add role and set members/rights
     */
    public function testAddRole()
    {
        // account to add as role member
        $account = Tinebase_User::getInstance()->getUserById($this->objects['user']->accountId);
        
        $roleData = $this->objects['role']->toArray();
        $roleMembers = array(
            array(
                "id"    => $account->getId(),
                "type"  => "user",
                "name"  => $account->accountDisplayName,
            )
        );
        $roleRights = array(
            array(
                "application_id"    => $this->objects['application']->getId(),
                "right"  => Tinebase_Acl_Rights::RUN,
            )
        );
        
        $result = $this->_json->saveRole($roleData, $roleMembers, $roleRights);
        
        // get role id from result
        $roleId = $result['id'];
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $this->assertEquals($role->getId(), $roleId);

        // check role members
        $result = $this->_json->getRoleMembers($role->getId());
        $this->assertGreaterThan(0, $result['totalcount']);
    }

    /**
     * try to get role rights
     *
     */
    public function testGetRoleRights()
    {
        $this->testAddRole();
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
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
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
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
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $result = $this->_json->deleteRoles(array($role->getId()));
        
        $this->assertTrue($result['success']);
        
        // try to get it, shouldn't be found
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
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
        $roleData = $this->objects['role']->toArray();
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
     */
    public function testSaveTagAndUpdateWithoutRights()
    {
        $tagData = $this->_getTagData();
        $this->objects['tag'] = $this->_json->saveTag($tagData);
        $this->assertEquals($tagData['name'], $this->objects['tag']['name']);
        
        $this->objects['tag']['rights'] = array();
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        $this->objects['tag'] = $this->_json->saveTag($this->objects['tag']);
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
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        $this->objects['tag'] = $this->_json->saveTag($tagData);
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
     * test searchContainers
     */
    public function testSearchContainers()
    {
        $personalAdb = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();
        
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
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $container['created_by']);
        
        // update container
        $container['name'] = 'testcontainerupdated';
        $container['account_grants'] = $this->_getContainerGrants();
        
        $containerUpdated = $this->_json->saveContainer($container);
        $this->assertEquals('testcontainerupdated', $containerUpdated['name']);
        $this->assertTrue($containerUpdated['account_grants'][0][Tinebase_Model_Grants::GRANT_ADMIN]);
        
        $deleteResult = $this->_json->deleteContainers(array($container['id']));
        $this->assertEquals('success', $deleteResult['status']);
    }
    
    /**
     * try to change container app
     */
    public function testChangeContainerApp()
    {
        $container = $this->_saveContainer();
        
        $container['application_id'] = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
        $this->setExpectedException('Tinebase_Exception_Record_NotAllowed');
        $containerUpdated = $this->_json->saveContainer($container);
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
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
        }
        $mailer->flush();
        
        // create and update container
        $container = $this->_saveContainer();
        $container['type'] = Tinebase_Model_Container::TYPE_PERSONAL;
        $container['note'] = 'changed to personal';
        $container['account_grants'] = $this->_getContainerGrants();
        $containerUpdated = $this->_json->saveContainer($container);
        
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }

        // check notification message
        $messages = $mailer->getMessages();
        $this->assertGreaterThan(0, count($messages));
        $notification = $messages[0];
        
        $translate = Tinebase_Translation::getTranslation('Admin');
        $body = quoted_printable_decode($notification->getBodyText(TRUE));
        $this->assertContains($container['note'],  $body, $body);
        
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
        $this->setExpectedException('Tinebase_Exception_Record_NotAllowed');
        $containerUpdated = $this->_json->saveContainer($container);
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
            "model" => "",
            "note" => ""
        ));
        // check if the model was set
        $this->assertEquals($container['model'], 'Addressbook_Model_Contact');
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
        
        Tinebase_Group::getInstance()->setGroupMembers($adminGroup->getId(), array($this->objects['user']->accountId));
        
        $containers = Tinebase_Container::getInstance()->getContainerByACL($this->objects['user']->accountId, 'Addressbook', Tinebase_Model_Grants::GRANT_ADD);
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
        $this->assertContains("phpinfo()", $info['html']);
        $this->assertContains("PHP Version =>", $info['html']);
    }
}
