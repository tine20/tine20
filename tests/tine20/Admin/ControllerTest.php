<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Admin
 */
class Admin_ControllerTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Admin Controller Tests');
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
            'id'            => 'test-controller-group',
            'name'          => 'tine20phpunit',
            'description'   => 'initial test group'
        ));
        
        $this->objects['updatedGroup'] = new Tinebase_Model_Group(array(
            'id'            => 'test-controller-group',
            'name'          => 'tine20phpunit updated',
            'description'   => 'updated test group'
        ));
         
        $this->objects['initialAccount'] = new Tinebase_Model_FullUser(array(
            'accountId'             => 'dflkjgldfgdfgd',
            'accountLoginName'      => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@metaways.de'
        ));
        
        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit-updated',
            'accountStatus'         => 'disabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getGroupByName('Users')->id,
            'accountLastName'       => 'Tine 2.0 Updated',
            'accountFirstName'      => 'PHPUnit Updated',
            'accountEmailAddress'   => 'phpunit@tine20.org'
        ));
        
            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);

                $this->objects['initialGroup']->container_id = $internalAddressbook->getId();
                $this->objects['updatedGroup']->container_id = $internalAddressbook->getId();
                $this->objects['initialAccount']->container_id = $internalAddressbook->getId();
                $this->objects['updatedAccount']->container_id = $internalAddressbook->getId();
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
    
    }
    
    /**
     * try to add an account
     *
     */
    public function testAddAccount()
    {
        $account = Admin_Controller_User::getInstance()->create($this->objects['initialAccount'], 'lars', 'lars');
        $this->assertTrue(!empty($account->accountId));
        //$this->assertTrue($this->objects['initialAccount']->accountId != $account->accountId);
        $this->assertEquals($this->objects['initialAccount']->accountLoginName, $account->accountLoginName);
        
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($account->accountId);
        $this->assertTrue(!empty($contact->creation_time));
    }
    
    /**
     * try to get all accounts containing phpunit in there name
     *
     */
    public function testGetAccounts()
    {
        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($this->objects['initialAccount']['accountLoginName']);
                
        $this->assertEquals(1, count($accounts));
    }
    
    /**
     * try to delete an accout
     *
     */
    public function testDeleteAccount()
    {
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
        $this->setExpectedException('Tinebase_Exception_AccessDenied');
        Admin_Controller_User::getInstance()->delete(Tinebase_Core::getUser()->getId());
    }

    /**
     * try to add a group
     *
     */
    public function testAddGroup()
    {
        $group = Admin_Controller_Group::getInstance()->create($this->objects['initialGroup']);
        
        $this->assertEquals($this->objects['initialGroup']->id, $group->id);
    }
    
    /**
     * try to get all groups
     *
     */
    public function testGetGroups()
    {
        $groups = Admin_Controller_Group::getInstance()->search($this->objects['initialGroup']->name);
        
        $this->assertEquals(1, count($groups));
    }    

    /**
     * try to get Users group
     *
     */
    public function testGetGroup()
    {
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
        $groups = Admin_Controller_Group::getInstance()->search($this->objects['initialGroup']->name);
        
        Admin_Controller_Group::getInstance()->delete($groups->getArrayOfIds());

        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');

        $group = Admin_Controller_Group::getInstance()->get($groups[0]->getId());
    }
    
}
