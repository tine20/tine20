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
        $this->objects['initialGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'updated group'
        )); 
            	
        $this->objects['account'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        $this->objects['accountUpdate'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 10,
            'accountLoginName'      => 'tine20phpunitup',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group_Sql::getInstance()->getGroupByName('Users')->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnitup',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        )); 
        
        /*
        $this->objects['application'] = new Tinebase_Model_Application ( array(
            'id'                    => 5,
            'name'                  => 'Crm',
            'status'                => 'enabled',
            'version'               => '0.1',
            'order'                 => '99',
        ));
        */

        $this->objects['application'] = Tinebase_Application::getInstance()->getApplicationByName('Crm');
       
        $this->objects['role'] = new Tinebase_Model_Role ( array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
        
        // add account for group / role member tests
        try {
            Tinebase_User::getInstance()->getUserById($this->objects['account']->accountId) ;
        } catch ( Exception $e ) {
            Tinebase_User::getInstance()->addUser(  $this->objects['account'] );
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
            Tinebase_User::getInstance()->deleteUser(  $this->objects['account']->accountId );
        } catch ( Exception $e ) {
            // do nothing
        }
                     
    }
    
    /**
     * try to get all accounts
     *
     */
    public function testGetAccounts()
    {
        $json = new Admin_Json();
        
        $accounts = $json->getUsers('PHPUnit', 'accountDisplayName', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $accounts['totalcount']);
    }    

    /**
     * try to save group data
     *
     */
    public function testAddGroup()
    {
        $json = new Admin_Json();
        
        //print_r ( $this->objects['initialGroup']->toArray());
        $encodedData = Zend_Json::encode( $this->objects['initialGroup']->toArray() );
        
        $result = $json->saveGroup( $encodedData, Zend_Json::encode(array()) );
        
        $this->assertEquals($this->objects['initialGroup']->description, $result['description']);
    }    

    /**
     * try to save an account
     *
     */
    public function testSaveAccount()
    {
        $json = new Admin_Json();
        
        $accountData = $this->objects['accountUpdate']->toArray();
        $accountData['accountPrimaryGroup'] = Tinebase_Group_Sql::getInstance()->getGroupByName('tine20phpunit')->getId();
        
        $encodedData = Zend_Json::encode( $accountData );
        
        $account = $json->saveUser($encodedData, 'test', 'test');
        
        $this->assertTrue ( is_array($account) );
        $this->assertEquals('tine20phpunitup', $account['accountLoginName']);
        $this->assertEquals(Tinebase_Group_Sql::getInstance()->getGroupByName('tine20phpunit')->getId(), $account['accountPrimaryGroup']['id']);
        // check password
        $authResult = Tinebase_Auth::getInstance()->authenticate($account['accountLoginName'], 'test');
        $this->assertTrue ( $authResult->isValid() );
    }    

    /**
     * try to delete accounts 
     *
     */
    public function testDeleteAccounts()
    {
        $json = new Admin_Json();
        $encodedAccountIds = Zend_Json::encode(array($this->objects['account']->accountId));
        
        $json->deleteUsers($encodedAccountIds);
        
        $this->setExpectedException ( 'Exception' );
        Tinebase_User::getInstance()->getUserById($this->objects['account']->getId);
    }

    /**
     * try to set account state
     *
     */
    public function testSetAccountState()
    {
        $json = new Admin_Json();
        
        $json->setAccountState(Zend_Json::encode(array($this->objects['account']->getId())), 'disabled');
        
        $account = Tinebase_User::getInstance()->getFullUserById($this->objects['account']);
        
        $this->assertEquals('disabled', $account->accountStatus);    
    }
    
    /**
     * try to reset password
     *
     */
    public function testResetPassword()
    {
        $json = new Admin_Json();
        
        $json->resetPassword(Zend_Json::encode($this->objects['account']->toArray()), 'password');
        
        $authResult = Tinebase_Auth::getInstance()->authenticate($this->objects['account']->accountLoginName, 'password');
        $this->assertTrue ( $authResult->isValid() );    
    }
    
    /**
     * try to get all groups
     *
     */
    public function testGetGroups()
    {
        $json = new Admin_Json();
        
        $groups = $json->getGroups(NULL, 'id', 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $groups['totalcount']);
    }    

    /**
     * try to update group data
     *
     */
    public function testUpdateGroup()
    {
        $json = new Admin_Json();

        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        // set encoded data array
        $data = $this->objects['updatedGroup']->toArray();
        $data['id'] = $group->getId();
        $encodedData = Zend_Json::encode( $data );
        
        // add group members array and encode it
        $groupMembers = array( $this->objects['account']->accountId );
        $encodedGroupMembers = Zend_Json::encode( $groupMembers );        
        
        $result = $json->saveGroup( $encodedData, $encodedGroupMembers );

        $this->assertGreaterThan(0,sizeof($result['groupMembers'])); 
        $this->assertEquals($this->objects['updatedGroup']->description, $result['description']); 
    }    

    /**
     * try to get group members
     *
     */
    public function testGetGroupMembers()
    {        
        $json = new Admin_Json();        
          
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['updatedGroup']->name);

        // set group members
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), array( $this->objects['account']->accountId ));
        
        // get group members with json
        $getGroupMembersArray = $json->getGroupMembers($group->getId());
        
        $this->assertTrue ( isset($getGroupMembersArray['results'][0]));
        $this->assertEquals($this->objects['account']->accountDisplayName, $getGroupMembersArray['results'][0]['accountDisplayName']);
        $this->assertGreaterThan(0, $getGroupMembersArray['totalcount']);
    }       
    
    /**
     * try to delete group
     *
     */
    public function testDeleteGroup()
    {
        
    	$json = new Admin_Json();
    	    	
    	// delete group with json.php function
    	$group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
    	$groupId = Zend_Json::encode( array($group->getId()) );
    	$result = $json->deleteGroups( $groupId );
    	
    	$this->assertTrue( $result['success'] );
    	
    	// try to get deleted group
    	$this->setExpectedException('Tinebase_Record_Exception_NotDefined');
    	
        // get group by name
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name); 
        	
    }    
    
    /**
     * try to get all access log entries
     *
     */
    public function testGetAccessLogs()
    {
        $json = new Admin_Json();
        
        $from = new Zend_Date ();
        $from->sub('02:00:00',Zend_Date::TIMES);
        $to = new Zend_Date ();
        
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), NULL, '{"sort":"li","dir":"DESC","start":0,"limit":50}');
        
        //print_r ( $accessLogs );
      
        // check total count
        $this->assertGreaterThan(0, sizeof($accessLogs['results']));
        $this->assertGreaterThan(0, $accessLogs['totalcount']);
    }    

    /**
     * try to delete access log entries
     *
     */
    public function testDeleteAccessLogs()
    {
        $json = new Admin_Json();
        
        $from = new Zend_Date ();
        $from->sub('02:00:00',Zend_Date::TIMES);
        $to = new Zend_Date ();
        
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), 'tine20admin', '{"sort":"li","dir":"DESC","start":0,"limit":50}');

        //print_r ( $accessLogs );
        
        $deleteLogIds = array();
        foreach ( $accessLogs['results'] as $log ) {
            $deleteLogIds[] = $log['id'];
        }
        
        // delete logs
        $json->deleteAccessLogEntries( Zend_Json::encode($deleteLogIds) );
        
        // check total count
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), 'tine20admin', 'id', 'ASC', 0, 10);
        $this->assertEquals(0, sizeof($accessLogs['results']), 'results not matched');
        $this->assertEquals(0, $accessLogs['totalcount'], 'totalcount not matched');
    }        
	
    /**
     * try to get an application
     *
     */
    public function testGetApplication()
    {
        $json = new Admin_Json();
        
        $application = $json->getApplication( $this->objects['application']->getId() );
        
        $this->assertEquals($application['status'], $this->objects['application']->status);
        
    }

    /**
     * try to get applications
     *
     */
    public function testGetApplications()
    {
        $json = new Admin_Json();
        
        $applications = $json->getApplications( NULL, NULL, 'ASC', 0, 10);
        
        $this->assertGreaterThan(0, $applications['totalcount']);
    }


    /**
     * try to set application state
     *
     */
    public function testSetApplicationState()
    {
        $json = new Admin_Json();
        
        $json->setApplicationState( Zend_Json::encode(array($this->objects['application']->getId())), 'disabled' );
        
        $application = $json->getApplication( $this->objects['application']->getId() );

        $this->assertEquals($application['status'], 'disabled');

        // enable again
        $json->setApplicationState( Zend_Json::encode(array($this->objects['application']->getId())), 'enabled' );
    }

    /**
     * try to set applications permissions
     * @deprecated isn't used anymore, replaced by role management
     */
    public function testSaveApplicationPermissions()
    {
        /*        
        $adminGroup = Tinebase_Group::getInstance()->getGroupByName('Administrators'); 
        $rights = Zend_Json::encode(array(
           array(
                "application_id" => $this->objects['application']->getId(),
                "account_id" => $adminGroup->getId(),
                "account_type" => "group",
                "accountDisplayName" => "Administrators",
                "run" => TRUE,
                "admin" => TRUE,           
           ) 
        ));
        
        $json = new Admin_Json();
        
        $result = $json->saveApplicationPermissions($this->objects['application']->getId(), $rights);
        
        $this->assertTrue( $result["success"], "save permissions failed" );
        $this->assertEquals ( 2, substr($result["welcomeMessage"],0,1) );
        */
    }
    
    /**
     * try to get applications permissions
     *
     * @deprecated isn't used anymore, replaced by role management
     */
    public function testGetApplicationPermissions()
    {
        /*
        $json = new Admin_Json();
        
        $permissions = $json->getApplicationPermissions($this->objects['application']->getId());
        //print_r( $permissions );
        
        $this->assertGreaterThan(0, $permissions['totalcount']);
        
        // get permissions for admin group
        $adminPermissions = array();
        foreach ( $permissions['results'] as $permission ) {
            if ( $permission['accountDisplayName'] === 'Administrators' ) {
                $adminPermissions = $permission;
                break;
            }
        }
        
        $this->assertTrue ( $adminPermissions['admin'], "admin group doesn't have the admin right" );
        */
    }

    /**
     * try to add role and set members/rights
     *
     */
    public function testAddRole()
    {
        // account to add as role member
        $account = Tinebase_User::getInstance()->getUserById($this->objects['account']->accountId);
        
        $json = new Admin_Json();
        
        $encodedData = Zend_Json::encode($this->objects['role']->toArray());
        $encodedRoleMembers = Zend_Json::encode(array(
            array(
                "account_id"    => $account->getId(),
                "account_type"  => "user",
                "name"  => $account->accountDisplayName,
            )
        ));
        $encodedRoleRights = Zend_Json::encode(array(
            array(
                "application_id"    => $this->objects['application']->getId(),
                "right"  => Tinebase_Acl_Rights::RUN,
            )
        ));
        
        $result = $json->saveRole($encodedData, $encodedRoleMembers, $encodedRoleRights);
        
        
        // get role id from result
        $roleId = $result['id'];
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $this->assertEquals($role->getId(), $roleId);

        // check role members
        $result = $json->getRoleMembers($role->getId());        
        $this->assertGreaterThan(0, $result['totalcount']);
    }

    /**
     * try to get role rights
     *
     */
    public function testGetRoleRights()
    {
        $json = new Admin_Json();
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        $rights = $json->getRoleRights( $role->getId() );
        
        //print_r ( $rights );
        $this->assertGreaterThan(0, $rights['totalcount']);
        $this->assertEquals(Tinebase_Acl_Rights::RUN, $rights['results'][0]['right']);
    }
    
    /**
     * try to save role
     *
     */
    public function testUpdateRole()
    {
        $json = new Admin_Json();
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        $role->description = "updated description";
        $roleArray = $role->toArray();
        $encodedData = Zend_Json::encode($roleArray);
        
        $result = $json->saveRole($encodedData, Zend_Json::encode(array()),Zend_Json::encode(array()));
        
        $this->assertEquals( "updated description", $result['description']);        
    }

    /**
     * try to get roles
     *
     */
    public function testGetRoles()
    {
        $json = new Admin_Json();
        
        $roles = $json->getRoles( NULL, NULL, 'ASC', 0, 10);
        
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
        
        $json = new Admin_Json();
        $encodedData = Zend_Json::encode(array($role->getId()));        
        //Tinebase_Acl_Roles::getInstance()->deleteRoles($role->getId());
        $result = $json->deleteRoles($encodedData);
        
        $this->assertTrue($result['success']);
        
        // try to get it, shouldn't be found
        $this->setExpectedException( 'Exception');
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
    }    

    /**
     * try to get all role rights
     *
     * @todo    check structure of return array
     */
    public function testGetAllRoleRights()
    {
        $json = new Admin_Json();
        
        $allRights = $json->getAllRoleRights();
        
        //print_r ( $allRights );
        $this->assertGreaterThan(0, $allRights);
    }
    
}       
    
if (PHPUnit_MAIN_METHOD == 'Admin_JsonTest::main') {
    Admin_JsonTest::main();
}
