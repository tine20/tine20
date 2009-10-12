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
 * @todo        remove obsolete state/type/products/source tests (when the functions are removed from Json.php)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_JsonTest::main');
}

/**
 * Test class for Crm_Json
 */
class Crm_JsonTest extends Crm_AbstractTest
{
    /**
     * Backend
     *
     * @var Crm_Frontend_Json
     */
    protected $_instance;
    
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
        $this->_instance = new Crm_Frontend_Json();
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
     * test get crm registry
     * 
     * @return void
     * @todo check products as well
     */
    public function testGetRegistryData()
    {
        $registry = $this->_instance->getRegistryData();
        
        $types = array('leadtypes', 'leadstates', 'leadsources'/*, 'products' */);
        
        // check data
        foreach ($types as $type) {
            $this->assertGreaterThan(0, $registry[$type]['totalcount']);
            $this->assertGreaterThan(0, count($registry[$type]['results']));
        }
        
        // check defaults
        $this->assertEquals(array(
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
        ), array(
            'leadstate_id' => $registry['defaults']['leadstate_id'],
            'leadtype_id' => $registry['defaults']['leadtype_id'],
            'leadsource_id' => $registry['defaults']['leadsource_id'],
        ));
        $this->assertEquals(
            Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Crm')->getId(),
            $registry['defaults']['container_id']['id']
        );
        //print_r($registry);
    }
    
    /**
     * try to add a lead and link a contact
     *
     * @todo add note and product
     */
    public function testAddGetSearchDeleteLead()
    {
        // create lead with task and contact
        $contact = $this->_getContact();
        $task = $this->_getTask();
        $lead = $this->_getLead();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',    'related_record' => $task->toArray()),
            array('type'  => 'PARTNER', 'related_record' => $contact->toArray()),
        );
        
        $savedLead = $this->_instance->saveLead(Zend_Json::encode($leadData));
        
        //print_r($savedLead);
        
        $searchLeads = $this->_instance->searchLeads(Zend_Json::encode($this->_getLeadFilter()), '');
        
        //print_r($searchLeads);
        
        // assertions
        $this->assertTrue($searchLeads['totalcount'] > 0);
        $this->assertEquals($lead->description, $searchLeads['results'][0]['description']);
        $this->assertTrue(count($searchLeads['results'][0]['relations']) == 2, 'did not get all relations');       
        $this->assertEquals($contact->n_fn, $searchLeads['results'][0]['relations'][0]['related_record']['n_fn'], 'contact not found');
        $this->assertEquals($task->summary, $searchLeads['results'][0]['relations'][1]['related_record']['summary'], 'task not found');
         
        // delete all
        $this->_instance->deleteLeads($savedLead['id']);
        Addressbook_Controller_Contact::getInstance()->delete($savedLead['relations'][0]['related_id']);
        
        // check if delete worked
        $result = $this->_instance->searchLeads(Zend_Json::encode($this->_getLeadFilter()), '');
        $this->assertEquals(0, $result['totalcount']);   
        
        // check if linked task got removed as well
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $task = Tasks_Controller_Task::getInstance()->get($savedLead['relations'][1]['related_id']);
        
        // obsolete / only as reminder
        /*
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',            
        );
        $leadData['notes'] = array($note);        
        
        $leadData['products'] = array($this->objects['productLink']);
        
        // check linked contacts / tasks
        $this->assertGreaterThan(0, count($result['relations']));
        $this->assertEquals($this->objects['contact']->getId(), $result['relations'][0]['related_id']);
        $this->assertEquals($GLOBALS['Crm_JsonTest']['taskId'], $result['relations'][1]['related_id']);

        // check linked products
        $this->assertGreaterThan(0, count($result['products']), 'products are missing!');
        $this->assertEquals($this->objects['productLink']['product_desc'], $result['products'][0]['product_desc']);
        
        // check notes
        $createdNoteType = Tinebase_Notes::getInstance()->getNoteTypeByName('created');
        foreach ($result['notes'] as $leadNote) {
            if ($leadNote['note_type_id'] !== $createdNoteType->getId()) {
                $this->assertEquals($note['note'], $leadNote['note']);
            }
        } 
        */                      
    }
    
    /**
     * try to update a lead and remove linked contact 
     *
     * @todo add update test again
     */
    public function testUpdateLead()
    {
        /*
        $result = $this->_instance->searchLeads(Zend_Json::encode($this->objects['filter']), Zend_Json::encode(array()));        
        $initialLeadId = $result['results'][0]['id'];
        
        $initialLead = $this->_instance->getLead($initialLeadId);
        
        $updatedLead = $this->objects['updatedLead'];
        $updatedLead->id = $initialLead['id'];
        // unset contact
        unset($initialLead['relations'][0]);
        
        //print_r($initialLead['relations']);
        
        $updatedLead->relations = new Tinebase_Record_Recordset('Tinebase_Model_Relation', $initialLead['relations']);
        
        //print_r($updatedLead->toArray());
        
        $encodedData = Zend_Json::encode($updatedLead->toArray());
        
        $result = $this->_instance->saveLead($encodedData);
        
        $this->assertEquals($this->objects['updatedLead']->description, $result['description']);

        // check if contact is no longer linked
        $lead = Crm_Controller_Lead::getInstance()->get($initialLead['id']);
        $this->assertEquals(1, count($lead->relations));
        */
    }

    /**
     * test leadsources
     * 
     * @deprecated
     */
    public function testLeadSources()
    {
        // test getLeadsources
        $leadsources = $this->_instance->getLeadsources('id', 'ASC');
        $this->assertEquals(4, $leadsources['totalcount']);

        /*
        // test saveLeadsources
        $this->_instance->saveLeadsources(Zend_Json::encode($leadsources['results']));

        $leadsourcesUpdated = $this->_instance->getLeadsources('id', 'ASC');
        $this->assertEquals(4, $leadsourcesUpdated['totalcount']);
        */
    }

    /**
     * test leadstates
     * 
     * @deprecated
     */
    public function testLeadStates()
    {
        // test getLeadstates
        $leadstates = $this->_instance->getLeadstates('id', 'ASC');
        $this->assertEquals(6, $leadstates['totalcount']);

        /*
        // test saveLeadstates
        $this->_instance->saveLeadstates(Zend_Json::encode($leadstates['results']));

        $leadstatesUpdated = $this->_instance->getLeadstates('id', 'ASC');
        $this->assertEquals(6, $leadstatesUpdated['totalcount']);
        */
    }

    /**
     * test leadtypes
     * 
     * @deprecated
     */
    public function testLeadTypes()
    {
        // test getLeadtypes
        $leadtypes = $this->_instance->getLeadtypes('id', 'ASC');
        $this->assertEquals(3, $leadtypes['totalcount']);

        /*
        // test saveLeadtypes
        $this->_instance->saveLeadtypes(Zend_Json::encode($leadtypes['results']));

        $leadtypesUpdated = $this->_instance->getLeadtypes('id', 'ASC');
        $this->assertEquals(3, $leadtypesUpdated['totalcount']);
        */
    }

    /**
     * test products
     * 
     * @deprecated
     */
    public function testProducts()
    {
        /*
        // test getProducts
        $products = $this->_instance->getProducts('id', 'ASC');

        // test saveProducts
        $this->_instance->saveProducts(Zend_Json::encode($products['results']));

        $productsUpdated = $this->_instance->getProducts('id', 'ASC');
        $this->assertEquals($products['totalcount'], $productsUpdated['totalcount']);
        */
    }
    
    /**
     * get contact
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return new Addressbook_Model_Contact(array(
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
            'note'                  => 'Bla Bla Bla',
            //'container_id'          => $addressbookContainer->id,
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
    }

    /**
     * get task
     * 
     * @return Tasks_Model_Task
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            //'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Zend_Date::now(),
            'percent'              => 70,
            'due'                  => Zend_Date::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));        
    }
    
    /**
     * get lead
     * 
     * @return Crm_Model_Lead
     */
    protected function _getLead()
    {
        return new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), 'Crm')->getId(),
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        ));
    }
    
    /**
     * get lead filter
     * 
     * @return array
     */
    protected function _getLeadFilter()
    {
        return array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit'),
        );
        
    }
}		