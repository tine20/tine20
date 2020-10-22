<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * All Calendar frontend tests
 * 
 * @package     Calendar
 */
class Calendar_Frontend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('All Calendar frontend tests');
        
        $suite->addTest(Calendar_Frontend_CalDAV_AllTests::suite());
        $suite->addTest(Calendar_Frontend_WebDAV_AllTests::suite());
        
        $suite->addTestSuite(Calendar_Frontend_ActiveSyncTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAVTest::class);
        $suite->addTestSuite(Calendar_Frontend_iMIPTest::class);
        $suite->addTestSuite(Calendar_Frontend_Json_PollTest::class);
        $suite->addTestSuite(Calendar_Frontend_Json_ResourceTest::class);
        $suite->addTestSuite(Calendar_Frontend_PollRoutingTest::class);
        $suite->addTestSuite(Calendar_Frontend_CliTest::class);

        return $suite;
    }
}
