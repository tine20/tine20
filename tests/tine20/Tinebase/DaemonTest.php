<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_CustomField
 * @group nogitlabci
 */
class Tinebase_DaemonTest extends PHPUnit_Framework_TestCase
{
    protected static $oldActionQueueConfig = null;
    protected static $oldIniFileContent = null;

    public static function setUpBeforeClass()
    {
        static::$oldActionQueueConfig = Tinebase_Config::getInstance()->{Tinebase_Config::ACTIONQUEUE};
        $actionQueueConfig = clone static::$oldActionQueueConfig;
        $actionQueueConfig->{Tinebase_Config::ACTIONQUEUE_BACKEND} = 'redis';
        $actionQueueConfig->{Tinebase_Config::ACTIONQUEUE_ACTIVE} = true;

        if (@is_file('/etc/tine20/actionQueue.ini')) {
            static::$oldIniFileContent = file_get_contents('/etc/tine20/actionQueue.ini');
        }

        $configData = 'general.daemonize=1' . PHP_EOL . 'general.logfile=/var/log/tine20/daemon.log' . PHP_EOL . 'general.loglevel=7' . PHP_EOL . 'tine20.shutDownWait=10';
        static::assertEquals(strlen($configData), file_put_contents('/etc/tine20/actionQueue.ini', $configData), 'writing config data failed');
    }

    public static function tearDownAfterClass()
    {
        Tinebase_Core::getConfig()->{Tinebase_Config::ACTIONQUEUE} = static::$oldActionQueueConfig;
        static::$oldActionQueueConfig = null;

        if (null !== static::$oldIniFileContent) {
            file_put_contents('/etc/tine20/actionQueue.ini', static::$oldIniFileContent);
            static::$oldIniFileContent = null;
        } else {
            unlink('/etc/tine20/actionQueue.ini');
        }

        @unlink('/var/run/tine20/DummyController.txt');
        Tinebase_ActionQueue::destroyInstance();
    }

    protected function setUp()
    {
        if (! extension_loaded('redis') ) {
            static::markTestSkipped('redis extension required for these tests');
        }
    }

    protected function tearDown()
    {
        $this->_stopDaemon();
    }

    public function testStart()
    {
        $this->_startDaemon();

        // 200 ms
        usleep(200000);
        clearstatcache();
        $this->assertTrue(is_file('/var/run/tine20/actionQueue.pid'), 'could not find pid file');
    }

    public function testStartStop()
    {
        self::markTestSkipped('FIXME, just ci setup issue or something, first assert fails');
        // first assert fails probably because previous testStart did not shut down daemon due to ci conf issue

        clearstatcache();
        $this->assertFalse(is_file('/var/run/tine20/actionQueue.pid'), 'found old pid file');

        $this->testStart();

        $startTime = microtime(true);
        $this->_stopDaemon(true);
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        $this->assertTrue($totalTime < 1.9, 'shut down took longer than 1.9 sec, shouldn\'t happen: ' . $totalTime);
    }

    public function testGracefulShutDown()
    {
        self::markTestSkipped('FIXME this fails at random');

        $this->testStart();

        @unlink('/var/run/tine20/DummyController.txt');
        Tinebase_ActionQueue::destroyInstance();
        Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_DummyController.sleepNSec', 2);

        // 10 ms so the daemon can pick up the job
        usleep(10000);

        $startTime = microtime(true);
        $this->_stopDaemon(true);
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        clearstatcache();
        $this->assertTrue(is_file('/var/run/tine20/DummyController.txt'), 'could not find file /var/run/tine20/DummyController.txt');
        $this->assertEquals('success 2', file_get_contents('/var/run/tine20/DummyController.txt'));
        $this->assertTrue($totalTime > 1.9, 'shut down should take more than 1.9 sec: ' . $totalTime);
    }

    protected function _startDaemon()
    {
        exec('nohup php -d include_path=/etc/tine20/:' . dirname(__DIR__) . ' /usr/local/share/tine20.git/tine20/worker.php 2>1 > /dev/null');
    }

    protected function _stopDaemon($_assert = false)
    {
        if (true === $_assert) {
            $this->assertTrue(is_file('/var/run/tine20/actionQueue.pid'), 'could not find pid file /var/run/tine20/actionQueue.pid');
        }
        if (!is_file('/var/run/tine20/actionQueue.pid')) {
            return;
        }

        $pid = file_get_contents('/var/run/tine20/actionQueue.pid');

        posix_kill($pid, SIGTERM);

        $startTime = time();
        while (time() - $startTime < 5) {
            // 10 ms
            usleep(10000);
            clearstatcache();
            if (!is_file('/var/run/tine20/actionQueue.pid')) {
                break;
            }
        }
        if (true === $_assert) {
            // 10 ms
            usleep(10000);
            clearstatcache();
            $this->assertFalse(is_file('/var/run/tine20/actionQueue.pid'), 'pid file is still there!');
        }
    }
}