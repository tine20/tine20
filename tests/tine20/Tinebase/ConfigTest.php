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
     * test set config for app
     *
     */
    public function testSetConfigForApplication()
    {
        $config = $this->objects['config'];
        $configSet = $this->_instance->setConfigForApplication($config->name, $config->value);
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
     * test if config from config.inc.php overwrites config in db
     *
     */
    public function testConfigFromFileOverwrites()
    {
        if (! isset(Tinebase_Core::getConfig()->{'Overwrite Test'})) {
            // test disabled
            return;
        }
        
        $config = $this->objects['config'];
        $config->name = 'Overwrite Test';
        $configSet = $this->_instance->setConfigForApplication($config->name, $config->value);
        
        $configGet = $this->_instance->getConfig($configSet->name);
        $this->assertEquals(Tinebase_Core::getConfig()->{'Overwrite Test'}, $configGet->value);
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
     * test get config from config.inc.php
     *
     */
    public function testGetConfigFromFile()
    {
        $result = $this->_instance->getConfigAsArray('database');
            
        $this->assertGreaterThan(0, count($result), 'could not get db config');
        $this->assertTrue($result['dbname'] != '', 'could not get dbname');
    }
}
