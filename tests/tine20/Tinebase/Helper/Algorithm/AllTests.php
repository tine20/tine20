<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_Helper_Algorithm_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 All Helper Algorithm Tests');

        $suite->addTestSuite(Tinebase_Helper_Algorithm_TerminationDeadlineTest::class);

        return $suite;
    }
}
