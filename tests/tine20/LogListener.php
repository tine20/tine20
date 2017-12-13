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
     * A test started.
     *
     * @param PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test: ' . $test->getName());
    }

    /**
     * A test suite started.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test suite: ' . $suite->getName());
    }

    /**
     * A test ended.
     *
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] End test: ' . $test->getName() . ' / Time: ' . $time);
    }

    /**
     * A test suite ended.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
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

    /**
     * Risky test.
     *
     * @param PHPUnit_Framework_Test $test
     * @param Exception $e
     * @param float $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // not used
    }
}
