<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * 
 * @todo        split this
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Auth_Abstract
 */
class Tinebase_AuthTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * @var mixed
     */
    protected $_originalBackendConfiguration = null;
    
    /**
     * @var mixed
     */
    protected $_originalBackendType = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_originalBackendConfiguration = Tinebase_Auth::getBackendConfiguration();
        $this->_originalBackendType = Tinebase_Auth::getConfiguredBackend();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // this needs to be done because Tinebase_Auth & Tinebase_Config use caching mechanisms
        Tinebase_Auth::setBackendType($this->_originalBackendType);
        Tinebase_Auth::deleteBackendConfiguration();
        Tinebase_Auth::setBackendConfiguration($this->_originalBackendConfiguration);
        Tinebase_Auth::saveBackendConfiguration();
        Tinebase_Auth::getInstance()->setBackend();
        
        parent::tearDown();
    }

    /**
     * testSaveBackendConfiguration
     */
    public function testSaveBackendConfiguration()
    {
        Tinebase_Auth::setBackendType(Tinebase_Auth::LDAP);
     
        $rawConfigBefore = Tinebase_Config::getInstance()->get(Tinebase_Config::AUTHENTICATIONBACKEND);
        $key = 'host';
        $testValue = 'phpunit-test-host2';
        Tinebase_Auth::setBackendConfiguration($testValue, $key);
        Tinebase_Auth::saveBackendConfiguration();
        $rawConfigAfter = Tinebase_Config::getInstance()->get(Tinebase_Config::AUTHENTICATIONBACKEND);
        $this->assertNotEquals($rawConfigBefore, $rawConfigAfter);
    }
    
    /**
     * 
     * testSetBackendConfiguration
     */
    public function testSetBackendConfiguration()
    {
        Tinebase_Auth::setBackendType(Tinebase_Auth::LDAP);
     
        $key = 'host';
        $testValue = 'phpunit-test-host';
        Tinebase_Auth::setBackendConfiguration($testValue, $key);
        $this->assertEquals($testValue, Tinebase_Auth::getBackendConfiguration($key));

        $testValues = array('host' => 'phpunit-test-host2',
           'username' => 'cn=testcn,ou=teestou,o=testo',
           'password' => 'secret'
        );
        Tinebase_Auth::setBackendConfiguration($testValues);
        foreach ($testValues as $key => $testValue) {
            $this->assertEquals($testValue, Tinebase_Auth::getBackendConfiguration($key));
        }
    }
    
    /**
     * testDeleteBackendConfiguration
     */
    public function testDeleteBackendConfiguration()
    {
        Tinebase_Auth::setBackendType(Tinebase_Auth::LDAP);
     
        $key = 'host';
        Tinebase_Auth::setBackendConfiguration('configured-host', $key);
        $this->assertEquals('configured-host', Tinebase_Auth::getBackendConfiguration($key, 'default-host'));
        Tinebase_Auth::deleteBackendConfiguration($key);
        $this->assertEquals('default-host', Tinebase_Auth::getBackendConfiguration($key, 'default-host'));
        
        $configOptionsCount = count(Tinebase_Auth::getBackendConfiguration());
        Tinebase_Auth::deleteBackendConfiguration('non-existing-key');
        $this->assertEquals($configOptionsCount, count(Tinebase_Auth::getBackendConfiguration()));
        
        Tinebase_Auth::setBackendConfiguration('phpunit-dummy-value', $key);
        $this->assertTrue(count(Tinebase_Auth::getBackendConfiguration()) > 0);
        Tinebase_Auth::deleteBackendConfiguration();
        $this->assertTrue(count(Tinebase_Auth::getBackendConfiguration()) == 0);
    }
    
    /**
     * testGetBackendConfigurationDefaults
     */
    public function testGetBackendConfigurationDefaults()
    {
        $defaults = Tinebase_Auth::getBackendConfigurationDefaults();
        $this->assertTrue((isset($defaults[Tinebase_Auth::SQL]) || array_key_exists(Tinebase_Auth::SQL, $defaults)));
        $this->assertTrue((isset($defaults[Tinebase_Auth::LDAP]) || array_key_exists(Tinebase_Auth::LDAP, $defaults)));
        $this->assertTrue(is_array($defaults[Tinebase_Auth::LDAP]));
        $this->assertFalse((isset($defaults['host']) || array_key_exists('host', $defaults)));
        
        $defaults = Tinebase_Auth::getBackendConfigurationDefaults(Tinebase_Auth::LDAP);
        $this->assertTrue((isset($defaults['host']) || array_key_exists('host', $defaults)));
        $this->assertFalse((isset($defaults[Tinebase_Auth::LDAP]) || array_key_exists(Tinebase_Auth::LDAP, $defaults)));
    }
    
    /**
     * test imap authentication
     */
    public function testImapAuth()
    {
        // use imap config for the auth config
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        if (empty($imapConfig)) {
             $this->markTestSkipped('No IMAP config found.');
        }
        
        $authConfig = array(
            'host'      => $imapConfig['host'],
            'port'      => $imapConfig['port'],
            'ssl'       => $imapConfig['ssl'],
            'domain'    => $imapConfig['instanceName'], // this is appended to the username when authenticating
        );
        Tinebase_Auth::setBackendType(Tinebase_Auth::IMAP);
        Tinebase_Auth::setBackendConfiguration($authConfig);
        Tinebase_Auth::saveBackendConfiguration();
        Tinebase_Auth::getInstance()->setBackend();
        
        $this->assertEquals(Tinebase_Auth::IMAP, Tinebase_Auth::getConfiguredBackend());

        $testCredentials = TestServer::getInstance()->getTestCredentials();
        
        // valid authentication
        $username = Tinebase_Core::getUser()->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP];
        $authResult = Tinebase_Auth::getInstance()->authenticate($username, $testCredentials['password']);
        $this->assertTrue($authResult->isValid(), 'could not authenticate with imap');
        
        // invalid authentication
        $authResult = Tinebase_Auth::getInstance()->authenticate($username, 'some pw');
        $this->assertFalse($authResult->isValid());
        $this->assertEquals(Tinebase_Auth::FAILURE_CREDENTIAL_INVALID, $authResult->getCode());
    }
    
    /**
     * test credential cache cleanup
     */
    public function testClearCredentialCacheTable()
    {
        // add dummy record to credential cache
        $id = Tinebase_Record_Abstract::generateUID();
        $db = Tinebase_Core::getDb();
        $oneMinuteAgo = Tinebase_DateTime::now()->subMinute(1)->format(Tinebase_Record_Abstract::ISO8601LONG);
        $data = array(
            'id'            => $id,
            'cache'         => Tinebase_Record_Abstract::generateUID(),
            'creation_time' => $oneMinuteAgo,
            'valid_until'   => $oneMinuteAgo,
        );
        $table = SQL_TABLE_PREFIX . 'credential_cache';
        Tinebase_Core::getDb()->insert($table, $data);
        
        Tinebase_Auth_CredentialCache::getInstance()->clearCacheTable();
        
        $result = $db->fetchCol('SELECT id FROM ' . $db->quoteIdentifier($table) . ' WHERE ' . $db->quoteInto($db->quoteIdentifier('valid_until') .' < ?', Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG)));
        $this->assertNotContains($id, $result);
    }

    /**
     * @see 0011366: support privacyIdea authentication
     */
    public function testMockAuthAdapter()
    {
        $authAdapter = Tinebase_Auth_Factory::factory('Mock', array(
            'url' => 'https://localhost/validate/check',
        ));
        $authAdapter->setIdentity('phil');
        $authAdapter->setCredential('phil');
        $result = $authAdapter->authenticate();
        $this->assertEquals(true, $result->isValid());
    }

    /**
     * @see 0013272: add pin column, backend and config
     */
    public function testPinAuth()
    {
        $user = Tinebase_Core::getUser();

        try {
            Tinebase_User::getInstance()->setPin($user, 'abcd1234');
            self::fail('expected exception - it is not allowed to have non-numbers in pin');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            self::assertEquals('Only numbers are allowed for PINs', $tesg->getMessage());
        }

        Tinebase_User::getInstance()->setPin($user, '1234');
        $authAdapter = Tinebase_Auth_Factory::factory(Tinebase_Auth::PIN);
        $authAdapter->setIdentity($user->accountLoginName);
        $authAdapter->setCredential('');
        $result = $authAdapter->authenticate();
        $this->assertFalse($result->isValid(), 'empty pin should always fail');

        $authAdapter->setCredential('1234');
        $result = $authAdapter->authenticate();
        $this->assertTrue($result->isValid());
    }
}
