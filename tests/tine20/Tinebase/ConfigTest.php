<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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

    protected $_filenamesToDelete = array();
    
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
        foreach ($this->_filenamesToDelete as $filename) {
            unlink($filename);
        }
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
        
        if (! (isset($configData['Overwrite Test']) || array_key_exists('Overwrite Test', $configData))) {
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
        
        $this->assertFalse((isset($clientConfig->Tinebase['SMTP']) || array_key_exists('SMTP', $clientConfig->Tinebase)), 'SMTP is not a client config');
    }
    
    /**
     * test if config returns empty array if it's empty
     */
    public function testReturnEmptyValue()
    {
        // Hold original value for further tests of sieve.
        $keepOriginalValue = $this->_instance->get("sieve");
        
        // Ensure  sieve key is null
        $this->_instance->set("sieve", null);
        
        // If key is null it throws an exception, so return empty array if it's null.
        $this->assertTrue($this->_instance->get("sieve") instanceof Tinebase_Config_Struct);
        
        // Check common function of the getFunction
        $this->assertTrue(is_numeric($this->_instance->get("acceptedTermsVersion")));
        
        // restore value
        $this->_instance->set("sieve", $keepOriginalValue);
    }
    
    /**
     * testApplicationDefaultConfig
     */
    public function testApplicationDefaultConfig()
    {
        $ignoreBillablesConfig = Sales_Config::getInstance()->get(Sales_Config::IGNORE_BILLABLES_BEFORE);
        $this->assertEquals('2000-01-01 22:00:00', $ignoreBillablesConfig);
        
        $dest = $this->_getSalesCustomDefaultConfig();
        
        if (! file_exists($dest)) {
            copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'configtest.inc.php', $dest);
            $this->_filenamesToDelete[] = $dest;

            Tinebase_Cache_PerRequest::getInstance()->resetCache('Tinebase_Config_Abstract');
            
            $ignoreBillablesConfigAppDefault = Sales_Config::getInstance()->get(Sales_Config::IGNORE_BILLABLES_BEFORE);
            $this->assertEquals('1999-10-01 22:00:00', $ignoreBillablesConfigAppDefault);
        }
    }
    
    protected function _getSalesCustomDefaultConfig()
    {
        return dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20' . DIRECTORY_SEPARATOR . 'Sales' . DIRECTORY_SEPARATOR . 'config.inc.php';
    }
    
    /**
     * testFeatureEnabled
     * 
     * @see 0010756: add feature switches for easy enabling/disabling of features
     */
    public function testFeatureEnabled()
    {
        $customConfigFilename = $this->_getSalesCustomDefaultConfig();
        if (file_exists($customConfigFilename)) {
            $this->markTestSkipped('do not test with existing custom config');
        }
        
        $invoicesFeatureEnabled = Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE);
        
        $this->assertTrue($invoicesFeatureEnabled);
    }

    /**
     * testComposeConfigDir
     *
     * @see 0010988: load additional config from conf.d
     */
    public function testComposeConfigDir()
    {
        $confdfolder = Tinebase_Config::getInstance()->get(Tinebase_Config::CONFD_FOLDER);
        if (empty($confdfolder) || ! is_readable($confdfolder)) {
            $this->markTestSkipped('no confdfolder configured/readable');
        }

        $configValues = array('config1' => 'value1', 'config2' => 'value2');
        foreach ($configValues as $configName => $expectedValue) {
            $configValue = Tinebase_Config::getInstance()->get($configName);
            $this->assertEquals($expectedValue, $configValue);
        }

        $cachedConfigFilename = Tinebase_Core::guessTempDir() . DIRECTORY_SEPARATOR . 'cachedConfig.inc.php';
        $this->assertTrue(file_exists($cachedConfigFilename), 'cached config file does not exist: ' . $cachedConfigFilename);
    }

    /**
     * @see 0011456: unable to add new activesync-devices in tine20
     */
    public function testDefaultNull()
    {
        // TODO maybe we need to remove the current config if is set
        $defaultPolicy = ActiveSync_Config::getInstance()->get(ActiveSync_Config::DEFAULT_POLICY, null);
        $this->assertTrue(is_null($defaultPolicy), 'config should be null: ' . var_export($defaultPolicy, true));
    }
}
