<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:AllTests.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        activate all test suites
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_AllTests::main');
}

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
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail All Tests');
        
        // only call Felamimail tests if imap is configured in config.inc.php
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (! empty($imapConfig) && array_key_exists('useSystemAccount', $imapConfig) && $imapConfig['useSystemAccount']) {
            $suite->addTestSuite('Felamimail_Controller_Cache_MessageTest');
            #$suite->addTestSuite('Felamimail_JsonTest');
            $suite->addTestSuite('Felamimail_Controller_FolderTest');
            $suite->addTestSuite('Felamimail_Controller_MessageTest');
            $suite->addTestSuite('Felamimail_Controller_AccountTest');
        }
        $suite->addTestSuite('Felamimail_Sieve_ScriptTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Felamimail_AllTests::main') {
    Felamimail_AllTests::main();
}
