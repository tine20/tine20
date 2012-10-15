<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_AsyncJob
 */
class Tinebase_AsyncJobTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_AsyncJobTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test start stop job.
     */
    public function testStartStopJob()
    {
        $async = Tinebase_AsyncJob::getInstance();
        $sequence = $async->getNextSequence('Test_Job');
        $job = $async->startJob('Test_Job');
        $this->assertTrue($job instanceof Tinebase_Model_AsyncJob);
        $this->assertFalse($async->getNextSequence('Test_Job'));
        $async->finishJob($job);
        $this->assertGreaterThan($sequence, $async->getNextSequence('Test_Job'));
    }
    
    /**
     * testGetNextSequence
     */
    public function testGetNextSequence()
    {
        $async = Tinebase_AsyncJob::getInstance();
        $job = $async->startJob('Test_Job1', 5);
        sleep(3);
        $this->assertFalse($async->getNextSequence('Test_Job1'));
        $async->finishJob($job);
    }
    
    /**
     * run multiple async jobs parallel
     */
    public static function triggerAsyncEvents($numOfParallels = 5)
    {
        $cmd = realpath(__DIR__ . "/../../../tine20/tine20.php") . ' --method Tinebase.triggerAsyncEvents';
        $cmd = TestServer::assembleCliCommand($cmd);
        
        // start multiple cronruns at the same time
        // NOTE: we don't use pnctl as we don't need it here and it's not always available
        for ($i = 0; $i < 5; $i++) {
            $tempNames[] = $fileName = tempnam(Tinebase_Core::getTempDir(), 'asynctest');
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Starting async job: ' . $cmd);
            $result = exec("$cmd > $fileName 2> /dev/null &");
        }
        
        // wait for processes to complete
        for ($i = 0; $i < count($tempNames) * 5; $i++) {
            sleep(1);
            $allJobsDone = TRUE;
            foreach ($tempNames as $fileName) {
                $output = file_get_contents($fileName);
                $allJobsDone &= (bool) preg_match('/complete.$/m', $output);
            }
            
            if ($allJobsDone) {
                break;
            }
        }
        
        // cleanup
        foreach ($tempNames as $fileName) {
            //echo 'removing ' . $fileName . "\n";
            unlink($fileName);
        }
        
        if (! $allJobsDone) {
            throw new Exception('jobs did not complete');
        }
    }
}
