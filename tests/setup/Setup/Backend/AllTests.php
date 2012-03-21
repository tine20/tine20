<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_Backend_AllTests::main');
}

class Setup_Backend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup All Backend Tests');
        $suite->addTestSuite('Setup_Backend_MysqlTest');
        $suite->addTestSuite('Setup_Backend_Schema_AllTests');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Setup_Backend_AllTests::main') {
    Setup_Backend_AllTests::main();
}
