<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Cache
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Auth_Abstract
 */
class Tinebase_CacheTest extends TestCase
{

    public function testRedisCacheClear()
    {
        $cache = Tinebase_Core::getCache();
        if (!$cache->getBackend() instanceof Zend_Cache_Backend_Redis) {
            static::markTestSkipped('this test is only for Redis backends');
        }
        $config = Tinebase_Config::getInstance();
        $host = $config->caching->host ? $config->caching->host :
            ($config->caching->redis && $config->caching->redis->host ? $config->caching->redis->host : 'localhost');
        $port = $config->caching->port ? $config->caching->port :
            ($config->caching->redis && $config->caching->redis->port ? $config->caching->redis->port : 6379);
        $redis = new Redis();
        $redis->connect($host, $port, 3);
        $redis->set('a', 'b');
        if ($config->session && $config->session->backend && ucfirst($config->session->backend) === 'Redis') {
            // otherwise the session data is not yet in redis and below we will not find *_SESSION_* there
            session_write_close();
        }

        $oldKeyCount = count($redis->keys('*'));

        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);

        $newKeyCount = count($redis->keys('*'));

        static::assertLessThan($oldKeyCount, $newKeyCount, 'no cache keys removed');
        static::assertEquals('b', $redis->get('a'), 'key "a" should not be affected');

        if ($config->session && $config->session->backend && ucfirst($config->session->backend) === 'Redis') {
            static::assertGreaterThan(0, count($redis->keys('*_SESSION_*')), 'no session keys found after cache clean');
        }

        $redis->close();
    }
}