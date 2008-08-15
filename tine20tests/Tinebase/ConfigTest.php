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
        $configSet = Tinebase_Config::getInstance()->setConfig($this->objects['config']);
        
        $configGet = Tinebase_Config::getInstance()->getConfig(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(), $configSet->name);
            
        $this->assertEquals($this->objects['config']->value, $configGet->value);
    }
        
    /**
     * test get applicaton config
     *
     */
    public function testGetApplicationConfig()
    {
        $result = Tinebase_Config::getInstance()->getConfigForApplication('Tinebase');
            
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
        $config = Tinebase_Config::getInstance()->getConfig(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(), $this->objects['config']->name);
        
        Tinebase_Config::getInstance()->deleteConfig($config);
            
        $this->setExpectedException('Exception');
        
        $config = Tinebase_Config::getInstance()->getConfig(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(), $this->objects['config']->name);        
    }
    
}
