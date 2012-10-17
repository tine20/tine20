<?php
/**
 * Tine 2.0
 * @package     Redis
 * @subpackage  Worker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Züleyha Toptas <z.toptas@hotmail.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';


class Tinebase_Redis_Worker_DaemonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array default daemon config
     */
    protected $_defaultConfig;
    
    /**
     * @var Zend_Config action queue config
     */
    protected $_actionQueueConfigBackup;
    
    /**
     * @var Redis
     */
    protected $_redis;
    
    /**
     * @var string path to log file
     */
    protected $_daemonLog;
    
    /**
     * @var string path to pid file
     */
    protected $_daemonPid;
    
    /**
     * @var string path to config ini
     */
    protected $_configIni;
    
    
    public function setUp()
    {
        $this->_defaultConfig = Tinebase_Redis_Worker_Daemon::getDefaultConfig();
        
        // config actionqueue
        $this->_actionQueueConfigBackup = Tinebase_Core::getConfig()->actionqueue;
        Tinebase_Core::getConfig()->actionqueue = array(
            'adapter'       => 'Redis'
        );
        
        $this->_redis = new Redis;
        $this->_redis->connect($this->_defaultConfig['host'], $this->_defaultConfig['port'], $this->_defaultConfig['timeout']);
       
        // start daemon
        $this->_daemonLog = tempnam("/tmp", "tine20daemonttestdaemonlog_");
        $this->_daemonPid = tempnam("/tmp", "tine20daemonttestdaemonpid_");
        $this->_configIni = tempnam("/tmp", "tine20daemontestconfigini_");
        file_put_contents($this->_configIni, <<<EOT
loglevel = 7
EOT
        );
        $cmd = realpath(__DIR__ . '/../../../../../tine20/Tinebase/Redis/Worker/Daemon.php') . " -v -d -p {$this->_daemonPid} --config {$this->_configIni} > {$this->_daemonLog} 2>&1 &";
        $cmd = TestServer::assembleCliCommand($cmd);
        exec($cmd);
        sleep(1);
    }

    public function tearDown()
    {
        // kill daemon
        $daemonpid = file_get_contents($this->_daemonPid);
        $killCommand = 'kill ' . $daemonpid;
        exec($killCommand);
        
        unlink($this->_daemonLog);
        unlink($this->_configIni);
        
        // restore actionqueueconfig
        Tinebase_Core::getConfig()->actionqueue = $this->_actionQueueConfigBackup;
    }

    /**
     * test whether the daemon has been started
     */
    public function testDaemonRuns()
    {
        $daemonPid = file_get_contents($this->_daemonPid);
        $this->assertTrue(!! $daemonPid, 'pid file ' . $this->_daemonPid . ' is empty');
        
        $daemonLog = file_get_contents($this->_daemonLog);
        $this->assertTrue(!! strstr($daemonLog, 'We are master. Exiting main process now...'), 'We are master. Exiting main process now...');
        $this->assertTrue(!! strstr($daemonLog, 'Starting Console_Daemon ...'), 'Starting Console_Daemon ...');
        // NOTE error free log setup contains 22 words in debug mode
        $this->assertLessThan(23, str_word_count($daemonLog), "There where errors:\n" . $daemonLog);
    }

    /**
     * test the execution of a job in dispatch mode
     */
    public function testExecuteJobDispatch()
    {
        $filepath = tempnam("/tmp", __METHOD__);
        Tinebase_ActionQueue::getInstance()->queueAction('Tinebase.testSpy', $filepath);
        sleep(3);
        
        $result = file_get_contents($filepath);
        unlink($filepath);
        
        $this->assertEquals(1, $result, file_get_contents($this->_daemonLog));

    }

    /**
     * test the execution of a job in exec mode
     */
    public function testExecuteJobExec()
    {
        $this->markTestIncomplete(
          "It's not clear how to config deamon"
        );
    }

    /**
     * test retry of a failed job
     */
    public function testExecuteRetry()
    {
        $filepath = tempnam("/tmp", __METHOD__);
        Tinebase_ActionQueue::getInstance()->queueAction('Tinebase.testSpy', $filepath, 0, 1);
        sleep(2);
        
        $result = file_get_contents($filepath);
        unlink($filepath);
        
        $this->assertEquals(2, $result, file_get_contents($this->_daemonLog));
    }
    
    /**
     * test max retry attempts must not exceed configured maxRetry
     */
    public function testExecuteMaxRetry()
    {
        $maxRetry = $this->_defaultConfig['maxRetry'];
        
        $filepath = tempnam("/tmp", __METHOD__);
        Tinebase_ActionQueue::getInstance()->queueAction('Tinebase.testSpy', $filepath, 0, $maxRetry*2);
        sleep(ceil($maxRetry/2));
        
        $daemonLog = file_get_contents($this->_daemonLog);
        $result = file_get_contents($filepath);
        unlink($filepath);
        
        $this->assertTrue(!! strstr($daemonLog, "ERR (3)"), $daemonLog);
        $this->assertTrue(!! strstr($daemonLog, "giving up after $maxRetry retries"), $daemonLog);
        $this->assertEquals($maxRetry, $result, file_get_contents($this->_daemonLog));
    }
    
    /**
     * test number of children must not exceed configured maxChilds
     */
    public function testMaxChildren()
    {
        $maxCilds = $this->_defaultConfig['maxChildren'];
        
        for ($i=0; $i<$maxCilds+1; $i++) {
            $filepath = $filepaths[] = tempnam("/tmp", __METHOD__);
            Tinebase_ActionQueue::getInstance()->queueAction('Tinebase.testSpy', $filepath, 10);
        }
        sleep(5);
        
        // collect states after 5 seconds of execution
        foreach($filepaths as $i => $filepath) {
            $counter[$i] = (int) file_get_contents($filepath);
        }
        
        // wait for children to finish
        sleep(20);
        $daemonLog = file_get_contents($this->_daemonLog);
        
        foreach($filepaths as $i => $filepath) {
            unlink($filepath);
            if ($i < $maxCilds) {
                $this->assertEquals(1, $counter[$i], "$i was not executed but should be \n$daemonLog");
            } else {
                $this->assertEquals(0, $counter[$i], "$i was executed but shouldn't \n$daemonLog");
            }
        }
    }
}