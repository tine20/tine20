<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_AllTests::main');
}

class Timetracker_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker All Tests');
        $suite->addTestSuite('Timetracker_JsonTest');
        $suite->addTestSuite('Timetracker_ControllerTest');
        $suite->addTestSuite('Timetracker_ExportTest');
        $suite->addTestSuite('Timetracker_FilterTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Timetracker_AllTests::main') {
    Timetracker_AllTests::main();
}
