<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Sipgate_AllTests::main');
}

class Sipgate_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Sipgate All Tests');
        $suite->addTestSuite('Sipgate_JsonTest');
        $suite->addTestSuite('Sipgate_ControllerTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Sipgate_AllTests::main') {
    Sipgate_AllTests::main();
}
