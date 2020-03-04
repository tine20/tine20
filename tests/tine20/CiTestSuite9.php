<?php
class CiTestSuite9
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Tasks_AllTests::class);
        $suite->addTestSuite(SimpleFAQ_AllTests::class);
        $suite->addTestSuite(Projects_AllTests::class);

        return $suite;
    }
}