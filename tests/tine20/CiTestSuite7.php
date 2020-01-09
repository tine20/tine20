<?php
class CiTestSuite7
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Crm_AllTests::class);
        $suite->addTestSuite(Sales_AllTests::class);

        return $suite;
    }
}