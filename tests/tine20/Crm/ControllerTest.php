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
 * @todo        refactor controller tests
 * @todo        resolve test dependencies - make them _stand-alone_
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
    
    /**
     * test container
     *
     * @var Tinebase_Model_Container
     */
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
            Tinebase_Model_Container::GRANT_EDIT
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
            'container_id'     => $this->_testContainer->id,
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
            'container_id'     => $this->_testContainer->id,
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
            Tinebase_Model_Container::GRANT_EDIT
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
            'container_id'                 => $addressbookContainer->id,
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
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->_objects['task'] = new Tasks_Model_Task(array(
            // tine record fields
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
                    'price' => '19.78')),
                new Crm_Model_Product(array(
                    'id' => 1004,
                    'productsource' => 'Just a phpunit test product #4',
                    'price' => '20.07'))
        );
        
        // products to update
        $this->_objects['someProductsToUpdate'] = array(
                new Crm_Model_Product(array(
                    'id' => 1002,
                    'productsource' => 'Just a phpunit test product #2 UPDATED',
                    'price' => '18.05')),
                new Crm_Model_Product(array(
                    'id' => 1003,
                    'productsource' => 'Just a phpunit test product #3 UPDATED',
                    'price' => '19.78'))
        );
        
        // some lead types
        $this->_objects['someLeadTypes'] = array(
                new Crm_Model_Leadtype(array(
                    'id' => 1001,
                    'leadtype' => 'Just a phpunit test lead type #1',
                    'leadtype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1002,
                    'leadtype' => 'Just a phpunit test lead type #2',
                    'leadtype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1003,
                    'leadtype' => 'Just a phpunit test lead type #3',
                    'leadtype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1004,
                    'leadtype' => 'Just a phpunit test lead type #4',
                    'leadtype_translate' => 0))
        );
        
        // some lead types to update
        $this->_objects['someLeadTypesToUpdate'] = array(
                new Crm_Model_Leadtype(array(
                    'id' => 1002,
                    'leadtype' => 'Just a phpunit test lead type #2 UPDATED',
                    'leadtype_translate' => 0)),
                new Crm_Model_Leadtype(array(
                    'id' => 1003,
                    'leadtype' => 'Just a phpunit test lead type #3 UPDATED',
                    'leadtype_translate' => 0))
        );
        
        // some lead sources
        $this->_objects['someLeadSources'] = array(
                new Crm_Model_Leadsource(array(
                    'id' => 1001,
                    'leadsource' => 'Just a phpunit test lead source #1',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1002,
                    'leadsource' => 'Just a phpunit test lead source #2',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1003,
                    'leadsource' => 'Just a phpunit test lead source #3',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1004,
                    'leadsource' => 'Just a phpunit test lead source #4',
                    'translate' => 0))
        );
        
        // some lead sources to update
        $this->_objects['someLeadSourcesToUpdate'] = array(
                new Crm_Model_Leadsource(array(
                    'id' => 1002,
                    'leadsource' => 'Just a phpunit test lead source #2 UPDATED',
                    'translate' => 0)),
                new Crm_Model_Leadsource(array(
                    'id' => 1003,
                    'leadsource' => 'Just a phpunit test lead source #3 UPDATED',
                    'translate' => 0))
        );
        
        // some lead states
        $this->_objects['someLeadStates'] = array(
                new Crm_Model_Leadstate(array(
                    'id' => 1001,
                    'leadstate' => 'Just a phpunit test lead state #1',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1002,
                    'leadstate' => 'Just a phpunit test lead state #2',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1003,
                    'leadstate' => 'Just a phpunit test lead state #3',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1004,
                    'leadstate' => 'Just a phpunit test lead state #4',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1005,
                    'leadstate' => 'Just a phpunit test lead state #5',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0))
                
        );
        
        // some lead states to update
        $this->_objects['someLeadStatesToUpdate'] = array(
                new Crm_Model_Leadstate(array(
                    'id' => 1002,
                    'leadstate' => 'Just a phpunit test lead state #2 UPDATED',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0)),
                new Crm_Model_Leadstate(array(
                    'id' => 1003,
                    'leadstate' => 'Just a phpunit test lead state #3 UPDATED',
                    'probability' => 10,
                    'endslead' => 0,
                    'translate' => 0))
        );

        $this->objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
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
     * try to add a lead
     *
     */
    public function testAddLead()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $lead = $this->_objects['initialLead'];
        $lead->notes = new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note']));
        $lead = Crm_Controller_Lead::getInstance()->create($lead);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
        
        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord('Crm_Model_Lead', $lead->getId());
        
        //print_r($notes->toArray());
        $createdNoteType = Tinebase_Notes::getInstance()->getNoteTypeByName('created');
        foreach ($notes as $note) {
            if ($note->note_type_id === $createdNoteType->getId()) {
                $translatedMessage = $translate->_('created') . ' ' . $translate->_('by') . ' '; 
                $this->assertEquals($translatedMessage.Zend_Registry::get('currentAccount')->accountDisplayName, $note->note);
            } else {
                $this->assertEquals($this->objects['note']->note, $note->note);
            }
        }
    }
    
    /**
     * try to get a lead
     *
     */
    public function testGetLead()
    {
        $lead = Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }
    
    
    /**
     * try to update a lead
     *
     */
    public function testUpdateLead()
    {
        $lead = Crm_Controller_Lead::getInstance()->update($this->_objects['updatedLead']);
        
        $this->assertEquals($this->_objects['updatedLead']->id, $lead->id);
        $this->assertEquals($this->_objects['updatedLead']->description, $lead->description);
    }

    /**
     * try to get all leads and compare counts
     *
     */
    public function testGetAllLeads()
    {
        $filter = $this->_getFilter();
        
        $leads = Crm_Controller_Lead::getInstance()->search($filter);
        $count = Crm_Controller_Lead::getInstance()->searchCount($filter);
                
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
        $filter = $this->_getFilter('shared');
        $leads = Crm_Controller_Lead::getInstance()->search($filter);
        
        $this->assertEquals(0, count($leads));
        $this->assertType('Tinebase_Record_RecordSet', $leads);
    }
    
    /**
     * try to set / get linked tasks
     *
     */
    public function testLinkedTasks()
    {        
        $task = Tasks_Controller_Task::getInstance()->create($this->_objects['task']);
        
        // link task
        $lead = Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']->getId());
        $lead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => Crm_Backend_Factory::SQL,
            'own_id'                 => $this->_objects['initialLead']->getId(),
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => $task->getId(),
            'type'                   => 'TASK'
        )); 
        $lead = Crm_Controller_Lead::getInstance()->update($lead);
        
        // check linked tasks
        $updatedLead = Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']->getId());
        
        //print_r($updatedLead->toArray());
        
        $this->assertGreaterThan(0, count($updatedLead->relations));
        $this->assertEquals($task->getId(), $updatedLead->relations[0]->related_id);
        
    }

    /**
     * try to set / get linked contacts
     *
     * @deprecated when we have the update test in jsonTest, remove that
     */
    public function testLinkedContacts()
    {
        /*
        // create test contact
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->get($this->_objects['user']->getId());
        } catch ( Exception $e ) {
            $contact = Addressbook_Controller_Contact::getInstance()->create($this->_objects['user']);
        }
        
        // link contact
        $lead = Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']->getId());
        $lead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => Crm_Backend_Factory::SQL,
            'own_id'                 => $this->_objects['initialLead']->getId(),
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Addressbook_Model_Contact',
            'related_backend'        => Addressbook_Backend_Factory::SQL,
            'related_id'             => $contact->getId(),
            'type'                   => 'RESPONSIBLE'
        ));
         
        $lead = Crm_Controller_Lead::getInstance()->update($lead);
                
        // check linked contacts
        $updatedLead = Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']->getId());
        
        $this->assertGreaterThan(0, count($updatedLead->relations));
        $this->assertEquals($contact->getId(), $updatedLead->relations[0]->related_id);
        */
    }
    
    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {
        Crm_Controller_Lead::getInstance()->delete($this->_objects['initialLead']);

        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();        
        $backend->purgeAllRelations('Crm_Model_Lead', Crm_Backend_Factory::SQL, $this->_objects['initialLead']->getId());

        // delete contact
        Addressbook_Controller_Contact::getInstance()->delete($this->_objects['user']->getId());
        
        $this->setExpectedException('Tinebase_Exception_NotFound');        
        Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']);
    }
    
    /**
     * try to get the lead sources
     *
     */
    public function testGetLeadSources()
    {
        $sources = Crm_Controller_LeadSources::getInstance()->getLeadSources();
        
        $this->assertEquals(4, count($sources));
    }
    
    /**
     * try to get the lead types
     *
     */
    public function testGetLeadTypes()
    {
        $types = Crm_Controller_LeadTypes::getInstance()->getLeadTypes();
        
        $this->assertEquals(3, count($types));
    }
    
    /**
     * try to get one lead type
     *
     */
    public function testGetLeadType()
    {
        $types = Crm_Controller_LeadTypes::getInstance()->getLeadTypes();
        
        $type = Crm_Controller_LeadTypes::getInstance()->getLeadType($types[0]->id);
        
        $this->assertType('Crm_Model_Leadtype', $type);
        $this->assertTrue($type->isValid());
    }
    
    /**
     * try to get all lead states
     *
     */
    public function testGetLeadStates()
    {
        $states = Crm_Controller_LeadStates::getInstance()->getLeadStates();
        
        $this->assertTrue(count($states) >= 6);
    }
    
    /**
     * try to get one lead state
     *
     */
    public function testGetLeadState()
    {
        $states = Crm_Controller_LeadStates::getInstance()->getLeadStates();
        
        $state = Crm_Controller_LeadStates::getInstance()->getLeadState($states[0]->id);
        
        $this->assertType('Crm_Model_Leadstate', $state);
        $this->assertTrue($state->isValid());
    }
    
    /**
     * try to save / create, update and delete more than one product
     * 
     * @todo complete test for products to delete
     */
    public function testSaveProducts() {
    	// save db table content (because of test dependencies)
    	$savedProducts = Crm_Controller_LeadProducts::getInstance()->getProducts();
    	
    	// go!
    	$someProducts = new Tinebase_Record_RecordSet('Crm_Model_Product',
                $this->_objects['someProducts']);
        
        // save / create some products
        $resultProducts = Crm_Controller_LeadProducts::getInstance()
                ->saveProducts($someProducts);
        
        $this->assertEquals($someProducts->toArray(), $resultProducts->toArray());
        
        // get every saved product back from database one by one
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::PRODUCTS);
        
        foreach ($this->_objects['someProducts'] as $product) {
        	$this->assertEquals($product->toArray(), $backend->get($product->id)->toArray());
        }
        
        // update some products
        $someProducts = new Tinebase_Record_RecordSet('Crm_Model_Product',
                $this->_objects['someProductsToUpdate']);
        
        $resultProducts = Crm_Controller_LeadProducts::getInstance()
                ->saveProducts($someProducts);
        
        foreach ($this->_objects['someProductsToUpdate'] as $product) {
            $this->assertEquals($product['productsource'],
                    //$backend->get($product->id)->productsource);
                    Crm_Controller_LeadProducts::getInstance()->getProduct($product->id)->productsource);
        }
        
        // cleanup
        Crm_Controller_LeadProducts::getInstance()->saveProducts($savedProducts);
    }
    
    /**
     * try to save / create, update and delete more than one lead type
     * 
     * @todo complete test for lead types to delete
     */
    public function testSaveLeadTypes() {
        // save db table content (because of test dependencies)
        $savedLeadTypes = Crm_Controller_LeadTypes::getInstance()->getLeadTypes();
        
        // go!
    	//$someLeadTypes = new Tinebase_Record_RecordSet('Crm_Model_Leadtype', $this->_objects['someLeadTypes']);
    	// add test lead types to saved lead types
    	$someLeadTypes = clone($savedLeadTypes);
    	foreach ($this->_objects['someLeadTypes'] as $record) {
    	    $someLeadTypes->addRecord($record);
    	}
        
        // save / create some lead types
        $resultLeadTypes = Crm_Controller_LeadTypes::getInstance()
                ->saveLeadtypes($someLeadTypes);
        
        $this->assertEquals($someLeadTypes->toArray(), $resultLeadTypes->toArray());
        
        // get every saved lead type back from database one by one
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_TYPES);
        
        foreach ($this->_objects['someLeadTypes'] as $leadType) {
            $this->assertEquals($leadType->toArray(), $backend->get($leadType->id)->toArray());
        }
        
        // update some lead types
        // removed that because of the sql constraint for leads/leadtypes
        /*
        $someLeadTypes = new Tinebase_Record_RecordSet('Crm_Model_Leadtype',
                $this->_objects['someLeadTypesToUpdate']);
        
        $resultLeadTypes = Crm_Controller_LeadTypes::getInstance()
                ->saveLeadtypes($someLeadTypes);
        
        foreach ($this->_objects['someLeadTypesToUpdate'] as $leadType) {
            $this->assertEquals($leadType['leadtype'],
                    $backend->get($leadType->id)->leadtype);
        }
        */
        
        // cleanup
        Crm_Controller_LeadTypes::getInstance()->saveLeadtypes($savedLeadTypes);
    }
    
    /**
     * try to save / create, update and delete more than one lead source
     * 
     * @todo complete test for lead sources to delete
     */
    public function testSaveLeadSources() {
        // save db table content (because of test dependencies)
        $savedLeadSources = Crm_Controller_LeadSources::getInstance()->getLeadSources();
        
        // go!
        //$someLeadSources = new Tinebase_Record_RecordSet('Crm_Model_Leadsource', $this->_objects['someLeadSources']);
        // add test lead sources to saved lead types
        $someLeadSources = clone($savedLeadSources);
        foreach ($this->_objects['someLeadSources'] as $record) {
            $someLeadSources->addRecord($record);
        }
                
        // save / create some lead types
        $resultLeadSources = Crm_Controller_LeadSources::getInstance()
                ->saveLeadsources($someLeadSources);
        
        $this->assertEquals($someLeadSources->toArray(), $resultLeadSources->toArray());
        
        // get every saved lead source back from database one by one
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_SOURCES);
        
        foreach ($this->_objects['someLeadSources'] as $leadSource) {
            $this->assertEquals($leadSource->toArray(), $backend->get($leadSource->id)->toArray());
        }
        
        // update some lead sources
        // removed that because of the sql constraint for leads/sources
        /*
        $someLeadSources = new Tinebase_Record_RecordSet('Crm_Model_Leadsource',
                $this->_objects['someLeadSourcesToUpdate']);
        
        $resultLeadSources = Crm_Controller_LeadSources::getInstance()
                ->saveLeadsources($someLeadSources);
        
        foreach ($this->_objects['someLeadSourcesToUpdate'] as $leadSource) {
            $this->assertEquals($leadSource['leadsource'],
                    $backend->get($leadSource->id)->leadsource);
        }
        */
        
        // cleanup
        Crm_Controller_LeadSources::getInstance()->saveLeadsources($savedLeadSources);
    }
    
    /**
     * try to save / create, update and delete more than one lead state
     * 
     * @todo complete test for lead states to delete
     */
    public function testSaveLeadStates() {
        // save db table content (because of test dependencies)
        $savedLeadStates = Crm_Controller_LeadStates::getInstance()->getLeadStates();
        
        // go!
        //$someLeadStates = new Tinebase_Record_RecordSet('Crm_Model_Leadstate', $this->_objects['someLeadStates']);
        // add test lead states to saved lead types
        $someLeadStates = clone($savedLeadStates);
        foreach ($this->_objects['someLeadStates'] as $record) {
            $someLeadStates->addRecord($record);
        }
                
        // save / create some lead states
        $resultLeadStates = Crm_Controller_LeadStates::getInstance()
                ->saveLeadstates($someLeadStates);
        
        $this->assertEquals($someLeadStates->toArray(), $resultLeadStates->toArray());
        
        // get every saved lead state back from database one by one
        $backend = Crm_Backend_Factory::factory(Crm_Backend_Factory::LEAD_STATES);
        
        foreach ($this->_objects['someLeadStates'] as $leadState) {
            $this->assertEquals($leadState->toArray(), $backend->get($leadState->id)->toArray());
        }
        
        // update some lead states
        // removed that because of the sql constraint for leads/states
        /*        
        $someLeadStates = new Tinebase_Record_RecordSet('Crm_Model_Leadstate',
                $this->_objects['someLeadStatesToUpdate']);
        
        $resultLeadStates = Crm_Controller_LeadStates::getInstance()
                ->saveLeadstates($someLeadStates);
        
        foreach ($this->_objects['someLeadStatesToUpdate'] as $leadState) {
            $this->assertEquals($leadState['leadstate'],
                    $backend->get($leadState->id)->leadstate);
        }
        */
        
        // cleanup
        Crm_Controller_LeadStates::getInstance()->saveLeadstates($savedLeadStates);
    }

    /**
     * get lead filter
     *
     * @return Crm_Model_LeadFilter
     */
    protected function _getFilter($container = 'single')
    {
        $filterData = array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'PHPUnit'
            ),
        );
        $filterData[] = ($container == 'single') 
            ? array(
                'field' => 'container_id', 
                'operator' => 'equals', 
                'value' => $this->_testContainer->id
            ) 
            : array(
                'field' => 'container_id', 
                'operator' => 'specialNode', 
                'value' => $container
            ); 
        
        $filter = new Crm_Model_LeadFilter($filterData);
        
        $filter->createFilter('showClosed', 'equals', TRUE);

        return $filter;
    }
}
