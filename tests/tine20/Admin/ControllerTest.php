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
//            'accountEmailAddress'   => 'phpunit@metaways.de'
//        ));
//
//        $this->objects['updatedAccount'] = new Tinebase_Model_FullUser(array(
//            'accountLoginName'      => 'tine20phpunit-updated',
//            'accountStatus'         => 'disabled',
//            'accountExpires'        => NULL,
//            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
//            'accountLastName'       => 'Tine 2.0 Updated',
//            'accountFirstName'      => 'PHPUnit Updated',
//            'accountEmailAddress'   => 'phpunit@tine20.org'
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
    public function testCustomFieldDelete()
    {
        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        $deleted = Admin_Controller_Customfield::getInstance()->delete($result->getId());

        $this->assertEquals(1, count($deleted));
    }
}
