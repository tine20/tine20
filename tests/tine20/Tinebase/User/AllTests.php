<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';
if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_AllTests::main');
}
class Tinebase_User_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All User Tests');
        $suite->addTestSuite('Tinebase_User_SqlTest');
        // disabled user registration tests -> this is not used atm and not functional
        //$suite->addTestSuite('Tinebase_User_RegistrationTest');
        $suite->addTestSuite('Tinebase_User_ModelTest');
        $suite->addTestSuite('Tinebase_User_AbstractTest');
        
        $imapConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        if (isset($imapConfig['backend'])) {
            switch (ucfirst($imapConfig['backend'])) {
                case Tinebase_EmailUser::DBMAIL:
                    $suite->addTestSuite('Tinebase_User_EmailUser_DbmailTest');
                    break;
                case Tinebase_EmailUser::LDAP_IMAP:
                    $suite->addTestSuite('Tinebase_User_EmailUser_LdapImapTest');
                    break;
            }
        }

        $stmpConfig = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::SMTP);
        if (isset($stmpConfig['backend']) && ucfirst($stmpConfig['backend']) == Tinebase_EmailUser::POSTFIX) {
            $suite->addTestSuite('Tinebase_User_EmailUser_PostfixTest');
        }
        return $suite;
    }
}
if (PHPUnit_MAIN_METHOD == 'Tinebase_User_AllTests::main') {
    Tinebase_User_AllTests::main();
}
