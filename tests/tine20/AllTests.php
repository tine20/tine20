<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id$
 */
/**
 * Test helper
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';


if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 All Tests');
        
        $suite->addTest(Tinebase_AllTests::suite());
        $suite->addTest(Addressbook_AllTests::suite());
        $suite->addTest(Admin_AllTests::suite());
        // only call Felamimail tests if imap is configured in config.inc.php
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (! empty($imapConfig) && array_key_exists('useSystemAccount', $imapConfig) && $imapConfig['useSystemAccount']) {
            $suite->addTest(Felamimail_AllTests::suite());
        }
        $suite->addTest(Crm_AllTests::suite());
        $suite->addTest(Tasks_AllTests::suite());
        $suite->addTest(Voipmanager_AllTests::suite());
        $suite->addTest(Phone_AllTests::suite());
        $suite->addTest(Sales_AllTests::suite());
        $suite->addTest(Timetracker_AllTests::suite());
        $suite->addTest(Courses_AllTests::suite());
        $suite->addTest(Calendar_AllTests::suite());
        $suite->addTest(ActiveSync_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}