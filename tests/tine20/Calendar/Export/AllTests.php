<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schhüle <p.schuele@metaways.de>
 */
class Calendar_Export_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Calendar All Tests Export');
        $suite->addTestSuite(Calendar_Export_ContainerCsvTest::class);
        $suite->addTestSuite(Calendar_Export_DocTest::class);
        $suite->addTestSuite(Calendar_Export_OdsTests::class);
        $suite->addTestSuite(Calendar_Export_ResourceCsvTest::class);
        $suite->addTestSuite(Calendar_Export_VCalendarReportTest::class);
        $suite->addTestSuite(Calendar_Export_VCalendarTest::class);

        return $suite;
    }
}

