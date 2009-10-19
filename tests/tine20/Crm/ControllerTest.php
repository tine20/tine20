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
            'own_backend'            => 'Sql',
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
            'own_backend'            => 'Sql',
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
        $backend->purgeAllRelations('Crm_Model_Lead', 'Sql', $this->_objects['initialLead']->getId());

        // delete contact
        Addressbook_Controller_Contact::getInstance()->delete($this->_objects['user']->getId());
        
        $this->setExpectedException('Tinebase_Exception_NotFound');        
        Crm_Controller_Lead::getInstance()->get($this->_objects['initialLead']);
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
        $backend = new Crm_Backend_Products();
        
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
