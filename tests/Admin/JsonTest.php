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
        $this->objects['initialGroup'] = new Tinebase_Group_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'initial group'
        )); 
        
        $this->objects['updatedGroup'] = new Tinebase_Group_Model_Group(array(
            'name'          => 'tine20phpunit',
            'description'   => 'updated group'
        )); 
            	
        $this->objects['account'] = new Tinebase_Account_Model_FullAccount(array(
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
        
        $this->objects['accountUpdate'] = new Tinebase_Account_Model_FullAccount(array(
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
       
        $this->objects['role'] = new Tinebase_Acl_Model_Role ( array(
            'name'                  => 'phpunit test role',
            'description'           => 'phpunit test role',
        ));
        
        // add account for group member tests
        try {
            Tinebase_Account::getInstance()->getAccountById($this->objects['account']->accountId) ;
        } catch ( Exception $e ) {
            Tinebase_Account::getInstance()->addAccount (  $this->objects['account'] );
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
        Tinebase_Account::getInstance()->deleteAccount (  $this->objects['account']->accountId );        
    }
    
    /**
     * try to get all accounts
     *
     */
    public function testGetAccounts()
    {
        $json = new Admin_Json();
        
        $accounts = $json->getAccounts('PHPUnit', 'accountDisplayName', 'ASC', 0, 10);
        
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
        
        $this->assertTrue($result['success']); 
        $this->assertEquals($this->objects['initialGroup']->description, $result['updatedData']['description']);
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
        
        $account = $json->saveAccount($encodedData, 'test', 'test');
        
        $this->assertTrue ( is_array($account) );
        $this->assertEquals('tine20phpunitup', $account['accountLoginName']);
        $this->assertEquals(Tinebase_Group_Sql::getInstance()->getGroupByName('tine20phpunit')->getId(), $account['accountPrimaryGroup']);
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
        
        $json->deleteAccounts( Zend_Json::encode(array($this->objects['account']->getId())) );
        
        $this->setExpectedException ( 'Exception' );
        Tinebase_Account::getInstance()->getAccountById($this->objects['account']->getId);
    }

    /**
     * try to set account state
     *
     */
    public function testSetAccountState()
    {
        $json = new Admin_Json();
        
        $json->setAccountState(Zend_Json::encode(array($this->objects['account']->getId())), 'disabled');
        
        $account = Tinebase_Account::getInstance()->getFullAccountById($this->objects['account']);
        
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

        $this->assertTrue($result['success']); 
        $this->assertGreaterThan(0,sizeof($result['groupMembers'])); 
        $this->assertEquals($this->objects['updatedGroup']->description, $result['updatedData']['description']); 
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
        
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), NULL, 'id', 'ASC', 0, 10);
        
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
        
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), 'tine20admin', 'id', 'ASC', 0, 10);

        //print_r ( $accessLogs );
        
        $deleteLogIds = array();
        foreach ( $accessLogs['results'] as $log ) {
            $deleteLogIds[] = $log['id'];
        }
        
        // delete logs
        $json->deleteAccessLogEntries( Zend_Json::encode($deleteLogIds) );
        
        // check total count
        $accessLogs = $json->getAccessLogEntries($from->getIso(), $to->getIso(), 'tine20admin', 'id', 'ASC', 0, 10);
        $this->assertEquals(0, sizeof($accessLogs['results']));
        $this->assertEquals(0, $accessLogs['totalcount']);
    }        
	
    /**
     * try to get an application
     *
     */
    public function testGetApplication()
    {
        $json = new Admin_Json();
        
        //@todo create new application
        
        $application = $json->getApplication( $this->objects['application']->getId() );
        
        //$this->assertEquals($application['name'], $this->objects['application']->name);
        $this->assertEquals($application['status'], $this->objects['application']->status);
        
        //@todo remove application (no delete function yet) 
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
     *
     */
    public function testSaveApplicationPermissions()
    {        
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
        
        //echo $rights;
                
        $json = new Admin_Json();
        
        $result = $json->saveApplicationPermissions($this->objects['application']->getId(), $rights);
        
        $this->assertTrue( $result["success"], "save permissions failed" );
        $this->assertEquals ( 2, substr($result["welcomeMessage"],0,1) );
    }
    
    /**
     * try to get applications permissions
     *
     */
    public function testGetApplicationPermissions()
    {
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
        
    }

    /**
     * try to add role
     *
     */
    public function testAddRole()
    {
        
        $json = new Admin_Json();
        
        $encodedData = Zend_Json::encode($this->objects['role']->toArray());
        $result = $json->saveRole($encodedData);
        
        $this->assertTrue($result['success']);    
        
        // get role id from result
        $roleId = $result['updatedData']['id'];
        
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        $this->assertEquals($role->getId(), $roleId);
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
        
        $result = $json->saveRole($encodedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals( "updated description", $result['updatedData']['description']);        
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
     * @todo implement json function and use it
     */
    public function testDeleteRoles()
    {
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
        //$json = new Admin_Json();        
        Tinebase_Acl_Roles::getInstance()->deleteRoles($role->getId());
        
        // try to get it, shouldn't be found
        $this->setExpectedException( 'Exception');
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($this->objects['role']->name);
        
    }
    
}       
    
if (PHPUnit_MAIN_METHOD == 'Admin_JsonTest::main') {
    Admin_JsonTest::main();
}
