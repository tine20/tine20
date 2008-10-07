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
    define('PHPUnit_MAIN_METHOD', 'Crm_Backend_LeadStatesTest::main');
}

/**
 * Test class for Crm_Backend_LeadStates
 */
class Crm_Backend_LeadStatesTest extends PHPUnit_Framework_TestCase
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
     * @var Crm_Backend_LeadStates
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm LeadStates Backend Tests');
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
            Tinebase_Model_Container::GRANT_EDIT
        );
        
        if($personalContainer->count() === 0) {
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialLeadState'] = new Crm_Model_Leadstate(array(
            'id' => 1000,
            'leadstate' => 'Just a unit test lead state',
            'probability' => 10,
            'endslead' => 0,
            'translate' => 0
        )); 
        
        $this->_backend = new Crm_Backend_LeadStates();
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
     * try to add a lead state
     */
    public function testAddLeadState()
    {
        $leadState = $this->_backend->create($this->_objects['initialLeadState']);
        
        $this->assertEquals($this->_objects['initialLeadState']->id, $leadState->id);
    }
    
    /**
     * try to get all lead states
     */
    public function testGetLeadStates()
    {
        $states = $this->_backend->getAll();
        
        $this->assertTrue(count($states) >= 6);
    }
    
    /**
     * try to get one lead state
     */
    public function testGetLeadState()
    {
        $states = $this->_backend->getAll();
        $state = $this->_backend->get($states[0]->id);
        
        $this->assertType('Crm_Model_Leadstate', $state);
        $this->assertTrue($state->isValid());
    }
    
    /**
     * try to delete a lead state
     */
    public function testDeleteLeadState()
    {
        $id = $this->_objects['initialLeadState']->getId();
        
        $this->_backend->delete($id);
        $this->setExpectedException('UnderflowException');
        $this->_backend->get($id);
    }
}


if (PHPUnit_MAIN_METHOD == 'Crm_Backend_LeadStatesTest::main') {
    Crm_Backend_LeadStatesTest::main();
}
