<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Backend_SqlTest::main');
}

/**
 * Test class for Crm_Backend_Leads
 */
class Crm_Backend_LeadsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Fixtures
     * 
     * @var array test objects
     */
	protected $_objects = array();
    
    /**
     * Testcontainer
     *
     * @var unknown_type
     */
	protected $_testContainer;
    
    /**
     * Backend
     *
     * @var Crm_Backend_Leads
     */
	protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Leads Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
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
            'id'            => 120,
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
            'id'            => 120,
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
        
        $this->_backend = new Crm_Backend_Leads();
    }

    /**
     * Tears down the fixture
     * 
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
	   #Tinebase_Container::getInstance()->deleteContainer($this->testContainer->id);
    }
    
    /**
     * try to add a lead
     */
    public function testAddLead()
    {
        $lead = $this->_backend->create($this->_objects['initialLead']);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }

    /**
     * try to get a lead
     */
    public function testGetLead()
    {
        $lead = $this->_backend->get($this->_objects['initialLead']);
        
        $this->assertEquals($this->_objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }

    /**
     * try to get initial lead with search function
     */
    public function testGetInitialLead()
    {
        $filter = new Crm_Model_LeadFilter();
        $filter->container = array($this->_testContainer->id);
        $filter->query = 'PHPUnit';
        $filter->showClosed = true;
        $leads = $this->_backend->search($filter);
        
        $this->assertEquals(1, count($leads));
    }
    
    /**
     * try to update a lead
     */
    public function testUpdateLead()
    {
        $lead = $this->_backend->updateLead($this->_objects['updatedLead']);
        
        $this->assertEquals($this->_objects['updatedLead']->id, $lead->id);
        $this->assertEquals($this->_objects['updatedLead']->description, $lead->description);
    }

    /**
     * try to get initial lead with search function
     */
    public function testGetUpdatedLead()
    {
        $filter = new Crm_Model_LeadFilter();
        $filter->container = array($this->_testContainer->id);
        $filter->query = 'PHPUnit';
        $pagination = new Crm_Model_LeadPagination();
        $leads = $this->_backend->search($filter, $pagination);
        
        $this->assertEquals(1, count($leads));
    }
    
    /**
     * try to get count of leads
     */
    public function testGetCountOfLeads()
    {
        $filter = new Crm_Model_LeadFilter();
        $filter->container = array($this->_testContainer->id);
        $filter->query = 'PHPUnit';
        $filter->showClosed = true;
        $count = $this->_backend->searchCount($filter);
        
        $this->assertEquals(1, $count);
    }
    
    /**
     * try to delete a contact
     */
    public function testDeleteLead()
    {
    	$id = $this->_objects['initialLead']->getId();
    	
        $this->_backend->delete($id);
        $this->setExpectedException('UnderflowException');
        $this->_backend->get($id);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Crm_Backend_LeadsTest::main') {
    Crm_Backend_LeadsTest::main();
}
