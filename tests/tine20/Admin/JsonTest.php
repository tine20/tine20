<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        remove deprecated application rights test? 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Admin_JsonTest::main');
}

/**
 * Test class for Tinebase_Admin
 */
class Admin_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Json
     */
    protected $_backend;
    
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
        $this->_backend = new Admin_Frontend_Json();
        
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'updated group'
        )); 
            	
        $this->objects['user'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        $this->objects['accountUpdate'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnitup',
            'accountEmailAddress'   => 'phpunit@metaways.de',
        )); 
        
        /*
        $this->objects['application'] = new Tinebase_Model_Application (array(
            'id'                    => 5,
            'name'                  => 'Crm',
            'status'                => 'enabled',
            'version'               => '0.1',
            'order'                 => '99',
        ));
        */

        $this->objects['application'] = Tinebase_Application::getInstance()->getApplicationByName('Crm');
       
        $this->objects['role'] = new Tinebase_Model_Role(array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
        
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        // add account for group / role member tests
        try {
            $user = Tinebase_User::getInstance()->getUserById($this->objects['user']->accountId) ;
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_User::getInstance()->addUser($this->objects['user']);
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
        // remove accounts for group member tests
        try {
            Tinebase_User::getInstance()->deleteUser($this->objects['user']->accountId);
        } catch (Exception $e) {
            // do nothing
        }
                     
    }
    
    /**
     * try to get all accounts
     *
     */
    public function testGetAccounts()
    {
        $accounts = $this->_backend->getUsers('PHPUnit', 'accountDisplayName', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $accounts['totalcount']);
    }
    
    /**
     * get account that doesn't exist (by id)
     *
     */
    public function testGetNonExistentAccountById()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $id = 12334567;
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        // add account for group / role member tests
        $user = Tinebase_User::getInstance()->getUserById($id);
        
        #$this->assertEquals($translate->_('unknown'), $user->accountDisplayName);
        #$this->assertEquals($id, $user->accountId);
    }

    /**
     * get account that doesn't exist (by login name)
     *
     */
    public function testGetNonExistentAccountByLoginName()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $loginName = 'something';
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        // add account for group / role member tests
        $user = Tinebase_User::getInstance()->getUserByLoginName($loginName);
        
        #$this->assertEquals($translate->_('unknown'), $user->accountDisplayName);
        #$this->assertEquals(0, $user->accountId);
    }

    /**
     * try to save group data
     *
     */
    public function testAddGroup()
    {
        //print_r ($this->objects['initialGroup']->toArray());
        $encodedData = Zend_Json::encode($this->objects['initialGroup']->toArray());
        
        $result = $this->_backend->saveGroup($encodedData, Zend_Json::encode(array()));
        
        $this->assertEquals($this->objects['initialGroup']->description, $result['description']);
    }    

    /**
     * try to save an account
     *
     */
    public function testSaveAccount()
    {
        $accountData = $this->objects['accountUpdate']->toArray();
        $accountData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getGroupByName('tine20phpunit')->getId();
        $accountData['accountPassword'] = 'test';
        $accountData['accountPassword2'] = 'test';
        
        $account = $this->_backend->saveUser($accountData);
        
        $this->assertTrue(is_array($account));
        $this->assertEquals('PHPUnitup', $account['accountFirstName']);
        $this->assertEquals(Tinebase_Group::getInstance()->getGroupByName('tine20phpunit')->getId(), $account['accountPrimaryGroup']['id']);
        // check password
        $authResult = Tinebase_Auth::getInstance()->authenticate($account['accountLoginName'], 'test');
        $this->assertTrue($authResult->isValid());
    }    

    /**
     * try to delete accounts 
     *
     */
    public function testDeleteAccounts()
    {
        $this->_backend->deleteUsers(array($this->objects['user']->accountId));
        
        $this->setExpectedException('Exception');
        Tinebase_User::getInstance()->getUserById($this->objects['user']->getId);
    }

    /**
     * try to set account state
     *
     */
    public function testSetAccountState()
    {
        $this->_backend->setAccountState(array($this->objects['user']->getId()), 'disabled');
        
        $account = Tinebase_User::getInstance()->getFullUserById($this->objects['user']);
        
        $this->assertEquals('disabled', $account->accountStatus);    
    }
    
    /**
     * try to reset password
     *
     */
    public function testResetPassword()
    {
        $this->_backend->resetPassword($this->objects['user']->toArray(), 'password', FALSE);
        
        $authResult = Tinebase_Auth::getInstance()->authenticate($this->objects['user']->accountLoginName, 'password');
        $this->assertTrue($authResult->isValid());    
    }
    
    /**
     * try to get all groups
     *
     */
    public function testGetGroups()
    {
        $groups = $this->_backend->getGroups(NULL, 'id', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $groups['totalcount']);
    }    

    /**
     * try to update group data
     *
     */
    public function testUpdateGroup()
    {
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        // set data array
        $data = $this->objects['updatedGroup']->toArray();
        $data['id'] = $group->getId();
        
        // add group members array
        $groupMembers = array($this->objects['user']->accountId);
        
        $result = $this->_backend->saveGroup($data, $groupMembers);

        $this->assertGreaterThan(0,sizeof($result['groupMembers'])); 
        $this->assertEquals($this->objects['updatedGroup']->description, $result['description']); 
    }    

    /**
     * try to get group members
     *
     */
    public function testGetGroupMembers()
    {        
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['updatedGroup']->name);

        // set group members
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), array($this->objects['user']->accountId));
        
        // get group members with json
        $getGroupMembersArray = $this->_backend->getGroupMembers($group->getId());
        
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
    	// delete group with json.php function
    	$group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
    	$result = $this->_backend->deleteGroups(array($group->getId()));
    	
    	$this->assertTrue($result['success']);
    	
    	// try to get deleted group
    	$this->setExpectedException('Tinebase_Exception_Record_NotDefined');
    	
        // get group by name
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name); 
        	
    }    
    
    /**
     * try to get all access log entries
     *
     */
    public function testGetAccessLogs()
    {
        $from = new Zend_Date ();
        $from->sub('02:00:00',Zend_Date::TIMES);
        $to = new Zend_Date ();
        
        $accessLogs = $this->_backend->getAccessLogEntries($from->get(Tinebase_Record_Abstract::ISO8601LONG), $to->get(Tinebase_Record_Abstract::ISO8601LONG), NULL, '{"sort":"li","dir":"DESC","start":0,"limit":50}');
        //print_r($accessLogs);
      
        // check total count
        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
    }
    
    /**
     * try to get all access log entries
     *
     */
    public function testGetAccessLogsWithDeletedUser()
    {
    	$user = $this->objects['user'];

        Tinebase_AccessLog::getInstance()->addLoginEntry('test_session_id', $user->accountLoginName, '127.0.0.1', Zend_Auth_Result::SUCCESS, $user->getId());
                
    	Tinebase_User::getInstance()->deleteUser($user->getId());
    	
        $from = new Zend_Date ();
        $from->sub('02:00:00',Zend_Date::TIMES);
        $to = new Zend_Date ();
        
        $accessLogs = $this->_backend->getAccessLogEntries($from->get(Tinebase_Record_Abstract::ISO8601LONG), $to->get(Tinebase_Record_Abstract::ISO8601LONG), NULL, '{"sort":"li","dir":"DESC","start":0,"limit":50}');

        // check total count
        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
        
        $testLogEntry = $accessLogs['results'][0];
        // check nonExistentUser
        $this->assertEquals(Tinebase_User::getInstance()->getNonExistentUser()->accountDisplayName, $testLogEntry['accountObject']['accountDisplayName']);
        
        // cleanup
        $this->_backend->deleteAccessLogEntries(Zend_Json::encode(array($testLogEntry['id'])));
    }    
    
    /**
     * try to delete access log entries
     *
     */
    public function testDeleteAccessLogs()
    {
        $from = new Zend_Date();
        $from->sub('02:00:00',Zend_Date::TIMES);
        $to = new Zend_Date ();
        
        $accessLogs = $this->_backend->getAccessLogEntries($from->get(Tinebase_Record_Abstract::ISO8601LONG), $to->get(Tinebase_Record_Abstract::ISO8601LONG), 'tine20admin', '{"sort":"li","dir":"DESC","start":0,"limit":50}');

        //print_r($accessLogs);
        
        $deleteLogIds = array();
        foreach ($accessLogs['results'] as $log) {
            $deleteLogIds[] = $log['id'];
        }
        
        // delete logs
        $this->_backend->deleteAccessLogEntries(Zend_Json::encode($deleteLogIds));
        
        // check total count
        $accessLogs = $this->_backend->getAccessLogEntries($from->get(Tinebase_Record_Abstract::ISO8601LONG), $to->get(Tinebase_Record_Abstract::ISO8601LONG), 'tine20admin', 'id', 'ASC', 0, 10);
        $this->assertEquals(0, sizeof($accessLogs['results']), 'results not matched');
        $this->assertEquals(0, $accessLogs['totalcount'], 'totalcount not matched');
    }        
	
    /**
     * try to get an application
     *
     */
    public function testGetApplication()
    {
        $application = $this->_backend->getApplication($this->objects['application']->getId());
        
        $this->assertEquals($application['status'], $this->objects['application']->status);
        
    }

    /**
     * try to get applications
     *
     */
    public function testGetApplications()
    {
        $applications = $this->_backend->getApplications(NULL, NULL, 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $applications['totalcount']);
    }


    /**
     * try to set application state
     *
     */
    public function testSetApplicationState()
    {
        $this->_backend->setApplicationState(array($this->objects['application']->getId()), 'disabled');
        
        $application = $this->_backend->getApplication($this->objects['application']->getId());

        $this->assertEquals($application['status'], 'disabled');

        // enable again
        $this->_backend->setApplicationState(array($this->objects['application']->getId()), 'enabled');
    }

    /**
     * try to add role and set members/rights
     *
     */
    public function testAddRole()
    {
        // account to add as role member
        $account = Tinebase_User::getInstance()->getUserById($this->objects['user']->accountId);
        
        $encodedData = Zend_Json::encode($this->objects['role']->toArray());
        $encodedRoleMembers = Zend_Json::encode(array(
            array(
                "id"    => $account->getId(),
                "type"  => "user",
                "name"  => $account->accountDisplayName,
            )
        ));
        $encodedRoleRights = Zend_Json::encode(array(
            array(
                "application_id"    => $this->objects['application']->getId(),
                "right"  => Tinebase_Acl_Rights::RUN,
            )
        ));
        
        $result = $this->_backend->saveRole($encodedData, $encodedRoleMembers, $encodedRoleRights);
        
        
        // get role id from result
        $roleId = $result['id'];
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $this->assertEquals($role->getId(), $roleId);

        // check role members
        $result = $this->_backend->getRoleMembers($role->getId());        
        $this->assertGreaterThan(0, $result['totalcount']);
    }

    /**
     * try to get role rights
     *
     */
    public function testGetRoleRights()
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        $rights = $this->_backend->getRoleRights($role->getId());
        
        //print_r ($rights);
        $this->assertGreaterThan(0, $rights['totalcount']);
        $this->assertEquals(Tinebase_Acl_Rights::RUN, $rights['results'][0]['right']);
    }
    
    /**
     * try to save role
     *
     */
    public function testUpdateRole()
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        $role->description = "updated description";
        $roleArray = $role->toArray();
        $encodedData = Zend_Json::encode($roleArray);
        
        $result = $this->_backend->saveRole($encodedData, Zend_Json::encode(array()),Zend_Json::encode(array()));
        
        $this->assertEquals("updated description", $result['description']);        
    }

    /**
     * try to get roles
     *
     */
    public function testGetRoles()
    {
        $roles = $this->_backend->getRoles(NULL, NULL, 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $roles['totalcount']);
    }
    
    /**
     * try to delete roles
     *
     *
     */
    public function testDeleteRoles()
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $encodedData = Zend_Json::encode(array($role->getId()));        
        $result = $this->_backend->deleteRoles($encodedData);
        
        $this->assertTrue($result['success']);
        
        // try to get it, shouldn't be found
        $this->setExpectedException('Exception');
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
    }    

    /**
     * try to get all role rights
     *
     * @todo    check structure of return array
     */
    public function testGetAllRoleRights()
    {
        $allRights = $this->_backend->getAllRoleRights();
        
        $this->assertGreaterThan(0, $allRights);
    }
    
}       
    
if (PHPUnit_MAIN_METHOD == 'Admin_JsonTest::main') {
    Admin_JsonTest::main();
}
