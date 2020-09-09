<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * All Addressbook tests
 *
 * @package     Addressbook
 */
class Addressbook_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Addressbook tests');

        $suite->addTest(Addressbook_Backend_AllTests::suite());
        $suite->addTest(Addressbook_Convert_AllTests::suite());
        $suite->addTest(Addressbook_Frontend_AllTests::suite());
        $suite->addTest(Addressbook_Import_AllTests::suite());
        $suite->addTest(Addressbook_Export_AllTests::suite());

        $suite->addTestSuite(Addressbook_ControllerTest::class);
        $suite->addTestSuite(Addressbook_Controller_ListTest::class);
        $suite->addTestSuite(Addressbook_PdfTest::class);
        $suite->addTestSuite(Addressbook_JsonTest::class);
        $suite->addTestSuite(Addressbook_CliTest::class);
        $suite->addTestSuite(Addressbook_Model_ContactIdFilterTest::class);

        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
            $suite->addTestSuite(Addressbook_LdapSyncTest::class);
        }

        // TODO: enable this again, when its fast
//         $suite->addTestSuite(Addressbook_Setup_DemoDataTests::class);
        return $suite;
    }
}
