<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Calendar_Import_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Calendar All Tests Import');
        $suite->addTestSuite('Calendar_Import_DemoDataTest');
        $suite->addTestSuite('Calendar_Import_CalDAVTest');
        $suite->addTestSuite('Calendar_Import_ICalTest');
        return $suite;
    }
}

