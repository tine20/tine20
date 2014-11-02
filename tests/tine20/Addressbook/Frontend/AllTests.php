<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * All Addressbook frontend tests
 * 
 * @package     Addressbook
 */
class Addressbook_Frontend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Addressbook frontend tests');
        
        $suite->addTest(Addressbook_Frontend_WebDAV_AllTests::suite());
        
        $suite->addTestSuite('Addressbook_Frontend_ActiveSyncTest');
        $suite->addTestSuite('Addressbook_Frontend_CardDAVTest');
        
        return $suite;
    }
}
