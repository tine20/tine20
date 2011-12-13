<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_Convert_Contact_VCard_AllTests::main');
}

class Addressbook_Convert_Contact_VCard_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook All Import Vcard Tests');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_GenericTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_MacOSXTest');
        $suite->addTestSuite('Addressbook_Convert_Contact_VCard_SogoTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Addressbook_Convert_Contact_VCard_AllTests::main') {
    Addressbook_Convert_Contact_VCard_AllTests::main();
}
