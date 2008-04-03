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
 * @todo        add more tests!
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
     * try to save group data
     *
     */
    public function testAddGroup()
    {
        $json = new Admin_Json();
        
        //print_r ( $this->objects['initialGroup']->toArray());
        $encodedData = Zend_Json::encode( $this->objects['initialGroup']->toArray() );
        
        $json->saveGroup( $encodedData, array() );
        
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        $this->assertEquals($this->objects['initialGroup']->description, $group->description);
    }    

    /**
     * try to update group data
     *
     */
    public function testUpdateGroup()
    {
        $json = new Admin_Json();

        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        $data = $this->objects['updatedGroup']->toArray();
        $data['id'] = $group->getId();
        $encodedData = Zend_Json::encode( $data );
        
        //@todo add group members array to the test
        $groupMembers = array();
        $json->saveGroup( $encodedData, $groupMembers );

        $updatedGroup = Tinebase_Group::getInstance()->getGroupByName($this->objects['updatedGroup']->name);
        
        $this->assertEquals($this->objects['updatedGroup']->description, $updatedGroup->description); 
    }    

    /**
     * try to set/get group members
     *
     */
    public function testGetGroupMembers()
    {        
        $json = new Admin_Json();        
        
        $setGroupMembersArray = array ( $this->objects['account']->accountId );
        
        $group = Tinebase_Group::getInstance()->getGroupByName($this->objects['initialGroup']->name);
        
        //@todo set with json?
        Tinebase_Group::getInstance()->setGroupMembers($group->getId(), $setGroupMembersArray );
        
        // get group members with json
        $getGroupMembersArray = $json->getGroupMembers($group->getId());
        
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
    
}		
	

if (PHPUnit_MAIN_METHOD == 'Admin_JsonTest::main') {
    Admin_JsonTest::main();
}
