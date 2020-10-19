<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class LogListener implements \PHPUnit\Framework\TestListener
{
    /**
     * A test started.
     *
     * @param \PHPUnit\Framework\Test $test
     */
    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test: ' . $test->getName());
    }

    /**
     * A test suite started.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] Starting test suite: ' . $suite->getName());
    }

    /**
     * A test ended.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param float                  $time
     */
    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] End test: ' . $test->getName() . ' / Time: ' . $time);
    }

    /**
     * A test suite ended.
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' [PHPUnit] End test suite: ' . $suite->getName());
    }
    
    public function addError(\PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        // not used
    }
 
    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        // not used
    }
 
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        // not used
    }
 
    public function addSkippedTest(\PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        // not used
    }

    /**
     * Risky test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        // not used
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        // TODO: Implement addWarning() method.
    }
}
