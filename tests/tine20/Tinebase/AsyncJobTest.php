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
        $this->assertGreaterThan($sequence + 1, $async->getNextSequence('Test_Job'));
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
}
