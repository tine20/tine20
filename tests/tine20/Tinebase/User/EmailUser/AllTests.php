<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_User_EmailUser_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All EmailUser Tests');

        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_DbmailTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_DovecotTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_LdapDbmailSchemaTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Imap_CyrusTest');
        
        $suite->addTestSuite('Tinebase_User_EmailUser_Smtp_PostfixTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Smtp_LdapDbmailSchemaTest');
        $suite->addTestSuite('Tinebase_User_EmailUser_Smtp_LdapMailSchemaTest');
        
        return $suite;
    }
}
