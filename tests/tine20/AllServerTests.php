<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

if (!Tinebase_Model_Filter_FilterGroup::$beStrict) {
    throw new Exception('unittests need to set Tinebase_Model_Filter_FilterGroup::$beStrict');
}
/**
 * all server tests
 * 
 * @package     Tinebase
 */
class AllServerTests
{
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 all server tests');
        
        $suite->addTestSuite(ActiveSync_Server_HttpTests::class);
        $suite->addTestSuite(Tinebase_ControllerServerTest::class);
        $suite->addTestSuite(Tinebase_Server_WebDAVTests::class);
        $suite->addTestSuite(Tinebase_Server_JsonTests::class);
        $suite->addTestSuite(Tinebase_Server_HttpTests::class);
        $suite->addTestSuite(Tinebase_Server_RoutingTests::class);

        return $suite;
    }
}
