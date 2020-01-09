<?php
class CiTestSuite2
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Addressbook_AllTests::class);
        $suite->addTestSuite(HumanResources_AllTests::class);

        return $suite;
    }
}