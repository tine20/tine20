<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $suite->addTestSuite('Tinebase_User_LdapTest');
        $suite->addTestSuite('Tinebase_User_Plugin_SambaTest');
        // disabled user registration tests -> this is not used atm and not functional
        //$suite->addTestSuite('Tinebase_User_RegistrationTest');
        $suite->addTestSuite('Tinebase_User_ModelTest');
        $suite->addTestSuite('Tinebase_User_AbstractTest');
        
        $suite->addTestSuite('Tinebase_User_EmailUser_AllTests');
        return $suite;
    }
}
