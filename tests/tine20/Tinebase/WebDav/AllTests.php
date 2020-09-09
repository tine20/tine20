<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Tinebase_WebDav_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All WebDAV Tests');
        $suite->addTestSuite(Tinebase_WebDav_Plugin_ACLTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_ExpandedPropertiesReportTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_InverseTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_OwnCloudTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_PrincipalSearchTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_PropfindTest::class);
        $suite->addTestSuite(Tinebase_WebDav_Plugin_SyncTokenTest::class);
        $suite->addTestSuite(Tinebase_WebDav_PrincipalBackendTest::class);
        $suite->addTestSuite(Tinebase_WebDav_RootTest::class);

        return $suite;
    }
}
