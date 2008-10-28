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
 * @todo Write test for lead source deletion.
 * @todo Write test for saving a lead source.
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_Backend_LeadSourcesTest::main');
}

/**
 * Test class for Crm_Backend_LeadSources
 */
class Crm_Backend_LeadSourcesTest extends PHPUnit_Framework_TestCase
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
     * @var Crm_Backend_LeadSources
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm LeadSources Backend Tests');
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
            $this->testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialLeadSource'] = new Crm_Model_Leadsource(array(
            'id' => 1000,
            'leadsource' => 'Just a unit test lead source.',
            'translate' => 0
        )); 
        
        $this->_backend = new Crm_Backend_LeadSources();
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
     * try to add a lead source
     */
    public function testAddLeadSource()
    {
        $source = $this->_backend->create($this->_objects['initialLeadSource']);
        
        $this->assertEquals($this->_objects['initialLeadSource']->id, $source->id);
    }
    
    /**
     * try to get one lead source
     */
    public function testGetLeadSource()
    {
    	$sources = $this->_backend->getAll();
        // $source = $this->_backend->get(1);
        $source = $this->_backend->get($sources[0]->id);
        
        $this->assertType('Crm_Model_Leadsource', $source);
        $this->assertTrue($source->isValid());
    }
    
    /**
     * try to get the lead sources
     */
    public function testGetLeadSources()
    {
        $sources = $this->_backend->getAll();
        
        $this->assertTrue(count($sources) >= 4);
    }
    
    /**
     * try to delete a lead source
     */
    public function testDeleteLeadSource()
    {
        $id = $this->_objects['initialLeadSource']->getId();
        
        $this->_backend->delete($id);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_backend->get($id);
    }
}		
	

if (PHPUnit_MAIN_METHOD == 'Crm_Backend_LeadSourcesTest::main') {
    Crm_Backend_LeadSourcesTest::main();
}
