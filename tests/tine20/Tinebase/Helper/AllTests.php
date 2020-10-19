<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_Helper_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 All Helper Algorithm Tests');

        $suite->addTestSuite(Tinebase_Helper_ZendConfigTests::class);

        $suite->addTest(Tinebase_Helper_Algorithm_AllTests::suite());

        return $suite;
    }
}
