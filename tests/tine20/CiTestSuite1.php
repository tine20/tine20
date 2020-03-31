<?php
class CiTestSuite1
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 all server tests');

        $suite->addTestSuite(ActiveSync_AllTests::class);
        $suite->addTestSuite(Filemanager_AllTests::class);
        $suite->addTestSuite(Voipmanager_AllTests::class);
        $suite->addTestSuite(Tinebase_AllTests::class);

        return $suite;
    }
}