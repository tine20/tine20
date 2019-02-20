<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Tinebase_AllTests
 *
 * @package     Tinebase
 */
class Tinebase_AllTests
{
    /**
     * suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All Tests');

        $suite->addTestSuite(Tinebase_ActionQueue_Test::class);
        $suite->addTestSuite(Tinebase_CacheTest::class);
        $suite->addTestSuite(Tinebase_AccessLogTest::class);
        $suite->addTestSuite(Tinebase_AccountTest::class);
        $suite->addTestSuite(Tinebase_AuthTest::class);
        $suite->addTestSuite(Tinebase_CoreTest::class);
        $suite->addTestSuite(Tinebase_DateTimeTest::class);
        $suite->addTestSuite(Tinebase_ExceptionTest::class);
        $suite->addTestSuite(Tinebase_LdapTest::class);
        $suite->addTestSuite(Tinebase_ModelConfigurationTest::class);
        $suite->addTestSuite(Tinebase_UserTest::class);
        $suite->addTestSuite(Tinebase_GroupTest::class);
        $suite->addTestSuite(Tinebase_ZendFilterTest::class);
        $suite->addTestSuite(Tinebase_ContainerTest::class);
        $suite->addTestSuite(Tinebase_ContainerPersistentCacheTest::class);
        $suite->addTestSuite(Tinebase_ImageHelperTest::class);
        $suite->addTestSuite(Tinebase_ConfigTest::class);
        $suite->addTestSuite(Tinebase_CustomFieldTest::class);
        $suite->addTestSuite(Tinebase_PreferenceTest::class);
        $suite->addTestSuite(Tinebase_ApplicationTest::class);
        $suite->addTestSuite(Tinebase_Relation_AllTests::class);
        $suite->addTestSuite(Tinebase_NotesTest::class);
        $suite->addTestSuite(Tinebase_TransactionManagerTest::class);
        $suite->addTestSuite(Tinebase_TranslationTest::class);
        $suite->addTestSuite(Tinebase_HelperTests::class);
        $suite->addTestSuite(Tinebase_FileSystem_StreamWrapperTest::class);
        $suite->addTestSuite(Tinebase_FileSystem_RecordAttachmentsTest::class);
        $suite->addTestSuite(Tinebase_FileSystemTest::class);
        $suite->addTestSuite(Tinebase_ControllerTest::class);
        $suite->addTestSuite(Tinebase_MailTest::class);
        $suite->addTestSuite(Tinebase_NotificationTest::class);
        $suite->addTestSuite(Tinebase_Model_Filter_TextTest::class);
        $suite->addTestSuite(Tinebase_TagsTest::class);
        $suite->addTestSuite(Tinebase_Log_AllTests::class);
        $suite->addTestSuite(Tinebase_TempFileTest::class);
        $suite->addTestSuite(Tinebase_Server_AllTests::class);
        $suite->addTestSuite(Tinebase_LockTest::class);
        $suite->addTestSuite(Tinebase_ScheduledImportTest::class);
        $suite->addTestSuite(Tinebase_Delegators_DelegateTest::class);
        $suite->addTestSuite(Tinebase_DaemonTest::class);
        $suite->addTestSuite(Tinebase_FullTextTest::class);
        $suite->addTestSuite(Tinebase_Helper_AllTests::class);
        $suite->addTestSuite(Tinebase_Export_DocTest::class);
        $suite->addTestSuite(Tinebase_Export_XlsxTest::class);
        $suite->addTestSuite(Tinebase_AreaLockTest::class);
        $suite->addTestSuite(Tinebase_StateTest::class);
        $suite->addTestSuite(Tinebase_FilterSyncTokenTest::class);
        $suite->addTestSuite(Tinebase_Frontend_AutodiscoverTests::class);


        $suite->addTest(Tinebase_User_AllTests::suite());
        $suite->addTest(Tinebase_Group_AllTests::suite());
        $suite->addTest(Tinebase_Timemachine_AllTests::suite());
        $suite->addTest(Tinebase_Frontend_AllTests::suite());
        $suite->addTest(Tinebase_Acl_AllTests::suite());
        $suite->addTest(Tinebase_Tree_AllTests::suite());
        $suite->addTest(Tinebase_Record_AllTests::suite());
        $suite->addTest(Tinebase_WebDav_AllTests::suite());
        $suite->addTest(OpenDocument_AllTests::suite());

        return $suite;
    }
}
