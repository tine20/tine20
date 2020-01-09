<?php
class CiTestSuite10
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Felamimail_AllTests::class);
        $suite->addTestSuite(Timetracker_AllTests::class);


        return $suite;
    }
}