<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: AllTests.php 1536 2008-03-28 19:13:57Z lkneschke $
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_AllTests::main');
}

class Setup_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup All Tests');
        $suite->addTest(Setup_Backend_AllTests::suite());
        //$suite->addTestSuite('Setup_ControllerTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Setup_AllTests::main') {
    Setup_AllTests::main();
}
