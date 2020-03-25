<?php
class CiTestSuite5
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(CoreData_AllTests::class);
        $suite->addTestSuite(Phone_AllTests::class);
        $suite->addTestSuite(Courses_AllTests::class);
        $suite->addTestSuite(Felamimail_AllTests::class);
        $suite->addTestSuite(Timetracker_AllTests::class);

        return $suite;
    }
}