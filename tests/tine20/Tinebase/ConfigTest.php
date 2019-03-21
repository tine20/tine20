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
     *
     * @var Tinebase_Config
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $objects = array();

    protected $_filenamesToDelete = array();

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

        Tinebase_Config::getInstance()->clearCache();
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

        $this->_instance->delete(Tinebase_Config::PAGETITLEPOSTFIX);

        $this->assertEquals('###PHPUNIT-NOTSET###', $this->_instance->get(Tinebase_Config::PAGETITLEPOSTFIX, '###PHPUNIT-NOTSET###'), 'config got not deleted');

        $this->assertFalse(isset($this->_instance->{Tinebase_Config::PAGETITLEPOSTFIX}), '__isset not working');
    }

    /**
     * test if config from config.inc.php overwrites config in db
     *
     */
    public function testConfigFromFileOverwrites()
    {
        /** @noinspection PhpIncludeInspection */
        $configData = include('config.inc.php');

        if (!(isset($configData['Overwrite Test']) || array_key_exists('Overwrite Test', $configData))) {
            $this->markTestSkipped('config.inc.php has no test key "Overwrite Test"');
            return;
        }

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
        $this->assertTrue($this->_instance->get("sieve") instanceof Tinebase_Config_Struct, 'sieve is: ' . print_r($this->_instance->get("sieve"), true));

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
        $defaultConfigFile = $this->_getExampleApplicationCustomDefaultConfig();

        $this->assertFalse(file_exists($defaultConfigFile), 'test needs to be recoded because Example Application default config exists');

        $exampleString = ExampleApplication_Config::getInstance()->get(ExampleApplication_Config::EXAMPLE_STRING);
        $this->assertEquals(ExampleApplication_Config::EXAMPLE_STRING, $exampleString);

        copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'configtest.inc.php', $defaultConfigFile);
        $this->_filenamesToDelete[] = $defaultConfigFile;

        ExampleApplication_Config::getInstance()->clearCache();
        ExampleApplication_Config::destroyInstance();

        $exampleString = ExampleApplication_Config::getInstance()->get(ExampleApplication_Config::EXAMPLE_STRING);
        $this->assertEquals('something else', $exampleString);
        $this->assertEquals(789, ExampleApplication_Config::getInstance()
            ->{ExampleApplication_Config::EXAMPLE_MAILCONFIG}->{ExampleApplication_Config::SMTP}
            ->{ExampleApplication_Config::PORT});
        $this->assertEquals('localhost', ExampleApplication_Config::getInstance()
            ->{ExampleApplication_Config::EXAMPLE_MAILCONFIG}->{ExampleApplication_Config::SMTP}
            ->{ExampleApplication_Config::HOST});
    }

    protected function _getExampleApplicationCustomDefaultConfig()
    {
        return dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20'
        . DIRECTORY_SEPARATOR . 'ExampleApplication' . DIRECTORY_SEPARATOR . 'config.inc.php';
    }

    /**
     * testFeatureEnabled
     *
     * @see 0010756: add feature switches for easy enabling/disabling of features
     */
    public function testFeatureEnabled()
    {
        $customConfigFilename = $this->_getExampleApplicationCustomDefaultConfig();

        $this->assertFalse(file_exists($customConfigFilename), 'test needs to be recoded because Example Application default config exists');

        $exampleFeatureEnabled = ExampleApplication_Config::getInstance()->featureEnabled(ExampleApplication_Config::EXAMPLE_FEATURE);

        $this->assertTrue($exampleFeatureEnabled);
    }

    /**
     * testComposeConfigDir
     *
     * @see 0010988: load additional config from conf.d
     */
    public function testComposeConfigDir()
    {
        $confdfolder = rtrim(Tinebase_Config::getInstance()->get(Tinebase_Config::CONFD_FOLDER), '/');
        if (empty($confdfolder) || !is_dir($confdfolder) || !is_writable($confdfolder)) {
            static::markTestSkipped('confd folder not defined or not a writeable folder');
        }

        try {
            static::assertTrue(copy(__DIR__ . '/files/conf.d/config1.inc.php', $confdfolder . '/config1.inc.php'));
            static::assertTrue(copy(__DIR__ . '/files/conf.d/config2.inc.php', $confdfolder . '/config2.inc.php'));
            Tinebase_Config::getInstance()->clearCache();

            $configValues = array('config1' => 'value1', 'config2' => 'value2');
            foreach ($configValues as $configName => $expectedValue) {
                $configValue = Tinebase_Config::getInstance()->get($configName);
                $this->assertEquals($expectedValue, $configValue);
            }

            $cachedConfigFilename = Tinebase_Core::guessTempDir() . DIRECTORY_SEPARATOR . 'cachedConfig.inc.php';
            $this->assertTrue(file_exists($cachedConfigFilename),
                'cached config file does not exist: ' . $cachedConfigFilename);
        } finally {
            unlink($confdfolder . '/config1.inc.php');
            unlink($confdfolder . '/config2.inc.php');
        }
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

    /**
     * set + get bool config
     */
    public function testBoolConfig()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);

        $this->assertEquals(true, Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME});
    }

    public function testConfDFolder()
    {
        $config = Tinebase_Core::getConfig();
        // having the clearCache here is part of the test! Leave it here!
        $config->clearCache();

        $logger = $config->logger;
        if (is_object($logger)) {
            $logger = $logger->toArray();
            if ($logger['priority'] < 8) {
                $logger['priority'] = 8;
            } else {
                static::markTestSkipped('logger priorty required < 8');
            }
        } else {
            static::markTestSkipped('logger config required');
        }

        try {
            if (empty($confd = $config->{Tinebase_Config::CONFD_FOLDER}) || !is_dir($confd) ||
                    !file_put_contents($confd . '/unittest.inc.php', '<?php return ["unittest" => "foobar", ' .
                        '"logger" => ' . var_export($logger, true) . ' ];')) {
                static::markTestSkipped('no conf.d folder setup');
            }

            $config->clearCache();
            static::assertEquals('foobar', $config->unittest);
            static::assertEquals(8, $config->logger->priority);
            static::assertTrue(Tinebase_Core::isLogLevel(8));

        } finally {
            if (isset($confd) && !empty($confd) && is_file($confd . '/unittest.inc.php')) {
                unlink($confd . '/unittest.inc.php');
            }
            $config->clearCache();
        }
    }

    /**
     * set + get array config
     */
    public function testArrayConfig()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ALLOWEDJSONORIGINS, array("www.test.de", "www.tine20.net"));
        $this->assertEquals(array("www.test.de", "www.tine20.net"), Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDJSONORIGINS, array()));
    }

    public function testConfigStructure()
    {
        $defaultConfigFile = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20'
            . DIRECTORY_SEPARATOR . 'ExampleApplication' . DIRECTORY_SEPARATOR . 'config.inc.php';

        $this->assertFalse(file_exists($defaultConfigFile), 'test needs to be recoded because ExampleApplication default config exists');

        copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'configExampleAppTest.inc.php', $defaultConfigFile);
        $this->_filenamesToDelete[] = $defaultConfigFile;

        ExampleApplication_Config::destroyInstance();
        $exampleConfig = ExampleApplication_Config::getInstance();
        $exampleConfig->clearCache();

        $smtpStruct = $exampleConfig->{ExampleApplication_Config::EXAMPLE_MAILCONFIG}->{ExampleApplication_Config::SMTP};
        $imapStruct = $exampleConfig->{ExampleApplication_Config::EXAMPLE_MAILCONFIG}->{ExampleApplication_Config::IMAP};

        $this->assertTrue($smtpStruct instanceof Tinebase_Config_Struct);
        $this->assertTrue(is_string($smtpStruct->{ExampleApplication_Config::HOST}) && $smtpStruct->{ExampleApplication_Config::HOST} === 'localhost');
        $this->assertTrue(is_int($smtpStruct->{ExampleApplication_Config::PORT}) && ($smtpStruct->{ExampleApplication_Config::PORT} === 123 ||
                $smtpStruct->{ExampleApplication_Config::PORT} === 789));
        $this->assertTrue(is_bool($smtpStruct->{ExampleApplication_Config::SSL}) && $smtpStruct->{ExampleApplication_Config::SSL} === true);

        $this->assertTrue($imapStruct instanceof Tinebase_Config_Struct);
        $this->assertTrue(is_string($imapStruct->{ExampleApplication_Config::HOST}) && $imapStruct->{ExampleApplication_Config::HOST} === '123');
        $this->assertTrue(is_int($imapStruct->{ExampleApplication_Config::PORT}) && $imapStruct->{ExampleApplication_Config::PORT} === 999);
        $this->assertTrue(is_bool($imapStruct->{ExampleApplication_Config::SSL}) && $imapStruct->{ExampleApplication_Config::SSL} === true);

        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        $imapStruct->shooo;
    }

    public function testDbMerge()
    {
        try {
            $db = Tinebase_Core::getDb();
            Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $regConfig = Tinebase_Config::getInstance()->getClientRegistryConfig();
            $tinebaseFeatures = $regConfig->Tinebase->{Tinebase_Config::ENABLED_FEATURES}->toArray();
            static::assertEquals(6, count($tinebaseFeatures['value']), print_r($tinebaseFeatures, true));

            $db->delete(SQL_TABLE_PREFIX . 'config', $db->quoteIdentifier('application_id') . $db->quoteInto(' = ?',
                Tinebase_Core::getTinebaseId()) . ' AND ' . $db->quoteIdentifier('name') . $db->quoteInto(' = ?',
                    Tinebase_Config::ENABLED_FEATURES));
            $db->insert(SQL_TABLE_PREFIX . 'config', [
                'id'                => Tinebase_Record_Abstract::generateUID(),
                'application_id'    => Tinebase_Core::getTinebaseId(),
                'name'              => Tinebase_Config::ENABLED_FEATURES,
                'value'             => json_encode([Tinebase_Config::FEATURE_SEARCH_PATH => false])
            ]);

            Tinebase_Config::getInstance()->clearCache();

            $regConfig = Tinebase_Config::getInstance()->getClientRegistryConfig();
            $tinebaseFeatures = $regConfig->Tinebase->{Tinebase_Config::ENABLED_FEATURES}->toArray();
            static::assertGreaterThanOrEqual(6, count($tinebaseFeatures['value']), print_r($tinebaseFeatures, true));
        } finally {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
    }
}
