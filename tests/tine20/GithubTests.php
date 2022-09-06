<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * @package     Tinebase
 */
class GithubTests
{
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('tine GitHub Action Smoke Testing');

        // deactivate nominatim on github
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_NOMINATIM_SERVICE, false);

        // addressbook
        $suite->addTestSuite(Addressbook_Frontend_JsonTest::class);
        $suite->addTest(Addressbook_Frontend_AllTests::suite());
        $suite->addTest(Addressbook_Convert_Contact_VCard_AllTests::suite());

        // calendar
        $suite->addTest(Calendar_Frontend_CalDAV_AllTests::suite());
        $suite->addTest(Calendar_Frontend_WebDAV_AllTests::suite());
        $suite->addTestSuite(Calendar_Frontend_ActiveSyncTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAVTest::class);
        $suite->addTestSuite(Calendar_Frontend_Json_PollTest::class);
        $suite->addTestSuite(Calendar_Frontend_Json_ResourceTest::class);
        $suite->addTestSuite(Calendar_Frontend_PollRoutingTest::class);
        $suite->addTestSuite(Calendar_Frontend_CliTest::class);

        // some more json tests
        $suite->addTestSuite(Timetracker_JsonTest::class);
        $suite->addTestSuite(Tinebase_Frontend_JsonTest::class);

        return $suite;
    }
}
