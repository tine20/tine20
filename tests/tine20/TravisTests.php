<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * @package     Tinebase
 */
class TravisTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Travis Smoke Testing');

        // addressbook
        $suite->addTestSuite(Addressbook_JsonTest::class);
        $suite->addTest(Addressbook_Frontend_AllTests::suite());
        $suite->addTest(Addressbook_Convert_Contact_VCard_AllTests::suite());

        // calendar
        $suite->addTestSuite(Calendar_AllTests::suite());

        // some more json tests
        $suite->addTestSuite(Timetracker_JsonTest::class);
        $suite->addTestSuite(Admin_Frontend_JsonTest::class);
        $suite->addTestSuite(Tinebase_Frontend_JsonTest::class);
        $suite->addTestSuite(CoreData_JsonTest::class);

        return $suite;
    }
}
