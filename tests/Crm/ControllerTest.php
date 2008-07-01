<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        complete code coverage of controller by adding more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Crm_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    protected $_testContainer;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Controller Tests');
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
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Crm', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->_testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Zend_Date::now(),
        )); 
        
        $this->_objects['updatedLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->_testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description updated',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        )); 

        $addressbookPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Addressbook', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        $addressbookContainer = $addressbookPersonalContainer[0];
        
        $this->_objects['user'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'id'                    => 120,
            'note'                  => 'Bla Bla Bla',
            'owner'                 => $addressbookContainer->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        )); 

        $tasksPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Tasks', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->_objects['task'] = new Tasks_Model_Task(array(
            // tine record fields
            'id'                   => '90a75021e353685aa9a06e67a7c0b558d0acae32',
            'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
        
        // some products
        $this->_objects['someProducts'] = array(
                new Crm_Model_Product(array(
                    'id' => 1001,
                    'productsource' => 'Just a phpunit test product #1',
                    'price' => '47.11')),
                new Crm_Model_Product(array(
                    'id' => 1002,
                    'productsource' => 'Just a phpunit test product #2',
                    'price' => '18.05')),
                new Crm_Model_Product(array(
                    'id' => 1003,
                    'productsource' => 'Just a phpunit test product #3',
                    'price' => '19.78'))
        );
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
     * try to add a lead
     *
     */
    public function testAddLead()
    {
        $lead = Crm_Controller::getInstance()->createLead($this->_objects['initialLead']);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }
    
    /**
     * try to get a lead
     *
     */
    public function testGetLead()
    {
        $lead = Crm_Controller::getInstance()->getLead($this->_objects['initialLead']);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }
    
    /**
     * try to get an empty lead
     *
     */
    public function testGetEmptyLead()
    {
        $lead = Crm_Controller::getInstance()->getEmptyLead();
                
        $this->assertType('Crm_Model_Lead', $lead);

        // empty lead can not be valid
        $this->assertFalse($lead->isValid());
    }
    
    /**
     * try to update a lead
     *
     */
    public function testUpdateLead()
    {
        $lead = Crm_Controller::getInstance()->updateLead($this->_objects['updatedLead']);
        
        $this->assertEquals($this->_objects['updatedLead']->id, $lead->id);
        $this->assertEquals($this->_objects['updatedLead']->description, $lead->description);
    }

    /**
     * try to get all leads and compare counts
     *
     */
    public function testGetAllLeads()
    {
        $filter = new Crm_Model_LeadFilter();
        $filter->container = array($this->_testContainer->id);
        $filter->query = 'PHPUnit';
        $filter->showClosed = true;
        $pagination = new Crm_Model_LeadPagination();
        $leads = Crm_Controller::getInstance()->searchLeads($filter, $pagination);
        $count = Crm_Controller::getInstance()->searchLeadsCount($filter);
                
        $this->assertEquals(1, count($leads));
        $this->assertEquals($count, count($leads));
        $this->assertType('Tinebase_Record_RecordSet', $leads);
    }
    
    /**
     * try to get all shared leads
     *
     */
    public function testGetSharedLeads()
    {
        $filter = new Crm_Model_LeadFilter();
        $filter->containerType = 'shared';
        $filter->query = 'PHPUnit';
        $filter->showClosed = true;
        $pagination = new Crm_Model_LeadPagination();
        $leads = Crm_Controller::getInstance()->searchLeads($filter, $pagination);
        
        $this->assertEquals(0, count($leads));
        $this->assertType('Tinebase_Record_RecordSet', $leads);
    }
    
    /**
     * try to set / get linked tasks
     *
     * @todo use relations here
     */
    public function testLinkedTasks()
    {        
        try {
            $task = Tasks_Controller::getInstance()->getTask($this->_objects['task']->id);
        } catch (Exception $e) {
            $task = Tasks_Controller::getInstance()->createTask($this->_objects['task']);
        }
        
        // link task
        //print_r($task->toArray());
        Crm_Controller::getInstance()->setLinksForApplication($this->_objects['initialLead']->getId(), array($task->getId()), 'Tasks');
        
        // get linked tasks
        $linkedTasks = Crm_Controller::getInstance()->getLinksForApplication($this->_objects['initialLead']->getId(), 'Tasks');
        
        //print_r($linkedTasks);
        
        $this->assertGreaterThan(0, count($linkedTasks));
        $this->assertEquals($task->getId(), $linkedTasks[0]['recordId']);
        
    }

    /**
     * try to set / get linked contacts
     *
     * @todo use relations here
     */
    public function testLinkedContacts()
    {
        // create test contact
        try {
            $contact = Addressbook_Controller::getInstance()->getContact($this->_objects['user']->getId());
        } catch ( Exception $e ) {
            $contact = Addressbook_Controller::getInstance()->addContact($this->_objects['user']);
        }
        
        // link contact
        Crm_Controller::getInstance()->setLinksForApplication($this->_objects['initialLead']->getId(), array($contact->getId()), 'Addressbook', 'account');
        
        // get linked contacts
        $linkedContacts = Crm_Controller::getInstance()->getLinksForApplication($this->_objects['initialLead']->getId(), 'Addressbook');
        
        //print_r($linkedContacts);
        
        $this->assertGreaterThan(0, count($linkedContacts));
        $this->assertEquals($contact->getId(), $linkedContacts[0]['recordId']);
        
        // delete contact
        Addressbook_Controller::getInstance()->deleteContact($this->_objects['user']->getId());
    }
    
    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {
        Crm_Controller::getInstance()->deleteLead($this->_objects['initialLead']);

        $this->setExpectedException('UnderflowException');
        
        Crm_Controller::getInstance()->getLead($this->_objects['initialLead']);
    }
    
    /**
     * try to get the lead sources
     *
     */
    public function testGetLeadSources()
    {
        $sources = Crm_Controller::getInstance()->getLeadSources();
        
        $this->assertEquals(4, count($sources));
    }
    
    /**
     * try to get the lead types
     *
     */
    public function testGetLeadTypes()
    {
        $types = Crm_Controller::getInstance()->getLeadTypes();
        
        $this->assertEquals(3, count($types));
    }
    
    /**
     * try to get one lead type
     *
     */
    public function testGetLeadType()
    {
        $types = Crm_Controller::getInstance()->getLeadTypes();
        
        $type = Crm_Controller::getInstance()->getLeadType($types[0]->id);
        
        $this->assertType('Crm_Model_Leadtype', $type);
        $this->assertTrue($type->isValid());
    }
    
    /**
     * try to get all lead states
     *
     */
    public function testGetLeadStates()
    {
        $states = Crm_Controller::getInstance()->getLeadStates();
        
        $this->assertTrue(count($states) >= 6);
    }
    
    /**
     * try to get one lead state
     *
     */
    public function testGetLeadState()
    {
        $states = Crm_Controller::getInstance()->getLeadStates();
        
        $state = Crm_Controller::getInstance()->getLeadState($states[0]->id);
        
        $this->assertType('Crm_Model_Leadstate', $state);
        $this->assertTrue($state->isValid());
    }
    
}		
