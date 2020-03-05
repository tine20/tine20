<?php
class CiTestSuite8
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Scheduler_AllTests::class);

        return $suite;
    }
}
