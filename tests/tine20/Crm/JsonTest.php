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
     */
    public function testGetRegistryData()
    {
        $registry = $this->_instance->getRegistryData();
        
        $types = array('leadtypes', 'leadstates', 'leadsources');
        
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
     * test get settings/config
     * 
     * @return void
     */
    public function testGetSettings()
    {
        $result = $this->_instance->getSettings();

        //print_r($result);
        
        $this->assertEquals(array('leadstates', 'leadtypes', 'leadsources', 'defaults'), array_keys($result));
        $this->assertEquals(6, count($result[Crm_Model_Config::LEADSTATES]));
        $this->assertEquals(3, count($result[Crm_Model_Config::LEADTYPES]));
        $this->assertEquals(4, count($result[Crm_Model_Config::LEADSOURCES]));
    }
    
    /**
     * test get settings/config
     * 
     * @return void
     */
    public function testSaveSettings()
    {
        $oldSettings = $this->_instance->getSettings();
        
        // change some settings
        $newSettings = $oldSettings;
        $newSettings['defaults']['leadstate_id'] = 2;
        $newSettings['leadsources'][] = array(
            'id' => 5,
            'leadsource' => 'Another Leadsource'
        );
        $anotherResult = $this->_instance->saveSettings(Zend_json::encode($newSettings));
        $this->assertEquals($anotherResult, $newSettings);
        
        // reset original settings
        $result = $this->_instance->saveSettings(Zend_json::encode($oldSettings));
        $this->assertEquals($oldSettings, $result);
        
        // test Crm_Model_Config::getOptionById
        $settings = Crm_Controller::getInstance()->getSettings();
        $this->assertEquals(array(), $settings->getOptionById(5, 'leadsources'));
    }
    
    /**
     * try to add a lead and link a contact
     *
     */
    public function testAddGetSearchDeleteLead()
    {
        // create lead with task and contact
        $contact    = $this->_getContact();
        $task       = $this->_getTask();
        $lead       = $this->_getLead();
        $product    = $this->_getProduct();
        
        $leadData = $lead->toArray();
        $leadData['relations'] = array(
            array('type'  => 'TASK',    'related_record' => $task->toArray()),
            array('type'  => 'PARTNER', 'related_record' => $contact->toArray()),
            array('type'  => 'PRODUCT', 'related_record' => $product->toArray(), 'remark' => array('price' => 200)),
        );
        // add note
        $note = array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',            
        );
        $leadData['notes'] = array($note);        
        
        $savedLead = $this->_instance->saveLead(Zend_Json::encode($leadData));
        $getLead = $this->_instance->getLead($savedLead['id']);
        $searchLeads = $this->_instance->searchLeads(Zend_Json::encode($this->_getLeadFilter()), '');
        
        //print_r($searchLeads);
        
        // assertions
        $this->assertEquals($getLead, $savedLead);
        $this->assertEquals($getLead['notes'][0]['note'], $note['note']);
        $this->assertTrue($searchLeads['totalcount'] > 0);
        $this->assertEquals($lead->description, $searchLeads['results'][0]['description']);
        $this->assertTrue(count($searchLeads['results'][0]['relations']) == 3, 'did not get all relations');     

        // get related records and check relations
        foreach ($searchLeads['results'][0]['relations'] as $relation) {
            switch ($relation['type']) {
                case 'PRODUCT':
                    //print_r($relation);
                    $this->assertEquals(200, $relation['remark']['price'], 'product price (remark) does not match');
                    $relatedProduct = $relation['related_record'];
                    break;
                case 'TASK':
                    $relatedTask = $relation['related_record'];
                    break;
                case 'PARTNER':
                    $relatedContact = $relation['related_record'];
                    break;
            }
        }
        $this->assertTrue(isset($relatedContact), 'contact not found');
        $this->assertEquals($contact->n_fn, $relatedContact['n_fn'], 'contact name does not match');
        $this->assertTrue(isset($relatedTask), 'task not found');
        $this->assertEquals($task->summary, $relatedTask['summary'], 'task summary does not match');
        $this->assertTrue(isset($relatedProduct), 'product not found');
        $this->assertEquals($product->name, $relatedProduct['name'], 'product name does not match');
         
        // delete all
        $this->_instance->deleteLeads($savedLead['id']);
        Addressbook_Controller_Contact::getInstance()->delete($relatedContact['id']);
        Sales_Controller_Product::getInstance()->delete($relatedProduct['id']);
        
        // check if delete worked
        $result = $this->_instance->searchLeads(Zend_Json::encode($this->_getLeadFilter()), '');
        $this->assertEquals(0, $result['totalcount']);   
        
        // check if linked task got removed as well
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $task = Tasks_Controller_Task::getInstance()->get($relatedTask['id']);
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
     * get product
     * 
     * @return Sales_Model_Product
     */
    protected function _getProduct()
    {
        return new Sales_Model_Product(array(
            'name'  => 'PHPUnit test product',
            'price' => 10000,        
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