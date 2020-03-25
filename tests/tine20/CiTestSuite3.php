<?php
class CiTestSuite3
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Admin_AllTests::class);
        $suite->addTestSuite(Inventory_AllTests::class);
        $suite->addTestSuite(Zend_AllTests::class);
        $suite->addTestSuite(Events_AllTests::class);
        $suite->addTestSuite(Scheduler_AllTests::class);

        return $suite;
    }
}