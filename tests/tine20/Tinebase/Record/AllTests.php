<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_Record_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 All Record Tests');

        $suite->addTestSuite(Tinebase_Record_RecordTest::class);
        $suite->addTestSuite(Tinebase_Record_RecordSetTest::class);
        $suite->addTestSuite(Tinebase_Record_PathTest::class);
        $suite->addTestSuite(Tinebase_Record_ExpanderTest::class);

        return $suite;
    }
}
