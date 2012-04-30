<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * test instance retrival
     * 
     */
    public function testConfigInstance()
    {
        $this->assertTrue($this->_instance === Tinebase_Core::getConfig(), 'Tinebase_Core::getConfig() is wrong instance');
    }
    
    /**
     * test basic config getting/setting/deleting cycle
     */
    public function testSetDeleteConfig()
    {
        $this->_instance->set(Tinebase_Config::PAGETITLEPOSTFIX, 'phpunit');
        $this->assertEquals('phpunit', $this->_instance->{Tinebase_Config::PAGETITLEPOSTFIX}, 'could not set config');
        
        $this->_instance->delete(Tinebase_Config::PAGETITLEPOSTFIX, 'phpunit');
        
        $this->assertEquals('###PHPUNIT-NOTSET###', $this->_instance->get(Tinebase_Config::PAGETITLEPOSTFIX, '###PHPUNIT-NOTSET###'), 'config got not deleted');
        
        $this->assertFalse(isset($this->_instance->{Tinebase_Config::PAGETITLEPOSTFIX}), '__isset not working');
    }
    
    /**
     * test if config from config.inc.php overwrites config in db
     *
     */
    public function testConfigFromFileOverwrites()
    {
        $configData = include('config.inc.php');
        
        if (! array_key_exists('Overwrite Test', $configData)) {
            $this->markTestSkipped('config.inc.php has no test key "Overwrite Test"');
            return;
        }
        
        $configFileValue = $configData['Overwrite Test'];
        $overwrittenValue = Tinebase_Record_Abstract::generateUID();
        $this->_instance->{'Overwrite Test'} = $overwrittenValue;
        
        $this->assertEquals($configData['Overwrite Test'], $this->_instance->{'Overwrite Test'});
        
        $this->_instance->delete('Overwrite Test');
    }
    
    /**
     * test get config from config.inc.php
     *
     */
    public function testGetConfigFromFile()
    {
        $dbConfig = $this->_instance->database;
        
        $this->assertGreaterThan(0, count($dbConfig), 'could not get db config');
        $this->assertTrue($dbConfig['dbname'] != '', 'could not get dbname');
    }
    
    /**
     * test config value is a struct
     *
     */
    public function testConfigTypeStruct()
    {
        $dbConfig = $this->_instance->database;
        
        $this->assertTrue($dbConfig instanceof Tinebase_Config_Struct, 'db config is not a struct');
        $this->assertTrue($dbConfig['dbname'] != '', 'could not get dbname via arrayAccess');
        $this->assertTrue($dbConfig->dbname != '', 'could not get dbname via objectAccess');
    }
    
    /**
     * test client config retrival
     * 
     */
    public function testGetClientRegistryConfig()
    {
        $clientConfig = $this->_instance->getClientRegistryConfig();
        $this->assertTrue($clientConfig instanceof Tinebase_Config_Struct, 'clientconfig is not a struct');
        $this->assertTrue($clientConfig->Calendar instanceof Tinebase_Config_Struct, 'calendar clientconfig is not a struct');
        $this->assertEquals(Calendar_Config::getInstance()->fixedCalendars, $clientConfig->Calendar->fixedCalendars->value, 'fixed calendar config not correct');
        
        $this->assertFalse(array_key_exists('SMTP', $clientConfig->Tinebase), 'SMTP is not a client config');
    }
}
