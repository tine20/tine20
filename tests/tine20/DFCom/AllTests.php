<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once 'TestHelper.php';

class DFCom_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('Tine 2.0 CFCom All Tests');
        
        $suite->addTestSuite(DFCom_HTTPAPIv1Test::class);
        $suite->addTestSuite(DFCom_JsonTest::class);
        $suite->addTestSuite(DFCom_RecordHandler_TimeAccountingTest::class);
        return $suite;
    }
}
