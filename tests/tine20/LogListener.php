<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class LogListener implements PHPUnit_Framework_TestListener
{
    /**
     * start test listener: print test name to logfile
     * 
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestListener::startTest()
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test: ' . $test->getName());
    }
    
    /**
     * start test suite listener: print suite name to logfile
     * 
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestListener::startTestSuite()
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test suite: ' . $suite->getName());
    }

    /**
     * end test
     * 
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestListener::endTest()
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] End test: ' . $test->getName() . ' / Time: ' . $time);
    }
 
    /**
     * end suite
     * 
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestListener::endTestSuite()
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] End test suite: ' . $suite->getName());
    }
    
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // not used
    }
 
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        // not used
    }
 
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // not used
    }
 
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // not used
    }
}
