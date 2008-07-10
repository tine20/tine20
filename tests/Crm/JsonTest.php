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
 * @todo        simplify relations tests: create related_records with relations class
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
        
        $tasksPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            'Tasks', 
            Zend_Registry::get('currentAccount'), 
            Tinebase_Container::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->objects['task'] = new Tasks_Model_Task(array(
            // tine record fields
            'id'                   => '90a75021e353685aa9a06e67a7c0b558d0acae32',
            'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
        
        // define filter
        $this->objects['filter'] = array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'lead_name',
            'dir' => 'ASC',
            'containerType' => 'all',
            'query' => $this->objects['initialLead']->lead_name     
        );

        $this->objects['productLink'] = array(
            'product_id'        => 1001,
            'product_desc'      => 'test product',
            'product_price'     => 4000.44
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
     * try to add a lead and link a contact
     *
     * @todo move creation of task & contact to relations class (via related_record)
     */
    public function testAddLead()
    {
        $json = new Crm_Json();
        
        // create test contact
        try {
            $contact = Addressbook_Controller::getInstance()->getContact($this->objects['contact']->getId());
        } catch ( Exception $e ) {
            $contact = Addressbook_Controller::getInstance()->createContact($this->objects['contact']);
        }

        // create test task
        try {
            $task = Tasks_Controller::getInstance()->getTask($this->objects['task']->getId());
        } catch ( Exception $e ) {
            $task = Tasks_Controller::getInstance()->createTask($this->objects['task']);
        }

        $leadData = $this->objects['initialLead']->toArray();
        $leadData['relations'] = array(
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => Crm_Backend_Factory::SQL,
                'own_id'                 => $this->objects['initialLead']->getId(),
                'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Tasks_Model_Task',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $this->objects['task']->getId(),
                'type'                   => 'TASK',
                //'related_record'         => $this->objects['task']->toArray()
            ),
            array(
                'own_model'              => 'Crm_Model_Lead',
                'own_backend'            => Crm_Backend_Factory::SQL,
                'own_id'                 => $this->objects['initialLead']->getId(),
                'own_degree'             => Tinebase_Relation_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Addressbook_Model_Contact',
                'related_backend'        => Addressbook_Backend_Factory::SQL,
                'related_id'             => $this->objects['contact']->getId(),
                'type'                   => 'RESPONSIBLE',
                //'related_record'         => $this->objects['contact']->toArray()
            )        
        );
        $leadData['tags'] = Zend_Json::encode(array());
        $leadData['products'] = array($this->objects['productLink']);
        
        $encodedData = Zend_Json::encode($leadData);
        
        $result = $json->saveLead($encodedData);

        //print_r ( $result );
        
        $this->assertTrue($result['success'], 'saving of lead failed'); 
        $this->assertEquals($this->objects['initialLead']->description, $result['updatedData']['description']);
        $leadId = $result['updatedData']['id'];

        // check linked contacts / tasks
        //print_r($result['updatedData']['relations']);
        $this->assertGreaterThan(0, count($result['updatedData']['relations']));
        $this->assertEquals($this->objects['contact']->getId(), $result['updatedData']['relations'][0]['related_id']);
        $this->assertEquals($this->objects['task']->getId(), $result['updatedData']['relations'][1]['related_id']);

        // check linked products
        $this->assertGreaterThan(0, count($result['updatedData']['products']));
        $this->assertEquals($this->objects['productLink']['product_desc'], $result['updatedData']['products'][0]['product_desc']);
    }

    /**
     * try to get a lead (test searchLeads as well)
     *
     */
    public function testGetLead()    
    {
        $json = new Crm_Json();
        
        $result = $json->searchLeads(Zend_Json::encode($this->objects['filter']));
        $leads = $result['results'];
        $initialLead = $leads[0];
        
        $lead = $json->getLead($initialLead['id']);
        
        //print_r($lead);
        
        $this->assertEquals($lead['description'], $this->objects['initialLead']->description);        
        $this->assertEquals($lead['relations'][0]['related_record']['assistent'], $this->objects['contact']->assistent);                
        $this->assertEquals($lead['products'][0]['product_desc'], $this->objects['productLink']['product_desc']);
    }

    /**
     * try to get all leads
     *
     */
    public function testGetLeads()    
    {
        $json = new Crm_Json();
        
        $result = $json->searchLeads(Zend_Json::encode($this->objects['filter']));
        $leads = $result['results'];
        $initialLead = $leads[0];

        $this->assertEquals($this->objects['initialLead']->description, $initialLead['description']);        
        $this->assertEquals($this->objects['contact']->assistent, $initialLead['relations'][0]['related_record']['assistent']);
    }
    
    /**
     * try to update a lead and remove linked contact 
     *
     * @todo add new task here
     */
    public function testUpdateLead()
    {   
        $json = new Crm_Json();

        $result = $json->searchLeads(Zend_Json::encode($this->objects['filter']));        
        $initialLead = $result['results'][0];
        
        $updatedLead = $this->objects['updatedLead'];
        $updatedLead->id = $initialLead['id'];
        $updatedLead->relations = array();
        $encodedData = Zend_Json::encode($updatedLead->toArray());
        
        $result = $json->saveLead($encodedData);
        
        //print_r($result['updatedData']);
        
        $this->assertTrue($result['success']); 
        $this->assertEquals($this->objects['updatedLead']->description, $result['updatedData']['description']);

        // check if tasks/contact are no longer linked
        $lead = Crm_Controller::getInstance()->getLead($initialLead['id']);
        $this->assertEquals(0, count($lead->relations));
        
        // delete contact
        Addressbook_Controller::getInstance()->deleteContact($this->objects['contact']->getId());

    }

    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {        
        $json = new Crm_Json();
        $result = $json->searchLeads(Zend_Json::encode($this->objects['filter']));        

        $deleteIds = array();
        
        $backend = new Tinebase_Relation_Backend_Sql();        
        foreach ($result['results'] as $lead) {
            $deleteIds[] = $lead['id'];

            // purge all relations
            $backend->purgeAllRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $lead['id']);
        }
        
        //print_r($deleteIds);
        
        $encodedLeadIds = Zend_Json::encode($deleteIds);
        
        $json->deleteLeads($encodedLeadIds);        
                
        $result = $json->searchLeads(Zend_Json::encode($this->objects['filter']));
        $this->assertEquals(0, $result['totalcount']);   

    }    
}		
	