<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * 
 * @todo        what about the account/group tests? only test the backends or the factory as well?
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_AllTests::main');
}

class Tinebase_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All Tests');
        $suite->addTestSuite('Tinebase_Record_RecordTest');
        $suite->addTestSuite('Tinebase_Record_RecordSetTest');
        $suite->addTestSuite('Tinebase_AuthTest');
        $suite->addTestSuite('Tinebase_UserTest');
        $suite->addTestSuite('Tinebase_GroupTest');
        $suite->addTestSuite('Tinebase_ZendFilterTest');
        $suite->addTestSuite('Tinebase_ContainerTest');
        $suite->addTestSuite('Tinebase_ImageHelperTest');
        $suite->addTestSuite('Tinebase_ConfigTest');
        $suite->addTestSuite('Tinebase_CustomFieldTest');
        $suite->addTestSuite('Tinebase_PreferenceTest');
        $suite->addTestSuite('Tinebase_ApplicationTest');
        $suite->addTestSuite('Tinebase_Relation_AllTests');
        $suite->addTestSuite('Tinebase_NotesTest');
        $suite->addTestSuite('Tinebase_JsonTest');
        $suite->addTestSuite('Tinebase_TransactionManagerTest');
        $suite->addTestSuite('Tinebase_TranslationTest');
        $suite->addTestSuite('Tinebase_AsyncJobTest');
        $suite->addTestSuite('Tinebase_HelperTests');
        
        $suite->addTest(Tinebase_User_AllTests::suite());
        $suite->addTest(Tinebase_Group_AllTests::suite());
        $suite->addTest(Tinebase_Timemachine_AllTests::suite());
        $suite->addTest(Tinebase_Json_AllTests::suite());
        $suite->addTest(Tinebase_Acl_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_AllTests::main') {
    Tinebase_AllTests::main();
}
