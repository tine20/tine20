<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Crm_AllTests::main');
}

class Crm_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm All Tests');
        $suite->addTest(Crm_Backend_AllTests::suite());
        $suite->addTestSuite('Crm_ControllerTest');
        $suite->addTestSuite('Crm_JsonTest');
        $suite->addTestSuite('Crm_Export_PdfTest');
        $suite->addTestSuite('Crm_Export_CsvTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Crm_AllTests::main') {
    Crm_AllTests::main();
}
