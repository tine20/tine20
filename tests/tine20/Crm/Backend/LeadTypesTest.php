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
    define('PHPUnit_MAIN_METHOD', 'Crm_Backend_LeadTypesTest::main');
}

/**
 * Test class for Crm_backend_LeadTypes
 */
class Crm_backend_LeadTypesTest extends PHPUnit_Framework_TestCase
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
     * @var Crm_Backend_LeadTypes
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm Lead Types Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
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
        
        $this->_objects['initialLeadType'] = new Crm_Model_Leadtype(array(
            'id' => 1000,
            'leadtype' => 'Just a unit test type',
            '0'
        )); 
        
        $this->_backend = new Crm_Backend_LeadTypes();
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
     * try to add a lead type
     */
    public function testAddLeadType()
    {
        $leadType = $this->_backend->create($this->_objects['initialLeadType']);
        
        $this->assertEquals($this->_objects['initialLeadType']->id, $leadType->id);
    }
    
    /**
     * try to get all lead types
     */
    public function testGetLeadTypes()
    {
        $types = $this->_backend->getAll();
        
        $this->assertTrue(count($types) >= 3);
    }
    
    /**
     * try to get one lead type
     */
    public function testGetLeadType()
    {
        $types = $this->_backend->getAll();
        $type = $this->_backend->get($types[0]->id);
        
        $this->assertType('Crm_Model_Leadtype', $type);
        $this->assertTrue($type->isValid());
    }
    
    /**
     * try to delete a lead type
     */
    public function testDeleteLeadType()
    {
        $id = $this->_objects['initialLeadType']->getId();
        
        $this->_backend->delete($id);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_backend->get($id);
    }
}


if (PHPUnit_MAIN_METHOD == 'Crm_Backend_LeadTypesTest::main') {
    Crm_Backend_LeadTypesTest::main();
}
