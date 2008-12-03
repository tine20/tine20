<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_ConfigTest::main();
}

/**
 * Test class for Tinebase_Config
 */
class Tinebase_ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Config
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_ConfigTest');
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
        $this->_instance = Tinebase_Config::getInstance();
        
        $this->objects['config'] = new Tinebase_Model_Config(array(
            "application_id"    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            "name"              => "Test Name",
            "value"             => "Test value",              
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
     * test set config
     *
     */
    public function testSetConfig()
    {
        $configSet = $this->_instance->setConfig($this->objects['config']);
        
        $configGet = $this->_instance->getConfig($configSet->name);
            
        $this->assertEquals($this->objects['config']->value, $configGet->value);
    }
        
    /**
     * test get applicaton config
     *
     */
    public function testGetApplicationConfig()
    {
        $tinebase = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $result = $this->_instance->getConfigForApplication($tinebase);
            
        //print_r($result);    
            
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($this->objects['config']->value, $result[$this->objects['config']->name]);
    }

    /**
     * test delete config
     *
     */
    public function testDeleteConfig()
    {
        $config = $this->_instance->getConfig($this->objects['config']->name);
        
        $this->_instance->deleteConfig($config);
            
        $this->setExpectedException('Exception');
        
        $config = $this->_instance->getConfig($this->objects['config']->name);        
    }

    /**
     * test custom fields
     *
     * - add custom field
     * - get custom fields for app
     * - delete custom field
     */
    public function testCustomFields()
    {
        // create
        $customField = $this->_getCustomField();
        $createdCustomField = $this->_instance->addCustomField($customField);
        $this->assertEquals($customField->name, $createdCustomField->name);
        $this->assertNotNull($createdCustomField->getId());
        
        // fetch
        $application = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $appCustomFields = $this->_instance->getCustomFieldsForApplication(
            $application->getId()
        );
        $this->assertGreaterThan(0, count($appCustomFields));
        $this->assertEquals($application->getId(), $appCustomFields[0]->application_id);

        // check with model name
        $appCustomFieldsWithModelName = $this->_instance->getCustomFieldsForApplication(
            $application->getId(),
            $customField->model
        );
        $this->assertGreaterThan(0, count($appCustomFieldsWithModelName));
        $this->assertEquals($customField->model, $appCustomFieldsWithModelName[0]->model, 'didn\'t get correct model name');
        
        // delete
        $this->_instance->deleteCustomField($createdCustomField);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_instance->getCustomField($createdCustomField->getId());
    }
    
    /**
     * get custom field record
     *
     * @return Tinebase_Model_CustomField
     */
    protected function _getCustomField()
    {
        return new Tinebase_Model_CustomField(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'label'             => Tinebase_Record_Abstract::generateUID(),        
            'model'             => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Record_Abstract::generateUID(),
            'length'            => 10,        
        ));
    }
}
