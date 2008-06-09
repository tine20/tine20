<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        implement json tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Crm_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * container to use for the tests
     *
     * @var Tinebase_Model_Container
     */
    protected $container;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Json Tests');
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
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->objects['initialLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        )); 
        
        $this->objects['updatedLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
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
        
        $this->objects['contact'] = new Addressbook_Model_Contact(array(
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
        
        $this->objects['task'] = new Tasks_Model_Task(array(
            'id'                   => '90a75021e353685aa9a06e67a7c06e58d0acae32',
            'container_id'         => 5,
            'created_by'           => 6,
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'our fist test task',        
        ));
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
     * try to add a lead and link a contact
     *
     */
    public function testAddLead()
    {
        $json = new Crm_Json();
        
        $encodedData = Zend_Json::encode( $this->objects['initialLead']->toArray() );

        // create test contact
        try {
            $contact = Addressbook_Backend_Sql::getInstance()->getContact($this->objects['contact']->getId());
        } catch ( Exception $e ) {
            $contact = Addressbook_Backend_Sql::getInstance()->addContact($this->objects['contact']);
        }
        $contactLinks = array(array( 
            'recordId'  => $contact->getId(),
            'remark'    => 'responsible'
        ));

        // create test task
        $tasksBackend = new Tasks_Backend_Sql();        
        try {
            $task = $tasksBackend->getTask($this->objects['task']->getId());
        } catch ( Exception $e ) {
            $task = $tasksBackend->createTask($this->objects['task']);
        }
        $taskLinks = array($task->getId());
        
        $result = $json->saveLead($encodedData, Zend_Json::encode($contactLinks), Zend_Json::encode($taskLinks), Zend_Json::encode(array()));
        
        $this->assertTrue($result['success']); 
        $this->assertEquals($this->objects['initialLead']->description, $result['updatedData']['description']);
        $leadId = $result['updatedData']['id'];

        // check linked contacts
        $linkedContacts = Crm_Controller::getInstance()->getLinksForApplication($leadId, 'Addressbook');        
        $this->assertGreaterThan(0, count($linkedContacts));
        $this->assertEquals($contact->getId(), $linkedContacts[0]['recordId']);        

        // check linked tasks
        $linkedTasks = Crm_Controller::getInstance()->getLinksForApplication($leadId, 'Tasks');        
        $this->assertGreaterThan(0, count($linkedTasks));
        $this->assertEquals($task->getId(), $linkedTasks[0]['recordId']);        
    }

    /**
     * try to update a lead and remove linked contact 
     *
     */
    public function testUpdateLead()
    {   
        $json = new Crm_Json();
        $leads = Crm_Controller::getInstance()->getAllLeads($this->objects['initialLead']->lead_name);
        $initialLead = $leads[0];
        
        $updatedLead = $this->objects['updatedLead'];
        $updatedLead->id = $initialLead->getId();
        $encodedData = Zend_Json::encode( $updatedLead->toArray() );
        
        $result = $json->saveLead($encodedData, Zend_Json::encode(array()), Zend_Json::encode(array()), Zend_Json::encode(array()), Zend_Json::encode(array()), Zend_Json::encode(array()));
        
        //print_r($result['updatedData']);
        
        $this->assertTrue($result['success']); 
        $this->assertEquals($this->objects['updatedLead']->description, $result['updatedData']['description']);

        // check if contact is no longer linked        
        $linkedContacts = Crm_Controller::getInstance()->getLinksForApplication($initialLead->getId(), 'Addressbook');        
        $this->assertEquals(0, count($linkedContacts));        
        // delete contact
        Addressbook_Controller::getInstance()->deleteContact($this->objects['contact']->getId());

        // check if task is no longer linked        
        $linkedTasks = Crm_Controller::getInstance()->getLinksForApplication($initialLead->getId(), 'Tasks');        
        $this->assertEquals(0, count($linkedTasks));        
        // delete task
        //$tasksBackend = new Tasks_Backend_Sql();    
        //$tasksBackend->deleteTask($this->objects['task']->getId());
    }

    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {        
        $json = new Crm_Json();
        $leads = Crm_Controller::getInstance()->getAllLeads($this->objects['initialLead']->lead_name);
        
        //print_r($leads);
        $deleteIds = array();
        foreach ( $leads as $lead ) {
            $deleteIds[] = $lead->getId();    
        }
        
        $encodedLeadIds = Zend_Json::encode($deleteIds);
        
        $json->deleteLeads($encodedLeadIds);
                
        $leads = Crm_Controller::getInstance()->getAllLeads($this->objects['initialLead']->lead_name);
        $this->assertEquals(0, count($leads));     
    }    
}		
	