<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: AllTests.php 2833 2008-06-13 09:43:03Z nelius_weiss $
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Log_AllTests::main');
}

class Tinebase_Log_AllTests
{
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(self::suite(), $testArguments);
    }
    
    public static function suite() 
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All Log Tests');
        $suite->addTestSuite('Tinebase_Log_Filter_FilterTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_Log_AllTests::main') {
    Tinebase_Log_AllTests::main();
}