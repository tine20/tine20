<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * All Felamimail tests
 * 
 * @package     Felamimail
 */
class Felamimail_AllTests
{
    /**
     * run Felamimail tests
     *
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    /**
     * get all Felamimail test suites 
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Felamimail tests');
        
        // only call Felamimail tests if imap is configured in config.inc.php
        if (TestServer::isEmailSystemAccountConfigured()) {
            $suite->addTestSuite(Felamimail_Controller_Cache_MessageTest::class);
            $suite->addTestSuite(Felamimail_Frontend_ActiveSyncTest::class);
            $suite->addTestSuite(Felamimail_Frontend_JsonTest::class);
            $suite->addTestSuite(Felamimail_Controller_FolderTest::class);
            $suite->addTestSuite(Felamimail_Controller_MessageTest::class);
            $suite->addTestSuite(Felamimail_Controller_AccountTest::class);
            $suite->addTestSuite(Felamimail_Controller_SieveTest::class);
            $suite->addTestSuite(Felamimail_Model_MessageTest::class);
            $suite->addTestSuite(Felamimail_Model_AccountTest::class);
        }
        
        $suite->addTestSuite('Felamimail_Frontend_WebDAVTest');
        $suite->addTestSuite('Felamimail_Sieve_Backend_ScriptTest');
        
        return $suite;
    }
}
