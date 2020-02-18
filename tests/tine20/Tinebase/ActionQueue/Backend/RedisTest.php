<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Action Queue Redis Backend
 */
class Tinebase_ActionQueue_Backend_RedisTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_ActionQueue_Backend_Redis
     */
    protected $_uit = null;

    protected $_redis = null;


    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();

        $redis = Tinebase_Config::getInstance()->{Tinebase_Config::CACHE}->redis;

        if (!$redis || !$redis->host || !$redis->port) {
            static::markTestSkipped('no redis config found');
        }

        $this->_uit = new Tinebase_ActionQueue_Backend_Redis([
            'queueName' => 'unitTestQueue',
            'host'      => $redis->host,
            'port'      => $redis->port,
        ]);

        $this->_redis = new Redis();
        $this->_redis->connect($redis->host, $redis->port);
        foreach ($this->_redis->keys('unitTestQueue*') as $key) {
            $this->_redis->del($key);
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->_redis->keys('unitTestQueue*') as $key) {
            $this->_redis->del($key);
        }
        $this->_redis->close();
    }

    public function testSend()
    {
        $msg = 'unittestMsg';
        $jobId = $this->_uit->send($msg);
        static::assertSame(1, $this->_uit->getQueueSize());
        static::assertSame(2, count($this->_redis->keys('unitTestQueue*')));

        static::assertSame($jobId, $this->_uit->peekJobId());
        static::assertSame($jobId, $this->_uit->waitForJob());
        static::assertSame($msg, $this->_uit->receive($jobId));
        static::assertFalse($this->_uit->peekJobId());

        $this->_uit->delete($jobId);
        static::assertSame(0, count($this->_redis->keys('unitTestQueue*')));
    }
}
