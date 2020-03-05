<?php
class CiTestSuite6
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(Tinebase_AllTests::class);

        return $suite;
    }
}