<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
    protected $objects = array();
    
    protected $testContainer;
    
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
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->objects['initialLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container'     => $this->testContainer->id,
            'start'         => Zend_Date::now(),
            'description'   => 'Description',
            'end'           => Zend_Date::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Zend_Date::now(),
        )); 
        
        $this->objects['updatedLead'] = new Crm_Model_Lead(array(
            'id'            => 20,
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
        $lead = Crm_Controller::getInstance()->addLead($this->objects['initialLead']);
        
        $this->assertEquals($this->objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->objects['initialLead']->description, $lead->description);
    }
    
    /**
     * try to get a lead
     *
     */
    public function testGetLead()
    {
        $lead = Crm_Controller::getInstance()->getLead($this->objects['initialLead']);
        
        $this->assertEquals($this->objects['initialLead']->id, $lead->id);
        $this->assertEquals($this->objects['initialLead']->description, $lead->description);
    }
    
    /**
     * try to update a lead
     *
     */
    public function testUpdateLead()
    {
        $lead = Crm_Controller::getInstance()->updateLead($this->objects['updatedLead']);
        
        $this->assertEquals($this->objects['updatedLead']->id, $lead->id);
        $this->assertEquals($this->objects['updatedLead']->description, $lead->description);
    }

    /**
     * try to get all leads
     *
     */
    public function testGetAllLeads()
    {
        $leads = Crm_Controller::getInstance()->getAllLeads('PHPUnit');
        
        $this->assertEquals(1, count($leads));
        $this->assertType('Tinebase_Record_RecordSet', $leads);
    }
    
    /**
     * try to get all shared leads
     *
     */
    public function testGetSharedLeads()
    {
        $leads = Crm_Controller::getInstance()->getSharedLeads('PHPUnit');
        
        $this->assertEquals(0, count($leads));
        $this->assertType('Tinebase_Record_RecordSet', $leads);
    }
    
    /**
     * try to get products associated with one lead
     *
     */
    public function testGetProductsByLeadId()
    {
        $products = Crm_Controller::getInstance()->getProductsByLeadId($this->objects['initialLead']);
        
        $this->assertType('Tinebase_Record_RecordSet', $products);
    }
    
    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {
        Crm_Controller::getInstance()->deleteLead($this->objects['initialLead']);

        $this->setExpectedException('UnderflowException');
        
        Crm_Controller::getInstance()->getLead($this->objects['initialLead']);
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
	

if (PHPUnit_MAIN_METHOD == 'Crm_ControllerTest::main') {
    Crm_ControllerTest::main();
}
