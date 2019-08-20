<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo make testLoginAndLogout work (needs to run in separate process)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Controller
 */
class Tinebase_ControllerTest extends TestCase
{
    /**
     * controller instance
     * 
     * @var Tinebase_Controller
     */
    protected $_instance = NULL;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_instance = Tinebase_Controller::getInstance();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_Config::getInstance()->maintenanceMode = 0;
        Tinebase_TransactionManager::getInstance()->rollBack();
    }

    /**
     * testMaintenanceModeLoginFail
     *
     * @param $maintenanceModeSetting
     */
    public function testMaintenanceModeLoginFail($maintenanceModeSetting = 1)
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('FIXME: Does not work with LDAP/AD backend (full test suite run)');
        }

        Tinebase_Config::getInstance()->maintenanceMode = $maintenanceModeSetting;
        $loginName = $maintenanceModeSetting === Tinebase_Config::MAINTENANCE_MODE_ALL
            ? Tinebase_Core::getUser()->accountLoginName
            : 'sclever';

        try {
            $this->_instance->login(
                $loginName,
                Tinebase_Helper::array_value('password', TestServer::getInstance()->getTestCredentials()),
                new Tinebase_Http_Request()
            );
            $this->fail('expecting exception: Tinebase_Exception_MaintenanceMode');
        } catch (Tinebase_Exception_MaintenanceMode $temm) {
            $this->assertEquals('Installation is in maintenance mode. Please try again later', $temm->getMessage());
        }
    }

    public function testMaintenanceModeLoginFailNormal()
    {
        $this->testMaintenanceModeLoginFail(Tinebase_Config::MAINTENANCE_MODE_NORMAL);
    }

    public function testMaintenanceModeLoginFailAll()
    {
        $this->testMaintenanceModeLoginFail(Tinebase_Config::MAINTENANCE_MODE_ALL);
    }

    /**
     * testCleanupCache
     */
    public function testCleanupCache()
    {
        $this->_instance->cleanupCache(Zend_Cache::CLEANING_MODE_ALL);
        
        $cache = Tinebase_Core::getCache();
        $oldLifetime = $cache->getOption('lifetime');
        $cache->setLifetime(1);
        $cacheId = Tinebase_Helper::convertCacheId('testCleanupCache');
        $cache->save('value', $cacheId);
        sleep(2);
        
        // cleanup with CLEANING_MODE_OLD
        $this->_instance->cleanupCache();
        $cache->setLifetime($oldLifetime);
        
        $this->assertFalse($cache->load($cacheId));
        
        // check for cache files
        $config = Tinebase_Core::getConfig();
        
        if ($config->caching && $config->caching->backend == 'File' && $config->caching->path) {
            $cacheFile = $this->_lookForCacheFile($config->caching->path);
            $this->assertEquals(NULL, $cacheFile, 'found cache file: ' . $cacheFile);
        }
    }
    
    /**
     * look for cache files
     * 
     * @param string $_path
     * @param boolean $_firstLevel
     * @return string|NULL
     */
    protected function _lookForCacheFile($_path, $_firstLevel = TRUE)
    {
        foreach (new DirectoryIterator($_path) as $item) {
            if ($item->isDir() && preg_match('/^zend_cache/', $item->getFileName())) {
                //echo 'scanning ' . $item->getFileName();
                if (null !== ($result = $this->_lookForCacheFile($item->getPathname(), FALSE))) {
                    // file found in subdir
                    return $result;
                }
            } else if ($item->isFile() && ! $_firstLevel) {
                // file found
                return $item->getPathname();
            }
        }
        
        return NULL;
    }

    public function testCleanAclTables()
    {
        $db = Tinebase_Core::getDb();

        $aclTables = [
            'tree_node_acl',
            'filter_acl',
            'container_acl',
        ];
        $counts = [];
        $newCounts = [];

        foreach ($aclTables as $table) {
            $row = $db->select()->from(SQL_TABLE_PREFIX . $table, new Zend_Db_Expr('count(*)'))->query()->
                fetch(Zend_Db::FETCH_NUM);
            $counts[$table] = $row[0];
        }

        $pw = 'test7652BA';
        $account = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'tine20phpunit',
            'accountDisplayName'    => 'tine20phpunit',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@' . $this->_getMailDomain(),
        ));
        $account = Admin_Controller_User::getInstance()->create($account, $pw, $pw);
        $filter = new Tinebase_Model_PersistentFilter(
            Tinebase_Frontend_Json_PersistentFilterTest::getPersistentFilterData(/*$account*/));
        $newFilter = Tinebase_PersistentFilter::getInstance()->create($filter);
        $newFilter = Tinebase_PersistentFilter::getInstance()->get($newFilter->getId());

        foreach ($aclTables as $table) {
            $row = $db->select()->from(SQL_TABLE_PREFIX . $table, new Zend_Db_Expr('count(*)'))->query()->
                fetch(Zend_Db::FETCH_NUM);
            $newCounts[$table] = $row[0];
            static::assertGreaterThan($counts[$table], $row[0], 'no acl created in table: ' . $table);
        }

        $oldPurgeValue = Tinebase_PersistentFilter::getInstance()->purgeRecords(true);
        try {
            Tinebase_PersistentFilter::getInstance()->delete($newFilter);
            try {
                Tinebase_PersistentFilter::getInstance()->get($newFilter->getId());
                static::fail('Tinebase_PersistentFilter delete failed');
            } catch (Tinebase_Exception_NotFound $tenf) {}
            // if we have a redis action queue here, we are in deep trouble. Direct is fine though
            Tinebase_User::getInstance()->deleteUser($account->getId());

            foreach ($aclTables as $table) {
                if ('filter_acl' === $table) {
                    // constraint and delete filter => no acl anymore ... :-/
                    continue;
                }
                $row = $db->select()->from(SQL_TABLE_PREFIX . $table, new Zend_Db_Expr('count(*)'))->query()->
                    fetch(Zend_Db::FETCH_NUM);
                static::assertEquals($newCounts[$table], $row[0], 'number of acl changed in table: ' . $table);
            }

            $this->_instance->cleanAclTables();

            foreach ($aclTables as $table) {
                $row = $db->select()->from(SQL_TABLE_PREFIX . $table, new Zend_Db_Expr('count(*)'))->query()->
                    fetch(Zend_Db::FETCH_NUM);
                if ('container_acl' === $table) {
                    static::assertLessThan($newCounts[$table], $row[0], 'container_acls did not decrease');
                } else {
                    static::assertEquals($counts[$table], $row[0],
                        'number of acl not back to normal in table: ' . $table);
                }
            }
        } finally {
            Tinebase_PersistentFilter::getInstance()->purgeRecords($oldPurgeValue);
        }
    }

    /**
     * testGetStatus
     */
    public function testGetStatus()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_API_KEY, 'fooobar123');
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_INFO, true);
        
        $jsonResponse = Tinebase_Controller::getInstance()->getStatus('fooobar123');
        $status = Tinebase_Helper::jsonDecode($jsonResponse->getBody()->getContents());
        self::assertTrue(isset($status['actionqueue']));
        self::assertEquals(Tinebase_Config::getInstance()->get(
            Tinebase_Config::ACTIONQUEUE)->{Tinebase_Config::ACTIONQUEUE_ACTIVE}, $status['actionqueue']['active']);
        self::assertEquals(0, $status['actionqueue']['size']);
    }

    /**
     * testGetStatus invalid api key
     */
    public function testGetStatusInvalidApiKey()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_API_KEY, 'fooobar123');
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_INFO, true);

        static::setExpectedException(Tinebase_Exception_AccessDenied::class);
        Tinebase_Controller::getInstance()->getStatus('hahahaIhackyou!!!');
    }

    /**
     * testGetStatus no api key
     */
    public function testGetStatusNoApiKey()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_API_KEY, 'fooobar123');
        Tinebase_Config::getInstance()->set(Tinebase_Config::STATUS_INFO, true);

        static::setExpectedException(Tinebase_Exception_AccessDenied::class);
        Tinebase_Controller::getInstance()->getStatus();
    }

    public function testGetFaviconLegacy()
    {
        $icon = './images/favicon.png';
        Tinebase_Config::getInstance()->set(Tinebase_Config::BRANDING_FAVICON, $icon);
        $response = Tinebase_Controller::getInstance()->getFavicon();

        $this->assertEquals('image/png', $response->getHeader('Content-Type')[0]);
        $image = Tinebase_ImageHelper::getImageInfoFromBlob($response->getBody());
        $this->assertEquals(16, $image['width']);
        $this->assertEquals(16, $image['height']);
    }

    public function testGetFaviconSingleResize()
    {
        $icon = './images/favicon300.png';
        Tinebase_Config::getInstance()->set(Tinebase_Config::BRANDING_FAVICON, $icon);
        $response = Tinebase_Controller::getInstance()->getFavicon();

        $this->assertEquals('image/png', $response->getHeader('Content-Type')[0]);
        $image = Tinebase_ImageHelper::getImageInfoFromBlob($response->getBody());
        $this->assertEquals(16, $image['width']);
        $this->assertEquals(16, $image['height']);
    }

    public function testGetFaviconMulti()
    {
        $icons = [
             16 => './images/favicon.png',
            180 => './images/favicon300.png',
        ];
        Tinebase_Config::getInstance()->set(Tinebase_Config::BRANDING_FAVICON, $icons);

        // test exact match
        $response = Tinebase_Controller::getInstance()->getFavicon();
        $this->assertEquals('image/png', $response->getHeader('Content-Type')[0]);
        $image = Tinebase_ImageHelper::getImageInfoFromBlob($response->getBody());
        $this->assertEquals(16, $image['width']);
        $this->assertEquals(16, $image['height']);

        // test resize nearest
        $response = Tinebase_Controller::getInstance()->getFavicon(160);
        $this->assertEquals('image/png', $response->getHeader('Content-Type')[0]);
        $image = Tinebase_ImageHelper::getImageInfoFromBlob($response->getBody());
        $this->assertEquals(160, $image['width']);
        $this->assertEquals(160, $image['height']);
    }

    public function testGetFaviconSVG()
    {
        $response = Tinebase_Controller::getInstance()->getFavicon('svg');
        $this->assertEquals('image/svg+xml', $response->getHeader('Content-Type')[0]);
    }

    public function testMeasureActionQueue()
    {
        $tbApp = Tinebase_Application::getInstance();

        $microTime = microtime(true);
        $now = time();
        $tbApp->setApplicationState('Tinebase', Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION, 3601);
        $tbApp->setApplicationState('Tinebase', Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE,
            $now - 30);

        $this->_instance->measureActionQueue($microTime);
        static::assertEquals(3601, $tbApp->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION));
        static::assertEquals($now - 30, $tbApp->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE));

        $tbApp->setApplicationState('Tinebase', Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE,
            $now - 60);
        $this->_instance->measureActionQueue($microTime);
        static::assertLessThan(10, $tbApp->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION));
        static::assertLessThan(10, time() - (int)($tbApp->getApplicationState('Tinebase',
            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE)));
    }
}
